<?php

namespace config;
/**
 * 上传文件配置
 * @author zengye
 * @since 20220105 22:13
 * @version 1.0 版本号
 */
return [
    // 腾讯cos的文件上传配置对象
    'cos' => [
        'secretId' => "AKIDsWrvjH96h38xXqrf290d079uPyINatBz", //"云 API 密钥 SecretId";
        'secretKey' => "hfKkvpTJyNWiOoxoOtGNoLkupxucBF3b", //"云 API 密钥 SecretKey";
        'region' => "ap-guangzhou", //设置一个默认的存储桶地域
        'bucket' => "admin-miaomeimei-1301812909", //存储桶名称 格式：BucketName-APPID
        'prefix_url' => "https://admin-miaomeimei-1301812909.cos.ap-guangzhou.myqcloud.com"
    ],
];
