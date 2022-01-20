<?php
namespace app\common\model\product;
use app\common\model\Base;
Class ViewProductBanner extends Base
{

    //获取列表
    public function getList($get){
//        $list = $this->order('sort desc')->select()->toArray();
        $where = [];
        if(isset($get['title'])) $where['title'] = ['like','%'.$get['title'].'%'];
//        if(isset($get['url'])) $where['url'] = ['like','%'.$get['url'].'%'];
        if(isset($get['show']) && strlen($get['show']) >= 1) $where['show'] = $get['show'];
        if(isset($get['is_skip']) && strlen($get['is_skip']) >= 1) $where['is_skip'] = $get['is_skip'];
        $list = $this->where($where)->order('sort desc')->select()->toArray();
        return $list;
    }

    //添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['title']))
                exception('请填写标题');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            /*
            if (empty($data['show']))
                $data['show'] = 1;
            */
            if(empty($data['img_url']))
                exception('请上传图片');
            // 数据保存
            $save = [
                'title'   =>  $data['title'],
                'sort'      =>  $data['sort'],
                'img_url' => $data['img_url'],
                'goods_id' => $data['goods_id']??0,
                'url' => $data['url']??'',
                'wxchat_url' => $data['wxchat_url']??'',
                'show' => $data['show'],
                'is_skip' => $data['is_skip'],
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
                $save['upd_time'] = time();
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '编辑成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}