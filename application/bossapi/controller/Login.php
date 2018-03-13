<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use think\Db;
use think\Request;

class Login extends Base {

   public function __construct(Request $request = null){
       parent::__construct($request);
   }

    /**
     * 执行登陆
     */
    public function doLogin(){
        if(empty($_REQUEST['mobile'])){
            api_response('0','请输入手机号');
        }elseif(empty($_REQUEST['password'])){
            api_response('0','请输入密码');
        }else{
            $row = Db::name('admin')->where('mobile',$_REQUEST['mobile'])->field('admin_id,nickname,mobile,password,role_id,sex,head_pic')->find();
            //dump(Db::name('admin')->getLastSql());die;
            if(empty($row)){
                api_response('0','手机号未注册');
            }
            if(encrypt($_REQUEST['password']) != $row['password']){
                api_response('0','密码输入不正确');
            }
            $row['head_pic'] = get_url().$row['head_pic'];
            unset($row['password']);
            if(!empty($_REQUEST['client_id'])){
                //查询cid是否存在
                $cid = Db::name('admin')->where('mobile',$_REQUEST['mobile'])->value('cid');
                if($cid != $_REQUEST['client_id']){
                    pushMessageToSingle(
                        array(
                            'cid' => $cid,
                            'title' => '异地登录提醒',
                            'body' => '您的账号在其它设备登录',
                            'transmissionContent' =>  json_encode([
                                'title' => '异地登录提醒',
                                'body'  => '您的账号在其它设备登录'
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                //更新client_id
                Db::name('admin')->where('mobile',$_REQUEST['mobile'])->update(['cid'=>$_REQUEST['client_id'],'update_time'=>time()]);
            }
            api_response('1','',$row);
        }
    }

    /**
     * 退出登陆
     */
    public function logOut(){
        check_param('admin_id');
        $result = Db::name('admin')->where('admin_id',$_REQUEST['admin_id'])->update(['cid'=>'','update_time'=>time()]);
        $result ? api_response('1',SUCCESS_INFO) : api_response('0',ERROR_INFO);
    }

    /**
     * 修改密码
     */
    public function retrieve(){
        $row = Db::name('admin')->where('mobile',$_REQUEST['mobile'])->value('mobile');
        if(empty($_REQUEST['mobile'])){
            api_response('0','请输入手机号');
        }elseif(empty($row)){
            api_response('0','手机号未注册');
        }elseif(empty($_REQUEST['verify'])){
            api_response('0','请输入验证码');
        }elseif(empty($_REQUEST['password'])){
            api_response('0','请输入密码');
        }else{
            $where['mobile'] = $_REQUEST['mobile'];
            $where['scene'] = 2;
            $verify = Db::name('sms_log')->where($where)->order('add_time desc')->value('code');
            if($_REQUEST['verify'] != $verify){
                api_response('0','验证码错误');
            }else{
                $result = Db::name('admin')->where('mobile',$_REQUEST['mobile'])->update(['password'=>encrypt($_REQUEST['password']),'update_time'=>time()]);
                if($result){
                    api_response('1',SUCCESS_INFO);
                }else{
                    api_response('0',ERROR_INFO);
                }
            }
        }
    }

    /**
     * 发送验证码
     */
    public function sendVerify(){
        if(empty($_REQUEST['mobile'])){
            api_response('0','请输入手机号');
        }
        $code = get_vc(6);
        $msg = '您的验证码为:'.$code;
        $data = array(
            'mobile'    => $_REQUEST['mobile'],
            'add_time'  => time(),
            'code'      => $code,
            'scene'     => 2,
            'msg'       => $msg
        );
        $result = Db::name('sms_log')->data($data)->add();
        if($result){
            //发送短信
            $s_res = send_sms($_REQUEST['mobile'],$msg);
            if($s_res){
                api_response('1','发送成功',array('code'=>$code));
            }else{
                api_response('0',ERROR_INFO);
            }
        }else{
            api_response('0',ERROR_INFO);
        }
    }

}