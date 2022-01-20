/**
 * Created by snuz on 2020/6/30.
 */

/**
 * 全局常量
 */
var HOST_URL = 'http://www.miaomeimei.com/'

/**
 * 弹窗提示样式
 * title 弹窗标题
 * icon 类型
 * fun 确认后的回调
 * closeFun 关闭后的函数
 */
function layerConfim(fun = false, title = '提示',icon = 6,text = '是否删除此项？',closeFun = false)
{
    layer.confirm('是否删除此项?', {
        title: '提示',
        icon: 5,
        btn: ['确认', '取消'] //可以无限个按钮
        ,yes: function(index, layero){
            layer.close(index)
            //按钮【按钮一】的回调
            if(fun){
                fun()
            }
        },
        btn2: function(index){
            if (!closeFun){
                layer.msg('取消成功',{icon: 6})
            }else {
                layer.close(index)
                closeFun()
            }
        }
    });
}

/**
 * 返回对错的图标
 */
function get_status_str(bool = true){
    return bool ? '<i class="layui-icon ok-icon">&#xe605;</i>' : '<i class="layui-icon ok-icon">&#x1006;</i>';
}

// 预览图片大小
function lookImg(url,str,self)
{
    //var s = encodeURI(url)
    var s = decodeURIComponent(url);
    if(self){
        s = self.getAttribute('data-src')
    }
    layer.open({
        type: 1,
        area: ['80vw','60vh'],
        fix: false, //不固定
        maxmin: true,
        shadeClose: true,
        shade:0.4,
        title: '预览图片中' + (str ? str : '' ),
        content: '<div style="text-align: center;padding-top:20px;"><img src="'+ s + '" /></div>'
    });
}

/**
 * 获取随机日期对象
 * @returns {Date}
 */
function getRandomDateBetween(min = 1,max = 5) { // 生成当前时间一个月内的随bai机时du间。
    var date = new Date();
    var e = date.getTime()-(min*24*60*60*1000);//当前时间的秒数
    var f = date.getTime()-(max*24*60*60*1000); //30天之前的秒数，
    return new Date(RandomNumBoth(f,e))
}

/**
 * 进行切割为需要的日期
 */
function getDateYmdStr(str = ''){
    if(!str) return false;
    var dateList = str.split('/');
    return dateList[0] + '-' + getNumShiJinZhi(dateList[1]) + '-' + getNumShiJinZhi(dateList[2]);
}

/**
 * 根据数字值 进行 填充
 */
function getNumShiJinZhi(num){
    if(num.length >= 2) return num;
    return '0' + num;
}

/**
 * 根据范围随机数生成
 * @param Min
 * @param Max
 * @returns {*}
 * @constructor
 */
function RandomNumBoth(Min,Max){
    var Range = Max - Min;
    var Rand = Math.random();
    var num = Min + Math.round(Rand * Range); //四舍五入
    return num;
}


// ajax封装
function ajaxFun(url,obj,type = 'post',fun,errFun,elem){
    var s = layer.msg('加载中...',{icon:4,time:999999})
    $.ajax({
        url,
        type,
        data:obj,
        dataType:"json",
        success:function (res) {
            if(res.code == 200){
                layer.msg(res.msg ? res.msg : '操作ok', {icon: 1,time:1000});
            }else{
                layer.alert(res.msg, {icon:2,time:1000});
            }
            layer.close(s)
            if(fun) fun(res.data,elem);
        },
        error:function(e){
            layer.close(s)
            layer.alert("网络错误", {icon:5,time:1000});
            if(errFun) errFun(res);
        },
    });
}

//建立一個可存取到該file的url
function getObjectURL(file) {
    var url;
    if (window.createObjectURL != undefined) { // basic
        url = window.createObjectURL(file);
    } else if (window.URL != undefined) { // mozilla(firefox)
        url = window.URL.createObjectURL(file);
    } else if (window.webkitURL != undefined) { // webkit or chrome
        url = window.webkitURL.createObjectURL(file);
    }
    return url;
}

// 根据字符串返回时间戳 并且 有效天数
function getDateTime(date_str){
    var times =  {
        start: new Date(date_str.substr(0,10)).getTime() / 1000,
        end:new Date(date_str.substr(13,date_str.length)).getTime() / 1000,
        day: null
    }
    times.day = (times.end - times.start)/ 3600 /24;
    return times;
}

// 获取到随机数
function randomNum(minNum,maxNum){
    switch(arguments.length){
        case 1:
            return parseInt(Math.random()*minNum+1,10);
            break;
        case 2:
            return parseInt(Math.random()*(maxNum-minNum+1)+minNum,10);
            break;
        default:
            return 0;
            break;
    }
}


/**
 * 封装弹窗中的数据信息
 * 参数：  请求地址， 请求数据， 标题，成功回调函数，错误回调函数
 */
function clickStatusBtn(url,obj,options,fun= false,errFun = false){
    var s = layer.msg('加载中...',{icon:4,time:999999})
    layer.confirm(options.msg,{title: options.title ? options.title : '操作提示',icon: options.icon ? options.icon : 3},function(index){
        $.ajax({
            url,
            type: "post",
            data:obj,
            dataType:"json",
            success:function (res) {
                if(res.code == 200){
                    //捉到所有被选中的，发异步进行删除
                    layer.msg(res.msg ? res.msg : '操作ok', {icon: 1,time:1000});
                    //$(".layui-form-checked").not('.header').parents('tr').remove();
                }else{
                    layer.alert(res.msg, {icon:2,time:1000});
                }
                layer.close(s)
                if(fun) fun(res.data,options.elem);
            },
            error:function(e){
                layer.close(s)
                layer.alert("网络错误", {icon:5,time:1000});
                if(errFun) errFun(res);
            },
        });
    });
}

// 获取到随机数
function randomNum(minNum,maxNum){
    switch(arguments.length){
        case 1:
            return parseInt(Math.random()*minNum+1,10);
            break;
        case 2:
            return parseInt(Math.random()*(maxNum-minNum+1)+minNum,10);
            break;
        default:
            return 0;
            break;
    }
}