<?php
namespace app\common\model\setting;
use app\common\model\Base;
Class ViewText extends Base
{
    # 配置自动更新时间等
//    protected $autoWriteTimestamp = true;
//    protected $updateTime = 'update_time';

    //获取列表
    public function getList(){
        $list = $this->order('sort desc')->select()->toArray();
        return $list;
    }

    //添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['text']))
                exception('请填写内容');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            if(empty($data['show']) || !is_numeric($data['show']) || $data['show'] < 0)
                $data['show'] = 0;
            if(empty($data['uname']))
                exception('请选择用户姓名');
            // 数据保存
            $save = [
                'text'   =>  $data['text'],
                'sort'      =>  $data['sort'],
                'uname' => $data['uname'],
                'show' => $data['show']
            ];
            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['text'=>$data['text']])->find())
                    exception('此列表项已存在');
                $save['add_time']   =   time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'text'=>$data['text']])->find())
                    exception('此列表项已存在');
                $save['upd_time'] = time();
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '编辑成功';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}