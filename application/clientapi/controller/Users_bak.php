<?php

/**
 * 用户类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\controller;

use think\Request;
use think\Db;
use app\clientapi\logic\UsersLogic;

class Users extends Base {

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new UsersLogic();
    }

    /**
     * 个人中心信息
     */
    public function userInfo(){
        $user_id = $this -> _getKey();
        $type = $this -> _getType();
        if($user_id == 0){
            $this -> _toJson("0", '您未登录,请登录');
            exit;
        }
        if(empty($type) || $type == 0){
            $this -> _toJson("0", '用户类型为空');
            exit;
        }

        $info =  $this -> logic -> getSessInfo($user_id, $type);
        if($info){
            $this -> _toJson("1", '获取用户信息成功', $info);
        }else{
            $this -> _toJson("0", '获取用户失败', (object)array());
        }
    }

    /**
     * 修改用户头像
     */
    public function updateHeadPic(){
        $this -> _check_param("key,user_type");
        $userid = $this -> _getKey();
        $type = $this -> _getType();
        $file = request() -> file('head_pic');
        $where = array();
        if($file){
            $info = $file->move(ROOT_PATH.'public'.DS.'upload'.DS.'head');
            $head_pic = '/public/upload/head/'.$info->getSaveName();
            if($type == 1){
                $where['user_id'] = $userid;
                $result = M('users')->where($where)->update(['head_img'=>$head_pic,'update_time'=>time()]);
            }else{
                $where['clerk_id'] = $userid;
                $result = M('user_store_clerk')->where($where)->update(['head_img'=>$head_pic,'update_time'=>time()]);
            }
            if($result){
                $this -> _toJson("1", '上传成功', array('head_pic'=>get_url().$head_pic));
            }else{
                $this -> _toJson("0", '上传失败', (object)array());
            }
        }else{
            $this -> _toJson("0", '参数错误', (object)array());
        }
    }

    /**
     * 性别修改
     */
    public function updateSex(){
        $this -> _check_param("key,sex");
        $userid = $this -> _getKey();
        $sex = $this -> _values -> param('sex');
        $res = $this -> logic -> updateSex($userid, $sex);
        if($res){
            $this -> _toJson("1", '修改成功');
        }
        $this -> _toJson("0", '修改失败');
    }

    /**
     * 修改昵称
     */
    public function updateNickname(){
        $this -> _check_param("key,nickname");
        $userid = $this -> _getKey();
        $nickname = $this -> _values -> param('nickname');
        $res = $this -> logic -> updateNickname($userid, $nickname);
        if($res){
            $this -> _toJson("1", "修改成功");
        }
        $this -> _toJson("0", '修改失败');
    }

    /**
     * 修改手机号
     */
    public function updateMobile(){
        $this -> _check_param("key,mobile");
        $userid = $this -> _getKey();
        $mobile = $this -> _values -> param('mobile');
        $this -> logic -> modifyMobile($userid, $mobile);
    }

    /*
     * 修改密码
     */
    public function updatePasswd(){
        $this -> _check_param("key,old_passwd,passwd");
        $userid = $this -> _getKey();
        $this -> logic -> modifyPasswd($userid, $this->request->post());
    }

    /**
     * 意见反馈
     */
    public function feedback(){
        $this -> _check_param('contact,content');
        $this -> logic -> feedback(request()->post());
    }

    /**
     * 客服电话
     */
    public function tel(){
        $tel = Db::name('config')->where('name','phone')->value('value');
        $this -> _toJson('1','获取成功',['tel'=>$tel]);
    }

    /**
     * 获取经纬度
     */
    public function coordinate(){
        $this -> _check_param('key,user_type,lat,lng');
        $userid = $this -> _getKey();
        $this -> logic -> coordinate($userid, $this->request->post());
    }
}