<?php
namespace app\common\model;

Class GoodsRole extends Base
{
    //获取商品权限相关设置
    public function getRole($goodsId,$field){
        $data = $this->where(['goods_id'=>$goodsId])->field($field)->find()->toArray();
        if(!empty($data['live_conf']))
            $data['live_conf'] = json_decode($data['live_conf'],true);
        if(!empty($data['sale_conf']))
            $data['sale_conf'] = json_decode($data['sale_conf'],true);
        if(!empty($data['role_conf']))
            $data['role_conf'] = json_decode($data['role_conf'],true);
        if(!empty($data['role_def']))
            $data['role_def'] = json_decode($data['role_def'],true);
        return $data;
    }

    //设置商品相关权限
    public function addRole($data){
        try{
            if(empty($data['id']))
                exception('商品错误');

            if($data['style'] == 'live'){
                if(empty($data['live_val']) || !is_numeric($data['live_val']))
                    exception('直播分红没有填写分红方式的默认值');

                $content = [];
                if($data['is_live_odd'] == 1){
                    $count = count($data['key']);
                    for ($i = 0;$i<$count;$i++){
                        $content[$data['key'][$i]] = $data['value'][$i]<0?0:$data['value'][$i];
                    }
                    $content['type'] = $data['type'];
                }
                $role = [
                    'is_live'       =>  $data['is_live'] == 1?1:0,
                    'live_type'     =>  $data['live_type'] == 1?1:2,
                    'live_val'      =>  $data['live_val']<0?0:$data['live_val'],
                    'is_live_odd'   =>  $data['is_live_odd'] == 1?1:0,
                    'live_conf' =>  json_encode($content),
                ];

                $msg = '直播分红权限已更新';
            }elseif($data['style'] == 'sale'){
                $count = count($data['key']);
                $content = [];
                for ($i = 0;$i<$count;$i++){
                    $content[$data['key'][$i]] = $data['value'][$i]<0?0:$data['value'][$i];
                }
                $content['type'] = $data['type'];

                $role = [
                    'is_sale_odd'   =>  $data['is_sale_odd'] == 1?1:0,
                    'sale_conf' =>  json_encode($content),
                ];

                $msg = '分销分佣权限已更新';
            }elseif($data['style'] == 'role'){
                $content = [];
                if($data['is_role_odd'] == 1){
                    $count = count($data['key']);
                    for ($i = 0;$i<$count;$i++){
                        $content[$data['key'][$i]]['limit'] = $data['limits'][$i] < 0?0:$data['limits'][$i];
                        $content[$data['key'][$i]]['count'] = $data['counts'][$i] < 0?0:$data['counts'][$i];
                        $content[$data['key'][$i]]['day'] = $data['days'][$i] < 0?0:$data['days'][$i];
                        $content[$data['key'][$i]]['week'] = $data['weeks'][$i] < 0?0:$data['weeks'][$i];
                        $content[$data['key'][$i]]['mouth'] = $data['mouths'][$i] < 0?0:$data['mouths'][$i];
                    }
                }
                $default = [];
                $default['limit'] = $data['limit']<0?0:$data['limit'];
                $default['count'] = $data['count']<0?0:$data['count'];
                $default['day'] = $data['day']<0?0:$data['day'];
                $default['week'] = $data['week']<0?0:$data['week'];
                $default['mouth'] = $data['mouth']<0?0:$data['mouth'];

                $role = [
                    'is_role'       =>  $data['is_role'] == 1?1:0,
                    'role_def'     =>  json_encode($default),
                    'is_role_odd'   =>  $data['is_role_odd'] == 1?1:0,
                    'role_conf' =>  json_encode($content),
                ];
                $msg = '会员权限已更新';
            }else{
                exception('未定义权限');
            }

            $this->allowField(true)->isUpdate(true)->save($role,['goods_id'=>$data['id']]);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}