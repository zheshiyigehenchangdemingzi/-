<?php
namespace app\common\model\product;
use app\common\model\Base;
Class HomeDisplay extends Base
{

    //获取列表
    public function getList(){
        $list = $this->where(['is_delete'=>0])->select()->toArray();
        return $list;
    }

    //添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['name']))
                exception('请填写标题');
            if(empty($data['img']))
                exception('请上传图片');
            // 数据保存
            $save = [
                'name'   =>  $data['name'],
                'img'    =>  $data['img'],
            ];
            if(isset($data['page'])) $save['page'] = $data['page'];
            if(isset($data['status'])) $save['status'] = $data['status'];
            if(isset($data['is_delete'])) $save['is_delete'] = $data['is_delete'];
            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name']])->find())
                    exception('此列表项已存在');
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name']])->find())
                    exception('此列表项已存在');
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '编辑成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}