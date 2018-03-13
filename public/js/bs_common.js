//本js文件赋值于global.js 部分函数做改动, writre by iWuxc
/**
 * 获取省份, 通过id为不同的城市联动的赋值
 * @param id
 */
function get_i_province(id){
    var url = '/index.php?m=Admin&c=Api&a=getRegion&level=1&parent_id=0';
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option value="0">选择省份</option>'+ v;
            $('#'+id).empty().html(v);
        }
    });
}


/**
 * 获取城市
 * @param t  省份select对象
 */
function get_i_city(t, city, district){
    var parent_id = $(t).val();
    if(!parent_id > 0){
        return;
    }
    $('#'+district).empty().css('display','none');
    // $('#'+twon).empty().css('display','none');
    var url = '/index.php?m=Admin&c=Api&a=getRegion&level=2&parent_id='+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option value="0">选择城市</option>'+ v;
            $('#'+city).empty().html(v);
        }
    });
}

/**
 * 获取地区
 * @param t  城市select对象
 */
function get_i_area(t, district){
    var parent_id = $(t).val();
    if(!parent_id > 0){
        return;
    }
    $('#'+district).empty().css('display','inline');
    // $('#'+twon).empty().css('display','none');
    var url = '/index.php?m=Admin&c=Api&a=getRegion&level=3&parent_id='+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option>选择区域</option>'+ v;
            $('#'+district).empty().html(v);
        }
    });
}

function get_area2(t){
    var parent_id = $(t).val();
    if(!parent_id > 0){
        return;
    }
    $('#twon').empty().css('display','none');
    var url = '/index.php?m=Admin&c=Api&a=getRegion&level=3&parent_id='+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(v) {
            v = '<option>选择区域</option>'+ v;
            $('#district').empty().html(v);
        }
    });
}
// 获取最后一级乡镇
function get_twon(obj){
    var parent_id = $(obj).val();
    // var url = '/index.php?m=Home&c=Api&a=getTwon&parent_id='+ parent_id;
    var url = '/index.php?m=Admin&c=Api&a=getTwon&parent_id='+ parent_id;
    $.ajax({
        type : "GET",
        url  : url,
        success: function(res) {
            if(parseInt(res) == 0){
                $('#twon').empty().css('display','none');
            }else{
                $('#twon').css('display','inline');
                $('#twon').empty().html(res);
            }
        }
    });
}

//获取产业类型城市
function get_industries_province(id, next, select_id){

    var url = '/index.php?m=Admin&c=Users&a=get_industries_province&industries_id='+ id;
    $.ajax({
        type: "GET",
        url: url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(res){
            res = "<option value='0'>选择城市</option>" + res;
            $('#'+next).empty().html(res);
            (select_id > 0) && $('#'+next).val(select_id);//默认选中
        }
    });
}

//获取产业类型区域
function get_industries_city(id, next, select_id){

    var url = '/index.php?m=Admin&c=Users&a=get_industries_city&p_id='+ id;
    $.ajax({
        type: "GET",
        url: url,
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function(res){
            res = "<option value='0'>选择区域</option>" + res;
            $('#'+next).empty().html(res);
            (select_id > 0) && $('#'+next).val(select_id);//默认选中
        }
    });
}

//手机号验证
function checkMobile(tel) {
    //var reg = /(^1[3|4|5|7|8][0-9]{9}$)/;
    var reg = /^1[0-9]{10}$/;
    if (reg.test(tel)) {
        return true;
    }else{
        return false;
    };
}

//港澳台身份证验证
function IsHKID(str) {
    var strValidChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
    // basic check length
    if (str.length < 8)
        return false;
    // handling bracket
    if (str.charAt(str.length-3) == '(' && str.charAt(str.length-1) == ')')
        str = str.substring(0, str.length - 3) + str.charAt(str.length -2);
    // convert to upper case
    str = str.toUpperCase();
    // regular expression to check pattern and split
    var hkidPat = /^([A-Z]{1,2})([0-9]{6})([A0-9])$/;
    var matchArray = str.match(hkidPat);
    // not match, return false
    if (matchArray == null)
        return false;
    return true;
}

//验证护照是否正确
function checknumber(number){
    var str=number;
//在JavaScript中，正则表达式只能使用"/"开头和结束，不能使用双引号
    var Expression=/(P\d{7})|(G\d{8})/;
    var objExp=new RegExp(Expression);
    if(objExp.test(str)==true){
        return true;
    }else{
        return false;
    }
};

/**
 * 唯一性验证
 * @param mobile
 */
function uniqueMobile(mobile){
    var flag = 0;//默认不可用
    $.ajax({
        type : "POST",
        url:"/index.php/Admin/Users/checkMobile",
        data : {'mobile': mobile},
        async:false,
        success: function(res){
            if(res == 'ok'){
                $flag = 1;
            }
        }
    });
    if(flag == 0){
        return false;
    }
    return true;
}