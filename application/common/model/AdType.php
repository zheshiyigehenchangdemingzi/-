<?php
namespace app\common\model;

Class AdType extends Base
{
    public $page = '';//数据分页
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        $where = ['is_delete'=>0];
        //类型名称
        if(!empty($get['name']))
            $where['name'] = ['like','%'.$get['name'].'%'];
        //识别码
        if(!empty($get['key']))
            $where['key'] = ['like','%'.$get['key'].'%'];
        //描述信息
        if(!empty($get['desc']))
            $where['desc'] = ['like','%'.$get['desc'].'%'];
        //状态
        if(strlen($get['status']) >= 1)
            $where['status'] = $get['status'];
        $list = $this->where($where)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    //获取广告类型
    public function getType(){
        return $this->where(['is_delete'=>0])->column('key,name');
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        return $this->where(['id'=>$id])->find();
    }

    public function addType($data){
        try{
            if(empty($data['name']))
                exception('请填写类型名称');
            if(empty($data['key']))
                exception('请填写识别码');
            if(strlen($data['key']) <= 4)
                exception('识别码长度不得低于4位');

            $save = [
                'name'      =>  $data['name'],
                'desc'      =>  $data['desc'],

                'status' =>$data['status']
            ];
            $msg = '广告类型已添加';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],'is_delete'=>0])->find())
                    exception('此类型名称已经使用');
                $save['add_time']   =   time();
                $save['key']  =  $data['key'];
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],'is_delete'=>0])->find())
                    exception('此类型名称已经使用');
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '广告类型已修改';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //广告类型删除
    public function typeDel($id){
        try{
            $count = count($id);
            if($count > 1){
                foreach ($id as $v){
                    if(!is_numeric($v))
                        exception('非法操作');
                }

                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>['in',$id]]);
            }else{
                if(!is_numeric($id[0]))
                    exception('非法操作');

                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id[0]]);
            }

            return_ajax(200,'广告类型已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}