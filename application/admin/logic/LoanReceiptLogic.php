<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 11:09
 */

namespace app\admin\logic;
use app\admin\model\LoanReceipt;
use think\Db;
use think\Loader;
use think\Model;

class LoanReceiptLogic extends LoanBaseLogic {

    private $model;

    function __construct($data = []){
        parent::__construct($data);
        $this->model = new LoanReceipt();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array(),$flag = ''){
        if(!empty($request['user_id'])){
            $param['where']['user_id'] = $request['user_id'];
        }
        if(!empty($request['user_name'])){
            $param['where']['user_name'] = $request['user_name'];
        }
        if(!empty($request['is_repay'])){
            $param['where']['is_repay'] = ['in',$request['is_repay']];
        }
        if(!empty($flag)){
            $param['where']['loan_id'] = 0;
        }
        $param['where']['status'] = 1;
        //时间段查询
        if(!empty($request['add_time_begin']) && !empty($request['add_time_end'])){
            $param['where']['addtime'] = ['between time',[$request['add_time_begin'],$request['add_time_end']]];
        }
        $param['parameter'] = $request;
        $param['order'] = 'addtime desc,id desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
                $list['list'][$key]['str_time'] = date('Y-m-d',$val['str_time']);
                $list['list'][$key]['stp_time'] = date('Y-m-d',$val['stp_time']);
                $list['list'][$key]['admin_name'] = Db::name('admin')->where('admin_id',$val['admin_id'])->value('user_name');
                //查询已还款金额
                $list['list'][$key]['actual_amount'] = Db::name('repay')->where('lr_id',$val['id'])->sum('actual_amount') ? : 0;

                if($val['status'] == 1){
                    $list['list'][$key]['status_name'] = '已放款';
                }else{
                    $list['list'][$key]['status_name'] = '待放款';
                }

                if($val['types'] == 1){
                    $list['list'][$key]['types_name'] = '平台默认';
                    $list['list'][$key]['yunyin_status'] = '--';
                }elseif($val['types'] == 2){
                    $list['list'][$key]['types_name'] = '云南信托';
                    $list['list'][$key]['yunyin_status'] = $this->getYxStatusName($val['yunxin_status']);
                }
                $list['list'][$key]['repay'] = $this->getRStatusName($val['is_repay']);
            }
        }
        return $list;
    }

    /**
     * 单条数据
     * @param array $request
     * @return array|false|\PDOStatement|string|Model
     */
    public function findRow($request = array()){
        $param['where']['id'] = $request['id'];
        $row = $this->model->findRow($param);
        $row['admin_name'] = Db::name('admin')->where('admin_id',$row['admin_id'])->value('user_name');
        $row['addtime'] = date('Y-m-d H:i:s',$row['addtime']);
        $row['str_time'] = date('Y-m-d',$row['str_time']);
        $row['stp_time'] = date('Y-m-d',$row['stp_time']);
        $row['repay'] = $this->getRStatusName($row['is_repay']);
        return $row;
    }

    /**
     * 编辑
     * @param array $request
     * @return array
     */
    public function edit($request = array()){
        $validate = Loader::validate('LoanReceipt');
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            return array('msg'=>$error_msg[0],'status'=>-1,'url'=>cookie('__forward__'));
        }
        if(empty($request['loan_amount'])){
            return array('msg'=>'请输入放款金额','status'=>-1,'url'=>cookie('__forward__'));
        }
        $data = [
            'bank_name'     => $request['bank_name'],
            'account'       => $request['account'],
            'bank_desc'     => $request['bank_desc'],
            'loan_amount'   => $request['loan_amount'],
            'str_time'      => strtotime($request['str_time']),
            'stp_time'      => strtotime($request['stp_time']),
            'updtime'       =>time()
        ];
        $result = $this->model->where('id',$request['id'])->update($data);
        if($result){
            return array('msg'=>'操作成功','status'=>1,'url'=>cookie('__forward__'));
        }else{
            return array('msg'=>'操作异常，请稍后再试','status'=>-1,'url'=>cookie('__forward__'));
        }
    }

    /**
     * 放款操作
     * @param array $request
     * @return array
     */
    public function receipt($request = array()){
        $admin_ = Db::name('admin')->where('admin_id',session('admin_id'))->field('cid,mobile,role_id')->find();
        if($admin_['role_id'] == 4 || $admin_['role_id'] == 5 || $admin_['role_id'] == 3){
            return array('msg'=>'您不能进行此操作','status'=>-1,'url'=>cookie('__forward__'));
        }
        $validate = Loader::validate('LoanReceipt');
        $data = $request;
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            return array('msg'=>$error_msg[0],'status'=>-1,'url'=>cookie('__forward__'));
        }
        $user = Db::name('users')->where('user_id',$request['user_id'])->field('user_id,cid,nickname,mobile,xd_id')->find();
        //获取信贷数据
        $xd =  Db::name('admin')->where('admin_id',$user['xd_id'])->field('cid,mobile')->find();
        //放款凭证号
        $loan_number = 'FK'.Db::name('config')->where('name','bank_number')->value('value');
        $error = 0;
        //启动事务
        $this->model->startTrans();
        try{
            if(strtotime($request['stp_time']) < strtotime($request['str_time'])){
                return array('msg'=>'结束日期必须大于开始日期','status'=>-1,'url'=>cookie('__forward__'));
            }
            if(strtotime($request['str_time']) < strtotime(date('Y-m-d',time()))){
                return array('msg'=>'开始日期不能小于当前日期','status'=>-1,'url'=>cookie('__forward__'));
            }
            //查询凭证号是否使用过
            $loan_number_ = Db::name('loan_receipt')->where('loan_number',$request['loan_number'])->value('id');
            if(!empty($loan_number_)){
                return array('msg'=>'该凭证号已使用过','status'=>-1,'url'=>cookie('__forward__'));
            }
            //调用云信放款接口
            if($request['types'] == 2){
                $yxq = Db::name('yunxin_enterprise')->where('user_id',$request['user_id'])
                    ->field('UniqueId,Corporation,CorporationIdCardNo,BankCardNo,BankCode,BranchBankName,BankCardAttribution')
                    ->find();
                if(!empty($UniqueId)){
                    return array('msg'=>'该用户未创建企业信息','status'=>-1,'url'=>cookie('__forward__'));
                }

                $PaymentBankCards = [
                    'AccountNo'             => $request['account'],
                    'BankCode'              => $yxq['BankCode'],
                    'BranchBankName'        => $yxq['BranchBankName'],
                    'BankCardAttribution'   => $yxq['BankCardAttribution'],
                    'AccountName'           => $request['user_name'],
                    'Amount'                => (float)$request['loan_amount'],
                    'Type'                  => 0
                ];

                $data_yx = [
                    'LoanContractNumber'    => $loan_number,
                    'ContractAmount'        => (float)$request['loan_amount'],
                    'SignDate'              => $request['str_time'],
                    'BeginDate'             => $request['stp_time'],
                    'SignRate'              => 0.0001,
                    'RepaymentCycle'        => '0',
                    'RepaymentMode'         => '2',
                    'RepaymentPeriod'       => '6',
                    'UniqueId'              => $yxq['UniqueId'],
                    'RequestId'             => 'loanquestid-'.$loan_number,
                    'PaymentBankCards'      => $PaymentBankCards
                ];
                $c_r = Db::name('config')->where('name','bank_number')->setInc('value');
                if(!$c_r){
                    return array('msg'=>'放款凭证号生成异常','status'=>-1,'url'=>cookie('__forward__'));
                }
                vendor('YunXin.YunXin');
                $yx = new \YunXin();
                $res = $yx->getYunXin('/EnterPriseLoan/CreateLoan',$data_yx);
                $j_res = json_decode($res,JSON_UNESCAPED_UNICODE);
                if(!$j_res['Status']['IsSuccess']){
                    return array('msg'=>$j_res['Status']['ResponseMessage'],'status'=>-1,'url'=>cookie('__forward__'));
                }
                //存储云信放款信息
                $data_yx['PaymentBankCards'] = json_encode($PaymentBankCards);
                $yx_db_res = Db::name('yunxin_loan')->insert($data_yx);
                if(!$yx_db_res){
                    $error = 1;
                }

                $data['account'] = $yxq['BankCardNo'];
                $data['bank_name'] = Db::name('yunxin_bank')->where('BankCode',$yxq['BankCode'])->value('BankName');
            }

            $data['admin_id'] = session('admin_id');
            $data['apply_amount'] = $request['loan_amount'];
            $data['loan_amount'] = $request['loan_amount'];
            $data['loan_number'] = $loan_number;
            $data['str_time'] = strtotime($request['str_time']);
            $data['stp_time'] = strtotime($request['stp_time']);
            $data['account'] = $request['account'];
            $data['bank_name'] = $request['bank_name'];
            $data['status'] = 1;
            $data['addtime'] = time();
            $data['types'] = $request['types'];

            if($request['types'] == 1){
                if(empty($request['bank_name'])){
                    return array('msg'=>'请选择银行','status'=>-1,'url'=>cookie('__forward__'));
                }
                if(empty($request['account'])){
                    return array('msg'=>'请输入银行卡号','status'=>-1,'url'=>cookie('__forward__'));
                }
                $data['account'] = $request['account'];
                $data['bank_name'] = $request['bank_name'];
            }
            $this->model->data($data);
            $result = $this->model->save();
            if(!$result){
                $error = 1;
            }

            //更新申请表状态为已放款
            $loan = Db::name('Loan')->where('id',$request['loan_id'])->update(['status'=>1,'loan_amount'=>$request['loan_amount']]);
            if(!$loan){
                $error = 1;
            }

            $bank_number_inc = Db::name('config')->where('name','bank_number')->setInc('value');
            if(!$bank_number_inc){
                $error = 1;
            }

            //增加贷款日志
            $data_ = [
                'admin_id'  => session('admin_id'),
                'value'     => $request['loan_id'],
                'action_url'=> 'Admin/LoanReceipt/receipt',
                'action_name'=>'放款操作',
                'action_desc'=>'放款给客户：'.$user['nickname'].'(ID为:'.$user['user_id'].')'.$request['loan_amount'],
                'add_time'  => time()
            ];

            $loan_log = Db::name('loan_log')->insert($data_);
            if(!$loan_log){
                $error = 1;
            }

            if($error == 0){
                $this->model->commit();
                $body = '尊敬的'.$user['nickname'].'，您通过力道金融申请的红木贷已成功发放，请您及时查看相应账户，我们期待与您的深化合作，温馨提醒您珍惜个人信用记录。';
                $body_ = '您的客户'.$user['nickname'].'申请的'.$request['loan_amount'].'红木贷已成功发放。';
                $tit = '放款提醒';
                if(!empty($user['cid'])){
                    //消息推送
                    pushMessageToSingle(
                        array(
                            'cid' => $user['cid'],
                            'title' => $tit,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'client'
                        )
                    );
                    pushMessageToSingle(
                        array(
                            'cid' => $admin_['cid'],
                            'title' => $tit,
                            'body' => $body_,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body_,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                    pushMessageToSingle(
                        array(
                            'cid' => $xd['cid'],
                            'title' => $tit,
                            'body' => $body_,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body_,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                //发送短信给客户
                if(!empty($user['mobile'])){
                    send_sms($user['mobile'],$body);
                }
                //发送短信给自己
                if(!empty($admin_['mobile'])){
                    send_sms($admin_['mobile'],$body_);
                }
                if(!empty($xd['mobile']) && $admin_['mobile'] != $xd['mobile']){
                    send_sms($xd['mobile'],$body_);
                }
            }else{
                $this->model->rollback();
                return array('msg'=>'操作异常，请稍后再试','status'=>-1,'url'=>cookie('__forward__'));
            }
        }catch (\Exception $e){
            $this->model->rollback();
            return array('msg'=>'操作异常，请稍后再试','status'=>-1,'url'=>cookie('__forward__'));
        }
        return array('msg'=>'操作成功','status'=>1,'url'=>cookie('__forward__'));
    }

    /**
     * 主动发放贷款
     * @param array $request
     * @return array
     */
    public function receipts($request = array()){
        $admin_ = Db::name('admin')->where('admin_id',session('admin_id'))->field('cid,mobile,role_id')->find();
        if($admin_['role_id'] == 4 || $admin_['role_id'] == 5 || $admin_['role_id'] == 3){
            return array('msg'=>'您不能进行此操作','status'=>-1,'url'=>cookie('__forward__'));
        }
        //查询当前用户可用额度
        $credit = Db::name('user_credit')->where('user_id',$request['user_id'])->value('credit_rest');
        if($request['loan_amount'] > $credit){
            return array('msg'=>'该客户剩余可用额度不足','status'=>-1,'url'=>cookie('__forward__'));
        }
        $validate = Loader::validate('LoanReceipts');
        $data = $request;
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            return array('msg'=>$error_msg[0],'status'=>-1,'url'=>cookie('__forward__'));
        }

        //放款凭证号
        $loan_number = 'FK'.Db::name('config')->where('name','bank_number')->value('value');

        $error = 0;

        //启动事务
        $this->model->startTrans();
        try{
            if(strtotime($request['stp_time']) < strtotime($request['str_time'])){
                return array('msg'=>'结束日期必须大于开始日期','status'=>-1,'url'=>cookie('__forward__'));
            }
            if(strtotime($request['str_time']) < strtotime(date('Y-m-d',time()))){
                return array('msg'=>'开始日期不能小于当前日期','status'=>-1,'url'=>cookie('__forward__'));
            }
            //查询凭证号是否使用过
            $loan_number_ = Db::name('loan_receipt')->where('loan_number',$request['loan_number'])->value('id');
            if(!empty($loan_number_)){
                return array('msg'=>'该凭证号已使用过','status'=>-1,'url'=>cookie('__forward__'));
            }

            $user = Db::name('users')->where('user_id',$request['user_id'])->field('cid,nickname,mobile,xd_id')->find();

            //调用云信放款接口
            if($request['types'] == 2){
                $yxq = Db::name('yunxin_enterprise')->where('user_id',$request['user_id'])
                    ->field('UniqueId,Corporation,CorporationIdCardNo,BankCardNo,BankCode,BranchBankName,BankCardAttribution')
                    ->find();
                if(!empty($UniqueId)){
                    return array('msg'=>'该用户未创建企业信息','status'=>-1,'url'=>cookie('__forward__'));
                }
                $PaymentBankCards = [
                    'AccountNo'             => $yxq['BankCardNo'],
                    'BankCode'              => $yxq['BankCode'],
                    'BranchBankName'        => $yxq['BranchBankName'],
                    'BankCardAttribution'   => $yxq['BankCardAttribution'],
                    'AccountName'           => $yxq['Corporation'],
                    'Amount'                => (int)$request['loan_amount'],
                    'Type'                  => 0
                ];

                $data_yx = [
                    'LoanContractNumber'    => $loan_number,
                    'ContractAmount'        => (int)$request['loan_amount'],
                    'SignDate'              => $request['str_time'],
                    'BeginDate'             => $request['stp_time'],
                    'SignRate'              => 0.0001,
                    'RepaymentCycle'        => '0',
                    'RepaymentMode'         => '2',
                    'RepaymentPeriod'       => '6',
                    'UniqueId'              => $yxq['UniqueId'],
                    'RequestId'             => 'loanquestid-'.$loan_number,
                    'PaymentBankCards'      => $PaymentBankCards
                ];
                $c_r = Db::name('config')->where('name','bank_number')->setInc('value');
                if(!$c_r){
                    return array('msg'=>'放款凭证号生成异常','status'=>-1,'url'=>cookie('__forward__'));
                }
                vendor('YunXin.YunXin');
                $yx = new \YunXin();
                $res = $yx->getYunXin('/EnterPriseLoan/CreateLoan',$data_yx);
                $j_res = json_decode($res,JSON_UNESCAPED_UNICODE);
                if(!$j_res['Status']['IsSuccess']){
                    return array('msg'=>$j_res['Status']['ResponseMessage'],'status'=>-1,'url'=>cookie('__forward__'));
                }
                //存储云信放款信息
                $data_yx['PaymentBankCards'] = json_encode($PaymentBankCards);
                $yx_db_res = Db::name('yunxin_loan')->insert($data_yx);
                if(!$yx_db_res){
                    $error = 1;
                }
                $data['account'] = $yxq['BankCardNo'];
                $data['bank_name'] = Db::name('yunxin_bank')->where('BankCode',$yxq['BankCode'])->value('BankName');
            }

            //本站贷款信息
            $data['admin_id'] = session('admin_id');
            $data['apply_amount'] = $request['loan_amount'];
            $data['loan_amount'] = $request['loan_amount'];
            $data['loan_number'] = $loan_number;
            $data['str_time'] = strtotime($request['str_time']);
            $data['stp_time'] = strtotime($request['stp_time']);
            $data['status'] = 1;
            $data['addtime'] = time();
            $data['types'] = $request['types'];

            if($request['types'] == 1){
                if(empty($request['bank_name'])){
                    return array('msg'=>'请选择银行','status'=>-1,'url'=>cookie('__forward__'));
                }
                if(empty($request['account'])){
                    return array('msg'=>'请输入银行卡号','status'=>-1,'url'=>cookie('__forward__'));
                }
                $data['account'] = $request['account'];
                $data['bank_name'] = $request['bank_name'];
            }
            $result = $this->model->insert($data);
            if(!$result){
                $error = 1;
            }

            $bank_number_inc = Db::name('config')->where('name','bank_number')->setInc('value');
            if(!$bank_number_inc){
                $error = 1;
            }

            //增加贷款日志
            $data_ = [
                'admin_id'  => session('admin_id'),
                'value'     => 0,
                'action_url'=> 'Admin/LoanReceipt/receipts',
                'action_name'=>'发放贷款操作',
                'action_desc'=>'放款给客户：'.$user['nickname'].'(ID为:'.$user['user_id'].')'.$request['loan_amount'],
                'add_time'  => time()
            ];

            $loan_log = Db::name('loan_log')->insert($data_);
            if(!$loan_log){
                $error = 1;
            }

            //减去剩余可用额度
            $credit_rest = Db::name('user_credit')->where('user_id',$request['user_id'])->setDec('credit_rest',$request['loan_amount']);
            if(!$credit_rest){
                $error = 1;
            }
            //增加已使用额度
            $credit_used = Db::name('user_credit')->where('user_id',$request['user_id'])->setInc('credit_used',$request['loan_amount']);
            if(!$credit_used){
                $error = 1;
            }

            //增加个人授信日志
            $uc_log = new LoanLogic();
            $new_credit_rest =  Db::name('user_credit')->where('user_id',$request['user_id'])->value('credit_rest');
            $ucl = $uc_log->userCreditlog(1,$request['user_id'],'发放贷款操作','剩余可用额度从'.$credit.'变更为'.$new_credit_rest);
            if(!$ucl){
                $error = 1;
            }

            if($error == 0){
                $this->model->commit();
                //获取信贷数据
                $xd =  Db::name('admin')->where('admin_id',$user['xd_id'])->field('cid,mobile')->find();
                $body = '尊敬的'.$user['nickname'].'，您通过力道金融申请的红木贷已成功发放，请您及时查看相应账户，我们期待与您的深化合作，温馨提醒您珍惜个人信用记录。';
                $body_ = '您的客户'.$user['nickname'].'申请的'.$request['loan_amount'].'红木贷已成功发放。';
                $tit = '放款提醒';
                if(!empty($user['cid'])){
                    //消息推送
                    pushMessageToSingle(
                        array(
                            'cid' => $user['cid'],
                            'title' => $tit,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'client'
                        )
                    );
                    pushMessageToSingle(
                        array(
                            'cid' => $admin_['cid'],
                            'title' => $tit,
                            'body' => $body_,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body_,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                    pushMessageToSingle(
                        array(
                            'cid' => $xd['cid'],
                            'title' => $tit,
                            'body' => $body_,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body_,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                //发送短信给客户
                if(!empty($user['mobile'])){
                    send_sms($user['mobile'],$body);
                }
                //发送短信给自己
                if(!empty($admin_['mobile'])){
                    send_sms($admin_['mobile'],$body_);
                }
                if(!empty($xd['mobile']) && $admin_['mobile'] != $xd['mobile']){
                    send_sms($xd['mobile'],$body_);
                }
            }else{
                $this->model->rollback();
                return array('msg'=>'操作异常，请稍后再试','status'=>-1,'url'=>cookie('__forward__'));
            }
        }catch (\Exception $e){
            $this->model->rollback();
            return array('msg'=>'操作异常，请稍后再试','status'=>-1,'url'=>cookie('__forward__'));
        }
        return array('msg'=>'操作成功','status'=>1,'url'=>url('LoanReceipt/lists'));
    }


}