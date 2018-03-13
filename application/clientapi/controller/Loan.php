<?php
/**
* 个人贷款信息首页
* Author: iWuxc
* time 2017-12-20
*/
namespace app\clientapi\controller;

use think\Loader;
use think\Request;
use app\clientapi\logic\LoanLogic;

class Loan extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new LoanLogic();
    }

    /**
     * 贷款首页-提款记录列表
     */
    public function index(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getLoanDetail($userid,request()->post());
        !(empty($res)) ? $this -> _toJson('1','获取成功',$res) : $this -> _toJson("1", '获取失败', []);
    }

    /**
     * 贷款首页-额度信息
     */
    public function indexLoan(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getLoanDetail($userid);
        !(empty($res)) ? $this -> _toJson('1','获取成功',$res) : $this -> _toJson("1", '获取失败', []);
    }

    /**
     * 提款申请添加页面
     */
    public function addLoan(){
        $this -> _check_param("key");
        $userid = $this -> _getKey();
        $this -> logic -> addLoan($userid);
    }

    /**
     * 贷款提交操作
     * @throws \think\exception\PDOException
     */
    public function loanHandle(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $this -> logic -> loanHandle(\request()->post(), $userid);
    }

    /**
     * 提款列表
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function LoanList(){
        $this -> _check_param('key,p');
        $userid = $this -> _getKey();
        $p = $this -> request -> param('p');
        $res = $this -> logic -> getLoanList($userid, $p);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '无数据', []);
    }

    /**
     * 提款信息详情
     */
    public function loanDetail(){
        $this -> _check_param('key,loan_id');
        $userid = $this -> _getKey();
        $loan_id = $this -> request -> param('loan_id');
        $res = $this -> logic -> loanDetail($loan_id,$userid);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('0', '获取失败', (object)array());
    }

    public function test(){
        $pushMsg = array(
            'cid' => '728f0bfa001d869480524ebd04dcddac',
            'title' => '测试',
            'body' => '提款成功',
            'transmissionContent' => json_encode(array('title'=>'测试','body'=>'提款成功'), JSON_UNESCAPED_UNICODE),
            'type' => 'client',
        );
        $r4 = pushMessageToSingle($pushMsg);
    }
}