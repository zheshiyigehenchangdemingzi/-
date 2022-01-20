<?php

namespace app\admin\controller;

use app\api\model\User;
use app\common\model\Config;
use app\common\model\Logistics;
use app\common\model\Receipt;
use app\common\model\RuleDescription;
use app\common\model\setting\ViewText;
use app\common\model\UserLevel;

// 首页轮播图-广告图
use app\common\model\setting\BannerHome as BannerHomeModel;

// 直播栏目轮播图-广告图
use app\common\model\setting\BannerLive as BannerLiveModel;

class Setting extends Common
{
    //微信设置
    public function index()
    {
        $model = new Config();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(14);
            $post = input('post.');
            if (!$this->check_input($post))
                return_ajax(400, '输入内容含有非法字符');
            $model->toSave($post);
        } else {
            return $this->fetch('', ['config' => $model->toData('wechat')]);
        }
    }

    //注册协议
    public function reg()
    {
        $model = new Config();
        if (request()->isPost() && request()->isAjax()) {
            //$this->checkAuth(57);
            $post = input('post.');
            $model->toReg($post);
        } else {
            $config = $model->toData('reg');
            if (empty($config)) {
                $config['content'] = '';
            } else {
                $config['content'] = htmlspecialchars_decode($config['content']);
            }
            return $this->fetch('', ['config' => $config]);
        }
    }

    //短信设置
    public function sms()
    {
        $model = new Config();
        if (request()->isPost() && request()->isAjax()) {
            //$this->checkAuth(57);
            $post = input('post.');
            $model->toSave($post);
        } else {
            return $this->fetch('', ['config' => $model->toData('sms')]);
        }
    }

    //交易设置
    public function business()
    {
        $model = new Config();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(35);
            $post = input('post.');
            $model->toSave($post);
        } else {
            $config = $model->toData('business');
            $payArr = [
                '1' => ['name' => '线上支付', 'checked' => 0],
                '2' => ['name' => '货到付款', 'checked' => 0],
                '3' => ['name' => '余额支付', 'checked' => 0],
            ];
            $expressArr = [
                '1' => ['name' => '快递配送', 'checked' => 0],
                '2' => ['name' => '到店自提', 'checked' => 0],
                '3' => ['name' => '同城配送', 'checked' => 0],
            ];
            if ($config) {
                $pay = $config['pay'];
                $express = $config['express'];
                foreach ($pay as $v) {
                    $payArr[$v]['checked'] = 1;
                }
                foreach ($express as $v) {
                    $expressArr[$v]['checked'] = 1;
                }
            }
            return $this->fetch('', ['config' => $config, 'pay' => $payArr, 'express' => $expressArr]);
        }
    }

    //物流设置
    public function express()
    {
        $model = new Logistics();
        $get = input('get.');
        return $this->fetch('', [
            'list' => $model->getList($get),
            'count' => $model->count,
            'page' => $model->page,
            'get' => $get
        ]);
    }

    //物流编辑
    public function express_edit()
    {
        $model = new Logistics();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(51);
            $post = input('post.');
            if (!$this->check_input($post))
                return_ajax(400, '输入内容含有非法字符');
            $model->addLogistics($post);
        } else {
            $id = input('get.id');
            $data = [];
            if (!empty($id))
                $data = $model->getOne($id);

            return $this->fetch('', ['data' => $data]);
        }
    }

    //物流删除
    public function express_del()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(52);
            $post = input('post.');
            $model = new Logistics();
            $model->logisticsDel($post['id']);
        }
    }

    //退货地址
    public function receipt()
    {
        $model = new Receipt();
        $get = input('get.');
        return $this->fetch('', [
            'list' => $model->getList($get),
            'count' => $model->count,
            'page' => $model->page,
            'get' => $get
        ]);
    }

    //退货地址编辑
    public function receipt_edit()
    {
        $model = new Receipt();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(54);
            $post = input('post.');
            if (!$this->check_input($post))
                return_ajax(400, '输入内容含有非法字符');
            $model->addReceipt($post);
        } else {
            $id = input('get.id');
            $data = [];
            if (!empty($id))
                $data = $model->getOne($id);

            return $this->fetch('', ['data' => $data]);
        }
    }

    //退货地址删除
    public function receipt_del()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(55);
            $post = input('post.');
            $model = new Receipt();
            $model->receiptDel($post['id']);
        }
    }

    //等级规则
    public function level_role()
    {
        $model = new Config();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(75);
            $post = input('post.');
            $model->toRole($post);
        } else {
            $config = $model->toData('level_role');
            if (empty($config)) {
                $config['content'] = '';
            } else {
                $config['content'] = htmlspecialchars_decode($config['content']);
            }
            return $this->fetch('', ['config' => $config]);
        }
    }

    //收益描述
    public function income_description()
    {
        $model = new RuleDescription();
        $list = $model->select();
        return $this->fetch('', ['list' => $list]);
    }

    // 扩展字段- 单个点击后更改
    public function income_description_edit()
    {
        $model = new RuleDescription();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(75);
//            $post = input('post.');
//            $model->toRole($post);
            $model->edit();
        } else {
            $config = $model->getValue();
            return $this->fetch('', ['config' => $config]);
        }
    }


    /*-----   --- 首页轮播图 - 广告图  start  --------------*/
    /*
    //首页轮播图 - 广告图-列表
    public function bannerHome()
    {
        $model = new BannerHomeModel();
        $get = Input('get.');
        if(!isset($get['show'])) $get['show'] = '';
        return $this->fetch('/setting/'.__FUNCTION__.'/index',[
            'list'  =>  $model->getList($get),
            'count' => $model->select()->count(),
            'get' => $get
        ]);
    }

    //轮播图编辑
    public function bannerHome_edit(){
        $this->checkAuth(93);
        $model = new BannerHomeModel();
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
            return $this->fetch('/setting/bannerHome/edit',['data'=>$data]);
        }
    }

    //首页轮播图 - 广告图-删除
    public function bannerHomeDel()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(93);
            $model = new BannerHomeModel();
            $modelFlag = $model->where('id',$post['id'][0])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    // 首页轮播图-  广告图  --  状态改变
    public function bannerHome__status(){
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','show']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(93);
            $modelFlag = BannerHomeModel::where('id',$post['id'])->update([
                $post['key'] =>  $post['val'] - 0
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态 - 广告图 -- 状态改变
    public function bannerHome_pinliang_status(){
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0) return_ajax(400,'请选择对应列表');
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],['status','show']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(93);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = BannerHomeModel::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 排序改变-  广告图  -  排序值
    public function bannerHome_sortEdit(){
        $get = input('get.');
        if(!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400,'错误记录值');
        if(!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400,'修改值错误');
        BannerHomeModel::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }
    */
    /*-----   --- 首页轮播图 - 广告图  end --------------*/


    /*-----   --- 直播栏目轮播图 - 广告图  start --------------*/
    /*
    //列表
    public function bannerLive()
    {
        $model = new BannerLiveModel();
        return $this->fetch('/setting/'.__FUNCTION__.'/index',[
            'list'  =>  $model->getList(),
            'count' => $model->select()->count()
        ]);
    }

    //编辑
    public function bannerLive_edit(){
        $this->checkAuth(94);
        $model = new BannerLiveModel();
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
            return $this->fetch('/setting/bannerLive/edit',['data'=>$data]);
        }
    }

    //删除
    public function bannerLiveDel()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            $model = new BannerLiveModel();
            $modelFlag = $model->where('id',$post['id'][0])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }
    */
    /*-----   --- 直播栏目轮播图 - 广告图  end --------------*/


    /**
     * *********************************
     * ------------------------------ 视图-首页记录开始 start  ------------------------------
     * *********************************
     */
    //列表
    public function view_text()
    {
        $model = new ViewText();
        $userModel = new User();
        return $this->fetch('/setting/viewText/index', [
            'list' => $model->getList(),
            'count' => $model->select()->count()
        ]);
    }

    //编辑
    public function view_text_edit()
    {
        $this->checkAuth(106);
        $model = new ViewText();
        $userModel = new User();
        $type = $userModel->where(['is_fictitious' => 1])->group('nickname')->field('id,nickname')->select()->toArray();
        if (request()->isPost() && request()->isAjax()) {
            $post = input('post.');
            if (!$this->check_input($post))
                return_ajax(400, '输入内容含有非法字符');
            $model->addLogistics($post);
        } else {
            $id = input('get.id');
            $data = [];
            if (!empty($id))
                $data = $model->where('id', $id)->find()->toArray();
            return $this->fetch('/setting/viewText/edit', ['data' => $data, 'type' => $type]);
        }
    }

    //删除
    public function viewTextDel()
    {
        $post = input('post.');
        if (!isset($post['id'])) {
            return_ajax(400, '请选择对应列表');
        }
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(106);
            $model = new ViewText();
            $modelFlag = $model->where('id', $post['id'][0])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400, $modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    /**
     * *********************************
     * ------------------------------ 视图-首页记录结束 end ------------------------------
     * *********************************
     */

    /**
     * ***********************************
     * 首页配置-  配置图文信息
     * ***********************************
     */
    /*
    public function viewHomeConfig()
    {
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            $data = [
                'switch' => $post['switch'],
                'img' => $post['img']
            ];
            Config::where('type','viewHome')->update([
                'content' => json_encode($data)
            ]);
            return return_ajax(200,'修改ok');
        }else{
            $model = Config::where('type','viewHome')->find();
            $data = json_decode($model->content,true);
            return $this->fetch('',['data'=>$data]);
        }
    }
    */
}   