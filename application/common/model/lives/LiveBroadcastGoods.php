<?php


namespace app\common\model\lives;

use app\common\model\Base;
use think\cache\driver\Redis;
use think\Db;
use lib\WxApi;
use lib\WxApiLikeGoods;

class LiveBroadcastGoods extends Base
{
    // 前缀 id值为 商品的id  goods_id
    public $prefix_url = 'pages/productDetails/productDetails?lives=lives&id=';

    // 微信审核状态值
    public $wx_status_strs = [
        '未审核',
        '审核中',
        '审核通过',
        '审核驳回'
    ];

    public function getNameAttr($value){
        return $value ? emojiDecode($value) : $value;
    }


    /*
     * 获取列表信息
     * */
    public function getList($get){
        // ->fetchSql();  当前查询的sql语句
        // ->getLastSql(); 最后一条sql执行的语句
//            ->paginate($get['limit'] < 200 ? $get['limit'] : 200,false,$get)
//        $where = ['l.is_delete'=>0];
//        if(isset($get['l.name'])) $where['name'] = ['like','%'.$get['name'].'%'];
//        if(strlen($get['l.status']) >= 1)    $where['l.status'] = $get['l.status'];
//        $field = 'l.id,l.goods_id,';
//        $list = $this->alias('l')
//            ->join('goods g','l.goods_id = g.id')
//            ->where($where)
//            ->select();
        $where = ['l.is_delete' => 0];
        if(isset($get['name'])) $where['l.name'] = ['like','%'.$get['name'].'%'];
        if(strlen($get['status']) >= 1)    $where['l.status'] = $get['status'];
        if(strlen($get['wx_status']) >= 1)    $where['l.wx_status'] = $get['wx_status'];
        if(strlen($get['cid']) >= 1)    $where['g.cid'] = $get['cid'];
        if(strlen($get['bid']) >= 1)    $where['g.bid'] = $get['bid'];
        $field = 'l.id,l.goods_id,l.wx_goods_id,l.cover,l.name,l.wx_status,l.price,l.price2,l.commission,l.status,l.sort,l.wx_status,l.is_hot,l.add_time,g.cid,g.bid,g.supply_url';
        $list = $this->alias('l')
            ->join('goods g','g.id = l.goods_id')->where($where)
            ->field($field)
            ->paginate($get['limit'] < 200 ? $get['limit'] : 200,false,$get);
        $this->count = $list->total();
        $this->page = $list->render();
        return $list;
    }

