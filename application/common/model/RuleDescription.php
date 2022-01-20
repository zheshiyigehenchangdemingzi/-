<?php
namespace app\common\model;

use think\Request;

Class RuleDescription extends Base
{
    // 拿取数据
    public function getValue()
    {
        $key = Request()->get('key',-1);
        if(!$key || $key < 0)
            return return_ajax(400,'操作失败');
        $one = $this->where("key",$key)->find();
        // 如果类型为1   则转换
        if($one->type == 1)
            $one->val = htmlspecialchars_decode($one->val,ENT_QUOTES);
        return $one;
    }


    // 更改数据
    public function edit()
    {
        $key = Request()->post('key',-1);
        if(!$key || $key < 0)
            return return_ajax(400,'操作失败');
        $val = Request()->post('editorValue',-1);
        if(!$key || $key < 0)
            return return_ajax(400,'操作失败');
        $one = $this->where("key",$key)->find();
        if($one->type == 1)
            $val = htmlspecialchars_decode($val);
        $this::where('key',$key)->update([
            'val' => $val
        ]);
        return return_ajax(200,'数据ok');
    }
}