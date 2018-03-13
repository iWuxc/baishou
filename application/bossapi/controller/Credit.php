<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\CreditLogic;
use think\Db;

class Credit extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new CreditLogic();
    }

    /**
     * 授信管理列表
     */
    public function index(){
        $list = $this->logic->getList($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 详情
     */
    public function detail(){
        $this->logic->detail($_REQUEST);
    }

    /**
     * 客户额度
     */
    public function creditDetail(){
        $this->logic->creditDetail($_REQUEST);
    }

    /**
     * 否决授信额度申请
     */
    public function cancel(){
        $this->logic->cancel($_REQUEST);
    }

    /**
     * 填写审批结论
     */
    public function ok(){
        $this->logic->ok($_REQUEST);
    }

    /**
     * 查看审批结论
     */
    public function showCredit(){
        $this->logic->showCredit($_REQUEST);
    }

}