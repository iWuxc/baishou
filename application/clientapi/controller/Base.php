<?php
/**
 * API基类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\controller;

use think\Controller;
use think\Db;
use Think\Page;
use think\Request;
use think\Config;

class Base extends Controller{

    protected $_values = '';
    public $key = ''; //用户登录唯一认证key

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        header('Content-type:text/html;charset=utf-8');
        $this -> _values = $this -> values();
        $this -> key = $this -> _values -> param('key');
        //$this -> _check_token($this -> _values -> param('api_token'));
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
     * 验证API_TOKEN
     * @param $c_token
     */
    protected function _check_token($c_token){
        if(empty($c_token)){
            $this -> _toJson("0", 'token为空');
        }
        if(isset($c_token) && !empty($c_token)){
            $controller = $this -> _getR(1);
            $action = $this -> _getR(2);
            $time = date("Y-m-d", time());
            $key = Config::get('key');
            $api_token = md5($controller.$action.$time.$key);
            //echo strtolower($api_token);exit;
            if(strtolower($c_token) != strtolower($api_token)){
                $this -> _toJson("0", "token值不匹配");
            }
        }else{
            $this -> _toJson("0", "token值为空");
        }
    }

    /**
     * 验证是否登录
     */
    public function _login(){
        $sql = "select * from bs_sessions where sesskey=?";
        $res = Db::query($sql, array($this->key));
        if($res){
            $this -> _toJson("1", '获取成功');
        }else{
            $this -> _toJson("0", '获取失败');
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

    /**
     * 获取登录凭证
     * @return int
     */
    protected function _getKey(){
        $key = $this->_values->param('key');
        if(isset($key) && !empty($key)){
            $sql = "select `userid` from bs_sessions where sesskey = ?";
            $userid = Db::query($sql, array($this -> _values -> param('key')));
            if(!empty($userid)){
                return $userid[0]['userid'];
            }else{
                return 0;
            }
        }else{
            $this -> _toJson("0", 'key值为空');
            exit;
        }
    }

    /**
     * 获取用户类型
     * @return mixed
     */
    protected function _getType(){
        $type = $this -> _values -> param('user_type');
        $arr_type = array(1,2);
        if(!in_array($type, $arr_type)){
            $this -> _toJson('0', '非法用户类型!');
        }
        return $type;
    }

    /**
     * 检测参数
     * @param $param
     */
    protected function _check_param($param){
        $e = 0;
        if(empty($param)){
            $this -> _toJson('0', '参数错误');
        }
        $arr = explode(',',$param);
        foreach ($arr as $v){
            if(empty($_REQUEST[$v])){
                $e = 1;
                break;
            }
        }
        if($e == 1){
            $this -> _toJson("0", "参数错误");
        }
    }

}