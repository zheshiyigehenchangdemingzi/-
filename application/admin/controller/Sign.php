<?php
namespace app\admin\controller;

use app\common\model\Config;
use think\Request;

Class Sign extends Common{
    /***
     *签到打卡奖励配置 -显示
     */
    public function sing_in_reward()
    {
        $signModel = new \app\common\model\Sign();
        //渲染页面
        $content = $signModel->toData('sing_in_reward') ? $signModel->toData('sing_in_reward') : [];
        return $this->fetch('sing',['config'=>$content]);
    }

    /***
     *签到打卡奖励配置 -编辑
     */
    public function sing_in_reward_edit()
    {
        $signModel = new \app\common\model\Sign();
        if(request()->isPost() && request()->isAjax()) {
            $this->checkAuth(14);
            $post = input('post.');
            unset($post['type']);
            $signModel->updateData($post);
            $msg = '更改信息成功';
            return_ajax(200, $msg);
        }
    }

    //签到打卡列表
    public function signFinds()
    {
        $signModel = new \app\common\model\Sign();

        $get = input('get.');
        if(!isset($get['limit'])){
            $get['limit'] = 10;
        }
        $signFindsData = $signModel->findsData($get);

        return $this->fetch('finds', [
            'signFindsData' => $signFindsData,
            'count'     =>  $signFindsData ? $signModel->count : 0 ,
            'page'  => $signFindsData ? $signModel->page : "",
            'get' => $get
        ]);

    }
}   