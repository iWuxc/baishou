<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\UserLogic;
use think\Db;

class User extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new UserLogic();
    }

    /**
     * 客户列表
     */
    public function index(){
        $list = $this->logic->getList($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 个人信息
     */
    public function userInfo(){
        $this->logic->userInfo($_REQUEST);
    }

    /**
     * 企业信息
     */
    public function userEnterprise(){
        $this->logic->userEnterprise($_REQUEST);
    }

}