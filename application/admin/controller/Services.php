<?php
namespace app\admin\controller;
use app\common\model\Logistics;
use app\common\model\Service;
use app\common\model\ServiceGoods;

// 引入运费设置模型
use app\common\model\services\Freight as FreightModel;

Class Services extends Common{
    //商品列表
    public function index()
    {
        $model = new Service();
        $get = input('get.');
        if(empty($get['cid']))
            $get['cid'] = '';
        if(empty($get['bid']))
            $get['bid'] = '';
        if(empty($get['status'])) $get['status'] = '';
        if(empty($get['limit'])) $get['limit'] = '';
        if($get['limit'] > 200)$get['limit'] = 200;

        //商品状态，状态0-申请，1-(已审核)等待买家寄货，2-(买家已寄货)等待卖家(收或者发,退款表示收，换货表示发)货，3-(商家已收货)-，4-退款中或商家发货中,5-完成（退款ok），6-已拒绝'
        $status = [
            0   =>  '申请',
            1   =>  '已审核',
            2   =>  '等待买家-发货', // 买家已寄货
            3   =>  '买家已发货', // 商家已收货
            4   =>  '商家-处理中',
            5   =>  '完成',
            6   => '拒绝'
        ];
        $color = [
            0   => '#000',
            1   =>  '#f90',
            2   =>  'blue',
            3   =>  'blue',
            4   =>  'green',
            5   =>  'blue',
            6   => 'red'
        ];
        $types = [
            1 => '换货',
            2 => '退货退款'
        ];
        $mod = new Logistics();
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
            'color'     =>  $color,
            'type'      => $types,
            'express'=>$mod->getLogistics()
        ]);
    }

    //审核售后订单
    public function examine(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(60);
            $post = input('post.');
            $model = new Service();
            $model->toExamine($post);
        }else{
            return $this->fetch('',['id'=>input('get.id')]);
        }
    }

    // 发货处理
    public function merchantShipment(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(60);
            $post = input('post.');
            $model = new Service();
            $model->toMerchantShipment($post);
        }else{
            $get = input('get.');
            $model = new Service();
            $mod = new Logistics();
            $data = [];
            $data['id'] = $get['id'];
            if(isset($get['id']) && $get['id'] > 0){
                $one = $model->where('id',$get['id'])->find();
                if($one->merchant_express_id > 0 && $one->getOneMerchantExpress){
                    $data = $one->getOneMerchantExpress;
                    $data['express_id'] = $one->getOneMerchantExpress['id'];
                }
            }
            return $this->fetch('',['data'=>$data,'express'=>$mod->getLogistics()]);
        }
    }

    //退款
    public function refund(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(59);
            $post = input('post.');
            $model = new Service();
            $model->toRefund($post);
        }else{
            $model = new Service();
            return $this->fetch('',['data'=>$model->getOne(input('get.id'))]);
        }
    }

    //换货物流信息
    public function express(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(58);
            $post = input('post.');
            $model = new Service();
            $model->toExpress($post);
        }else{
            $model = new Service();
            $mod = new Logistics();
            return $this->fetch('',['data'=>$model->getOne(input('get.id')),'express'=>$mod->getLogistics()]);
        }
    }

    // 退款-确认收货
    public function receipt(){
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            $model = new Service();
            $model->toReceipt($post);
        }
    }

    public function info(){
        $model = new Service();
        $mod = new Logistics();
        //商品状态，状态0-申请，1-(已审核)等待买家寄货，2-(买家已寄货)等待卖家(收或者发,退款表示收，换货表示发)货，3-(商家已收货)-，4-退款中或商家发货中,5-完成（退款ok），6-已拒绝'
        $status = [
            0   =>  '申请',
            1   =>  '已审核',
            2   =>  '买家已寄货',
            3   =>  '商家已收货',
            4   =>  '处理中',
            5   =>  '完成',
            6   => '拒绝'
        ];
        return $this->fetch('',['data'=>$model->getOne(input('get.id')),'express'=>$mod->getLogistics(),'status'    =>  $status,]);
    }

    /**
     * ---------------------------------------
     * ******   运费设置开始开始  *********
     * freight
     * ---------------------------------------
     */
    // 列表
    public function freight()
    {
        $model = new FreightModel();
        return $this->fetch('/services/freight/index',[
            'list' => $model->getList(),
            'count' => $model->count,
            'configTyepStr' => $model->configTyepStr
        ]);
    }

    // 编辑
    public function freightEdit()
    {
        $this->checkAuth(97);
        $model = new FreightModel();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->addLogistics($post);
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id))
                $data = $model->where('id',$id)->find()->toArray();
            return $this->fetch('/services/freight/edit',['data'=>$data]);
        }
    }

    // 删除
    public function freightDel()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(97);
            $model = new FreightModel();
            $modelFlag = $model->where('id',$post['id'][0])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    /**
     * ---------------------------------------
     * ******   运费设置结束  *********
     * freight
     * ---------------------------------------
     */

}