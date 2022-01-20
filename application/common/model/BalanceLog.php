<?php
namespace app\common\model;

use think\Db;

Class BalanceLog extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数
    //获取列表
    public function getList($get){
        if(!empty($get['id'])){
            $where['b.uid'] = $get['id'];
            $where1['uid'] = $get['id'];
        }
        $where['b.is_delete'] = 0;
        $where1['is_delete'] = 0;
        /*$where = ['uid'=>$get['id']];
        $field = 'type,num,is_sys,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));*/
        $field1 = "b.uid,CONCAT(CASE  WHEN b.type=1 THEN '充值' WHEN b.type=2 THEN '消费' ELSE '退款' END,IF(o.order_sn,CONCAT(' (订单号:',o.order_sn,')'),'')) as type_c,b.type,b.num,3 as status,IF(b.is_sys=1,1,0) as is_sys,b.add_time";
        $balances = $this->alias('b')->join('order o','o.id=b.relation_id','left')->field($field1)->where($where)->buildSql();
        $ucash = Db::name('user_cash')->where($where1)->field('uid,IF(type=1,"喵呗兑换余额","余额提现") as type_c,type,gold as num,status,0 as is_sys,add_time')->union([$balances],true)->buildSql();
        $item = Db::table($ucash)->alias('union')->order('add_time desc')->paginate(20, false, array('query'=>$get));
        $this->page = $item->render();
        $this->count = $this->alias('b')->where($where)->count();
        $list = empty($item) ? array():$item->toArray();
        if(!empty($list['data'])){
            foreach($list['data'] as $k=>$v)
                $list['data'][$k]['num'] = number_format($v['num']/100,2,'.','');
        }

        return $list['data'];
    }
}