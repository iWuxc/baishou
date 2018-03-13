<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/20
 * Time: 11:18
 */

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
 * 小数点后保留两位
 * @param $price
 * @return string
 */
function numberFormat($price){
    return number_format($price, 2, '.', '');
}

/**
 *  API返回信息格式函数 ；失败：code=0，成功：code=1
 * @param string $code
 * @param string $message
 * @param array $data
 */
function api_response($code = '1', $message = '',$data = array()){
    header('Access-Control-Allow-Origin: *');
    header('Content-Type:application/json; charset=utf-8');
    $result = array(
        'code'  => $code,
        'msg'   => $message,
    );
    if(!empty($data)){
        $result['data'] = $data;
    }
    die(json_encode($result,JSON_UNESCAPED_UNICODE));
}

function api_response_($code = '1', $message = '',$data = array()){
    header('Access-Control-Allow-Origin: *');
    header('Content-Type:application/json; charset=utf-8');
    $result = array(
        'code'  => $code,
        'msg'   => $message,
        'data'  => $data
    );
    die(json_encode($result,JSON_UNESCAPED_UNICODE));
}

/**
 * 参数是否完整
 * @param string $param
 */
function check_param($param = ''){
    if(empty($param)){
        api_response('0',PARAM_INFO);
    }
    $arr = explode(',',$param);
    $error = 0;
    foreach ($arr as $val){
        if(empty($_REQUEST[$val])){
            $error = 1;
            break;
        }
    }
    if($error == 1){
        api_response('0',PARAM_INFO);
    }
}

/**
 * 获取当前url
 * @return string
 */
function get_url(){
    return empty($_SERVER['REQUEST_SCHEME']) ? 'http' . '://' . $_SERVER['HTTP_HOST'] : $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
}

