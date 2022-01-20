<?php
namespace app\api\model;
use app\common\model\Base;

Class UserInfo extends Base
{
    //更新个人信息
    public function updateInfo($data){
        $info = [];
        if(!empty($data['name']))
            $info['name'] = $data['name'];
        if(!empty($data['avatarurl']))
            $info['avatarurl'] = $data['avatarurl'];
    }

    //获取邀请码
    public function getInvite(){
        $str = 'QWERTYUIOPASDFGHJKLZXCVBNM0123456789';
        $len = strlen($str);
        $inviteCode = '';
        for($i=0;$i<7;$i++){
            $position = mt_rand(0,$len-1);//字符串节点
            $inviteCode .= substr($str,$position,1);
        }
        if($this->where(['invite_code'=>$inviteCode])->find())
            $this->getInvite();

        return $inviteCode;
    }

    //获取会员等级信息
    public function getLevel($uid){
        return $this->where(['uid'=>$uid])->field('invite_id,level')->find();
    }

    //获取会员昵称
    public function getName($id){
        $name = $this->where(['id'=>$id])->value('name');
        return emojiDecode($name);
    }

    //获取团队业绩相关
    public function getTeam($uid){
        return $this->alias('i')->join('user_wallet w','i.uid=w.uid','left')
            ->where(['i.uid'=>$uid])->field('u.level,u.invite_id,w.teams,w.consume')->find();
    }
}