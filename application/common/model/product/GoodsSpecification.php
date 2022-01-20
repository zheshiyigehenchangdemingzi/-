<?php
/**
 * Created by PhpStorm.
 * User: snuz
 * Date: 2020/7/1
 * Time: 9:24
 */
namespace app\common\model\product;
use app\common\model\Base;
Class GoodsSpecification extends Base
{
    // 条数
    public $count = 0;

    // 获取列表
    public function getList($value,$type)
    {
        // 获取所有数据
        $allList = $this->select()->toArray();
        $this->count = count($allList);
        $data = [];

        foreach($allList as $key => $item){
            // 如果为一级菜单
            if ($item['pid'] == 0){
                $item['son'] = [];
                $data[$key] = $item;
                $data[$key]['son'] = [];

                foreach($allList as $key2 => $item2){
                    // 是父级
                    if ($item2['pid'] == $item['id']){
                        $data[$key]['son'][] = $item2;
                        unset($allList[$key2]);
                    }
                }
            }
        }

        // 搜索值
        $searchValue = $value && strlen(trim($value)) >= 2 ? trim($value) : false;

        // 搜索类型 0 表示一级菜单 ， 1 表示二级
        $searchType = $type;
        if($searchValue){
            foreach($data as $key => $item){
                // 一级菜单
                if($searchType == 0 && !strstr($item['value'],$searchValue)){
                    unset($data[$key]);
                // 二级菜单
                }else if($searchType == 1 && isset($item['son']) && count($item['son']) > 0){
                    // 是否需要删除一级
                    $delBool = true;
                    foreach($item['son'] as $key2 => $item2){
                        // 如果不存在
                        if(!strstr($item2['value'],$searchValue)){
                            //unset($data[$key]['son'][$key2]);
                        }else{
                            $delBool = false;
                        }
                    }
                    if($delBool)
                        unset($data[$key]);
                }
            }
        }
        return $data;
    }



    // 添加-编辑操作
    public function addLogistics($data){
        try{
            if(empty($data['title']))
                exception('请填写标题');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            if(empty($data['value']))
                exception('请输入规格值');
            /*
            if (empty($data['show']))
                $data['show'] = 1;
            */

            // 数据保存
            $save = [
                'value' => $data['title'],
                'sort'      =>  $data['sort'],
                'is_show' => $data['is_show']
            ];

            // 解析数据流
            // $insertData = $this->analysisData($data,$save, true);

            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['value'=>$data['title']])->find())
                    exception('此规格模板名称已经存在');
                $save['add_time'] = time();
                // 保存规格模板-标题值  获取到id
                $insertId = $this->allowField(true)->insertGetId($save);
                // 刚开始不弄消息队列 后期量大时  使用tp 消息队列监听  此处进行分离
                if ($insertId){
                    $this->insertData($data['value'],$save,$insertId);
                }
            }else{
                /*
                if($this->where(['id'=>['neq',$data['id']],'title'=>$data['title']])->find())
                    exception('此列表项已存在');
                */
                $save['upd_time'] = time();
                $this->allowField(true)->isUpdate(true)->save($save, ['id' => $data['id']]);
                // 先删除所有 然后进行添加即可
                $this->where('pid',$data['id'])->delete();
                $this->insertData($data['value'],$save,$data['id']);
                $msg = '编辑成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    // 前台传递数据解析，需要指定pid 进行父级关联 - 编辑写入数据分析
    public function analysisData($data,$saveData = [],$pid)
    {
        $arr = [];
        $obj = [];
        foreach($data as $item){
            if(!isset($obj[$item])){
                $saveData['value'] = $item;
                $saveData['pid'] = $pid;
                $arr[] = $saveData;
            }
            $obj[$item] = 1;
        }
        return $arr;
    }

    // 插入记录操作
    public function insertData($data,$save,$pid)
    {
        $insertData = $this->analysisData($data,$save,$pid);
        if(!$insertData || count($insertData) == 0)
            exception('当前没有记录');
        $this->insertAll($insertData);
    }

    // 获取子级和当前
    public function getChild($id)
    {
        $selfData = $this->where('id',$id)->find()->toArray();
        $childData = $this->where('pid',$id)->select()->toArray();
        $selfData['title'] = $selfData['value'];
        return [
            'data' => $selfData,
            'childList' => $childData
        ];
    }

    // 获取规格模板
    public function getMuBanList()
    {
        $old_attrData = $this->select()->toArray();
        $attrData = [];
        foreach($old_attrData as $key => $item){
            // 如果为一级菜单
            if ($item['pid'] == 0){
                $attrData[$key]['title'] = $item['value'];
                $attrData[$key]['id'] = $item['id'];
                $attrData[$key]['children'] = [];
                foreach($old_attrData as $key2 => $item2){
                    // 是父级
                    if ($item2['pid'] == $item['id']){
                        $attrData[$key]['children'][] = [
                            'title' => $item2['value'],
                            'id' => $item2['id']
                        ];
                        unset($old_attrData[$key2]);
                    }
                }
            }
        }
        return $attrData;
    }
}