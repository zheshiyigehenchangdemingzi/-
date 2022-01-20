<?php
namespace app\common\model;

use think\cache\driver\Redis;

Class GoodsBrand extends Base
{
    public $count = 0;//数据总数
    public $page = '';//数据分页
    public $redis_key = 'admin_goods_brands_list';

    //获取品牌名称ID键值对
    public function getBrandColumn(){
        //return $this->where(['is_delete'=>0])->column('id,name');
        return $this->getSrList();
    }

    /**
     * 获取所有分类信息-缓存
     */
    public function getSrList($bool = true){
        $redis = new Redis();
        $list = $bool ? $redis->get($this->redis_key) : false;
        if($list && is_array($list))return $list;
        $list = $this->where(['is_delete'=>0])->column('id,name');
        $redis->set($this->redis_key,$list);
        return $list;
    }

    /*
     * 获取所有品牌
     * */
    public function getList($get){
        $where = ['is_delete'=>0];
        if(!empty($get['name']))
            $where['name'] = ['like','%'.$get['name'].'%'];

        $field = 'id,name,cover,desc,add_time,sort';
        $list = $this->where($where)->field($field)->order('sort desc')
            ->paginate($get['limit'],false,array('query'=>$get));

        $this->page = $list->render();
        $this->count = $this->where($where)->count();

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
    public function addBrand($data,$file=[]){
        try{
            if(empty($data['name']))
                exception('品牌名称不能为空');
            if(empty($data['desc']))
                exception('品牌描述不能为空');

            $save = [
                'name'          =>  $data['name'],
                'sort'          =>  empty($data['sort'])?0:$data['sort'],
                'desc'          =>  $data['desc']
            ];
            $msg = '商品品牌已添加';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],])->find())
                    exception('此商品品牌已存在');

                // if(empty($file['file']['name']))
                //     exception('请上传品牌图片');

                // $res = imgUpdate('file','goods_brand');//品牌图片上传
                // if($res['code'] == 400)
                //     exception($res['msg']);
                $save['cover']  =   $data['cover'];
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],])->find())
                    exception('此商品品牌已存在');
                // if(!empty($file['file']['name'])){
                //     $res = imgUpdate('file','goods_brand');//品牌图片上传
                //     if($res['code'] == 400)
                //         return_ajax(400,$res['msg']);
                //     $save['cover']  =   $res['data'];
                // }
                $save['cover']  =   $data['cover'];    
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '商品品牌已修改';
            }
            $this->getSrList(false);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //商品分类删除
    public function brandDel($id){
        try{
            if(!is_numeric($id))
                exception('非法操作');

            $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            $this->getSrList(false);
            return_ajax(200,'商品品牌已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}