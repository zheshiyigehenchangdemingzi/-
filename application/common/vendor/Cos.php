<?php
/**
 * Created by.
 * @author zengye
 * @since 20220108 1:01
 */

namespace app\common\vendor;

use think\Config;

/**
 * cos 腾讯cos对象操作
 * Class Cos
 * @package app\common\vendor
 * @author zengye
 * @since 20220108 1:01
 */
class Cos
{
    public $cosClient = null; // cos对象
    public $cosConfig = []; // 配置文件

    /**
     * 初始化 构造函数
     * Cos constructor.
     * @param array $config 配置文件信息
     * @param string $prefix_url 默认的前缀
     */
    public function __construct(array $config = [], $prefix_url = '')
    {
        if (empty($config)) {
            $config = Config::get('cos');
        }

        // 初始化cos对象
        $this->cosConfig = [
            'secretId' => $config['secretId'], //"云 API 密钥 SecretId";
            'secretKey' => $config['secretKey'], //"云 API 密钥 SecretKey";
            'region' => $config['region'], //设置一个默认的存储桶地域
            'bucket' => $config['bucket'], //存储桶名称 格式：BucketName-APPID
            'prefix_url' => $prefix_url ?: $config['prefix_url'],
        ];
        // 创建存储对象
        $this->cosClient = new \Qcloud\Cos\Client([
                'region' => $this->cosConfig['region'],
                'credentials' => ['secretId' => $this->cosConfig['secretId'], 'secretKey' => $this->cosConfig['secretKey']]]
        );
    }

    /**
     * 上传文件
     * @param $filestr 文件内容
     * @param string $key 键值
     * @param string $bucket 存储桶
     * @return bool
     * @author zengye
     * @since 20220108 1:12
     */
    public function putObject($filestr = '', $key = '', $bucket = '')
    {
        if (empty($filestr) || empty($key)) {
            return false;
        }
        $result = $this->cosClient->putObject([
            "Bucket" => $bucket ?: $this->cosConfig['bucket'],
            "Key" => $key,
            "Body" => $filestr
        ]);
        if (!$result) {
            return false;
        }
        return $result;
    }
}