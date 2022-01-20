<?php

define('COS_PREFIX', 'https://admin-miaomeimei-1301812909.cos.ap-guangzhou.myqcloud.com');

/*
 * 获取随机数
 * $len 随机数长度
 * */
function getCode($len = 32)
{
    $string = 'QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm0147852369';//随机字符串
    $strLen = strlen($string);//字符串长度
    $code = '';//储存随机值
    for ($i = 0; $i < $len; $i++) {
        $position = mt_rand(0, $strLen - 1);//字符串节点
        $code .= substr($string, $position, 1);
    }
    return $code;
}

/**
 * [imgUpdate TP5文件上传]
 * @Author   PengJun
 * @DateTime 2018-05-10
 * @param    [type]     $fileName [文件变量名]
 * @param    [type]     $fileUrl  [文件路径]
 * @return   [type]               [description]
 */
function imgUpdate($fileName, $fileUrl = '')
{
    $fileUrl ? $fileUrl . '/' . date('Ymd', time()) : date('Ymd', time());
    // 获取表单上传文件
    $files = request()->file($fileName);
    if (is_array($files)) { //多图上传
        foreach ($files as $key => $file) {
            //进行大小，文件后缀验证，通过移动到public/uploads/pet 目录下
            $info = $file->validate(['size' => 1073741824, 'ext' => 'jpeg,jpg,png,gif,mp4,zip,rar,mp3'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $fileUrl);
            if ($info) {
                //获取文件名称 并将路径中的 \ 替换成 /
                $data[$key] = '/uploads/' . $fileUrl . '/' . str_replace("\\", '/', $info->getSaveName());
            } else {
                $str = '第' . ($key + 1) . '个文件上传失败,失败原因:' . $file->getError();
                return ['code' => 400, 'msg' => $str];
            }
        }
    } else { //单图上传
        //进行大小，文件后缀验证，通过移动到public/uploads/pet 目录下
        $info = $files->validate(['size' => 1073741824, 'ext' => 'jpeg,jpg,png,gif,mp4,zip,rar,mp3'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $fileUrl);
        if ($info) {
            //获取文件名称 并将路径中的 \ 替换成 /
            $data = '/uploads/' . $fileUrl . '/' . str_replace("\\", '/', $info->getSaveName());
        } else {
            $str = '文件上传失败,失败原因:' . $files->getError();
            return ['code' => 400, 'msg' => $str];
        }
    }
    return ['code' => 200, 'msg' => '上传成功', 'data' => $data];
}

//视频上传
function videoUpdate($fileName, $fileUrl = '')
{
    $fileUrl ? $fileUrl . '/' . date('Ymd', time()) : date('Ymd', time());
    // 获取表单上传文件
    $files = request()->file($fileName);
    //进行大小，文件后缀验证，通过移动到public/uploads/pet 目录下
    $info = $files->validate(['size' => 1073741824, 'ext' => 'mp4,rmvb,avi'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . $fileUrl);
    if ($info) {
        //获取文件名称 并将路径中的 \ 替换成 /
        $data = '/uploads/' . $fileUrl . '/' . str_replace("\\", '/', $info->getSaveName());
    } else {
        $str = '文件上传失败,失败原因:' . $files->getError();
        return ['code' => 400, 'msg' => $str];
    }
    return ['code' => 200, 'msg' => '上传成功', 'data' => $data];
}

/*
 * 获取加密后的密码
 * $salt    随机盐值
 * $pwd     明文密码
 * $pubkey  公共key
 * */
function getPassword($salt, $pwd, $pubkey = 'jwei')
{
    $password = md5($pwd);//md5加密明文密码
    $password = md5($salt . substr($password, 0, 17) . $pubkey);//盐值与公共key拼接到密码上，再次md5加密
    $password = strtoupper($password);
    return $password;
}

/*
 * 返回信息组装
 * $code 状态码
 * $info 提示内容
 * $data 返回的数据
 * */
function return_ajax($code = 200, $info = '操作成功', $data = [])
{
    exit(json_encode(['code' => $code, 'msg' => $info, 'data' => $data]));
}

/**
 * 使用curl get获取远程数据
 * $url     url连接
 * $header  请求头部信息
 */
function curlGet($url, $header = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);                                 //设置访问的url地址
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);                        //设置超时
    curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);   //用户访问代理 User-Agent
    curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_HOST']);           //设置 referer
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);                //跟踪301
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);                //返回结果
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);            //不验证证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);            //不验证证书
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

/**
 * 使用curl post获取远程数据
 * $url     请求URL
 * $params  请求参数
 * $header  请求头部信息
 */
