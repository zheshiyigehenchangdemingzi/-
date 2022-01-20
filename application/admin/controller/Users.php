<?php
namespace app\admin\controller;
use app\common\model\BalanceLog;
use app\common\model\MiaoLog;
use app\common\model\Order;
use app\common\model\User;
use app\common\model\UserAddress;
use app\common\model\UserBank;
use app\common\model\UserInfo;
use app\common\model\UserLevel;
use app\common\model\UserVolume;
use app\common\model\UserVolumeLog;
use app\common\model\UserWallet;
use app\common\model\UserCash;
use app\common\model\UserIntegralLog;
use app\common\model\UserViewLevel;
use app\common\model\ViewPoster;

Class Users extends Common{
    //列表
    public function index()
    {
        $model = new User();
        $mod = new UserLevel();
        $get = input('get.');

        // is_fictitious
        if(!isset($get['is_fictitious']))
            $get['is_fictitious'] = '';
        if(!isset($get['limit']))
            $get['limit'] = 20;
        if($get['limit'] > 200)
            $get['limit'] = 200;
        if(!isset($get['is_userType'])){
            $get['is_userType'] = '';
        }
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'level' =>  $mod->getLevel(),
            'today' => $model->today,
            'zongDay' => $model->zongDay
        ]);
    }

    //信息详情
    public function info(){
        $model = new User();
        $mod = new UserLevel();
        return $this->fetch('',[
            'data'  =>  $model->getInfo(input('get.id')),
            'level' =>  $mod->getLevel()
        ]);
    }

    //余额详情
    public function balance(){
        $model = new BalanceLog();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //银行卡详情
    public function bank(){
        $model = new UserBank();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //收货地址详情
    public function address(){
        $model = new UserAddress();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //购物券
    public function volume(){
        $model = new UserVolume();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //购物券记录
    public function volume_log(){
        $model = new UserVolumeLog();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //推荐下级
    public function invite(){
        $model = new User();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getinvList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    //收入详情 hc修改
    public function miao(){
        $model = new MiaoLog();
        $mod = new UserWallet();
        $get = input('get.');
        $typeMap = [
            0   =>  '兑换现金',
            1   =>  '拼团活动',
            2   =>  '团队奖励',
            3   =>  '分享购物',
            4   =>  '购物券',
        ];
        $statusMap = [
            1   =>  '预估',
            2   =>  '待结算',
            3   =>  '已入账',
            4   =>  '已撤销',
            11  =>  '等待审核',
            12  =>  '审核通过',
            13  =>  '兑换成功',
            14  =>  '兑换失败',
            15  =>  '审核拒绝'
        ];
        $gets = $get;
        if(empty($gets['type']))
            $gets['type'] = '10000';
        return $this->fetch('',[
            'list'  =>  $model->getListAndCash($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $gets,
            'wallet'=>  $mod->getWallet($get),
            'typeMap' => $typeMap,
            'statusMap' => $statusMap
        ]);
    }

    //订单详情
    public function order(){
        $model = new Order();
        $get = input('get.');
        //订单状态
        $status = [
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
        $color = [
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
        return $this->fetch('',[
            'list'      =>  $model->getList1($get),
            'status'    =>  $status,
            'color'     =>  $color,
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
            'wallet'=>  [
                'totalAmount' => $model->getOrderTotal($get['id']),
                'totalNumber' =>$model->getOrderNum($get['id'])
            ],
        ]);
    }

    //解除推荐关系
    public function invite_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(81);
            $model = new User();
            $model->inviteDel(input('post.id'));
        }
    }

    //更改推荐人
    public function invite_edit(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(82);
            $model = new User();
            $post = input('post.');
            $model->editInvite($post);
        }
    }

    //更改昵称
    public function nickname_edit(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(82);
            $model = new User();
            $post = input('post.');
            $model->editNickname($post);
        }
    }

    //增加购物券额度
    public function volume_edit(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(83);
            $model = new UserVolume();
            $post = input('post.');
            $model->editVolume($post);
        }
    }

    //增加积分额度
    public function int_edit(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(84);
            $model = new UserWallet();
            $post = input('post.');
            $model->editInt($post);
        }
    }

    //添加用户
    public function add(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(85);
            $model = new User();
            $post = input('post.');
            $model->addUser($post,$_FILES);
        }else{
            $model = new UserLevel();
            return $this->fetch('',['level'=>$model->getLevel()]);
        }
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
        return $this->fetch('',[
            'list'  =>  $model->getBalanceList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $gets,
            'statusMap' => $statusMap,
        ]);
    }

    /*
     * 后台增加余额
     */
    public function balance_edit(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(104);
            $model = new UserWallet();
            $post = input('post.');
            $model->incBalance($post);
        }
    }

    /*
     * 后台修改备注
     */
    public function remarks_edit(){
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(104);
            $model = new User();
            $post = input('post.');
            $model->editRemarks($post);
        }
    }

    //积分记录
    public function integral_log(){
        $model = new UserIntegralLog();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getListForId($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get,
        ]);
    }

    /**
     * -----        会员等级设置开始     ------------
     */
    //会员等级设置
    public function level()
    {
        $model = new UserLevel();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get
        ]);
    }

    //会员等级编辑
    public function level_edit(){
        $model = new UserLevel();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(16);
            $post = input('post.');
            $model->addLevel($post);
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id))
                $data = $model->getOne($id);
            return $this->fetch('',['data'=>$data]);
        }
    }

    //会员等级删除
    public function level_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(36);
            $post = input('post.');
            $model = new UserLevel();
            $model->levelDel($post['id']);
        }
    }

    /**
     * -----        会员等级设置结束     ------------
     */

    /**
     * -----  会员等级视图显示开始------
     */
    public function level_view(){
        $model = new UserViewLevel();
        $get = input('get.');
        return $this->fetch('',[
            'list'  =>  $model->getList($get),
            'count' =>  $model->count,
            'page'  =>  $model->page,
            'get'   =>  $get
        ]);
    }

    //等级视图-编辑
    public function level_view_edit(){
        $model = new UserViewLevel();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(16);
            $post = input('post.');
            $model->addLevel($post);
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id))
                $data = $model->getOne($id);
            return $this->fetch('',['data'=>$data]);
        }
    }

    // 等级视图-删除
    public function level_view_del()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            $model = new UserViewLevel();
            $modelFlag = $model->where('id',$post['id'][0])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    /**
     * -----  会员等级视图显示结束------
     */

    // 海报图列表
    public function viewPoster(){
        $model = new ViewPoster();
        $get = input('get.');
        return $this->fetch('/users/viewPoster/index',['list'=>$model->select(),'count' => $model->count()]);
    }

    // 海报图列表
    public function viewPosterEdit(){
        $model = new ViewPoster();
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
            return $this->fetch('/users/viewPoster/edit',['data'=>$data]);
        }
    }

    //删除
    public function viewPosterDel()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            $model = new ViewPoster();
            $modelFlag = $model->where('id',$post['id'][0])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    // 测试专用-编辑
    public function testEdit(){
        $model = new User();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            $this->checkAuth(136);
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            if(!isset($post['id']) || $post['id'] <= 0) return_ajax(400,'记录为空');
            $one = $model->where('id',$post['id'])->find();
            if(!$one) return_ajax(400,'没有该记录');
            $model->where('id',$post['id'])->update([
                'level' => $post['level'],
                'is_fictitious' => $post['is_fictitious']
            ]);
            UserWallet::where('uid',$post['id'])->update([
                'balance' => $post['balance'] * 100,
                'volume_balance' => $post['volume_balance'] * 100
            ]);
            return_ajax(200,'更新成功');
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id)){
                $data = $model->where('id',$id)->field('id,level,is_fictitious')->find()->toArray();
                if($data){
                    $walletData = UserWallet::where('uid',$id)->field('balance,volume_balance')->find();
                    $data['balance'] = $walletData['balance'] / 100;
                    $data['volume_balance'] = $walletData['volume_balance'] / 100;
                }
            }
            return $this->fetch('',['data'=>$data,'levels'=>(new UserLevel())->getLevel()]);
        }
    }

    // 测试专用-删除
    public function testDel(){
        $model = new User();
        if(request()->isPost() && request()->isAjax()) {
            $post = input('post.');
            $this->checkAuth(136);
            if (!isset($post['id']) || $post['id'] <= 0) return_ajax(400, '记录为空');
            $one = $model->where('id', $post['id'])->find();
            if (!$one) return_ajax(400, '没有该记录');
            $model->where('id', $post['id'])->update([
                'unionid' => null,
                'openid' => null,
                'phone' => 0,
                'is_delete' => 0,
            ]);
            return_ajax(200, '删除成功');
        }
    }
}
