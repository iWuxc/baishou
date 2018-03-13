<?php

/**
 * 基类
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\logic;

use think\Model;
use think\Config;
use think\Db;
use app\clientapi\logic\UserCreditLogic as UserCredit;

class BaseLogic extends Model{

    protected $_msg = array();//推送消息内容

    public function __construct($data = [])
    {
        parent::__construct($data);
    }

    /**
     * 获取用户类型
     * @param $type
     * @return string
     */
    protected function getUserType($type){
        if($type == 1){
            return "user_id"; //boss
        }
        return "clerk_id"; //员工
    }


    public function getClientNo($userid){
        $client_no = Db::name('users') -> where('user_id',$userid) -> value('client_no');
        //只返回客户的序列号
        return substr($client_no, -7);
    }

    /**
     * 提款限额
     * @param $user_id
     * @param $apply_amount
     * @return bool
     */
    public function withdrawal_limit($user_id, $apply_amount){
        $UserCredit = new UserCredit();
        //获取个人信用信息
        $creditInfo = $UserCredit -> getRow($user_id);
        //查看提款限额
        if($creditInfo['credit_rest'] > $apply_amount){
            return true;
        }
        return false;
    }

    /*
     * 判断当前抵押率是否小于审批抵押率
     */
    public function is_check_rate($user_id){
        //查询最近一条抵押品的提交记录
        $where['user_id'] = $user_id;
        $param['order'] = 'id desc';
        $pawn_info = Db::name('pawn_applygreen') -> where($where) -> order($param['order']) -> find();
        //当最近一条未审核时,需要计算该家具对审批抵押率的影响
        if(!empty($pawn_info) && $pawn_info['status'] == 3){
            $r = $this -> deal_outbound($user_id, $pawn_info['pawn_id']);
            //如果触发人工审核, 则直接禁止提交
            if($r['res'] == 2){
                return false;
            }
        }
        //若没有提交记录,则直接进行计算当前抵押率对审批抵押率的影响
        if(!$this -> rate_check($user_id)){
            return false;
        }
        return true;
    }

    /*
     * 直接计算当前抵押率对抵押率的影响
     */
    public function rate_check($user_id){
        $UserCredit = new UserCredit();
        //获取个人信用信息
        $credit_info = $UserCredit -> getRow($user_id);
        if(!empty($credit_info)){//可能是1.11%这样的数, 比例增大至10000倍, 对比整数
            if(($credit_info['now_rate'] * 10000) >= ($credit_info['check_rate'] * 10000)){
                return false;
            }
            return true;
        }
    }

    /**
     * 判断抵押品解压方式
     * @param $user_id
     * @param $pawn_id
     * @return bool
     */
    public function deal_outbound($user_id, $pawn_id){
        $UserCredit = new UserCredit();
        //获取个人信用信息
        $credit_info = $UserCredit -> getRow($user_id);

        //获取家具的最新评估值
        $pawn_new_val = Db::name('pawn') -> where(array('pawn_id'=>$pawn_id)) -> value('new_value');
        if(!$credit_info || !$pawn_new_val){
            return array('res'=>0); //走人工审核
        }
        $credit_used = $credit_info['credit_used']; //已使用额度
        $assess_total = $credit_info['assess_total']; //评估总值
        $check_rate = $credit_info['check_rate']; //审批抵押率

        $temp_total = $assess_total - $pawn_new_val; //变化时的评估总值(当有家具商品出库时触动)
        if($temp_total == 0 && $credit_used > 0){
            return array('res'=>2);
        }
        //+++++++++++++++++++++++++计算当前抵押率++++++++++++++++++++++++++++++//
        $cur_rate = round(($credit_used / $temp_total), 4) * 10000; //当前抵押率

        //$credit_used = ($temp_total * $check_rate) - $credit_info['credit_rest'];//已使用弃用

        $credit_rest =($temp_total * ($credit_info['check_rate'] / 100)) - $credit_used ;

        $check_rate = $check_rate * 100;
        //+++++++++++++++++++++++++计算当前抵押率++++++++++++++++++++++++++++++//
        if($cur_rate <= $check_rate){//在额度范围之内, 走自动解压
            return array('res'=>1, 'assess_total'=>$temp_total, 'cur_rate'=>($cur_rate/100), 'credit_used'=>$credit_used,'credit_rest'=>$credit_rest);
        }
        return array('res'=>2); //走人工审核
    }

    /*
     * 家具操作日志
     * logtype:操作人类型:1后台人员ID,2用户ID
     * pawn_id:家具ID
     * action:动作(方法)
     * note:操作备注
     */
    public function pawnLog($logtype,$pawn_id,$action,$note=''){
        $furniture = M('pawn')->where(array('pawn_id'=>$pawn_id))->find();
        $data['pawn_id'] = $pawn_id;             //家具ID
        $data['act_desc'] = $action;             //动作描述
        $data['log_note'] = $note;               //操作备注
        $data['log_time'] = time();
        if($logtype ==1){
            $data['log_type'] = 1;
            $data['log_user'] = session('admin_id');
        }
        if($logtype == 2){
            $data['log_type'] = 2;
            $data['log_user'] = $furniture['user_no'];
        }
        return M('pawn_log')->add($data);
    }

    /**
     * 消息推送
     * @param array $data
     * $data = [
     *      'type' => '',
     *      'user_id' => '',
     *      'title' => '',
     *      'content' => '',
     *      'add_time' => '',
     * ];
     * @return bool
     */
    public function insertPushMsg(array $data){
        $param['type'] = $data['type'];
        $param['user_id'] = $data['user_id'];
        $param['title'] = $data['title'];
        $param['content'] = serialize($data['content']);
        $param['add_time'] = time();
        $param['status'] = 0;
        if(isset($data['pawn_id']) && !empty($data['pawn_id'])){
            $param['pawn_id'] = $data['pawn_id'];
        }
        $res = Db::name('client_message') -> insert($param);
        if($res){
            return true;
        }
        return false;
    }

    /*
     * 按需索取-pawn
     */
    public function getPawnInfo($pawn_id){
        $info = Db::name('pawn') -> field('pawn_id, pawn_name, pawn_no, store_id, user_id, user_name, store_name, store_no, new_value') -> where('pawn_id',$pawn_id) -> find();
        if($info){
            $img = Db::name('pawn_imgs') -> field('img_url') -> where('pawn_id',$pawn_id) -> find();
            if($img){
                $info['pawn_pic'] = $img ? get_url().$img['img_url'] : '';
            }
            return $info;
        }
        return array();
    }

    public function getUserInfo($user_id, $type = 1){
        if($type == 2){
            $info = Db::name('user_store_clerk') -> field('cid,mobile') -> where('clerk_id',$user_id) -> find();
        }
        $info = Db::name('users') -> field('cid,xd_id,mobile') -> where('user_id',$user_id) -> find();
        return $info;
    }
}