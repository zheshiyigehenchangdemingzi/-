<?php
namespace app\common\model;

Class GoodsAttr extends Base
{
    //字段预处理 格式化 注意：所有价格单位我习惯用分 所以保存不带小数
    public function getCostPriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //字段预处理 格式化
    public function getPriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //字段预处理 格式化
    public function getDiscountPriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //字段预处理 格式化
    public function getLivePriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    // 预处理数据 json
    public function getValjsonAttr($value)
    {
        return json_decode($value,true);
    }

    //获取单个商品所有属性
    public function getList($productId){
        return $this->where(['product_id'=>$productId,'is_delete'=>0])->select();
    }

    //获取改商品的全部属性id
    public function getAttrId($productId){
        return $this->where(['product_id'=>$productId,'is_delete'=>0])->column('id');
    }

    /**
     * 将当前记录的商品规格信息进行整合
     */
    public static function getAttrDataList($list){
        $allData = [
            'key' => [],
            'data' => []
        ];
        foreach($list as $item){
            if(isset($item['valjson']) && is_array($item['valjson']) && count($item['valjson']) > 0){
                foreach($item['valjson'] as $k => $i ){
//                        echo $k."-----------".$i."\r\n<h3>***************</h3>";
                    // 键值存储
                    in_array($k,$allData['key']) ? false : $allData['key'][] = $k;
                    // 键值对应的value存储,判断是否有存储
                    if(isset($allData['data'][$k])){
                        if(!in_array($i,$allData['data'][$k]))
                            $allData['data'][$k][] = $i;
                    }else{
                        $allData['data'][$k] =[$i];
                    }
                }
            }
        }
        return $allData;
    }
}