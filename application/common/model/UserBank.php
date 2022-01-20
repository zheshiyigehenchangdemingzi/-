<?php
namespace app\common\model;

Class UserBank extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数
    //获取列表
    public function getList($get){
        $where = ['uid'=>$get['id'],'is_delete'=>0];

        $field = 'id,bank_name,phone,card,identity,name,add_time';
        $list = $this->where($where)->field($field)->order('id desc')
            ->paginate(20,false,array('query'=>$get));
        if($list){
//            $model = new Bank();
//            foreach ($list as &$val){
//                $val['bank_id'] = $model->getName($val['id']);
//            }unset($val);
        }
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    /*
     * 编辑银行卡
     */
    public function editBank($data,$type){
        try{
            if(empty($data)) exception('数据为空');
            if(empty($data['id'])) exception('找不到该卡信息');
            $where = ['id'=>$data['id'],'is_delete'=>0];
            $info = $this->where($where)->find();
            if($type == 1) return !empty($info) ? $info->toArray() : [];
            else{
                if(!empty($data['card'])){
                    $res = $this->whereNotIn('id',$info['id'])->where(['uid'=>$info['uid'],'card'=>$data['card'],'is_delete'=>0])->find();
                    if(!empty($res)) exception('已存在该卡信息');
                }
                if($data['is_default'] == 1&&$info->is_default != 1){
                    $res = $this->whereNotIn('id',$info['id'])->where(['uid'=>$info['uid'],'is_delete'=>0])->update(['is_default'=>0]);
                }
                $ret['id'] = $data['id']??$info->id;
                $ret['bank_name'] = $data['bank_name']??$info->bank_name;
                $ret['card'] = $data['card']??$info->card;
                $ret['identity'] = $data['identity']??$info->identity;
                $ret['name'] = $data['name']??$info->name;
                $ret['phone'] = $data['phone']??$info->phone;
                $ret['is_default'] = $data['is_default']??$info->is_default;
                if($info->save($ret))
                    return_ajax(200,'修改成功',[]);
            }
            exception('找不到该卡信息，修改失败');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 删除银行卡
     */
    public function delBank($id){
        try{
            $where = ['id'=>$id,'is_delete'=>0];
            $info = $this->where($where)->field('id')->find();
            if($info){
                if($this->where(['id'=>$info['id']])->update(['is_delete'=>1]))
                    return_ajax(200,'删除成功',[]);
            }
            exception('找不到该卡信息，删除失败');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}