<?php
namespace app\admin\controller;


use app\common\model\Config;
use app\common\model\Goods;
use app\common\model\GoodsBrand;
use app\common\model\GoodsType;
use app\common\model\shop\ShopAccount;
use app\common\model\shop\ShopCoupon;
use app\common\model\shop\ShopOrderLog;
use app\common\model\shop\ShopRecharge;
use app\common\model\shop\ShopUserInfo;
use app\common\model\shop\ShopUsers;
use app\common\model\shop\ShopWallet;
use app\common\model\shop\ShopWalletLog;
use app\common\model\shop\ShopWithdraw;
use app\common\model\users\UserCoupon;

Class Shop extends Common{

    /* -------- 店铺开始了   --------  */
    // 店铺列表
    public function index()
    {
        $get = input('get.');
        if(!isset($get['limit'])) $get['limit'] = 20;
        if(!isset($get['apply_status'])) $get['apply_status'] = '';
        if(!isset($get['is_open'])) $get['is_open'] = '';
        $model = new ShopUsers();

        $apply_status_arrs = [
            '申请中',
            '审核通过',
            '审核拒绝'
        ];
        return $this->fetch('/shop/list/index',[
            'list'      =>  $model->getList($get),
            'page'      =>  $model->page,
            'count'     =>  $model->count,
            'get'       =>  $get,
            'apply_status_arrs' => $apply_status_arrs
        ]);
    }

    // 店铺编辑
    public function list_edit()
    {
        $model = new ShopUsers();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(156);
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->edit($post);
        }else{
            $id = input('get.id');
            $data = [];
            if($id && $id > 0){
                $data = $model->getShop($id);
                $data['rule'] = htmlspecialchars_decode($data['rule']);
                $shopUserInfoModel = new ShopUserInfo();
                $data['card_img'] = $shopUserInfoModel->getCardImgs($data['card_img']);
                $data['certificate_photo'] = prefixUrlImg($data['certificate_photo']);
            }
            return $this->fetch('/shop/list/edit',['data'=>$data]);
        }
    }

    // 店铺审核
    public function check(){
        $model = new ShopUsers();
        if(request()->isPost() && request()->isAjax()){
            $post = Input('post.');
            if(!isset($post['id']) || $post['id'] <= 0) return return_ajax(400,'序号错误');
            // 创建账号并且发送信息
            $model->createShopAdmin($post);
        }else{
            return return_ajax(400,'错误');
        }
    }

    // 店铺删除
    public function list_del()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(140);
            $model = new ShopUsers();
            $modelFlag = $model->where('id',$post['id'])->update([
                'is_delete' => 1
            ]);
            ShopAccount::where('shop_id',$post['id'])->update([
                'is_delete' => 1
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    // 店铺 --  状态改变
    public function list__status(){
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        $status = ['is_open'];
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],$status))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(156);
            $modelFlag = ShopUsers::where('id',$post['id'])->update([
                $post['key'] =>  $post['val'] - 0
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 店铺改变状态 - 批量 -- 状态改变
    public function list_pinliang_status(){
        $post = input('post.');
        $status = ['is_open'];
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0) return_ajax(400,'请选择对应列表');
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],$status))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(156);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = ShopUsers::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态 - 店铺 -- 批量删除
    public function list_delAll(){
        $post = input('post.');
        if(!isset($post['id']) || !is_array($post['id']) || count($post['id']) == 0) return_ajax(400,'请选择对应列表');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(140);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = ShopUsers::whereIn('id',$post['id'])->update([
                'is_delete' =>  1
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }
    /* -------- 店铺结束了   --------  */


    // 店铺充值记录
    public function recharge_list()
    {
        $model = new ShopRecharge();
        $get = input('get.');
        $data = $model->getList($get);
        if(!isset($get['type'])) $get['type'] = '';
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['limit'])) $get['limit'] = '';
        return $this->fetch('/shop/recharge/index',[
            'list'  =>  $data['data'],
            'sum'  =>  $data['sum'],
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    // 店铺充值编辑
    public function recharge_edit()
    {
        $model = new ShopRecharge();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(64);
            $post = input('post.');
            $model->rechargeEdit($post);
        }else{
            return $this->fetch('/volumes/edit/edit',['data'=>$model->getOne(input('get.id'))]);
        }
    }

    // 店铺提现记录
    public function withdraw_list()
    {
        $model = new ShopWithdraw();
        $get = input('get.');
        $data = $model->getList($get);
        if(!isset($get['type'])) $get['type'] = '';
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['limit'])) $get['limit'] = '';
        return $this->fetch('/shop/withdraw/index',[
            'list'  =>  $data['data'],
            'sum'  =>  $data['sum'],
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    // 店铺提现审核
    public function withdraw_edit()
    {
        $model = new ShopWithdraw();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(64);
            $post = input('post.');
            $model->withdrawEdit($post);
        }else{
            return $this->fetch('/volumes/edit/edit',['data'=>$model->getOne(input('get.id'))]);
        }
    }

    /*
     * -----   商家优惠券开始  -----
     * */
//优惠券 -     列表
    public function coupon(){
        $get = Input('get.');
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['limit'])) $get['limit'] = '';
        $model = new ShopCoupon();
        return $this->fetch('/shop/coupon/index',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'viewData' => $model->getViewData()
        ]);
    }

    // 优惠券  - 编辑
    public function coupon_edit()
    {
        $model = new ShopCoupon();
        $post = input('post.');
        $get = input('get.');
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(145);
            $model->edit($post);
        }else{
            $data = [];
            if(isset($get['id']) && $get['id'] > 0)$data = $model->getOne($get['id']);
            return $this->fetch('/shop/coupon/edit',['data'=>$data]);
        }
    }

    /*
     * -----   商家优惠券结束  -----
     * */

    /**
     * 优惠券 领取列表
     */
    public function user_coupon(){
        $get = Input('get.');
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['limit'])) $get['limit'] = '';
        $model = new UserCoupon();
        return $this->fetch('/shop/user_coupon/index',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'viewData' => $model->getViewData()
        ]);
    }


    /**
     *  ---- 商家 - 商品  ---- 结束  ----
     */

    /**
     * 商家钱包  -  编辑 - 查看 数据
     */
    public function wallet_edit(){
        $model = new ShopWallet();
        if(request()->isAjax() && request()->isPost()){
            $post = input('post.');
            if (!isset($post['id'])) return return_ajax(400, '没有该记录');
            if (!$post['id'] || $post['id'] <= 0) return return_ajax(400, '记录为空');
            $this->checkAuth(151);
            $model->edit($post,$this->AdminInfo->id);
        }else {
            $get = Input('get.');
            if (!isset($get['id'])) return return_ajax(400, '没有该记录');
            if (!$get['id'] || $get['id'] <= 0) return return_ajax(400, '记录为空');
            $data = $model->getOne($get['id']);
            return $this->fetch('/shop/list/wallet', [
                'data' => $data
            ]);
        }
    }

    /**
     * 钱包操作记录
     */
    public function walletlog(){
        $get = Input('get.');
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['limit'])) $get['limit'] = '';
        $model = new ShopWalletLog();
        return $this->fetch('/shop/shopWalletLog/index',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'viewData' => $model->getViewData()
        ]);
    }

    /**
     * 店铺订单列表记录
     */
    public function shopOrderLog()
    {
        $get = Input('get.');
        if(!isset($get['status'])) $get['status'] = '';
        if(!isset($get['limit'])) $get['limit'] = '';
        $model = new ShopOrderLog();
        return $this->fetch('/shop/ShopOrderLog/index',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'viewData' => $model->getViewData()
        ]);
    }

    // 店铺配置信息
    public function config(){
        $model = new Config();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(154);
            $post = input('post.');
            if(!$this->check_input($post))
                return return_ajax(400,'输入内容含有非法字符');
            foreach($model->shopOrderConfigView as $item){
                if(!isset($post[$item['key']]) || $post[$item['key']] <= 0) return return_ajax(400,'数量必须输入,且必须大于0');
            }
            $post['platform_rate'] = $post['platform_rate'] /100;
            if($post['platform_rate'] > 0.5) $post['platform_rate'] = 0.5;
            $model->toSave($post);
        }else{
            $config = [];
            $config = $model->toData('shopOrderConfig');
            if(isset($config['platform_rate']))$config['platform_rate'] = $config['platform_rate'] * 100;
            return $this->fetch('/shop/config/index',['config'=>$config,'viewData'=>$model->shopOrderConfigView]);
        }
    }

    // 店铺关闭
    public function close()
    {
        $post = Input('post.');
        if(!isset($post['id']) || $post['id'] <= 0)return return_ajax(400,'记录为空');
        $model = new ShopUsers();
        $model->toClose($post);
    }
}