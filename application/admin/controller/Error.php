<?php
namespace app\admin\controller;
use app\common\controller\Base;

Class Error extends Base {

    public function index()
    {
        return $this->fetch('common/error');
    }
}