<?php
namespace app\common\model;

use app\common\model\Video;
use app\common\model\VideoComment;

use think\Db;

Class VideoCommentChild extends Base
{
    public $page = '';//数据分页
    public $count = '';//数据总数
    public $error = '';//异常

    //获取列表
    public function getList($get){
        $where = ['fid'=>$get['fid']];
        $field = 'c.content,c.reply,c.add_time,u.nickname,c.id,c.reply_time,c.fid,c.upid,c.uid,ui.nickname as pname';
        $list = $this->alias('c')->join('user u','c.uid = u.id','left')
            ->join('user ui','c.upid = ui.id','left')->where($where)->field($field)
            ->order('id desc')->paginate(3,false,array('query'=>$get));

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

    // 添加回复内容
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
            
            # 一级评论
            /*
            $vComment = VideoComment::find($data['fid']);
            $videoS  = Video::find($vComment->relation_id);
            var_dump($videoS->toArray());
            VideoComment::update([
                'child_count' => $vComment->child_count + 1
            ]);
            Video::where('id',$videoS->id)->update([
                'comments' => $videoS->comments + 1
            ]);
            // 一级评论中数量 求和即可 加上一级评论本身
            // $num = VideoComment::where('relation_id',$id)->count() + VideoComment::where('relation_id',$id)->sum('child_count');
            // $this::where('id',$id)->update([
            //     'comments' => $num
            // ]);
 */
            return_ajax(200,'评论已回复');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 后台添加二级评论
     */
    public function addComment($data){
        try{
            if(empty($data['fid']))
                exception('请选择你要评论的一级评论内容');
            if(empty($data['content']))
                exception('请输入回复内容');
            if(empty($data['uid']))
                exception('请选择评论用户');
            if(empty($data['upid']))
                exception('选择想要评论的用户');
            $this::startTrans();
            $this->allowField(true)->save([
                'uid'=>$data['uid'],
                'upid'=>$data['upid'],
                'fid'=>$data['fid'],
                'content'=>htmlspecialchars($data['content']),
                'add_time'=>time()
            ]);
            (new VideoComment())->where('id', $data['fid'])->inc('child_count')->update();
            $videComments = VideoComment::find($data['fid']);
            (new Video())->where('id', $videComments['relation_id'])->inc('comments')->update();
            $this::commit();
            return_ajax(200,'评论成功');
        }catch (\Exception $e){
            $this::startTrans();
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 获取二级评论用户列表
     */
    public function getCommentUser($fid,$uid){
        try{
            if(empty($fid))
                exception('请选择你要评论的一级评论内容');
            if(empty($uid))
                exception('请选择你要评论的一级评论内容');
            $comchild = $this->alias('c')->join('user u','c.uid = u.id','left')->where('c.fid', $fid)->field('u.id,u.nickname')->buildSql();
            $comuser = Db::name('user')->where('id',$uid)->field('id,nickname')->union([$comchild])->buildSql();
            $list = Db::table($comuser)->alias('union')->order('id desc')->select();
            return $list;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
    }
}