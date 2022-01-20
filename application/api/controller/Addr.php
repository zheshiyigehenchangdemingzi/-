<?php
namespace app\api\controller;
use app\common\controller\Base;
use app\common\model\Config;
use app\common\model\District;
use app\common\model\User;
use think\Loader;

Class Addr extends Base{
    //获取区域地址
    public function get()
    {
        $get = input();
        $model = new District();
        if(empty($get['code'])){
            $addr = $model->getDistrict();
        }else{
            $addr = $model->getDistrict($get['code']);
        }

        return_ajax(10000,'获取成功',$addr);
    }

    // 测试
    public function test()
    {
        return_ajax(10000, 'ok', [
            'test' => 'dssdsd'
        ]);
    }
}