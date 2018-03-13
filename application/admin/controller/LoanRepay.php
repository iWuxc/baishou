<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/15
 * Time: 11:00
 */
namespace app\admin\controller;

use app\admin\logic\RepayLogic;
use think\Db;
use think\Request;
class LoanRepay extends Base{

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new RepayLogic();
    }

    public function index(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list = $this->logic->getList(Request::instance()->get());
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

    /**
     * 查看详情
     * @return mixed
     */
    public function detail(){
        $row = $this->logic->findRow(Request::instance()->get());
        $this->assign('row',$row);
        return $this->fetch();
    }

}