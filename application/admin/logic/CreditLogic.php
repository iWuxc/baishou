<?php

/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 授信额度逻辑类
 * Author: iWuxc
 * Date: 2017-12-9
 */

namespace app\admin\logic;

use think\Model;
use think\Db;

class CreditLogic extends Model{

    /**
     * 获取客户是否添加审批结论信息
     * @param $user_id
     * @return mixed
     */
    public function get_credit_info($user_id){
        $count = M("credit_conclusion") -> where("user_id=".$user_id) -> count();
        return $count;
    }

    /**
     * 获取客户详情
     * @param $user_id
     * @return mixed
     */
    public function get_credit_detail($user_id){
       $info = M('credit_conclusion') -> where('user_id='.$user_id) -> find();
       return $info;
    }

    /**
     * 审批日志操作
     * @param $user_id
     * @param $action
     * @param string $note
     * @return mixed
     */
    public function approveActionLog($user_id,$action,$note=''){
        $user = M('users')->where(array('user_id'=>$user_id))->find();
        $data['user_id'] = $user_id;
        $data['user_no'] = $user['client_no'];;
        $data['action_user'] = session('admin_id');
        $data['action_note'] = $note;
        $data['user_status'] = $user['is_approval'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;
        return Db::name('approval_action')->add($data);//客户操作记录
    }


    /**
     * 录入个人信用信息操作
     * @param $user_id
     * @return bool
     */
    public function credit_add_action($user_id){
        if(!$user_id){
            return false;
        }
        $credit_info = M('credit_conclusion') -> field('credit, check_rate, store_sum') -> where(array('user_id'=>$user_id)) -> find();
        $condition = [];
        $condition['user_id'] = $user_id;
        $condition['credit'] = $credit_info['credit']; //审批额度, 参考值
        $condition['check_rate'] = $credit_info['check_rate']; //审批抵押率
        $condition['store_total'] = $credit_info['store_sum']; //抵押门店数量
        $condition['addtime'] = time();
        return Db::name('user_credit') -> add($condition);
    }
}