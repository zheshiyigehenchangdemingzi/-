<?php
namespace app\common\model;

Class UserViewLevel extends Base
{
    //数据预处理
    public function getDescAttr($value)
    {
        return explode(',',$value);
    }

    // //数据预处理
    // public function getImgAttr($value)
    // {
    //     return (isHTTPS() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'].'/newUploads'.$value;
    // }

    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        //等级名称
        if(!empty($get['name']))
            $where['nmae'] = ['like','%'.$get['name'].'%'];
        $list = $this->order('sort desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->count();
        return $list;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        $data = $this->where(['id'=>$id])->find();
//        $data['desc'] = explode(',',$data['desc']);
        return $data;
    }

    public function addLevel($data){
        try{
            if(empty($data['name']))
                exception('请填写等级名称');
             if(empty($data['desc']))
                 exception('请填写等级说明');
             if(empty($data['img']))
                 exception('请上传图片');
            if(!is_numeric($data['sort']) || $data['sort'] < 0 || $data['sort'] > 99999)
                $data['sort'] = 0;

            $save = [
                'name'      =>  $data['name'],
                 'sort'      =>  $data['sort'],
                 'show'      =>  $data['show'],
                 'img' =>  $data['img'],
                 'desc'      =>  implode(',',array_values($data['desc']))
            ];

//            $data['level']  = $data['level'] * 1;
            $msg = '会员等级已添加';
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name']])->find())
                    exception('此会员等级名称已经使用');
                $newData = $save;
                //$newData = $data;
                $newData['add_time']   =   time();
                $this->allowField(true)->save($newData);
            }else{
                 $newData = $save;
                //$newData = $data;
//                $newData = [
//                    'name' => $data['name'],
//                    'sort' => $data['sort'] - 0,
//                    'level' => $data['level'] - 0,
//                    'purchase' => $data['purchase'] - 0,
//                    'share' => $data['share'] - 0,
//                    'team' => $data['team'] - 0,
//                    'manage' => $data['manage'] - 0
//                ];
                 $this->allowField(true)->isUpdate(true)->save($newData,['id'=>$data['id']]);
                $this::where('id',$data['id'])->update();
                $msg = '会员等级已修改';
            }
            return_ajax(200,$msg,$newData);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}