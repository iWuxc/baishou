<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Admin;
use think\Db;

class AdminLogic extends BaseLogic {

    protected $model;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Admin();
    }

    /**
     * 获取会员信息
     * @param array $request
     */
    public function findRow($request = array()){
        $row = $this->model->findRow($request);
        $row['head_pic'] = get_url().$row['head_pic'];
        api_response('1','',(object)$row);
    }

    /**
     * 更新性别
     * @param array $request
     */
    public function updateSex($request = array()){
        check_param('sex,admin_id');
        $result = $this->model->where('admin_id',$request['admin_id'])->update(['sex'=>$request['sex'],'update_time'=>time()]);
        $result ? api_response('1',SUCCESS_INFO) : api_response('0',ERROR_INFO);
    }

    /**
     * 更新昵称
     * @param array $request
     */
    public function updateNickname($request = array()){
        check_param('nickname,admin_id');
        $result = $this->model->where('admin_id',$request['admin_id'])->update(['nickname'=>$request['nickname'],'update_time'=>time()]);
        $result ? api_response('1',SUCCESS_INFO) : api_response('0',ERROR_INFO);
    }

    /**
     * 更新头像
     * @param array $request
     */
    public function updateHead($request = array()){
        $where['admin_id'] = $request['admin_id'];
        $file = request()->file('head_pic');
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'admin');
            if($info){
                $head_pic = '/public/upload/admin/'.$info->getSaveName();
                $result = $this->model->where($where)->update(['head_pic'=>$head_pic,'update_time'=>time()]);
                if($result){
                    api_response('1',SUCCESS_INFO,array('head_pic'=>get_url().$head_pic));
                }else{
                    api_response('0',ERROR_INFO);
                }
            }else{
                // 上传失败获取错误信息
                api_response('0',$file->getError());
            }
        }
    }

    /**
     * 更换手机号
     * @param array $request
     */
    public function updateMobile($request = array()){
        check_param('admin_id,mobile');
        $where['admin_id'] = $request['admin_id'];
        $mobile = $this->model->where('mobile',$request['mobile'])->value('mobile');
        if(!empty($mobile)){
            api_response('0','该手机号已注册');
        }
        $data['mobile'] = $request['mobile'];
        $result = $this->model->where($where)->update($data);
        $result ? api_response('1',SUCCESS_INFO) : api_response('0',ERROR_INFO);
    }

    /**
     * 更新密码
     * @param array $request
     */
    public function updatePass($request = array()){
        check_param('admin_id,password,old_password');
        $where['admin_id'] = $request['admin_id'];
        $password = $this->model->where($where)->value('password');
        if($password != encrypt($request['old_password'])){
            api_response('0','原密码输入错误');
        }
        $data['password'] = encrypt($request['password']);
        $data['update_time'] = time();
        $result = $this->model->where($where)->update($data);
        $result ? api_response('1',SUCCESS_INFO) : api_response('0',ERROR_INFO);
    }

    /**
     * 意见反馈
     * @param array $request
     */
    public function feedback($request = array()){
        check_param('contact,content');
        $data['contact'] = $request['contact'];
        $data['content'] = $request['content'];
        $data['addtime'] = time();
        $result = Db::name('feedback')->add($data);
        $result ? api_response('1',SUCCESS_INFO) : api_response('0',ERROR_INFO);
    }
}