<?php

namespace app\admin\controller;

use app\common\model\VideoComment;
use app\common\model\VideoCommentChild;
use app\common\model\User;
use app\common\model\UserInfo;
use app\common\model\Video;
use app\common\model\VideoType;
use app\common\model\Config;

class VideoHot extends Common
{
    // 视频列表
    public function index()
    {
        $model = new Video();
        $mod = new VideoType();
        $get = input('get.');
        if (!isset($get['status']))
            $get['status'] = '';
        if (!isset($get['is_hot']))
            $get['is_hot'] = '';
        if (empty($get['type_id']))
            $get['type_id'] = '';
        if (!isset($get['is_check'])) $get['is_check'] = '';
        if (!isset($get['limit']))
            $get['limit'] = 20;
        if ($get['limit'] > 200)
            $get['limit'] = 200;
        $is_checks = [
            '待审核',
            '审核通过',
            '审核拒绝'
        ];
        return $this->fetch('', [
            'list' => $model->getList($get),
            'count' => $model->count,
            'page' => $model->page,
            'get' => $get,
            'type' => $mod->getTypeColumn(),
            'is_checks' => $is_checks
        ]);
    }

    public function edit()
    {
        $model = new Video();
        if (request()->isAjax() && request()->isPost()) {
            $this->checkAuth(92);
            $post = input('post.');
            $model->addVideo($post, $_FILES);
        } else {
            $mod = new VideoType();
            $id = input('get.id');
            $data = ['type_id' => 0, 'status' => 1, 'is_hot' => 0, 'type' => 1];
            if ($id)
                $data = $model->getOne($id);
            $data['add_time'] = date('Y-m-d', isset($data['add_time']) ? $data['add_time'] : time());
            $users = (new User())->getUsers();
            shuffle($users);
            return $this->fetch('', ['type' => $mod->getTypeColumn(), 'data' => $data, 'users' => $users, 'count' => count($users)]);
        }
    }

