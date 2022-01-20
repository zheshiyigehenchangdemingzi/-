<?php
namespace app\common\model\lives;
use think\cache\driver\Redis;
use app\common\model\Base;

Class LiveBroadcastType extends Base
{
    public $count = 0;//数据总数
    public $redis_key = 'admin_lives_broadcast_type_list';

    /**
     * 所有类型返回
     * @return array|bool|mixed|string
     */
    public function getTypeColumn(){
        return $this->getSrList();
    }

    //public $table = 'm3_live_broadcast_type';

    /**
     * 获取所有分类信息-缓存
     */
    public function getSrList($bool = true){
        $redis = new Redis();
        $list = $bool ? $redis->get($this->redis_key) : false;
        if($list && is_array($list))return $list;
        $list = $this->where(['is_delete'=>0,'status'=>1])->column('id,name');
        $redis->set($this->redis_key,$list);
        return $list;
    }

    /*
     * 获取所有分类
     * */
    public function getList($get){
        $where = ['is_delete'=> 0];
        if(isset($get['name']))    $where['name'] = ['like','%'.$get['name'].'%'];
        if(strlen($get['status']) >= 1)    $where['status'] = $get['status'];
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

    //分类删除
    public function typeDel($id){
        try{
            if(!is_numeric($id))
                exception('非法操作');

            $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            $this->getSrList(false);
            return_ajax(200,'直播分类已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}