/** 添加企业后返回到个人信息添加页面专用 */

/**
 * ajax 提交表单 到后台去验证然后回到前台提示错误
 * 验证通过后,再通过 form 自动提交
 */
before_request = 1; // 标识上一次ajax 请求有没回来, 没有回来不再进行下一次
function ajax_submit_form1(form_id,submit_url){

    if(before_request == 0)
        return false;

    $("[id^='err_']").hide();  // 隐藏提示
    $.ajax({
        type : "POST",
        url  : submit_url,
        data : $('#'+form_id).serialize(),// 你的formid
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
        },
        success: function(v) {
            before_request = 1; // 标识ajax 请求已经返回
            var v =  eval('('+v+')');
            // 验证成功提交表单
            if(v.hasOwnProperty('status'))
            {
                // layer.alert(v.msg);
                layer.msg(v.msg, {
                    icon: 1,   // 成功图标
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function(){
                    if(v.status == 1)
                    {
                        if(v.hasOwnProperty('data')){
                            if(v.data.hasOwnProperty('ent_id')){
                                javascript:window.parent.call_back(v.data.ent_id);
                            }
                        }
                        return true;
                    }
                    if(v.status == 0)
                    {
                        return false;
                    }
                    //return false;
                });
            }
            // 验证失败提示错误
            for(var i in v['data'])
            {
                $("#err_"+i).text(v['data'][i]).show(); // 显示对于的 错误提示
            }
        }
    });
    before_request = 0; // 标识ajax 请求已经发出
}

/**
 * 企业信息更改专用
 * @param form_id
 * @param submit_url
 * @returns {boolean}
 */
function ajax_ent_submit_form(form_id,submit_url){
    if(before_request == 0)
        return false;

    $("[id^='err_']").hide();  // 隐藏提示
    $.ajax({
        type : "POST",
        url  : submit_url,
        data : $('#'+form_id).serialize(),// 你的formid
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
        },
        success: function(v) {
            before_request = 1; // 标识ajax 请求已经返回
            var v =  eval('('+v+')');
            // 验证成功提交表单
            if(v.hasOwnProperty('status'))
            {
                //layer.alert(v.msg);
                layer.msg(v.msg, {
                    icon: 1,   // 成功图标
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function(){
                    if(v.status == 1)
                    {
                        if(v.hasOwnProperty('data')){
                            if(v.data.hasOwnProperty('url')){
                                location.href = v.data.url;
                            }else{
                                location.href = location.href;
                            }
                        }else{
                            location.href = location.href;
                        }
                        return true;
                    }
                    if(v.status == 0)
                    {
                        return false;
                    }
                    //return false;
                });


            }
            // 验证失败提示错误
            for(var i in v['data'])
            {
                $("#err_"+i).text(v['data'][i]).show(); // 显示对于的 错误提示
            }
        }
    });
    before_request = 0; // 标识ajax 请求已经发出
}

/**
 * 云信接口数据提交按钮
 * @param form_id
 * @param submit_url
 * @returns {boolean}
 */
