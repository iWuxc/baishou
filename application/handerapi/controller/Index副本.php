<?php
/**
 * 消息通知
 * Author: iWuxc
 * time 2017-12-25
 */
namespace app\handerapi\controller;

use think\Request;
use app\handerapi\logic\IndexLogic;

class Index extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new IndexLogic();
    }
    //主页
    public function index(){
        // 店铺巡视数量
        $check_count = M('user_store') -> count(); 
        //异常数量
        $wrong_count = M('equipment_wrong')-> group('store_name') -> count(); 
        $res = array();
        $res['check_count'] = $check_count;
        $res['wrong_count'] = $wrong_count;
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '暂无数据', []);
    }
    //店铺列表
    public function check(){
        $check =  M('user_store') -> field('store_id,store_name') -> select();
        $check ? $this -> _toJson('1', '获取成功', $check) : $this -> _toJson('1', '暂无数据', []);
    }
    //异常店铺列表
    public function wrong(){
        $wrong = M('equipment_wrong')-> group('store_name') -> field('store_id,store_name') ->select(); 
        $wrong ? $this -> _toJson('1', '获取成功', $wrong) : $this -> _toJson('1', '暂无数据', []);
    }
    //巡店记录列表
    public function checklog(){
        $check_id = $_POST['check_id'];
        $where = array();
        $where['check_user_id'] = $check_id;
        $checklog = M('check_log') -> where($where) -> field('store_name,store_id,time，bad_pawn_id')->select();
        $checklog ? $this -> _toJson('1', '获取成功', $checklog) : $this -> _toJson('1', '暂无数据', []);
    }
    
    //所有抵押品列表
    public function moods(){
        $store_id = $_POST['store_id'];
        $where = array();
        $where['store_id'] = $store_id;
        $moods = M('pawn') -> where($where) -> field('pawn_id,pawn_rfid,pawn_name')->select();
        $moods ? $this -> _toJson('1', '获取成功', $moods) : $this -> _toJson('1', '暂无数据', []);
    }

    //异常抵押品列表
    public function moods_wrong(){
        $store_id = $_POST['store_id'];
        $where = array();
        $where['store_id'] = $store_id;
        $moods_wrong = M('equipment_wrong') -> where($where) -> field('em_id,em_name')->select();
        $moods_wrong ? $this -> _toJson('1', '获取成功', $moods_wrong) : $this -> _toJson('1', '暂无数据', []);
    }

    //抵押品照片
    public function imgs(){
        if ($_POST['pawn_id']) {
            $pawn_id = $_POST['pawn_id'];
        }
        if ($_POST['em_id']) {
            $pawn_id = $_POST['em_id'];
        }
        
        $where = array();
        $where['pawn_id'] = $pawn_id;
        $imgs = M('pawn_imgs') -> where($where) -> field('') -> select();
        $imgs ? $this -> _toJson('1', '获取成功', $imgs) : $this -> _toJson('1', '暂无数据', []);
    }

    public function checkend(){
        $add = array();
        $add['check_user_id'] = $_POST['check_id'];
        $add['store_id'] = $_POST['store_id'];
        $add['store_name'] = $_POST['store_name'];
        $add['rfid_count'] =$_POST['rfid_count'];
        $add['bad_rfids'] =$_POST['bad_rfids'];
        $add['bad_pawn_id'] =$_POST['bad_pawn_id'];
        $add['time'] = time();
        $add['status'] = $_POST['status'];
        $add['result'] = $_POST['result'];
        $add['is_add_task'] = $_POST['is_add_task'];
        $res = M('check_log') -> add($add);
        $res ? $this -> _toJson('1', '巡店成功', $res) : $this -> _toJson('1', '巡店失败', []);
    }
}