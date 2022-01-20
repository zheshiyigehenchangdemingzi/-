<?php
namespace app\common\model;

use app\common\vendor\ImageSize;
use think\cache\driver\Redis;

Class Video extends Base
{
    const DEFAULT_TYPE = 0; // 默认的关联类型
    const TYPE_GOODS = 1; // 关联类型 商品
    const TYPE_ACTIVITY = 2; // 关联类型 活动
    const TYPE_ONLINE_RETAILERS = 3; // 关联类型 电商
    const TYPE_GROUP_BUYING = 4; // 关联类型 团购
    const TYPE_APPLET = 5; // 关联类型 小程序
    const TYPE_EXTERNAL_LINKS = 6; // 关联类型 外部链接

    static $TYPE_TEXTS = [ // 关联类型
        self::DEFAULT_TYPE => '普通',
        self::TYPE_GOODS  => '商品',
        self::TYPE_ACTIVITY  => '活动',
        self::TYPE_ONLINE_RETAILERS  => '电商',
        self::TYPE_GROUP_BUYING  => '团购',
        self::TYPE_APPLET  => '小程序',
        self::TYPE_EXTERNAL_LINKS  => '外部链接',
    ];

    const AD_TYPE_DEFAULT = 0; // 默认的 关联广告类型
    const AD_TYPE_POSTER = 1; // 关联广告类型 海报
    const AD_TYPE_IMAGE_TEXT = 2; // 关联广告类型 图文
    const AD_TYPE_TEXT = 3; // 关联广告类型 文字
    const AD_TYPE_ONLINE_RETAILERS = 4; // 关联广告类型 电商
    const AD_TYPE_CONSULT = 5; // 关联广告类型 咨询
    const AD_TYPE_THIRD_PARTY_APPLET = 6; // 关联广告类型 第三方小程序
    const AD_TYPE_OUTER_CHAIN = 7; // 关联广告类型 外链广告

    static $AD_TYPE_TEXTS = [ // 关联广告类型
        self::AD_TYPE_DEFAULT => '普通',
        self::AD_TYPE_POSTER  => '海报广告',
        self::AD_TYPE_IMAGE_TEXT  => '图文广告',
        self::AD_TYPE_TEXT  => '文字广告',
        self::AD_TYPE_ONLINE_RETAILERS  => '电商广告',
        self::AD_TYPE_CONSULT  => '咨询广告',
        self::AD_TYPE_THIRD_PARTY_APPLET => '第三方小程序',
        self::AD_TYPE_OUTER_CHAIN  => '外链广告',
    ];


    public $page = '';//分页数据
    public $count = '';//数据总数


    public function getTitleAttr($value)
    {
        return emojiDecode($value);
    }

    // 更新该视频的评论数
    public function updateCount($id){
        if(!$id || $id < 0)
            return false;
        if($this::where('id',$id)->count() <= 0 ) return false;
        # 一级评论中数量 求和即可 加上一级评论本身
        $num = VideoComment::where('relation_id',$id)->count() + VideoComment::where('relation_id',$id)->sum('child_count');
        $this::where('id',$id)->update([
            'comments' => $num
        ]);
        return true;
    }


    //获取列表
    public function getList($get){
        $where = [];
        if(!empty($get['title']))
            $where['title'] = ['like','%'.$get['title'].'%'];
        if(strlen($get['status']) >= 1) $where['status'] = $get['status'];
        if(strlen($get['is_hot']) >= 1) $where['is_hot'] = $get['is_hot'];
        if(strlen($get['is_check']) >= 1) $where['is_check'] = $get['is_check'];
        if(!empty($get['uid'])) $where['uid'] = $get['uid'];
        if(!empty($get['log_id'])) $where['log_id'] = $get['log_id'];
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['add_time'] = ['gt',strtotime($get['start'])];
            }
        }

        if(!empty($get['province'])) $where['province'] = $get['province'];
        if(!empty($get['city'])) $where['city'] = $get['city'];
        if(!empty($get['area'])) $where['area'] = $get['area'];
        if(!empty($get['pcd'])) $where['pcd'] = ['like','%'.$get['pcd'].'%'];

        $where['is_delete'] = 0;
        if(isset($get['type_id']) && strlen($get['type_id']) >= 1)
            $where['type_id'] = $get['type_id'];
        $field = 'id,type,type_id,relation_id,cover,title,status,is_hot,likes,shares,comments,add_time,video,uid,sort,is_check,province,city,area';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            $mod1 = new Goods();
            $mod2 = new Activity();

            $regionIds = Region::getDataCodeIds($list->toArray()['data']);
            $regionNames = Region::getListInCode($regionIds);

