<?php
namespace app\common\model;

use app\api\model\UserWallet;
use app\common\model\shop\ShopOrderLog;
use app\common\model\shop\ShopWallet;
use think\Cache;

use think\cache\driver\Redis;
//use think\Db;
use think\Db;
use think\image\Exception;
use think\Log;

Class Order extends Base
{
    //数据预处理
    public function getTotalAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getFreightAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getVolumeAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getCouponMoneyAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getDeliverAttr($value)
    {
        $deliver = [
            1   =>  '快递配送',
            2   =>  '到店自提',
            3   =>  '同城配送'
        ];
        return $deliver[$value];
    }

    //数据预处理
    public function getTypeAttr($value)
    {
        $type = [
            1   =>  '常规订单',
            2   =>  '活动订单',
            3   =>  '直播订单',
            4   =>  '视频订单'
        ];
        return $type[$value];
    }

    //数据预处理
    public function getAddressJsonAttr($value)
    {
        return json_decode($value,true);
    }

    public $page = '';//分页数据
    public $count = '';//数据总数
    //获取商品列表
    /*
    public function getList($get){
        $orderIds1 = [];
        $orderIds2 = [];
        $where = [];

        // 订单类型
        if(strlen($get['type']) >= 1) $where['o.type'] = $get['type'];
        //下单时间
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['o.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['o.add_time'] = ['gt',strtotime($get['start'])];
            }
        }
        //订单状态
        if(!empty($get['status']))
            $where['o.status'] = $get['status'];
        //订单序号
        if(!empty($get['id']))
            $where['o.id'] = $get['id'];
        //用户昵称
        if(!empty($get['uname']))
            $where['u.name'] = ['like',$get['uname'].'%'];
        //下单用户
        if(!empty($get['uid']))
            $where['o.uid'] = $get['uid'];
        //店铺序号
        if(!empty($get['shop_id']))
            $where['o.shop_id'] = $get['shop_id'];

        // 商品名称
        if(!empty($get['gname'])){
            $mod1 = new Goods();
            $goodIds1 = $mod1->where(['name'=>['like',$get['gname'].'%'],'is_delete'=>0])->column('id');
            $mod2 = new OrderGoods();
            $orderIds1 = $mod2->where(['goods_id'=>['in',$goodIds1]])->column('order_id');
        }

        //订单号
        if(!empty($get['orser_sn']))
            $where['o.orser_sn'] = ['like',$get['orser_sn'].'%'];
        //收货人
        if(!empty($get['receipt_name'])) $where['o.receipt_name'] = ['like','%'.$get['receipt_name'].'%'];
        //收货人电话
        if(!empty($get['receipt_phone'])) $where['o.receipt_phone'] = ['like','%'.$get['receipt_phone'].'%'];
        //收货人地址
        if(!empty($get['receipt_address'])) $where['o.receipt_address'] = ['like','%'.$get['receipt_address'].'%'];
        //货号
        if(!empty($get['spu'])){
            if(empty($mod1))
                $mod1 = new Goods();
            $goodIds2 = $mod1->where(['spu'=>['like','%'.$get['spu'].'%'],'is_delete'=>0])->column('id');
            if(empty($mod2))
                $mod2 = new OrderGoods();
            $orderIds2 = $mod2->where(['goods_id'=>['in',$goodIds2]])->column('order_id');
        }
        if(!empty($get['type'])) $where['o.type'] = $get['type'];
        if(isset($get['shop_type']) && strlen($get['shop_type']) > 0){$where['o.shop_id'] = $get['shop_type'] == 1 ? ['>',0] : 0;}
        $orderIds = array_merge($orderIds1,$orderIds2);
        if($orderIds) $where['o.id'] = ['in',$orderIds];
        $field = 'o.id,o.aid,o.order_sn,o.add_time,o.volume,o.type,o.status,u.nickname as uname,u.is_fictitious,o.deliver,o.total,o.express_id,o.coupon_money,o.shop_id,o.receipt_name,o.receipt_phone,o.receipt_address';
        $list = $this->alias('o')
            ->join('user u','o.uid=u.id','left')
            ->where($where)->field($field)->order('o.id desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            if(empty($mod2))
            foreach ($list as &$v){
                $v = $this->getOrderDefault($v);
            }unset($v);
        }
        $this->page = $list->render();
        $this->count = $this->alias('o')->join('user u','o.uid=u.id','left')
            ->join('user_address a','o.addr_id=a.id','left')->where($where)->count();
        return $list;
    }
    */
    public function getList($get){
        $orderIds1 = [];
        $orderIds2 = [];
        $where = [];

        // 订单类型
        if(strlen($get['type']) >= 1) $where['o.type'] = $get['type'];
        //下单时间
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['o.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['o.add_time'] = ['gt',strtotime($get['start'])];
            }
        }
        //订单状态
        if(!empty($get['status'])) $where['o.status'] = $get['status'];
        //订单序号
        if(!empty($get['id'])) $where['o.id'] = $get['id'];
        //用户昵称
        if(!empty($get['nickname'])) $where['u.nickname'] = ['like',$get['nickname'].'%'];
        //下单用户
        if(!empty($get['uid'])) $where['o.uid'] = $get['uid'];
        //店铺序号
        if(!empty($get['shop_id'])) $where['o.shop_id'] = $get['shop_id'];

        // 商品名称
        if(!empty($get['gname'])){
            $mod1 = new Goods();
            $goodIds1 = $mod1->where(['name'=>['like',$get['gname'].'%'],'is_delete'=>0])->column('id');
            $mod2 = new OrderGoods();
            $orderIds1 = $mod2->where(['goods_id'=>['in',$goodIds1]])->column('order_id');
        }
        //return $orderIds1;
        //exit;

        //订单号
        if(!empty($get['order_sn'])) $where['o.order_sn'] = ['like',$get['order_sn'].'%'];

        //收货人
        if(!empty($get['receipt_name'])) $where['o.receipt_name'] = ['like','%'.$get['receipt_name'].'%'];


        //收货人电话
        if(!empty($get['receipt_phone'])) $where['o.receipt_phone'] = ['like','%'.$get['receipt_phone'].'%'];
        //收货人地址
        if(!empty($get['receipt_address'])) $where['o.receipt_address'] = ['like','%'.$get['receipt_address'].'%'];
        //货号
        if(!empty($get['spu'])){
            //if(empty($mod1))
            $mod12 = new Goods();
            $goodIds2 = $mod12->where(['spu'=>['like','%'.$get['spu'].'%'],'is_delete'=>0])->column('id');
            // if(empty($mod2))
            $mod22 = new OrderGoods();
            $orderIds2 = $mod22->where(['goods_id'=>['in',$goodIds2]])->column('order_id');
        }
        //exit;
        if(!empty($get['type'])) $where['o.type'] = $get['type'];
        if(isset($get['shop_type']) && strlen($get['shop_type']) > 0){$where['o.shop_id'] = $get['shop_type'] == 1 ? ['>',0] : 0;}

        $orderIds = [];
        if($orderIds1 && $orderIds2) {
            $orderIds = array_merge($orderIds1, $orderIds2);
        }else if($orderIds1){
            $orderIds = $orderIds1;
        }else if($orderIds2){
            $orderIds = $orderIds2;
        }
        if($orderIds) $where['o.id'] = ['in',$orderIds];

        $field = 'o.id,o.aid,o.order_sn,o.add_time,o.volume,o.type,o.status,u.nickname as uname,u.is_fictitious,o.deliver,o.total,o.express_id,o.coupon_money,o.shop_id,o.receipt_name,o.receipt_phone,o.receipt_address,o.remarks';

        $list = $this->alias('o')
            ->join('user u','o.uid=u.id','left')
            ->where($where)->field($field)->order('o.id desc')
            ->paginate($get['limit'],false,array('query'=>$get));

        //return $list;
        //exit;
        if($list){
            //if(empty($mod2))
            foreach ($list as &$v){
                $v = $this->getOrderDefault($v);
            }
            unset($v);
        }
        //return 1;
        //exit;
        $this->page = $list->render();
        unset($where['goods']);
        // exit;
        $this->count = $this->alias('o')->join('user u','o.uid=u.id','left')
            ->join('user_address a','o.addr_id=a.id','left')->where($where)->count();
        //exit;
        return $list;
    }

    //获取订单列表
    public function getList1($get){
        $where = ['uid'=>$get['id']];
        $field = 'order_sn,total,status,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    //确认收货
    public function confirmReceipt($orderId){
        // 开启事务
        $this::startTrans();
        try{
            if(empty($orderId))
                exception('请选择需要确认收货的订单');
            $order = $this->where(['id'=>$orderId])->field('id,uid,status')->find();
            if(empty($order))
                exception('订单错误');
            if($order['status'] != 3)
                exception('订单不是待收货状态');
            # 判断是否为 拼团  且 入团
            $orderInfoModel = OrderInfo::where('oid',$order->id)->find();
            if($orderInfoModel && $orderInfoModel->switch_num == 3){
                $ActivityLogModel = ActivityLog::where('oid',$order->id)->find();
                # 查找父级 必须有父级id
                if($ActivityLogModel && $ActivityLogModel->pid > 0 && $ActivityLogPModel = ActivityLog::find($ActivityLogModel->pid)){
                    if($ActivityLogPModel->partake && $listObj = json_decode($ActivityLogPModel->partake,true)){
                        $newData = false;
                        # 迭代循环, 需要没有结算 且等于当前
                        foreach($listObj as $k => &$item){
                            # 订单相等
                            if($item['uid'] == $order->uid && $item['status'] == 0 && $item['oid'] == $order->id){
                                $item['status']  = 1;
                                $listObj[$k]['status'] = 1;
                                $newData = $item;
                                break;
                            }
                        }
                        if($newData && $newData['miao'] > 0){
                            # 改变状态 并且 修改 状态
                            ActivityLog::where('id',$ActivityLogPModel->id)->update([
                                'partake' => json_encode($listObj)
                            ]);
                            # 改变 喵呗  并 插入记录   
                            $UserWalletPmodel = UserWallet::where('uid',$ActivityLogPModel->uid)->find();
                            UserWallet::where('uid',$ActivityLogPModel->uid)->update([
                                'pre_miao' => $UserWalletPmodel->pre_miao -  $newData['miao'] > 0 ? $UserWalletPmodel->pre_miao -  $newData['miao'] : 0,
                                'miao' => $UserWalletPmodel->miao + $newData['miao'],
                                'miaos' => $UserWalletPmodel->miaos + $newData['miao']
                            ]);
                            # 记录 钱包操作记录 -  此处需要记录的有三条
                            $wallLogData = [];$time = time();
                            # 记录  -  扣掉 预估喵呗
                            $wallLogData[] = [
                                'status' => 2,
                                'type' => 2,
                                'uid' => $UserWalletPmodel->uid,
                                'reward' => $newData['miao'],
                                'extend' => 'ActivityLog',
                                'extend_id' => $ActivityLogPModel->id,
                                'describe' => '后台-订单结算-拼团-下级团员处理-扣除待预估喵呗',
                                'add_time' => $time
                            ];
                            # 记录  -   增加的喵呗
                            $wallLogData[] = [
                                'status' => 1,
                                'type' => 2,
                                'uid' => $UserWalletPmodel->uid,
                                'reward' => $newData['miao'],
                                'extend' => 'ActivityLog',
                                'extend_id' => $ActivityLogPModel->id,
                                'describe' => '后台-订单结算-拼团-下级团员处理-增加喵呗',
                                'add_time' => $time
                            ];
                            # 记录  -  -增加累计喵呗
                            $wallLogData[] = [
                                'status' => 1,
                                'type' => 2,
                                'uid' => $UserWalletPmodel->uid,
                                'reward' => $newData['miao'],
                                'extend' => 'ActivityLog',
                                'extend_id' => $ActivityLogPModel->id,
                                'describe' => '后台-订单结算-拼团-下级团员处理-增加累计喵呗',
                                'add_time' => $time
                            ];
                            # 喵呗 更改状态 状态为 预估 且 当前订单号 和 父级uid  拼团
                            $miaoLogUId = MiaoLog::where(['status' => 1, 'uid' => $ActivityLogPModel->uid,'oid' => $ActivityLogPModel->oid,'type' => 2,'reward' => $newData['miao']])->update([
                                'status' => 3 // 入账
                            ]);
                            # 喵呗更改错误
                            if(!$miaoLogUId) return return_ajax(400,'喵呗更改错误');
                            Db::name('wallet_operation_log')->insertAll($wallLogData);
                        }
                    }
                }
            }
            UserShareOrder::where('oid',$orderId)->update([
                'status' => 4
            ]);
            $this->allowField(true)->isUpdate(true)->save(['status'=>4,'receive_time'=>time()],['id'=>$orderId]);
            # 提交事务
            $this::commit();
            return_ajax(200,'订单已确认收货');
        }catch (\Exception $e){
            $this::rollback();
            return_ajax(400,$e->getMessage());
        }
    }

    //结束订单
    public function finishOrder($orderId){
        try{
            if(empty($orderId))
                exception('请选择需要结束的订单');
            $order = $this->where(['id'=>$orderId])->field('status,id,shop_id,uid')->find();
            if(empty($order))
                exception('订单错误');
            if($order['status'] != 4)
                exception('订单不是待完成状态');
            $orderGoods = OrderGoods::where('order_id',$order->id)->find();
            // 开启事务
            $this::startTrans();
            try{
                $ok = $this->allowField(true)->isUpdate(true)->save(['status'=>5,'complete_time'=>time()],['id'=>$orderId]);
                UserShareOrder::where('oid',$orderId)->update([
                    'status' => 5
                ]);
                if(!$ok)
                    return return_ajax(400,'错误');
                $redis = new Redis([
                    'host'   => '127.0.0.1',  //redis服务器ip
    //                'password' => 'zeng123456',
                    'port'   => '6379',
                    'select' => 9
                ]);
                # 获取喵呗 array  状态为  预估  1
                $miaoLogs = MiaoLog::where(['oid'=>$order->id,'status'=>1])->select();
                if(!$miaoLogs){
                    Log::write('程序bug,请查看，订单id'.$order->id,'订单完成bug-请查看');
                    return_ajax(200,'订单已完成，已推入');
                }
                $time = time();
                // 钱包
                $wallLogData = [];
                // 迭代
                foreach($miaoLogs as $v){
                    $wallLogData[] = [
                        'status' => 1,
                        'type' => 2,
                        'uid' => $v->uid,
                        'reward' => $v->reward,
                        'extend' => 'order',
                        'extend_id' => $order->id,
                        'describe' => '后台-订单结算-增加待结算喵呗',
                        'add_time' => $time
                    ];
                    $wallLogData[] = [
                        'status' => 2,
                        'type' => 2,
                        'uid' => $v->uid,
                        'reward' => $v->reward,
                        'extend' => 'order',
                        'extend_id' => $order->id,
                        'describe' => '后台-订单结算-扣除待结算喵呗',
                        'add_time' => $time
                    ];
                    // 操作钱包
                    $userWall = UserWallet::where('uid',$v->uid)->find();
                    // 扣掉当前喵呗里面的预估 增加待结算的喵呗
                    if($userWall){
                        UserWallet::where('uid',$v->uid)->update([
                            'pre_miao' => $userWall->pre_miao - $v->reward > 0 ? $userWall->pre_miao - $v->reward : 0,
                            'wait_miao' => $userWall->wait_miao + $v->reward
                        ]);
                    }
                }
                # 如果有联币记录
                $miaologCount = MiaoLog::where('oid',$order->id)->count();
                if($miaologCount > 0){
                    # 更改喵呗-记录 待结算状态
                    $miaoLogId = MiaoLog::where('oid',$order->id)->update([
                        'status' => 2, // 改状态为待结算
                    ]);
                    if(!$miaoLogId) return return_ajax(400,'喵呗记录---错误，重新尝试');
                }   
                # 推入 团队
                if($orderGoods->is_team == 1) $b = $redis->set('mmei_miaolog:team:'.$order->id,1,20);
                # 推入 分享
                if($orderGoods->is_sale == 1) $b1 = $redis->set('mmei_miaolog:sale:'.$order->id,1,20);
                # 插入对应的记录 # 插入钱包操作记录
                if($wallLogData) Db::name('wallet_operation_log')->insertAll($wallLogData);
                # 如果有店铺
                if($order && $order->shop_id && $order->shop_id > 0)(new ShopOrderLog())->orderFinish($order);
                $this::commit();
                return return_ajax(200,'操作ok'.$b.$b1);
            }catch (Exception $e){
                $this::rollback();
                return return_ajax(400,'错误'.$e->getMessage());
            }
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //获取单个订单信息
    public function getOne($id){
        $field = 'o.id,o.order_sn,o.add_time,o.pay_type,o.volume,o.type,o.status,u.nickname,o.deliver,o.total,
        o.pay_time,o.deliver_time,o.receive_time,o.complete_time,o.remarks,o.express_id,u.nickname,u.phone
         utel,a.name,a.tel,a.province,a.city,a.area,o.coupon_money,o.receipt_name,o.receipt_phone,o.receipt_address';
        // i.nickname,i.phone
        $info = $this->alias('o')
            ->join('user u','o.uid=u.id','left')
            //->join('user i','o.uid=i.uid','left')
            ->join('user_address a','o.addr_id=a.id','left')
            //->join('express e','e.id=o.express_id','left')
            ->where(['o.id'=>$id])->field($field)->find();
        $info = $this->getOrderDefault($info);
        return $info;
    }

    //订单退款
    public function orderRefund($data){
        try{
            if(empty($data['id']))
                exception('请选择需要审核的退款订单');
            if(empty($data['check']))
                exception('请选择审核结果');
            $order = $this->where(['id'=>$data['id']])->field('status')->find();
            if(empty($order))
                exception('订单错误');
            if($order['status'] != 8)
                exception('此订单不在申请退款状态');

            $msg = '审核已通过';
            if($data['check'] == 1){
                $this->allowField(true)->isUpdate(true)->save(['status'=>9],['id'=>$data['id']]);
            }elseif($data['check'] == 2){
                if(empty($data['reason']))
                    exception('拒绝理由不能为空');
                $this->allowField(true)->isUpdate(true)->save(['status'=>2,'reason'=>$data['reason']],['id'=>$data['id']]);
                $msg = '审核已拒绝';
            }else{
                exception('审核类型未识别');
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //获取用户订单消费总金额
    public function getOrderTotal($uid){
        $total = $this->where(['uid'=>$uid,'status'=>5])->sum('total');
        $refund = $this->where(['uid'=>$uid,'status'=>5])->sum('refund');
        return number_format($total - $refund,2,'.','');
    }

    //获取用户订单总数
    public function getOrderNum($uid){
        return $this->where(['uid'=>$uid,'status'=>5])->count();
    }

    // 根据单个订单拿取信息
    public function getOrderDefault(&$v){
        $v['purchase_money'] = 0;
        $orderInfoOne = OrderInfo::where('oid',$v['id'])->field('purchase_money,purchase_ratio')->find();
        $v['purchase_money'] = $orderInfoOne['purchase_money'];
        $v['purchase_ratio'] = $orderInfoOne['purchase_ratio'];
        $sum_money = 0;
        $goodListV = [];
        $orderGoodsField = 'goods_id,attr_str,title,cost,price,num,freight,volume,purchase_money,purchase_ratio,img,coupon_money,volume_ratio';
        if($orderInfoOne && $goodsModel = OrderGoods::where('order_id',$v['id'])->field($orderGoodsField)->select()){
            $supply_url_arrs = [];
            foreach($goodsModel as $item){
                $supply_url = '';
                //如果存在客源链接 则  不需要拿取数据库
                if(count($supply_url_arrs) > 0 && isset($supply_url_arrs[$item['goods_id']])){
//                            $supply_url = $supply_url_arrs[$item['goods_id']];
                }else{
                    // 拿取客源链接
                    if($goodsOne =  Goods::where('id',$item['goods_id'])->field('supply_url')->find()){
                        if($goodsOne['supply_url'] && strlen($goodsOne['supply_url']) > 2){
                            $supply_url = $goodsOne['supply_url'];
                            $supply_url_arrs[$item['goods_id']] = $supply_url;
                        }
                    }
                }
                $item['supply_url'] = $supply_url;
                $goodListV[] = $item;
                $sum_money += bcmul($item['price'],$item['num'],2);
            }
        }
        $v['goods'] = $goodListV;
        $v['sum_money'] = $sum_money;
        return $v;
    }

    // 获取到快递信息
    public function getExpressOne(){
        return $this::hasOne(Express::class,'id','express_id');
    }
}