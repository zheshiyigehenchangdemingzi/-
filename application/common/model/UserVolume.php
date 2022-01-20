<?php
namespace app\common\model;

Class UserVolume extends Base
{
    //数据预处理
    public function getPriceAttr($value){
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getBalanceAttr($value){
        return number_format($value/100,2,'.','');
    }

    //获取用户购物券余额总数
    public function getVolumeNum($uid){
        return $this->where(['uid'=>$uid])->sum('balance');
    }

    //变更购物券余额 系统操作
    public function editVolume($data){
        try{
            if(empty($data['id']))
                exception('缺少用户参数');
            if(empty($data['volume_num']) || !is_numeric($data['volume_num']) || $data['volume_num'] <= 0)
                exception('请填写正确的积分数额');

            $volume = $this->where(['uid'=>$data['id']])->find();
            if(!$volume)
                exception('该用户没有购物券，不可充值');
            $total = $this->getVolumeNum($data['id']);
            $model = new UserVolumeLog();
            $this::startTrans();
            try{
                $log = [
                    'uid'       =>  $data['id'],
                    'type'      =>  2,
                    'vid'       =>  $volume['id'],
                    'price'     =>  $data['volume_num'],
                    'balance'   =>  $data['balance']+$data['volume_num'],
                    'is_sys'    =>  1,
                    'add_time' =>   time(),
                ];
                $model->allowField(true)->save($log);

                $this->where(['id'=>$volume['id']])->setInc('balance',$data['volume_num']);
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'变更成功',['volume'=>$total+$data['volume_num']]);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        if(!empty($get['id'])){
            $get['uid'] = $get['id'];
            unset($get['id']);
            $where = ['u.uid'=>$get['uid']];
        }
        $where['u.is_delete'] = 0;
        $field = 'v.title,u.uid,u.pid,u.order_sn,u.balance,u.pay_amount,u.status,u.add_time';
        $list = $this->alias('u')->join('volume v','u.volume_id=v.id','left')->where($where)->field($field)->order('u.id desc')
            ->paginate(20,false,array('query'=>$get));
        /*if(!empty($list)){
            foreach($list as $k=>$v)
                $list[$k]['name'] = $ret['name']??'购物卷';
        }*/
        $this->page = $list->render();
        $this->count = $this->alias('u')->where($where)->count();

        return $list;
    }
}