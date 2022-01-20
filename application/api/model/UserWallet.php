<?php
namespace app\api\model;
use app\common\model\Base;

Class UserWallet extends Base
{
    public function incReward($uid,$field,$int=1){
        $this->where(['uid'=>$uid])->setInc($field,$int);
    }

    public function decReward($uid,$field,$int=1){
        $this->where(['uid'=>$uid])->setDec($field,$int);
    }

    //获取单一信息
    public function getField($uid,$field){
        return $this->where(['uid'=>$uid])->value($field);
    }
}