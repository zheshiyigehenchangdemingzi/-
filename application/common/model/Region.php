<?php
namespace app\common\model;

use think\cache\driver\Redis;

/**
 * 省市区 地区模型
 * Class Region
 * @package app\common\model
 * @author zengye
 * @since 20220110 23:22
 */
Class Region extends Base
{
    const REDIS_KEY = 'region_p_code_';
    const REDIS_TIME = 7200;

    /**
     * 获取到省市区  缓存时间 为 7200秒 父级查询子级
     * @param int $pCode 父级id 默认为0
     * @param int $time 过期时间
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zengye
     * @since 20220110 23:20
     */
    public static function getListParentCode($pCode = 0, $time = self::REDIS_TIME){
        $redis = new Redis();
        $key = self::REDIS_KEY . $pCode;
        if(!$time || !$list = $redis->get($key)){
            $list = self::field('code,p_code,name')->where(['p_code' => (int)$pCode])->order('code asc')->column('code,p_code,name','code');
            if($time)  $redis->set($key,$list,$time);
        }
        return $list;
    }

    /**
     * 获取到省市区  缓存时间 为 7200秒 查询code
     * @param int $code code 默认为0
     * @param int $time 过期时间
     * @return bool|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zengye
     * @since 20220110 23:20
     */
    public static function getListInCode($code = 0, $time = self::REDIS_TIME){
        $redis = new Redis();
        $key = self::REDIS_KEY . md5(is_array($code) ? implode($code,'_') : $code);
        if(!$time || !$list = $redis->get($key)){
            $where = ['code' => $code];
            if ( is_array($code)) {
                $where = ['code' => ['in',$code]];
            }
            $list = self::field('code,p_code,name')->where($where)->order('code asc')->column('name','code');
            if($time)  $redis->set($key,$list,$time);
        }
        return $list;
    }

    /**
     * 根据当前的省市 查找出下级
     * @param int $province 省id
     * @param int $city 市id
     * @return array|array[]|bool[]
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zengye
     * @since 20220111 1:02
     */
    public static function getProvinceCity($province = 0, $city = 0) {
        $cityList = [];
        // 查找出 当前省级的下级
        if (!empty($province)) {
            $cityList = Region::getListParentCode($province);
        }

        $areaList = [];
        // 查找出 当前市级的下级
        if (!empty($city)) {
            $areaList = Region::getListParentCode($city);
        }
        return [$cityList,$areaList];
    }

    /**
     * 获取到数组的 code
     * @param array $list 数组
     * @return array
     * @author zengye
     * @since 20220111 1:14
     */
    public static function getDataCodeIds(array $list = []) {
        $regionIds = [];
        if (empty($list)) {
            return $regionIds;
        }
        $regionIds = array_column($list, 'province');
        $regionIds = array_merge($regionIds,array_column($list, 'city'));
        $regionIds = array_merge($regionIds,array_column($list, 'area'));
        $regionIds = array_unique($regionIds);
        return $regionIds;
    }
}