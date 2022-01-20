<?php
namespace app\common\model;

use think\Db;

Class Sign extends Base
{
    //获取配置
    public function toData($type){
        $configModel = new Config();
        $config = $configModel->where(['type'=>$type])->value('content');
        if(empty($config))
            return [];

        return json_decode($config,true);
    }

    //更改配置
    public function updateData($data)
    {
        $configModel = new Config();
        $configData = $configModel::where('type','sing_in_reward')->find();
        if($configData){
            $configData->content = json_encode($data);
            $configData->save();
        }else{
            $configModel = new Config();
            $configModel->type = 'sing_in_reward';
            $configModel->content = json_encode($data);
            $configModel->is_delete = 0;
            $configModel->desc = '连续打卡奖励配置';
            $configModel->save();
        }
    }

    //列表数据
    public function findsData($get = [])
    {
        $where = [];
        $where['ml.type'] = 8;
        $where['ml.order_type'] = 3;
        $miaoLogModel = new MiaoLog();

        /*搜索用户名的*/
        $userIds = [];
        if(!empty($get['nickname'])){
            //echo 1;exit;
            $userIds = User::where(['nickname' => ['like','%'.$get['nickname'].'%']])->column('id');
            if(!$userIds) return [];
        }

        /*搜索连续打卡天数*/
        $signData = [];
        if(!empty($get['days'])){
            //echo 1;exit;
            $signData = Sign::where(['days' => $get['days']])->column('uid');
            if(!$signData) return [];
        }

        //因为打卡天数和用户名的搜索条件在过滤时候重叠 需要做处理
        $uIds = [];
        if($userIds && $signData){
            $uIds = array_intersect($userIds,$signData);
            $where['ml.uid'] = ['in',$uIds];
        }else if($userIds){
            $uIds = $userIds;
            $where['ml.uid'] = ['in',$uIds];
        }else if($signData){
            $uIds = $signData;
            $where['ml.uid'] = ['in',$uIds];
        }

        /*搜索时间*/
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['ml.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['ml.add_time'] = ['gt',strtotime($get['start'])];
            }
        }

        //搜索数据
        $field = 'ml.id,ml.uid,ml.reward,ml.add_time,u.nickname,uw.miao,s.days';
        $join = [
            ['user u','ml.uid = u.id','left'],
            ['user_wallet uw','uw.uid = ml.uid','right'],
            ['sign s','s.uid = ml.uid','left']
        ];
        $miaoLogData = $miaoLogModel->alias('ml')
                                    ->join($join)
                                    ->where($where)
                                    ->field($field)
                                    ->order('ml.id desc')
                                    ->paginate($get['limit'], false, array('query' => $get));

        //渲染分页
        $this->page = $miaoLogData->render();
        //搜索多少条数
        $this->count = $miaoLogModel->alias('ml')
                                    ->join($join)
                                    ->where($where)
                                    ->count();
        return $miaoLogData;
    }
}