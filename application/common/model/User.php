<?php
namespace app\common\model;

use think\cache\driver\Redis;
use think\Loader;

Class User extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数
    public function getList($get){
        $where = [];
        //会员id
        if(!empty($get['uid']))
            $where['u.id'] = $get['uid'];
        //上级id
        if(!empty($get['invite_id'])) $where['u.invite_id'] = $get['invite_id'];
        //会员名称
        if(!empty($get['uname'])) $where['u.nickname'] = ['like','%'.$get['uname'].'%'];
        // 虚拟用户    
        if(isset($get['is_fictitious']) && strlen($get['is_fictitious']) >= 1) $where['u.is_fictitious'] = $get['is_fictitious'];
        //真实姓名
        if(!empty($get['name'])) $where['u.name'] = ['like','%'.$get['name'].'%'];
        //电话
        if(!empty($get['phone'])) $where['u.phone'] = ['like','%'.$get['phone'].'%'];
        // 会员层级
        if(!empty($get['level'])) $where['u.level'] = $get['level'];
        //注册时间
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['u.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['u.add_time'] = ['gt',strtotime($get['start'])];
            }
        }

        // 用户类型  如果存在
        if(strlen($get['is_userType']) >= 1){
            // 类型  0  游客，     1 潜在用户      2 有效用户
            
            if($get['is_userType'] == 0){
                $where['u.level'] = 0;
                $where['u.is_fictitious'] = 0;
            // 潜在用户    
            }else if($get['is_userType'] == 1){
                $where['u.level'] = 1;
                $where['u.is_fictitious'] = 0;
                $where['w.consume'] = 0;
            // 有效用户    
            }else if($get['is_userType'] == 2){
                $where['w.consume'] = ['>',0];
                $where['u.is_fictitious'] = 0;
            }
        }

        $field = 'u.id,u.avatarurl,u.add_time,u.nickname,u.level,u.invite_id,u.invite_code,w.balance,w.integral,u.is_fictitious,w.volume_balance,us.nickname as invite_name';
        $list = $this->alias('u')->join('user us','u.invite_id=us.id','left')
            ->join('user_wallet w','u.id=w.uid','inner')->where($where)
            ->field($field)->order('u.id desc')->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            $model = new UserInvite();
            foreach($list as &$val){
                $val['name'] = emojiDecode($val['nickname']);
                if($val['invite_id'] > 0){
                    $val['invite_id'] = $val['invite_name'];
                }else{
                    $val['invite_id'] = '无邀请人';
                }
                $invite_num = $model->loopInvite($val['id']);
                $val['invite_num'] = count($invite_num);
                $val['volume_num'] = number_format($val['volume_balance']/100,2,'.','');
                $val['balance'] = number_format($val['balance']/100,2,'.','');
            }unset($val);
        }
        $this->page = $list->render();
        $this->count = $this->alias('u')->join('user_wallet w','u.id=w.uid','inner')->where($where)->count();

        // 统计开始---
        // 今天  访客 潜在  有效 用户
        $time = strtotime(date('Y-m-d'));
        $this->today = [
            'fangke' => $this->where(['add_time'=>['>',$time],'level'=>0,'is_fictitious' => 0])->count(),
            'qianzai' => $this->alias('u')
                            ->join('user_wallet w','u.id=w.id')
                            ->where(['u.add_time'=>['>',$time],'u.level'=>1,'w.consume' =>0,'u.is_fictitious' => 0 ])->count(),
            'youxiao' => $this->alias('u')
            ->join('user_wallet w','u.id=w.id')
            ->where(['u.add_time'=>['>',$time],'u.level'=>1,'w.consume' =>['>',0] ])->count()
        ];

        //  总的
        $this->zongDay = [
            'fangke' => $this->where(['level'=>0,'is_fictitious' => 0])->count(),
            'qianzai' => $this->alias('u')
                            ->join('user_wallet w','u.id=w.id')
                            ->where(['u.level'=>1,'w.consume' =>0 ,'u.is_fictitious' => 0])->count(),
            'youxiao' => $this->alias('u')
            ->join('user_wallet w','u.id=w.id')
            ->where(['w.consume' =>['>',0], 'u.is_fictitious' => 0 ])->count()
        ];

        // 统计结束---
        return $list;
    }

    // 获取到所有虚拟的会员信息
    public function getUsers(){
        $redis = new Redis();
        $users = $redis->get('admin_users_list');
        if($users && is_array($users)) return $users;
        $users = $this->where(['is_delete'=>0,'is_fictitious'=>1])->field('id,nickname,avatarurl')->select()->toArray();
        $redis->set('admin_users_list',$users,3600);
        return $users;
    }

    //获取会员详情信息
    public function getInfo($id){
        $field = 'u.id,u.add_time,u.nickname as name,u.level,u.invite_id,u.invite_code,w.balance,w.integral,w.volume_balance as volume_num,u.avatarurl,u.phone,u.remarks,us.nickname as invite_name';
        $data = $this->alias('u')->join('user us','u.invite_id=us.id','left')->join('user_wallet w','u.id=w.uid','left')->where(['u.id'=>$id])->field($field)->find();
        $mod2 = new Order();
        $data['balance'] = number_format($data['balance']/100,2,'.','');
        $data['name'] = emojiDecode($data['name']);
        $data['volume_num'] = number_format($data['volume_num']/100,2,'.','');
        $data['order_total'] = $mod2->getOrderTotal($id);
        if($data['invite_id'] > 0){
            $data['invite_id'] = $data['invite_name'];
        }else{
            $data['invite_id'] = '无邀请人';
        }
        return $data;
    }

    //获取token
    public function getToken($openid){
        Loader::import('tool.Jwt', EXTEND_PATH,'.php');
        $jwt = new Jwt();
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

    //获取微信昵称
    public function getName($id){
        $name = $this->where(['id'=>$id])->value('nickname');
        return emojiDecode($name);
    }

    //添加用户
    public function addUser($data,$file){
        try{
            if(empty($data['tel']))
                exception('手机号不能为空');
            if(empty($data['name']))
                exception('昵称不能为空');
            if(empty($data['pass']))
                exception('密码不能为空');
            elseif(strlen($data['pass'])>12 || strlen($data['pass'])<6)
                exception('密码为6-12位组合');
            if(empty($data['level']) || !is_numeric($data['level']))
                $data['level'] = 0;
            if(empty($data['invite_id']) || !is_numeric($data['invite_id']))
                $data['invite_id'] = 0;
            if(empty($file['file']['name']))
                exception('请上传头像');

            if($this->where(['phone'=>$data['tel']])->find())
                exception('该手机号已存在');
            if($data['invite_id'] > 0){
                if(!$this->where(['id'=>$data['invite_id']])->find())
                    exception('上级用户不存在');
            }
            if($data['level'] > 0){
                $model = new UserLevel();
                if(!$model->where(['id'=>$data['level']])->find())
                    exception('此用户等级不存在');
            }
            $upload = imgUpdate('file','user');//广告图片上传
            $r = new Redis();
            if($upload && is_array($upload) && isset($upload['data'])){
                $dir = explode('/',$upload['data']);
                $file_name = COS_PREFIX.'/admin/userNickName/'.$dir[3].'/'.$dir[4];
                $d = $_SERVER['DOCUMENT_ROOT']; 
                $r->rpush('mmei_cos_files',$d.'/'.$upload['data']."@@@".'/admin/userNickName/'.$dir[3].'/'.$dir[4]);
            }
            /*
            dump([
                "存储sql"=>$file_name,
                "本地文件路劲_绝对" => $d.'/'.$upload['data'],
                "cos存储库路径" => '/admin/userNickName/'.$dir[3].'/'.$dir[4]
            ]);
             */
            if($upload['code'] == 400)
                exception($upload['msg']);

            $now = time();
            $model = new UserInfo();
            $mod = new UserWallet();
            $user = [
                'token'     =>  $this->getToken($data['tel']),
                'unionid'   =>  $data['tel'],
                'openid'    =>  $data['tel'],
                'phone'     =>  $data['tel'],
                'nickname'  =>  $data['name'],
                'name'       =>  $data['name'],
                'avatarurl' =>  isset($file_name) ? $file_name :$upload['data'],
                'level'         =>  $data['level'],
                'invite_id'     =>  $data['invite_id'],
                'invite_code'   =>  $this->getInvite(),
                'is_fictitious' =>  $data['is_fictitious'],
                'gender'    =>  0,
                'add_time'  =>  $now,
                'upd_time'  =>  $now,
                'last_time' =>  $now
            ];

            $this::startTrans();
            try{
                if(!$this->allowField(true)->save($user))
                    exception('用户信息添加失败');

                $info['uid'] = $this->id;
                if(!$model->allowField(true)->save(['uid'=>$this->id]))
                    exception('用户详情信息添加失败');

                if(!$mod->allowField(true)->save(['uid'=>$this->id]))
                    exception('用户钱包信息添加失败');
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'用户添加成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
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

    /*
     * 获取虚拟用户列表
     */
    public function getFictitious(){
        // $list = $this->where(['is_fictitious'=>1])->field('id,nickname')->select();
        $list = $this->field('id,nickname')->select();
        return empty($list) ? array():$list->toArray();
    }


    //获取下级数量
    public function getInviteNum($id){
        return $this->where(['invite_id'=>$id])->count();
    }


    //获取下级数量
    public function getInviteTop($id){
        return $this->alias('u')->join('user_invite i','u.id=i.uid','left')->where(['u.id'=>$id])->field('u.id,u.invite_id,i.top_id,i.upid,i.team_pid,i.pid')->find();
    }

    //更改推荐人
    public function editInvite($data){
        try{
            if(empty($data['id']))
                exception('缺少用户参数');
            if(empty($data['invite_id']))
                exception('推荐人id不能为空');
            if($data['id'] == $data['invite_id'])
                exception('推荐人不能是自己');
            $self = $this->where(['id'=>$data['id']])->find();
            if(!$self)
                exception('用户错误');
            $user = $this->where(['id'=>$data['invite_id']])->find();
            if(!$user)
                exception('推荐人错误');
            if($self['level']>$user['level'])
                exception('推荐人等级不能低于被推荐人');
            $model = new UserInvite();
            if($model->user_check($data['invite_id'],$data['id']))
                exception('推荐人与此会员存在闭合关系,不能建立推荐关系');
            if($model->bindTop($data['id'],$data['invite_id']) == false)
                exception('绑定异常，请联系客服');
            $this->allowField(true)->isUpdate(true)->save(['invite_id'=>$data['invite_id'],'upd_time'=>time(),'binding_time'=>time()],['id'=>$data['id']]);
            return_ajax(200,'变更成功',['invite'=>$user['nickname']]);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 修改备注
     */
    public function editRemarks($data){
        try{
            if(empty($data['id']))
                exception('缺少用户参数');
            if(empty($data['remarks']))
                exception('备注不能为空！');
            $user = $this->where(['id'=>$data['id']])->find();
            if(!$user)
                exception('用户id有误');
            $this->allowField(true)->isUpdate(true)->save(['remarks'=>$data['remarks'],'upd_time'=>time()],['id'=>$data['id']]);
            return_ajax(200,'变更成功',['remarks'=>$data['remarks']]);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 修改备注
     */
    public function editNickname($data){
        try{
            if(empty($data['id']))
                exception('缺少用户参数');
            if(empty($data['username']))
                exception('昵称不能为空！');
            $user = $this->where(['id'=>$data['id']])->find();
            if(!$user)
                exception('用户id有误');
            $this->allowField(true)->isUpdate(true)->save(['nickname'=>$data['username'],'upd_time'=>time()],['id'=>$data['id']]);
            return_ajax(200,'变更成功',['username'=>$data['username']]);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    public function getinvList($get){
        $where = ['invite_id'=>$get['id']];
        //会员昵称
        if(!empty($get['name']))
            $where['nickname'] = ['like',$get['name'].'%'];
        //手机号
        if(!empty($get['tel']))
            $where['phone'] = ['like',$get['tel'].'%'];

        $field = 'id,nickname as name,phone as tel,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        if($list){
            $model = new Order();
            foreach($list as &$val){
                $val['invite_num'] = $this->getInviteNum($val['id']);
                $val['order_num'] = $model->getOrderNum($val['id']);
                $val['order_total'] = $model->getOrderTotal($val['id']);
            }unset($v);
        }
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    //获取会员等级信息
    public function getLevel($uid){
        return $this->where(['id'=>$uid])->field('invite_id,level')->find();
    }


    //获取团队业绩相关
    public function getTeam($uid){
        return $this->alias('u')->join('user_wallet w','u.id=w.uid','left')
            ->where(['u.id'=>$uid])->field('u.level,u.invite_id,w.teams,w.consume')->find();
    }
}