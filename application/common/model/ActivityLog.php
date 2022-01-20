<?php
namespace app\common\model;

Class ActivityLog extends Base
{
    //价格字段 预处理
    // public function getPriceAttr($value){
    //     return number_format($value/100,2,'.','');
    // }

    public $page = '';//分页变量
    public $count = '';//数据总数
    //获取列表
    public function getList($get,$activityName){
        //初始判断条件
        $where = ['l.is_delete'=>0];
        //会员名
        if(!empty($get['name']))
            $where['u.nickname'] = ['like','%'.$get['name'].'%'];
        //手机号
        if(!empty($get['tel']))
            $where['u.phone'] = $get['tel'];
        //订单号
        if(!empty($get['order_sn']))
            $where['o.order_sn'] = $get['order_sn'];
        //会员id
        if(!empty($get['uid']))
            $where['l.uid'] = $get['uid'];
        //时间
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['o.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['o.add_time'] = ['gt',strtotime($get['start'])];
            }
        }

        $field = 'l.id,l.aid,l.price,l.partake,l.miao,l.num,l.nums,o.add_time,l.end_time,o.order_sn,u.nickname as name,u.phone as tel';
        $list = $this->alias('l')
            ->join('user u','l.uid=u.id','left')
            ->join('order o','l.oid=o.id','left')
            ->where($where)->field($field)->order('l.id desc')
            ->paginate(20,false,array('query'=>$get));
        if($list){
            $model = new User();
            foreach ($list as &$v){
                $partake = json_decode($v['partake'],true);
                if($partake){
                    foreach ($partake as $key=>$val){
                        $val['uid'] = $model->getName($val['uid']);//获取会员名称
                        $partake[$key] = $val;
                    }
                }
                $v->activityName = $activityName[$v->aid] ?? '无此活动名称';
                $v->activityName = emojiDecode($v->activityName);
                $v['partake'] = $partake;
            }unset($v);
        }

        $this->page = $list->render();
        $this->count = $this->alias('l') ->join('user u','l.uid=u.id','left')
            ->join('order o','l.oid=o.id','left')->where($where)->count();
        return $list;
    }
}