<?php
/**
 * Created by PhpStorm.
 * User: snuz
 * Date: 2020/6/30
 * Time: 16:13
 */

namespace app\admin\controller;

use app\common\vendor\Cos;
use QCloud\COSSTS\Sts;
use think\Config;


class Uploads extends Common
{
    const UPLOADS_TYPES_IMAGE = 'image';
    const UPLOADS_TYPES_VIDEO = 'video';

    // 批量上传文件 -- 结合 cos
    public function cosFilesPl()
    {
        $file = request()->file('image');
        $file_prefix = request()->post('prefix');
        if (!$file || !is_array($file))
            $file = request()->file('file');
        if (empty($file))
            return return_ajax(400, '请选择文件');
        // 类型验证
        $types = [
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        $return_datas = [];
        $fileDatas = [];
        $time = time();

        // 初始化cos对象  并且上传中
        $cosConfig = [
            'secretId' => "AKIDsWrvjH96h38xXqrf290d079uPyINatBz", //"云 API 密钥 SecretId";
            'secretKey' => "hfKkvpTJyNWiOoxoOtGNoLkupxucBF3b", //"云 API 密钥 SecretKey";
            'region' => "ap-guangzhou", //设置一个默认的存储桶地域
            'bucket' => "admin-miaomeimei-1301812909", //存储桶名称 格式：BucketName-APPID
            'prefix_url' => "https://admin-miaomeimei-1301812909.cos.ap-guangzhou.myqcloud.com"
        ];
        $secretId = $cosConfig['secretId']; //"云 API 密钥 SecretId";
        $secretKey = $cosConfig['secretKey']; //"云 API 密钥 SecretKey";
        $region = $cosConfig['region']; //设置一个默认的存储桶地域
        # 创建存储对象
        $cosClient = new \Qcloud\Cos\Client([
                'region' => $region,
                'credentials' => ['secretId' => $secretId, 'secretKey' => $secretKey]]
        );

        foreach ($file as $k => $v) {
            $fileDatas[$k] = $v->getInfo(1);
            if (!in_array($fileDatas[$k]['type'], $types))
                return return_ajax(400, '文件' . ($k + 1) . ',中类型不正确');
            if ($fileDatas[$k]['size'] > 1024 * 1024 * 2)
                return return_ajax(400, '文件' . ($k + 1) . '超过2mb');
            $s = $v->getInfo();
            $n = explode('.', $s['name']);
            if (!$n || count($n) == 1) return return_ajax(400, '文件' . ($k + 1) . '没有后缀名');
            $filestr = fopen($s['tmp_name'], 'rb');
            if (!$filestr) return return_ajax(400, '读取文件错误');
            $day = date('Y_m_d');
            ### 上传文件流
            try {
                $bucket = $cosConfig['bucket']; //存储桶名称 格式：BucketName-APPID
                if ($filestr) {
                    $result = $cosClient->putObject([
                        "Bucket" => $bucket,
                        "Key" => 'newUploads/' . ($file_prefix && strlen($file_prefix) > 0 ? $file_prefix . '/' : '') . $day . '/' . time() . '_' . rand(100, 1000) . '.' . $n[1],
                        "Body" => $filestr
                    ]);
                    if ($result) {
                        $return_datas[] = [
                            'url' => 'https://' . $result['Location'],
                            'type' => $fileDatas[$k]['type'],
                            'name' => $fileDatas[$k]['name'],
                            'size' => $fileDatas[$k]['size']
                        ];
                        // return return_ajax(200,'上传ok',[
                        //     'url' => 'https://'.$result['Location']
                        // ]);
                    } else {
                        return return_ajax(400, '文件' . ($k + 1) . '没有数据');
                    }
                }
            } catch (\Exception $e) {
                return return_ajax(400, '错误');
            }
            /*    
            $info = $v->move(ROOT_PATH.'public/newUploads'.($file_prefix ? '/'.$file_prefix : ''),$time."_".md5($k + rand(1000,33333).'_'.rand(50,10000)));
            if($info){
                $return_datas[] = [
                    'url' => '/newUploads'.'/'.($file_prefix ? $file_prefix.'/' : '').$info->getSaveName(),
                    'type' => $fileDatas[$k]['type'],
                    'name' => $fileDatas[$k]['name'],
                    'size' => $fileDatas[$k]['size']
                ];
            }
            */
        }
        if ($return_datas && is_array($return_datas) && count($return_datas) > 0) return return_ajax(200, '上传成功', $return_datas);
        else return return_ajax(400, '失败请联系管理员或者开发人员');
    }

    // cos单文件上传操作
    public function cosFiles()
    {
        if (!\think\Config::get('cos_open')) {
            return $this->uploadImg();
        }

        $file = request()->file('image');
        $file_prefix = request()->post('prefix');
        $file_type = request()->post('type',self::UPLOADS_TYPES_IMAGE);
        if (!$file)
            $file = request()->file('file');
        if (!$file) return return_ajax(400, '文件不存在');
        $s = $file->getInfo();
        if (!$s) return return_ajax(400, '文件错误');
        $n = explode('.', $s['name']);
        if (!$n || count($n) == 1) return return_ajax(400, '没有后缀名');
        // 初始化cos对象
        $cos = new Cos(\config('cos'));
        $filestr = fopen($s['tmp_name'], 'rb');
        if (!$filestr) return return_ajax(400, '读取文件错误');
        $day = date('Y_m_d');
        // 上传文件流
        try {
            $key = 'newUploads/' . ($file_prefix && strlen($file_prefix) > 0 ? $file_prefix . '/' : '') . $day . '/' . time() . '_' . rand(1, 100000) . '.' . $n[1];
            $result = $cos->putObject($filestr, $key);
            if (!$result) {
                return return_ajax(400, '没有数据');
            }

            // 视频类型
            $prefix_url = '';
            if($file_type == self::UPLOADS_TYPES_VIDEO) {
                $prefix_url = Config::get('static_url');
            }

            return return_ajax(200, '上传ok', [
                'url' => $prefix_url. '/' . $result['Key'],
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return return_ajax(400, '错误');
        }
    }

    public function uploadImg()
    {
        $file = request()->file('image');
        $file_prefix = request()->post('prefix');
        if (!$file)
            $file = request()->file('file');
        if (empty($file))
            return return_ajax(400, '请选择文件');
        // 类型验证
        $types = [
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        $fileData = $file->getInfo(1);
        if (!in_array($fileData['type'], $types))
            return return_ajax(400, '类型不正确');
        if ($fileData['size'] > 1024 * 1024 * 2)
            return return_ajax(400, '文件超过2mb');
        $info = $file->move(ROOT_PATH . 'public/newUploads' . ($file_prefix ? '/' . $file_prefix : ''));
        if ($info) {
            $fileName = $info->getSaveName();
            $url = '/newUploads' . ($file_prefix ? '/' . $file_prefix : '') . '/' . $fileName;
            $url = str_replace('\\', '/', $url);
            return return_ajax(200, '加载成功', [
                'url' => $url,
                'prefix' => $file_prefix,
                'fileName' => 'newUploads'
            ]);
        } else {
            return return_ajax(400, '失败请联系管理员或者开发人员');
        }
    }

    // 批量上传文件 
    public function uploadsImgs()
    {
        $file = request()->file('image');
        $file_prefix = request()->post('prefix');
        if (!$file || !is_array($file))
            $file = request()->file('file');
        if (empty($file))
            return return_ajax(400, '请选择文件');
        // 类型验证
        $types = [
            'image/jpg',
            'image/jpeg',
            'image/png',
            'image/gif',
        ];
        $return_datas = [];
        $fileDatas = [];
        $time = time();
        foreach ($file as $k => $v) {
            $fileDatas[$k] = $v->getInfo(1);
            if (!in_array($fileDatas[$k]['type'], $types))
                return return_ajax(400, '文件' . ($k + 1) . ',中类型不正确');
            if ($fileDatas[$k]['size'] > 1024 * 1024 * 2)
                return return_ajax(400, '文件' . ($k + 1) . '超过2mb');
            $info = $v->move(ROOT_PATH . 'public/newUploads' . ($file_prefix ? '/' . $file_prefix : ''), $time . "_" . md5($k + rand(1000, 33333) . '_' . rand(50, 10000)));
            if ($info) {
                $return_datas[] = [
                    'url' => '/newUploads' . '/' . ($file_prefix ? $file_prefix . '/' : '') . $info->getSaveName(),
                    'type' => $fileDatas[$k]['type'],
                    'name' => $fileDatas[$k]['name'],
                    'size' => $fileDatas[$k]['size']
                ];
            }
        }
        if ($return_datas && is_array($return_datas) && count($return_datas) > 0) return return_ajax(200, '上传成功', $return_datas);
        else return return_ajax(400, '失败请联系管理员或者开发人员');
    }

    // 获取到秘钥
    public function apiKey()
    {
        $sts = new Sts();
        $config = [
            'url' => 'https://sts.tencentcloudapi.com/',
            'domain' => 'sts.tencentcloudapi.com',
            'proxy' => '',
            'secretId' => 'AKIDsWrvjH96h38xXqrf290d079uPyINatBz', // 固定密钥
            'secretKey' => 'hfKkvpTJyNWiOoxoOtGNoLkupxucBF3b', // 固定密钥
            'bucket' => 'admin-miaomeimei-1301812909', // 换成你的 bucket
            'region' => 'ap-guangzhou', // 换成 bucket 所在园区
            'durationSeconds' => 1800, // 密钥有效期
            'allowPrefix' => '*', // 这里改成允许的路径前缀，可以根据自己网站的用户登录态判断允许上传的具体路径，例子： a.jpg 或者 a/* 或者 * (使用通配符*存在重大安全风险, 请谨慎评估使用)
            // 密钥的权限列表。简单上传和分片需要以下的权限，其他权限列表请看 https://cloud.tencent.com/document/product/436/31923
            'allowActions' => [
                // 简单上传
                'name/cos:PutObject',
                'name/cos:PostObject',
                // 分片上传
                'name/cos:InitiateMultipartUpload',
                'name/cos:ListMultipartUploads',
                'name/cos:ListParts',
                'name/cos:UploadPart',
                'name/cos:CompleteMultipartUpload'
            ]
        ];
        // 获取临时密钥，计算签名
        $tempKeys = $sts->getTempKeys($config);
        return json($tempKeys);
    }
}