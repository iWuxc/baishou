<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\StoreLogic;
use think\Db;

class Store extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new StoreLogic();
    }

    /**
     * 门店列表
     */
    public function index(){
        $list = $this->logic->getList($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 门店详情
     */
    public function detail(){
        $row = $this->logic->findRow($_REQUEST);
        api_response('1','',(object)$row);
    }

    /**
     * 监控模块门店列表
     */
    public function store(){
        $list = $this->logic->store($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

}