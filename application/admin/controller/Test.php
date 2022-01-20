<?php
namespace app\admin\controller;
use app\common\controller\Base;
use app\common\model\Admin;
use app\common\model\OrderGoods;
use app\common\model\UserInvite;

Class Test extends Base{
    public function index(){
        $str = '123,123,123,';
        $str = trim($str,',');
        printData($str);
    }

    public function index1(){
        $arr1 = [1,2,3];
        $arr2 = [1,2,3];
        $arr3 = [];

        printData(array_diff($arr1,$arr2));
    }

    public function index2(){
        $str = str_replace('<p><br/></p>','','<p>123</p><p><br/></p>');
        printData($str);
    }

    public function test(){
        $data = [
            [
                'uid'   =>  2,
                'oid'   =>  7,
                'miao'  =>  3
            ],
            [
            'uid'   =>  2,
            'oid'   =>  7,
            'miao'  =>  6
        ]
        ];

        printData(json_encode($data));
    }

    public function test1(){


        $a = array('1001','1002');
        $b = array('1002','1003','1004');
        $c = array('1003','1004','1005');
        $d = count(array_flip($a) + array_flip($b) + array_flip($c));
        var_dump(array_keys(array_flip( $b)+array_flip($c)));

        $year = isset($data['date']) ? strtotime($data['date']) : strtotime(date('Y-01-01',time()));
        $end =  strtotime(date('Y-01-01',$year)." +1 year");
        var_dump($year,$end,2);

//        $firstday = date("Y-m-01",time());
//        $lastday = date("Y-m-d",strtotime("$firstday +1 month"));
//        var_dump(array($firstday,$lastday),strtotime("$firstday +1 month"));
//        var_dump(strtotime('2012-7'));

//        $test = new UserInvite();
//        $data = $test->loopInvite(2);
//        $last_names = array_column($data,'uid');
//        var_dump($last_names,$data);
//        $a = array_chunk($last_names, 2, true);
//        foreach($a as $v){
//            var_dump(implode(',',$v));
//        }
    }
}