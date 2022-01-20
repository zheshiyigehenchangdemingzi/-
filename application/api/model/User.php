<?php
namespace app\api\model;
use app\common\model\Base;
use think\Loader;

Class User extends Base
{
    //添加用户
    public function addUser($data){
        try{
            if(empty($data['openid']))
                exception('openid不能为空',20001);
            if(empty($data['nickname']))
                exception('微信昵称不能为空',20002);
            if(empty($data['avatarurl']))
                exception('微信头像不能为空',20003);
            if(!in_array($data['gender'],[0,1,2]))
                exception('性别参数有误',20004);

            $now = time();
            //微信用户信息
            $user = [
                'token'     =>  $this->getToken($data['openid']),
                'openid'    =>  $data['openid'],
                'nickname'  =>  emojiEncode($data['nickname']),
                'avatarurl' =>  $data['avatarurl'],
                'gender'    =>  $data['gender'],
                'province'  =>  empty($data['province'])?'':$data['province'],
                'city'      =>  empty($data['city'])?'':$data['city'],
                'country'   =>  empty($data['country'])?'':$data['country'],
                'upd_time'  =>  $now
            ];

            //检测是否新用户
            $uid = $this->where(['openid'=>$data['openid']])->value('id');
            if($uid){
                $this->allowField(true)->isUpdate(true)->save($user,['id'=>$uid]);
            }else{
                //开启事务
                $this::startTrans();
                try{
                    $user['add_time'] = $now;
                    $this->allowField(true)->save($user);

                    //个人信息
                    $infoModel = new UserInfo();
                    $info = [
                        'uid'             =>  $this->id,
                        'name'           =>  emojiEncode($data['nickname']),
                        'avatarurl'     =>  $data['avatarurl'],
                        'invite_code'   =>  $infoModel->getInvite(),
                        'add_time'      =>  $now
                    ];

                    $infoModel->save($info);

                    //钱包信息
                    $wallet = [
                        'uid'   =>  $this->id
                    ];
                    $wallerModel = new UserWallet();
                    $wallerModel->save($wallet);

                    $this::commit();
                }catch (\Exception $e1){
                    $this::rollback();
                    exception('用户保存失败',20005);
                }
            }

            return_ajax(10000,'登陆成功',['token'=>$user['token']]);
        }catch (\Exception $e){
            return_ajax($e->getCode(),$e->getMessage());
        }
    }

    //刷新token
    public function refreshToken($data){
        try{
            if(empty($data['openid']))
                exception('openid不能为空',20001);
            $uid = $this->where(['openid'=>$data['openid']])->value('id');
            if(empty($uid))
                exception('用户不存在',20006);
            $token = $this->getToken($data['openid']);
            $this->allowField(true)->isUpdate(true)->save(['token'=>$token],['id'=>$uid]);

            return_ajax(10000,'token刷新成功',['token'=>$token]);
        }catch (\Exception $e){
            return_ajax($e->getCode(),$e->getMessage());
        }
    }

    //获取token
    public function getToken($openid){
        Loader::import('tool.Jwt', EXTEND_PATH,'.php');
        $jwt = new \Jwt;
        $now = time();
        $payload = [
            'iss'   =>  'miaommei2020',   //该JWT的签发者
            'iat'   =>  $now,               //签发时间
            'exp'   =>  $now + 3600*7,      //过期时间
            'nbf'   =>  $now,               //该时间之前不接收处理该Token
            'sub'   =>  $openid,            //面向的用户
            'jti'   =>  md5(uniqid('JWT').time())   //该Token唯一标识
        ];
        $token=$jwt->getToken($payload);

        return $token;
    }

    public function getName($id){
        return $this->where(['id'=>$id])->value('nickname');
    }
}