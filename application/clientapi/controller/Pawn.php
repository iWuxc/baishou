<?php
/**
 * 抵押品管理
 * Author: iWuxc
 * time 2017-12-25
 */
namespace app\clientapi\controller;

use think\Loader;
use think\Request;
use think\Db;
use app\clientapi\logic\PawnLogic;

class Pawn extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new PawnLogic();
    }

    /**
     * 1组件列表, 2待解压组件列表
     */
    public function pawnList(){
        $this -> _check_param('key,user_type');
        $userid = $this -> _getKey();
        $type = $this -> _getType();
        $p = I('p/d', 1); //分页
        $status = I('status','','trim'); //待解压列表
        $res = $this -> logic -> getPawnList($userid, $type, $status, $p);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '无抵押品', array());
    }

    /**
     * 抵押品详情
     */
    public function pawnDetail(){
        $this -> _check_param('pawn_id');
        $pawn_id = $this -> request -> param('pawn_id');
        $res = $this -> logic -> detail($pawn_id);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '获取失败', (object)array());
    }

    /*
     * 组件列表
     */
    public function pawnOne(){
        $this -> _check_param('pawn_id');
        $pawn_id = $this -> request -> param('pawn_id');
        $res = $this -> logic -> getPawnOneList($pawn_id);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '获取失败', array());
    }

    public function pawnOneDetail(){
        $this -> _check_param('one_id');
        $one_id = $this -> request -> param('one_id');
        $pawn_id = $this -> request -> param('pawn_id');
        $res = $this -> logic -> pawnOneDetail($pawn_id, $one_id);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '获取失败', (object)array());
    }

    /**
     * 搜索
     */
    public function search(){
        $this -> _check_param('key,user_type');
        $userid = $this -> _getKey();
        $type = $this -> _getType();
        $keyword = I('keyword','','trim');
        $p = I('p/d', 1); //分页
        $res = $this -> logic -> search($userid, $type, $keyword, $p);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '无抵押品', array());
    }

    /**
     * 申请解压
     */
    public function unpack(){
        $this -> _check_param('key,user_type');
        $userid = $this -> _getKey();
        $this -> logic -> unpack($userid, $_REQUEST);
    }

    /*
     * 增加抵押品客服电话
     */
    public function pawnTel(){
        $tel = Db::name('config')->where('name','pawn_tel')->value('value');
        $this -> _toJson('1', '获取成功', format_tel($tel));
    }

    /*
     * 解押前判断抵押率
     */
    public function unpackLimit(){
        $this -> _check_param('key,user_type');
        $userid = $this -> _getKey();
        $this -> logic -> unpackLimit($userid);
    }

    public function test(){
        $a = $this -> logic -> deal_outbound(1, 1);
        var_dump($a);
    }
}