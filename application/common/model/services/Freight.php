<?php
namespace app\common\model\services;
use app\common\model\Base;
Class Freight extends Base
{

    public $count;

    // 根据类型获取字符提示
    public $configTyepStr = [
        '按件收费' => ['个','件'],
        '按重收费' => ['克','重']
    ];
    

    //数据预处理 get 字段名  Attr
    public function getTypeAttr($value)
    {
        return $value == 1 ? '按重收费' : '按件收费';
    }

    // 数据预处理 downpayment
    public function getDownpaymentAttr($value)
    {
        return $value / 100;
    }

    // 数据预处理renew,renew
    public function getRenewAttr($value)
    {
        return $value / 100;
    }

    // 数据预处理-入库 downpayment
    public function setDownpaymentAttr($value)
    {
        return $value * 100;
    }

    // 数据预处理-入库 renew
    public function setRenewAttr($value)
    {
        return $value * 100;
    }


    //获取列表
    public function getList(){
        $list = $this->order('sort desc')->select()->toArray();
        $this->count = count($list);
        return $list;
    }

    //添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['name']))
                exception('请填写名称');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            if(!isset($data['type']))
                exception('选择类型');
            if(empty($data['company']))
                exception('请输入首件或首重');
            if(empty($data['downpayment']))
                exception('请输入首费');
            if(empty($data['continuation']))
                exception('请输入续件或续重');
            if(empty($data['renew']))
                exception('请输入续费或续费');

            // 数据保存
            /*
            $save = [
                'name'   =>  $data['title'],
                'sort'      =>  $data['sort'],
                'img_url' => $data['img_url'],
                'show' => $data['show']
            ];
            */
            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name']])->find())
                    exception('此名称已存在');
                $this->allowField(true)->save($data);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name']])->find())
                    exception('此名称已存在');
                $this->allowField(true)->isUpdate(true)->save($data,['id'=>$data['id']]);
                $msg = '编辑成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

}