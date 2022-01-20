<?php
namespace app\common\controller;
use think\Controller;

class Base extends Controller
{
    //过滤输入内容
    protected function check_input($data){
        $check = true;
        foreach ($data as $v){
            if (is_array($v))
                $v = implode($v);

            if(preg_match('/^and/i', $v))
                $check = false;

            if(preg_match('/^or/i', $v))
                $check = false;

            if(preg_match('/^select/i', $v))
                $check = false;

            if(preg_match('/^insert/i', $v))
                $check = false;

            if(preg_match('/^create/i', $v))
                $check = false;

            if(preg_match('/^update/i', $v))
                $check = false;

            if(preg_match('/^delete/i', $v))
                $check = false;

            if(preg_match('/^count/i', $v))
                $check = false;

            if(preg_match('/^union/i', $v))
                $check = false;

            if(preg_match('/^into/i', $v))
                $check = false;

            if(preg_match('/^load_file/i', $v))
                $check = false;

            if(preg_match('/^outfile/i', $v))
                $check = false;

            if(preg_match('/^\/\*|\*|\.\.\/|\.\//i', $v))
                $check = false;

            if(preg_match('/<script/i', $v))
                $check = false;
        }

        return $check;
    }
}
