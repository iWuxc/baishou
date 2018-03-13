<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Credit;
use app\bossapi\model\User;
use think\Db;
use think\Loader;

class CreditLogic extends BaseLogic {

    protected $model;

    protected $uc;

    protected $user;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Credit();
        $this->uc = Db::name('user_credit');
        $this->user = new User();
    }

    /**
     * 存在授信额度的用户列表
     * @param array $request
     * @return mixed
     */
    public function getUser($request = array()){
        check_param('admin_id,role_id');
        if($request['role_id'] == 4){
            $param['where']['xd_id'] = $request['admin_id'];
            $param['where']['is_approval'] = 2;
        }
        elseif($request['role_id'] == 2){
            //$param['where']['fk_id'] = $request['admin_id'];
            $param['where']['is_approval'] = 1;
        }

        $param['parameter'] = $request;
        $param['order'] = 'user_id desc';
        $model = new User();
        $list = $model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['reg_time'] = date('Y-m-d',time());
                //状态
                $credit = Db::name('credit_conclusion')->where('user_id',$val['user_id'])->field('id,status')->find();
                if(!empty($credit)){
                    $list['list'][$key]['credit_id'] = $credit['id'];
                    $list['list'][$key]['status'] = $credit['status'];
                }else{
                    $list['list'][$key]['credit_id'] = 0;
                    $list['list'][$key]['status'] = 1;
                }
            }
        }
        return $list['list'];
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        check_param('admin_id,role_id');
        if($request['role_id'] == 4){
            $param['where']['xd_id'] = $request['admin_id'];
        }
        $param['parameter'] = $request;
        $param['order'] = 'add_time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['reg_time'] = date('Y-m-d H:i:s',$val['add_time']);
                //客户类型
                $type = Db::name('users')->where('user_id',$val['user_id'])->value('type');
                if($type == 2){
                    $list['list'][$key]['user_name'] = Db::name('user_enterprise')->where('user_id',$val['user_id'])->value('name');
                }
            }
        }
        return $list['list'];
    }

    /**
     * 客户额度
     * @param array $request
     */
    public function creditDetail($request = array()){
        check_param('user_id');
        $where['cc.user_id'] = $request['user_id'];
        $row = $this->model->alias('cc')
            ->where($where)
            ->field('uc.credit,uc.credit_used,uc.credit_rest,cc.term,cc.is_loop,cc.draw_strtime,cc.draw_stptime,cc.inter_rate,cc.store_sum,
                    uc.assess_total,uc.check_rate,uc.now_rate')
            ->join('__USER_CREDIT__ uc','uc.user_id = cc.user_id')
            ->find();
        //dump($this->model->getLastSql());die;
        if(!empty($row)){
            $row['inter_rate'] = $row['inter_rate'].'%';
            $row['check_rate'] = $row['check_rate'].'%';
            $row['now_rate'] = $row['now_rate'].'%';
            $row['is_loop'] = $row['is_loop'] == 1 ? '是' : '否';
            $row['max_amount'] = $row['credit_rest'];
        }else{
            $row = (object)array();
        }
        api_response('1','',$row);
    }

    /**
     * 授信额度详情信息
     * @param array $request
     */
    public function detail($request = array()){
        check_param('user_id,role_id,admin_id');
        $where['u.user_id'] = $request['user_id'];
        if($request['role_id'] == 4){ // 不为超管时获取指定管理的客户
            $where['u.xd_id'] = $request['admin_id'];
        }
        //授信额度信息
        $credit = $this->uc->where('user_id',$request['user_id'])->field('credit,credit_rest,check_rate,assess_total')->find();
        if(!empty($credit)){
            $credit['max_amount'] = numberFormat(($credit['check_rate'] / 100) * $credit['assess_total']);
        }else{
            $credit = (object)array();
        }
        //查询个人信息
        $user = $this->user->where($where)->alias('u')
            ->field('u.user_id,u.name,u.sex,u.mobile,ud.credential_type,ud.id_number,ud.account_address,ud.home_address')
            ->join('__USER_DETAIL__ ud','ud.user_id = u.user_id')
            ->find();
        if(!empty($user)){
            //查询企业信息
            $user_enterprise = Db::name('user_enterprise')->where('user_id',$request['user_id'])
                ->field('enterprise_id,name,legal_person,reg_capital,business_no,main_business,con_workmobile,reg_address,operating_years')
                ->find() ? : (object)array();
            //授信额度审核状态
            $credit_status = $this->model->where('user_id',$request['user_id'])->field('status,com_remarks')->find() ? : (object)array();
        }else{
            $user = (object)array();
            $user_enterprise = (object)array();
            $credit_status = (object)array();
        }

        $data = array(
            'credit_status'     => $credit_status,
            'credit'            => $credit,
            'user'              => $user,
            'user_enterprise'   => $user_enterprise
        );

        api_response('1','',$data);
    }

    /**
     * 审批结论
     * @param array $request
     */
    public function showCredit($request = array()){
        check_param('credit_id');
        $where['id'] = $request['credit_id'];
        $row = $this->model
            ->where($where)
            ->field('user_name,credit,check_rate,str_time,stp_time,draw_strtime,draw_stptime,service_rate,inter_rate,
            price_rate,store_sum,assurer_mode,assurer_name,repayment,loan_con,loan_admin,add_time,fk_id')
            ->find();
        if(!empty($row)){
            $row['inter_rate'] = $row['inter_rate'].'%';
            $row['check_rate'] = $row['check_rate'].'%';
            $row['service_rate'] = $row['service_rate'].'%';
            $row['str_time'] = date('Y-m-d',$row['str_time']);
            $row['stp_time'] = date('Y-m-d',$row['stp_time']);
            $row['draw_strtime'] = date('Y-m-d',$row['draw_strtime']);
            $row['draw_stptime'] = date('Y-m-d',$row['draw_stptime']);
            $row['addtime'] = date('Y-m-d H:i:s',$row['add_time']);
            $str = '';
            if(!empty($row['loan_con'])){
                foreach (unserialize($row['loan_con']) as $val){
                    $str .= $val.';';
                }
            }
            $row['loan_con'] = substr($str,0,-1);
            if($row['assurer_mode'] == 1) $row['assurer_mode'] = '新增保证人';
            elseif($row['assurer_mode'] == 2) $row['assurer_mode'] = '新增房产抵押';
            elseif($row['assurer_mode'] == 3) $row['assurer_mode'] = '其他担保方式';

            if($row['repayment'] == 1) $row['repayment'] = '按月付息到期还本';
            elseif($row['repayment'] == 2) $row['repayment'] = '额度内随借随还';
            elseif($row['repayment'] == 3) $row['repayment'] = '按月付息按还款计划还本';

            //获取审批人
            $row['admin_name'] = Db::name('admin')->where('admin_id',$row['fk_id'])->value('user_name') ? : '';
        }

        api_response('1','',$row);
    }

    /**
     * 否决授信额度申请
     * @param array $request
     */
    public function cancel($request = array()){
        check_param('credit_id,admin_id,user_id');
        $error = 0;
        $this->model->startTrans();
        try{
            $title = '授信否决';
            $body = empty($request['remarks']) ? '授信否决' : $request['remarks'];
            $user = $this->user->where('user_id',$request['user_id'])->field('name,client_no,is_lock,xd_id,fk_id')->find();
            if($user['fk_id'] == $request['admin_id']){
                api_response('0','不能操作自己的');
            }
            $admin = Db::name('admin')->where('admin_id',$request['admin_id'])->field('cid,mobile,nickname')->find();
            $admin_ = Db::name('admin')->where('admin_id',$user['xd_id'])->field('cid,mobile,nickname')->find();
            $where['id'] = $request['credit_id'];
            $data['status'] = 3;
            $data['fh_name'] = $admin['nickname'];
            $data['fh_id'] = $request['admin_id'];
            $data['com_remarks'] = $request['remarks'];
            $data['update_time'] = time();
            $result = $this->model->where($where)->update($data);
            if(!$result){
                $error = 1;
            }

            $is_approval = $this->user->where('user_id',$request['user_id'])->update(['is_approval'=>3,'update_time'=>time()]);
            if(!$is_approval){
                $error = 1;
            }

            //增加记录日志
            $data_ = [
                'user_id'       => $request['user_id'],
                'user_no'       => $user['client_no'],
                'action_user'   => $request['admin_id'],
                'user_status'   => $user['is_lock'],
                'credit_status' => 3,
                'action_note'   => '授信额度否决操作',
                'log_time'      => time(),
                'status_desc'   => 'APP端操作'
            ];
            $r = Db::name('approval_action')->add($data_);
            if(!$r){
                $error = 1;
            }

            //发送站内信
            $serialize = [
                'type'          => 2,
                'apply_amount'  => $this->model->where($where)->value('credit'),
                'credit_id'     => $request['credit_id'],
                'user_id'       => $request['user_id'],
                'user_name'     => $user['name'],
                'reg_time'      => date('Y-m-d H:i:s',time()),
                'remarks'       => $request['remarks']
            ];
            $data__ = [
                [
                    'admin_id'  => $user['xd_id'],
                    'title'     => $title,
                    'type'      => 4,
                    'content'   => serialize($serialize),
                    'add_time'  => time()
                ]
            ];
            //获取全部分控
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
                foreach ($fk_list as $key=>$val){
                    $data__ []= [
                        'admin_id'  => $val['admin_id'],
                        'title'     => $title,
                        'type'      => 4,
                        'content'   => serialize($serialize),
                        'add_time'  => time()
                    ];
                }
            }
            $send = Db::name('message')->insertAll($data__);
            if(!$send){
                $error = 1;
            }

            if($error == 0){
                $this->model->commit();
                //消息推送
                if(!empty($admin['cid'])){
                    pushMessageToSingle(
                        array(
                            'cid' => $admin['cid'],
                            'title' => $title,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $title,
                                'body'  => $body
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                if(!empty($admin_['cid'])){
                    pushMessageToSingle(
                        array(
                            'cid' => $admin_['cid'],
                            'title' => $title,
                            'body' => $body,
                            'transmissionContent' =>  json_encode([
                                'title' => $title,
                                'body'  => $body
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                if(!empty($admin['mobile'])){
                    //发送短信给风控
                    send_sms($admin['mobile'],$body);
                }
                if(!empty($admin_['mobile'])){
                    //发送短信给信贷
                    send_sms($admin_['mobile'],$body);
                }
                api_response('1',SUCCESS_INFO);
            }else{
                $this->model->rollback();
                api_response('0',ERROR_INFO);
            }
        }catch(\Exception $e){
            $this->model->rollback();
            api_response('0',ERROR_INFO);
        }
    }

    /**
     * 同意操作
     * @param array $request
     */
    public function ok($request = array()){
        check_param('credit_id,user_id,admin_id');
        $error = 0;
        $this->model->startTrans();
        try{
            $user = $this->user->where('user_id',$request['user_id'])->field('name,client_no,is_lock,xd_id,fk_id')->find();
            if($user['fk_id'] == $request['admin_id']){
                api_response('1','不能操作自己的');
            }
            $admin = Db::name('admin')->where('admin_id',$request['admin_id'])->field('cid,mobile,nickname')->find();
            $admin_ = Db::name('admin')->where('admin_id',$user['xd_id'])->field('cid,mobile,nickname')->find();
            $where['id'] = $request['credit_id'];
            $data['status'] = 2;
            $data['fh_name'] = $admin['nickname'];
            $data['fh_id'] = $request['admin_id'];
            $data['com_remarks'] = '复核通过';
            $data['update_time'] = time();
            $result = $this->model->where($where)->update($data);
            if(!$result){
                $error = 1;
            }

            $is_approval = $this->user->where('user_id',$request['user_id'])->update(['is_approval'=>2,'update_time'=>time()]);
            if(!$is_approval){
                $error = 1;
            }

            //增加记录日志
            $data_ = [
                'user_id'       => $request['user_id'],
                'user_no'       => $user['client_no'],
                'action_user'   => $request['admin_id'],
                'user_status'   => $user['is_lock'],
                'credit_status' => 2,
                'action_note'   => '授信额度同意操作',
                'log_time'      => time(),
                'status_desc'   => 'APP端操作'
            ];
            $r = Db::name('approval_action')->add($data_);
            if(!$r){
                $error = 1;
            }

            //发送站内信
            $serialize = [
                'type'          => 1,
                'apply_amount'  => $this->model->where($where)->value('credit'),
                'credit_id'     => $request['credit_id'],
                'user_id'       => $request['user_id'],
                'user_name'     => $user['name'],
                'reg_time'      => date('Y-m-d H:i:s',time()),
                'remarks'       => ''
            ];
            $data__ = [
                [
                    'admin_id'  => $user['xd_id'],
                    'title'     => '授信通过',
                    'type'      => 4,
                    'content'   => serialize($serialize),
                    'add_time'  => time()
                ],
            ];
            //获取全部分控
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
                foreach ($fk_list as $key=>$val){
                    $data__ []= [
                        'admin_id'  => $val['admin_id'],
                        'title'     => '授信通过',
                        'type'      => 4,
                        'content'   => serialize($serialize),
                        'add_time'  => time()
                    ];
                }
            }
            $send = Db::name('message')->insertAll($data__);
            if(!$send){
                $error = 1;
            }
            if($error == 0){
                $this->model->commit();
                $title = '授信通过';
                $body = '授信通过';
                //消息推送
                if(!empty($admin['cid'])){
                    pushMessageToSingle(
                        array(
                            'cid' => $admin['cid'],
                            'title' => $title,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $title,
                                'body'  => $body
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                if(!empty($admin_['cid'])){
                    pushMessageToSingle(
                        array(
                            'cid' => $admin_['cid'],
                            'title' => $title,
                            'body' => $body,
                            'transmissionContent' =>  json_encode([
                                'title' => $title,
                                'body'  => $body
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                if(!empty($admin['mobile'])){
                    //发送短信给风控
                    send_sms($admin['mobile'],$body);
                }
                if(!empty($admin_['mobile'])){
                    //发送短信给信贷
                    send_sms($admin_['mobile'],$body);
                }
                api_response('1',SUCCESS_INFO);
            }else{
                $this->model->rollback();
                api_response('1',ERROR_INFO);
            }
        }catch (\Exception $e){
            $this->model->rollback();
            api_response('1',ERROR_INFO);
        }


    }


    /**
     * 填写审批结论 （废弃）
     * @param array $request
     */
    public function ok__($request = array()){
        $validate = Loader::validate('CreditConclusion');
        $data = $request;
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            api_response('0',$error_msg[0]);
        }
        $data['str_time'] = strtotime($request['str_time']);
        $data['stp_time'] = strtotime($request['stp_time']);
        $data['store_sum'] = count(explode(',',$request['pawn_store']));
        $data['status']   = 3;
        $error = 0;
        $this->model->startTrans();
        try{
            $admin_id = Db::name('users')->where('user_id',$request['user_id'])->value('fk_id');
            if($admin_id != $request['admin_id']){
                api_response('0','不能操作自己的');
            }
            $result = $this->model->add($data);
            if(!$result){
                $error = 1;
            }

            $uc = $this->uc->where('user_id',$request['user_id'])->field('assess_total')->find();
            //更新个人信用表信息
            //剩余可使用额度(评估总值*审批抵押率-已使用额度)
            $data_ = [
                'credit'        => $request['credit'],
                'credit_rest'   => $uc['assess_total'] * $request['check_rate'],
                'check_rate'    => $request['check_rate'],
                'updtime'       => time()
            ];
            $uc_res = $this->uc->where('user_id',$request['user_id'])->update($data_);
            if(!$uc_res){
                $error = 1;
            }

            //发送站内信

            if($error == 0){
                $this->model->commit();
                api_response('1',SUCCESS_INFO);
            }else{
                $this->rollback();
                api_response('0',ERROR_INFO);
            }
        }catch(\Exception $e){
            $this->model->rollback();
            api_response('0',ERROR_INFO);
        }
    }


}