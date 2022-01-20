<?php
namespace app\common\model\setting;
use app\common\model\Base;
Class BannerHome extends Base
{
    # 配置自动更新时间等
    protected $autoWriteTimestamp = true;
    protected $updateTime = 'update_time';
    //数据预处理
//    public function getShowAttr($value)
//    {
//        return $value == 1 ? '是' : '否';
//    }

    //获取列表
    public function getList($get){
        $where = [];
        if(isset($get['title'])) $where['title'] = ['like','%'.$get['title'].'%'];
        if(isset($get['url'])) $where['url'] = ['like','%'.$get['url'].'%'];
        if(isset($get['show']) && strlen($get['show']) >= 1) $where['show'] = $get['show'];
        $list = $this->where($where)->order('sort desc')->select()->toArray();
        return $list;
    }

    //添加 编辑
    public function addLogistics($data){
        try{
            if(empty($data['title']))
                exception('请填写标题');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            /*
            if (empty($data['show']))
                $data['show'] = 1;
            */
            if(empty($data['img_url']))
                exception('请上传图片');
            // 数据保存
            $save = [
                'title'   =>  $data['title'],
                'sort'      =>  $data['sort'],
                'img_url' => $data['img_url'],
                'show' => $data['show']
            ];
            if(isset($data['url'])) $save['url'] = $data['url'];
            $msg = '添加成功';
            if(empty($data['id'])){
                if($this->where(['title'=>$data['title']])->find())
                    exception('此列表项已存在');
                $save['add_time']   =   time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'title'=>$data['title']])->find())
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