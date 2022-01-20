<?php
namespace app\common\model;

Class Role extends Base
{
    public $count = 0;//数据总数
    public $page = '';//数据分页
    /*
     * 获取角色列表
     * $get     请求参数
     * */
    public function getList($get){
        $where = ['is_delete'=>0];
        //角色名称
        if(!empty($get['name']))
            $where['name'] = ['like','%'.$get['name'].'%'];
        $list = $this->where($where)->order('id desc')->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        return $this->where(['id'=>$id])->find();
    }

    /*
     * 获取角色列
     * $id    主键ID
     * */
    public function getRoleColumn(){
        return $this->where(['is_delete'=>0])->column('id,name');
    }

    //获取权限集合
    public function getRole($roleId){
        return $this->where(['id'=>$roleId])->value('sub_menu');
    }

    /*
     * 修改，新增角色
     * $data    角色数据
     * */
    public function addRole($data){
        try{
            if(empty($data['name']))
                exception('菜单名称不能为空');
            if(empty($data['menu']))
                exception('角色权限不允许为空');
            /*if($data['id'] == 1)
                exception('最高权限不可编辑');*/
            $model = new Menu();
            $main_menu = $model->supply_roles($data['menu']);
            $save = [
                'name'      =>  $data['name'],
                'desc'      =>  empty($data['desc'])?'':$data['desc'],
                'main_menu' =>  implode(',',$main_menu),
                'sub_menu'     =>  implode(',',$data['menu'])
            ];
            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],'is_delete'=>0])->find())
                    exception('角色名称已被使用');
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if(!$this->where(['id'=>$data['id'],'is_delete'=>0])->find())
                    exception('角色错误');
                if($this->where(['name'=>$data['name'],'is_delete'=>0,'id'=>['neq',$data['id']]])->find())
                    exception('角色名称已被使用');
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '修改成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 删除角色
     * id    角色id
     * */
    public function RoleDel($id){
        try{
            $count = count($id);
            if($count > 1){
                if(in_array(1,$id))
                    exception('暂无权限');
                foreach ($id as $v){
                    if(!is_numeric($v))
                        exception('非法操作');
                }

                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>['in',$id]]);
            }else{
                if($id[0] == 1)
                    exception('暂无权限');
                if(!is_numeric($id[0]))
                    exception('非法操作');
                $role = $this->getOne($id[0]);
                if(empty($role))
                    return_ajax(400,'角色错误');
                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id[0]]);
            }

            return_ajax(200,'角色已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}