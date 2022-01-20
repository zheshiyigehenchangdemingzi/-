<?php
namespace app\common\model;

Class UserIntegralLog extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取用户积分记录列表
    public function getListForId($get){
        $where = ['uid'=>$get['id']];

        $field = 'type,integral,total_integral,is_sys,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        return $list;
    }
}