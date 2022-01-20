<?php
namespace app\common\model;

Class Icon extends Base
{
    //后台菜单图标
    public function getIcon(){
        $icon = $this->where(['is_delete'=>0])->column('id,unicode');
        return $icon;
    }
}