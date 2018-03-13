<?php
/**
 * 用户模型类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;
use think\Config;

class Users extends Model {

    /*
     * 登录
     */
    public function login($mobile, $passwd, $cid){
        //检测是否登录
        $is_login = $this -> check_login($mobile, $cid);
        if($is_login['status'] == 1){//已登录
            $uArr = array(
                'user_type' => $is_login['msg']['user_type'],
                'key' => $is_login['msg']['sesskey'],
                'userid' => $is_login['msg']['userid'],
                'user_name' => $is_login['msg']['user_name'],
                'data' => $is_login['msg']['data']
            );
            $where['user_id'] = $is_login['msg']['userid'];
            if($is_login['msg']['user_type'] == 2){
                $boss_id = Db::name('user_store_clerk') -> where('clerk_id',$is_login['msg']['userid']) -> value('user_id');
                $uArr['boss_id'] = $boss_id;
                $where['user_id'] = $boss_id;
            }
            //获取信贷经理电话
            $xd_id = Db::name('users') -> where($where) -> value('xd_id');
            if(empty($xd_id)){
                $uArr['xd_mobile'] = '';
            }
            $uArr['xd_mobile'] = $this -> getXdTel($xd_id);
            return $uArr;
        }

        $data = array();
        if(!$mobile || !$passwd){
            return  1; //请填写账号或密码
            exit;
        }
        $data[] = $mobile;
        $data[] = encrypt($passwd);
        //登录两张表
        $user_info = $this -> where(array('mobile'=>$data[0],'password'=>$data[1])) -> find();
        if(!$user_info){
            $clerk_info = M('user_store_clerk') -> where(array('mobile'=>$data[0],'password'=>$data[1])) -> find();
            if(!$clerk_info){
                return 2; //账号或密码不正确
                exit;
            } else {//员工登录
                if($is_login['status'] == -2){//异地登录处理
                    Db::name('sessions') -> where('mobile', $mobile) -> delete();
                    $this -> pushGtMessage($is_login['msg']);
                }
                $entry = Config::get('key');
                $time = time();
                $key = md5($data[0] . $data[1] . $entry . $time);
                $_datab = array(
                    'sesskey' => $key,
                    'cid' => $cid,
                    'expiry' => time(),
                    'userid' => $clerk_info['clerk_id'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_name' => $clerk_info['clerk_name'],
                    'mobile' => $clerk_info['mobile'],
                    'data' => $key,
                    'user_type' => 2
                );
                $res = M('sessions')->insert($_datab);
                if ($res) {
                    $uArr = array(
                        'user_type' => 2,
                        'boss_id' => $clerk_info['user_id'],
                        'key' => $key,
                        'userid' => $clerk_info['clerk_id'],
                        'user_name' => $clerk_info['clerk_name'],
                        'data' => $key
                    );
                    //获取信贷经理电话
                    $xd_id = Db::name('users') -> where('user_id',$clerk_info['user_id']) -> value('xd_id');
                    if(empty($xd_id)){
                        $uArr['xd_mobile'] = '';
                    }
                    $uArr['xd_mobile'] = $this -> getXdTel($xd_id);
                    Db::name('user_store_clerk') -> where('clerk_id',$clerk_info['clerk_id']) -> update(array('cid'=>$cid));
                    return $uArr;
                    exit;
                } else {
                    return 4; //用户登录失败
                    exit;
                }
            }
        } else {
            if($user_info['is_lock'] == 2){
                return 3;
                exit;
            } else {
                if($is_login['status'] == -2){//异地登录处理
                    Db::name('sessions') -> where('mobile', $mobile) -> delete();
                    $this -> pushGtMessage($is_login['msg']);
                }
                $entry = Config::get('key');
                $time = time();
                $key = md5($data[0].$data[1].$entry.$time);
                $_datab = array(
                    'sesskey' => $key,
                    'cid' => $cid,
                    'expiry' => time(),
                    'userid' => $user_info['user_id'],
                    'ip' => $_SERVER['REMOTE_ADDR'],
                    'user_name' => $user_info['name'],
                    'mobile' => $user_info['mobile'],
                    'data' => $key,
                    'user_type' => 1
                );
                $res = M('sessions') -> insert($_datab);
                if($res){
                    //Db::name()
                    $uArr = array(
                        'user_type' => 1,
                        'key' =>$key,
                        'userid' =>$user_info['user_id'],
                        'user_name' => $user_info['name'],
                        'data' =>$key
                    );
                    //获取信贷经理电话
                    $xd_id = Db::name('users') -> where('user_id',$user_info['user_id']) -> value('xd_id');
                    if(empty($xd_id)){
                        $uArr['xd_mobile'] = '';
                    }
                    $uArr['xd_mobile'] = $this -> getXdTel($xd_id);
                    Db::name('users') -> where('user_id',$user_info['user_id']) -> update(array('cid'=>$cid));
                    return $uArr;
                    exit;
                }else{
                    return 4; //用户登录失败
                    exit;
                }
            }
        }

    }

    /**
     * 退出操作
     * @param $key
     * @return int
     */
    public function logout($key){
        $res = M('sessions') -> where(array('sesskey'=>$key)) -> delete();
        if($res){
            return 1;
            exit();
        }else{
            return 0;
            exit;
        }
    }

    /**
     * @param $userid
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSessInfo($userid){
        $info = $this
            -> field('user_id, client_no, name, nickname, sex, mobile, head_img')
            -> where('user_id='.$userid)
            -> find()
            -> getData();
        return $info;
    }

    /**
     * 修改性别
     * @param $type
     * @param $userid
     * @param $sex
     * @return $this
     */
    public function updateSex($userid, $sex){
        $result = $this -> where('user_id', $userid)
            -> update(['sex'=>$sex, 'update_time'=>time()]);
        return $result;
    }

    /**
     * 修改昵称
     * @param  [type] $type     [description]
     * @param  [type] $userid   [description]
     * @param  [type] $nickname [description]
     * @return [type]           [description]
     */
    public function updateNickname($userid, $nickname){
        $result = $this -> where('user_id', $userid)
            -> update(['nickname'=>$nickname, 'update_time'=>time()]);
        return $result;
    }

    /**
     * 修改手机号
     * @param $userid
     * @param $mobile
     * @return $this
     */
    public function modifyMobile($userid, $mobile){
        $result = $this -> where('user_id', $userid)
            -> update(['mobile'=>$mobile, 'update_time'=>time()]);
        return $result;
    }

    /**
     * 修改密码
     * @param $userid
     * @param $mobile
     * @return $this
     */
    public function modifyPasswd($userid, $passwd){
        $result = $this -> where('user_id', $userid)
            -> update(['password'=>encrypt($passwd), 'update_time'=>time()]);
        return $result;
    }

    /**
     * 获取密码信息
     * @param $userid
     * @return mixed
     */
    public function getPasswd($userid){
        $result = $this -> where('user_id', $userid) -> value('password');
        return $result;
    }

    /*
     * 信贷经理联系方式
     */
    public function getXdTel($xd_id){
        if(empty($xd_id)){
            return false;
        }
        $tel = Db::name('admin') -> where('admin_id',$xd_id) -> value('mobile');
        return $tel;
    }

    /*
     * 检测是否登录
     */
    protected function check_login($mobile,$cid){
        $user_key_time = Config::get('APP_TOKEN_TIME');//token
        $res = Db::name('sessions') -> where('mobile',$mobile) -> find();
        if($res){
            //验证登录失效时间
            /**
            if(time() > ($res['expiry'] + $user_key_time)){
                //失效删除登录信息
                $del = Db::name('sessions') -> where('mobile', $mobile) -> delete();
                if($del){
                    return array('status' => -1);//登录失效
                }
            }
             * */
            //验证是否是异地登录登录
            if($res['cid'] != $cid){
                return array(
                    'status' => -2,
                    'msg' => $res['cid']
                );
            }
            return array('status'=>1, 'msg'=>$res);
        }else{
            return array('status'=>-3);//未登录
        }
    }

    /**
     * 异地登录异常使用
     * @param $cid
     */
    public function pushGtMessage($cid){
        //异地登录
        pushMessageToSingle(
            array(
                'cid' => $cid,
                'title' => '异地登录提醒',
                'body' => '您的账号在其它设备登录',
                'transmissionContent' =>  json_encode([
                    'title' => '异地登录提醒',
                    'body'  => '您的账号在其它设备登录'
                ],JSON_UNESCAPED_UNICODE),
                'type'=>'client'
            )
        );
    }
}