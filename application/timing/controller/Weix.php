<?php
namespace app\timing\controller;
use app\common\controller\Base;
use app\common\model\Config;

Class Weix extends Base {
    //定期更新token 10分钟执行一次
    public function index(){
        $model = new Config();
        $token = $model->toData('token');//获取token数据
        $now = time();
        if($now >= $token['expires_in']){
            $wx = $model->toData('wechat');//获取微信配置
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$wx['appid'].'&secret='.$wx['appsecret'];//请求地址
            $res = curlGet($url);
            $res = json_decode($res,true);
            $res['expires_in'] = $now + 6600;//过期时间提前10分钟
            $res['type'] = 'token';
            $model->toSave($res,false);
        }
    }
}