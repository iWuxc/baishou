<?php
/**
 * 客户额度信息操作
 * Author: iWuxc
 * time 2017-12-25
 */
namespace app\clientapi\controller;

use think\Loader;
use think\Request;
use app\clientapi\logic\CreditLogic;

class Credit extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new CreditLogic();
    }

    /*
     * 首页-额度信息
     */
    public function index(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> getCreditInfo($userid);
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '无数据', (object)array());
    }

    /**
     * 客户额度详情
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function creditDetail(){
        $this -> _check_param('key');
        $userid = $this -> _getKey();
        $res = $this -> logic -> creditDetail($userid);
        $res ? $this -> _toJson("1","获取成功",$res) : $this -> _toJson("1", "请求失败", (object)array());
    }
}