<?php
namespace app\common\model;

Class Volume extends Base
{
    //数据预处理
    public function getPriceAttr($value){
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getInitialAttr($value){
        return number_format($value/100,2,'.','');
    }

    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        $where = ['is_delete'=>0];
        //购物券名称
        if(!empty($get['name']))
            $where['nmae'] = ['like','%'.$get['name'].'%'];

        $field = 'id,title,initial,price,is_team,is_sale,share,desc,status,start_time,end_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    //获取购物券id-名称键值对
    public function getVolume(){
        return $this->where(['is_delete'=>0])->column('id,name');
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        $data = $this->where(['id'=>$id])->field('id,title,level,initial,price,is_team,is_sale,sale_val,type,share,share_grade,share_num,details_img,desc,status,start_time,end_time')->find();
        $data['desc'] = htmlspecialchars_decode($data['desc']);
        return $data;
    }

    //添加购物券
    public function addVolume($data){
        try{
            if(empty($data['name']))
                exception('请填写购物券名称');
            if(empty($data['initial']) || !is_numeric($data['initial']) || $data['initial'] <= 0)
                exception('请填写正确的购物券初始额度');
            if(empty($data['price']) || !is_numeric($data['price']) || $data['price'] <= 0)
                exception('请填写正确的购物券购买价格');

            $save = [
                'name'          =>  $data['name'],
                'initial'       =>  $data['initial']*100,
                'price'         =>  $data['price']*100,
                'is_team'       =>  $data['is_team']==1?1:0,
                'is_sale'       =>  $data['is_sale']==1?1:0,
            ];

            $msg = '购物券已添加';
            if(empty($data['id'])){
                if($save['is_sale'] == 1){
                    $count = count($data['key']);
                    $content = ['type'=>$data['type']==1?1:0];
                    for ($i = 0;$i<$count;$i++){
                        $content[$data['key'][$i]] = $data['value'][$i]<0?0:$data['value'][$i];
                    }
                    $save['sale_val'] = json_encode($content);
                }else{
                    $save['sale_val'] = json_encode(['type'=>'']);
                }

                if($this->where(['name'=>$data['name'],'is_delete'=>0])->find())
                    exception('此购物券名称已经使用');
                $save['add_time']   =   time();
                $this->allowField(true)->save($save);
            }else{
                if($save['is_sale'] == 1){
                    $count = count($data['key']);
                    $content = ['type'=>$data['type']==1?1:0];
                    for ($i = 0;$i<$count;$i++){
                        $content[$data['key'][$i]] = $data['value'][$i]<0?0:$data['value'][$i];
                    }
                    $save['sale_val'] = json_encode($content);
                }
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],'is_delete'=>0])->find())
                    exception('此购物券名称已经使用');
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '购物券已修改';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //购物券删除
    public function volumeEdit($data){
        try{
            if(empty($data['title']))
                exception('活动标题不能为空');
            if(empty($data['level']) || !is_numeric($data['level']) || $data['level'] <= 0)
                exception('请选择正确的等级');
            if(empty($data['initial']) || !is_numeric($data['initial']) || $data['initial'] <= 0)
                exception('请填写正确的购物券额度');
            if(empty($data['price']) || !is_numeric($data['price']) || $data['price'] < 0)
                exception('请填写正确的购买金额');
            if(!empty($data['status']) && !is_numeric($data['status']))
                exception('请选择正确的状态');
            if(!empty($data['is_team']) && !is_numeric($data['is_team']))
                exception('请选择正确的团队消费配置');
            if(!empty($data['is_sale']) && !is_numeric($data['is_sale']))
                exception('请选择正确的分销配置');
            if(!empty($data['sale_val']) && !is_numeric($data['sale_val']))
                exception('请填写正确的分销数值');
            if(empty($data['share']))
                exception('请上传活动分享图');
            if(empty($data['details_img']))
                exception('请上传活动详情图');
            if(empty($data['editorValue']))
                exception('请输入活动规则');
            if(!in_array($data['type'],[1,2]))
                exception('请选择活动类型');

            $time_start = $time_end = 0;
            if($data['type'] == 1){
                if(empty($data['start_time']))
                    exception('请选择活动开始时间');
                if(!isset($data['end_time']))
                    exception('请选择活动结束时间');
                $time_start = strtotime($data['start_time']);
                $time_end = empty($data['end_time']) ? 0 : strtotime($data['end_time']." +1 day");
            }
            $save = [
                'title'         =>  $data['title'],
                'type'          =>  $data['type'],
                'level'         =>  $data['level'],
                'initial'       =>  $data['initial']*100,
                'price'         =>  $data['price']*100,
                'status'        =>  $data['status']==1?1:2,
                'is_team'       =>  $data['is_team']==1?1:2,
                'is_sale'       =>  $data['is_sale'],
                'sale_val'      =>  $data['sale_val'],
                'share_grade'   =>  ($data['share_grade']??0),
                'share_num'     =>  ($data['share_num']??0),
                'start_time'    => $time_start,
                'end_time'      => $time_end,
                'share'         =>  $data['share'],
                'details_img'         =>  $data['details_img'],
                'desc'          =>  htmlspecialchars(str_replace('<p><br/></p>','',$data['editorValue'])),
            ];
            // 更新 设置为
            $this->allowField(true)->save($save,['id'=>$data['id']]);
            return_ajax(200,'活动修改成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //活动状态编辑
    public function editStatus($data){
        try{
            if(empty($data['id']))
                exception('活动错误');
            $status = 1;
            $msg = '活动上架成功';
            if($data['type'] == 'end'){
                $msg = '活动下架成功';
                $status = 2;
            }
            $this->allowField(true)->isUpdate(true)->save(['status'=>$status],['id'=>$data['id']]);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //购物券删除
    public function volumeDel($id){
        try{
            if(!is_numeric($id))
                exception('非法操作');

            $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            return_ajax(200,'购物券已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}