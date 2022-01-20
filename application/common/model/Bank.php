<?php
namespace app\common\model;

Class Bank extends Base
{
    //获取银行名称
    public function getName($id){
        return $this->where(['id'=>$id])->value('name');
    }
}