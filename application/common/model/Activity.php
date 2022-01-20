<?php
namespace app\common\model;

Class Activity extends Base
{
    public $page = '';//分页变量
    public $count = '';//数据总数
    
    public function getNameAttr($value){
        return emojiDecode($value);
    }

    /**
     * 预处理数据
     */
    public function getEndTimeAttr($value)
    {
        return date('Y-m-d',$value);
    }

    //获取列表
    public function getList($get){
        //初始判断条件
        $where = ['is_delete'=>0];
        //活动名称
        if(!empty($get['name']))
            $where['name'] = ['like',$get['name'].'%'];
        //活动状态
        if(!empty($get['status'])){
            if($get['status'] == -1){
                $where['status'] = 0;
            }else{
                $where['status'] = 1;
            }
        }
        if(isset($get['start']) && strlen($get['start']) > 0)
            $where['end_time'] = ['<=',strtotime($get['start'])];
        //查询内容
        $field = 'id,name,cover,status,add_time,sort,num,hours,goods,end_time';
        //查询结果
        $list = $this->where($where)->field($field)->order('sort desc')
            ->paginate(20,false,array('query'=>$get));
        $this->page = $list->render();
        $this->count = $this->where($where)->count();

        return $list;
    }

    public function addActivity($data,$file){
        try{
            if(empty($data['name']))
                exception('活动名称不能为空');
            if(empty($data['title']))
                exception('活动分享标题不能为空');
            if(empty($data['goods']))
                exception('活动商品不能为空');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            if(empty($data['hours']) || !is_numeric($data['hours']) || $data['hours'] <= 0)
                exception('请填写正确的活动有效时间');
            if(empty($data['participate_num']) || !is_numeric($data['participate_num']) || $data['participate_num'] <= 0)
                $data['participate_num'] = 0;
            if(empty($data['num']) || !is_numeric($data['num']) || $data['num'] <= 0)
                exception('请填写正确的活动推广人数');
            // if(empty($file['cover']['name']))
            //     exception('请上传活动封面图');
            // if(empty($file['file']['name']))
            //     exception('请上传活动分享图');
            if(empty($data['cover']))
                exception('请上传活动封面图');
            if(empty($data['share']))
                exception('请上传活动分享图');
            if(empty($data['detailsImg']))
                exception('请上传活动详情图');
            if(empty($data['editorValue']))
                exception('活动规则不能为空');
            if(empty($data['end_time']))
                exception('请选择拼团结束时间');
            if(empty($data['btn_str']))
                exception('请输入按钮文字');    
            if($this->where(['name'=>$data['name'],'is_delete'=>0])->find())
                exception('此活动名称已经被使用');

            $conent = [];
            for($i=0;$i<$data['num'];$i++){
                $p = $i+1;
                if(empty($data['val'][$i]))
                    exception('第'.$p.'人佣金为空');
                $conent[$i] = $data['val'][$i];
            }

            // $cover = imgUpdate('cover','activity');//活动封面图上传
            // if($cover['code'] == 400)
            //     exception($cover['msg']);

            // $res = imgUpdate('file','activity');//活动分享图上传
            // if($res['code'] == 400)
            //     exception($res['msg']);
            $time_str = strtotime($data['end_time']);
            $save = [
                'name'          =>  emojiEncode($data['name']),
                'title'         =>  $data['title'],
                'goods'         =>  str_replace('，',',',$data['goods']),
                'sort'          =>  $data['sort'],
                'hours'         =>  $data['hours'],
                'num'           =>  $data['num'],
                'rule'          =>  htmlspecialchars($data['editorValue']),
                'deliver'       =>  $data['deliver']==1?1:2,
                'type'          =>  $data['type']==1?1:2,
                'status'        =>  $data['status']==1?1:0,
                'cover'         =>  $data['cover'],
                'share'         =>  $data['share'],
                'participate_num' => $data['participate_num'],
                'content'       =>  json_encode($conent),
                'add_time'      =>  time(),
                'end_time'      => $time_str,
                'desc'          => isset($data['desc']) ? emojiEncode($data['desc']) : '',
                'detailsImg'              => isset($data['detailsImg']) ? $data['detailsImg'] : '',
                'btn_str'       => isset($data['btn_str']) ? $data['btn_str'] : ''
            ];

            // 更新活动结束时间
            $this::where('status',1)->update([
                'end_time' => $time_str
            ]);

            $goodIds = explode(',',$save['goods']);
            $goosData = [];

            $allList = $this->field('id,goods,name')->select()->toArray();
            // 过滤活动
            foreach($goodIds as $i){
                if(!is_numeric($i))
                    exception('只能为数字');
                foreach($allList as $item){
                    $ids = explode(',',$item['goods']);
                    if($ids && in_array($i,$ids)){
                        exception('当前商品id:'.$i.'已经存在活动：'.$item['name'].'中');
                    }
                }
                if(!Goods::where('id',$i)->count())
                    exception('当前商品id:'.$i.'不存在，麻烦重新选择一个');
                $goosData[] = [
                    'id' => $i,
                    'is_activity' => 1 // 表示1
                ];
            }

            // 开启事务
            $this::startTrans();
            $goodsModel = new Goods();
            // 更新 设置为
            //count($goosData) == 1 ? $goodsModel->isUpdate(true)->allowField(true)->save($goosData[0]) : $goodsModel->isUpdate(true)->allowField(true)->saveAll($goosData);
            $goodsModel->isUpdate(true)->allowField(true)->saveAll($goosData);
            $this->allowField(true)->save($save);
            $this::commit();
            return_ajax(200,'活动添加成功');
        }catch (\Exception $e){
            $this::rollback();
            return_ajax(400,$e->getMessage());
        }
    }

    public function editActivity($data,$file){
        try{
            if(empty($data['name']))
                exception('活动名称不能为空');
            if(empty($data['title']))
                exception('活动分享标题不能为空');
            if(empty($data['goods']))
                exception('活动商品不能为空');
            if(empty($data['sort']) || !is_numeric($data['sort']) || $data['sort'] < 0)
                $data['sort'] = 0;
            if(empty($data['hours']) || !is_numeric($data['hours']) || $data['hours'] <= 0)
                exception('请填写正确的活动有效时间');
            if(empty($data['num']) || !is_numeric($data['num']) || $data['num'] <= 0)
                exception('请填写正确的活动推广人数');
            if(empty($data['participate_num']) || !is_numeric($data['participate_num']) || $data['participate_num'] <= 0)
                $data['participate_num'] = 0;
            if(empty($data['editorValue']))
                exception('活动规则不能为空');
            if(empty($data['cover']))
                exception('请上传活动封面图');
            if(empty($data['share']))
                exception('请上传活动分享图');    
            if(empty($data['detailsImg']))
                exception('请上传活动详情图');    
            if(empty($data['end_time']))
                exception('请选择拼团结束时间');    
            if(empty($data['last_goods']))
                exception('前期数据不得为空');    
            if(empty($data['btn_str']))
                exception('请输入按钮文字');     

            $conent = [];
            for($i=0;$i<$data['num'];$i++){
                $p = $i+1;
                if(empty($data['val'][$i]))
                    exception('第'.$p.'人佣金为空');
                $conent[$i] = $data['val'][$i];
            }
            $time_str = strtotime($data['end_time']);
            $save = [
                'name'          =>  emojiEncode($data['name']),
                'title'         =>  $data['title'],
                'goods'         =>  str_replace('，',',',$data['goods']),
                'sort'          =>  $data['sort'],
                'hours'         =>  $data['hours'],
                'num'           =>  $data['num'],
                'rule'          =>  htmlspecialchars(str_replace('<p><br/></p>','',$data['editorValue'])),
                'deliver'       =>  $data['deliver']==1?1:2,
                'type'          =>  $data['type']==1?1:2,
                'status'        =>  $data['status']==1?1:0,
                'participate_num' => $data['participate_num'],
                'content'       =>  json_encode($conent),
                'end_time'      => $time_str,
                'cover'         =>  $data['cover'],
                'share'         =>  $data['share'],
                'desc'          => isset($data['desc']) ? emojiEncode($data['desc']) : '',
                'detailsImg'              => isset($data['detailsImg']) ? $data['detailsImg'] : '',
                'btn_str'       => isset($data['btn_str']) ? $data['btn_str'] : ''
            ];

            // 更新活动结束时间
            $this::where('status',1)->update([
                'end_time' => $time_str
            ]);

            $allList = $this->field('id,goods,name')->select()->toArray();
            $goodIds = explode(',',$data['goods']);
            $goosData = [];

            // 将以前的设置为 0
            $lastGoodIds = explode(',',$data['last_goods']);
            // 过滤活动
            foreach($goodIds as $i){
                $i = $i - 0;
                if(!is_numeric($i - 0))
                    exception('只能为数字');
                foreach($allList as $item){
                    if($item['id'] == $data['id'])
                        continue;
                    $ids = explode(',',$item['goods']);
                    if($ids && in_array($i,$ids)){
                        exception('当前商品id:'.$i.'已经存在活动：'.$item['name'].'中');
                    }
                }
                // 当前已经存在前期数据中
                if(in_array($i,$lastGoodIds))
                    continue;
                if(!Goods::where('id',$i)->count())
                    exception('当前商品id:'.$i.'不存在，麻烦重新选择一个');
                $goosData[] = [
                    'id' => $i,
                    'is_activity' => 1 // 表示1
                ];
            }

            foreach($lastGoodIds as $item){
                // 如果不等于 数字  或者  当前已经存在前期数据中
                if(!is_numeric($item) || in_array($item,$goodIds))
                    continue;
                $goosData[] = [
                    'id' => $item,
                    'is_activity' => 0 // 表示0
                ];
            }
            // if(!empty($file['cover']['name'])){
            //     $cover = imgUpdate('cover','activity');//活动封面图上传
            //     if($cover['code'] == 400)
            //         exception($cover['msg']);
            //     $save['cover'] = $cover['data'];
            // }
            // if(!empty($file['file']['name'])){
            //     $res = imgUpdate('file','activity');//活动分享图上传
            //     if($res['code'] == 400)
            //         exception($res['msg']);
            //     $save['share'] = $res['data'];
            // }

//            dump($file['cover']);
//
//            exit;
            // 开启事务
            $this::startTrans();
            $goodsModel = new Goods();
            // 更新 设置为
            $goodsModel->isUpdate(true)->allowField(true)->saveAll($goosData);
            $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
            $this::commit();
            return_ajax(200,'活动修改成功');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //获取活动名称
    public function getName($id){
        return $this->where(['id'=>$id])->value('name');
    }

    //获取活动id-名称键值对
    public function getListName(){
        return $this->where(['status'=>1])->column('id,name');
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
                $status = 0;
            }
            $this->allowField(true)->isUpdate(true)->save(['status'=>$status],['id'=>$data['id']]);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //获取主键数据信息
    public function getOne($id){
        $data = $this->where(['id'=>$id])->find();
        $data['rule'] = htmlspecialchars_decode($data['rule']);
        $data['content'] = json_decode($data['content'],true);
        //$data['end_time'] = date('Y-m-d',$data['end_time']);
        return $data;
    }
}