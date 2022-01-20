<?php
namespace app\common\model;

Class Logistics extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        $where = ['is_delete'=>0];
        //物流公司名称
        if(!empty($get['company']))
            $where['company'] = ['like','%'.$get['company'].'%'];

        $field = 'id,company,sort,add_time';
        $list = $this->where($where)->field($field)->order('sort desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    //获取物流公司名称
    public function getLogistics(){
        return $this->where(['is_delete'=>0])->column('id,company');
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        return $this->where(['id'=>$id])->field('id,company,sort')->find();
    }

    //物流公司添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['company']))
                exception('请填写物流公司名称');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;

            $save = [
                'company'   =>  $data['company'],
                'sort'      =>  $data['sort']
            ];
            $msg = '物流公司已添加';
            if(empty($data['id'])){
                if($this->where(['company'=>$data['company'],'is_delete'=>0])->find())
                    exception('此物流公司已经存在');
                $save['add_time']   =   time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'company'=>$data['company'],'is_delete'=>0])->find())
                    exception('此物流公司已经存在');
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '物流公司已修改';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //物流公司删除
    public function logisticsDel($id){
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

            return_ajax(200,'物流公司已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}