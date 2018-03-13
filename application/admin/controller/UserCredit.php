<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/13
 * Time: 20:00
 */
namespace app\admin\controller;

use app\admin\logic\UserCreditLogic;
use think\Request;
class UserCredit extends Base{

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new UserCreditLogic();
    }

    /**
     * 记录列表
     * @return mixed
     */
    public function index(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list = $this->logic->getList(Request::instance()->get());
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

}