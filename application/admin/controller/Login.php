<?php
namespace app\admin\controller;
use app\common\controller\Base;
use app\common\model\Admin;
use think\captcha\Captcha;

Class Login extends Base{
    //登录页
    public function index()
    {
        return $this->fetch();
    }

    //登录处理
    public function login(){
        if(request()->isAjax() && request()->isPost()){
            $post = input('post.');
            $post['type'] = 1;
            $model = new Admin();
            // $model->toLogin($post);
            if(captcha_check($post['captcha'])){
                if(!$this->check_input($post))
                    return_ajax(400,'输入内容含有非法字符');
                $model = new Admin();
                $model->toLogin($post);
            }else{
                return_ajax(400,'验证码错误');
            }
        }else{
            return_ajax(400,'网络错误');
        }
    }

    //获取验证码
    public function captcha(){
        //验证码配置
        $config =    [
            'codeSet'     =>    '0123456789',
            // 验证码字体大小
            'fontSize'    =>    40,
            // 验证码位数
            'length'      =>    6,
            // 关闭验证码杂点
            'useNoise'    =>    true,
            //去除关闭混淆曲线
            'useCurve'    =>    false,
            'expire'      =>    300,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    //退出
    public function logout(){
        session('admin_admin_info',null);
        $this->redirect('Login/index');
    }
}