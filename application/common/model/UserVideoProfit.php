<?php
declare (strict_types = 1);

namespace app\common\model;

use think\Model;

/**
 * @mixin \think\Model
 */
class UserVideoProfit extends Model
{
    public $page = '';//分页数据
    public $count = '';//数据总数
    public $error = '';//报错

    /*
     * 更新记录
     * uid 用户id
     * type 1-发布视频 2-点赞互动视频 3-转发分享视频 4-评论视频 5-视频被观看奖励(5的uid为视频发布者的用户id)
     */
    public function updateProfit($data){
        try{
            if(empty($data['uid'])) exception('用户id不能为空!');
            if(empty($data['type'])) exception('类型不能为空!');
            $oid = isset($data['oid']) ? $data['oid'] : 0;
            $userInfo = (new User())->where(['id'=>$data['uid'],'is_delete'=>0])->find();
            if(empty($userInfo)) exception('用户为空!');
            if($userInfo['is_proceeds'] == 0&&$userInfo['level'] <= 1) exception('等级权限不足，无法享受收益!');
            $type = '';$typeNum = '';
            if($data['type'] == 1){
                $type = 'post_day';
                $typeNum = 'post_num';
            }
            if($data['type'] == 2){
                $type = 'like_day';
                $typeNum = 'like_num';
            }
            if($data['type'] == 3){
                $type = 'share_day';
                $typeNum = 'share_num';
            }
            if($data['type'] == 4){
                $type = 'comment_day';
                $typeNum = 'comment_num';
            }
            if($data['type'] == 5){
                $type = 'watch_day';
                $typeNum = 'watch_num';
            }
            if(empty($type)||empty($typeNum)) exception('类型不存在!');
            $time = !empty($data['time']) ? $data['time'] : time();
            $day = date('Ymd',$time);
            $info = $this->where(['uid'=>$data['uid'],'date'=>$day])->find();
            $model = new UserWallet();
            $walletOperationLog = new WalletOperationLog();
            $video = (new Config())->toData('video');
            if(empty($video)||!isset($video[$type])) exception('数据走丢了，请联系客服!');
            $pid = 0;$updateInfo = 0;
            $miaoModel = new MiaoLog();
            $this::startTrans();
            if(!empty($info)){
                $videoType = $video[$type];
                $infoType = ($info[$type]+1);
                if($infoType <= $videoType){
                    #如果达到次数增加喵呗记录
                    $upData = [$type=>$infoType];
                    if($data['type']==5){
                        if(($info['watch_limit']+1) == $video['watch_limit']){ #判断观看视频任务是否完成
                            $miao = $miaoModel->insertGetId(['type'=>1,'uid'=>$data['uid'],'reward'=>$video[$typeNum],'status'=>3,'add_time'=>time(),'end_time'=>time(),'oid'=>$oid]);
                            $upData['watch_limit'] = ($info['watch_limit']+1);
                            $updateInfo = $this->where(['uid'=>$data['uid'],'date'=>$day])->update($upData);
                        }else{
                            $upData = ['watch_limit'=>($info['watch_limit']+1)];
                            $updateInfo = $this->where(['uid'=>$data['uid'],'date'=>$day])->update($upData);
                        }
                    }else{
                        $miao = $miaoModel->insertGetId(['type'=>1,'uid'=>$data['uid'],'reward'=>$video[$typeNum],'status'=>3,'add_time'=>time(),'end_time'=>time(),'oid'=>$oid]);
                        $updateInfo = $this->where(['uid'=>$data['uid'],'date'=>$day])->update($upData);
                    }
                }
            }else{
                if($data['type']==5) $type = 'watch_limit';
                $updateInfo = $this->insertGetId(['uid'=>$data['uid'],'date'=>$day,$type=>1]);
                if($data['type']!=5)
                    $miao = $miaoModel->insertGetId(['type'=>1,'uid'=>$data['uid'],'reward'=>$video[$typeNum],'status'=>3,'add_time'=>time(),'end_time'=>time(),'oid'=>$oid]);
            }
            if($data['type']==5){ #如果观看视频任务未完成，不增加记录和喵呗
                if(empty($info)) {
                    $this::commit();
                    return true;
                }
                if(!empty($info))
                    if($video[$type] <= $info[$type]||($info['watch_limit']+1) != $video['watch_limit']){
                        $this::commit();
                        return true;
                    }
            }
            #给钱包添加喵呗和钱包操作记录
            if(!empty($updateInfo)){
                $operationLog = ['uid'=>$data['uid'],'reward'=>$video[$typeNum],'status'=>1,'type'=>2,'extend'=>'user_video_profit','extend_id'=>($info['id']??$pid),'describe'=>'任务奖励喵呗','add_time'=>time()];
                $mywid = $walletOperationLog->insertGetId($operationLog);
                $model->where(['uid'=>$data['uid'],'is_delete'=>0])->inc('miao',(int)$video[$typeNum])->inc('miaos',(int)$video[$typeNum])->update();
                $this::commit();
                return true;
            }
            exception('数据操作有误!');
        }catch (\Exception $e){
            $this::rollback();
            if(stristr($e->getMessage(),'SQLSTATE')){
                $this->error = '数据走丢了，请稍后再试！';
            }else $this->error = $e->getMessage();
            return false;
        }
    }

    /*
     * 更新记录
     * type 1-发布视频 2-点赞互动视频 3-转发分享视频 4-评论视频 5-视频被观看奖励
     */
    public function updateProfit_1111($data){
        try{
            if(empty($data['uid'])) exception('用户id不能为空!');
            if(empty($data['type'])) exception('类型不能为空!');
            $type = '';
            if($data['type'] == 1) $type = 'post_day';
            if($data['type'] == 2) $type = 'like_day';
            if($data['type'] == 3) $type = 'share_day';
            if($data['type'] == 4) $type = 'comment_day';
            if($data['type'] == 5) $type = 'watch_day';
            if(empty($type)) exception('类型不存在!');
            $day = date('Ymd',time());
            $info = $this->where(['uid'=>$data['uid'],'date'=>$day])->find();
            if(!empty($info)){
                $video = (new Config())->toData('video');
                if(empty($video)||!isset($video[$type])) exception('数据走丢了，请联系客服!');
                $videoType = ($data['type'] == 5) ? $video['watch_limit'] : $video[$type];
                $infoType = ($info[$type]+1);
                if($infoType <= $videoType){
                    $info = $this->where(['uid'=>$data['uid'],'date'=>$day])->update([$type=>$infoType]);
                    if($infoType == $videoType){
                        #如果达到次数增加喵呗记录
                        $type = '';
                        if($data['type'] == 1) $type = 'post_num';
                        if($data['type'] == 2) $type = 'like_num';
                        if($data['type'] == 3) $type = 'share_num';
                        if($data['type'] == 4) $type = 'comment_num';
                        if($data['type'] == 5) $type = 'watch_num';
                        if(empty($type)) exception('类型不存在!');
                        $miao = (new MiaoLog())->insertGetId(['type'=>1,'uid'=>$data['uid'],'reward'=>$video[$type],'status'=>3,'add_time'=>time(),'end_time'=>time()]);
                    }
                }
            }else{
                $info = $this->insertGetId(['uid'=>$data['uid'],'date'=>$day,$type=>1]);
            }
            return true;
        }catch (\Exception $e){
            $this->error = $e->getMessage();
            return false;
        }
    }
}
