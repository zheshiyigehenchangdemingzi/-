<?php
namespace app\common\model;

Class District extends Base
{
    //获取指定区域 pid默认全国
    public function getDistrict($pid=100000){
        return $this->where(['pid'=>$pid])->field('id code,name')->select();
    }

    //获取地区名字
    public function getName($id){
        return $this->where(['id'=>$id])->value('name');
    }
}