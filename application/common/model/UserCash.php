<?php
namespace app\common\model;
use think\cache\driver\Redis;

use think\Db;

Class UserCash extends Base
{

    public $page = '';//分页数据
    public $count = '';//数据总数

    /*
     * 获取余额提现记录
     */
    public function getBalanceList($get){
        $where = [];
        if(!empty($get['id']))
            $where = ['c.uid'=>$get['id']];
        if(!isset($get['type'])) $where['c.type'] = 2;
        if(!empty($get['status'])) $where['c.status'] = $get['status'];
        if(!empty($get['uname']))
            $where['u.nickname'] = ['like','%'.$get['uname'].'%'];
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            $where['c.add_time'] = ['gt',$start];
        }
        if(!empty($get['end'])){
            $end = strtotime($get['end']."+1 day");
            if(!empty($start))
                $where['c.add_time']  =  ['between',[$start,$end]];
            else
                $where['c.add_time'] = ['lt',$end];
        }
        $field = 'c.id,c.uid,c.bid,c.gold,c.rate,c.amount,c.remarks,c.status,c.add_time,c.dk_time,c.withdrawal_type,b.name,u.nickname,IF(c.withdrawal_type=1,b.bank_name,"微信提现") as bank_name,IF(c.withdrawal_type=1,b.card,"微信提现") as card';
        $list = $this->alias('c')->join('user_bank b','c.bid=b.id','LEFT')->join('user u','c.uid=u.id','LEFT')->where($where)
            ->field($field)->order('c.id desc')->paginate(20,false,array('query'=>$get));
        foreach($list as $k=>$v){
            $list[$k]['gold'] = number_format($v['gold']/100,2,'.','');
            $list[$k]['rate'] = number_format($v['rate']/100,2,'.','');
            $list[$k]['amount'] = number_format($v['amount']/100,2,'.','');
        }
        $this->page = $list->render();
        if(isset($where['u.nickname'])) unset($where['u.nickname']);
        $this->count = $this->alias('c')->where($where)->count();
        return $list;
    }

    /*
     * 获取累计余额提现
     */
    public function getSumBalance($get){
        $where = ['uid' => $get['id'], 'status' => 3, 'type' => 2];
        $field = 'sum(gold) gold';
        $data = $this->where($where)->field($field)->find();
        $data['gold'] = number_format($data['gold']/100,2,'.','');
        return $data;
    }

    /*
     * 提现审核
     */
    public function withdrawExamine($data){
        try{
            if(empty($data['id'])) exception('记录为空');
            if(empty($data['type'])) exception('审核类型为空');
            $info = $this->where(['id'=>$data['id'],'type'=>2,'is_delete'=>0])->find();
            if(empty($info)) exception('记录不存在');
            $udata = ['status'=>1,'remarks'=>''];
            if($data['type'] == 1){
                $redis = new Redis([
                    'host'   => '127.0.0.1',  //redis服务器ip
                    //'password' => 'zeng123456',
                    'port'   => '6379',
                    'select' => 0
                ]);
                $key = 'withdrawal_'.$data['id'];
                $keyInfo = $redis->get($key);
                if(!empty($keyInfo)) exception('不能重复提现');
                if($info['status'] == 2) exception('该提现已审核');
                if($info['status'] == 3) exception('该提现已完成');
                $user = (new User())->where(['id'=>$info['uid'],'is_delete'=>0])->find();
                if($info['withdrawal_type'] == 2){
                    if(empty($user['openid'])) exception('提现用户为授权微信信息，无法微信提现！');
                    if($redis->setnx($key,$data['id'])){
                        $model = new WechatApi();
                        $apiData = [
                            'oid'=>$info['id'],
                            'partner_trade_no'=>getNumber(),
                            'amount'=>(int)$info['amount'],
                            'openid'=>$user['openid'],
                        ];
                        $orderInfo = $model->transfers($apiData);
                        if($orderInfo == true){
                            $udata['status'] = 3;
                            $udata['dk_time'] = time();
                            $udata['trade_no'] = $apiData['partner_trade_no'];
                        }else{
                            $redis->del($key);
                            exception($model->error);
                        }
                    }
                }else $udata['status'] = 2;
            }elseif($data['type'] == 2){
                if($info['status'] == 5) exception('该提现已拒绝');
                $udata = ['status'=>5,'remarks'=>$data['msg']??''];
            }elseif($data['type'] == 3){
                if($info['status'] == 3) exception('该提现已完成');
                $udata['status'] = 3;
                $udata['dk_time'] = time();
            }
            $this::startTrans();
            if($this->where(['id'=>$info['id']])->update($udata)){
                if($data['type'] == 2){ #拒绝提现，补回余额
                    (new UserWallet())->where(['uid'=>$info['uid']])->inc('balance',$info['gold'])->update();
                    Db::name('wallet_operation_log')->insertGetId(['uid'=>$info['uid'],'reward'=>$info['gold'],'status'=>1,'type'=>1,'extend'=>'user_cash','extend_id'=>$info['id'],'describe'=>'余额提现-审核拒绝','add_time'=>time()]);
                }
                $this::commit();
                return_ajax(200,'用户添加成功');
            }
            exception('审核失败');
        }catch (\Exception $e){
            $this::rollback();
            return_ajax(400,$e->getMessage());
        }
    }

}