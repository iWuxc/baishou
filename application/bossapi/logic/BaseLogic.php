<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;
use think\Model;
use think\Config;
class BaseLogic extends Model{

    public function __construct($data = []) {
        parent::__construct($data);
    }

    /*
    * 个人信用操作日志
    * logtype:操作人类型:1后台人员ID,2前台用户ID
    * user_id:用户ID
    * action:动作(方法)
    * note:操作详情
    */
    public function userCreditlog($admin_id,$logtype,$user_id,$action,$note=''){
        $data['user_id'] = $user_id;
        $data['act_desc'] = $action;
        $data['log_note'] = $note;
        $data['log_time'] = time();
        $data['log_type'] = $logtype;
        $data['log_user'] = $admin_id;
        $log = M('user_credit_log');
        return $log->add($data);
    }

}