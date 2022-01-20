<?php
namespace app\admin\controller;

use sms\TencentMsg;

Class Index extends Common{
    //框架首页
    public function index()
    {
        return $this->fetch();
    }

    //显示首页
    public function welcome()
    {
        return $this->fetch();
    }


    /*public function test()
    {
        $mode = TencentMsg::setMobiles(['+8613533580420'])::setContent(['test','test123456'])::setTemplateId('shop_examine_pass')::sendSms();
        var_dump($mode,TencentMsg::getReturn());
    }*/
}