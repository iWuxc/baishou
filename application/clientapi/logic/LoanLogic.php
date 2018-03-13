<?php

/**
 * 首页额度信息
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\logic;

use think\Db;
use app\clientapi\model\Loan;
use think\Exception;
use think\Loader;

class LoanLogic extends BaseLogic{

    protected $model;

    protected $cd;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this -> model  = new Loan();
    }

    /*
     * 首页信息-提款记录
     * @param $userid
     * @param $request
     */
    public function getLoanDetail($userid, $request){
        $param['where']['user_id'] = $userid;
        $param['order'] = "addtime desc";
        $param['p'] = $request['p'];
        $param['field'] = 'id loan_id,loan_number,apply_amount,stp_time';
        //获取提款信息
        $loanList = $this -> model -> getLoanList($param);
        if(!empty($loanList)){
            foreach ($loanList as $key=>$val){
                $loanList[$key]['loan_level'] = loan_level($val['apply_amount']);
                $loanList[$key]['stp_time'] = date('Y-m-d',$val['stp_time']);
                //授信额度期限
                $loanList[$key]['term'] = Db::name('credit_conclusion')->where('user_id',$userid)->value('term');
            }
        }
        return $loanList;
    }

    /**
     * 提款申请添加页面
     * @param $userid
     */
    public function addLoan($userid){
        //获取用户剩余可用额度
        $credit_info = Db::name('user_credit') -> field('credit_rest') -> where('user_id',$userid) -> find();
        //获取额度期限
        $term = Db::name('credit_conclusion') -> where('user_id', $userid) -> value('term');
        $res = array('credit_rest'=>$credit_info['credit_rest'], 'term'=>$term);
        toJson("1", "请求成功", $res);
    }

    /**
     * 贷款提交操作
     * @param array $request
     * @param $userid
     * @throws \think\exception\PDOException
     */
    public function loanHandle($request = array(), $userid){
        $validate = Loader::validate('Loan');
        $data = $request;
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            toJson("0",$error_msg[0]);
        }

        //判断提款限额
        $is_loan = $this -> withdrawal_limit($userid, $data['apply_amount']);

        if(!$is_loan){
            toJson("0", '提款超限');
        }

        $data['month_amount'] = trim($data['month_amount'], '%');
        $data['str_time'] = strtotime($request['str_time']);
        $data['stp_time'] = strtotime($request['stp_time']);
        $data['addtime'] = time();
        $clientNo = $this -> getClientNo($userid);
        $data['loan_number'] = $clientNo.date("YmdHi");
        $data['user_id'] = $userid;
        $data['status'] = 0;
        $data['is_check'] = 1;
        //获取客户姓名
        $data['user_name'] = Db::name('users') -> where('user_id',$userid) -> value('name');
        $data['inter_rate'] = Db::name('credit_conclusion') -> where('user_id',$userid) -> value('inter_rate');
        $userInfo = $this -> getUserInfo($userid);
        $error = 0;
        Db::transaction();
        try{
            //1. 记录贷款信息
            $r1 = Db::name('Loan') -> add($data); //插入数据
            !$r1 && $error = 1;

            //2. 修改使用额度
            $credit = $this -> getCredit($userid);
            $param['credit_used'] = $credit['credit_used'] + $data['apply_amount']; //已使用额度
            $param['credit_rest'] = $credit['credit_rest'] - $data['apply_amount']; //剩余可使用额度
            $param['now_rate'] = round(($param['credit_used'] / $credit['assess_total']), 4) * 100; //当前抵押率
            $param['grand_total'] = $credit['grand_total'] + $data['apply_amount']; //历史提款累计金额
            $param['updtime'] = time();
            $r2 = Db::name('user_credit') -> where('user_id',$userid) -> update($param);
            !$r2 && $error = 1;

            //插入日志
            $loan_log = array(
                'user_id' => $userid,
                'log_type' => 2,
                'log_user' => $userid,
                'log_time' => time(),
                'act_desc' => '提款操作',
                'log_note' => "成功提款{$data['apply_amount']}元, 剩余可使用额度为{$param['credit_rest']}元"
            );
            Db::name('user_credit_log') -> add($loan_log);

            //3. 推送消息(直接成功贷款) - 客户端
            $this -> _msg = array(
                'type' => 3,
                'user_id' => $userid,
                'title' => '提款成功',
                'content' => array(
                    'user_id' => $userid,
                    'user_name' => $data['user_name'],
                    'apply_amount' => $data['apply_amount'],
                    'rem_money' => $param['credit_rest'],
                    'loan_number' => $data['loan_number'],
                    'str_time' => date("Y-m-d", $data['str_time']),
                    'stp_time' => date("Y-m-d", $data['stp_time']),
                ),
            );
            $r3 = $this -> insertPushMsg($this->_msg);
            !$r3 && $error = 1;

            //4. 推送消息(直接成功贷款) - 管理端 信贷
            $xd_msg = array(
                'admin_id'  => $userInfo['xd_id'],
                'title'     => '提款成功',
                'type'      => 3,
                'content'   => serialize(array(
                    "apply_amount" => $data['apply_amount'],
                    "user_id" => $userid,
                    "user_name" => $data['user_name'],
                    "str_time" => date("Y-m-d", $data['str_time']),
                    "stp_time" => date("Y-m-d", $data['stp_time']),
                    "remarks" => '自动审核通过'
                )),
                'add_time'  => time()
            );
            $r4 = Db::name('message')->insert($xd_msg);
            !$r4 && $error = 1;

            //5. 推送->客户端
            $pushMsg = array(
                'name' => $data['user_name'],
                'date' => date('m月d日'),
            );
            PushMessage(1, $userInfo['cid'], '提款成功', $pushMsg);

            $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id', $userInfo['xd_id']) -> find();

            //6. 推送->管理端推送
            $pushMsg_xd = array(
                'name' => $data['user_name'],
                'date' => date('m月d日'),
                'apply_mount' => $data['apply_amount']
            );
            PushMessage(3, $xd_info['cid'], '提款成功', $pushMsg_xd, 'boss');

            //7. 发送短信 - 信贷经理
            $xd_sms_msg = array(
                'name' => $data['user_name'],
                'date' => date('m月d日'),
                'apply_mount' => $data['apply_amount']
            );
            sendSms(3, $xd_info['mobile'], $xd_sms_msg);

            //8. 发送短信 - 客户
            $c_sms_msg = array(
                'name' => $data['user_name'],
                'date' => date('m月d日'),
            );
            sendSms(4, $userInfo['mobile'], $c_sms_msg);

            //给所有风控推送
            $fk_infos = Db::name('admin') -> field('admin_id,cid,user_name,mobile') -> where('role_id=2') -> select();
            foreach($fk_infos as $k => $v){
                //站内信
                $fk_msg = array(
                    'admin_id'  => $v['admin_id'],
                    'title'     => '提款成功',
                    'type'      => 3,
                    'content'   => serialize(array(
                        "apply_amount" => $data['apply_amount'],
                        "user_id" => $userid,
                        "user_name" => $data['user_name'],
                        "str_time" => date("Y-m-d", $data['str_time']),
                        "stp_time" => date("Y-m-d", $data['stp_time']),
                        "remarks" => '自动审核通过'
                    )),
                    'add_time'  => time()
                );
                Db::name('message')->insert($fk_msg);

                //推送
                $pushMsg_fk = array(
                    'name' => $data['user_name'],
                    'date' => date('m月d日'),
                    'apply_mount' => $data['apply_amount']
                );
                PushMessage(3, $v['cid'], '提款成功', $pushMsg_fk, 'boss');

                //发短信
                $fk_sms_msg = array(
                    'name' => $data['user_name'],
                    'date' => date('m月d日'),
                    'apply_mount' => $data['apply_amount']
                );
                sendSms(3, $v['mobile'], $fk_sms_msg);
            }
            Db::commit();
            if($error == 0){
                tojson('1', '提交成功', array('apply_amount'=>$data['apply_amount'],'account'=>$data['account']));
            }
            tojson('0', '操作失败');
        }catch(Exception $e){
            //回滚事务
            Db::rollback();
            toJson('0','提交失败', []);
        }
    }

    /**
     * 提款记录
     * @param $userid
     * @param $p
     * @return array
     */
    public function getLoanList($userid, $p){
        $param['where']['user_id'] = $userid;
        $param['where']['is_check'] = array('in', '1,2,3,9');
        $param['field'] = "id loan_id,loan_number,apply_amount, addtime,is_check";
        $param['p'] = $p;
        $param['order'] = "addtime desc";
        $result = $this -> model -> getLoanList($param);
        if(!empty($result)){
            foreach($result as $key=>$val){
                $result[$key]['loan_level'] = loan_level($val['apply_amount']);
                $result[$key]['addtime'] = date("Y-m-d");
            }
        }
        return $result;
    }

    /**
     * 提款单条数据
     * @param $loan_id
     * @param $userid
     * @return mixed
     */
    public function loanDetail($loan_id,$userid){
        $where['id'] = $loan_id;
        $where['user_id'] = $userid;
        $result = $this -> model -> detail($where);
        if($result){
            $result['str_time'] = date("Y-m-d", $result['str_time']);
            $result['stp_time'] = date("Y-m-d", $result['stp_time']);
            return $result;
        }
        return (object)array();
    }

    /*
     * 获取信用额度信息
     */
    public function getCredit($userid){
        $credit = Db::name('user_credit') -> where('user_id',$userid) -> find();
        return $credit;
    }
}