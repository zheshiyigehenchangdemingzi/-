<?php
namespace app\common\model;
use \think\Model;

Class Base extends Model
{
    # 声明变量  可以使用 ->toArray();  进行转换为数组
    protected $resultSetType = 'collection';

    const STATUS_ACTIVE = 1; // 启用
    const STATUS_DEACTIVE = 2; // 停用
    const STATUS_DETELE = 0; // 删除

}