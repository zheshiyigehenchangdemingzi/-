<?php
namespace app\common\model;
use Think\Db;

Class MiaoLog extends Base
{
    //获取用户与佣金键值对
    public function getReward($orderId){
        return $this->where(['oid'=>$orderId])->column('uid,reward');
    }

    //修改喵呗状态
    public function editStatus($orderId,$status=1){
        $this->allowField(true)->isUpdate(true)->save(['status'=>$status],['oid'=>$orderId]);
    }

    public $page = '';//分页数据
    public $count = '';//数据总数
    public function getList($get){
        $where = ['m.is_delete'=>0];
        if(!empty($get['uid']))
            $where['m.uid'] = $get['uid'];
        if(!empty($get['type'])){
            if($get['type']==4) $where['m.type'] = ['>=',$get['type']];
            else $where['m.type'] = $get['type'];
        }
        if(!empty($get['status']))
            $where['m.status'] = $get['status'];
        if(!empty($get['uname']))
            $where['u.nickname'] = ['like','%'.$get['uname'].'%'];
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            $where['m.add_time'] = ['gt',$start];
        }
        if(!empty($get['end'])){
            $end = strtotime($get['end']."+1 day");
            if(!empty($start))
                $where['m.add_time']  =  ['between',[$start,$end]];
            else
                $where['m.add_time'] = ['lt',$end];
        }
        $field = 'm.type,m.order_type,m.oid,m.reward,m.status,m.add_time,m.end_time,u.id,u.nickname';
        $list = $this->alias('m')->join('user u','u.id=m.uid','left')->where($where)->field($field)->order('m.id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->alias('m')->join('user u','u.id=m.uid','left')->where($where)->count();
        $sum = $this->alias('m')->join('user u','u.id=m.uid','left')->where($where)->sum('reward');
        $list = !empty($list)?$list->toArray():[];
        if(!empty($list['data'])){
            foreach($list['data'] as $k=>$v){
                $list['data'][$k]['reward'] = number_format($v['reward']/100,2,'.','');
                if($v['type'] > 4) $list['data'][$k]['type'] = 4;
            }
        }
        $list['sum'] = number_format($sum/100,2,'.','');
        return $list;
    }

    /*
     * 获取收入and支出记录
     */
    public function getListAndCash($get){
        $where = ['uid'=>$get['id']];
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            $where['add_time'] = ['gt',$start];
        }
        if(!empty($get['end'])){
            $end = strtotime($get['end']."+1 day");
            if(!empty($start))
                $where['add_time']  =  ['between',[$start,$end]];
            else
                $where['add_time'] = ['lt',$end];
        }
        //bill_type 1为收入记录，2为兑换记录; type 0为兑换记录，其它对应收入记录type字段
        if(!empty($get['bill_type']) && $get['bill_type']==2 || isset( $get['type'])&&$get['type']==='0')
            $where['type'] = 0;
        if(!empty($get['type']))
            $where['type'] = $get['type'];
        $field = '1 as bill_type,type,reward,status,add_time';
        $miao = $this->where($where)->field($field)->buildSql();//喵呗收入记录
        if((!empty($get['bill_type']) && $get['bill_type']==1) || !empty($get['type']) )
            $where['type'] = 0;
        else
            $where['type'] = 1;
        $cash = Db::name('user_cash')->where($where)->field('2 as bill_type,0 as type,gold as reward,status+10 status,add_time')->union([$miao])->buildSql();//喵呗兑换记录
        $list = Db::table($cash)->alias('union')->order('add_time desc')->paginate(20,false,array('query'=>$get));
        return $list;
    }
}