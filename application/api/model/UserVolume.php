<?php
namespace app\api\model;
use app\common\model\Base;

Class UserVolume extends Base
{
    //数据预处理
    public function getPriceAttr($value){
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getBalanceAttr($value){
        return number_format($value/100,2,'.','');
    }
}