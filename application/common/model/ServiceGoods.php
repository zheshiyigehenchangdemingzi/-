<?php
namespace app\common\model;

Class ServiceGoods extends Base
{
    //数据预处理
    public function getPriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }
}