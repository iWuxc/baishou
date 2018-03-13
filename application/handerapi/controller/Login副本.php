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

    protected $model;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> model = Loader::model('check_user');
    }

    public function loginApi(){
        $mobile = $this -> _values -> param('mobile');
        $password = $this -> _values -> param('password');
        $uArr = $this -> model -> login($mobile, $password);
        $arr = (object)array();
        switch($uArr){
            case 1;
                $this -> _toJson('0', '请输入账户名和密码', $arr);
                break;
            case 2;
                $this -> _toJson('0', '账号或密码不正确', $arr);
                break;
            case 3;
                $this -> _toJson('0', '用户已冻结', $arr);
                break;
            case 4;
                $this -> _toJson('0', '用户登录失败', $arr);
                break;
            default:
                $where = array();
                $where['mobile'] = $mobile;
                $check_id = M('check_user') -> where($where) ->field('check_id') -> seleclt();
                $_SESSION['check_id'] = $check_id['0']['check_id'];
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
        $this -> _check_param("verify,password,mobile");
        $code = $this -> _values -> param('verify');
        $password = $this -> _values -> param('password');
        $mobile = $this -> _values -> param('mobile');
        if(empty($mobile)){
            $this -> _toJson("0", "请输入手机号");
        }elseif(empty($code)){
            $this -> _toJson("0", "请输入验证码");
        }elseif(empty($password)){
            $this -> _toJson("0", "请输入密码");
        }
        $m = Db::name('check_user')->where('mobile',$mobile)->value('mobile');
        $where['mobile'] = $mobile;
        $verify = Db::name('sms_log')->where($where)->value('code');
        if($verify != $code){
            $this -> _toJson("0", "验证码错误");
        }else{
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