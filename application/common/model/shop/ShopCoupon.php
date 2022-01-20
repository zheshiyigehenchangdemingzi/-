<?php


namespace app\common\model\shop;


use app\common\model\shop\ShopUsers;
use app\common\model\Base;

// 商家优惠券列表
class ShopCoupon extends Base
{
    public function getNameAttr($value){
        return emojiDecode($value);
    }

    // 选择框大全
    public $select_list = [
        // 类型
        'status' => [
            1 => "全店铺使用",
            2 => "指定商品使用"
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
        "name" => "优惠券名",
        "code" => "优惠识别码",
        "day" => "有效天数",
        "amount" => "抵扣金额",
        "total" => "发放数量",
        "status" => "类型",
        "limit" => "数据条数",
        "start" => "优惠券开始时间",
        "end" => "优惠券结束时间",
    ];

    // viewData
    public $viewData = [
        "nav_title" => [
            "店铺管理",
            "店铺优惠券列表"
        ],
        "serch_form" => [
            [
                "key" => "shop_id",
                "type" => "number",
            ],
            [
                "key" => "name",
                "type" => "text",
            ],
            [
                "key" => "code",
                "type" => "text",
            ],
            [
                "key" => "amount",
                "type" => "number",
            ],
            [
                "key" => "total",
                "type" => "number",
            ],
            [
                "key" => "day",
                "type" => "number",
            ],
            [
                "key" => "status",
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
                "title" => "商家序号"
            ],
            [
                "title" => "优惠券名"
            ],
            [
                "title" => "类型"
            ],
            [
                "title" => "使用条件"
            ],
            [
                "title" => "抵扣金额"
            ],
            [
                "title" => "数量"
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
        if(isset($get['shop_id']) && strlen($get['shop_id']) >= 1)$where['shop_id'] = $get['shop_id'];
        if(isset($get['total']) && strlen($get['total']) >= 1)$where['total'] = $get['total'];
        if(isset($get['amount']) && strlen($get['amount']) >= 1)$where['amount'] = $get['amount'];
        if(isset($get['day']) && strlen($get['day']) >= 1)$where['day'] = $get['day'];
        if(isset($get['status']) && strlen($get['status']) >= 1)$where['status'] = $get['status'];
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            $where['start_time'] = ['>',$start];
        }
        if(!empty($get['end'])){
            $end = strtotime($get['end']."+1 day");
            $where['end_time'] = ['<=',$end];
        }
        $list = $this->where($where)->order('add_time desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    // 拿取单个
    public function getOne($id){
        if($data = $this::find($id)){
            $data['name'] = emojiDecode($data['name']);
            $data['start_time'] = date('Y-m-d H:i:s',$data['start_time']).' - '.date('Y-m-d H:i:s',$data['end_time']);
        }
        return $data;
    }

    // 编辑操作
    public function edit($post){
        if(!isset($post['shop_id']) || $post['shop_id'] <= 0) return return_ajax(400,'店铺序号不得为空');
        if(!isset($post['name']) || strlen($post['name']) <= 2) return return_ajax(400,'优惠券不得为空，且长度大于2');
        if(!isset($post['amount']) || $post['amount'] <= 0) return return_ajax(400,'抵扣金额需要大于0');
        if(!isset($post['total']) || $post['total'] <= 0) return return_ajax(400,'发放数量需要大于0');
        if(!isset($post['start_time']) || $post['start_time'] <= 0) return return_ajax(400,'有效时间必须填写');
        $post['times'] = json_decode($post['times'],true);
        $post['start_time'] = $post['times']['start'];
        $post['end_time'] = $post['times']['end'];
        $post['day'] = $post['times']['day'];
        $post['name'] = emojiEncode($post['name']);
        $time = time();
        $title = '添加成功';
        $flag = null;
        // 如果是编辑
        if(isset($post['id']) && $post['id'] > 0){
            $title = '编辑成功';
            $post['upd_time'] = $time;
            $flag = $this->allowField(true)->isUpdate(true)->save($post,['id'=>$post['id']]);
        }else{
            $post['add_time'] = $time;
            $post['code'] = md5(uniqid(microtime(true),true));
            if($this::where(['shop_id'=>$post['shop_id'],'name'=>$post['name']])->count() > 0)return return_ajax(400,'当前已存在相同的名称，麻烦更换一个');
            $flag = $this::allowField(true)->save($post);
        }
        if(!$flag) return return_ajax(400,'网络异常，麻烦重新尝试');

        return_ajax(200,$title);
    }

    // 获取到店铺
    public function getShopOne(){
        return $this::hasOne(ShopUsers::class,'id','shop_id');
    }
}