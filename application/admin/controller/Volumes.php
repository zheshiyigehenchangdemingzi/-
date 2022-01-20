<?php
namespace app\admin\controller;
use app\common\model\Config;
use app\common\model\UserLevel;
use app\common\model\UserVolumeLog;
use app\common\model\Volume;
use think\cache\driver\Redis;

Class Volumes extends Common{
    //购物券列表
    /*
    public function index()
    {
        $model = new Volume();
        $get = input('get.');

        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }
    */

    //添加购物券
    public function volume_edit(){
        $model = new Volume();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(70);
            $post = input('post.');
            $model->addVolume($post);
        }else{
            $id = input('get.id');
            $type = ['type'=>''];
            $data = ['is_team'=>'','is_sale'=>'','sale_val'=>$type];
            if($id)
                $data = $model->getOne($id);
            $mod = new UserLevel();
            return $this->fetch('',[
                'data'      =>  $data,
                'level'     =>  $mod->getLevel()
            ]);
        }
    }


    //购物券活动列表
    public function volumes_list()
    {
        $model = new Volume();
        $get = input('get.');
        if(empty($get['status']))
            $get['status'] = '';
        return $this->fetch('/volumes/edit/index',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //购物券活动列表 添加
    public function volumes_add(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(136);
            $post = input('post.');
            $model = new Volume();
            $model->addActivity($post,$_FILES);
        }else{
            return $this->fetch('/volumes/edit/add');
        }
    }

    //购物券活动列表 修改
    public function volumes_edit(){
        $model = new Volume();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(136);
            $post = input('post.');
            $model->volumeEdit($post);
        }else{
            $level = new UserLevel();
            return $this->fetch('/volumes/edit/edit',['data'=>$model->getOne(input('get.id')),'level'=>$level->getList([])]);
        }
    }

    //购物券活动列表 修改
    public function volumes_status(){
        if(request()->isAjax() && request()->isPost()){
            $model = new Volume();
            $this->checkAuth(136);
            $post = input('post.');
            $model->editStatus($post);
        }
    }

    /*
     * 编辑
     */
    public function volumeEdit(){
        $model = new Config();
        if(request()->isAjax() && request()->isPost()){
            $post = input('post.');
            if(!empty($post['initial'])&&$post['initial']>(int)$post['initial']) return_ajax(400,'请输入整数的初始额度');
            if(!empty($post['price'])&&$post['price']>(int)$post['price']) return_ajax(400,'请输入整数的购买价格');
            if(!empty($post['sale_val'])&&$post['sale_val']>(int)$post['sale_val']) return_ajax(400,'请输入整数的比例值');
            $post['type'] = 'volume';
            $this->checkAuth(70);
            $model->toSave($post);
        }else{
            $data = $model->toData('volume');
            return $this->fetch('volumeEdit',['data' => $data,]);
        }
    }

    /*
     * vip用户设置
     */
    public function volume_edit_vip(){
        $model = new Config();
        $redis = new Redis([
            'host'   => '127.0.0.1',  //redis服务器ip
            'port'   => '6379',
            'select' => 11
        ]);
        if(request()->isAjax() && request()->isPost()){
            $post = input('post.');
            if(!empty($post['purchase'])&&$post['purchase']>(int)$post['purchase']) return_ajax(400,'请输入整数的自购省比例');
            if(!empty($post['share'])&&$post['share']>(int)$post['share']) return_ajax(400,'请输入整数的分享赚比例');
            if(!empty($post['name'])&&$post['name']>(int)$post['name']) return_ajax(400,'请输入名称');
            $this->checkAuth(116);
            $flag = $model->where('type','volumeVip')->update([
                'content' => json_encode($post)
            ]);
            // mmei_ 前缀
            if($redis->has('mmei_config_volumeVip')){
                $redis->del('mmei_config_volumeVip');
            }
            return return_ajax(200,$flag ? '购物券VIP用户设置ok' : '错误');
        }else{
            $data = $model->toData('volumeVip');
            return $this->fetch('',['data' => $data]);
        }
    }

    //购物券删除
    public function volume_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(71);
            $post = input('post.');
            $model = new Volume();
            $model->volumeDel($post['id']);
        }
    }

    //消费记录
    public function consume(){
        $model = new UserVolumeLog();
        $get = input('get.');

        return $this->fetch('',[
            'list'  =>  $model->getConsumeList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //充值记录
    public function recharge(){
        $model = new UserVolumeLog();
        $get = input('get.');

        return $this->fetch('',[
            'list'  =>  $model->getRechargeList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }
}