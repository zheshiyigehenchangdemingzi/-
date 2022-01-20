<?php
namespace app\common\model;

Class UserInfo extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数


    //更新个人信息
    public function updateInfo($data){
        $info = [];
        if(!empty($data['name']))
            $info['name'] = $data['name'];
        if(!empty($data['avatarurl']))
            $info['avatarurl'] = $data['avatarurl'];
    }
}