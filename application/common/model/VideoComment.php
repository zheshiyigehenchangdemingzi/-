<?php
namespace app\common\model;

Class VideoComment extends Base
{
    public $page = '';//数据分页
    public $count = '';//数据总数

    public function getContentAttr($value){
        return emojiDecode($value);
    }

    public function getReplyAttr($value){
        return emojiDecode($value);
    }

    //获取列表
    public function getList($get){
        //type 评论类型 relation_id 关联id    假如类型是商品 关联id就是商品id
        $where = ['c.type'=>$get['type'],'c.relation_id'=>$get['id']];

        $field = 'c.content,c.reply,c.add_time,u.nickname,c.id,c.reply_time,c.child_count,c.uid';
        $list = $this->alias('c')->join('user u','c.uid=u.id','left')
            ->where($where)->field($field)->order('c.id desc')
            ->paginate(3,false,array('query'=>$get));
        if($list){
            foreach ($list as &$val){
                $val['content'] = htmlspecialchars_decode($val['content']);
                $val['reply'] = htmlspecialchars_decode($val['reply']);
            }unset($val);
        }
        $this->page = $list->render();
        $this->count = $this->alias('c')->join('user u','c.uid=u.id','left')->where($where)->count();
        return $list;
    }

    // 评论列表-- 新的拿取方式
    public function getListnew($get){
        $where = ['c.is_delete'=>0];
        //视频id
        if(!empty($get['vid']))
            $where['c.relation_id'] = $get['vid'];
        //评论者序号
        if(!empty($get['uid']))
            $where['c.uid'] = $get['uid'];    
        //是否回复
        if(isset($get['is_reply']) && strlen($get['is_reply']) >= 1)
            $get['is_reply'] == 1 ? $where['c.reply_time'] = ['>',0] : $where['c.reply_time'] = 0;
        //视频类型
        if(isset($get['type_id']) && strlen($get['type_id']) >= 1)
            $where['v.type_id'] = $get['type_id'];
        //评论内容
        if(!empty($get['content']))
            $where['c.content'] = ['like','%'.$get['content'].'%'];
        //回复内容
        if(!empty($get['reply']))
            $where['c.reply'] = ['like','%'.$get['reply'].'%'];
        //用户昵称
        if(!empty($get['nickname']))
            $where['u.nickname'] = ['like','%'.$get['nickname'].'%'];    
        //视频标题
        if(!empty($get['title']))
            $where['v.title'] = ['like','%'.$get['title'].'%'];
        $field = 'c.id,c.uid,c.relation_id,c.reply_time,c.child_count,c.likes,c.content,c.reply,c.add_time,u.nickname,v.title,v.type_id';
        $list = $this->alias('c')
                ->join('video v','c.relation_id=v.id','left')
                ->join('user u','c.uid=u.id','left')
                ->where($where)->field($field)->order('c.id desc')
                ->paginate($get['limit'],false,array('query'=>$get));
        if($list){
            foreach($list as &$v) $v['title'] = emojiDecode($v['title']);
        }        
        
        $this->page = $list->render();
        $this->count = $list->total();
        $list = $list->toArray();
        return $list['data'];        
    }

    //删除
    public function commentDel($id){
        try{
            if(!$id || $id < 0)
                exception('非法操作');
            // $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            $chilidNum = VideoCommentChild::where('fid',$id[0])->count();
            if($chilidNum > 0) return_ajax(400,'此评论有下级子评论数，不可删除');
            $one = $this->find($id[0])->toArray();
            # 一级评论  视频评论数
            $this->where('id',$id[0])->delete();
            $videoOne = Video::find($one['relation_id']);
            Video::where('id',$videoOne->id)->update([
                'comments' => $videoOne->comments - 1
            ]);
            #Video::where('id',$one->relation_id)->dec('comments');
            // VideoComment::where('id',$modelFlag->fid)->update([
            //     'child_count' => $count > 0 ? $count - 1 : 0
            // ]);
            return_ajax(200,'视频评论已删除一级评论');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    // 批量删除 并且 下级评论
    public function commentAllDel($id){
        if(!$id || !is_array($id))
            return_ajax(400,'请选择需要删除的');
        $num = 0;
        foreach($id as $i){
            $item = $this->find($i);
            $limit = 1;
            // 如果有下级评论
            if($item->child_count > 0){
                $limit += $item->child_count;
                VideoCommentChild::where('fid',$i)->delete();
            }
            // 更新视频评论数量
            Video::where('id',$item->relation_id)->setDec('comments', $limit);
            if($this::where('id',$i)->delete()) $num += 1;
        } 
        return_ajax(200, '删除成功-'.$num);
    }

    public function addReply($data){
        try{
            if(empty($data['id']))
                exception('请选择回复内容');
            if(empty($data['reply']))
                exception('请输入回复内容');

            $this->allowField(true)->isUpdate(true)->save([
                'reply'=>htmlspecialchars($data['reply']),
                'reply_time'=>time()
            ],['id'=>$data['id']]);
            return_ajax(200,'评论已回复');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    // 批量评论一级
    public function addCommentPlText($data){
        try{
            if(empty($data['goodsIds']))
                exception('请选择评论的视频列表');
            $goodsIds = json_decode($data['goodsIds']);
            if(!$goodsIds || !is_array($goodsIds)) exception('请选择评论的视频列表');
            if(empty($data['userList']))
                exception('请选择评论的会员和信息');
            $userList = json_decode($data['userList'],true);
            if(!$userList || !is_array($userList)) exception('会员和评论列表必须输入');
//            $start = strtotime(substr($data['dateYmd'],0,10));
//            $end = strtotime(substr($data['dateYmd'],12));
            $save = [
                // 'goods_id'      =>  $data['goods_id'],
                // 'uid'           =>  $data['uid'],
//                'level'         =>  $data['level'],
//                'discuss'       =>   emojiEncode($data['discuss']),// htmlspecialchars($data['discuss']),
                'reply' => isset($data['reply']) ? emojiEncode($data['reply']) : '',// htmlspecialchars($data['reply']),
                'add_time'      =>  time(),
//                'cover' => isset($data['bannerImgStr']) ? $data['bannerImgStr'] : '',
//                'is_anonymous' => $data['is_anonymous'],
            ];

            $allList = [];
            foreach($goodsIds as $goodsId){
                foreach($userList as $uid => $uid_item){
                    $save['uid'] = $uid;
                    $save['relation_id'] = $goodsId;
                    $save['add_time'] = strtotime($uid_item['date']) + rand(0,3600 * 24);
                    $save['content'] = emojiEncode($uid_item['discuss']);
                    $save['likes'] = $uid_item['likes'];
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

    /*
     * 后台添加评论
     */
    public function addComment($data){
        try{
            if(empty($data['relation_id']))
                exception('请选择评论的视频/商品');
            if(empty($data['content']))
                exception('请输入回复内容');
            if(empty($data['uid']))
                exception('请选择评论用户');

            $this->allowField(true)->save([
                'type'=>2,
                'uid'=>$data['uid'],
                'relation_id'=>$data['relation_id'],
                'content'=>htmlspecialchars($data['content']),
                'add_time'=>time()
            ]);
            # 一级评论  视频评论数
            (new Video())->updateCount($data['relation_id']);
            return_ajax(200,'评论成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());


         }
    }
}