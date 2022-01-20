<?php
namespace app\common\model;

use think\Model;

Class ViewPoster extends Model
{
    //添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['title']))
                exception('请填写标题');
            if(empty($data['img']))
                exception('请上传图片');
            // 数据保存
            $save = [
                'title'   =>  $data['title'],
                'is_open' => $data['is_open'],
                'img' => $data['img']
            ];
            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['title'=>$data['title']])->find())
                    exception('此列表项已存在');
                $save['add_time']   =   time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'title'=>$data['title']])->find())
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