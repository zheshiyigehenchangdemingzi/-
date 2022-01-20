<?php
namespace app\common\model;

use app\common\model\shop\ShopUsers;

Class Goods extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数

    // 预处理数据
    public function getAttrDataAttr($value)
    {
        return json_decode($value);
    }

    // 预处理数据
    public function getProductsJsonAttr($value)
    {
        return json_decode($value,true);
    }

    // 预处理数据
    public function getFreightAttr($value)
    {
        return  $value / 100;
    }

    // 预处理数据
    public function getDefaultCostPriceAttr($value)
    {
        return  $value / 100;
    }

    // 预处理数据
    public function getDefaultPriceAttr($value)
    {
        return  $value / 100;
    }

    // 预处理数据
    public function getDefaultDiscountPriceAttr($value)
    {
        return  $value / 100;
    }

    // 预处理数据
    /*
    public function setProductsJsonAttr($value)
    {
        return json_encode($value,true);
    }
    */

    // 预处理数据 attr_data
    /**
    public function setAttrDataAttr($value)
    {
    return json_encode($value);
    }
     * */


    //获取商品列表
    public function getList($get,$status_where){
        $where = $status_where;
        $where['is_delete'] = 0;
        //商品id
        if(!empty($get['id']))
            $where['id'] = $get['id'];
        //商品名称
        if(!empty($get['name']))
            $where['name'] = ['like','%'.$get['name'].'%'];
        //分类id
        if(!empty($get['cid']))
            $where['cid'] = $get['cid'];
        //品牌id
        if(!empty($get['bid']))
            $where['bid'] = $get['bid'];
        //精选
        if(isset($get['is_hot']) && strlen($get['is_hot']) >= 1)
            $where['is_hot'] = $get['is_hot'];
        //排序值
        if(isset($get['sort']) && strlen($get['sort']) >= 1)
            $where['sort'] = $get['sort'];
        //商品添加时间
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            if(!empty($get['end'])){
                $end = strtotime($get['end']);
                $where['add_time']  =  ['between',[$start,$end]];
            }else{
                $where['add_time'] = ['gt',$start];
            }
        }
        $sortVal = $get['sort_type'].' '.$get['sort_val'];
        $sort_str = ($sortVal && strlen($sortVal)  > 5 ? $sortVal.',add_time desc' : 'sort desc,add_time desc');
        $field = 'id,cid,bid,cover,name,status,stock,sale,add_time,sort,is_activity,is_hot,status,supply_url,shop_id,is_explosion,is_welfare,is_new';
        $list = $this->where($where)->field($field)->order($sort_str)
            ->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            $model = new GoodsAttr();
            foreach ($list as &$v){
                $cover = explode(',',$v['cover']);
                $v['cover'] = $cover[0];//轮播图可以是多个，保存形式中间用逗号间隔，显示取第一张
                $attr = $model->where(['product_id'=>$v['id']])->field('cost_price,price,discount_price,live_price')->find();
                $v['attr'] = $attr;
            }unset($v);
        }
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        $data = $this->where(['id'=>$id])->find();
        if(!$data)
            exception('没有该记录');
        $data['getAttrData'] = $data->AttrMany->toArray();
        if($data['getAttrData'] && is_array($data['getAttrData'])){
            // 调用整合数据
            $data['getAttrData'] = GoodsAttr::getAttrDataList($data['getAttrData']);
        }
        $data['cover'] = explode(',',$data['cover']);
        $data['desc'] = htmlspecialchars_decode($data['desc']);
