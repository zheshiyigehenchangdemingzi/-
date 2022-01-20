<?php
namespace app\common\model;

Class UserWallet extends Base
{
    //钱包指定字段增加数额
    public function incReward($uid,$field,$int=1){
        $this->where(['uid'=>$uid])->setInc($field,$int);
    }

    //钱包指定字段减除数额
    public function decReward($uid,$field,$int=1){
        $this->where(['uid'=>$uid])->setDec($field,$int);
    }

    //获取钱包信息
    public function getWallet($get){
        $model = new UserCash();
        $where = ['uid'=>$get['id']];
        $field = 'balance,miaos,miao';
        $data = $this->where($where)->field($field)->find();
        $data['balance'] = number_format($data['balance']/100,2,'.','');
        $data['miaos'] = number_format($data['miaos']/100,2,'.','');
        $data['miao'] = number_format($data['miao']/100,2,'.','');
        $data['gold'] = $model->getSumBalance($get)['gold'];
        return $data;
    }

    //获取单一信息
    public function getField($uid,$field){
        return $this->where(['uid'=>$uid])->value($field);
    }

    //变更积分余额 系统操作
    public function editInt($data){
        try{
            if(empty($data['id']))
                exception('缺少用户参数');
            if(empty($data['int_num']) || !is_numeric($data['int_num']) || $data['int_num'] == 0)
                exception('请填写正确的积分数额');
            $user = $this->where(['uid'=>$data['id']])->find();
            if(!$user)
                exception('用户信息错误');
            $model = new IntegralLog();
            $this::startTrans();
            try{
                $log = [
                    'uid'       =>  $data['id'],
                    'type'      =>  1,
                    'style'     =>  $data['int_num'] > 0?1:2,
                    'num'       =>  $data['int_num'],
                    'add_time' =>   time(),
                ];
                $model->allowField(true)->save($log);

                $this->where(['uid'=>$data['id']])->setInc('integral',$data['int_num']);
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'变更成功',['int_num'=>$user['integral']+$data['int_num']]);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 系统增加用户余额
     */
    public function incBalance($data){
        try{
            if(empty($data['id']))
                exception('缺少用户参数');
            if(empty($data['balance_num']) || !is_numeric($data['balance_num']) || $data['balance_num'] == 0)
                exception('请填写正确的余额');
            $user = $this->where(['uid'=>$data['id']])->find();
            if(!$user)
                exception('用户信息错误');
            $model = new BalanceLog();
            $this::startTrans();
            try{
                $log = [
                    'uid'       =>  $data['id'],
                    'type'      =>  1,
                    'num'       =>  $data['balance_num'],
                    'is_sys'   =>  1,
                    'add_time' =>   time(),
                ];
                $model->allowField(true)->save($log);//记录日志

                $this->where(['uid'=>$data['id']])->setInc('balance',$data['balance_num']);
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'变更成功',['balance_num'=>number_format(($user['balance']+$data['balance_num'])/100,2,'.','')]);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}