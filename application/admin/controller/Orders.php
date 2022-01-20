<?php
namespace app\admin\controller;
use app\common\model\Express;
use app\common\model\Logistics;
use app\common\model\Order;
use app\common\model\UserShareOrder;
use think\Log;

Class Orders extends Common{
    //订单列表
    public function index()
    {
        $model = new Order();
        $get = input('get.');
        if(empty($get['status']))
            $get['status'] = '';
        if(!isset($get['type'])) $get['type'] = '';
        if(!isset($get['shop_type'])) $get['shop_type'] = '';
        if(!isset($get['limit']) || !$get['limit']) $get['limit'] = 20;
        if($get['limit'] > 200)$get['limit'] = 200;

        //订单状态
        $status = [
            0 => '已取消',
            1   =>  '待付款',
            2   =>  '待发货',
            3   =>  '待收货',
            4   =>  '已收货',
            5   =>  '已完成',
            6   =>  '待处理',
            7   =>  '已取消',
            8   =>  '申请退款',
            9   =>  '已退款'
        ];
        $deliver = [
            1   =>  '快递配送',
            2   =>  '到店自提',
            3   =>  '同城配送'
        ];
        $color = [
            0 => '#000',
            1   =>  'red',
            2   =>  '#f90',
            3   =>  '#f90',
            4   =>  'blue',
            5   =>  'green',
            6   =>  'yellow',
            7   =>  '#ccc',
            8   =>  '#f90',
            9   =>  'red'
        ];

        $types = [
            1 => '常规订单',
            2 => '活动订单',
            3 => '直播订单'
        ];

        // 物流公司名称
        $logistics = Logistics::column('id,company');
//        return $this->fetch('',[
//            'list'      =>  $model->getList($get),
        $list = $model->getList($get);
        if($get['status'] != '') $get['status'] = $get['status'] - 1;
        return $this->fetch('',[
            'list'      =>  $list,
            'page'      =>  $model->page,
            'count'     =>  $model->count,
            'get'       =>  $get,
            'status'    =>  $status,
            'deliver'   =>  $deliver,
            'color'     =>  $color,
            'types'     => $types,
            'logistics' => $logistics
        ]);
    }

    //订单发货
    public function deliver()
    {
        $model = new Express();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(45);
            $post = input('post.');
            $model->addExpress($post);
        }else{
            $id = input('get.id');
            $data = $model->getExpress($id);
            $mod = new Logistics();
            if(isset($data['desc']) && $data['desc'] == '仓库已揽件') $data['desc'] = '已发货';
            return $this->fetch('',['id'=>$id,'data'=>$data,'express'=>$mod->getLogistics()]);
        }
    }

    //申请退款处理
    public function refund()
    {
        $model = new Order();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(49);
            $post = input('post.');
            $model->orderRefund($post);
        }else{
            return $this->fetch('',['id'=>input('get.id')]);
        }
    }

    //确认收货
    public function receipt(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(46);
            $model = new Order();
            $model->confirmReceipt(input('post.id'));
        }
    }

    //完成订单
    public function finish(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(47);
            $model = new Order();
            $model->finishOrder(input('post.id'));
        }
    }

    public function info(){
        $id = input('get.id');
        $model = new Order();
        $data = $model->getOne($id);
        // 物流公司名称
        $logistics = Logistics::column('id,company');
        return $this->fetch('',['data'=>$data,'logistics' => $logistics]);
    }

    /**
     * ----分享订单开始----
     */
    public function share()
    {
        $list = [];
        $get = Input('get.');
        // 状态
        if(!isset($get['status'])) $get['status'] = '';
        // uid
        if(!isset($get['uid'])) $get['uid'] = '';
        // puid
        if(!isset($get['puid'])) $get['puid'] = '';
        // 数量
        if(!isset($get['limit'])) $get['limit'] = 20;
        // 数量限制最大
        if($get['limit'] > 100) $get['limit'] = 100;
        // 类型
        if(!isset($get['type'])) $get['type'] = '';
        // 状态信息
        $status = [
            '已取消',
            '待付款',
            '待发货',
            '待收货',
            '已收货',
            '已完成'
        ];
        $type = [
            1 => '视频分享',
            2 => '商品分享'
        ];
        $model = new  UserShareOrder();
        return $this->fetch('',[
            'list' => $model->list($get),
            'get' => $get,
            'status' => $status,
            'count' => $model->count,
            'page' =>  $model->page,
            'type' => $type
        ]);
    }
}