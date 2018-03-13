<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\MessageLogic;
use app\bossapi\logic\PawnLogic;
use think\Db;

class Pawn extends Base {

    protected $logic;

    protected $message;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new PawnLogic();
        $this->message = new MessageLogic();
    }

    /**
     * 抵押品列表
     */
    public function index(){
        $list = $this->logic->getList($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 抵押品详情
     */
    public function detail(){
        $row = $this->logic->findRow($_REQUEST);
        api_response('1','',(object)$row);
    }

    /**
     * 出库数据列表
     */
    public function handle(){
        $list = $this->logic->getList($_REQUEST,2);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 门店列表
     */
    public function store(){
        $list = $this->logic->store($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 信息录入门店下的家具
     */
    public function pawnList(){
        $this->logic->pawnList($_REQUEST);
    }

    /**
     * 上传门店图片
     */
    public function uploadImg(){
        $this->logic->uploadImg($_REQUEST);
    }

    /**
     * 同意出库操作
     */
    public function ok(){
        $this->logic->okOrCancel($_REQUEST,3);
    }

    /**
     * 否决出库操作
     */
    public function cancel(){
        $this->logic->okOrCancel($_REQUEST,4);
    }

    /**
     * 确认家具审核
     */
    public function checkConfirm(){
        $this->logic->checkConfirm($_REQUEST);
    }

    /**
     * 评估师评估
     */
    public function estimate(){
        $this->logic->estimate($_REQUEST);
    }
    
    /**
     * 评估师评估历史
     */
    public function history(){
        $this->logic->history($_REQUEST);
    }

    /**
     * 评估师评估报告
     */
    public function estimateMessage(){
        $list = $this->message->estimateMessage($_REQUEST);
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 评估师最新消息
     */
    public function estimateNew(){
        $this->message->estimateFind($_REQUEST);
    }

    /**
     * 套件下的单件家具列表
     */
    public function pawnOne(){
        $this->logic->pawnOne($_REQUEST);
    }

    /**
     * 组件详情
     */
    public function findRowOne(){
        $row = $this->logic->findRowOne($_REQUEST);
        api_response('1','',(object)$row);
    }

}