//        $data['freight'] = number_format($data['freight']/100,2);
        return $data;
    }

    //获取商品状态
    public function getStatus($id){
        return $this->where(['id'=>$id])->field('id,status')->find();
    }

    //获取商品名称
    public function getName($id){
        return $this->where(['id'=>$id])->value('name');
    }

    //编辑商品状态
    public function editStatus($data){
        try{
            if(empty($data['id']))
                exception('商品错误');
            if(empty($data['status']))
                exception('请选择商品状态');
            if(!is_numeric($data['status']) || !in_array($data['status'],[1,2,3,4,5]))
                exception('商品状态错误');
            $this->allowField(true)->isUpdate(true)->save($data);
            return_ajax(200,'商品状态修改成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 获取商品列表
     * */
    public function getGoods(){
        return $this->where(['is_delete'=>0,'status'=>1])->column('id,name');
    }

    //获取部分字段
    public function getField($goodsId,$field){
        return $this->where(['id'=>$goodsId])->field($field)->find();
    }

    //商品删除
    public function goodsDel($id){
        try{
            $count = count($id);
            $now = time();
            if($count > 1){
                foreach ($id as $v){
                    if(!is_numeric($v))
                        exception('非法操作');
                }
                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1,'upd_time'=>$now],['id'=>['in',$id]]);
            }else{
                if(!is_numeric($id[0]))
                    exception('非法操作');
                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1,'upd_time'=>$now],['id'=>$id[0]]);
            }
            return_ajax(200,'商品已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //商品添加
    public function addGoods($data,$file){
        try{
            if(empty($data['img']))
                exception('规格图片不得为空');
            //基础数据检验
            if(empty($data['name']))
                exception('商品名称不能为空');
            if(empty($data['cid']) || !is_numeric($data['cid']))
                exception('请选择商品分类');
            if(empty($data['bid']) || !is_numeric($data['bid']))
                exception('请选择正确的商品品牌');
            if(empty($data['spu']))
                exception('商品货号不能为空');
            if(empty($data['title']))
                exception('商品分享标题不能为空');
            if(empty($data['share']))
                exception('请上传分享图');
//            if(empty($file['file']['name']))
//                exception('请上传分享图');
            // if(empty($data['cover']))
            //     exception('商品轮播图需要点击页面开始上传按钮');
//            if(empty($data['freight']) || !is_numeric($data['freight']))
//                exception('请填写运费');
            if(empty($data['editorValue']))
                exception('商品详情不能为空');
            if(empty($data['color']))
                $data['color'] = '红';//exception('商品颜色不能为空');
            if(empty($data['size']))
                $data['size'] = '小';//exception('商品尺寸不能为空');
            if(empty($data['cost_price']))
                exception('商品成本价格不能为空');
            if(empty($data['price']))
                exception('商品价格不能为空');
            if(empty($data['discount_price']))
                exception('默认商品价不能为空');
            if(empty($data['default_cost_price']))
                exception('默认商品成本价不能为空');
            if(empty($data['default_price']))
                exception('默认商品划线价不能为空');
            if(empty($data['default_discount_price']))
                exception('默认商品价不能为空');
            if(!isset($data['bannerImgStr']) || !$data['bannerImgStr'])
                exception('轮播图不得为空');
            if(empty($data['live_price']))
                exception('商品直播价格不能为空');
            if(empty($data['products_json']))
                exception('产品参数不得为空');
            if(empty($data['stock']))
                exception('商品库存不能为空');
            if(!is_numeric($data['status']) || !in_array($data['status'],[1,2,3,4,5]))
                exception('请选择正确的商品状态');
            if(!is_numeric($data['is_stock']) || !in_array($data['is_stock'],[1,2,3]))
                exception('请选择正确的减库存方式');
            // 货号不得相同
            if(isset($data['spu']) && $data['spu']){
                if($this::where('spu',$data['spu'])->count() > 0)
                    exception('货号相同，麻烦更改');
            }

            $count = count($data['price']);
            $now = time();
            $attr = [];
            $stock = 0;

            # 最大 最小金额
            $min_money = 9999999999999;
            $max_money = 0;

            // 如果有数据且 是json
            if($data['getAttrData'] && $getAttrData = json_decode($data['getAttrData'],true)){
                for ($i = 0;$i<$count;$i++){
                    $p = $i + 1;
                    if(empty($data['cost_price'][$i]))
                        exception('第'.$p.'行商品属性中的成本价为空');
                    if(empty($data['price'][$i]))
                        exception('第'.$p.'行商品属性中的商品价为空');
                    if(empty($data['discount_price'][$i]))
                        exception('第'.$p.'行商品属性中的折扣价为空');
                    if(empty($data['live_price'][$i]))
                        exception('第'.$p.'行商品属性中的直播价为空');
                    if(empty($data['stock'][$i]))
                        exception('第'.$p.'行商品属性中的库存为空');
                    if(empty($data['img'][$i]))
                        exception('第'.$p.'行商品属性中的图片为空');
                    $stock += $data['stock'][$i];
                    # 计算出 -  最小金额
                    if($min_money > $data['price'][$i])  $min_money = $data['price'][$i];
                    # 计算出 - 最大金额
                    if($data['price'][$i] > $max_money)  $max_money = $data['price'][$i];

                    $linshiData = [
                        'color'                 =>  '',//isset($data['color'][$i]) ?: $data['color'][$i],
                        'size'                  =>  '',
                        'valjson' => '{}',
                        'cost_price'            =>  $data['cost_price'][$i] * 100,
                        'price'                 =>  $data['price'][$i] * 100,
                        'discount_price'        =>  $data['discount_price'][$i] * 100,
                        'live_price'            =>  $data['live_price'][$i] * 100,
                        'stock'                 =>  $data['stock'][$i] - 0, // 库存
                        'integral'             =>  isset($data['integral']) && isset($data['integral'][$i]) ? $data['integral'][$i] - 0 : 0,
                        'add_time'              =>  $now,
                        'upd_time'              =>  $now,
                        'img' => $data['img'][$i]
                    ];
                    // 如果有自定义属性
                    if($getAttrData && isset($getAttrData[$i])){
                        $linshiData['valjson'] = json_encode($getAttrData[$i],true);
                    }
                    $attr[] = $linshiData;
                }
            }

            /*if($this->where(['spu'=>$data['spu'],'is_delete'=>0])->find())
                exception('此货号已被使用');*/
//            $res = imgUpdate('file','goods_share');//广告图片上传
//            if($res['code'] == 400)
//                exception($res['msg']);
            if(isset($data['default_cost_price']) && $data['default_cost_price'] - 10000000 > 0)
                exception('默认成本价不得超过百万');
            if(isset($data['default_price']) && $data['default_price'] - 10000000 > 0)
                exception('默认商品价不得超过百万');
            if(isset($data['default_discount_price']) && $data['default_discount_price'] - 10000000 > 0)
                exception('默认折扣价不得超过百万');

            $goods = [
                'name'          =>  $data['name'],
                'cid'           =>  $data['cid'],
                'bid'           =>  empty($data['bid'])?0:$data['bid'],
                'spu'           =>  $data['spu'],
                'cover'         =>   $data['bannerImgStr'],//trim($data['cover'],','),
                'desc'          =>  htmlspecialchars($data['editorValue']),
                'title'         =>  $data['title'],
                'share'         =>  $data['share'],
                'active_img'    =>  $data['active_img'],
                'status'        =>  $data['status'],
                'stock'         =>  $stock,
                'freight'       =>  empty($data['freight'])?0:$data['freight'] * 100,
                'sort'          =>  empty($data['sort'])?0:$data['sort'],
                'default_cost_price' => empty($data['default_cost_price'])?0:$data['default_cost_price'] * 100,
                'default_price' => isset($data['default_price']) ? $data['default_price'] * 100 : 0,// empty($data['default_price'])?0:$data['default_price'] * 100,
                'default_discount_price' => isset($data['default_discount_price']) ? $data['default_discount_price'] * 100 : 0, // empty($data['default_discount_price'])?0:$data['default_discount_price'] * 100,
                'sales_volume' => empty($data['sales_volume'])?0:$data['sales_volume'],
                'ticket'        =>  empty($data['ticket'])?50:$data['ticket'],
                'products_json' => $data['products_json'],
                'is_hot'        =>  $data['is_hot']==1?1:0,
                'is_explosion'        =>  $data['is_explosion']==1?1:0,
                'is_welfare'        =>  $data['is_welfare']==1?1:0,
                'is_new'        =>  $data['is_new']==1?1:0,
                'is_use'        =>  $data['is_use']==1?1:0,
                'is_team'       =>  $data['is_team']==1?1:0,
                'is_refund'     =>  $data['is_refund']==1?1:0,
                'is_sale'       =>  $data['is_sale']==1?1:0,
                'is_stock'      =>  $data['is_stock'],
                'add_time'      =>  $now,
                'upd_time'      =>  $now,
                'min_money'     =>  $min_money,
                'max_money'     =>  $max_money,
                'supply_url'    => isset($data['supply_url']) ?  $data['supply_url'] : ''
            ];

            $model = new GoodsAttr();
            $mod = new GoodsRole();
            $this::startTrans();
            try{
                //保存商品
                if(!$this->allowField(true)->save($goods))
                    exception('商品添加失败');
                //商品属性
                if(count($attr) > 1){
                    foreach ($attr as &$v){
                        $v['product_id'] = $this->id;
                    }unset($v);
                    if(!$model->allowField(true)->saveAll($attr))
                        exception('商品属性添加失败');
                }else{
                    $attr[0]['product_id'] = $this->id - 0;
                    if(!$model->allowField(true)->save($attr[0]))
                        exception('商品属性添加失败');
                }
                //商品其他权限记录
                $mod->allowField(true)->save(['goods_id'=>$this->id]);
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'商品添加成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //商品编辑
    public function editGoods($data,$file){
        try{
            //基础数据检验
            if(empty($data['id']))
                exception('商品错误');
            if(empty($data['name']))
                exception('商品名称不能为空');
            if(empty($data['cid']) || !is_numeric($data['cid']))
                exception('请选择商品分类');
            if(empty($data['bid']) || !is_numeric($data['bid']))
                exception('请选择正确的商品品牌');
            if(empty($data['spu']))
                exception('商品货号不能为空');
            if(empty($data['title']))
                exception('商品分享标题不能为空');
//            if(empty($data['freight']) || !is_numeric($data['freight']))
//                exception('请填写运费');
            if(empty($data['editorValue']))
                exception('商品详情不能为空');
            if(empty($data['color']))
                $data['color'] = '黄';
//                exception('商品颜色不能为空');
            if(empty($data['size']))
                $data['size'] = '小';
//                exception('商品尺寸不能为空');
            if(empty($data['cost_price']))
                exception('商品成本价格不能为空');
            if(empty($data['price']))
                exception('商品价格不能为空');
            if(empty($data['discount_price']))
                exception('商品折扣价不能为空');
            if(empty($data['products_json']))
                exception('产品参数不得为空');
            if(empty($data['live_price']))
                exception('商品直播价格不能为空');
            if(!isset($data['bannerImgStr']) || !$data['bannerImgStr'])
                exception('轮播图不得为空');
            if(empty($data['stock']))
                exception('商品库存不能为空');
            if(empty($data['default_cost_price']))
                exception('默认商品成本价不能为空');
            if(empty($data['default_price']))
                exception('默认商品划线价不能为空');
            if(empty($data['default_discount_price']))
                exception('默认商品价不能为空');
            if(!is_numeric($data['status']) || !in_array($data['is_stock'],[1,2,3,4,5]))
                exception('请选择正确的商品状态');
            if(!is_numeric($data['is_stock']) || !in_array($data['is_stock'],[1,2,3]))
                exception('请选择正确的减库存方式');
            $count = count($data['price']);
            $now = time();
            $attr = [];
            $ids = [];
            $stock = 0;

            # 最大 最小金额
            $min_money = 9999999999999;
            $max_money = 0;

            // 如果有数据且 是json
            if($data['getAttrData'] && $getAttrData = json_decode($data['getAttrData'],true)){
                for ($i = 0;$i<$count;$i++){
                    $p = $i + 1;
                    if(empty($data['cost_price'][$i]))
                        exception('第'.$p.'行商品属性中的成本价为空');
                    if(empty($data['price'][$i]))
                        exception('第'.$p.'行商品属性中的商品价为空');
                    if(empty($data['discount_price'][$i]))
                        exception('第'.$p.'行商品属性中的折扣价为空');
                    if(empty($data['live_price'][$i]))
                        exception('第'.$p.'行商品属性中的直播价为空');
                    if(empty($data['stock'][$i]))
                        exception('第'.$p.'行商品属性中的库存为空');
                    # 计算出 -  最小金额
                    if($min_money > $data['price'][$i])  $min_money = $data['price'][$i];
                    # 计算出 - 最大金额
                    if($data['price'][$i] > $max_money)  $max_money = $data['price'][$i];
                    $stock += $data['stock'][$i];
                    $linshiData = [
                        'color'                 =>  '',//isset($data['color'][$i]) ?: $data['color'][$i],
                        'size'                  =>  '',
                        'valjson' => '{}',
                        'cost_price'            =>  $data['cost_price'][$i] * 100,
                        'price'                 =>  $data['price'][$i] * 100,
                        'discount_price'        =>  $data['discount_price'][$i] * 100,
                        'live_price'            =>  $data['live_price'][$i] * 100,
                        'stock'                 =>  $data['stock'][$i] - 0, // 库存
                        'integral'             =>  isset($data['integral']) && isset($data['integral'][$i]) ? $data['integral'][$i] - 0 : 0,
                        'add_time'              =>  $now,
                        'upd_time'              =>  $now,
                        'product_id'  => $data['id'],
                        'img' => $data['img'][$i]
                    ];
                    // 如果有自定义属性
                    if($getAttrData && isset($getAttrData[$i])){
                        $linshiData['valjson'] = json_encode($getAttrData[$i],true);
                    }
                    // 商品规格属性-库存等
                    $attr[] = $linshiData;
                }
            }

            if(isset($data['default_cost_price']) && $data['default_cost_price'] - 10000000 > 0)
                exception('默认成本价不得超过百万');
            if(isset($data['default_price']) && $data['default_price'] - 10000000 > 0)
                exception('默认商品价不得超过百万');
            if(isset($data['default_discount_price']) && $data['default_discount_price'] - 10000000 > 0)
                exception('默认折扣价不得超过百万');

            $goods = [
                'name'          =>  $data['name'],
                'cid'           =>  $data['cid'],
                'bid'           =>  empty($data['bid'])?0:$data['bid'],
                'spu'           =>  $data['spu'],
                'desc'          =>  htmlspecialchars(str_replace('<p><br/></p>','',$data['editorValue'])),
                'share'         => $data['share'], // 分享图片
                'active_img'    =>  $data['active_img'],
                'title'         =>  $data['title'],
                'status'        =>  $data['status'],
                'stock'         =>  $stock,
                'freight'       =>  empty($data['freight'])?0:$data['freight'] * 100,
                'sort'          =>  empty($data['sort'])?0:$data['sort'],
                'default_cost_price' => empty($data['default_cost_price'])?0:$data['default_cost_price'] * 100,
                'default_price' => empty($data['default_price'])?0:$data['default_price'] * 100,
                'default_discount_price' => isset($data['default_discount_price']) ? $data['default_discount_price'] * 100 : 0, // empty($data['default_discount_price'])?0:$data['default_discount_price'] * 100,
                'sales_volume' => empty($data['sales_volume'])?0:$data['sales_volume'],
                'ticket'        =>  empty($data['ticket'])?50:$data['ticket'],
                'products_json' => $data['products_json'],
                'is_hot'        =>  $data['is_hot']==1?1:0,
                'is_explosion'        =>  $data['is_explosion']==1?1:0,
                'is_welfare'        =>  $data['is_welfare']==1?1:0,
                'is_new'        =>  $data['is_new']==1?1:0,
                'is_use'        =>  $data['is_use']==1?1:0,
                'is_team'       =>  $data['is_team']==1?1:0,
                'is_refund'     =>  $data['is_refund']==1?1:0,
                'is_sale'       =>  $data['is_sale']==1?1:0,
                'is_stock'      =>  $data['is_stock'],
                'upd_time'      =>  $now,
                'min_money'     =>  $min_money,
                'max_money'     =>  $max_money,
                'supply_url'    => isset($data['supply_url']) ?  $data['supply_url'] : '',
                'cover' => $data['bannerImgStr']
            ];

//            if(!empty($file['file']['name'])){
//                $res = imgUpdate('file','goods_share');//广告图片上传
//                if($res['code'] == 400)
//                    return_ajax(400,$res['msg']);
//                $goods['share'] = $res['data'];
//            }
            // if(!empty($data['cover']))
            //     $goods['cover'] = trim($data['cover'],',');

            $attrModel = new GoodsAttr();
//            $attrList = $goodsModel->AttrMany->toArray();
//            var_dump(count($attrList));
//            var_dump($id);
            $this::startTrans();
            try{
                // 先删除然后 新增商品属性
                $Aid = $attrModel->where('product_id',$data['id'])->delete();
                if(!$Aid)
                    exception('商品修改失败，请重新尝试');
                //保存商品
                if(!$this->allowField(true)->isUpdate(true)->save($goods,['id'=>$data['id']]))
                    exception('商品修改失败');

                if(count($attr) > 1){
                    if(!$attrModel->saveAll($attr))
                        exception('商品属性修改失败');
                }else{
                    if(!$attrModel->save($attr[0]))
                        exception('商品属性修改失败');
                }
                $this::commit();
            }catch (\Exception $e1){
                $this::rollback();
                exception($e1->getMessage());
            }
            return_ajax(200,'商品修改成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }


    // 一对多关联 获取到商品规格信息
    public function AttrMany()
    {
        return $this->hasMany(GoodsAttr::class,'product_id','id');
    }

    // 一对一关联 获取到店铺信息
    public function shopOne(){
        return $this->hasOne(ShopUsers::class,'id','shop_id');
    }
}