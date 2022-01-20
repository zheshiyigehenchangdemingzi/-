<?php

namespace app\common\model;


use think\Env;

class Config extends Base
{
    // 商城首页-爆款，福利 ， 新品 配置信息
    public $shopConfigView = [
        [
            "title" => "爆款",
            "key" => "explosion",
            "default_num" => 8,
            "default_title" => "限时限量抢好货",
        ],
        [
            "title" => "福利",
            "key" => "welfare",
            "default_num" => 2,
            "default_title" => "香奈儿加白丽尔6折抢！",
        ],
        [
            "title" => "新品",
            "key" => "new",
            "default_num" => 2,
            "default_title" => 'SK-ll“神仙水”半价上新',
        ]
    ];

    // 店铺配置  店铺详情操作
    public $shopOrderConfigView = [
        [
            'title' => '提现平台手续费',
            'key' => 'platform_rate',
            'default' => 10,
            'type' => 'number',
            'desc' => '百分比，商家金额提现时扣除的平台费'
        ],
        [
            'title' => '商家保证金',
            'key' => 'security_deposit',
            'default' => 2000,
            'type' => 'number',
            'desc' => '商家保证金金额'
        ]
    ];


    /*
     * 保存配置
     * $mode true 保存成功，响应提示 false 保存成功，不响应提示
     * */
    public function toSave($data, $mode = true)
    {
        if (empty($data['type']))
            return_ajax(400, '配置类型不能为空');
        $type = $data['type'];
        unset($data['type']);
        if ($this->where(['type' => $type])->find()) {
            $this->allowField(true)->isUpdate(true)->save(['content' => json_encode($data)], ['type' => $type]);
            $msg = '配置修改成功';
        } else {
            $this->allowField(true)->save(['content' => json_encode($data), 'type' => $type]);
            $msg = '配置保存成功';
        }
        if ($mode)
            return_ajax(200, $msg);
    }

    //获取配置
    public function toData($type)
    {
        $config = $this->where(['type' => $type])->value('content');
        if (empty($config))
            return [];

        return json_decode($config, true);
    }

    //注册协议图文保存
    public function toReg($data)
    {
        try {
            if (empty($data['editorValue']))
                return_ajax(400, '注册协议不能为空');

            $content = htmlspecialchars($data['editorValue']);
            if ($this->where(['type' => 'reg'])->find()) {
                $this->allowField(true)->isUpdate(true)->save(['content' => json_encode(['content' => $content])], ['type' => 'reg']);
                $msg = '配置修改成功';
            } else {
                $this->allowField(true)->save(['content' => json_encode(['content' => $content]), 'type' => 'reg']);
                $msg = '配置保存成功';
            }
            return_ajax(200, $msg);
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }

    //等级规则保存
    public function toRole($data)
    {
        try {
            if (empty($data['editorValue']))
                return_ajax(400, '等级规则不能为空');

            $content = htmlspecialchars($data['editorValue']);
            if ($this->where(['type' => 'reg'])->find()) {
                $this->allowField(true)->isUpdate(true)->save(['content' => json_encode(['content' => $content])], ['type' => 'reg']);
                $msg = '配置修改成功';
            } else {
                $this->allowField(true)->save(['content' => json_encode(['content' => $content]), 'type' => 'reg']);
                $msg = '配置保存成功';
            }
            return_ajax(200, $msg);
        } catch (\Exception $e) {
            return_ajax(400, $e->getMessage());
        }
    }
}