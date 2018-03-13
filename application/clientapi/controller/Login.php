<?php

/**
 * 登录api
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\controller;

use think\Db;
use think\Loader;
use think\Request;

class Login extends Base {

    protected $model;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> model = Loader::model('Users');
    }

    public function loginApi(){
        $mobile = $this -> _values -> param('mobile');
        $passwd = $this -> _values -> param('passwd');
        $cid = $this -> _values -> param('cid');
        $uArr = $this -> model -> login($mobile, $passwd, $cid);
        $arr = (object)array();
        switch($uArr){
            case 1:
                $this -> _toJson('0', '请输入账户名和密码', $arr);
                break;
            case 2:
                $this -> _toJson('0', '账号或密码不正确', $arr);
                break;
            case 3:
                $this -> _toJson('0', '用户已冻结', $arr);
                break;
            case 4:
                $this -> _toJson('0', '用户登录失败', $arr);
                break;
            default:
                $this -> _toJson('1', '登录成功', $uArr);
                break;
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
        $this -> _check_param("verify,passwd,mobile");
        $code = $this -> _values -> param('verify');
        $passwd = $this -> _values -> param('passwd');
        $mobile = $this -> _values -> param('mobile');
        if(empty($mobile)){
            $this -> _toJson("0", "请输入手机号");
        }elseif(empty($code)){
            $this -> _toJson("0", "请输入验证码");
        }elseif(empty($passwd)){
            $this -> _toJson("0", "请输入密码");
        }
        $m = Db::name('users')->where('mobile',$mobile)->value('mobile');
        if(!$m){
            $m = Db::name('user_store_clerk')->where('mobile',$mobile)->value('mobile');
            !$m && $this -> _toJson("0", "手机号未注册");
        }
        $where['mobile'] = $mobile;
        $where['scene'] = 2;
        $verify = Db::name('sms_log')->where($where)->value('code');
        if($verify != $code){
            $this -> _toJson("0", "验证码错误");
        }else{
            $result = Db::name('users')->where('mobile',$mobile)->update(['password'=>encrypt($passwd),'update_time'=>time()]);
            if(!$result){
                $result = Db::name('user_store_clerk')->where('mobile',$mobile)->update(['password'=>encrypt($passwd),'update_time'=>time()]);
                !$result && $this -> _toJson("0", "修改失败");
            }
            Db::name('sms_log')->where($where)->delete();
            $this -> _toJson("1", "修改成功");

        }
    }

    /**
     * 发送验证码
     */
    public function sendVerify(){
        $mobile = $this -> _values -> param('mobile');
        if(empty($mobile)){
            $this -> _toJson("0", "请输入手机号码");
        }
        $code = get_vc(6);
        $data = array(
            'code'  =>  $code,
        );

        //$result = Db::name('sms_log')->data($data)->add();
        //开始发送短信
        $sms_res = sendSms("2", $mobile, $data); //用户注册
        if($sms_res){
            $this -> _toJson('1','发送成功',array('code'=>$code));
        }else{
            $this -> _toJson('0','发送失败');
        }
    }
}