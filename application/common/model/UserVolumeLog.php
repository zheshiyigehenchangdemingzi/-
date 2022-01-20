<?php
namespace app\common\model;

use think\Db;

Class UserVolumeLog extends Base
{
    //数据预处理
    public function getPriceAttr($value){
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getBalanceAttr($value){
        return number_format($value/100,2,'.','');
    }

    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getConsumeList($get){
        $where = ['l.is_delete'=>0,'l.type'=>1];
        //会员名称
        if(!empty($get['name']))
            $where['u.nickname'] = ['like','%'.$get['name'].'%'];
        //会员id
        if(!empty($get['uid']))
            $where['l.uid'] = $get['uid'];
        if(!empty($get['start'])){
            if(!empty($get['end'])){
                $where['l.add_time'] = ['between',[strtotime($get['start']),strtotime($get['end'])]];
            }else{
                $where['l.add_time'] = ['gt',strtotime($get['start'])];
            }
        }

        $field = 'l.id,l.type,l.uid,l.price,l.balance,l.add_time,u.nickname as name,o.order_sn';
        $list = $this->alias('l')->join('user u','l.uid=u.id','left')
            ->join('order o','l.relation_id=o.id','left')
            ->where($where)->field($field)->order('l.id desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->alias('l')->join('user u','l.uid=u.id','left')
            ->join('order o','l.relation_id=o.id','left')->where($where)->count();

        return $list;
    }

    //充值记录
    public function getRechargeList($get){
        $where = ['vl.type'=>2,'vl.is_delete'=>0];
        $where1 = ['v.is_delete'=>0];
        if(!empty($get['uid'])){
            $where['vl.uid'] = $get['uid'];
            $where1['v.uid'] = $get['uid'];
        }
        if(!empty($get['ordersn'])){
            $where1['v.order_sn'] = $get['ordersn'];
        }
        if(!empty($get['uname'])){
            $where['u.nickname'] = ['like','%'.$get['uname'].'%'];
            $where1['u.nickname'] = ['like','%'.$get['uname'].'%'];
        }
        if(!empty($get['status'])){
            $where1['v.status'] = $get['status'];
        }else $where1['v.status'] = ['>',0];
        if(!empty($get['start'])){
            $start = strtotime($get['start']);
            $where['vl.add_time'] = ['gt',$start];
            $where1['v.add_time'] = ['gt',$start];
        }
        if(!empty($get['end'])){
            $end = strtotime($get['end']."+1 day");
            if(!empty($start)){
                $where['vl.add_time']  =  ['between',[$start,$end]];
                $where1['v.add_time']  =  ['between',[$start,$end]];
            }else{
                $where['vl.add_time'] = ['lt',$end];
                $where1['v.add_time'] = ['lt',$end];
            }
        }

        $ret = (new Config())->toData('volume');
        if(empty($ret)) exception('系统繁忙，数据走丢了!');
        $field = 'u.id,u.nickname,vl.price,IF(vl.vid=0,(select order_sn from `m3_order` where id=vl.oid),(select order_sn from `m3_user_volume` where id=vl.vid)) as order_sn,vl.is_sys,1 as status,vl.add_time';
        $item = $this->alias('vl')->join('user u','vl.uid=u.id','left')->field($field)->where($where)->order('add_time desc')->paginate(20, false, array('query'=>$get));
//        $field = 'u.id,u.nickname,vl.price,0 as order_sn,vl.is_sys,1 as status,vl.add_time';
//        $balances = $this->alias('vl')->join('user u','vl.uid=u.id','left')->field($field)->where($where)->buildSql();
//        $ucash = Db::name('user_volume')->alias('v')->join('user u','v.uid=u.id','left')->where($where1)->field('u.id,u.nickname,v.balance as price,v.order_sn,0 as is_sys,v.status,v.add_time')->union([$balances],true)->buildSql();
//        $item = Db::table($ucash)->alias('union')->order('add_time desc')->paginate(20, false, array('query'=>$get));
        $this->page = $item->render();
//        $this->count = Db::table($ucash)->alias('union')->count();
        $this->count = $this->alias('vl')->join('user u','vl.uid=u.id','left')->where($where)->count();
        $list = !empty($item) ? $item->toArray() : [];

        if(!empty($list['data'])){
            foreach($list['data'] as $k=>$v){
                $list['data'][$k]['name'] = $ret['name']??'购物卷';
                // $list['data'][$k]['price'] = number_format($v['price'] / 100, 2, '.', '');
            }
        }

        return $list['data'];
    }

    //获取列表
    public function getList($get){
        $where = ['is_delete'=>0];
        $where1 = ['is_delete'=>0];
        if(!empty($get['id'])){
            $where['uid'] = $get['id'];
            $where1['uid'] = $get['id'];
        }

        $ret = (new Config())->toData('volume');
        if(empty($ret)) exception('系统繁忙，数据走丢了!');
        $field = 'type,price,is_sys,add_time';
        $balances = $this->field($field)->where($where)->buildSql();
        $ucash = Db::name('user_volume')->where($where1)->field('2 as type,balance as price,0 as is_sys,add_time')->union([$balances],true)->buildSql();
        $item = Db::table($ucash)->alias('union')->order('add_time desc')->paginate(20, false, array('query'=>$get));
        $this->page = $item->render();
        $this->count = Db::table($ucash)->alias('union')->count();
        $list = !empty($item) ? $item->toArray() : [];

        if(!empty($list['data'])){
            foreach($list['data'] as $k=>$v){
                $list['data'][$k]['name'] = $ret['name']??'购物卷';
                $list['data'][$k]['price'] = number_format($v['price'] / 100, 2, '.', '');
            }
        }
        return $list['data'];
    }
}