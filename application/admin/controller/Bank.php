<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 10:00
 */
namespace app\admin\controller;

use app\admin\logic\BankLogic;
use think\Db;
use think\Request;
use think\AjaxPage;
use think\Page;
class Bank extends Base{

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new BankLogic();
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

    public function add(){
        if(IS_POST){
            if(empty($_POST['bank_name'])){
                $this->error('请输入银行卡');
            }
            $data = [
                'bank_name'  => $_POST['bank_name'],
                'add_time'  => time()
            ];
            $result = Db::name('bank')->insert($data);
            if($result){
                $this->success(SUCCESS_INFO,cookie('__forward__'));
            }else{
                $this->success(ERROR_INFO,cookie('__forward__'));
            }
        }
        return $this->fetch();
    }

    public function edit(){
        if(IS_POST){
            if(empty($_POST['bank_name'])){
                $this->error('请输入银行卡');
            }
            $where['id'] = $_POST['id'];
            $data = [
                'bank_name'  => $_POST['bank_name'],
                'update_time'  => time()
            ];
            $result = Db::name('bank')->where($where)->update($data);
            if($result){
                $this->success(SUCCESS_INFO,cookie('__forward__'));
            }else{
                $this->error(ERROR_INFO,cookie('__forward__'));
            }
        }
        $this->assign('row',Db::name('bank')->where(['id'=>$_GET['id']])->find());
        return $this->fetch('edit');
    }

    public function del(){
        $where['id'] = Request::instance()->get('id');
        $result = Db::name('bank')->where($where)->delete();
        if($result){
            $this->success('删除成功',cookie('__forward__'));
        }else{
            $this->error(ERROR_INFO,cookie('__forward__'));
        }
    }
}