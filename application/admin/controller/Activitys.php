<?php
namespace app\admin\controller;
use app\common\model\Activity;
use app\common\model\ActivityLog;
use app\common\model\Order;

Class Activitys extends Common{
    //活动列表
    public function index()
    {
        $model = new Activity();
        $get = input('get.');
        if(empty($get['status']))
            $get['status'] = '';

        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //添加活动
    public function add(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(63);
            $post = input('post.');
            $model = new Activity();
            $model->addActivity($post,$_FILES);
        }else{
            return $this->fetch();
        }
    }

    //修改活动
    public function edit(){
        $model = new Activity();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(64);
            $post = input('post.');
            $model->editActivity($post,$_FILES);
        }else{
            return $this->fetch('',['data'=>$model->getOne(input('get.id'))]);
        }
    }

    public function status(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(65);
            $post = input('post.');
            $model = new Activity();
            $model->editStatus($post);
        }
    }

    public function miao(){
        $model = new ActivityLog();
        $mod = new Activity();
        $get = input('get.');
        $activityName = $mod->getListName();
        return $this->fetch('',[
            'list'      =>  $model->getList($get,$activityName),
            'page'      =>  $model->page,
            'count'     =>  $model->count,
            'get'       =>  $get,
            'activity'  =>  $activityName
        ]);
    }
}