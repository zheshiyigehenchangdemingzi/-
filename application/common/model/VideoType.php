<?php
namespace app\common\model;
use think\cache\driver\Redis;
Class VideoType extends Base
{
    public $count = 0;//数据总数
    public $redis_key = 'admin_video_type_list';
    public function getTypeColumn(){
        // return $this->where(['is_delete'=>0,'status'=>1,'pid'=>0])->column('id,name');
        return $this->getSrList();
    }

    /**
     * 获取所有分类信息-缓存
     */
    public function getSrList($bool = true){
        $redis = new Redis();
        $list = $bool ? $redis->get($this->redis_key) : false;
        if($list && is_array($list))return $list;
        $list = $this->where(['is_delete'=>0,'status'=>1,'pid'=>0])->column('id,name');
        $redis->set($this->redis_key,$list);
        return $list;
    }

    /*
     * 获取子分类
     * */
    public function getType($pid){
        $menu = $this->where(['pid'=>$pid,'is_delete'=>0])->field('id,name,pid,status,sort')->order('sort desc')->select();
        return $menu;
    }

    /*
     * 获取所有分类
     * */
    public function getList(){
        $type = $this->getType(0);
        $this->count = count($type);
        if($type){
            foreach ($type as &$val){
                $val['son'] = $this->getType($val['id']);
                $this->count += count($val['son']);
            }unset($val);
        }

        return $type;
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
                'pid'           =>  empty($data['pid'])?0:$data['pid'],
                'name'          =>  $data['name'],
                'sort'          =>  empty($data['sort'])?0:$data['sort'],
                'status'        =>  $data['status']==1?1:0,
            ];
            $msg = '视频分类已添加';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],])->find())
                    exception('此视频分类已存在');

                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],])->find())
                    exception('此视频分类已存在');

                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '视频分类已修改';
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
            return_ajax(200,'视频分类已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}