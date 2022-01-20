<?php
namespace app\common\model;

use think\cache\driver\Redis;

Class Menu extends Base
{
    public $redis_key = 'admin_all_menus';

    public $count = 0;//菜单总数
    //获取一级菜单 id-名称键值对
    public function getMenuColumn(){
        return $this->where(['is_delete'=>0,'status'=>1,'pid'=>0])->column('id,name');
    }

    // 获取到所有菜单  并且写入缓存数据
    public function getAllListMenu(){
        $redis = new Redis();
        if(!$menuArrs = $redis->get($this->redis_key)){
            $menuArrs = $this::field('id,pid,url,name,icon,status')->order('sort desc')->select()->toArray();
            $redis->set($this->redis_key,$menuArrs,3600);
        }
        return $menuArrs;
    }

    /*
     * 获取权限菜单
     * $roleId  权限id
     * */
    public function getMenu($roleId){
        $model = new Role();
        $menuArrs = $this->getAllListMenu();
        $menu = [];
        if($roleId > 1){
            $role = $model->where(['id'=>$roleId])->field('main_menu,sub_menu')->find();
            // pid表示一级菜单
            // menuId 表示子菜单--二级菜单
            $pId = explode(',',$role['main_menu']);
            $menuId = explode(',',$role['sub_menu']);
            // 找出一级
            foreach($menuArrs as $item)if($item['pid'] == 0 && in_array($item['id'],$pId))$menu[$item['id']] = $item;
            // 找出二级
            foreach($menuArrs as $item)
                if($item['pid'] > 0 && $item['status'] == 1 && in_array($item['id'],$menuId) && isset($menu[$item['pid']]))
                    $menu[$item['pid']]['kids'][] = $item;

        }else{
            $menuKeys = [];
            foreach($menuArrs as $item){
                $menuKeys[$item['id']] = $item;
                if($item['pid'] == 0)$menu[$item['id']] = $item;
            }
            foreach($menuKeys as $k => $item) if($item['url'] && $item['status'] == 1) if($item['pid'] > 0 && isset($menuKeys[$item['pid']]) && $menuKeys[$item['pid']]['pid'] == 0 )$menu[$item['pid']]['kids'][] = $item;
        }
        return array_values($menu);
    }

    /*
     * 获取子菜单
     * $pid     菜单父id
     * */
    public function getKidMenu($pid){
        $menu = $this->where(['pid'=>$pid,'is_delete'=>0])->field('id,icon,name,url,pid,status,sort')->order('sort desc')->select();
        return $menu;
    }

    /*
     * 获取所有菜单
     * */
    public function getMenuList(){
        $menu = $this->getKidMenu(0);
        $this->count = count($menu);
        if($menu){
            foreach ($menu as &$val){
                $val['son'] = $this->getKidMenu($val['id']);
                $this->count += count($val['son']);
            }unset($val);
        }

        return $menu;
    }

    /*
     * 获取单条数据
     * $id    主键ID
     * */
    public function getOne($id){
        return $this->where(['id'=>$id])->find();
    }

    /*
     * 修改，新增菜单
     * $data    菜单数据
     * */
    public function addMenu($data){
        try{
            if(empty($data['pid']) || !is_numeric($data['pid']))
                $data['pid'] = 0;
            if(empty($data['name']))
                exception('菜单名不能为空');
            if($data['pid'] == 0){
                if(empty($data['icon']))
                    exception('请选择图标');
            }else{
                if(empty($data['url']))
                    exception('菜单地址不能为空');
            }

            $save = [
                'pid'           =>  $data['pid'],
                'name'          =>  $data['name'],
                'url'           =>  empty($data['url'])?'':$data['url'],
                'sort'          =>  $data['sort']>0?$data['sort']:0,
                'icon'          =>  empty($data['icon'])?0:$data['icon'],
                'status'        =>  $data['status'],
            ];
            $msg = '菜单添加成功';
            if(empty($data['id'])){
                if($this->where(['pid'=>$data['pid'],'name'=>$data['name'],'is_delete'=>0])->find())
                    exception('菜单名称不可重复');
                $save['add_time'] = time();
                $this->allowField(true)->save($save);
            }else{
                if(!$this->where(['id'=>$data['id'],'is_delete'=>0])->find())
                    exception('菜单错误');
                if($this->where(['pid'=>$data['pid'],'name'=>$data['name'],'id'=>['neq',$data['id']],'is_delete'=>0])->find())
                    exception('菜单名称不可重复');

                $this->allowField(true)->isUpdate(true)->save($save,['id'=>$data['id']]);
                $msg = '菜单修改成功';
            }
            // 删除对应的缓存
            $redis = new Redis();
            $redis->del($this->redis_key);
            return_ajax(200,$msg);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }

    }

    /*
     * 菜单启用，禁用
     * */
    public function MenuStatus($id){
        try{
            if(!is_numeric($id))
                exception('非法操作');
            $menu = $this->getOne($id);
            if(empty($menu))
                return_ajax(400,'菜单错误');
            $data['status'] = 2;
            $msg = '菜单已隐藏';
            if($menu['status'] == 2){
                $data['status'] = 1;
                $msg = '菜单已显示';
            }
            $this->allowField(true)->isUpdate(true)->save($data,['id'=>$id]);
            // 删除对应的缓存
            $redis = new Redis();
            $redis->del($this->redis_key);
            return_ajax(200,$msg,$data);
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    /*
     * 菜单删除
     * */
    public function MenuDel($id){
        try{
            if(!is_numeric($id))
                exception('非法操作');
            $menu = $this->getOne($id);
            if(empty($menu))
                return_ajax(400,'菜单错误');
            if($id == 1 || $menu['pid'] == 1)
                exception('暂无权限');

            $this->allowField(true)->isUpdate(true)->save(['is_delete'=>1],['id'=>$id]);
            // 删除对应的缓存
            $redis = new Redis();
            $redis->del($this->redis_key);
            return_ajax(200,'菜单已删除');
        }catch (\Exception $e){
            return_ajax(400,$e->getMessage());
        }
    }

    //采取菜单的二级菜单的一级菜单集合
    public function supply_roles($menu){
        $main_menu = [];
        $data = $this->where(['id'=>['in',$menu]])->distinct(true)->field('pid')->select();
        if(!empty($data)){
            foreach ($data as $val){
                $main_menu[] = $val['pid'];
            }
        }
        return $main_menu;
    }

    // 根据数组传递 判断哪些是没有的数据 ，如果没有数据  则表示会自动更新控制器 并且会删除不存在的控制器
    public function queryMenu($sonlist,$allControllerList){
        // 拿取全部数据
        $menuList = $this->field('id,name,url,pid,is_delete')->select();
        $menuKeyList = [];
        $bool = is_array($allControllerList) && count($allControllerList) > 0;
        // 遍历组合数组  并且 自动删除开始
        $delIds = [];
        $delStr = '';
        $delNum = 0;
        // 纠正的控制器
        $zqStr = '';
        $zqNum = 0;
        $updatIds = [];
        foreach($menuList as $v){
            $menuKeyList[strtolower($v['url'])] = $v;
            if($bool && !in_array($v['url'],$allControllerList)){
                $delIds[] = $v['id'];
                $delStr .= $v['name'].'--';
                $delNum++;
            }
            // 如果 为两个 且 当前pid > 0 自动更新
            if(count(explode('/',$v['url'])) == 3 && $v['pid'] > 0){
                $updatIds[] = $v['id'];
                $zqStr .= '一级菜单：'.$v['name'].',';
                $zqNum++;
            }
        }
        if($delNum > 0)$delStr .= '，删除'.$delNum.'个菜单';

        // 删除多余的控制器
        if($delIds && count($delIds) > 0)$this::whereIn('id',$delIds)->delete();
        // 纠正一级菜单
        if($updatIds && count($updatIds) > 0)$this::whereIn('id',$updatIds)->update(['pid' => 0]);

        // 自动增加的控制器
        $addStr = '';
        $addNum = 0;

        // 自动更新   自动更改上级  遍历迭代开始
        $time = time();
        foreach($sonlist as $k => $v){
            // 一级菜单---最外面的展示
            $url = '/admin/'.$k;
            $name = $url;
            // 如果没有 则 插入数据
            if(!isset($menuKeyList[strtolower($url)])){
                $pid = $this::insertGetId([
                    'url' => $url,
                    'name' => $url,
                    'pid' => 0,
                    'add_time' => $time
                ]);
                // 存在则拿取值
            }else{
                $name = $menuKeyList[$url]['name'];
                $pid = $menuKeyList[$url]['id'];
            }
            // 再次遍历
            if($v && is_array($v) && count($v)){
                foreach($v as $i){
                    if($i == $url)continue;
                    $x_i = strtolower($i);
                    // 如果没有 进行插入数据
                    if(!isset($menuKeyList[$x_i])){
                        $this::insert([
                            'url' => $i,
                            'name' => $name.'-'.$i,
                            'pid' => $pid,
                            'status' => 2,
                            'add_time' => $time
                        ]);
                        $addStr .= $name.',';
                        $addNum++;
                        // 如果存在 且 父级id 不相同
                    }else if($menuKeyList[$x_i]['pid'] != $pid){
                        $this::where('id',$menuKeyList[$x_i]['id'])->update([
                            'pid' => $pid
                        ]);
                        $zqStr .= $menuKeyList[$x_i]['name'];
                        $zqNum++;
                        // 如果被删除了 那么还原
                    }else if($menuKeyList[$x_i]['is_delete'] != 0){
                        $this::where('id',$menuKeyList[$x_i]['id'])->update([
                            'is_delete' => 0
                        ]);
                        $zqStr .= $menuKeyList[$x_i]['name'].',';
                        $zqNum++;
                    }
                }
            }
        }
        if($zqNum > 0)$zqStr .='，纠正了'.$zqNum.'个控制器';
        if($addNum > 0)$addStr .='，增加了'.$zqNum.'个控制器';
        // 删除对应的缓存
        $redis = new Redis();
        $redis->del($this->redis_key);
        return [
            'del_str' => $delStr,
            'add_str' => $addStr,
            'zq_str' => $zqStr
        ];
    }
}