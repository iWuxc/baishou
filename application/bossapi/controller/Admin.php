<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;
use app\bossapi\logic\AdminLogic;
use think\Db;

class Admin extends Base {

    protected $logic;

    public function _initialize(){
        parent::_initialize();
        $this->logic = new AdminLogic();
    }

    public function index(){
        return $this->fetch();
    }

    /**
     * 更新昵称
     */
    public function updateNickname(){
        $this->logic->updateNickname(request()->post());
    }

    /**
     * 更新性别
     */
    public function updateSex(){
        $this->logic->updateSex(request()->post());
    }

    /**
     * 更新头像
     */
    public function updateHead(){
        $this->logic->updateHead(request()->post());
    }

    /**
     * 更换手机
     */
    public function updateMobile(){
        $this->logic->updateMobile(request()->post());
    }

    /**
     * 更新密码
     */
    public function updatePass(){
        $this->logic->updatePass($_REQUEST);
    }

    /**
     * 意见反馈
     */
    public function feedback(){
        $this->logic->feedback(request()->post());
    }

    /**
     * 客服电话
     */
    public function tel(){
        $tel = Db::name('config')->where('name','phone')->value('value');
        api_response('1','',['tel'=>$tel]);
    }

}