    //商品删除
    public function goodsDel($id){
        try{
            $one = $this->find($id);
            if(!$one)exception('记录不存在');
            $wxGoods = new WxApiLikeGoods();
            $status = $wxGoods->delGoods($one->wx_goods_id);
            if(!$status) return_ajax(400,'麻烦重新尝试');
            if($status['errcode'] != 0) return_ajax(400,'网络异常麻烦重新尝试');
            $flag = $this->where('id',$id)->update([
                'is_delete' => 1
            ]);
            if(!$flag) return_ajax(400,'异常，重新尝试');
            //$this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            return_ajax(200,'已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    // 商品状态 - 拉取
    public function goodsPush($id)
    {
        if(!$id || $id < 0) return return_ajax(400,'记录不得为空');
        try{
            $one = $this->where('wx_goods_id',$id)->find();
            if(!$one)exception('记录不存在');
            if($one->wx_status == 2)exception('当前状态已审核通过');
            if($one->add_time > time() - 360) exception('创建时间不得低于6分钟');
            $redis = new Redis();
            $bool = $redis->get('admin_lives_goods_list_push_status'.$id);
            if($bool) return_ajax(400,'还剩下'.(900 - time() + $bool).'秒即可更新状态');
            $redis->set('admin_lives_goods_list_push_status'.$id,time(),900);
            // 发起api状态
            $wxGoods = new WxApiLikeGoods();
            $status = $wxGoods->getGoodsStatus([$id]);
            if(!$status) return_ajax(400, '失败麻烦重新尝试');
            if($status['errcode'] != 0 || !isset($status['goods']) ) return_ajax(400, '失败麻烦重新尝试');
            if($status['goods'][0]['audit_status'] == 1) return return_ajax(400,'该商品还在审核中');
            $flag = $this->where('wx_goods_id',$id)->update([
                'wx_status'=>$status['goods'][0]['audit_status']
            ]);
            if(!$flag) return_ajax(400,'异常，麻烦重新尝试'.$status['goods'][0]['audit_status']);
            $str_return = $this->wx_status_strs[$status['goods'][0]['audit_status']];
            return_ajax(200,'状态已更新为'.$str_return,$str_return);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    // 商品编辑
    public function edit($data)
    {
        // 没有该记录
        if(!isset($data['id']) || !$data['id']) return return_ajax(400,'没有该序号');
        //名称
        if(!isset($data['name']) || !$data['name'] || strlen($data['name']) <= 2) return return_ajax(400,'名称不得为空，且名称长度不得小于2');
        // 价格
        if(!isset($data['price']) || !$data['price'] || $data['price'] <= 0) return return_ajax(400,'原价不得为空，且不得小于0');
        // 直播价
        if(!isset($data['price2']) || !$data['price2'] || $data['price2'] <= 0) return return_ajax(400,'直播不得为空，且不得小于0');
        //佣金
        if(!isset($data['commission']) || !$data['commission'] || $data['commission'] <= 0) return return_ajax(400,'佣金不得为空，且不得小于0');
        // 商品id
        if(!isset($data['goods_id']) || !$data['goods_id'] || $data['goods_id'] <= 0) return return_ajax(400,'商品不得为空，商品id大于0');
        // 图片
        if(!isset($data['cover']) || !$data['cover'] || strlen($data['cover']) < 5)
            return return_ajax(400,'图片失败');
        $one = $this->find($data['id']);
        if(!$one) return return_ajax(400, '没有该记录');

        // 一旦上传 昵称和封面图片没有变化更改
        /*
        $imgList = false;
        // 判断是否图片不一致, 则需要重新上传
        if($one->cover != $data['cover']){
            // 上传图片素材
            $imgList = $this->uploadImg($data['cover']);
        }
        */
        $updateGoods = [];
        $keys = ['price','price2'];
        // 如果 价格有变动
        if($data['price'] != $one->price || $data['price2'] != $one->price2){
            $updateGoods['priceType'] = 3;
            $updateGoods['price'] = $data['price'];
            $updateGoods['price2'] = $data['price2'];
        }
        // 迭代遍历
        //foreach($keys as $i) if($data[$i] != $one[$i]) $updateGoods[$i] = $data[$i];
        //if($imgList) $updateGoods['coverImgUrl'] = $imgList;
        $status = false;
        // 发起请求
        if($updateGoods && count($updateGoods) > 0){
            $redis = new Redis();
            $time = $redis->get('admin_lives_goods_update_edit_'.$one->id);
            if($time) return return_ajax(400,'抱歉单个商品,一个小时只能更新一次价格');
            $redis->set('admin_lives_goods_update_edit_'.$one->id,time(),3600 * 1);
            $wxGoods = new WxApiLikeGoods();
            $updateGoods['goodsId'] = $one->wx_goods_id;
            $status = $wxGoods->updateGoods($updateGoods);
            if(!$status) return_ajax(400, '失败，麻烦重新尝试',$status);
            if(!isset($status['success']) || $status['success'] != 'true') return_ajax(400, '微信直播商品更改失败，麻烦重新尝试',$status);
        }
        $data['name'] = emojiEncode($data['name']);
        $flag = $this->allowField(true)->isUpdate(true)->save($data,['id'=>$data['id']]);
        if(!$flag) return return_ajax(400,'错误');
        return return_ajax(200,'ok',[
            'sqlData' => $data,
            'wxData'  => $updateGoods,
            'wxStatus' => $status
        ]);
//        $this->isUpdate(true)->
    }

    // 商品添加
    public function add($data)
    {
        //名称
        if(!isset($data['name']) || !$data['name'] || strlen($data['name']) <= 3) return return_ajax(400,'名称不得为空，且名称长度不得小于3');
        // 价格
        if(!isset($data['price']) || !$data['price'] || $data['price'] <= 0) return return_ajax(400,'原价不得为空，且不得小于0');
        // 直播价
        if(!isset($data['price2']) || !$data['price2'] || $data['price2'] <= 0) return return_ajax(400,'直播不得为空，且不得小于0');
        //佣金
        if(!isset($data['commission']) || !$data['commission'] || $data['commission'] <= 0) return return_ajax(400,'佣金不得为空，且不得小于0');
        // 商品id
        if(!isset($data['goods_id']) || !$data['goods_id'] || $data['goods_id'] <= 0) return return_ajax(400,'商品不得为空，商品id大于0');
        // 图片
        if(!isset($data['cover']) || !$data['cover'] || strlen($data['cover']) < 5)
            return return_ajax(400,'图片失败');
        // 商品id限制
        if($this->where(['goods_id' =>$data['goods_id'],'is_delete'=>0])->count()) return return_ajax(400, '此商品已经存在，请选择另外一个');
        $cover_file = THINK_PATH.'../public'.$data['cover'];
        // 新增临时素材图片 api
        // 后台使用 cos 远程图片地址失效 只能使用本地的cos文件路劲
//        $wx = new WxApi();
//        $imgList = $wx->uploadImg([
//           'img' => $cover_file,
//           'type' => 'image'
//        ]);
        // 上传图片素材
        $imgList = $this->uploadImg($data['cover']);
        $redis = new Redis();
        $day = date('Y-m-d');
        $redis->sAdd('admin_lives_goods_add_'.$day,$imgList);
        $redis->expire('admin_lives_goods_add_'.$day,3600 * 24 * 3);
//        $redis->e
        // 发起api 请求
        $wxGoods = new WxApiLikeGoods();
        // api商品数据请求
        $goodList = $wxGoods->addGoods([
                'coverImgUrl' => $imgList,
                'name' => strlen($data['name']) > 16 ? substr($data['name'],0,14) : $data['name'],
                'priceType' => 3,
                'price' => $data['price'],
                'price2' => $data['price2'],
                'url' => $this->prefix_url.$data['goods_id']
        ]);
        if(!$goodList) return return_ajax(400,'错误');
        if($goodList['errcode'] != 0) return return_ajax(400,'错误，麻烦重新尝试，要求如下：图片分辨率300*300以内，名称包含中文',$goodList);
        $redis->sAdd('admin_lives_goods_lists',json_encode($goodList));
        $data['wx_goods_id'] = $goodList['goodsId'];
        $data['wx_audit_id'] = $goodList['auditId'];
        $data['coverImgUrl'] = $imgList;
        $data['name'] = emojiEncode($data['name']);
        $data['add_time'] = time();
        $log = $this->insert($data);
        if(!$log) return return_ajax(400,'异常，麻烦重新尝试');
        return return_ajax(200,'添加成功');
    }

    /**
     * 图片上传操作
     */
    public function uploadImg($img)
    {
        $sign = md5(round(time()/100).'lu_a_lu_lzs');
        //$context = stream_context_create(array('http'=>array('ignore_errors'=>true)));
        //$imgList = file_get_contents("https://abc.miaommei.com/api/index/uploadImgWx?img=$img&sign=$sign", FALSE, $context);
        //$imgList = file_get_contents();
        $imgList = curlGet("https://abc.miaommei.com/api/index/uploadImgWx?img=$img&sign=$sign");
        if(!$imgList) return return_ajax(400,'图片失败，麻烦重新尝试',$imgList);
        $imgList = json_decode($imgList,true);
        if($imgList['code'] != 200) return return_ajax(400,'图片失败了',$imgList);
        return $imgList['data'];
    }
}