    public function status()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(88);
            $post = input('post.');
            $model = new Video();
            $model->editStatus($post);
        }
    }

    // 排序值  需要编辑排序值
    public function sortEdit()
    {
        $get = input('get.');
        if (!isset($get['id']) || $get['id'] < 0)
            return return_ajax(400, '错误记录值');
        if (!isset($get['sort']) || $get['sort'] < 0)
            return return_ajax(400, '修改值错误');
        Video::where('id', $get['id'])->update([
            'sort' => $get['sort']
        ]);
        return return_ajax(200, '操作ok', ['id' => $get['id']]);
    }

    //评论详情
    public function comment()
    {
        $model = new VideoComment();
        $get = input('get.');
        $get['type'] = 2;
        return $this->fetch('', [
            'list' => $model->getList($get),
            'count' => $model->count,
            'page' => $model->page,
            'get' => $get
        ]);
    }

    //视频评论删除
    public function comment_del()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(90);
            $post = input('post.');
            $model = new VideoComment();
            $model->commentDel($post['id']);
        }
    }

    //视频评论批量-删除，删除下级评论
    public function comment_pl_del()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(90);
            $post = input('post.');
            $model = new VideoComment();
            $model->commentAllDel($post['id']);
        }
    }

    //评论回复
    public function reply()
    {
        $model = new VideoComment();
        if (request()->isAjax() && request()->isPost()) {
            $this->checkAuth(91);
            $post = input('post.');
            $model->addReply($post);
        } else {
            return $this->fetch('', ['id' => input('get.id')]);
        }
    }

    /*
     * 后台添加评论
     */
    public function add_comment()
    {
        $model = new VideoComment();
        if (request()->isAjax() && request()->isPost()) {
            $this->checkAuth(91);
            $post = input('post.');
            if (!empty($post['fid']))
                $model = new VideoCommentChild();
            $model->addComment($post);
        } else {
            $id = input('get.id');
            $fid = input('get.fid');
            $uid = input('get.uid');
            $upid = input('get.upid');
            $childUser = [];
            if (empty($upid) && !empty($fid) && !empty($uid))
                $childUser = (new VideoCommentChild())->getCommentUser($fid, $uid);
            $mod = new User();
            return $this->fetch('', ['id' => $id, 'fid' => $fid, 'upid' => $upid, 'users' => $mod->getFictitious(), 'child_user' => $childUser]);
        }
    }

    /**
     * -----------------   二级评论开始 start -----------------------
     * comment_child
     */

    // 二级评论列表
    public function comment_child()
    {
        $model = new VideoCommentChild();
        $get = input('get.');
        return $this->fetch('', [
            'list' => $model->getList($get),
            'count' => $model->count,
            'page' => $model->page,
            'get' => $get,
        ]);
    }

    //二级评论回复
    public function reply_child()
    {
        $model = new VideoCommentChild();
        if (request()->isAjax() && request()->isPost()) {
            $this->checkAuth(90);
            $post = input('post.');
            $model->addReply($post);
        } else {
            return $this->fetch('', ['id' => input('get.id')]);
        }
    }

    //二级视频评论删除
    public function comment_child_del()
    {
        $post = input('post.');
        if (!isset($post['id']))
            return_ajax(400, '请选择对应列表');
        if (request()->isPost() && request()->isAjax()) {
            //$this->checkAuth(90);
            $model = new VideoCommentChild();
            $modelFlag = $model->find((int)$post['id'][0]);
            $count = VideoCommentChild::where('fid', $modelFlag->fid)->count();
            if (!$modelFlag)
                return return_ajax(400, '没有该记录');
            VideoComment::where('id', $modelFlag->fid)->update([
                'child_count' => $count > 0 ? $count - 1 : 0
            ]);
            $videoCommentModel = VideoComment::where('id', $modelFlag->fid)->find();
            (new Video())->where('id', $videoCommentModel['relation_id'])->dec('comments')->update();
            $s = $modelFlag->delete();
            return return_ajax($s > 0 ? 200 : 400, $s ? '删除成功' : '不存在或者网络问题，麻烦重新尝试');
        }
    }

    /**
     * -----------------   二级评论开始 end -----------------------
     * comment_child 二级评论结束
     */

    //视频删除
    public function video_del()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(89);
            $post = input('post.');
            $model = new Video();
            $model->videoDel($post['id']);
        }
    }

    //分类
    public function type()
    {
        $model = new VideoType();
        return $this->fetch('', ['list' => $model->getList(), 'count' => $model->count]);
    }

    //分类编辑
    public function type_edit()
    {
        $model = new VideoType();
        if (request()->isAjax() && request()->isPost()) {
            $this->checkAuth(86);
            $post = input('post.');
            $model->addType($post);
        } else {
            $id = input('get.id');
            $data = ['pid' => ''];
            if ($id) {
                $data = $model->getOne($id);
            }
            return $this->fetch('', ['type' => $model->getTypeColumn(), 'data' => $data]);
        }
    }

    //分类删除
    public function type_del()
    {
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(87);
            $post = input('post.');
            $model = new VideoType();
            $model->typeDel($post['id']);
        }
    }

    /**
     * 视频设置-配置信息表
     */
    public function edit_setting()
    {
        $model = new Config();
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(98);
            $post = input('post.');
            $model->toSave($post);
        } else {
            $config = $model->toData('video');
            return $this->fetch('', ['config' => $config]);
        }
    }

    /**
     * 视频更改状态
     */
    public function video_list_status()
    {
        $post = input('post.');
        if (!isset($post['id']) || $post['id'] <= 0) {
            return_ajax(400, '请选择对应列表');
        }
        if (!isset($post['key']) || !$post['key']) return_ajax(400, '错误键');
        if (!in_array($post['key'], ['status', 'is_hot'])) return_ajax(400, '类型错误');
        if (!isset($post['val'])) return_ajax(400, '错误值');
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(115);
            $modelFlag = Video::where('id', $post['id'])->update([
                $post['key'] => $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400, $modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试', $post);
        }
    }

    public function video_list_status_pl()
    {
        $post = input('post.');
        if (!isset($post['ids']) || !is_array($post['ids']) || count($post['ids']) == 0) {
            return_ajax(400, '请选择对应列表');
        }
        if (!isset($post['key']) || !$post['key']) return_ajax(400, '错误键');
        if (!in_array($post['key'], ['status', 'is_hot'])) return_ajax(400, '类型错误');
        if (!isset($post['val'])) return_ajax(400, '错误值');
        if (request()->isPost() && request()->isAjax()) {
            $this->checkAuth(115);
            // 如果为状态  且 不等于 指定状态
            #if($post['key'] == 'status' && !in_array($post['val'],[1,2])) return return_ajax(400,'状态错误');
            $modelFlag = Video::whereIn('id', $post['ids'])->update([
                $post['key'] => $post['val']
            ]);
            return return_ajax($modelFlag > 0 ? 200 : 400, $modelFlag ? '更新成功' : '不存在或者网络问题，麻烦重新尝试', $post);
        }
    }

    /**
     * 视频评论列表
     */
    public function comment_list()
    {
        $model = new VideoComment();
        $get = input('get.');
        if (!isset($get['limit'])) $get['limit'] = 20;
        if (!isset($get['is_reply'])) $get['is_reply'] = '';
        if (!isset($get['type_id'])) $get['type_id'] = '';
        return $this->fetch('', [
            'list' => $model->getListnew($get),
            'count' => $model->count,
            'page' => $model->page,
            'get' => $get,
            'type' => (new VideoType())->getTypeColumn()
        ]);
    }

    /**
     * 批量添加一级评论
     */
    public function comment_add_text_pl()
    {
        if (request()->isPost() && request()->isAjax()) {
            $post = input('post.');
            if (!$this->check_input($post))
                return_ajax(400, '回复内容含有非法字符');
            $model = new VideoComment();
            $model->addCommentPlText($post, true);
        } else {
            $data = [];
            $users = (new User())->getUsers();
            shuffle($users);
            return $this->fetch('', [
                'data' => $data,
                'users' => $users,
                'count' => count($users),
                'type' => (new VideoType())->getTypeColumn()
            ]);
        }
    }

    /**
     * 获取视频列表信息
     */
    public function getVideos()
    {
        $get = input('get.');
        $type_id = isset($get['type_id']) ? $get['type_id'] : -1;
        $video_title = isset($get['video_title']) ? $get['video_title'] : false;
        $video_id = isset($get['video_id']) ? $get['video_id'] : -1;
        $where = ['is_delete' => 0, 'status' => 1, 'is_check' => 1];
        if ($video_title && strlen($video_title) >= 1) $where['title'] = ['like', '%' . $video_title . '%'];
        if ($type_id > 0) $where['type_id'] = $type_id;
        if ($video_id > 0) $where['id'] = $video_id;
        $list = Video::where($where)->field('id,title,cover,video')->select();
        return return_ajax(200, '操作ok', ['data' => $list]);
    }

    /**
     * 视频评论 -  快捷审核
     */
    public function videoCheck()
    {
        if (request()->isPost() && request()->isAjax()) {
            $get = input('post.');
            if (!isset($get['id']) || $get['id'] <= 0) return return_ajax(400, '序号不得为空');
            $this->checkAuth(138);
            $model = new Video();
            if (!$model->check($get['id'])) return return_ajax(400, '审核通过失败，请重新尝试');
            return return_ajax(200, '审核成功');
        }
    }

    /**
     * 视频评论 -  快捷审核 - 批量审核
     */
    public function videoCheckAll()
    {
        if (request()->isPost() && request()->isAjax()) {
            $get = input('post.');
            if (!isset($get['ids']) || !is_array($get['ids']) || count($get['ids']) <= 0) return return_ajax(400, '序号不得为空');
            if (count($get['ids']) > 50) return return_ajax(400, '最多不得批量50个');
            $this->checkAuth(139);
            $model = new Video();
            $limit = 0;
            foreach ($get['ids'] as $id) if ($model->check($id)) $limit++;
            if ($limit <= 0) return return_ajax(400, '批量审核失败，麻烦重新操作');
            return return_ajax(200, '批量审核成功-' . $limit);
        }
    }
}