function ajax_lend_form(form_id,submit_url,btn_id){
    if(before_request == 0)
        return false;

    $("[id^='err_']").hide();  // 隐藏提示
    $.ajax({
        type : "POST",
        url  : submit_url,
        data : $('#'+form_id).serialize(),// 你的formid
        beforeSend: function(){
            //触发ajax请求开始时执行
            $("#"+btn_id).text('提交中...');
            $('#'+btn_id).attr('onclick','javascript:void();');//改变提交按钮上的文字并将按钮设置为不可点击
        },
        error: function(request) {
            $("#"+btn_id).text('确认添加');
            $('#'+btn_id).attr('onclick',"ajax_lend_form('form1','/index.php/admin/LendConf/handle/is_ajax/1','LendBtn'");
            alert("服务器繁忙, 请联系管理员!");
        },
        success: function(v) {
            before_request = 1; // 标识ajax 请求已经返回
            var v =  eval('('+v+')');
            // 验证成功提交表单
            if(v.hasOwnProperty('status'))
            {
                layer.msg(v.msg, {
                    icon: 1,   // 成功图标
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function(){
                    if(v.status == 1)
                    {
                        if(v.hasOwnProperty('data')){
                            if(v.data.hasOwnProperty('url')){
                                location.href = v.data.url;
                            }else{
                                location.href = location.href;
                            }
                        }else{
                            location.href = location.href;
                        }
                        return true;
                    }
                    $("#"+btn_id).text('确认添加');
                    $('#'+btn_id).attr('onclick',"ajax_lend_form('form1','/index.php/admin/LendConf/handle/is_ajax/1','LendBtn')");
                    return false;
                });


            }
            // 验证失败提示错误
            for(var i in v['data'])
            {
                $("#err_"+i).text(v['data'][i]).show(); // 显示对于的 错误提示
            }
        }
    });
    before_request = 0; // 标识ajax 请求已经发出
}

function credit_submit_form(form_id,submit_url){

    if(before_request == 0)
        return false;

    $("[id^='err_']").hide();  // 隐藏提示
    $.ajax({
        type : "POST",
        url  : submit_url,
        data : $('#'+form_id).serialize(),// 你的formid
        error: function(request) {
            alert("服务器繁忙, 请联系管理员!");
        },
        success: function(v) {
            before_request = 1; // 标识ajax 请求已经返回
            var v =  eval('('+v+')');
            // 验证成功提交表单
            if(v.hasOwnProperty('status'))
            {
                // layer.alert(v.msg);
                layer.msg(v.msg, {
                    icon: 1,   // 成功图标
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function(){
                    if(v.status == 1)
                    {
                        if(v.hasOwnProperty('data')){
                            if(v.data.hasOwnProperty('user_id')){
                                javascript:window.parent.call_back(v.data.user_id);
                            }
                        }
                        return true;
                    }
                    if(v.status == 0)
                    {
                        return false;
                    }
                    return false;
                });
            }
            // 验证失败提示错误
            for(var i in v['data'])
            {
                $("#err_"+i).text(v['data'][i]).show(); // 显示对于的 错误提示
            }
        }
    });
    before_request = 0; // 标识ajax 请求已经发出
}

function return_client(){
    var data = getCookie('ent_id');
    delCookie('ent_id')
    javascript:window.parent.call_back(data);
}

function credit_handle(form_id,submit_url,btn_id){
    $("[id^='err_']").hide();  // 隐藏提示
    $.ajax({
        type : "POST",
        url  : submit_url,
        data : $('#'+form_id).serialize(),// 你的formid
        beforeSend: function(){
            //触发ajax请求开始时执行
            $("#"+btn_id).text('正在提交中...');
            $('#'+btn_id).attr('onclick','javascript:void();');//改变提交按钮上的文字并将按钮设置为不可点击
        },
        success: function(v) {
            var v =  eval('('+v+')');
            // 验证成功提交表单
            if(v.hasOwnProperty('status'))
            {
                // layer.alert(v.msg);
                layer.msg(v.msg, {
                    icon: 1,   // 成功图标
                    time: 2000 //2秒关闭（如果不配置，默认是3秒）
                }, function(){
                    if(v.status == 1)
                    {
                        if(v.hasOwnProperty('data')){
                            if(v.data.hasOwnProperty('user_id')){
                                javascript:window.parent.call_back(v.data.user_id);
                            }
                        }
                        return true;
                    }
                    $("#"+btn_id).text('提交并申请复核');
                    $('#'+btn_id).attr('onClick',"credit_handle('creditAdd','/index.php/admin/Credit/credit_conclu_handle/is_ajax/1','submitBtn')");
                    return false;
                });
            }
            // 验证失败提示错误
            for(var i in v['data'])
            {
                $("#err_"+i).text(v['data'][i]).show(); // 显示对于的 错误提示
            }
        },
        error:function(){
            layer.alert('网络异常', {icon: 2,time: 3000});
            $("#"+btn_id).text('提交并申请复核');
            $('#'+btn_id).attr('onclick',"act_submit(2, {$credit['id']})");
        },
    });
}

