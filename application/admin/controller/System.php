<?php
namespace app\admin\controller;
use app\common\model\Admin;
use app\common\model\Menu;
use app\common\model\Role;

Class System extends Common{
    //管理员列表
    public function index()
    {
        $get = input('get.');
        if(empty($get['status']))
            $get['status'] = '';
        if(empty($get['role_id']))
            $get['role_id'] = '';
        if(empty($get['shop_id']))
            $get['shop_id'] = '';

        $status = [
            1   =>  '正常',
            2   =>  '冻结',
            3   =>  '禁用'
        ];
        // 获取到 管理员
        $model = new Admin();
        $mod = new Role();
        /*
        if($get){
            var_dump($get);
            exit;
        }
        */

        return $this->fetch('',[
            'list'=>$model->getList($get),
            'page'=>$model->page,
            'count'=>$model->count,
            'status'=>$status,
            'get'=>$get,
            'role'=>$mod->getRoleColumn()
        ]);
    }

    public function edit(){
        $model = new Admin();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            //默认开放自己修改自己信息权限
            if($this->uid != $post['id'])
                $this->checkAuth(7);

            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');

            $model->editAdmin($post);
        }else{
            $id = input('get.id');
            $data = ['role_id'=>''];
            if(!empty($id)){
                $data = $model->getOne($id);
            }
            $mod = new Role();
            return $this->fetch('',['data'=>$data,'role'=>$mod->getRoleColumn()]);
        }
    }

    //管理员状态处理，删除、禁用、启用
    public function admin_status(){
        $this->checkAuth(8);
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            $model = new Admin();
            $model->editStatus($post['id'],$post['type']);
        }else{
            return_ajax(400,'请求方式错误');
        }
    }

    //菜单页
    public function menu()
    {
        $model = new Menu();
        return $this->fetch('',['list'=>$model->getMenuList(),'count'=>$model->count]);
    }

    //菜单编辑
    public function menu_edit(){
        $model = new Menu();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(9);
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->addMenu($post);
        }else{
            $id = input('get.id');
            $data = ['icon'=>'','pid'=>''];//数组变量，预设为空
            if(!empty($id)){
                $data = $model->getOne($id);
            }
            return $this->fetch('',['menu'=>$model->getMenuColumn(),'data'=>$data]);
        }
    }

    //菜单状态编辑
    public function menu_status(){
        $this->checkAuth(10);
        if(request()->isPost() && request()->isAjax()){
            $model = new Menu();
            $model->MenuStatus(input('post.id'));
        }else{
            return_ajax(400,'请求方式错误');
        }
    }

    //菜单删除
    public function menu_del(){
        $this->checkAuth(11);
        if(request()->isPost() && request()->isAjax()){
            $model = new Menu();
            $model->MenuDel(input('post.id'));
        }else{
            return_ajax(400,'请求方式错误');
        }
    }

    // 菜单自动拉取 目前只有指定账号才可以使用
    public function loadMenu(){
        if(!isset($this->AdminInfo->account) || !$this->AdminInfo->account)return return_ajax(400,'错误');
        if(!regAccountMenus($this->AdminInfo->account))return return_ajax(400,'账号错误');
        list($son,$list) = $this->getAllClassMethods();
        $menuModel = new Menu();
        $list = $menuModel->queryMenu($son,$list);
        return return_ajax(200,'操作成功',$list);
    }

    //角色列表
    public function role()
    {
        $get = input('get.');
        $model = new Role();
        return $this->fetch('',['list'=>$model->getList($get),'page'=>$model->page,'count'=>$model->count,'get'=>$get]);
    }

    //角色编辑
    public function role_edit(){
        $model = new Role();
        // 如果为编辑
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(12);
            $post = input('post.');
            $post1 = $post;
            unset($post1['menu'],$post1['main']);
            if(!$this->check_input($post1))
                return_ajax(400,'输入内容含有非法字符');
            $model->addRole($post);
        // 如果为查看，或者新增
        }else{
            $id = input('get.id');
            $data = [];
            $main = [];
            $sub = [];
            // 有id 则表示编辑
            if($id){
                $data = $model->getOne($id);
                $main = explode(',',$data['main_menu']);
                $main = array_values($main);
                $sub = explode(',',$data['sub_menu']);
                $sub = array_values($sub);
            }

            //获取菜单
            $model = new Menu();
            // 获取一级子菜单 此处表示0
            $menu = $model->getKidMenu(0);
            $menu = $this->object2array($menu);
            if($menu) {
                foreach ($menu as &$val) {
                    $val['check'] = 0;
                    if($main){
                        if (in_array($val['id'], $main))
                            $val['check'] = 1;
                    }
                    $val['son'] = $model->getKidMenu($val['id']);
                    $val['son'] = $this->object2array($val['son']);
                    if($val['son']){
                        foreach ($val['son'] as &$v){
                            $v['check'] = 0;
                            if($sub){
                                if (in_array($v['id'], $sub))
                                    $v['check'] = 1;
                            }
                        }
                    }
                }unset($val,$v);
            }
            return $this->fetch('',['data'=>$data,'menu'=>$menu]);
        }
    }

    //角色删除
    public function role_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(13);
            $post = input('post.');
            $model = new Role();
            $model->RoleDel($post['id']);
        }else{
            return_ajax(400,'请求方式错误');
        }
    }
}