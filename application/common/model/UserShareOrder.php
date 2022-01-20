<?php
namespace app\common\model;

Class UserShareOrder extends Base
{
    //数据预处理
    public function getTotalAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getMiaoAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getPShareMoneyAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getSelfMoneyAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    // 查询列表开始
    public function list($get){
        //$this->
        $where = [];
        if(strlen($get['uid']) > 0) $where['uid'] = $get['uid'];
        if(strlen($get['puid']) > 0) $where['puid'] = $get['puid'];
        // 状态
        if(strlen($get['status']) > 0) $where['status'] = $get['status'];
        // 类型
        if(strlen($get['type']) > 0 && $get['type']) $where['type'] = $get['type'];
        //下单时间
        if(!empty($get['start'])){
            if(!empty($get['end'])) $where['add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            else $where['add_time'] = ['gt',strtotime($get['start'])];
        }
        $list = $this->where($where)->paginate($get['limit'],false,array('query'=>$get));
        $this->page = $list->render();
        #$list->toArray();
        $this->count = $list->toArray()['total'];
        return $list;
    }

    /**
     * 模型关联 一对一 购买人的信息
     */ 
    public function oneUser(){
        return $this::hasOne(User::class,'id','uid');
    }

    /**
     * 模型关联 一对一 拿取到 分享人的信息
     */
    public function onePUser(){
        return $this::hasOne(User::class,'id','puid');
    }

    /**
     * 模型关联 一对一 商品 模型
     */
    public function oneGoods(){
        return $this::hasOne(Goods::class,'id','goods_id');
    }
}