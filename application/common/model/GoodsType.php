<?php
namespace app\common\model;

use think\cache\driver\Redis;

Class GoodsType extends Base
{
    public $count = 0;//分类总数
    public $redis_key = 'admin_goods_types_list';

    //获取分类ID 名称键值对
    public function getTypeColumn(){
        return $this->getSrList();
        //return $this->where(['is_delete'=>0,'status'=>1,'pid'=>0])->column('id,name');
    }

    /*
     * 获取子分类
     * */
    public function getType($pid){
        $menu = $this->where(['pid'=>$pid,'is_delete'=>0])->field('id,cover,name,pid,status,is_show,sort')->order('sort desc')->select();
        return $menu;
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
     * 获取所有菜单
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
    public function addType($data,$file=[]){
        try{
            if(empty($data['name']))
                exception('分类名称不能为空');
            if(!is_numeric($data['status']) || !in_array($data['status'],[1,0]))
                exception('请选择分类是否启用');
            if(!is_numeric($data['is_show']) || !in_array($data['status'],[1,0]))
                exception('请选择分类是否显示');

            $save = [
                'pid'           =>  empty($data['pid'])?0:$data['pid'],
                'name'          =>  $data['name'],
                'sort'          =>  empty($data['sort'])?0:$data['sort'],
                'status'        =>  $data['status']==1?1:0,
                'is_show'       =>  $data['is_show']==1?1:0
            ];
            $msg = '商品分类已添加';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],])->find())
                    exception('此商品分类已存在');

                if(!empty($file['file']['name'])){
                    $res = imgUpdate('file','goods_type');//分类图片上传
                    if($res['code'] == 400)
                        exception($res['msg']);
                    $save['cover']  =   $res['data'];
                }
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],])->find())
                    exception('此商品分类已存在');
                if(!empty($file['file']['name'])){
                    $res = imgUpdate('file','goods_type');//分类图片上传
                    if($res['code'] == 400)
                        exception($res['msg']);
                    $save['cover']  =   $res['data'];
                }

                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '商品分类已修改';
            }
            $this->getSrList(false);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //商品分类删除
    public function typeDel($id){
        try{
            if(!is_numeric($id))
                exception('非法操作');

            $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            $this->getSrList(false);
            return_ajax(200,'商品分类已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}