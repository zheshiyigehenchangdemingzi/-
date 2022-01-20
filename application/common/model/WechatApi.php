<?php
namespace app\common\model;

Class WechatApi
{
    protected $appid = 'wxee9ad6e2b6603134';
    protected $mch_id = '1593472881';
    protected $refund_api = 'https://api.mch.weixin.qq.com/secapi/pay/refund';
    protected $transfers_api = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers';
    protected $pay_bank_api = 'https://api.mch.weixin.qq.com/mmpaysptrans/pay_bank';
    protected $key = '2f8368a53bccbf0f2e810a965278a1e1';
    protected $apiclient_cert;
    protected $apiclient_key;
    protected $notify_url = 'https://abc.miaommei.com/api/index/refundLog';
    public $error;

    function __construct() {
        //$this->appid = config('wechat.appid');//你的小程序appid
        // 商户号
        //$this->mch_id = config('wechat.mch_id'); //你的商户号。找不到的在你的小程序里边的微信支付里边找，前提是你必须先开启你的微信支付
        // 支付秘钥
        //$this->key = config('wechat.key'); //这个是你商户号的api秘钥，在产品中心里边找
        //密钥文件
        $this->apiclient_cert = ROOT_PATH.'/cert/apiclient_cert.pem';
        $this->apiclient_key = ROOT_PATH.'/cert/apiclient_key.pem';
        //回调地址
        //$this->notify_url = env('app.host_domin').'://'.env('app.api_static_host').'/api/index/refundLog';
    }

    /*
     * 退款api
     */
    public function refund($data){
        try{
            if(empty($data['out_trade_no'])) exception('订单号不能为空!');
            if(empty($data['out_refund_no'])) exception('退款单号不能为空!');
            if(empty($data['desc'])) $data['desc'] = '客户申请退款';
            if(empty($data['total'])||!is_numeric($data['total'])||$data['total']<=0||$data['total']!=(int)$data['total']) exception('订单总额不能为空!');
            if(empty($data['amount'])||!is_numeric($data['amount'])||$data['amount']<=0||$data['amount']!=(int)$data['amount']) exception('退款金额不能为空!');
            $req = [
                'appid'=> $this->appid,
                'mch_id'=> $this->mch_id,
                'nonce_str'=> $this->createNoncestr(),
                'sign_type'=> 'MD5',
                //'transaction_id'=> 0, //微信订单号
                'out_trade_no'=> $data['out_trade_no'], //商户订单号
                'out_refund_no'=> $data['out_refund_no'], //商户退款单号
                'total_fee'=> $data['total'], //订单金额
                'refund_fee'=> $data['amount'], //退款金额
                'refund_fee_type'=> 'CNY',
                'refund_desc'=> $data['desc'],
                //'refund_account'=> 10, //退款资金来源 仅针对老资金流商户使用
                'notify_url'=> $this->notify_url,
            ];
            $req['sign'] = $this->getSign($req);
            $xml = $this->post($this->refund_api, $this->arrayToXml($req));
            $res = $this->xmlToArray($xml);
            $pay_log = (new PayLog())->insertGetId(['uid'=>0,'oid'=>0,'type'=>2,'orn'=>$data['out_trade_no'],'request'=>json_encode($req),'response'=>json_encode(['res'=>$res,'xml'=>$xml]),'explain'=>'微信退款-金额：'.$data['amount'],'add_time'=>time(),'ip'=>0]);//写入接口调用记录
            if(isset($res['result_code'])&&$res['result_code'] == 'SUCCESS'&&$res['mch_id'] == $this->mch_id){
                return true;
            }
            exception(isset($res['err_code_des']) ? $res['err_code_des'] : ($res['return_msg']??'接口调用失败'));
            return false;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /*
     * 提现到银行卡api
     */
    public function pay_bank($data){
        try{
            if(empty($data['out_trade_no'])) exception('订单号不能为空!');
            if(empty($data['out_refund_no'])) exception('退款单号不能为空!');
            if(empty($data['desc'])) $data['desc'] = '客户申请退款';
            if(empty($data['total'])||!is_numeric($data['total'])||$data['total']<=0||$data['total']!=(int)$data['total']) exception('订单总额不能为空!');
            if(empty($data['amount'])||!is_numeric($data['amount'])||$data['amount']<=0||$data['amount']!=(int)$data['amount']) exception('退款金额不能为空!');
            $req = [
                'appid'=> $this->appid,
                'mch_id'=> $this->mch_id,
                'nonce_str'=> $this->createNoncestr(),
                'sign_type'=> 'MD5',
                //'transaction_id'=> 0, //微信订单号
                'out_trade_no'=> $data['out_trade_no'], //商户订单号
                'out_refund_no'=> $data['out_refund_no'], //商户退款单号
                'total_fee'=> $data['total'], //订单金额
                'refund_fee'=> $data['amount'], //退款金额
                'refund_fee_type'=> 'CNY',
                'refund_desc'=> $data['desc'],
                //'refund_account'=> 10, //退款资金来源 仅针对老资金流商户使用
                'notify_url'=> $this->notify_url,
            ];
            $req['sign'] = $this->getSign($req);
            $xml = $this->post($this->pay_bank_api, $this->arrayToXml($req));
            $res = $this->xmlToArray($xml);
            $pay_log = (new PayLog())->insertGetId(['uid'=>0,'oid'=>0,'type'=>3,'orn'=>$data['out_trade_no'],'request'=>json_encode($req),'response'=>json_encode(['res'=>$res,'xml'=>$xml]),'explain'=>'微信退款-金额：'.$data['amount'],'add_time'=>time(),'ip'=>0]);//写入接口调用记录
            if(isset($res['result_code'])&&$res['result_code'] == 'SUCCESS'&&$res['mchid'] == $this->mch_id){
                return true;
            }
            exception(isset($res['err_code_des']) ? $res['err_code_des'] : ($res['return_msg']??'接口调用失败'));
            return false;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /*
     * 提现到余额api
     */
    public function transfers($data){
        try{
            if(empty($data['partner_trade_no'])) exception('订单号不能为空!');
            if(empty($data['desc'])) $data['desc'] = '提现申请';
            if(empty($data['amount'])||!is_numeric($data['amount'])||$data['amount']<=0||$data['amount']!=(int)$data['amount']) exception('提现金额不能为空!');
            if(empty($data['openid'])) exception('openid不能为空!');
            $req = [
                'mch_appid'=> $this->appid,
                'mchid'=> $this->mch_id,
                'nonce_str'=> $this->createNoncestr(),
                'partner_trade_no'=> $data['partner_trade_no'], //商户订单号
                'openid'=> $data['openid'], //商户订单号
                'check_name'=> 'NO_CHECK', //FORCE_CHECK：强校验真实姓名  NO_CHECK：不校验真实姓名
                'amount'=> $data['amount'], //退款金额
                'desc'=> $data['desc'],
            ];
            $req['sign'] = $this->getSign($req);
            $xml = $this->post($this->transfers_api, $this->arrayToXml($req));
            $res = $this->xmlToArray($xml);
            $pay_log = (new PayLog())->insertGetId(['uid'=>0,'oid'=>($data['oid']??0),'type'=>3,'orn'=>$data['partner_trade_no'],'request'=>json_encode($req),'response'=>json_encode(['res'=>$res,'xml'=>$xml]),'explain'=>'微信提现-金额：'.$data['amount'],'add_time'=>time(),'ip'=>0]);//写入接口调用记录
            if(isset($res['result_code'])&&$res['result_code'] == 'SUCCESS'&&$res['mchid'] == $this->mch_id){
                return true;
            }
            exception(isset($res['err_code_des']) ? $res['err_code_des'] : ($res['return_msg']??'接口调用失败'));
            return false;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /*
     * 回调处理
     */
    public function notify($xml) {
        try{
            fileLog($xml, date('Y-m-d').'_wechat_refund_log');
            if(empty($xml)) return '空数据';
            $result = $this->decryptData($xml);//解密
            if(!empty($result)&&is_array($result)){
                //处理数据
                if(!isset($result['appid'])||$result['appid'] != $this->appid)  return 'appid不正确';
                if(!isset($result['mch_id'])||$result['mch_id'] != $this->appid)  return 'mch_id不正确';
                if($result['refund_status']=='SUCCESS'){
                    #修改数据库
                    $result['out_trade_no'];//商户订单号
                    $result['out_refund_no'];//商户退款订单号
                    $result['refund_fee'];//申请退款金额
                    $result['settlement_refund_fee'];//实际退款金额 = 申请金额-非充值代金券退款金额
                    return true;
                }
            }
            return false;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
    }

    /*
     * 数据解密
     */
    public function decryptData($xml){
        $aesKEY=md5($this->key);
        $data = $this->xmlToArray($xml);
        $aesCipher=base64_decode($data['req_info']);
        $result=openssl_decrypt( $aesCipher, "AES-256-ECB", strtolower($aesKEY), 1);
        if(!empty($result)) return $this->xmlToArray($result);
        else{
            fileLog($xml, date('Y-m-d').'_wechat_refund_error');
            return '解密失败';
        }
    }

    public function arrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . ($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }


    //xml转换成数组
    private function xmlToArray($xml) {
        //禁止引用外部xml实体
        libxml_disable_entity_loader(true);

        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);

        $val = json_decode(json_encode($xmlstring), true);

        return $val;
    }


    //作用：产生随机字符串，不长于32位
    private function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }


    //作用：生成签名
    private function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }


    ///作用：格式化参数，签名过程需要使用
    private function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }



    /**
     * 使用curl post获取远程数据
     * $url     请求URL
     * $params  请求参数
     * $header  请求头部信息
     */
    public function post($url, $params = '', $header = [])
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
        //微信证书
        curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLCERT, $this->apiclient_cert);
        curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        curl_setopt($ch,CURLOPT_SSLKEY, $this->apiclient_key);
        if ($header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }

        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}