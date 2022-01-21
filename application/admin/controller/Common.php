<?php

namespace app\admin\controller;

use app\common\controller\Base;
use app\common\model\Admin;
use app\common\model\Menu;
use app\common\model\Icon;
use app\common\model\Region;
use app\common\model\Role;
use think\Config;
use think\Request;

class Common extends Base
{

    public $AdminInfo;

    public function _initialize()
    {
        //管理员信息
        $AdminInfo = session('admin_admin_info');
        $this->AdminInfo = $AdminInfo;
        if (empty($AdminInfo))
            $this->redirect('Admin/Login/index');
        define('__ADMIN_NAME__', $this->AdminInfo->name);
        define('__ACCOUNT__', $this->AdminInfo->account);
        // 如果大于 0 表示是商家端操作
        if (!$modelAdmin = Admin::where($this->AdminInfo->id)->find()) return return_ajax(400, '此用户不存在');
        if ($modelAdmin->shop_id > 0) return $this->redirect('Admin/Login/index');

        $this->assign('AdminInfo', $AdminInfo);
        $this->uid = $AdminInfo['id'];
        $this->rid = $AdminInfo['role_id'];

        //图标
        $iconModel = new Icon();
        $icon = $iconModel->getIcon();
        $this->assign('icon', $icon);

        // 上传文件配置
        $configUpload = Config::get('upload');
        $this->assign('CONFIG_UPLOAD', $configUpload);

        // 看板娘配置
        $live2dConfig = Config::get('live2d');
        $this->assign('LIVE2D', $live2dConfig);

        // skin 皮肤
        $configSkin = Config::get('skin');
        $this->assign('CONFIG_SKIN', $configSkin);

        // 网站标题
        $configTitle = Config::get('title');
        $this->assign('CONFIG_TITLE', $configTitle);

        //网站菜单
        $menuModel = new Menu();
        $menu = $menuModel->getMenu($AdminInfo['role_id']);
        if (count(explode('/', Request::instance()->path())) >= 3) {
            //admin模块 允许访问的控制器Login、Index、Error、uploads
            if (!in_array(Request::instance()->module() . '/' . Request::instance()->controller(), ['admin/Login', 'admin/Index', 'admin/Error', 'admin/Uploads'])) {
                $path = '/' . Request::instance()->path();
                $menuId = $menuModel->field('id')->where(['url' => $path])->find();
                if ($this->AdminInfo->role_id > 1) $this->newCheckAuth($menuId['id']);#权限校验只对店员
            }
        }
        $this->assign('AdminMenu', $menu);
    }

    public function _empty()
    {
        return $this->fetch('common/error');
    }


    //对象转数组
    protected function object2array($obj)
    {
        $arr = [];
        if (empty($obj))
            return $arr;

        foreach ($obj as $val) {
            $arr[] = $val->toArray();
        }

        return $arr;
    }

    /**
     *  获取到所有控制器对应的 所有方法
     */
    public function getAllClassMethods()
    {

        // 获取到控制器如下
        $controllerArrs = [
            'activitys' => Activitys::class,
            'advert' => Advert::class,
            'finance' => Finance::class,
            'lives' => Lives::class,
            'Orders' => Orders::class,
            'Product' => Product::class,
            'Services' => Services::class,
            'Setting' => Setting::class,
            'Shop' => Shop::class,
            'System' => System::class,
            'Users' => Users::class,
            'Videos' => Videos::class,
            'Volumes' => Volumes::class,
            'Sign' => Sign::class,
            'VideoHot' => VideoHot::class,
        ];
        $allMethons = [];
        $alllist = [];
        foreach ($controllerArrs as $name => $className) {
            if ($className && $name) {
                if ($methods = get_class_methods($className)) {
                    $regMeths = ['getAllClassMethods', "_initialize", "_empty", "object2array", "checkAuth", "newCheckAuth", "check_input", "__construct", "beforeAction", "fetch", "display", "assign", "engine", "validateFailException", "validate", "success", "error", "result", "redirect", "getResponseType"];
                    $name = strtolower($name);
                    $meths = ['/admin/' . $name];
                    foreach ($methods as $item) if (!in_array($item, $regMeths)) $meths[] = '/admin/' . $name . '/' . $item;
                    $alllist = array_merge($alllist, $meths);
                    $allMethons[$name] = $meths;
                }
            }
        }
        return [$allMethons, $alllist];
    }

    /*
     * 检测权限
     * $menuId  权限id
     * */
    protected function checkAuth($menuId)
    {
        return true;
        if ($this->uid != 1) {
            $model = new Role();
            $role = $model->getRole($this->rid);
            $role = explode(',', $role);
            $role = array_values($role);
//            if(!in_array($menuId,$role)) return_ajax(400,'你未拥有此权限,请联系管理员添加权限');
            if (!in_array($menuId, $role)) {
                if (Request::instance()->isPost()) return return_ajax(400, '你未拥有此权限,请联系管理员添加权限');
                else exit('<h1>你未拥有此权限,请联系管理员添加权限!</h1>');
            }
        }
    }

    /*
     * 新版检测权限
     * $menuId  权限id
     * */
    protected function newCheckAuth($menuId)
    {
        if ($this->uid != 1) {
            $model = new Role();
            $role = $model->getRole($this->rid);
            $role = explode(',', $role);
            $role = array_values($role);
//            if(!in_array($menuId,$role)) return_ajax(400,'你未拥有此权限,请联系管理员添加权限');
            if (!in_array($menuId, $role)) {
                if (Request::instance()->isPost()) return return_ajax(400, '你未拥有此权限,请联系管理员添加权限');
                else exit('<h1>你未拥有此权限,请联系管理员添加权限!</h1>');
            }
        }
    }

    /**
     * 获取到省市区
     * @param array $get 参数
     * @return array[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zengye
     * @since 20220111 1:35
     */
    public function getRegionPcdList($get = [])
    {
        $provinceList = (array)Region::getListParentCode(0);
        $cityList = [];
        if (!empty($get['province']) && is_numeric($get['province'])) {
            $cityList = (array)Region::getListParentCode($get['province']);
        }
        $areaList = [];
        if (!empty($get['city']) && is_numeric($get['city'])) {
            $areaList = (array)Region::getListParentCode($get['city']);
        }
        return [$provinceList, $cityList, $areaList];
    }
}