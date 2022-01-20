<?php
namespace app\admin\controller;
use app\common\model\Ad;
use app\common\model\AdType;
use app\common\model\Config;
use app\common\model\Goods;

Class Advert extends Common{
    //广告列表
    public function index()
    {
        $model = new Ad();
        $mod = new AdType();
        $get = input('get.');
        if(empty($get['type_key']))
            $get['type_key'] = '';
        if(empty($get['goods_id']))
            $get['goods_id'] = '';
        if(!isset($get['show']))
            $get['show'] = '';
        if(!isset($get['is_skip']))
            $get['is_skip'] = '';
        if(!isset($get['limit']))
            $get['limit'] = 20;
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'type'  =>  $mod->getType()
        ]);
    }

    public function ad_edit(){
        $model = new Ad();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            $model->addLogistics($post);
        }else{
            $id = input('get.id');
            $data = ['type_id'=>'','goods_id'=>'','status'=>'','is_skip'=>''];
            if($id){
                $data = $model->getOne($id);
                $data['expires_in'] = date('Y-m-d',$data['expires_in']);
            }
            $mod = new AdType();
            return $this->fetch('',['data'=>$data,'types'=>$mod->getType()]);
        }
    }

    //广告删除
    public function ad_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(23);
            $post = input('post.');
            $model = new Ad();
            $model->adDel($post['id']);
        }
    }


    // 首页轮播图-  广告图  --  状态改变
    public function ad__status(){
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['is_skip','show']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(21);
            $modelFlag = Ad::where('id',$post['id'])->update([
                $post['key'] =>  $post['val'] - 0
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态 - 广告图 -- 状态改变
    public function ad_pinliang_status(){
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0) return_ajax(400,'请选择对应列表');
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['is_skip','show']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(21);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = Ad::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态 - 广告图 -- 批量删除
    public function ad_delAll(){
        $post = input('post.');
        if(!isset($post['id']) || !is_array($post['id']) || count($post['id']) == 0) return_ajax(400,'请选择对应列表');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(23);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = Ad::whereIn('id',$post['id'])->update([
                'is_delete' =>  1
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 排序改变-  广告图  -  排序值
    public function ad_sortEdit(){
        $get = input('get.');
        if(!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400,'错误记录值');
        if(!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400,'修改值错误');
        Ad::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }

    //广告类型
    public function type(){
        $model = new AdType();
        $get = input('get.');
        if(!isset($get['status']))  $get['status'] = '';
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get
        ]);
    }

    public function type_edit(){
        $model = new AdType();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(20);
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->addType($post);
        }else{
            $id = input('get.id');
            $data = [];
            if($id){
                $data = $model->getOne($id);
            }

            return $this->fetch('',['data'=>$data]);
        }
    }

    //广告类型删除
    public function type_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(22);
            $post = input('post.');
            $model = new AdType();
            $model->typeDel($post['id']);
        }
    }

    // 首页轮播图-  广告图  --  状态改变
    public function type__status(){
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','show']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(20);
            $modelFlag = AdType::where('id',$post['id'])->update([
                $post['key'] =>  $post['val'] - 0
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态 - 广告图 -- 状态改变
    public function type_pinliang_status(){
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0) return_ajax(400,'请选择对应列表');
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','show']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(20);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = AdType::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态 - 广告图 -- 批量删除
    public function type_delAll(){
        $post = input('post.');
        if(!isset($post['id']) || !is_array($post['id']) || count($post['id']) == 0) return_ajax(400,'请选择对应列表');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(22);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = AdType::whereIn('id',$post['id'])->update([
                'is_delete' =>  1
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 商城首页 - 爆款、福利、新品 配置信息
    public function shopConfig(){
        $model = new Config();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(137);
            $post = input('post.');
            if(!$this->check_input($post))
                return return_ajax(400,'输入内容含有非法字符');
            foreach($model->shopConfigView as $item){
                if(!isset($post[$item['key'].'_num']) || $post[$item['key'].'_num'] <= 0) return return_ajax(400,'数量必须输入,且必须大于0');
                if(!isset($post[$item['key'].'_title']) || strlen($post[$item['key'].'_title']) <= 1) return return_ajax(400,'提示文本必须输入,且长度大于2');
            }
            $model->toSave($post);
        }else{
            $config = [];
            $config = $model->toData('shopConfig');
            return $this->fetch('',['config'=>$config,'viewData'=>$model->shopConfigView]);
        }
    }
}