<?php
namespace app\common\model;

Class OrderInfo extends Base
{
    //数据预处理
    public function getpurchaseMoneyAttr($value)
    {
        return number_format($value/100,2,'.','');
    }
}