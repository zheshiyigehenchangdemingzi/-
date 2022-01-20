<?php
namespace app\common\model;

use app\common\model\shop\ShopOrderLog;
use app\common\model\shop\ShopWallet;
use app\common\model\shop\ShopWalletLog;

Class Service extends Base
{
    //数据预处理
    public function getPriceAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getRefundAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getVolumeAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    public $page = '';//分页数据
    public $count = '';//数据总数
    //获取商品列表
    public function getList($get){
        $where = [];
        //申请售后时间
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['s.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['s.add_time'] = ['gt',strtotime($get['start'])];
            }
        }
        //售后状态
        if(!empty($get['status'])) $where['s.status'] = $get['status'] - 1;
        // 序号
        if(!empty($get['id'])) $where['s.id'] = $get['id'];
        // 申请金额
        if(!empty($get['price'])) $where['s.price'] = $get['price'] * 100;
        // 申请数量
        if(!empty($get['apply_num'])) $where['s.apply_num'] = $get['apply_num'];
        //用户昵称
        if(!empty($get['uname'])) $where['u.nickname'] = ['like',$get['uname'].'%'];
        //申请售后用户
        if(!empty($get['uid'])) $where['s.uid'] = $get['uid'];
        // 类型
        if(!empty($get['type'])) $where['s.type'] = $get['type'];
        //订单序号
        if(!empty($get['order_id'])) $where['s.order_id'] = $get['order_id'];
        $field = 's.id,o.order_sn,u.nickname as name,s.service_sn,s.type,s.status,s.apply_reason,s.add_time,s.price,s.refund,s.volume,s.order_id as oid,s.change_express_id,s.merchant_express_id,s.apply_num,s.shop_id,u.id as uid';
        $list = $this->alias('s')->join('user u','s.uid=u.id','left')
            ->join('order o','s.order_id=o.id','left')
            ->where($where)->field($field)->order('id desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            $mod = new ServiceGoods();
            $field1 = 'sg.num,sg.price as return_price,og.img,og.attr_str,og.title,og.price';
            foreach ($list as &$v){
                $v['goods'] = $mod->alias('sg')->join('order_goods og','sg.order_goods_id=og.id','left')
                    ->where(['sg.service_id'=>$v['id']])->field($field1)->select();
            }unset($v);
        }
        $this->page = $list->render();
        $this->count = $this->alias('s')->join('user u','s.uid=u.id','left')->join('order o','s.order_id=o.id','left')->where($where)->count();
        return $list;
    }

    //通过售后订单审核
    public function toExamine($data){
        try{
            if(empty($data['id'])) exception('请选择需要审核的售后订单');
            if(empty($data['check'])) exception('请选择审核结果');
            $service = $this->where(['id'=>$data['id']])->find();
            if(empty($service)) exception('售后订单错误');
            if($service['status'] != 0) exception('此售后订单不在等待审核状态');

            $msg = '审核已通过';
            if($data['check'] == 1){
                $this->allowField(true)->isUpdate(true)->save(['status'=>2],['id'=>$data['id']]);
            }elseif($data['check'] == 2){
                if(empty($data['reply_reason'])) exception('拒绝理由不能为空');
                $this::startTrans();
                try{
                    //售后订单改为拒绝状态
                    $this->allowField(true)->isUpdate(true)->save(['status'=>6,'reply_reason'=>$data['reply_reason']],['id'=>$data['id']]);
                    OrderGoods::where([
                        'id' => $service['order_goods_id'],
                        'order_id' => $service['order_id']
                    ])->update([
                        'service' => 4
                    ]);
                    // 如果有店铺序号
                    if($service['shop_id'] && $service['shop_id'] > 0){
                        // 更新状态
                        $flag = ShopOrderLog::where([
                            'shop_id' => $service['shop_id'],
                            'oid' => $service['order_id'],
                            'order_goods_id' => $service['order_goods_id'],
                            'retuen_status' => 2
                        ])->update([
                            'retuen_status' => 0
                        ]);
                        if(!$flag)exception('网络异常');
                    }
                    //更改商品状态
//                    $model->allowField(true)->isUpdate(true)->save(['status'=>5,'complete_time'=>time()],['id'=>$service['order_id']]);
                    $this::commit();
                }catch (\Exception $e1){
                    $this::rollback();
                    exception($e1->getMessage());
                }
                $msg = '审核已拒绝';
            }else{
                exception('审核类型未识别');
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //退款
    public function toRefund($data){
        try{
            if(empty($data['id'])) exception('请选择需要操作的售后订单');
            if(!isset($data['refund']) || !is_numeric($data['refund'])) exception('请填写退款金额');
            $service = $this->where(['id'=>$data['id']])->field('id,uid,type,order_id,status,price,volume,shop_id,order_goods_id')->find();
            if(empty($service)) exception('售后订单信息错误');
            if($service['type'] != 2) exception('此售后订单不属于退货退款类型');
            if($service['status'] != 4) exception('售后订单状态不是待商家处理状态');
            if($data['refund'] > $service['price']) exception('退款金额不能大于用户申请退货金额');
            $mod1 = new UserWallet();
            $mod2 = new Order();
            $this::startTrans();
            try{
                $time = time();
                //退款金额退至用户余额
                $gold = $data['refund']*100;
                if($gold > 0){
//                    echo "当前退款金额：$gold\r\n";
//                    var_dump("增加前的用户余额：".$mod1->where(['uid'=>$service['uid']])->field('balance')->find()['balance']);
                    $mod1->where(['uid'=>$service['uid']])->setInc('balance',$gold);
//                    var_dump("增加后的用户余额：".$mod1->where(['uid'=>$service['uid']])->field('balance')->find()['balance']);
                    // 写入记录
                    $flag = WalletOperationLog::insert([
                        'uid' => $service['uid'],
                        'reward' => $gold,
                        'status' => 1,
                        'type' => 1,
                        'extend' => 'service',
                        'extend_id' => $service['id'],
                        'add_time' => $time,
                        'describe' => '售后退款-余额退还'
                    ]);
//                    echo $flag;
                }
                $volumeMoney = 0;
                // 判断是否使用了购物券  如果使用了则进行购物券退款
                if($service['volume'] && $service['volume'] > 0){
                    $volumeMoney = $service['volume'] * 100;
                    // 更新前的购物券余额
//                    var_dump("更新前的购物券余额：".$mod1->where(['uid'=>$service['uid']])->field('volume_balance')->find()['volume_balance']);
                    // 更新数据  - 并且记录
                    $mod1->where(['uid'=>$service['uid']])->setInc('volume_balance',$volumeMoney);
                    // 更新后的购物券余额
//                    var_dump("更新后的购物券余额：".$mod1->where(['uid'=>$service['uid']])->field('volume_balance')->find()['volume_balance']);
                    // 写入记录
                    WalletOperationLog::insert([
                        'uid' => $service['uid'],
                        'reward' => $volumeMoney,
                        'status' => 1,
                        'type' => 3,
                        'extend' => 'service',
                        'extend_id' => $service['id'],
                        'add_time' => $time,
                        'describe' => '售后退款-购物券退还'
                    ]);
                }

                //更改订单状态
                $mod2->allowField(true)->isUpdate(true)->save([
                    'complete_time'     =>  time(),
                    'refund'             =>  $gold,
                    'status'             =>  5
                ],['id'=>$service['order_id']]);
                // 判断是否有店铺信息
                if($service['shop_id'] && $service['shop_id'] > 0){
                    // 退款金额为 购物券 +  填写的余额
                    $shopMoney = ($volumeMoney + $gold ) / 100;
                    // 成交金额
                    $shopDealMoney = 0;
                    if($shopOrderLogOne = ShopOrderLog::where([
                        'shop_id' => $service['shop_id'],
                        'oid' => $service['order_id'],
                        'order_goods_id' => $service['order_goods_id']
                    ])->find()) $shopDealMoney = $shopOrderLogOne['money'];

                    // 退款金额操作
                    if($shopMoney > 0){
                        // 更新订单对应的金额
                        // 记录实际退款金额
                        ShopOrderLog::where([
                            'shop_id' => $service['shop_id'],
                            'oid' => $service['order_id'],
                            'order_goods_id' => $service['order_goods_id']
                        ])->update([
                            'retuen_money' => $shopMoney,
                            'status' => 1
                        ]);
                        $balance = $shopDealMoney - $shopMoney;
                        // 拿取店铺钱包信息
                        $ShopWalletOne = ShopWallet::where('shop_id',$service['shop_id'])->field('pre_money,money')->find();
                        // 减掉对应的 店铺成交金额 增加对应的  剩下金额
                        //ShopWallet::where('shop_id',$service['shop_id'])->setDec('pre_money',$shopDealMoney);
                        // 钱包数据
                        $shopWalletData = [
                            'pre_money' => $ShopWalletOne->pre_money - $shopMoney,
                        ];
                        // 钱包记录
                        $shopWalletLogData = [];
                        // 成交金额 - 记录
                        $shopWalletLogData[] = [
                            'shop_id' => $service['shop_id'],
                            'oid'   => $service['id'],
                            'operating_money' => $ShopWalletOne->pre_money,
                            'admin_num' => 0,
                            'type' => 1,
                            'status' => 0,
                            'add_time' => $time,
                            'uid' => $service['uid'],
                            'money' => $shopDealMoney,
                            'action_type' => 5,
                            'describe' => '后台管理员操作名称：'.__ADMIN_NAME__."，账号：".__ACCOUNT__
                        ];
                        if($balance > 0){
                            $shopWalletData['money'] = $ShopWalletOne->money + $balance;
                            // 提现金额 - 记录
                            $shopWalletLogData[] = [
                                'shop_id' => $service['shop_id'],
                                'oid'   => $service['id'],
                                'operating_money' => $ShopWalletOne->money,
                                'admin_num' => 0,
                                'type' => 2,
                                'status' => 1,
                                'add_time' => $time,
                                'uid' => $service['uid'],
                                'money' => $balance,
                                'action_type' => 5,
                                'describe' => '后台管理员操作名称：'.__ADMIN_NAME__."，账号：".__ACCOUNT__
                            ];
                        }
                        ShopWallet::where('shop_id',$service['shop_id'])->update($shopWalletData);
                        // 记录店铺钱包操作
                        $falg = ShopWalletLog::insertAll($shopWalletLogData);
                    }
                }
                //更改售后订单状态
                $this->allowField(true)->isUpdate(true)->save(['status'=>5,'refund'=>$gold,'complete_time'=>time()],['id'=>$service['id']]);
                $this::commit();
                return_ajax(200,'售后完结，订单已完结');
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //获取单个售后订单信息
    public function getOne($serviceId){
        $find = 's.id,s.price,s.cover,s.type,s.service_sn,o.order_sn,s.order_id,s.price,s.refund,s.apply_reason,s.reply_reason,s.status,s.add_time,s.volume,u.nickname as name';
        $service = $this->alias('s')->join('order o','s.order_id=o.id','left')
            ->join('user u','s.uid=u.id','left')
            ->where(['s.id'=>$serviceId])->field($find)->find();
        if($service['status'] == 3){
            $mod = new Express();
            $express = $mod->where(['type'=>2,'relation_id'=>$service['id']])->field('company,express_sn')->find();
            $service['company'] = $express['company'];
            $service['express_sn'] = $express['express_sn'];
            if($service['status'] == 4){
                $express1 = $mod->where(['type'=>1,'relation_id'=>$service['order_id']])->field('company,express_sn')->order('id desc')->find();
                $service['company1'] = $express1['company'];
                $service['express_sn1'] = $express1['express_sn'];
            }
        }
        $service['cover'] = explode(',',$service['cover']);
        $model = new ServiceGoods();
        $field1 = 'sg.num,sg.price as return_price,og.img,og.attr_str,og.title,og.price';
        $goods = $model->alias('sg')->join('order_goods og','sg.order_goods_id=og.id','left')
            ->where(['sg.service_id'=>$service['id']])->field($field1)->select();
        $service['goods'] = $goods;
        return $service;
    }

    // 发货处理
    public function toMerchantShipment($data){
        try{
            if(empty($data['id'])) exception('请选择需要操作的售后订单');
            if(empty($data['company']) || !is_numeric($data['company']) || $data['company'] <= 0) exception('请选择物流公司');
            if(empty($data['express_sn'])) exception('请填写物流单号');
            $service = $this->where(['id'=>$data['id']])->field('id,type,order_id,status')->find();
            if(empty($service)) exception('售后订单信息错误');
            if($service['type'] != 1) exception('此售后订单不属于换货类型');
            if($service['status'] != 4) exception('售后订单状态不是商家待发货状态');
            $mod1 = new Express();
            $this::startTrans();
            try{
                // 如果没有物流信息
                if($data['express_id'] == 0){
                    //记录物流信息
                    $eId = $mod1->insertGetId([
                        'type'          =>  1,
                        'company'       =>  $data['company'],
                        'express_sn'    =>  $data['express_sn'],
                        'relation_id'   =>  $service['id']
                    ]);
                    $this::where('id',$service['id'])->update([
                        'merchant_express_id' => $eId
                    ]);
                    return_ajax(200,'商家已发货');
                }else{
                    // 更新物流信息
                    $mod1->where('id',$data['express_id'])->update(['company' =>  $data['company'], 'express_sn' => $data['express_sn']]);
                    return_ajax(200,'商家编辑发货成功');
                }
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //换货物流
    public function toExpress($data){
        try{
            if(empty($data['id']))
                exception('请选择需要操作的售后订单');
            if(empty($data['company']) || !is_numeric($data['company']) || $data['company'] <= 0)
                exception('请选择物流公司');
            if(empty($data['express_sn']))
                exception('请填写物流单号');
            $service = $this->where(['id'=>$data['id']])->field('id,type,order_id,status')->find();
            if(empty($service)) exception('售后订单信息错误');
            if($service['type'] != 1) exception('此售后订单不属于换货类型');
            if($service['status'] != 3) exception('售后订单状态不是待商家处理状态');
            $mod1 = new Express();
            $mod2 = new Order();
            $this::startTrans();
            try{
                //记录物流信息
                $mod1->allowField(true)->save([
                    'type'          =>  1,
                    'company'       =>  $data['company'],
                    'express_sn'    =>  $data['express_sn'],
                    'relation_id'   =>  $service['order_id']
                ]);

                //更新订单状态
                $mod2->allowField(true)->isUpdate(true)->save(['status'=>3],['id'=>$service['order_id']]);

                //更改售后订单状态
                $this->allowField(true)->isUpdate(true)->save(['status'=>4,'complete_time'=>time()],['id'=>$service['id']]);
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'售后已完成');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /**
     * 确定收货 - 买家发货物流 - 商家确认
     */
    public function toReceipt($data){
        if(empty($data['id'])) exception('请选择需要操作的售后订单');
        if(!$one = $this::find($data['id'] - 0))exception('没有该记录');
        if($one->status != 3 || $one->change_express_id == 0)exception('状态错误');
        $this::where('id',$one->id)->update(['status' => 4]);
        return return_ajax(200,'确定收货成功');
    }

    /**
     * 一对一关联  买家发给商家的物流信息
     */
    public function getOneChangeExpress(){
        return $this::hasOne(Express::class,'id','change_express_id');
    }

    /**
     * 一对一关联  商家发给买家的物流信息
     */
    public function getOneMerchantExpress(){
        return $this::hasOne(Express::class,'id','merchant_express_id');
    }
}