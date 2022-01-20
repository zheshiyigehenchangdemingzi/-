<?php


namespace app\common\model\shop;



use app\common\model\Base;
use think\Exception;

// 店铺钱包表
class ShopWallet extends Base
{
    /*
     * 获取信息
     */
    public function getOne($id){
        $info = $this->where('shop_id',$id)->find();
        // 如果没有则创建钱包 且当前状态为已审核
        if(!$info){
            $one = ShopUsers::field('apply_status')->find($id);
            if($one && $one->apply_status == 1){
                $this::insert([
                    'shop_id'=>$id
                ]);
            }
        }
        return $info;
    }

    /**
     * 编辑数据
     */
    public function edit($post,$adminId){
        if(!isset($post['money']) || $post['money'] < 0)return return_ajax(400,'金额不得小于0');
        if(!isset($post['pre_money']) || $post['pre_money'] < 0)return return_ajax(400,'预估金额不得小于0');
        if(!isset($post['security_deposit']) || $post['security_deposit'] < 0)return return_ajax(400,'保证金额不得小于0');
        $walletOne = $this::where('shop_id',$post['id'])->find();
        // 找出哪些数据是更改过的
        $moneys = ['security_deposit','pre_money','money'];
        $walletLogsArrs = [];
        $time = time();
        foreach($moneys as $k => $item){
            if($item){
                if($post[$item] > 0 && $walletOne[$item] != $post[$item]){
                    $num = $post[$item] - $walletOne[$item];
                    $status = $num < 0 ? 0 : 1;
                    $walletLogsArrs[] = [
                        'shop_id' => $post['id'] - 0,
                        'type' => $k,
                        'money' => $num < 0 ? -$num : $num,
                        'operating_money' => $walletOne[$item],
                        'status' => $status,
                        'admin_num' => $adminId,
                        'add_time' => $time,
                        'action_type' => 3
                    ];
                }
            }
        }
        $this::startTrans();
        try{
            $flag = $this::allowField(true)->save($post,[
                'shop_id' => $post['id']
            ]);
            if(!$flag)return return_ajax(400,'数据更新失败'.$flag);
            ShopWalletLog::insertAll($walletLogsArrs);
            $this::commit();
            return return_ajax(200,'数据更新ok');
        }catch (Exception $e){
            $this::rollback();
            return return_ajax(400,'网络错我'.$e->getMessage());
        }
    }

}