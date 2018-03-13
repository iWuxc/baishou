<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\handerapi\controller;
use think\Db;
use think\Request;
use think\Loader;

class Login extends Base {
    //登录
    public function loginApi(){
        $mobile = $_POST['mobile'];
        $password = encrypt($_POST['password']);
        if (!$mobile) {
            $this -> _toJson("0", "请输入手机号");
        }
        if (!$password) {
            $this -> _toJson("0", "请输入密码");
        }
        $where = array();
        $where['mobile'] = $mobile;
        $where['password'] = $password;
        $check_ids = M('check_user') -> where($where) ->field('check_id') -> select();
        foreach ($check_ids as $key => $value) {
           $check_id = $value;
        }
        if ($check_id) {
            $this -> _toJson('1', '登录成功', $check_id);
        }else{
            $this -> _toJson("0", "账号或密码错误");
        }
    }

    /**
     * 退出操作
     */
    public function logout(){
        $res = $this -> model -> logout($this -> key);
        if($res){
            $this -> _toJson("1", '退出成功');
        }else{
            $this -> _toJson("0", '退出失败');
        }
    }

    /**
     * 找回密码
     */
    public function rePassword(){
        $this -> _check_param("verify,password,mobile");
        $code = $_POST['verify'];
        $password = $_POST['password'];
        $mobile = $_POST['mobile'];
        if(empty($mobile)){
            $this -> _toJson("0", "请输入手机号");
        }elseif(empty($code)){
            $this -> _toJson("0", "请输入验证码");
        }elseif(empty($password)){
            $this -> _toJson("0", "请输入密码");
        }
        $where = array();
        $where['mobile'] = $mobile;
        $verify = M('sms_log')->where($where) -> order('add_time desc')-> limit(1)->select();
        if($verify['0']['code'] != $code){
            $this -> _toJson("0", "验证码错误");
        }else{
            $data = array();
            $data['password'] = encrypt($password);
            $m = M('check_user')->where($where)-> save($data);
            $this -> _toJson("1", "修改成功");
        }
    }

    /**
     * 发送验证码
     */
    public function sendVerify(){
        $mobile = $_POST['mobile'];
        if(empty($mobile)){
            $this -> _toJson("0", "请输入手机号码");
        }
        $code = rand(100000,999999);
        $data = array(
            'code'  =>  $code,
        );

        $result = M('sms_log')->data($data)->add();
        //开始发送短信
        $sms_res = sendSms("2", $mobile, $data); //用户注册
        if($sms_res){
            $this -> _toJson('1','发送成功',array('code'=>$code));
        }else{
            $this -> _toJson('0','发送失败');
        }
    }

}