<?php
namespace app\common\model;

Class UserAddress extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数
    //获取列表
    public function getList($get){
        $where = ['uid'=>$get['id']];

        $field = 'province,city,area,address,tel,name,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        if($list){
            $model = new District();
            foreach ($list as &$val){
                $val['province'] = $model->getName($val['province']);
                $val['city'] = $model->getName($val['city']);
                $val['area'] = $model->getName($val['area']);
            }unset($val);
        }
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }
}