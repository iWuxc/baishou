<?php

/**
 * 用户详情管理
 * Author: iWuxc
 * time 2017-12-22
 */
namespace app\clientapi\controller;

use think\Request;
use app\clientapi\logic\UserDetailLogic;

class UserDetail extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new UserDetailLogic();
    }

    /**
     * 个人信息
     */
    public function userDetail(){
        $this -> _check_param("key,user_type");
        $userid = $this -> _getKey();
        $type = $this -> _getType();
        $res = $this -> logic -> userDetail($userid, $type);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('0', '获取失败', (object)array());
    }

    /**
     * 获取企业信息
     */
    public function userEnterprise(){
        $this -> _check_param("key,user_type");
        $userid = $this -> _getKey();
        $res = $this -> logic -> userEnterprise($userid);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('0', '获取失败', (object)array());
    }

    /**
     * 获取门店列表
     */
    public function storeList(){
        $this -> _check_param("key,user_type");
        $type = $this -> _getType();
        $userid = $this -> _getKey();
        $p = request() -> param('p');
        $res = $this -> logic -> storeList($userid, $type, $p);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '获取失败', array());
    }

    /**
     * 门店详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function storeDetail(){
        $this -> _check_param("store_id");
        $store_id = \request() -> param('store_id');
        $res = $this -> logic -> storeDetail($store_id);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('0', '获取失败', (object)array());
    }
}