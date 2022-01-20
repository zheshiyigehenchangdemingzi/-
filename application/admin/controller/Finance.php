<?php
namespace app\admin\controller;

use app\common\model\Config;
use app\common\model\MiaoLog;
use app\common\model\UserCash;

Class Finance extends Common{

    // 基础设置
    public function edit_setting()
    {
        $model = new Config();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(92);
            $post = input('post.');
            $model->toSave($post);
        }else{
            $config = $model->toData('finance');
            return $this->fetch('',['config'=>$config]);
        }
    }

    // 充值记录
    public function rechange_log()
    {
        return '<h1>敬请期待2.0版本开启</h1>';
    }

    //  积分管理
    public function pointsManagement()
    {
        return '<h1>敬请期待2.0版本开启</h1>';
    }

    /*
     * 用户提现记录
     */
    public function  withdrawList(){
        $model = new UserCash();
        $get = input('get.');
        $statusMap = [
            1  =>  '等待审核',
            2  =>  '审核通过',
            3  =>  '提现成功',
            4  =>  '提现失败',
            5  =>  '审核拒绝'
        ];
        $gets = $get;
        if(empty($gets['status']))
            $gets['status'] = '0';
        return $this->fetch('/users/withdraw_list',[
            'list'  =>  $model->getBalanceList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $gets,
            'statusMap' => $statusMap,
        ]);
    }

    /*
     * 提现审核
     */
    public function  withdrawExamine(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(16);
            $model = new UserCash();
            $model->withdrawExamine(input('post.'));
        }
    }

    /*
     * 喵呗记录
     */
    public function  miao_log(){
        $model = new MiaoLog();
        $get = input('get.');
        $statusMap = [
            1  =>  '视频内容收益',
            2  =>  '普通拼团奖励',
            4  =>  '短视频/直播/分享奖励',
            3  =>  '团队奖励',
        ];
        $gets = $get;
        $data = $model->getList($get);
        return $this->fetch('/finance/miao_list',[
            'list'  =>  $data['data'],
            'sum'  =>  $data['sum'],
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $gets,
            'statusMap' => $statusMap,
        ]);
    }

}