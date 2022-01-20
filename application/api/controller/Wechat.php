<?php
namespace app\api\controller;
use app\common\controller\Base;
use app\common\model\Config;
use app\api\model\User;
use think\Loader;

Class Wechat extends Base{
    /**
     * [getOpenid 获取用户openid和session_key]
     * @Author   PengJun
     * @DateTime 2019-03-23
     * @return   [type]     [description]
     */
    public function auth()
    {
        if(request()->isPost()){
            $post = input('post.');
            $config = $this->getConfig();
            if(empty($post['code']))
                return_ajax(400,'缺少code参数');

            $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$config['appid'].'&secret='.$config['appsecret'].'&js_code='.$post['code'].'&grant_type=authorization_code';
            $info = json_decode(curlGet($url),true);

            return_ajax(10000,'获取成功',$info);
        }
    }

    //获取小程序配置
    private function getConfig(){
        $model = new Config();
        return $model->toData('wechat');
    }

    //保存用户微信信息 获取数据token
    public function add(){
        if(request()->isPost()){
            $post = input('post.');
            $model = new User();
            $model->addUser($post);
        }
    }

    //刷新token
    public function refresh(){
        if(request()->isPut()){
            $put = input('put.');
            $model = new User();
            $model->refreshToken($put);
        }
    }
}