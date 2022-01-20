<?php

namespace app\common\model;

use app\common\vendor\ImageSize;

class Ad extends Base
{
    public $page = '';//分页变量
    public $count = '';//数据总数

    //获取列表
    public function getList($get)
    {
        //初始条件
        $where = ['is_delete' => 0];
        //标题
        if (!empty($get['title']))
            $where['title'] = ['like', '%' . $get['title'] . '%'];
        //小程序跳转地址
        if (!empty($get['url']))
            $where['url'] = ['like', '%' . $get['url'] . '%'];
        //app跳转地址
        if (!empty($get['app_url']))
            $where['app_url'] = ['like', '%' . $get['app_url'] . '%'];
        //显示
        if (strlen($get['show']) >= 1)
            $where['show'] = $get['show'];
        //跳转
        if (strlen($get['is_skip']) >= 1)
            $where['is_skip'] = $get['is_skip'];
        //类型
        if (!empty($get['type_key']))
            $where['type_key'] = $get['type_key'];
        /** @var Ad $list */
        $list = $this->where($where)->order('id desc')
            ->paginate($get['limit'], false, ['query' => $get]);
        foreach ($list as $key => &$item) {
            $item->img_url = ImageSize::cosThumbnail($item->img_url);
        }
        unset($item);
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id)
    {
        return $this->where(['id' => $id])->find();
    }

    //添加广告
    public function addAd($data, $file = [])
    {
        try {
            if (empty($data['name']))
                exception('广告名称不能为空');
            if (empty($data['type_id']) || !is_numeric($data['type_id']))
                exception('请选择广告类型');
            if (empty($data['type']) || !is_numeric($data['type']) || !in_array($data['type'], [1, 2, 3]))
                $data['type'] = 1;
            if (!is_numeric($data['is_skip']) || !in_array($data['is_skip'], [1, 2]))
                $data['is_skip'] = 2;
            if (!is_numeric($data['status']) || !in_array($data['status'], [1, 2]))
                $data['status'] = 2;
            if ($data['type'] == 1) {
                if (empty($data['relation_id']) || !is_numeric($data['relation_id']))
                    exception('商品ID只能输入一个，并且必须是数字');
                $model = new Goods();
                if (!$model->where(['id' => $data['relation_id'], 'is_delete' => 0])->find())
                    exception('此商品ID找不到对应商品');
            } elseif ($data['type'] == 2) {
                if (empty($data['relation_id']) || !is_numeric($data['relation_id']))
                    exception('用户ID只能输入一个，并且必须是数字');
                $model = new User();
                if (!$model->where(['id' => $data['relation_id']])->find())
                    exception('此用户ID找不到对应用户');
            } else {
                if (empty($data['relation_id']) || !is_numeric($data['relation_id']))
                    exception('活动ID只能输入一个，并且必须是数字');
                $model = new Activity();
                if (!$model->where(['id' => $data['relation_id']])->find())
                    exception('此活动ID找不到对应活动');
            }

            $save = [
                'type_id' => $data['type_id'],
                'type' => $data['type'],
                'name' => $data['name'],
                'relation_id' => $data['relation_id'],
                'status' => $data['status'],
                'is_skip' => $data['is_skip'],
                'expires_in' => empty($data['expires_in']) ? 0 : strtotime($data['expires_in']),
            ];

            $map = [
                'status' => 1,
                'type_id' => $data['type_id'],
                'type' => $data['type'],
                'name' => $data['name'],
                'relation_id' => $data['relation_id']
            ];

            $msg = '广告已添加';
            if (empty($data['id'])) {
                if (isset($data['goods_id']) && $data['goods_id'] > 0) {
                    if ($this->where($map)->find())
                        exception('此商品已存在此类型广告');
                }

                if (empty($file['file']['name']))
                    exception('请上传广告图片');
                $res = imgUpdate('file', 'ad');//广告图片上传
                if ($res['code'] == 400)
                    exception($res['msg']);

                $save['cover'] = $res['data'];
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            } else {
                if ($data['goods_id'] > 0) {
                    $map['id'] = ['neq', $data['id']];
                    if ($this->where($map)->find())
                        exception('此商品已存在此类型广告');
                }
                if (!empty($file['file']['name'])) {
                    $res = imgUpdate('file', 'ad');//广告图片上传
                    if ($res['code'] == 400)
                        exception($res['msg']);
                    $save['cover'] = $res['data'];
                }

                $this->allowField(true)->isUpdate(true)->save($save, ['id' => $data['id']]);
                $msg = '广告已修改';
            }
            return_ajax(200, $msg);
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }

    //广告删除
    public function adDel($id)
    {
        try {
            $count = count($id);
            if ($count > 1) {
                foreach ($id as $v) {
                    if (!is_numeric($v))
                        exception('非法操作');
                }

                $this->allowField(true)->isUpdate(true)->save(['is_delete' => 1], ['id' => ['in', $id]]);
            } else {
                if (!is_numeric($id[0]))
                    exception('非法操作');

                $this->allowField(true)->isUpdate(true)->save(['is_delete' => 1], ['id' => $id[0]]);
            }

            return_ajax(200, '广告已删除');
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }

    // 添加
    public function addLogistics($data)
    {
        try {
            if (empty($data['title']))
                exception('请填写标题');
            if (empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            /*
            if (empty($data['show']))
                $data['show'] = 1;
            */
            if (empty($data['img_url']))
                exception('请上传图片');
            // 数据保存
            $save = [
                'type_key' => $data['type_key'],
                'title' => $data['title'],
                'img_url' => $data['img_url'],
                'is_skip' => $data['is_skip'],
                'url' => $data['url'],
                'app_url' => $data['app_url'],
                'sort' => isset($data['sort']) ? $data['sort'] : 0,
                'show' => $data['show']
            ];
            $msg = '添加成功';
            if (empty($data['id'])) {
                if ($this->where(['title' => $data['title'], 'type_key' => $data['type_key']])->find())
                    exception('此列表项已存在');
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            } else {
                if ($this->where(['id' => ['neq', $data['id']], 'title' => $data['title'], 'type_key' => $data['type_key']])->find())
                    exception('此列表项已存在');
                $this->allowField(true)->isUpdate(true)->save($save, ['id' => $data['id']]);
                $msg = '编辑成功';
            }
            return_ajax(200, $msg);
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }
}