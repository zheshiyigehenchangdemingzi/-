<?php


namespace app\common\model\shop;



use app\common\model\Admin;
use app\common\model\Base;

// 店铺钱包流水表-记录
class ShopOrderLog extends Base
{
    // 选择框大全
    public $select_list = [
        // 类型
        'status' => [
            0 => "处理",
            1 => "完结"
        ],
        'retuen_status' => [
            0 => "无售后",
            1 => "售后-换货",
            2 => "售后-退款"
        ],
        // limit 数量
        'limit' => [
            10 => "10条",
            20 => "20条",
            30 => "30条",
            40 => "40条",
            50 => "50条",
            100 => "100条",
            150 => "150条",
            200 => "200条"
        ]
    ];

    // 键值提示
    public $key_titles = [
        "shop_id" => "商家序号",
        "oid" => "订单序号",
        "money" => "金额",
        "status" => "状态",
        "retuen_status" => "售后类型",
        "retuen_money" => "用户退款金额",
        "limit" => "数据条数",
        "start" => "开始时间开始",
        "end" => "结束时间结束",
    ];

    // viewData
    public $viewData = [
        "nav_title" => [
            "店铺管理",
            "商家订单记录"
        ],
        "serch_form" => [
            [
                "key" => "shop_id",
                "type" => "number",
            ],
            [
                "key" => "oid",
                "type" => "number",
            ],
            [
                "key" => "money",
                "type" => "number",
            ],
            [
                "key" => "retuen_money",
                "type" => "number",
            ],
            [
                "key" => "status",
                "type" => "select",
            ],
            [
                "key" => "retuen_status",
                "type" => "select",
            ],
            [
                "key" => "limit",
                "type" => "select",
            ],
            [
                "key" => "start",
                "type" => "time",
            ],
            [
                "key" => "end",
                "type" => "time",
            ]
        ],
        // 表头数据
        "thead" => [
            [
                "title" => "商家信息"
            ],
            [
                "title" => "金额"
            ],
            [
                "title" => "订单状态"
            ],
            [
                "title" => "是否售后"
            ],
            [
                'title' => '退款金额'
            ],
            [
                "title" => "时间"
            ]
        ]
    ];

    // 返回视图数据
    public function getViewData(){
        // 选择框
        $this->viewData['select_list'] = $this->select_list;
        // 键值提示
        $this->viewData['key_titles'] = $this->key_titles;
        return $this->viewData;
    }

    // 获取列表
    public function getList($get){
        $where = [];
        if(isset($get['money']) && $get['money']) $where['money'] = $get['money'];
        if(isset($get['oid']) && $get['oid']) $where['oid'] = $get['oid'];
        if(isset($get['status']) && strlen($get['status']) >= 1) $where['status'] = $get['status'];
        if(isset($get['retuen_status']) && strlen($get['retuen_status']) >= 1) $where['retuen_status'] = $get['retuen_status'];
        if(isset($get['action_type']) && strlen($get['action_type']) >= 1) $where['action_type'] = $get['action_type'];
        $list = $this->where($where)->order('add_time desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

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
     * 订单完结 处理逻辑
     */
    function orderFinish($order){
        # 店铺钱包模型
        if(!$walletOne = ShopWallet::where('shop_id',$order->shop_id)->find()) return false;
        # 店铺订单记录模型
        if(!$shopOrderOne = $this::where(['shop_id'=>$order->shop_id,'oid'=>$order->id,'status'=>0])->find())return false;
        # 改变状态店铺订单 记录 状态
        $this::where('id',$shopOrderOne->id)->update(['status' => 1]);
        # 更新 店铺钱包表
        ShopWallet::where('shop_id',$order->shop_id)->dec('pre_money',$shopOrderOne->money)->inc('money',$shopOrderOne->money)->update();
        $dataAll = [];
        $time = time();
        # 预估金额
        $dataAll[] = [
            'shop_id' => $order->shop_id,
            'money' => $shopOrderOne->money,
            'type' => 1,
            'status' => 0,
            'operating_money' => $walletOne->pre_money,
            'oid' => $order->id,
            'uid' => $order->uid,
            'action_type' => 1,
            'add_time' => $time
        ];
        # 余额
        $dataAll[] = [
            'shop_id' => $order->shop_id,
            'money' => $shopOrderOne->money,
            'type' => 2,
            'status' => 1,
            'operating_money' => $walletOne->money,
            'oid' => $order->id,
            'uid' => $order->uid,
            'action_type' => 1,
            'add_time' => $time
        ];
        return ShopWalletLog::insertAll($dataAll);
    }

// 获取到店铺
    public function getShopOne(){
        return $this::hasOne(ShopUsers::class,'id','shop_id');
    }
}