function curlPost($url, $params = '', $header = [])
{
    //$postFields = http_build_query($postFields);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //不验证证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); //不验证证书
    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

/**
 * Emoji原形转换为String
 * $content 需要转换的字符
 */
function emojiEncode($content)
{
    return json_decode(preg_replace_callback("/(\\\u[ed][0-9a-f]{3})/i", function ($str) {
        return addslashes($str[0]);
    }, json_encode($content)));
}

/**
 * Emoji字符串转换为原形
 * $content 需要转换的字符
 */
function emojiDecode($content)
{
    return json_decode(preg_replace_callback('/\\\\\\\\/i', function () {
        return '\\';
    }, json_encode($content)));
}

//主动判断是否HTTPS
function isHTTPS()
{
    if (defined('HTTPS') && HTTPS) return true;
    if (!isset($_SERVER)) return FALSE;
    if (!isset($_SERVER['HTTPS'])) return FALSE;
    if ($_SERVER['HTTPS'] === 1) {  //Apache
        return TRUE;
    } elseif ($_SERVER['HTTPS'] === 'on') { //IIS
        return TRUE;
    } elseif ($_SERVER['SERVER_PORT'] == 443) { //其他
        return TRUE;
    }
    return FALSE;
}

/**
 * 隐藏用户名 使用星号 匹配
 */
function substr_cut($user_name, $limit = 2)
{
    $strlen = mb_strlen($user_name, 'utf-8');
    $firstStr = mb_substr($user_name, 0, 1, 'utf-8');
    $lastStr = mb_substr($user_name, -1, 1, 'utf-8');
    if ($strlen < 2) {
        return $user_name;
    } else {
        return $strlen == 2 ? $firstStr . str_repeat('*', mb_strlen($user_name, 'utf-8') - 1) : $firstStr . '**' . $lastStr;
    }
    // str_repeat("*", $strlen - 2)
}

/**
 * 目前只适用于素材上传图片
 * @param $url 请求的地址
 * @param array $data 传递的数据
 * @param bool $json_encode 是否需要json化
 * @return bool|mixed|string 返回的数据
 */
function curlFormPost($url, $data = [], $json_encode = true)
{
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    if ($data != null) {
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        // curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        if (gettype($data) === "string") {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        } else {
            if ($json_encode) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
            } else {
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            }
        }
    }
    curl_setopt($curl, CURLOPT_TIMEOUT, 300); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $res = curl_exec($curl); // 执行操作
    curl_close($curl);

    $data = json_decode($res, true);
    if ($data == NULL) {
        return $res;
    } else {
        return $data;
    }
}

/**
 * 生成订单号  leng 20
 * @param string $prefix 前缀
 * @return string $order_id
 */
function getNumber($prefix = '')
{
    @date_default_timezone_set("PRC");
    //订单号码主体（YYYYMMDDHHIISSNNNNNNNN）
    $order_id_main = date('ymdHis') . rand(10000000, 99999999);
    return $prefix . $order_id_main;
    //订单号码主体长度
    $order_id_len = strlen($order_id_main);
    $order_id_sum = 0;
    for ($i = 0; $i < $order_id_len; $i++) {
        $order_id_sum += (int)(substr($order_id_main, $i, 1));
    }
    //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
    $order_id = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
    return $order_id;
}

/**
 * 写入文件日志
 */
function fileLog($content, $name)
{
    $filename = ROOT_PATH . 'pay_log/' . $name;
    $content = $content . "\r\n";
    // 文件不存在则创建
    if (!file_exists($filename)) {
        $fh = fopen($filename, 'w');
    } else {
        $fh = fopen($filename, 'a');
    }
    if (!$fh) {
        //echo "不能打开文件 $filename";
        return false;
    }
    // 写入内容
    if (fwrite($fh, $content) === FALSE) {
        //echo "不能写入到文件 $filename";
        return false;
    }
    //echo "成功地将 $content 写入到文件 $filename";
    fclose($fh);
    return true;
}

/**
 * 判断是否需要返回http
 * @param $v  图片路劲地址
 * @param string $prefix 前缀拼接地址
 * @return string 拼接好的地址
 */
function prefixUrlImg($v, $prefix = 'https://abc.miaommei.com')
{
    return strpos($v, 'http') !== false ?
        $v : $prefix . $v;
}

/**
 * 判断是否可以操作账号管理
 * @param $account 账号名
 * @return bool 是否可以操作菜单管理
 */
function regAccountMenus($account)
{
    return in_array($account, ['xiaozeng', 'kaifei', '13005689967', 'wxh']);
}