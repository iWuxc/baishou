<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 10:00
 */
namespace app\admin\controller;

use app\admin\logic\LoanLogLogic;
class LoanLog extends Base{

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new LoanLogLogic();
    }

    /**
     * 贷款列表首页
     * @return mixed
     */
    public function index(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list = $this->logic->getList($_GET);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

}