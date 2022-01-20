<?php
namespace app\common\model;

Class OrderGoods extends Base
{
    //数据预处理
    public function getPriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getCostAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getFreightAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getVolumeAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getpurchaseMoneyAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getCouponMoneyAttr($value)
    {
        return number_format($value/100,2,'.','');
    }
}