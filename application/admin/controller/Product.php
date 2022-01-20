<?php
namespace app\admin\controller;
use app\common\model\Goods;
use app\common\model\GoodsAttr;
use app\common\model\GoodsBrand;
use app\common\model\GoodsComment;
use app\common\model\GoodsRole;
use app\common\model\GoodsType;
use app\common\model\product\GoodsProduct;
use app\common\model\product\HomeDisplay;
use app\common\model\product\ViewProductBanner;
use app\common\model\UserLevel;

// 商品-规格模板模型
use app\common\model\product\GoodsSpecification as GoodsSpecificationModel;
use app\common\model\User;

Class Product extends Common{
    //商品列表
    public function index()
    {
        // 商品列表, 分类，品牌
        $model = new Goods();
        $get = input('get.');
        if(!isset($get['cid'])) $get['cid'] = '';
        if(!isset($get['bid'])) $get['bid'] = '';
        if(!isset($get['status_key'])) $get['status_key'] = '';
        if(!isset($get['status_type'])) $get['status_type'] = '';
        if(!isset($get['sort_type'])) $get['sort_type'] = '';
        if(!isset($get['sort_val'])) $get['sort_val'] = '';
        if(!isset($get['limit'])) $get['limit'] = 20;
        if($get['limit'] > 200) $get['limit'] = 200;
        //商品状态，1-上架，2-下架，3-缺货，4-预售，5-禁售
        $status = [
            1   =>  '上架',
            2   =>  '下架',
            3   =>  '缺货',
            4   =>  '预售',
            5   =>  '禁售'
        ];


        $status_types = [
            'is_hot' => '热门商品',
            'status' => '上架',
            'is_explosion' => '爆款',
            'is_welfare' => '福利',
            'is_new' => '新品',
            'is_use' => '购物券',
            'is_team' => '团队',
            'is_sale' => '分享'
        ];

        $status_keys = [
            'is_hot' => [0,1],
            'status' => [2,1],
            'is_explosion' => [0,1],
            'is_welfare' => [0,1],
            'is_new' => [0,1],
            'is_use' => [0,1],
            'is_team' => [0,1],
            'is_sale' => [0,1]
        ];
        $where = [];
        if(isset($status_types[$get['status_type']]) && isset($status_keys[$get['status_type']][$get['status_key']]))$where[$get['status_type']] = $status_keys[$get['status_type']][$get['status_key']];

        $sort_types = [
            'id' => '序号',
            'sort' => '排序值',
            'add_time' => '添加时间',
            'stock' => '库存',
            'sale' => '销量',
            'default_cost_price' => '默认成本价',
            'default_price' => '默认划线价',
            'default_discount_price' => '默认折扣-商品价',
            'sales_volume' => '默认销量',
            'min_money' => '最小价格',
            'max_money' => '最大价格',
            'ticket' => '购物券可抵扣',
            'upd_time' => '更新时间',
        ];


        return $this->fetch('',[
            'type'      =>  (new GoodsType())->getSrList(), //  $mod->getTypeColumn(),
            'brand'     =>  (new GoodsBrand())->getSrList(), // $mods->getBrandColumn(),
            'list'      =>  $model->getList($get,$where),
            'page'      =>  $model->page,
            'count'     =>  $model->count,
            'get'       =>  $get,
            'status'    =>  $status,
            'status_types' => $status_types,
            'sort_types' => $sort_types
        ]);
    }

    //商品图片上传处理
    /*
    public function uploads(){
        if(empty($_FILES['fileList']['name']))
            return_ajax(400,'请选择上传的商品图片');

        $res = imgUpdate('fileList','goods');
        if($res['code'] == 400)
            return_ajax(400,$res['msg']);
        return_ajax(200,'success',['cover'=>$res['data'].',']);
    }
    */

    //商品添加
    public function add()
    {
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(27);
            $post = input('post.');
            $model = new Goods();
            $model->addGoods($post,$_FILES);
        }else{

            // 获取规格模板
            $attrData =  (new GoodsSpecificationModel())->getMuBanList();
            // 获取到 产品模板
            #$productList = (new GoodsProduct())->getMuBanList();
            $productList = GoodsProduct::where('pid',0)->select();
            return $this->fetch('',[
                'type'  =>(new GoodsType())->getSrList(),
                'brand' =>(new GoodsBrand())->getSrList(),//  $mods->getBrandColumn(),
                'specificationList' =>$attrData,
                'productList' => $productList
            ]);
        }
    }

    //商品编辑
    public function edit()
    {
        $model = new Goods();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(27);
            $post = input('post.');
            $model->editGoods($post,$_FILES);
        }else{
            $mod = new GoodsType();
            $mods = new GoodsBrand();
            $attrMod = new GoodsAttr();
            $productList = GoodsProduct::where('pid',0)->select();
            $id = input('get.id');
            $data = $model->getOne($id);
            $attr = $attrMod->getList($data['id']);
            return $this->fetch('',[
                'type'  =>(new GoodsType())->getSrList(),
                'brand' =>(new GoodsBrand())->getSrList(),//  $mods->getBrandColumn(),
                'data'  =>  $data,
                'attr'  =>  $attr,
                'productList' => $productList
            ]);
        }
    }

    //商品状态编辑
    public function status()
    {
        $model = new Goods();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(38);
            $post = input('post.');
            $model->editStatus($post);
        }else{
            $status = [
                1   =>  '上架',
                2   =>  '下架',
                3   =>  '缺货',
                4   =>  '预售',
                5   =>  '禁售'
            ];
            $id = input('get.id');
            $data = $model->getStatus($id);
            return $this->fetch('',[
                'data'     =>  $data,
                'status'  =>  $status
            ]);
        }
    }

    //商品独立规则设定
    public function role()
    {
        $model = new GoodsRole();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(37);
            $post = input('post.');
            $model->addRole($post);
        }else{
            $get = input('get.');
            if($get['type'] == 'sale'){
                $field = 'is_sale_odd,sale_conf';
                $role = $model->getRole($get['id'],$field);
            }elseif($get['type'] == 'live'){
                $field = 'is_live,live_type,live_val,is_live_odd,live_conf';
                $role = $model->getRole($get['id'],$field);
                if($role['is_live_odd'] == 0)
                    $role['live_conf']['type'] = '';
            }else{
                $field = 'is_role,role_def,is_role_odd,role_conf';
                $role = $model->getRole($get['id'],$field);
            }
            $role['id'] = $get['id'];
            //printData($role);
            $mods = new UserLevel();
            $level = $mods->getLevel();

            return $this->fetch($get['type'],[
                'role'   =>  $role,
                'level'  =>  $level,
                'style'  =>  $get['type']
            ]);
        }
    }

    //商品删除
    public function goods_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(28);
            $post = input('post.');
            $model = new Goods();
            $model->goodsDel($post['id']);
        }
    }

    //商品分类
    public function type()
    {
        $model = new GoodsType();
        #return return_ajax(200,'ok',$model->getList());
        return $this->fetch('',['list'=>$model->getList(),'count'=>$model->count]);
    }

    //商品分类编辑
    public function type_edit()
    {
        $model = new GoodsType();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(27);
            $post = input('post.');

            $model->addType($post,$_FILES);
        }else{
            $id = input('get.id');
            $data = ['pid'=>''];
            if($id){
                $data = $model->getOne($id);
            }
            return $this->fetch('',['type'=>$model->getTypeColumn(),'data'=>$data]);
        }
    }

    //分类删除
    public function type_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(28);
            $post = input('post.');
            $model = new GoodsType();
            $model->typeDel($post['id']);
        }
    }

    //商品品牌
    public function brand()
    {
        $model = new GoodsBrand();
        $get = input('get.');
        if(!isset($get['limit']) || strlen($get['limit']) < 1)
            $get['limit'] = 20;
        return $this->fetch('',[
            'list'=>$model->getList($get),
            'count'=>$model->count,
            'page'=>$model->page,
            'get'=>$get
        ]);
    }

    //商品品牌编辑
    public function brand_edit()
    {
        $model = new GoodsBrand();
        if(request()->isAjax() && request()->isPost()){
            $this->checkAuth(32);
            $post = input('post.');
            $model->addBrand($post,$_FILES);
        }else{
            $id = input('get.id');
            $data = [];
            if($id){
                $data = $model->getOne($id);
            }
            return $this->fetch('',['data'=>$data]);
        }
    }

    //品牌删除
    public function brand_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(33);
            $post = input('post.');
            $model = new GoodsBrand();
            $model->brandDel($post['id']);
        }
    }

    public function comment(){
        $model = new GoodsComment();
        $get = input('get.');
        if(!isset($get['limit'])){
            $get['limit'] = 20;
        }
        if(!isset($get['is_anonymous'])){
            $get['is_anonymous'] = '';
        }
        if(!isset($get['is_reply'])){
            $get['is_reply'] = '';
        }
        if(!isset($get['bid'])){
            $get['bid'] = '';
        }
        if(!isset($get['cid'])){
            $get['cid'] = '';
        }
        return $this->fetch('',[
            'list'=>$model->getList($get),
            'count'=>$model->count,
            'page'=>$model->page,
            'get'=>$get,
            'goodtypes' => (new GoodsType())->getSrList(), // GoodsType::where(['is_delete'=>0,'status'=>1,'is_show'=>1])->field('id,name')->select(),
            'goodBs' => (new GoodsBrand())->getSrList() , //GoodsBrand::where(['is_delete' => 0])->field('id,name')->select(),
        ]);
    }

    public function reply_edit(){
        $model = new GoodsComment();
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(41);
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'回复内容含有非法字符');
            $model->saveReply($post);
        }else{
            $id = input('get.id');
            $data = $model->getComment($id,'id,reply,is_display');
            return $this->fetch('',['data'=>$data]);
        }
    }

    public function comment_add(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(40);
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'回复内容含有非法字符');
            $model = new GoodsComment();
            $model->addComment($post,true);
        }else{
            $model = new GoodsComment();
            $id = input('get.id');
            if(!$id)
                return $this->fetch('',['data'=>[]]);
            $data = $model->find($id);
            return $this->fetch('',['data'=>$data,'imgs' => explode(',',$data->cover)]);
            // ,'imgs' => explode(',',$data->cover)
        }
    }

    /**
     * 批量添加商品评论
     * @return mixed
     */
    public function comment_add_pl(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(40);
            $post = input('post.');
            // if(!$this->check_input($post))
            //     return_ajax(400,'回复内容含有非法字符');
            $model = new GoodsComment();
            $model->addCommentPl($post,true);
        }else{
            $users = (new User())->getUsers();
            shuffle($users);
            $data = [];
            return $this->fetch('',[
                'data'=>$data,
                'users' => $users,
                'goodtypes' => (new GoodsType())->getSrList(), // GoodsType::where(['is_delete'=>0,'status'=>1,'is_show'=>1])->field('id,name')->select(),
                'goodBs' => (new GoodsBrand())->getSrList() , //GoodsBrand::where(['is_delete' => 0])->field('id,name')->select(),
                'count' => count($users)
            ]);
            // ,'imgs' => explode(',',$data->cover)
        }
    }

    /**
     * 批量评论-- 无图片操作
     */
    public function comment_add_text_pl(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(40);
            $post = input('post.');
            // if(!$this->check_input($post))
            //     return_ajax(400,'回复内容含有非法字符');
            $model = new GoodsComment();
            $model->addCommentPlText($post,true);
        }else{
            $data = [];
            $users = (new User())->getUsers();
            shuffle($users);
            return $this->fetch('',[
                'data'=>$data,
                'users' => $users,
                'goodtypes' => (new GoodsType())->getSrList(), // GoodsType::where(['is_delete'=>0,'status'=>1,'is_show'=>1])->field('id,name')->select(),
                'goodBs' => (new GoodsBrand())->getSrList(), //GoodsBrand::where(['is_delete' => 0])->field('id,name')->select(),
                'count' => count($users)
            ]);
            // ,'imgs' => explode(',',$data->cover)
        }
    }

    //评论删除
    public function comment_del(){
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(42);
            $post = input('post.');
            $model = new GoodsComment();
            $model->commentDel($post['id']);
        }
    }

    /**
     * ---------------------------------------
     * ******   规格模板开始  *********
     * Specification
     * ---------------------------------------
     */
    // 查看列表
    public function Specification()
    {
        $model = new GoodsSpecificationModel();
        $get = input('get.');
        $list = $model->getList($get['value'] ?? '' ,$get['type'] ?? '');
        #return return_ajax(200,'测试', $list);
        return $this->fetch('/product/specification/index',[
            'list' => $list,
            'count' => $model->count,
            'get' => $get
        ]);
    }

    //编辑添加等
    public function specificationEdit()
    {
        $this->checkAuth(96);
        $model = new GoodsSpecificationModel();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->addLogistics($post);
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id))
                $data = $model->getChild($id);
            return $this->fetch('/product/specification/edit',$data);
        }
    }

    // 删除操作
    public function specificationDel(){
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(96);
            $model = new GoodsSpecificationModel();
            $modelFlag = $model->where('id',$post['id'])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    /**
     * ---------------------------------------
     * ******   规格模板结束  *********
     * Specification
     * ---------------------------------------
     */

      /**
     * ---------------------------------------
     * ******   规格模板开始  *********
     * Specification
     * ---------------------------------------
     */
    // 查看列表
    public function product()
    {
        $model = new GoodsProduct();
        $get = input('get.');
        $list = $model->getList($get['value'] ?? '' ,$get['type'] ?? '');
        #return return_ajax(200,'测试', $list);
        return $this->fetch('/product/product/index',[
            'list' => $list,
            'count' => $model->count,
            'get' => $get
        ]);
    }

    // 获取
    public function productGet(){
        $id = input('get.id');
        if(!$id || $id < 1) return return_ajax( 400 ,'参数错误');
        $list = GoodsProduct::where('pid',$id)->order('sort desc')->select();
        if($list && count($list) > 0) $list = $list->toArray();
        return return_ajax( 200 ,'操作ok',$list);
    }

    //编辑添加等
    public function productEdit()
    {
        $this->checkAuth(96);
        $model = new GoodsProduct();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->addLogistics($post);
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id))
                $data = $model->getChild($id);
            return $this->fetch('/product/product/edit',$data);
        }
    }

    // 删除操作
    public function productDel(){
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(96);
            $model = new GoodsProduct();
            $modelFlag = $model->where('id',$post['id'])->delete();
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    /**
     * ---------------------------------------
     * ******   规格模板结束  *********
     * Specification
     * ---------------------------------------
     */

    /**
     * --- 商品轮播图 start ----
     */
    /*
    //列表
    public function banner()
    {
        $model = new ViewProductBanner();
        $get = Input('get.');
        if(!isset($get['show'])) $get['show'] = '';
        if(!isset($get['is_skip'])) $get['is_skip'] = '';
        return $this->fetch('/product/banner/index',[
            'list'  =>  $model->getList($get),
            'count' => $model->select()->count(),
            'get' => $get
        ]);
    }

    //编辑
    public function banner_edit(){
        $this->checkAuth(107);
        $model = new ViewProductBanner();
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
            return $this->fetch('/product/banner/edit',['data'=>$data]);
        }
    }

    //删除
    public function bannerDel()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(107);
            $model = new ViewProductBanner();
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
        if(!in_array($post['key'],['show','is_skip']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
//        Ban
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(93);
            $modelFlag = ViewProductBanner::where('id',$post['id'])->update([
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
        if(!in_array($post['key'],['show','is_skip']))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(93);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = ViewProductBanner::whereIn('id',$post['ids'])->update([
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
        ViewProductBanner::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }
*/
    /**
     * --- 商品首页功能展示配置 start ----
     */
    /*
    //商品首页功能菜单
    public function functionMenu()
    {
        $model = new HomeDisplay();
        return $this->fetch('/product/function_menu/index',[
            'list'  =>  $model->getList(),
            'count' => $model->select()->count()
        ]);
    }

    //编辑
    public function functionMenuEdit(){
        $this->checkAuth(133);
        $model = new HomeDisplay();
        if(request()->isPost() && request()->isAjax()){
            $post = input('post.');
            if(!$this->check_input($post))
                return_ajax(400,'输入内容含有非法字符');
            $model->addLogistics($post);
        }else{
            $id = input('get.id');
            $data = [];
            if(!empty($id))
                $data = $model->where(['id'=>$id,'is_delete'=>0])->find()->toArray();
            return $this->fetch('/product/function_menu/edit',['data'=>$data]);
        }
    }

    //删除
    public function functionMenuDel()
    {
        $post = input('post.');
        if(!isset($post['id'])){
            return_ajax(400,'请选择对应列表');
        }
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(134);
            $model = new HomeDisplay();
            $modelFlag = $model->where('id',$post['id'])->update(['is_delete'=>1]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }
    */

    /**
     * ----  改变状态  ----
     */
    public function goods_list_status()
    {
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        $status = ['status','is_hot','is_explosion','is_welfare','is_new'];
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],$status))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            // 如果为状态  且 不等于 指定状态
            if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = Goods::where('id',$post['id'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态
    public function goods_list_pinliang_status()
    {
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0){
            return_ajax(400,'请选择对应列表');
        }
        $status = ['status','is_hot','is_explosion','is_welfare','is_new'];
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        if(!in_array($post['key'],$status))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            // 如果为状态  且 不等于 指定状态
            if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = Goods::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 排序
    public function sortEdit()
    {
        $get = input('get.');
        if(!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400,'错误记录值');
        if(!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400,'修改值错误');
        Goods::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }

    // 获取商品
    public function getGoods(){
        $get = input('get.');
        $cid = isset($get['cid']) ? $get['cid'] : -1;
        $bid = isset($get['bid']) ? $get['bid'] : -1;
        $goods_id = isset($get['goods_id']) ? $get['goods_id'] : -1;
        $name = isset($get['name']) ? $get['name'] : false;
        // 商品id
        $where = ['is_delete' => 0,'status' => 1];
        if($bid > 0 ) $where['bid'] = $bid;
        if($cid > 0 ) $where['cid'] = $cid;
        if($goods_id > 0 ) $where['id'] = $goods_id;
        if($name && strlen($name) >= 1) $where['name'] = ['like','%'.$name.'%'];
        $list = Goods::where($where)->field('id,name,cover,supply_url')->select();
        return return_ajax(200,'操作ok',['data'=>$list]);
    }

    /**
     * 商品评论批量开始--
     */
    /**
     * ----  改变状态  ----
     */
    public function comment_list_status()
    {
        $post = input('post.');
        if(!isset($post['id']) || $post['id'] <= 0 ){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        $keys = ['is_display','is_anonymous'];
        if(!in_array($post['key'],$keys))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            // 如果为状态  且 不等于 指定状态
            if(!in_array($post['val'],[0,1])) return return_ajax(400,'状态错误');
            // if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = GoodsComment::where('id',$post['id'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 批量改变状态
    public function comment_pinliang_status()
    {
        $post = input('post.');
        if(!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0){
            return_ajax(400,'请选择对应列表');
        }
        if(!isset($post['key']) || !$post['key'])return_ajax(400,'错误键');
        $keys = ['is_display','is_anonymous'];
        if(!in_array($post['key'],$keys))return_ajax(400,'类型错误');
        if(!isset($post['val']))return_ajax(400,'错误值');
        if(request()->isPost() && request()->isAjax()){
            $this->checkAuth(94);
            // 如果为状态  且 不等于 指定状态
            if(!in_array($post['val'],[0,1])) return return_ajax(400,'状态错误');
            // if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = GoodsComment::whereIn('id',$post['ids'])->update([
                $post['key'] =>  $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400,$modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试',$post);
        }
    }

    // 排序
    public function comment_sortEdit()
    {
        $get = input('get.');
        if(!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400,'错误记录值');
        if(!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400,'修改值错误');
        GoodsComment::where('id',$get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200,'操作ok',['id'=>$get['id']]);
    }

     /**
      * 商品评论批量结束
      */
}