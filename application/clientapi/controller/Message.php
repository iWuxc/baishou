<?php
/**
 * 消息通知
 * Author: iWuxc
 * time 2017-12-25
 */
namespace app\clientapi\controller;

use think\Request;
use app\clientapi\logic\MessageLogic;
use app\clientapi\logic\UserCreditLogic as UserCredit;

class Message extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new MessageLogic();
    }

    /**
     * 客户消息类型列表
     */
    public function index(){
        $this -> _check_param('key,user_type');
        $userid = $this -> _getKey();
        $res = $this -> logic -> noticeList(\request()->post(), $userid);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '暂无数据', []);
    }

    /**
     * 还款消息列表
     */
    public function list_6(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getLists($userid, $_REQUEST);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '暂无数据', []);
    }

    /**
     * 提款通知列表
     */
    public function list_3(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getLists($userid, $_REQUEST);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '暂无数据', []);
    }

    /*
     * 抵押品出库列表
     */
    public function list_5(){
        $this -> _check_param('key,user_type');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getLists($userid, $_REQUEST);
        if(!empty($res)){
            foreach($res as $k => $v){
                $res[$k]['content']['status'] = M('pawn_applygreen') -> where('pawn_id',$v['content']['pawn_id'])->value('status');
            }
            $this -> _toJson('1', '获取成功', $res);
        }
        $this -> _toJson('1', '暂无数据', []);
    }

    /**
     * 抵押品详情
     */
    public function pawnInfo(){
        $this -> _check_param('key,pawn_id');
        $userid = $this -> _getKey();
        $pawn_id = $this -> request -> param('pawn_id');
        $res = $this -> logic -> pawnInfo($pawn_id,$userid);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '暂无数据', (object)array());
    }

    /**
     * 内部同意
     */
    public function agreed(){
        $this -> _check_param('key,pawn_id');
        $userid = $this -> _getKey();
        $this -> logic -> agreed($userid, request()->param('pawn_id'));
    }

    /*
     * 内部否决
     */
    public function refused(){
        $this -> _check_param('key','pawn_id','remark');
        $userid = $this -> _getKey();
        $this -> logic -> refused($userid, $_REQUEST);
    }
}