<?php
namespace app\common\model;


Class Express extends Base
{
    //获取物流信息
    public function getExpress($rid,$type=1){
        return $this->where(['relation_id'=>$rid,'type'=>$type])->find();
    }

    //保存物流数据
    public function addExpress($data){
        try{
            if(empty($data['company']) || !is_numeric($data['company']) || $data['company'] <= 0)
                exception('请选择物流公司');
//            if(empty($data['express_sn'])) exception('物流单号不能为空');

            $save = [
                'company'       =>  $data['company'],
                'express_sn'    =>  isset($data['express_sn']) ? $data['express_sn'] : '',
                'type'          =>  1,
                'relation_id'   =>  $data['rid'],
                'desc' => $data['desc'],
                'upd_time' => time()
            ];
            $msg = '订单已发货';
            if(empty($data['id'])){
                $now = time();
                $model = new Order();
                $mod = new OrderGoods();
                $this::startTrans();
                try{
                    //保存发货物流信息
                    $save['add_time'] = $now;
                    $express_id  = $this->insertGetId($save);
                    #$express_id= $this->allowField(true)->save($save);
                    $msg .= '-'.$express_id;
                    //更改订单状态
                    $model->where('id',$data['rid'])->update([
                        'express_id' => $express_id,
                        'status' => 3,
                        'deliver_time' => time()
                    ]);
                    UserShareOrder::where('oid',$data['rid'])->update([
                        'status' => 3
                    ]);
                     
                    #$model->allowField(true)->isUpdate(true)->save(['status'=>3,'deliver_time'=>$now,'express_id' => $express_id],['id'=>$data['rid']]);

                    //更改订单商品状态
                    $mod->save(['status'=>2],['order_id'=>$data['rid']]);
                    $this::commit();
                }catch (\Exception $e1){
                    $this::rollback();
                    exception($e1->getMessage());
                }

            }else{
                $msg = '物流信息已更改';
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                /*
                $express_id = $this->insertGetId($save);
                Order::where('id',$data['rid'])->update([
                        'express_id' => $express_id,
                        'status' => 3
                ]);
                 */
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}