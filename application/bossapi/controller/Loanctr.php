<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\LoanLogic;
use think\Db;

class Loanctr extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new LoanLogic();
    }

    /**
     * 贷款首页信息
     */
    public function index(){
        $list = $this->logic->getList($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }


    /**
     * 额度详情
     */
    public function detail(){
        $row = $this->logic->findRow($_REQUEST);
        api_response('1','',(object)$row);
    }

    /**
     * 提款操作
     */
    public function addLoan(){
        $result = $this->logic->addLoan($_REQUEST);
        api_response($result['status'],$result['msg']);
    }

}