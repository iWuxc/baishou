<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;

use think\Controller;
use think\Request;
use think\Config;

class Base extends Controller {

    protected $_values = '';

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        header('Content-type:text/html;charset=utf-8');
        $this -> _values = $this -> values();
        //$this->_check_token($_REQUEST['api_token']);
        error_reporting('E_ALL^E_NOTICE');
    }

    /**
     * 用于接收值
     * @return Request
     */
    protected function values(){
        $rValue = Request::instance();
        return $rValue;
    }

    /**
     * json输出格式
     * @param $code  错误码
     * @param $msg   错误信息
     * @param string $data  返回的内容
     */
    protected function _toJson($code, $msg, $data=''){
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
        echo json_encode($data);die;
    }

    /**
     * 验证API_TOKEN
     * @param $c_token
     */
    protected function _check_token($c_token){
        if(isset($c_token) && !empty($c_token)){
            $controller = $this -> _getR(1);
            $action = $this -> _getR(2);
            $time = date("Y-m-d", time());
            $key = Config::get('key');
            $api_token = md5($controller.$action.$time.$key);
            if(strtolower($c_token) != strtolower($api_token)){
                $this->_toJson('0', "TOKEN验证错误");
            }
        }else{
            $this->_toJson('0', "TOKEN不能为空");
        }
    }

    /**
     * 获取URL中的值
     * @param $num
     * @return mixed
     */
    protected function _getR($num){
        $module = $this -> _values -> dispatch();
        return $module['module'][$num];
    }

}