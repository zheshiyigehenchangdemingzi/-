<?php


namespace app\common\model\shop;



use app\common\model\Base;

// 店铺详情
class ShopUserInfo extends Base
{
    // 预处理数据
    public function getCardImgAttr($value){
        $arrs = explode(',',$value);
        $list = [];
        foreach($arrs as &$i)$list[] = prefixUrlImg($i);
        return $list;
    }

    // 获取列表
    public function getList($get){

        return [];
    }

    // 将身份证图片进行切割
    public function getCardImgs($v){
//        if(strpos($v,',')!== false)
//            $imgs = explode(',',$v);
//        else
//            $imgs = json_decode($v,true);
        $imgs = json_decode($v,true);
        foreach($imgs as &$i)$i  = prefixUrlImg($i);
        return $imgs;
    }
}