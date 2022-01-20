<?php
namespace app\common\model;

Class Receipt extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        $where = ['is_delete'=>0];

        $field = 'id,name,tel,address,remarks,is_def,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        return $this->where(['id'=>$id])->field('id,name,tel,address,remarks,is_def')->find();
    }

    //退货地址添加 编辑
    public function addReceipt($data){
        try{
            if(empty($data['name']))
                exception('请填写收货人名称');
            if(empty($data['tel']))
                exception('请填写收货人电话');
            if(empty($data['address']))
                exception('请填写退货地址');

            $save = [
                'name'          =>  $data['name'],
                'tel'           =>  $data['tel'],
                'address'      =>  $data['address'],
                'remarks'      =>  $data['remarks'],
                'is_def'        =>  $data['is_def'] == 1?1:0
            ];

            $map = [
                'name'      =>  $data['name'],
                'tel'       =>  $data['tel'],
                'address'   =>  $data['address'],
                'is_delete' =>  0
            ];

            $msg = '退货地址已添加';
            if(empty($data['id'])){
                if($this->where($map)->find())
                    exception('退货地址已经存在');

                $this->allowField(true)->save($save);
            }else{
                $map['id'] = ['neq',$data['id']];
                if($this->where($map)->find())
                    exception('退货地址已经存在');

                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '退货地址已修改';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //退货地址删除
    public function receiptDel($id){
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

            return_ajax(200,'收货地址已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}