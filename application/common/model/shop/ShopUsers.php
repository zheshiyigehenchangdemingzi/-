<?php


namespace app\common\model\shop;



use app\admin\controller\Shop;
use app\common\model\Base;
use app\common\model\Goods;
use app\common\model\User;
use sms\TencentMsg;
use think\Exception;

// 店铺用户
class ShopUsers extends Base
{
    // 预处理数据
    public function getLogoAttr($value){
        return prefixUrlImg($value);
    }


    // 获取列表
    //获取商品列表
    public function getList($get){

        $where = ['is_delete'=>0];
        //用户id
        if(!empty($get['uid']))
            $where['uid'] = $get['uid'];
        //序号
        if(!empty($get['id']))
            $where['id'] = $get['id'];
        //店铺名称
        if(!empty($get['name']))
            $where['name'] = ['like','%'.$get['name'].'%'];
        // 审核状态
        if(!empty($get['apply_status']))
            $where['apply_status'] = $get['apply_status'];
        // 开启
        if(strlen($get['is_open']) >= 1)
            $where['is_open'] = $get['is_open'];
        //排序值
        if(isset($get['sort']) && strlen($get['sort']) >= 1)
            $where['sort'] = $get['sort'];
        //添加时间
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            if(!empty($get['end'])){
                $end = strtotime($get['end']);
                $where['add_time']  =  ['between',[$start,$end]];
            }else{
                $where['add_time'] = ['gt',$start];
            }
        }
//        $field = 'id,cid,bid,cover,name,status,stock,sale,add_time,sort,is_activity,is_hot,status,supply_url,shop_id';
//         ->field($field)
        $list = $this->where($where)->order('add_time desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    // 创建保存数据
    public function edit($post){
        if(!isset($post['name']) || strlen($post['name']) <= 2) return return_ajax(400,'店铺名称不得为空,且长度不得小于2');
        if(!isset($post['real_name']) || strlen($post['real_name']) <= 1) return return_ajax(400,'真实姓名不得为空,且长度不得小于1');
        if(!isset($post['phone']) || strlen($post['phone']) < 11) return return_ajax(400,'手机号码不得为空,且长度不得小于11');
        if(!isset($post['identity']) || strlen($post['identity']) < 18) return return_ajax(400,'身份证不得为空,且长度不得小于18');
        if(!isset($post['logo']) || strlen($post['logo']) < 18) return return_ajax(400,'logo图片必须上传');
        if(!isset($post['cardImgZhengMian_name']) || strlen($post['cardImgZhengMian_name']) < 18) return return_ajax(400,'身份证正面图片必须上传');
        if(!isset($post['cardImgFanMian_name']) || strlen($post['cardImgFanMian_name']) < 18) return return_ajax(400,'身份证反面图片必须上传');
        if(!isset($post['certificate_photo']) || strlen($post['certificate_photo']) < 18) return return_ajax(400,'营业执照图片必须上传');
        if(!isset($post['desc']) || strlen($post['desc']) < 10) return return_ajax(400,'店铺描述不得为空,且长度不得小于10');
        if(!isset($post['editorValue']) || strlen($post['editorValue']) < 5) return return_ajax(400,'店铺详情不得为空');

        // 主表数据
        $shopUserData = [
            'name' => $post['name'],
            'real_name' => $post['real_name'],
            'phone' => $post['phone'],
            'logo' => $post['logo'],
            'apply_status' => $post['apply_status'] - 0,
            'is_open' => $post['is_open'] - 0,
            'warm_number' => $post['warm_number'] - 0
        ];

        // 详情表数据
        $shopUserInfoData = [
            "card_img" => json_encode([$post['cardImgZhengMian_name'], $post['cardImgFanMian_name']]),
            'certificate_photo' => $post['certificate_photo'],
            'identity' => $post['identity'],
            'desc' => $post['desc'],
            'rule' => htmlspecialchars($post['editorValue']),
            'refuse_str' => $post['refuse_str']
        ];
        // 编辑状态
        $this::startTrans();
        try{
            $title = '添加商品成功';
            if(isset($post['id']) && $post['id'] > 0){
                $uM = $this->where('id',$post['id'] - 0)->update($shopUserData);
                $uiM = ShopUserInfo::where('shop_id',$post['id'] - 0)->update($shopUserInfoData);
                $title = '更新数据成功';
            } else {
                $uM = $this::create($shopUserData);
                $uiM = ShopUserInfo::create($shopUserInfoData);
            }
            if(!$uM && !$uiM) throw new Exception('数据更新失败'.$uM.'----'.$uM);
            $this::commit();
            return return_ajax(200,$title);
        }catch (Exception $e){
            $this::rollback();
            return return_ajax(400,$e->getMessage());
        }
    }

    // 店铺审核成功 操作
    public function createShopAdmin($data){
        if(!$one = $this->where([
            'id' => $data['id'],
            'is_delete' => 0
        ])->field('id,apply_status,name,phone,real_name')->find())return return_ajax(400,'没有该记录');
        if($one->apply_status != 0)return return_ajax(400,'当前状态不是待审核');
        // 验证 店铺账号中是否有该操作人员
        if(ShopAccount::where('shop_id',$one->id)->count())return return_ajax(400,'当前店铺已存在店铺后台管理账号');
        // 创建账号和密码
        $salt = getCode(10);
        $init_pwd = $data['id'].'_123456789';
        $pwd = getPassword($salt,$init_pwd);
//        $pinyin = new \Overtrue\Pinyin\Pinyin();
//        $account = $pinyin->abbr($one['real_name']).'_'.'admin';
        $account = $one['phone'];
        // 创建店铺账号信息
        ShopAccount::insert([
            'account' => $account,
            'password' => $pwd,
            'salt'      =>  $salt,
            'name'      =>  $one['real_name'],
            'role_id'   =>  1,
            'tel'       =>  $one['phone'],
            'shop_id' => $one['id'],
            'status'    =>  1,
            'type'    =>  1,
            'add_time'  =>  time(),
            'desc' => $account.' ------ '.$init_pwd
        ]);
        $this::where('id',$one->id)->update([
           'apply_status' => 1
        ]);

        $s = TencentMsg::setMobiles(['+86'.$one['phone']])
                        ::setContent([$account,$init_pwd])
                        ::setTemplateId('shop_examine_pass')
                        ::sendSms();
        return return_ajax(200,'审核成功');
    }

    // 店铺关闭
    public function toClose($data){
        if(!$one = $this::find($data['id']))return return_ajax(400,'没有该记录');
        if($one->is_open == 0)return return_ajax(400,'此店铺已经是关闭状态');
        // 将此店铺所有商品改为下架
        Goods::where([
            'shop_id' => $one->id,
            'is_delete' => 0
        ])->update([
           'status' => 2
        ]);
        $this::where('id',$one->id)->update([
            'is_open' => 0
        ]);
        return return_ajax(200,'下架成功');
    }



    // 获取数据  - 店铺记录
    public function getShop($id){
        if(!$id || $id <= 0) return [];
        return $this::alias('s')->join('shop_user_info i','i.shop_id = s.id','left')->where('s.id',$id)->find();
    }

    // 获取到用户信息
    public function getUserOne(){
        return $this::hasOne(User::class,'id','uid');
    }
}