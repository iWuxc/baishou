<?php
/**
 * 公共函数
 * Author: iWuxc
 * time 2017-12-20
 */

/**
 * json输出格式
 * @param $code  错误码
 * @param $msg   错误信息
 * @param string $data  返回的内容
 */
function toJson($code, $msg, $data=''){
    header('Access-Control-Allow-Origin: *');
    header('Content-Type:application/json; charset=utf-8');
    //当data为空时
    $str = array(
        'code' => $code,
        'msg' => $msg
    );

    //当data不为空时
    $array = array(
        'code' => $code,
        'msg' => $msg,
        'data' => $data
    );
    $data = $data == '' ? $str : $array;
    echo json_encode($data, JSON_UNESCAPED_UNICODE);exit;
}

/**
 * 获取当前url
 * @return string
 */
function get_url(){
    return empty($_SERVER['REQUEST_SCHEME']) ? 'http' . '://' . $_SERVER['HTTP_HOST'] : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
}

/**
 * @param int $flag 0数字字符混合 1字符 2数字
 * @param int $num 验证标识的个数
 * @return string
 */
function get_vc($num = 0, $flag = 2) {
    /**获取验证标识**/
    $arr = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',1,2,3,4,5,6,7,8,9,0);
    $vc  = '';
    switch($flag) {
        case 0 : $s = 0;  $e = 61; break;
        case 1 : $s = 0;  $e = 51; break;
        case 2 : $s = 52; $e = 61; break;
    }

    for($i = 0; $i < $num; $i++) {
        $index = rand($s, $e);
        $vc   .= $arr[$index];
    }
    return $vc;
}

/**
 * 截取密码
 * @param $id_number
 * @param int $length
 * @return bool|string
 */
function get_passwd($id_number, $length = 0){
    if($length == 0){
        return false;
    }
    $len = strlen($id_number);
    if($len < $length){
        return $id_number;
    }else{
        return substr($id_number, -$length);
    }

}

function getIDCardInfo($IDCard,$format=1){
    $result['error']=0;//0：未知错误，1：身份证格式错误，2：无错误
    $result['flag']='';//0标示成年，1标示未成年
    $result['tdate']='';//生日，格式如：2012-11-15
    if(!preg_match("/^(\d{15}$|^\d{18}$|^\d{17}(\d|X|x))$/",$IDCard)){
        $result['error']=1;
        return $result;
    }else{
        if(strlen($IDCard)==18)
        {
            $tyear=intval(substr($IDCard,6,4));
            $tmonth=intval(substr($IDCard,10,2));
            $tday=intval(substr($IDCard,12,2));
        }
        elseif(strlen($IDCard)==15)
        {
            $tyear=intval("19".substr($IDCard,6,2));
            $tmonth=intval(substr($IDCard,8,2));
            $tday=intval(substr($IDCard,10,2));
        }

        if($tyear>date("Y")||$tyear<(date("Y")-100))
        {
            $flag=0;
        }
        elseif($tmonth<0||$tmonth>12)
        {
            $flag=0;
        }
        elseif($tday<0||$tday>31)
        {
            $flag=0;
        }else
        {
            if($format)
            {
                $tdate=$tyear."-".$tmonth."-".$tday;
            }
            else
            {
                $tdate=$tmonth."-".$tday;
            }

            if((time()-mktime(0,0,0,$tmonth,$tday,$tyear))>18*365*24*60*60)
            {
                $flag=0;
            }
            else
            {
                $flag=1;
            }
        }
    }
    $result['error']=2;//0：未知错误，1：身份证格式错误，2：无错误
    $result['isAdult']=$flag;//0标示成年，1标示未成年
    $result['birthday']=$tdate;//生日日期
    return $result;
}

/**
 * 获取用户类型
 * @return mixed
 */
function get_type(){
    $type = request() -> param('user_type');
    $arr_type = array(1,2);
    if(!in_array($type, $arr_type)){
        toJson('0', '非法用户类型!');
    }
    return $type;
}

/*
 * 手机号码格式化
 */
function format_tel($mobile){
    preg_match('/([\d]{3})([\d]{4})([\d]{4})/', $mobile,$match);
    unset($match[0]);
    return implode(' ', $match);
}

/*
 * 新增店员验证证件类型(app返回的是字符串)
 */
function is_credential($str){
    $credential = array(
        '身份证',
        '港澳台身份证',
        '护照',
        '其他'
    );
    if(empty($str) || !in_array(trim($str), $credential)){
        return false;
    }
    return true;
}

/*
 * 身份证验证
 */
function validation_filter_id_card($id_card){
    if(strlen($id_card)==18){
        return idcard_checksum18($id_card);
    }elseif((strlen($id_card)==15)){
        $id_card=idcard_15to18($id_card);
        return idcard_checksum18($id_card);
    }else{
        return false;
    }
}
// 计算身份证校验码，根据国家标准GB 11643-1999
function idcard_verify_number($idcard_base){
    if(strlen($idcard_base)!=17){
        return false;
    }
    //加权因子
    $factor=array(7,9,10,5,8,4,2,1,6,3,7,9,10,5,8,4,2);
    //校验码对应值
    $verify_number_list=array('1','0','X','9','8','7','6','5','4','3','2');
    $checksum=0;
    for($i=0;$i<strlen($idcard_base);$i++){
        $checksum += substr($idcard_base,$i,1) * $factor[$i];
    }
    $mod=$checksum % 11;
    $verify_number=$verify_number_list[$mod];
    return $verify_number;
}
// 将15位身份证升级到18位
function idcard_15to18($idcard){
    if(strlen($idcard)!=15){
        return false;
    }else{
        // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
        if(array_search(substr($idcard,12,3),array('996','997','998','999')) !== false){
            $idcard=substr($idcard,0,6).'18'.substr($idcard,6,9);
        }else{
            $idcard=substr($idcard,0,6).'19'.substr($idcard,6,9);
        }
    }
    $idcard=$idcard.idcard_verify_number($idcard);
    return $idcard;
}
// 18位身份证校验码有效性检查
function idcard_checksum18($idcard){
    if(strlen($idcard)!=18){
        return false;
    }
    $idcard_base=substr($idcard,0,17);
    if(idcard_verify_number($idcard_base)!=strtoupper(substr($idcard,17,1))){
        return false;
    }else{
        return true;
    }
}

//贷款金额类别名称
function loan_level($amount){
    //定义金额范围
    $L1 = 0;
    $L2 = 500000;
    $L3 = 5000000;
    $N1 = '安家银元';
    $N2 = '安家金条';
    if($amount > $L1 && $amount <= $L2){
        return $N1;
    }elseif($amount > $L2 && $amount <= $L3){
        return $N2;
    }
}