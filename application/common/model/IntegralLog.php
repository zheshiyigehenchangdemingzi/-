<?php
namespace app\common\model;

Class IntegralLog extends Base
{
    //获取用户与佣金键值对
    public function getReward($orderId){
        return $this->where(['oid'=>$orderId])->column('uid,reward');
    }

    public function editStatus($orderId,$status=1){
        $this->allowField(true)->isUpdate(true)->save(['status'=>$status],['oid'=>$orderId]);
    }
}