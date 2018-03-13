<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\MessageLogic;
use think\Db;

class Message extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new MessageLogic();
    }

    /**
     * 客户列表
     */
    public function index(){
        $list = $this->logic->getList($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 客户逾期报警
     */
    public function lista(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 贷款到期通知
     */
    public function listb(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 提款审批通知
     */
    public function listc(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 额度授信通知
     */
    public function listd(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 抵押品出库通知
     */
    public function liste(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 提款结清通知
     */
    public function listf(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 设备异常
     */
    public function listg(){
        $list = $this->logic->getLists($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

}