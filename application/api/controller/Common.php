<?php
namespace app\api\controller;
use app\common\controller\Base;
use think\Loader;

Class Common extends Base{
    //入口处理
    public function _initialize()
    {
        $token = input('token');
        if(empty($token))
            return_ajax(10007,'token不能为空');

        $this->checkToken($token);
    }

    //对象转数组
    protected function object2array($obj)
    {
        $arr = [];
        if(empty($obj))
            return $arr;

        foreach($obj as $val){
            $arr[] = $val->toArray();
        }

        return $arr;
    }

    //jwt token 验证
    protected function checkToken($token){
        Loader::import('tool.Jwt', EXTEND_PATH,'.php');
        $jwt = new \Jwt;
        $code = $jwt->verifyToken($token);

        if($code != 10000)
            return_ajax($code,$this->errCode($code));
    }

    protected function errCode($code){
        $codeArr = [
            10001   =>  'token错误',
            10002   =>  'token解析错误' ,
            10003   =>  'token签名错误',
            10004   =>  '非法token',
            10005   =>  'token已过期',
            10006   =>  'token处于缓冲期，不可用'
        ];
        return  $codeArr[$code];
    }
}