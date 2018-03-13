<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/11
 * Time: 11:34
 */
namespace app\admin\controller;

use app\admin\logic\LoanLogic;
use app\admin\logic\LoanReceiptLogic;
use app\admin\logic\RepayLogic;
use think\Db;
use think\Request;
class LoanReceipt extends Base{

    protected $logic;

    protected $repay;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new LoanReceiptLogic();
        $this->repay = new RepayLogic();
    }

    /**
     * 放款记录列表（待还款列表）
     * @return mixed
     */
    public function index(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $_GET['is_repay'] = '2,3';
        $list = $this->logic->getList($_GET);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

    /**
     * 已还款列表
     * @return mixed
     */
    public function okList(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $_GET['is_repay'] = '1,4';
        $list = $this->logic->getList($_GET);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

    /**
     * 放款记录
     * @return mixed
     */
    public function lists(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list = $this->logic->getList($_GET,1);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch('lists');
    }

    /**
     * 详情
     * @return mixed
     */
    public function detail(){
        $row = $this->logic->findRow(Request::instance()->get());
        $this->assign('row',$row);
        return $this->fetch();
    }

    /**
     * 编辑
     * @return mixed
     */
    public function edit(){
        if(Request::instance()->isPost()){
            $result = $this->logic->edit(Request::instance()->post());
            if($result['status'] == 1){
                $this->success($result['msg'],$result['url']);
            }else{
                $this->error($result['msg']);
            }
        }else{
            $bank_list = Db::name('bank')->field('id,bank_name')->select();
            $this->assign('bank_list',$bank_list);
            $row = $this->logic->findRow(Request::instance()->get());
            $this->assign('row',$row);
        }
        return $this->fetch();
    }

    /**
     * 放款操作
     * @return mixed
     */
    public function receipt(){
        if(Request::instance()->isPost()){
            $result = $this->logic->receipt(Request::instance()->post());
            if($result['status'] == 1){
                $this->apiResponse('1',$result['msg'],array('url'=>$result['url']));
            }else{
                $this->apiResponse('0',$result['msg']);
            }
        }else{
            $loan = Db::name('loan')->where('id',$_GET['id'])->field('user_name,apply_amount,account,service_rate,inter_rate,str_time,stp_time')->find();
            $loan['str_time'] = date('Y-m-d',$loan['str_time']);
            $loan['stp_time'] = date('Y-m-d',$loan['stp_time']);
            $bank_list = Db::name('bank')->field('id,bank_name')->select();
            $this->assign('bank_list',$bank_list);
            $this->assign('loan',$loan);
        }
        return $this->fetch();
    }

    /**
     * 主动发放贷款
     * @return mixed
     */
    public function receipts(){
        if(Request::instance()->isPost()){
            $result = $this->logic->receipts(Request::instance()->post());
            if($result['status'] == 1){
                $this->apiResponse('1',$result['msg'],array('url'=>$result['url']));
            }else{
                $this->apiResponse('0',$result['msg']);
            }
        }
        $bank_list = Db::name('bank')->field('id,bank_name')->select();
        $this->assign('bank_list',$bank_list);
        return $this->fetch();
    }


    /**
     * 删除数据
     */
    public function del(){
        if(empty($_GET['id']) || !is_numeric($_GET['id'])){
            $this->error('参数异常',cookie('__forward__'));
        }
        $result = Db::name('loan_receipt')->where('id',$_GET['id'])->update(['status'=>9]);
        $result ? $this->success('操作成功',cookie('__forward__')) : $this->error('操作异常，请稍后再试');
    }


    /**
     * 新增还款
     * @return mixed
     */
    public function repay(){
        if(Request::instance()->isPost()){
            $result = $this->repay->repay(Request::instance()->post());
            if($result['status'] == 1){
                $this->success($result['msg'],$result['url']);
            }else{
                $this->error($result['msg']);
            }
        }else{
            $bank_list = Db::name('bank')->field('id,bank_name')->select();
            $this->assign('bank_list',$bank_list);
            $row = $this->logic->findRow(Request::instance()->get());
            $this->assign('row',$row);
        }
        return $this->fetch();
    }

    public function searchUser(){
        $where['name|user_id|client_no'] = ['LIKE','%'.$_GET['user_name'].'%'];
        $row = Db::name('users')->where($where)->field('name user_name,user_id,client_no')->find();
        if(!empty($row)){
            $row['price'] = Db::name('user_credit')->where('user_id',$row['user_id'])->value('credit_rest');
            die (json_encode($row,JSON_UNESCAPED_UNICODE));
        }else{
            echo 0;
        }

    }

    function apiResponse($code = '1', $message = '',$data = array()){
        header('Access-Control-Allow-Origin: *');
        header('Content-Type:application/json; charset=utf-8');
        $result = array(
            'code'  => $code,
            'msg'   => $message,
        );
        if(!empty($data)){
            $result['data'] = $data;
        }
        die(json_encode($result,JSON_UNESCAPED_UNICODE));
    }

}