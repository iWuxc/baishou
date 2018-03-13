<?php

/**
 * 店员信息管理
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\controller;

use think\Request;
use app\clientapi\logic\ClerkManageLogic;

class ClerkManage extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new ClerkManageLogic();
    }

    /**
     * 店员列表
     */
    public function clerkList(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getClerkList($userid);
        if(!$res){
            $this -> _toJson("1", "无店员信息", array());
        }
        $this -> _toJson("1", "请求成功", $res);
    }

    /**
     * 店员详情信息
     */
    public function detail(){
        $this -> _check_param('key,clerk_id');
        $userid = $this -> _getKey();
        $clerk_id = $this -> request -> param('clerk_id');
        $res = $this -> logic -> detail($clerk_id,$userid);
        $res ? $this -> _toJson("1", "获取成功", $res) : $this -> _toJson("1", "获取失败", (object)array());
    }

    /**
     * 修改店员归属门店
     */
    public function modifyStore(){
        $this -> _check_param("clerk_id");
        $userid = $this -> _getKey();
        $clerk_id = request() -> param("clerk_id");
        $store_ids = request() -> param("store_ids"); //不做验证,可能提交为0
        !isset($store_ids) && $this -> _toJson("0", "参数错误", (object)array());
        $this -> logic -> modifyStore($clerk_id,$store_ids,$userid);
    }

    /**
     * 修改权限(解压复核)
     */
    public function modifyAuth(){
        $this -> _check_param("key,clerk_id,double_check");
        $userid = $this -> _getKey();
        $clerk_id = request() -> param("clerk_id");
        $double_check = request() -> param("double_check");
        $this -> logic -> modifyAuth($clerk_id,$double_check,$userid);
    }

    /**
     * 更改店员激活状态
     */
    public function lockClerk(){
        $this -> _check_param("clerk_id,is_lock");
        $clerk_id = request() -> param("clerk_id");
        $is_lock = request() -> param("is_lock");
        $res = $this -> logic -> lockClerk($clerk_id,$is_lock);
        $res ? $this -> _toJson("1", "修改成功") : $this -> _toJson("0", "修改失败");
    }

    /**
     * 添加员工
     */
    public function addClerk(){
        $this -> _check_param('key,user_type,c_mobile,c_name,credential_type,id_number,belong_to_store,double_check');
        $userid = $this -> _getKey();
        $this -> logic -> addClerk(request()->post(), $userid);
    }

    /**
     * 获取抵押门店
     */
    public function getStoreList(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $this -> logic -> getStoreList($userid);
    }
}