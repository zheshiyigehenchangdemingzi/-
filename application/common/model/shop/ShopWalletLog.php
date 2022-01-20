<?php


namespace app\common\model\shop;



use app\common\model\Admin;
use app\common\model\Base;

// 店铺钱包流水表-记录
class ShopWalletLog extends Base
{
    // 选择框大全
    public $select_list = [
        // 类型
        'status' => [
            0 => "支出",
            1 => "收入"
        ],
        'type' => [
            0 => "保证金",
            1 => "预估",
            2 => "金额"
        ],
        // 操作类型
        'action_type' => [
            0 => '下单操作',
            1 => '后台-订单完结操作',
            2 => '自动-订单完结',
            3 => '后台管理人-更改',
            4 => '店铺-提现',
            5 => '售后'
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
        "uid" => "用户序号",
        "money" => "金额",
        "operating_money" => "操作前金额",
        "status" => "支出/收入",
        "action_type" => "操作来源",
        "type" => "类型",
        "limit" => "数据条数",
        "start" => "开始时间开始",
        "end" => "结束时间结束",
    ];

    // viewData
    public $viewData = [
        "nav_title" => [
            "店铺管理",
            "钱包流水记录"
        ],
        "serch_form" => [
            [
                "key" => "shop_id",
                "type" => "number",
            ],
            [
                "key" => "money",
                "type" => "number",
            ],
            [
                "key" => "operating_money",
                "type" => "number",
            ],
            [
                "key" => "oid",
                "type" => "number",
            ],
            [
                "key" => "uid",
                "type" => "number",
            ],
            [
                "key" => "type",
                "type" => "select",
            ],
            [
                "key" => "status",
                "type" => "select",
            ],
            [
                "key" => "action_type",
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
                "title" => "金额信息"
            ],
            [
                "title" => "类型"
            ],
            [
                "title" => "支出/收入"
            ],
            [
                "title" => "操作来源"
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
        if(isset($get['operating_money']) && $get['operating_money']) $where['operating_money'] = $get['operating_money'];
        if(isset($get['oid']) && $get['oid']) $where['oid'] = $get['oid'];
        if(isset($get['uid']) && $get['uid']) $where['uid'] = $get['uid'];
        if(isset($get['status']) && strlen($get['status']) >= 1) $where['status'] = $get['status'];
        if(isset($get['type']) && strlen($get['type']) >= 1) $where['type'] = $get['type'];
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
     * 编辑数据
     */
    public function edit($post){
        if(!isset($post['money']) || $post['money'] < 0)return return_ajax(400,'金额不得小于0');
        if(!isset($post['pre_money']) || $post['pre_money'] < 0)return return_ajax(400,'预估金额不得小于0');
        if(!isset($post['security_deposit']) || $post['security_deposit'] < 0)return return_ajax(400,'保证金额不得小于0');
        $flag = $this::allowField(true)->save($post,[
            'shop_id' => $post['id']
        ]);
        if(!$flag)return return_ajax(400,'数据更新失败');
        return return_ajax(200,'数据更新ok');
    }

// 获取到店铺
    public function getShopOne(){
        return $this::hasOne(ShopUsers::class,'id','shop_id');
    }

    // 获取到管理人
    public function getAdminOne(){
        return $this::hasOne(Admin::class,'id','admin_num');
    }
}