<?php
/**
 * Created by.
 * @author zengye
 * @since 20220108 2:14
 */

namespace app\common\vendor;

/**
 * 图片参数 携带
 * Class ImageSize
 * @doc https://cloud.tencent.com/document/product/460/36540
 * @package app\common\vendor
 * @author zengye
 * @since 20220108 2:14
 */
class ImageSize
{
    const DEFAULT_WIDTH_HEIGHT_750X300 = '750X300'; // 默认的宽高 750 * 300 宽高等比缩放
    const DEFAULT_WIDTH_HEIGHT_750_AUTO = '750x'; // 默认的宽高 750  高等比缩放
    const DEFAULT_ADMIN_WIDTH_HEIGHT_300_AUTO = '300x'; // 默认的宽高 300 高等比缩放
    const DEFAULT_ADMIN_WIDTH_HEIGHT_250_AUTO = '250x'; // 默认的宽高 250 高等比缩放
    const DEFAULT_ADMIN_WIDTH_HEIGHT_200_AUTO = '200x'; // 默认的宽高 200 高等比缩放

    const COS_PREFIX_URL = '?imageMogr2';// 图像处理的前缀
    const COS_THUMBNAIL = '/thumbnail/'; // 缩放处理

    /**
     * 获取到静态资源的域名
     * @param string $url 路径- 域名
     * @param string $host 拼接的路径或域名
     * @return string
     * @author zengye
     * @since 20220108 2:17
     */
    public static function getStaticHostUrl($url = '', $host = '')
    {
        $url = trim($url);
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        $preFixUrl = $host ?: config('static_url');
        $url = $preFixUrl . $url;
        return $url;
    }

    /**
     * cos缩放样式拼接
     * @param string $url
     * @param string $type
     * @param string $host
     * @return string
     * @author zengye
     * @since 20220109 0:56
     */
    public static function cosThumbnail($url = '', $type = self::DEFAULT_ADMIN_WIDTH_HEIGHT_200_AUTO, $host = '')
    {
        if (empty($url)) {
            return $url;
        }
        $url = self::getStaticHostUrl($url, $host);
        $cosUrl = $url . self::COS_PREFIX_URL . self::COS_THUMBNAIL . $type;
        return $cosUrl;
    }
}