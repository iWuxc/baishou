<?php

/**
 * 用户信息处理
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\logic;

use think\Loader;
use think\Db;

class UsersLogic extends BaseLogic {

    protected $model;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this -> getModelType(request()->param('user_type'));
    }

    /**
     * 获取客户信息
     * @param $userid
     * @return mixed
     */
    public function getSessInfo($userid){
        $sex = array(1=>'男', 2=>'女');
        $res = $this -> model -> getSessInfo($userid);
        $res['sex'] = $sex[$res['sex']];
        return $res;
    }

    /**
     * 修改性别
     * @param $userid
     * @param $sex
     * @return mixed
     */
    public function updateSex($userid, $sex){
        $result = $this -> model -> updateSex($userid, $sex);
        return $result;
    }

    /**
     * 修改昵称
     * @param $userid
     * @param $nickname
     * @return mixed
     */
    public function updateNickname($userid, $nickname){
        $result = $this -> model -> updateNickname($userid, $nickname);
        return $result;
    }

    /**
     * 修改手机号码
     * @param $type
     * @param $userid
     * @param $mobile
     * @return int
     */
    public function modifyMobile($userid, $mobile){
        $c_mobile = M('Users')->where('mobile',$mobile)->value('mobile');
        if(!$c_mobile){
            $c_mobile = M('user_store_clerk')->where('mobile',$mobile)->value('mobile');
        }
        $c_mobile && toJson("0", "手机号已注册");
        $result = $this -> model -> modifyMobile($userid, $mobile);
        if($result){
            toJson("1", "修改成功");
        }else{
            toJson("0", "修改成功");
        }
    }

    /**
     * 修改密码
     * @param $userid
     * @param $request
     */
    public function modifyPasswd($userid, $request){
        $request['user_type'] == 1 ? $where['user_id'] = $userid : $where['clerk_id'] = $userid;
        $password = $this -> model -> getPasswd($userid);
        if($password != encrypt($request['old_passwd'])){
            toJson('0','原密码输入错误');
        }
        if($password == encrypt($request['passwd'])){
            toJson('0','新密码不能和原密码相同');
        }
        $result = $this -> model -> modifyPasswd($userid, $request['passwd']);
        $result ? toJson('1',"修改成功") : toJson('0',"修改失败");
    }

    protected function getModelType($type){
        if(empty($type) || $type > 2){
            toJson("0", "未知用户类型");
        }
        return $type == 1 ? $this->model = Loader::model('Users') : $this->model = Loader::model('Clerk');
    }

    /**
     * 意见反馈
     * @param array $request
     */
    public function feedback($request = array()){
        $data['contact'] = $request['contact'];
        $data['content'] = $request['content'];
        $data['addtime'] = time();
        $result = Db::name('feedback')->add($data);
        $result ? toJson('1', '操作成功') : toJson('0', '操作失败');
    }

    /*
     * 记录经纬度
     */
    public function coordinate($userid, $request){
        $request['user_type'] != 1 && toJson('0', '非法用户');
        $request['user_id'] = $userid;
        $request['add_time'] = time();
        //记录经纬度
        $res = Db::name('user_coordinate') -> insert($request);
        $res ? toJson('1', '记录成功') : toJson('0', '记录失败');
    }

}