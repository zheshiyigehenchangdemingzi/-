<?php


namespace app\common\model\shop;



use app\common\model\Base;

// 店铺详情
class ShopRecharge extends Base
{
    // 获取列表
    public function getList($get){
        $where = ['r.is_delete'=>0];
        if(!empty($get['shop_id']))
            $where['r.shop_id'] = $get['shop_id'];
        if(!empty($get['order_sn']))
            $where['r.order_sn'] = $get['order_sn'];
        if(isset($get['type'])&&$get['type']!='')
            $where['r.type'] = $get['type'];
        if(isset($get['status'])&&$get['status']!='')
            $where['r.status'] = $get['status'];
        if(!empty($get['name']))
            $where['u.name'] = ['like','%'.$get['name'].'%'];
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            $where['r.add_time'] = ['gt',$start];
        }
        if(!empty($get['end'])){
            $end = strtotime($get['end']."+1 day");
            if(!empty($start))
                $where['r.add_time']  =  ['between',[$start,$end]];
            else
                $where['r.add_time'] = ['lt',$end];
        }
        $field = 'r.money,r.order_sn,r.type,r.status,r.add_time,u.name';
        $list = $this->alias('r')->join('shop_users u','u.id=r.shop_id','left')->where($where)->field($field)->order('r.add_time desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->alias('r')->join('shop_users u','u.id=r.shop_id','left')->where($where)->count();
        $sum = $this->alias('r')->join('shop_users u','u.id=r.shop_id','left')->where($where)->sum('money');
        $list = !empty($list)?$list->toArray():[];
        if(!empty($list['data'])){
            /*foreach($list['data'] as $k=>$v){

            }*/
        }
        $list['sum'] = $sum;
        return $list;
    }

    /*
     * 获取信息
     */
    public function getOne($id){
        $info = $this->where(['id'=>$id])->find();
        return $info;
    }

    /*
     * 编辑
     */
    public function withdrawEdit($id){
        try{
            if(empty($data['title']))
                exception('活动标题不能为空');
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

            $time_start = strtotime($data['start_time']);
            $time_end = strtotime($data['end_time']." +1 day");
            $save = [
                'title'         =>  $data['title'],
                'initial'       =>  $data['initial']*100,
                'price'         =>  $data['price']*100,
                'status'        =>  $data['status']==1?1:2,
                'is_team'       =>  $data['is_team']==1?1:2,
                'is_sale'       =>  $data['is_sale'],
                'sale_val'      =>  $data['sale_val'],
            ];
            // 更新 设置为
            //$this->allowField(true)->save($save,['id'=>$data['id']]);
            return_ajax(200,'活动修改成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}