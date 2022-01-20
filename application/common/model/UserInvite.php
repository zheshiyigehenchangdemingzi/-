<?php
namespace app\common\model;

//use think\facade\Db;
use think\Db;

Class UserInvite extends Base
{
    public $page = '';//分页数据
    public $count = '';//数据总数

    // 绑定上下级
    /*public function bindTop($uid, $upid)
    {
        try{
            $self = $this->where(['uid'=>$uid])->find();
            $upidModel = $this->getTop($upid);//获取用户上级信息
            $foot = $this->getFoot($uid);//获取用户下级信息
            $team_pid = isset($upidModel['pid']) ? $upidModel['pid'] : 0;//团队层级
            $top_id = !empty($upidModel['topId'])?$upidModel['topId']:$upid;//最上级id
            $pid = isset($upidModel['pid']) ? $upidModel['pid'] + 1 : 1;//用户单曲
            $this::startTrans();
            if(!empty($foot)){
                $team_pid += $foot['pid'];
                if($pid > $self['pid']){
                    $this->inviteIncPid($uid,1,$pid-$self['pid']);//所有下级pid+1
                }else{
                    $this->inviteIncPid($uid,2,$self['pid']-$pid);//所有下级pid+1
                }
                $this->where(['top_id'=>$uid])->update(['top_id'=>$top_id,'team_pid'=>$team_pid]);
            }
            if(!empty($self)){
                //已存在记录
                $this->where(['top_id'=>$upidModel['topId']])->update(['team_pid'=>$team_pid]);
                $this->where(['uid'=>$uid])->update([
                    'pid' => $pid,
                    'upid' => $upid,
                    'top_id' => $top_id,
                    'team_pid' => $team_pid,
                    'add_time' => time()
                ]);
            }else{
                $this->where(['top_id'=>$upidModel['topId']])->update(['team_pid'=>$team_pid]);
                $this->insert([
                    'uid' => $uid,
                    'pid' => isset($upidModel['pid']) ? $upidModel['pid'] + 1 : 1,
                    'upid' => $upid,
                    'top_id' => $top_id,
                    'team_pid' => $team_pid,
                    'add_time' => time()
                ]);
            }
            $this::commit();
            return true;
        }catch (\Exception $e){
            $this::rollback();
            return $e->getMessage();
        }
    }*/


    //检查上级是否存在跟自己闭合关系
    public function user_check($pid,$uid){
        $invite = $this->where(['uid'=>$pid])->value('upid');
        if($invite == 0)
            return false;
        if($invite == $uid)
            return true;

        $this->user_check($invite,$uid);
    }



    /*
     ***************************** 更新后绑定方法 *********************************
     */


    /*
     * 绑定用户关系
     * array $user   被邀请人 数据包含用户id和等级level和invite_id邀请人id字段
     * array $invite 邀请人  数据包含用户id和等级level
     */
    public function bindTop($user, $invite)
    {
        try{
            if(empty($user)||empty($invite)) exception('绑定失败：参数有误!');
            if($invite['id'] == $user['id']) exception('绑定失败：不能绑定自己!');
//            if($invite['level'] < 1) exception('绑定失败!');#游客不能邀请新用户
//            if($invite['level'] < $user['level']) exception('绑定失败：邀请人等级比自己低，无法接受邀请!');
            $info = $this->where(['uid'=>$user['id']])->find();
            if(!empty($info['upid'])){
                if($info['upid'] == $invite['id']) exception('绑定失败：不能接受自己的下级邀请!');
            }
            $check = $this->checkPid($user['id'],$invite['id']);
            if($check == false) exception('绑定失败：与邀请人存在关系链，无法绑定!');
            $upidModel = $this->where(['uid'=>$invite['id']])->find();//获取用户上级信息
            $foot = $this->where(['upid'=>$user['id']])->count();//获取用户下级
            $pid = isset($upidModel['pid']) ? $upidModel['pid'] + 1 : 1;//用户当前层级
            $this::startTrans();
            if(!empty($foot)){
                //存在下级用户
                if($pid > 0) {
                    $type = 1;
                    if ($info['pid'] > 0){
                        if($info['pid'] > $pid){
                            $num = $pid;
                            $type = 2;
                        }else $num = $pid - $info['pid'];
                    }else
                        $num = $pid;
                    $this->inviteIncPidByLevel($user['id'], $type, $num, $upidModel);//所有下级pid,level更新
                }
            }
            $this->where(['uid' => $user['id']])->update([
                'pid' => $pid,
                'upid' => $invite['id'],
                'level' => (empty($upidModel['level'])) ? $invite['id'] : ($upidModel['level'].','.$upidModel['uid']),
                'add_time' => time()
            ]);
            $this::commit();
            return true;
        }catch (\Exception $e){
            $this::rollback();
            /*if(stristr($e->getMessage(),'SQLSTATE')){
                $this->error = '数据走丢了，请稍后再试！';
            }else*/ $this->error = $e->getMessage();
            return false;
        }
    }

    /*
     * 获取该用户所有下级(递归)
     */
    public function loopInvite($uid){
        $map = $this->where(['upid'=>$uid])->select()->toArray();
        if(empty($map)) return [];
        foreach($map as $k => $v)
        {
            $item = $this->loopInvite($v['uid']);
            if($item)
                $map = array_merge($map,$item);
        }
        return $map;
    }

    /*
     * 获取该用户所有上级(递归)
     */
    public function loopTopPid($uid){
        $map = $this->where(['uid'=>$uid])->field('uid,upid,pid')->select();
        if(empty($map[0])) return [];
        else $map = $map->toArray();
        $item = $this->loopTopPid($map[0]['upid']);
        if($item)
            $map = array_merge($map,$item);
        return $map;
    }

    /*
     * 获取该用户所有上级(递归)
     */
    public function checkPid($uid,$pid){
        $map = $this->loopTopPid($pid);
        if(empty($map)) return true;
        $ids = array_column($map,'upid');
        $map = array_merge([$pid],$ids);
        if(in_array($uid,$map)) return false;
        return true;
    }

    /*
     * 所有下级pid+1
     */
    public function inviteIncPidByLevel($id,$type,$num,$upid){
        $map = $this->loopInvite($id);
        $map = array_column($map,'uid');//获取所有下级id
        $level = (empty($upid['level']))? $upid['uid'] : ($upid['level'].','.$upid['uid']);
        if(count($map) > 2000){
            //大于2000条记录则分次执行
            $map = array_chunk($map, 2000, true);
            foreach($map as $v){
                $ids = implode(',',$v);
                if($type == 1)
                    $this->whereIn('uid',$ids)->inc('pid',$num)->update(['level'=>Db::raw("REPLACE(level,IF(LOCATE(".$id.",level)>0,SUBSTRING(level,1,LOCATE('".$id."',level)-2),SUBSTRING(level,1)),'".$level."')")]);
                else
                    $this->whereIn('uid',$ids)->dec('pid',$num)->update(['level'=>Db::raw("REPLACE(level,IF(LOCATE(".$id.",level)>0,SUBSTRING(level,1,LOCATE('".$id."',level)-2),SUBSTRING(level,1)),'".$level."')")]);
            }
        }else
            if($type == 1)
                $this->whereIn('uid',implode(',',$map))->inc('pid',$num)->update(['level'=>Db::raw("REPLACE(level,IF(LOCATE(".$id.",level)>0,SUBSTRING(level,1,LOCATE('".$id."',level)-2),SUBSTRING(level,1)),'".$level."')")]);
            else
                $this->whereIn('uid',implode(',',$map))->dec('pid',$num)->update(['level'=>Db::raw("REPLACE(level,IF(LOCATE(".$id.",level)>0,SUBSTRING(level,1,LOCATE('".$id."',level)-2),SUBSTRING(level,1)),'".$level."')")]);

        return true;
    }
}