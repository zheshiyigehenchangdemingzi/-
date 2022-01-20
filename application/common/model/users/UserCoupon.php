<?php


namespace app\common\model\users;



use app\common\model\Base;
use app\common\model\shop\ShopCoupon;
use app\common\model\shop\ShopUsers;
use app\common\model\User;

// 商家优惠券列表
class UserCoupon extends Base
{
    // 选择框大全
    public $select_list = [
        // 类型
        'status' => [
            0 => "未使用",
            1 => "已使用",
            2 => "商家作废"
        ],
        // condition 条件
        'use_status' => [
            1 => '全店铺使用',
            2 => '指定商品'
        ],
        // 领取渠道
        'type' => [
            1 => '商家券',
            2 => '平台券'
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
        "uid" => "用户序号",
        "coupon_id" => "优惠券序号",
        "shop_id" => "商家序号",
        'type' => '优惠券类型',
        'channel' => '领取渠道',
        'use_status' => '使用类型',
        "status" => "状态",
        "total_amount" => "满减抵扣",
        "amount" => "抵扣金额",
        "limit" => "数据条数",
        "start" => "开始时间开始",
        "end" => "结束时间结束",
    ];

    // viewData
    public $viewData = [
        "nav_title" => [
            "店铺管理",
            "优惠券领取列表"
        ],
        "serch_form" => [
            [
                "key" => "uid",
                "type" => "number",
            ],
            [
                "key" => "shop_id",
                "type" => "number",
            ],
            [
                "key" => "coupon_id",
                "type" => "number",
            ],
            [
                "key" => "status",
                "type" => "select",
            ],
            [
                "key" => "use_status",
                "type" => "select",
            ],
            [
                "key" => "type",
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
                "title" => "用户信息"
            ],
            [
                "title" => "优惠券"
            ],
            [
                "title" => "优惠券类型"
            ],
            [
                "title" => "使用条件"
            ],
            [
                "title" => "满减抵扣"
            ],
            [
                "title" => "抵扣金额"
            ],
            [
                "title" => "状态"
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
        if(!empty($get['uid']))$where['uid'] = $get['uid'];
        if(!empty($get['shop_id']))$where['shop_id'] = $get['shop_id'];
        if(!empty($get['coupon_id']))$where['coupon_id'] = $get['coupon_id'];
        if(!empty($get['status']))$where['status'] = $get['status'];
        if(!empty($get['use_status']))$where['use_status'] = $get['use_status'];
        if(!empty($get['type']))$where['type'] = $get['type'];
        $list = $this->where($where)->order('add_time desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    // 获取到优惠券信息
    public function getShopCouponOne(){
        return $this::hasOne(ShopCoupon::class,'id','coupon_id');
    }

    // 获取到用户信息
    public function getUserOne(){
        return $this::hasOne(User::class,'id','uid');
    }

    // 获取到商家信息
    public function getShopUsersOne(){
        return $this::hasOne(ShopUsers::class,'id','shop_id');
    }
}