//            getListInCode
            foreach ($list as &$v){
                if($v['type'] == 1)
                    $v['relation_id'] = $mod1->getName($v['relation_id']);
                if($v['type'] == 2)
                    $v['relation_id'] = $mod2->getName($v['relation_id']);
                $v['cover'] = ImageSize::cosThumbnail($v['cover'] ?? '');
                $v['video'] = ImageSize::getStaticHostUrl($v['video'] ?? '');
                $v['pcd_name'] = ($regionNames[$v['province']] ?? '') . ($regionNames[$v['city']] ?? '')  .($regionNames[$v['area']] ?? '');
            }unset($v);
        }
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }

    //发布视频
    public function addVideo($data,$file){
        try{
            if(empty($data['title']))
                exception('视频标题不能为空');
            /*if(empty($data['type']) || !is_numeric($data['type']))
                exception('请选择产品关联类型');*/
            if(empty($data['type_id']) || !is_numeric($data['type_id']))
                exception('请选择视频分类');
            /*if(empty($data['relation_id']) || !is_numeric($data['relation_id']))
                exception('请选择关联产品');*/
            if(empty($data['uid']) || !is_numeric($data['uid']) || $data['uid'] <= 0)
                exception('请选择上传的用户');
            if(empty($data['sort']) || !is_numeric($data['sort']))
                $data['sort'] = 0;
            if(empty($data['likes']) || !is_numeric($data['likes']))
                $data['likes'] = 0;
            if(empty($data['shares']) || !is_numeric($data['shares']))
                $data['shares'] = 0;
            if(empty($data['comments']) || !is_numeric($data['comments']))
                $data['comments'] = 0;

            $save = [
                'title'             =>  emojiEncode($data['title']),
                'uid' =>  $data['uid'],
                'cover' => $data['cover'],
                'video' => $data['video'],
                'type'              =>  $data['type']??0,
                'type_id'           =>  $data['type_id'],
                'relation_id'       =>  $data['relation_id']??0,
                'sort'              =>  $data['sort'],
                'likes'              =>  $data['likes'],
                'is_check' => $data['is_check'],
                'shares'            =>  $data['shares'],
                'comments'          =>  $data['comments'],
                'status'            =>  $data['status'] == 1?1:0,
                'is_hot'            =>  $data['is_hot'] == 1?1:0,
                'add_time'   => isset($data['add_time']) ? strtotime($data['add_time']) + rand(500,3600 * 24) : time(),
                'pcd' => $data['pcd'] ?? '',
                'province' => $data['province'] ?? 0,
                'city' => $data['city'] ?? 0,
                'area' => $data['area'] ?? 0,
            ];
            $msg = '视频发布成功';

            $userCount = (new User())->where('id',$data['uid'])->count();
            if(!$userCount || $userCount <= 0)
                exception('当前用户id不存在');
            $r = new Redis();
            if(empty($data['id'])){
                if(empty($data['cover'])) exception('请上传视频封面');
                if(empty($data['video'])) exception('请上传视频');
//                if(empty($file['cover']['name']))
//                    exception('请上传视频封面');
//                if(empty($file['video']['name'])) exception('请上传视频');
                /*
                $cover = imgUpdate('cover','video_img');
                if($cover['code'] == 400)
                    exception($cover['msg']);
                if($cover && is_array($cover) && isset($cover['data'])){
                    $dir = explode('/',$cover['data']);
                    $file_name = COS_PREFIX.'/video/fengmian/'.$dir[3].'/'.$dir[4];
                    $d = $_SERVER['DOCUMENT_ROOT']; 
                    $r->rpush('mmei_cos_files',$d.'/'.$cover['data']."@@@".'/video/fengmian/'.$dir[3].'/'.$dir[4]);
                }
                */
//                $video = videoUpdate('video','video');
//                if($video['code'] == 400) exception($video['msg']);

                //$save['add_time'] = time();
//                $save['cover'] = isset($file_name) ? $file_name : $cover['data'];
//                $save['video'] = $video['data'];
                $this->allowField(true)->save($save);
            }else{
                /*
                if(!empty($file['cover']['name'])){
                    $cover = imgUpdate('cover','video_img');
                    if($cover['code'] == 400)
                        exception($cover['msg']);
                    if($cover && is_array($cover) && isset($cover['data'])){
                        $dir = explode('/',$cover['data']);
                        $file_name = COS_PREFIX.'/video/fengmian/'.$dir[3].'/'.$dir[4];
                        $d = $_SERVER['DOCUMENT_ROOT']; 
                        $r->rpush('mmei_cos_files',$d.'/'.$cover['data']."@@@".'/video/fengmian/'.$dir[3].'/'.$dir[4]);
                    }
                    $save['cover'] = isset($file_name) ? $file_name : $cover['data'];
                }
                if(!empty($file['video']['name'])){
                    $video = videoUpdate('video','video');
                    if($video['code'] == 400)
                        exception($video['msg']);
                    if($video && is_array($video) && isset($video['data'])){
                        $dir = explode('/',$video['data']);
                        $file_name_video = COS_PREFIX.'/video/houtai_mp4/'.$dir[3].'/'.$dir[4];
                        $d = $_SERVER['DOCUMENT_ROOT']; 
                        $r->rpush('mmei_cos_files',$d.'/'.$video['data']."@@@".'/video/houtai_mp4/'.$dir[3].'/'.$dir[4]);
                    } 
                    $save['video'] = isset($file_name_video) ? $file_name_video : $video['data'];
                }
                */

                # 如果为通过审核 , 那么推送该视频
                if(isset($data['id']) && $data['id'] && $save['is_check'] == 1){
                    // 如果审核状态为待审核
                    $model = Video::find($data['id']);
                    if($model && $model->is_check == 0){
                        $videoIsCheckKey = '_admin_video_edit_is_check_'.date('Y_m_d',$model['add_time']).'_'.$data['id'];
                        if(!$videoIsCheckValue = $r->get($videoIsCheckKey)){
                            (new UserVideoProfit())->updateProfit([
                                'type' => 1,
                                'uid'  => $save['uid'],
                                'time' => $model['add_time'],
                                'oid' => $data['id']
                            ]);
                            $r->set($videoIsCheckKey,1,3600 * 2);
                        }
                    }
                }

                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '视频修改成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //删除
    public function videoDel($id){
        try{
            if(!$id)
                exception('非法操作');
            $this->whereIn('id',$id)->update(['is_delete'=>1]);
            // ->allowField(true)->isUpdate(true)
            //$this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            return_ajax(200,'视频已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //获取单条记录
    public function getOne($id){
        return $this->where(['id'=>$id])->find();
    }

    //视频状态编辑
    public function editStatus($data){
        try{
            if(empty($data['id']))
                exception('视频错误');
            $status = 1;
            $msg = '视频上架成功';
            if($data['type'] == 'end'){
                $msg = '视频下架成功';
                $status = 0;
            }
            $this->allowField(true)->isUpdate(true)->save(['status'=>$status],['id'=>$data['id']]);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /**
     * 视频审核 只允许单次点击 ，且  首次 必定为待审核 状态 未发布
     */
    public function check($id){
        if(!$one = $this::find($id)) return false;
        if($one->is_check != 0) return false;
        $r = new Redis();
        $videoIsCheckKey = '_admin_video_edit_is_check_'.date('Y_m_d',$one['add_time']).'_'.$one['id'];
        if($videoIsCheckValue = $r->get($videoIsCheckKey))return false;
        $this::where('id',$one['id'])->update(['is_check' => 1]);
        (new UserVideoProfit())->updateProfit([
            'type' => 1,
            'uid'  => $one['uid'],
            'time' => $one['add_time'],
            'oid' => $one['id']
        ]);
        $r->set($videoIsCheckKey,1,3600 * 6);
        return 1;
    }


    /**
     * 一对一关联 关联用户表
     */
    public function oneUser()
    {
        return $this::hasOne(User::class,'id','uid');
    }
}