<?php


namespace app\admin\controller;


use app\common\model\Activity;
use app\common\model\Config;
use app\common\model\Goods;
use app\common\model\GoodsBrand;
use app\common\model\GoodsType;
use app\common\model\lives\LiveBroadcastGoods;
use app\common\model\lives\LiveBroadcastType;
use app\common\model\lives\LiveBroadcast;
use app\common\model\User;
use app\common\model\Video;
use app\common\model\VideoType;

class Lives extends Common
{
    // 直播列表
    public function index()
    {
        $model = new LiveBroadcast();
        $mod = new LiveBroadcastType();
        $get = input('get.');
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['type_id'])) $get['type_id'] = '';
        if(!isset($get['title'])) $get['title'] = '';
        if(!isset($get['limit'])) $get['limit'] = 20;
        if($get['limit'] > 200) $get['limit'] = 200;
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'type'  =>  $mod->getTypeColumn()
        ]);
    }

    /**
     * 改变状态
     */
    public function lives_status()
    {
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','is_hot']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(130);
            // 如果为状态  且 不等于 指定状态
            if($post['key'] == 'status' && !in_array($post['val'],[0,1])) return return_ajax(400,'状态错误');
            $modelFlag = LiveBroadcast::where('id',$post['id'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    /**
     * 直播列表-批量改变-状态
     */
    public function lives_list_pinliang_status()
    {
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0)
            return_ajax(400,'请选择对应列表');
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','is_hot']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(130);
            // 如果为状态  且 不等于 指定状态
            if($post['key'] == 'status' && !in_array($post['val'],[0,1])) return return_ajax(400,'状态错误');
            $modelFlag = LiveBroadcast::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 排序
    public function lives_sortEdit()
    {
        $get = input('get.');
        if(!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400,'错误记录值');
        if(!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400,'修改值错误');
        LiveBroadcast::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }

    /**
     * 删除
     */
    public function lives_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(124);
            $post = input('post.');
            $model = new LiveBroadcast();
            $model->del($post['id']);
        }
    }


    /**
     * ---------- 直播分类开始 ----------
     */
    //列表
    public function type()
    {
        $model = new LiveBroadcastType();
        $get = input('get.');
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['name'])) $get['name'] = '';
        if(!isset($get['limit'])) $get['limit'] = 20;
        return $this->fetch('',['list'=>$model->getList($get),'count'=>$model->count,'page' => $model->page,'get' => $get]);
    }

    //分类编辑
    public function type_edit()
    {
        $model = new LiveBroadcastType();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(122);
            $post = input('post.');
            $model->addType($post);
        }else{
            $id = input('get.id');
            $data = [];
            if($id){
                $data = $model->getOne($id);
            }
            return $this->fetch('',['type'=>$model->getTypeColumn(),'data'=>$data]);
        }
    }

    //分类删除
    public function type_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(124);
            $post = input('post.');
            $model = new LiveBroadcastType();
            $model->typeDel($post['id']);
        }
    }
    /**
     * ---------- 直播分类结束 ----------
     */

    /**
     * 直播商品池
     */
    public function goods()
    {
        // 商品列表, 分类，品牌
        $model = new LiveBroadcastGoods();
        $get = input('get.');
        if(!isset($get['cid']))
            $get['cid'] = '';
        if(!isset($get['bid']))
            $get['bid'] = '';
        if(!isset($get['status']))
            $get['status'] = '';
        if(!isset($get['wx_status']))
            $get['wx_status'] = '';
        if(!isset($get['is_hot']))
            $get['is_hot'] = '';
        if(!isset($get['limit']))
            $get['limit'] = 20;
        if($get['limit'] > 200)
            $get['limit'] = 200;
        //商品状态，0-不上架，1-上架
        $status = [
            0   =>  '下架',
            1   =>  '上架',
        ];

        // 微信审核状态 微信小程序-0：未审核。1：审核中，2：审核通过，3：审核驳回
        $wx_status = [
            0 => '未审核',
            1 => '审核中',
            2 => '审核通过',
            3 => '审核驳回'
        ];

        // 微信审核状态文字颜色
        $wx_colos = [
            '#000',
            'yellow',
            '#48affc',
            'red'
        ];
        return $this->fetch('',[
            'type'      =>  (new GoodsType())->getSrList(), //  $mod->getTypeColumn(),
            'brand'     =>  (new GoodsBrand())->getSrList(), // $mods->getBrandColumn(),
            'list'      =>  $model->getList($get),
            'page'      =>  $model->page,
            'count'     =>  $model->count,
            'get'       =>  $get,
            'status'    =>  $status,
            'wx_status' => $wx_status,
            'wx_colos' => $wx_colos,
            'time'    => time(),
            'num_time' => 360
        ]);
    }

    /**
     * 直播商品添加-编辑
     */
    public function goods_edit(){
        $model = new LiveBroadcastGoods();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(126);
            $post = input('post.');
            $post['id'] && $post['id'] > 0 ? $model->edit($post) : $model->add($post);
        }else{
            $data = [];
            $get = input('get.');
            if(isset($get['id']) && $get['id'] > 0){
                if($one = $model->find($get['id'])){
                    $data= $one;
                    $data['wx_status'] = $model->wx_status_strs[$data['wx_status']];
                    $data['goods_list'] = Goods::field('id,name,cover,supply_url')->find($one->goods_id);
                    if($data['goods_list']){
                        $data['goods_list']['cover'] = explode(',',$data['goods_list']['cover'])[0];
                    }
                }
            }
            return $this->fetch('',[
                'data'=>$data,
                'goodtypes' => (new GoodsType())->getSrList(), // GoodsType::where(['is_delete'=>0,'status'=>1,'is_show'=>1])->field('id,name')->select(),
                'goodBs' => (new GoodsBrand())->getSrList() , //GoodsBrand::where(['is_delete' => 0])->field('id,name')->select(),
            ]);
        }
    }

    /**
     * 拉取状态 - 拉取数据
     */
    public function goods_update_wxstatus()
    {
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(128);
            $post = input('post.');
            $model = new LiveBroadcastGoods();
            $model->goodsPush($post['id']);
        }
    }

    /**
     * 改变状态
     */
    public function goods_list_status()
    {
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','is_hot']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(126);
            // 如果为状态  且 不等于 指定状态
            if($post['key'] == 'status' && !in_array($post['val'],[0,1])) return return_ajax(400,'状态错误');
            $modelFlag = LiveBroadcastGoods::where('id',$post['id'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 排序
    public function sortEdit()
    {
        $get = input('get.');
        if(!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400,'错误记录值');
        if(!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400,'修改值错误');
        LiveBroadcastGoods::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }

    /**
     * 批量改变-状态
     */
    public function goods_list_pinliang_status()
    {
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0)
            return_ajax(400,'请选择对应列表');
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','is_hot']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(126);
            // 如果为状态  且 不等于 指定状态
            if($post['key'] == 'status' && !in_array($post['val'],[0,1])) return return_ajax(400,'状态错误');
            $modelFlag = LiveBroadcastGoods::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    /**
     * 直播商品-删除
     */
    public function goods_del()
    {
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(127);
            $post = input('post.');
            $model = new LiveBroadcastGoods();
            $model->goodsDel($post['id']);
        }
    }

    /**
     * 设置直播配置项
     */
    public function live_setting()
    {
        $model = new Config();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(131);
            $post = input('post.');
            $model->toSave($post);
        }else{
            $config = $model->toData('live');
            return $this->fetch('',['config'=>$config]);
        }
    }

    /**
     * ----直播商品结束----
     */
}