<?php
namespace app\common\model\lives;
use app\common\model\User;
use think\cache\driver\Redis;
use app\common\model\Base;

Class LiveBroadcast extends Base
{
    public $count = 0;//数据总数
    public $redis_key = 'admin_lives_broadcast_list';
    public function getTypeColumn(){
        // return $this->where(['is_delete'=>0,'status'=>1,'pid'=>0])->column('id,name');
        return $this->getSrList();
    }

    /*
     * 获取所有分类
     * */
    public function getList($get){
        $where = ['is_delete'=> 0];
        if(isset($get['title']))    $where['title'] = ['like','%'.$get['title'].'%'];
        if(strlen($get['status']) >= 1)    $where['status'] = $get['status'];
        if(strlen($get['type_id']) >= 1)    $where['type_id'] = $get['type_id'];
        if(isset($get['start']) && $get['start']) $where ['start_time'] = ['>',strtotime($get['start'])];
        if(isset($get['end']) && $get['end']) $where ['end_time'] = ['<',strtotime($get['end'])];
        $list = $this->where($where)->order('sort desc')->paginate($get['limit'] < 200 ? $get['limit'] : 200,false,$get);
        $this->count = $list->total();
        $this->page = $list->render();
        return $list;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        return $this->where(['id'=>$id])->find();
    }

    //编辑、保存分类
    public function addType($data){
        try{
            if(empty($data['name']))
                exception('分类名称不能为空');
            if(!is_numeric($data['status']) || !in_array($data['status'],[1,0]))
                exception('请选择分类是否启用');

            $save = [
                'name'          =>  $data['name'],
                'sort'          =>  empty($data['sort'])?0:$data['sort'],
                'status'        =>  $data['status']==1?1:0,
            ];
            $msg = '直播分类已添加';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],])->find())
                    exception('此直播分类已存在');

                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],])->find())
                    exception('此直播分类已存在');

                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '直播分类已修改';
            }
            $this->getSrList(false);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //删除
    public function del($id){
        try{
            if(!$id)
                exception('非法操作');
            $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>is_array($id) && count($id) == 1 ? $id[0] : $id]);
            return_ajax(200,'直播列表已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }


    public function getUser(){
        return $this::hasOne(User::class,'id','uid');
    }
}