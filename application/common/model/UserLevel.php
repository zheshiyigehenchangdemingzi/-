<?php
namespace app\common\model;

use think\cache\driver\Redis;

Class UserLevel extends Base
{
    //数据预处理
    public function getLevelAttr($value)
    {
        return number_format($value/100,2,'.','');
    }

    //数据预处理
    public function getImgAttr($value)
    {
        return (isHTTPS() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'].'/newUploads'.$value;
    }

    public $page = '';//分页数据
    public $count = '';//数据总数

    //获取列表
    public function getList($get){
        $where = ['is_delete'=>0];
        //等级名称
        if(!empty($get['name']))
            $where['nmae'] = ['like','%'.$get['name'].'%'];

        $field = 'id,name,level,purchase,share,manage,team,team_twe,coupon_team,coupon_type,coupon_team_twe,coupon_manage,add_time,sort,desc';
        $list = $this->where($where)->field($field)->order('sort desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();
        $list = $list->toArray();
        if(!empty($list['data'])){
            foreach($list['data'] as $k=>$v){
                $list['data'][$k]['team'] = $v['team']/10;
                $list['data'][$k]['team_twe'] = $v['team_twe']/10;
                $list['data'][$k]['manage'] = $v['manage']/10;
                $list['data'][$k]['coupon_team'] = $v['coupon_team']/10;
                $list['data'][$k]['coupon_team_twe'] = $v['coupon_team_twe']/10;
                $list['data'][$k]['coupon_manage'] = $v['coupon_manage']/10;
            }
        }
        return $list['data'];
    }

    //获取等级信息
    public function levelInfo(){
        return $this->where(['is_delete'=>0])->field('id,level,name,purchase,share,team,manage,img')->select();
    }

    //获取等级
    public function getLevel(){
        return $this->where(['is_delete'=>0])->column('id,name');
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        $data = $this->where(['id'=>$id])->field('id,name,level,direct_push,team_num,purchase,share,manage,team,team_twe,coupon_type,coupon_team,coupon_team_twe,coupon_manage,add_time,sort,desc')->find();
        $data['team'] = $data['team']/10;
        $data['team_twe'] = $data['team_twe']/10;
        $data['manage'] = $data['manage']/10;
        $data['coupon_team'] = $data['coupon_team']/10;
        $data['coupon_team_twe'] = $data['coupon_team_twe']/10;
        $data['coupon_manage'] = $data['coupon_manage']/10;
        return $data;
    }

    public function addLevel($data){
        try{
            if(empty($data['name']))
                exception('请填写等级名称');
            if(!is_numeric($data['level']) || $data['level'] < 0)
                exception('请填写正确的等级条件');
            if(!is_numeric($data['purchase']) || $data['purchase'] < 0 || $data['purchase'] > 99)
                exception('请填写正确的自购省比例');
            if(!is_numeric($data['share']) || $data['share'] < 0 || $data['share'] > 99)
                exception('请填写正确的分享赚比例');
            if(!is_numeric($data['team']) || $data['team'] < 0 || $data['team'] > 99)
                exception('请填写正确的团队佣金比例');
            if(!is_numeric($data['manage']) || $data['manage'] < 0 || $data['manage'] > 99)
                exception('请填写正确的管理津贴比例');
//            if(!is_numeric($data['discount']) || $data['discount'] < 0 || $data['discount'] > 99)
//                exception('请填写正确的折扣比例');
//            if(empty($data['desc']))
//                exception('请填写等级说明');
//            if(empty($data['img']))
//                exception('请上传图片');
            if(!is_numeric($data['sort']) || $data['sort'] < 0 || $data['sort'] > 99999)
                $data['sort'] = 0;

            $save = [
                'name'      =>  $data['name'],
                'level'     =>  $data['level'] * 100,
                'purchase' =>  $data['purchase'],
                'share'     =>  $data['share'],
                'direct_push'  =>  ($data['direct_push']??0),
                'team_num'     =>  ($data['team_num']??0),
                'sort'      =>  $data['sort'],
                'team'      =>  $data['team']*10,
                'team_twe'      =>  $data['team_twe']*10,
                'manage'    =>  $data['manage']*10,
                'coupon_type'      =>  $data['coupon_type'],
                'coupon_team'      =>  $data['coupon_type']==1 ? $data['coupon_team']*10 : $data['coupon_team'],
                'coupon_team_twe'  =>  $data['coupon_team_twe']*10,
                'coupon_manage'    =>  $data['coupon_manage']*10,
//                'img' => $data['img'],
                'desc'  =>  $data['desc'],
                // 'desc'      =>  implode(',',array_values($data['desc']))
            ];
            $msg = '会员等级已添加';
            $redis = new Redis();
            if(empty($data['id'])){
                if($this->where(['name'=>$data['name'],'is_delete'=>0])->find())
                    exception('此会员等级名称已经使用');
                $redis->select(5);
                $redis->del('mmei_user_level_list');
                $save['add_time']   =   time();
                $this->allowField(true)->save($save);
            }else{
                if($this->where(['id'=>['neq',$data['id']],'name'=>$data['name'],'is_delete'=>0])->find())
                    exception('此会员等级名称已经使用');
                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $redis->select(5);
                $redis->del('mmei_user_level_list');
                $msg = '会员等级已修改';
            }
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //等级删除
    public function levelDel($id){
        try{
            $count = count($id);
            if($count > 1){
                foreach ($id as $v){
                    if(!is_numeric($v))
                        exception('非法操作');
                }

                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>['in',$id]]);
            }else{
                if(!is_numeric($id[0]))
                    exception('非法操作');

                $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id[0]]);
            }

            return_ajax(200,'会员等级已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }
}