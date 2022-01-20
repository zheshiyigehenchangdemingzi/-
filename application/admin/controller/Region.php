<?php
namespace app\admin\controller;

use app\common\Code;
use app\common\model\Config;
use think\Request;

/**
 * 区域数据
 * Class Region
 * @package app\admin\controller
 * @author zengye
 * @since 20220110 23:34
 */
Class Region extends Common{
    /**
     * 获取到省市区
     * @api /admin/region/index?p_code=0
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zengye
     * @since 20220110 23:41
     */
    public function index() {
        $p_code = $this->request->get('p_code',0);
        $list = \app\common\model\Region::getListParentCode($p_code);
        return $this->result($list,Code::CODE_SUCCESS);
    }
}   