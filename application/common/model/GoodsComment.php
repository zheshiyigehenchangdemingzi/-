<?php
namespace app\common\model;

use think\cache\driver\Redis;

Class GoodsComment extends Base
{
    public $count = 0;//数据总数
    public $page = '';//分页数据

    public function getDiscussAttr($value){
        return emojiDecode($value);
    }

    public function getReplyAttr($value){
        return emojiDecode($value);
    }

    /*
     * 提示：
     * 可能内容太多，此商品评论表是我前期所建，后面我建了一张评论表，里面有类型对应 视频或者商品，后来者可以根据实际情况，用那张表取代这个商品评论表
     * 也可以分开，商品评论继续用这张表，视频评论用那张评论表 Commnet
     * */

    /*
     * 获取所有评论
     * */
    public function getList($get){
        $where = ['c.is_delete'=> 0 ];
        //会员id
        if(!empty($get['uid']))
            $where['uid'] = $get['uid'];
        //是否回复
        if(isset($get['is_reply']) && strlen($get['is_reply']) >= 1)
            $where['is_reply'] = $get['is_reply'];    
        //是否匿名
        if(isset($get['is_anonymous']) && strlen($get['is_anonymous']) >= 1)
            $where['is_anonymous'] = $get['is_anonymous'];        
        //商品id
        if(!empty($get['goods_id']))
            $where['goods_id'] = $get['goods_id'];
        //评论内容
        if(!empty($get['discuss']))
            $where['discuss'] = ['like','%'.$get['discuss'].'%'];
        //回复内容
        if(!empty($get['reply']))
            $where['reply'] = ['like','%'.$get['reply'].'%'];
        //用户昵称
        if(!empty($get['nickname']))
            $where['u.nickname'] = ['like','%'.$get['nickname'].'%'];    
        //商品昵称
        if(!empty($get['name']))
            $where['g.name'] = ['like','%'.$get['name'].'%'];
        // 商品分类
        if(!empty($get['cid']))
            $where['g.cid'] = ['like','%'.$get['cid'].'%'];    
        // 商品品牌
        if(!empty($get['bid']))
            $where['g.bid'] = ['like','%'.$get['bid'].'%'];    
        $field = 'c.id,c.goods_id,c.uid,c.level,c.sort,c.discuss,c.is_display,c.add_time,c.reply_time,c.reply,c.is_reply,c.is_anonymous,c.cover as ccover,g.name,u.nickname,g.cid,g.bid,g.cover';
        //$list = $this->where($where)->field($field)->order('sort desc')
        //    ->paginate($get['limit'],false,array('query'=>$get));
        $list = $this->alias('c')
                ->join('user u','c.uid = u.id','left')
                ->join('goods g','c.goods_id = g.id','left')
                ->where($where)->order('c.sort desc,c.add_time desc')->field($field)
                ->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            foreach ($list as &$v){
                // $v['nickname'] = $v['nickname'] ? $v['nickname'] : '匿名用户';
                // if($v['nickname'] != '匿名用户'){
                //     $v['nickname'] = substr_cut(trim($v['nickname']));
                // }
                // $v['goods_id'] = $v['name'];
                // $v['uid'] = $v['nickname'];
                // $v['is_display'] = $v['is_display']==1?'显示':'隐藏';
                // $v['is_reply'] = $v['is_reply']>0?'已回复':'待回复';
                // $v['is_anonymous'] = $v['is_anonymous']>0?'匿名':'正常昵称';
                // $v['discuss'] = htmlspecialchars_decode($v['discuss']);
                if($v['ccover'] && strlen($v['ccover']) >= 5) $v['ccover'] = explode(',',$v['ccover']);
                if($v['cover'] && strlen($v['cover']) >= 5) $v['cover'] = explode(',',$v['cover'])[0];
            }unset($v);
        }
        $this->page = $list->render();
        $this->count = $list->total();

        return $list;
    }

    //获取评论表的指定数据内容
    public function getComment($id,$field){
        return $this->where(['id'=>$id])->field($field)->find();
    }

    //回复商品评论
    public function saveReply($data){
        try{
            if(empty($data['id']))
                exception('评论数据有误');
            $save = [
                'is_display'    =>  $data['is_display']==1?1:0
            ];
            if(!empty($data['reply'])){
                $save['reply'] = htmlspecialchars($data['reply']);
                $save['reply_time'] = time();
            }
            $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
            return_ajax(200,'修改成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //发布商品评论 - 后台
    public function addComment($data){
        try{
            if(empty($data['goods_id']))
                exception('请选择评论的商品');
            if(empty($data['uid']))
                exception('请选择评论的会员');

            $mod1 = new Goods();
            $mod2 = new User();
            if(!$mod1->where(['id'=>$data['goods_id']])->find())
                exception('评论的商品不存在');
            if(!$mod2->where(['id'=>$data['uid']])->find())
                exception('评论的会员不存在');
            if(empty($data['level']) || !is_numeric($data['level']) || $data['level'] > 5 || $data['level'] < 1)
                exception('评级范围1-5，取整数');
            if(empty($data['discuss']))
                exception('评论内容不能为空');
            $save = [
                'goods_id'      =>  $data['goods_id'],
                'uid'           =>  $data['uid'],
                'level'         =>  $data['level'],
                'discuss'       =>   emojiEncode($data['discuss']),// htmlspecialchars($data['discuss']),
                'reply' => emojiEncode($data['reply']),// htmlspecialchars($data['reply']),
                'is_reply' => strlen($data['reply']) > 3 ? 1 : 0, // 是否回复
                'is_display' => $data['is_display'],
                'add_time'      =>  time(),
                'sort'   => isset($data['sort']) ? $data['sort'] - 0 : 0
            ];
            /*
            if(!empty($data['cover']))
                $save['cover'] = trim($data['cover'],',');
            */


            $save['cover'] = isset($data['bannerImgStr']) ? $data['bannerImgStr'] : '';
            if(isset($data['bannerImgStr']) && $data['bannerImgStr'] && strlen($data['bannerImgStr']) > 5){
                $cover_files = explode(',',$save['cover']);
                $r = new Redis();
                $ymd = date('Y_m_d');
                $new_imgs = '';
                $len = count($cover_files);
                foreach($cover_files as $k => $img){
                    if(!$img) continue;
                    // 存在https
                    if(strpos($img,'https') !== false){
                        $new_imgs .= $img.($k == $len - 1 ? "" : ',');
                    }else{
                        $dir = explode('/',$img);
                        $cos_file_prefix = '/'.$dir[1].'/'.$dir[2].'/'.$ymd.'/'.$dir[3];
                        $d = $_SERVER['DOCUMENT_ROOT'];
                        $r->rpush('mmei_cos_files',$d.'/'.$img."@@@".$cos_file_prefix);
                        $new_imgs .= COS_PREFIX.$cos_file_prefix.($k == $len - 1 ? "" : ',');
                    }
                }
                if($new_imgs && strlen($new_imgs) > 10)
                    $save['cover'] = $new_imgs;
            }

            
            $boolInsert = isset($data['id']) && $data['id'] > 0;
            if($boolInsert)
                $save['id'] = $data['id']; 
            //$this->isUpdate(!$boolInsert)->allowField(true)->save($save);
            $this->allowField(true)->insert($save,$boolInsert);
            return_ajax(200,$boolInsert ? '更新成功' : '评论成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //发布商品评论 - 后台 - 批量
    public function addCommentPl($data){
        try{
            if(empty($data['goodsIds']))
                exception('请选择评论的商品');
            if(empty($data['uids']))
                exception('请选择评论的会员');
            // dateYmd    
            if(empty($data['dateYmd']))
                exception('请选择日期');
            $start = strtotime(substr($data['dateYmd'],0,10));    
            $end = strtotime(substr($data['dateYmd'],12));
            $mod1 = new Goods();    
            $mod2 = new User();
            // if(!$mod1->where(['id'=>$data['goods_id']])->find())
            //     exception('评论的商品不存在');
            // if(!$mod2->where(['id'=>$data['uid']])->find())
            //     exception('评论的会员不存在');
            if(empty($data['level']) || !is_numeric($data['level']) || $data['level'] > 5 || $data['level'] < 1)
                exception('评级范围1-5，取整数');
            if(empty($data['discuss']))
                exception('评论内容不能为空');
            $save = [
                // 'goods_id'      =>  $data['goods_id'],
                // 'uid'           =>  $data['uid'],
                'level'         =>  $data['level'],
                'discuss'       =>   emojiEncode($data['discuss']),// htmlspecialchars($data['discuss']),
                'reply' => emojiEncode($data['reply']),// htmlspecialchars($data['reply']),
                'is_reply' => strlen($data['reply']) > 3 ? 1 : 0, // 是否回复
                'is_display' => $data['is_display'],
                'add_time'      =>  time(),
                'cover' => isset($data['bannerImgStr']) ? $data['bannerImgStr'] : '',
                'is_anonymous' => $data['is_anonymous'],
                'sort'   => isset($data['sort']) ? $data['sort'] : 0
            ];

            $allList = [];
            $timeKey = [];
            foreach($data['goodsIds'] as $goodsId){
                foreach($data['uids'] as $uid){
                    $linshi = rand($start,$end);
                    if(isset($timeKey[$linshi])) {
                        $linshi = rand($start,$end);
                    }
                    $save['uid'] = $uid;
                    $save['goods_id'] = $goodsId;
                    $save['add_time'] = $linshi;
                    $allList[] = $save;
                    $timeKey[$linshi] = 1;
                }
            }
            // $save['cover'] = $cover_list;
            //$this->isUpdate(!$boolInsert)->allowField(true)->save($save);
            // $this->allowField(true)->insert($save,$boolInsert);
            $flag = $this->allowField(true)->insertAll($allList);
            return_ajax(200,'批量评论成功'.$flag);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /**
     * 批量评论-没有图片参与
     */
    public function addCommentPlText($data){
        try{
            if(empty($data['goodsIds']))
                exception('请选择评论的商品');
            $goodsIds = json_decode($data['goodsIds']);
            if(!$goodsIds || !is_array($goodsIds)) exception('请选择评论的商品');
            if(empty($data['userList']))
                exception('请选择评论的会员和信息');
            $userList = json_decode($data['userList'],true);
            if(!$userList || !is_array($userList)) exception('会员和评论列表必须输入');
//            $start = strtotime(substr($data['dateYmd'],0,10));
//            $end = strtotime(substr($data['dateYmd'],12));
            $mod1 = new Goods();
            $mod2 = new User();
            $save = [
                // 'goods_id'      =>  $data['goods_id'],
                // 'uid'           =>  $data['uid'],
//                'level'         =>  $data['level'],
//                'discuss'       =>   emojiEncode($data['discuss']),// htmlspecialchars($data['discuss']),
                'reply' => isset($data['reply']) ? emojiEncode($data['reply']) : '',// htmlspecialchars($data['reply']),
                'is_reply' => strlen($data['reply']) > 3 ? 1 : 0, // 是否回复
                'is_display' => $data['is_display'],
                'add_time'      =>  time(),
//                'cover' => isset($data['bannerImgStr']) ? $data['bannerImgStr'] : '',
//                'is_anonymous' => $data['is_anonymous'],
                'sort'   => isset($data['sort']) ? $data['sort'] : 0
            ];

            $allList = [];
            foreach($goodsIds as $goodsId){
                foreach($userList as $uid => $uid_item){
                    $save['uid'] = $uid;
                    $save['goods_id'] = $goodsId;
                    $save['add_time'] = strtotime($uid_item['date']) + rand(0,3600 * 24);
                    $save['discuss'] = emojiEncode($uid_item['discuss']);
                    $save['level'] = $uid_item['level'];
                    $save['is_anonymous'] = $uid_item['is_anonymous'];
                    $allList[] = $save;
                }
            }
            // $save['cover'] = $cover_list;
            //$this->isUpdate(!$boolInsert)->allowField(true)->save($save);
            // $this->allowField(true)->insert($save,$boolInsert);
            $flag = $this->allowField(true)->insertAll($allList);
            return_ajax(200,'批量评论成功'.$flag);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //评论删除
    public function commentDel($id){
        try{
            $count = count($id);
            if($count > 1){
                foreach ($id as $v){
                    if(!is_numeric($v))
                        exception('非法操作');
                }
                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>['in',$id]]);
            }else{
                if(!is_numeric($id[0]))
                    exception('非法操作');
                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id[0]]);
            }
            return_ajax(200,'评论已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}