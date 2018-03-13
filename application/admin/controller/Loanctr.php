<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 10:00
 */
namespace app\admin\controller;

use app\admin\logic\LoanLogic;
use app\admin\logic\LoanReceiptLogic;
use think\Db;
use think\Request;
use think\AjaxPage;
use think\Page;
class Loanctr extends Base{

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new LoanLogic();
    }

    /**
     * 贷款列表首页
     * @return mixed
     */
    public function index(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list = $this->logic->getList($_GET,0);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

    /**
     * 已审核列表
     * @return mixed
     */
    public function okList(){
        cookie('__forward__', $_SERVER['REQUEST_URI']);
        $list = $this->logic->getList($_GET,1);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch('okList');
    }

    /**
     * 查看详情
     * @return mixed
     */
    public function detail(){
        $row = $this->logic->findRow(Request::instance()->get());
        $this->assign('row',$row);
        return $this->fetch();
    }

    /**
     * 当前客户提款明细列表
     * @return mixed
     */
    public function loanDetail(){
        $loan_receipt = new LoanReceiptLogic();
        $_GET['status'] = 1;
        $list = $loan_receipt->getList($_GET);
        $this->assign('list',$list['list']);
        $this->assign('page',$list['page']);
        $this->assign('page_total',$list['page_total']);
        return $this->fetch();
    }

    /**
     * 个人信用信息
     * @return mixed
     */
    public function userCredit(){
        if(empty($_GET['user_id']) || !is_numeric($_GET['user_id'])){
            $this->error('参数异常','Admin/Loanctr/index');
        }
        $row = Db::name('user_credit') 
            ->alias('uc')
            ->field('uc.user_id,uc.credit_total,uc.credit_used,uc.credit_rest,uc.assess_total,uc.check_rate,u.name user_name')
            ->join('__USERS__ u','uc.user_id = u.user_id')
            ->where('uc.user_id',$_GET['user_id'])
            ->find();
        $this->assign('row',$row);
        return $this->fetch('userCredit');
    }


    /**
     * 否决操作
     */
    public function cancel(){
        if(!empty($request['id']) || !empty($request['user_id'])){
            $this->error('请不要随意修改url参数');
        }
        $result = $this->logic->cancel(Request::instance()->get(),4);
        $result['status'] == 1 ? $this->success($result['msg'],$result['url']) : $this->error($result['msg']);
    }

    /**
     * 续议操作
     */
    public function cancelS(){
        if(!empty($request['id']) || !empty($request['user_id'])){
            $this->error('请不要随意修改url参数');
        }
        $result = $this->logic->cancel(Request::instance()->get(),5);
        $result['status'] == 1 ? $this->success($result['msg'],$result['url']) : $this->error($result['msg']);
    }

    /**
     * 删除数据
     */
    public function del(){
        if(empty($_GET['id']) || !is_numeric($_GET['id'])){
            $this->error('参数异常','Admin/Loanctr/index');
        }
        $result = Db::name('loan')->where('id',$_GET['id'])->update(['is_del'=>9]);
        $result ? $this->success('操作成功',cookie('__forward__')) : $this->error('操作异常，请稍后再试');
    }

    /**
     * 个人信用日志
     */
    public function userCreditlog(){
        //搜索条件
        $condition = array();
        $log =  M('user_credit_log');

        if(!empty($_GET['user_id'])){
            $condition['user_id'] = $_GET['user_id'];
        }
        if(!empty($_GET['log_user'])){
            $condition['log_user'] = $_GET['log_user'];
        }
        //时间段查询
        if(!empty($_GET['start_time']) && !empty($_GET['end_time'])){
            $condition['log_time'] = ['between time',[$_GET['start_time'],$_GET['end_time']]];
        }

        $count = $log->where($condition)->count();
        $Page = new Page($count,10);

        $show = $Page->show();
        $list = $log->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $key => $val) {
            $users = M('users')->field('user_id,name')->where(['user_id' => $val['user_id']] )->order('user_id desc')->find();
            $list[$key]['username'] = $users['name'];
            if($val['log_type'] == 2){
                $list[$key]['admin_name'] = $users['name'];
            }else{
                $list[$key]['admin_name'] = M('Admin')->where(['admin_id'=>$val['log_user']])->getField('user_name');
            }
        }
        //操作人
        $admin = M('admin')->getField('admin_id,user_name');
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);    
        $this->assign('admin',$admin);      
        return $this->fetch();
    }


}