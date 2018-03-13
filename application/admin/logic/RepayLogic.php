<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/15
 * Time: 11:15
 */

namespace app\admin\logic;
use app\admin\model\Repay;
use think\Db;
use think\Loader;
use think\Model;

class RepayLogic extends Model{

    private $model;

    function __construct($data = []){
        parent::__construct($data);
        $this->model = new Repay();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        if(!empty($request['user_id'])){
            $param['where']['user_id'] = $request['user_id'];
        }
        if(!empty($request['user_name'])){
            $param['where']['user_name'] = $request['user_name'];
        }

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
                $list['list'][$key]['repay_date'] = date('Y-m-d',$val['repay_date']);
                $list['list'][$key]['actual_date'] = date('Y-m-d',$val['actual_date']);
                if($val['types'] == 1){
                    $list['list'][$key]['types_name'] = '平台默认';
                }else{
                    $list['list'][$key]['types_name'] = '云南信托';
                }
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
        $row['repay_date'] = date('Y-m-d',$row['repay_date']);
        $row['actual_date'] = date('Y-m-d',$row['actual_date']);
        $row['types'] == 1 ?  $row['types_name'] = '平台默认' : $row['types_name'] = '云南信托';
        return $row;
    }

    /**
     * 新增还款
     * @param array $request
     * @return array
     */
    public function repay($request = array()){
        if(session('role_id') == 4){
            return array('msg'=>'信贷不能进行此操作','status'=>-1,'url'=>cookie('__forward__'));
        }
        $validate = Loader::validate('Repay');
        $data = $request;
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            return array('msg'=>$error_msg[0],'status'=>-1,'url'=>cookie('__forward__'));
        }
        $user = Db::name('users')->where('user_id',$request['user_id'])->field('cid,user_id,name,nickname,mobile,xd_id')->find();
        //获取信贷数据
        $xd =  Db::name('admin')->where('admin_id',$user['xd_id'])->field('admin_id,cid,mobile')->find();
        //判断还款金额是否超额
        $actual_amount = Db::name('repay')->where('lr_id',$request['lr_id'])->sum('actual_amount');
        if($request['actual_amount'] > ($request['repay_amount'] - $actual_amount)){
            return array('msg'=>'还款金额超过贷款金额，请确认后再操作','status'=>-1,'url'=>cookie('__forward__'));
        }
        $data['repay_date'] = strtotime($request['repay_date']);
        $data['actual_date'] = strtotime($request['actual_date']);
        $data['is_repay'] = 1;
        $data['bank_number'] = Db::name('Config')->where('name','bank_number')->value('value');
        $data['addtime'] = time();
        $error = 0;
        $expire = 0;
        //启动事务
        $this->model->startTrans();
        try{
            $this->model->data($data);
            $result = $this->model->save();
            if(!$result){
                $ss = 1;
                $error = 1;
            }
            $credit_rest = Db::name('user_credit')->where('user_id',$request['user_id'])->value('credit_rest');
            //实际还款金额
            $actual_amount = $request['actual_amount'];
            //还款成功后减少已使用授信额度、增加剩余授信额度
            $sql = "UPDATE __PREFIX__user_credit SET credit_used=credit_used-$actual_amount,
                      credit_rest=credit_rest+$actual_amount WHERE user_id={$request['user_id']}";
            $query = DB::execute($sql);
            if(!$query){
                $ss = 2;
                $error = 1;
            }

            $repay = Db::name('loan')->where('id',$request['loan_id'])->setInc('repay',$actual_amount);
            if(!$repay){
                $ss = 22;
                $error = 1;
            }

            //自增放款编号
            $config = Db::name('Config')->where('name','bank_number')->setInc('value');
            if(!$config){
                $ss = 3;
                $error = 1;
            }

            //增加个人授信日志
            $uc_log = new LoanLogic();
            $ucl = $uc_log->userCreditlog(1,$request['user_id'],'还款操作','可用额度增加'.$actual_amount.'原剩余可使用额度为'.$credit_rest);
            if(!$ucl){
                $ss = 4;
                $error = 1;
            }

            //增加贷款日志
            $data_ = [
                'admin_id'  => session('admin_id'),
                'value'     => $request['lr_id'],
                'action_url'=> 'Admin/LoanReceipt/repay',
                'action_name'=>'新增还款',
                'action_desc'=>'客户'.$user['user_name'].'(ID为:'.$user['user_id'].')还款'.$this->numberFormat($request['actual_amount']),
                'add_time'  => time()
            ];

            $loan_log = Db::name('loan_log')->insert($data_);
            if(!$loan_log){
                $ss = 5;
                $error = 1;
            }
            $loan_ = Db::name('loan')->where('id',$request['loan_id'])->field('apply_amount,repay,loan_number,str_time,stp_time')->find();
            //发送站内信
            $serializes = [
                'user_id'          => $user['user_id'],
                'user_name'        => $user['user_name'],
                'repay_amount'     => $request['actual_amount'],
                'rem_payment'      => $this->numberFormat($loan_['apply_amount'] - $loan_['repay']),
                'loan_number'      => $loan_['loan_number'],
                'str_time'         => date('Y-m-d',$loan_['str_time']),
                'stp_time'         => date('Y-m-d',$loan_['stp_time']),
            ];
            $datas = [
                'user_id'   => $user['user_id'],
                'title'     => '还款成功',
                'type'      => 6,
                'content'   => serialize($serializes),
                'add_time'  => time()
            ];
            $messages = Db::name('client_message')->insert($datas);
            if(!$messages){
                $error = 1;
            }
            //统计往期还款总金额
            $loan_count = Db::name('repay')->where('lr_id',$request['lr_id'])->sum('actual_amount');
            $loan = Db::name('loan')->where('id',$request['loan_id'])->field('str_time,stp_time,apply_amount')->find();
            if($loan_count >= $loan['apply_amount']){
                //逾期还款
                if(strtotime(date('Ymd',time())) > $loan['stp_time']){
                    $serialize = [
                        'apply_amount'  => $request['actual_amount'],
                        'user_id'       => $request['user_id'],
                        'user_name'     => $user['name'],
                        'str_time'      => date('Y-m-d',$loan['str_time']),
                        'stp_time'      => date('Y-m-d',$loan['stp_time']),
                        'remarks'       => ''
                    ];
                    $data__ = [
                        [
                            'admin_id'  => $xd['xd_id'],
                            'title'     => '还款逾期',
                            'type'      => 1,
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
                                'title'     => '还款逾期',
                                'type'      => 1,
                                'content'   => serialize($serialize),
                                'add_time'  => time()
                            ];
                        }
                    }
                    $check_data['is_check'] = 9;
                    $loan_data['is_repay'] = 4;
                    $expire = 1;
                }else{
                    //贷款结清发送站内信
                    $serialize = [
                        'loan_id'       => $request['loan_id'],
                        'apply_amount'  => $request['actual_amount'],
                        'user_id'       => $request['user_id'],
                        'user_name'     => Db::name('users')->where('user_id',$request['user_id'])->value('name'),
                        'str_time'      => date('Y-m-d',$loan['str_time']),
                        'stp_time'      => date('Y-m-d',$loan['stp_time']),
                        'remarks'       => ''
                    ];
                    $data__ = [
                        [
                            'admin_id'  => $xd['admin_id'],
                            'title'     => '提款已结清',
                            'type'      => 6,
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
                                'title'     => '提款已结清',
                                'type'      => 1,
                                'content'   => serialize($serialize),
                                'add_time'  => time()
                            ];
                        }
                    }
                    $check_data['is_check'] = 2;
                    $loan_data['is_repay'] = 1;
                }
                $message = Db::name('message')->insertAll($data__);
                if(!$message){
                    $ss = 6;
                    $error = 1;
                }

                //更新状态为已还款
                $loan_data['ok_time'] = time();
                $loan = Db::name('LoanReceipt')->where('id',$request['lr_id'])->update($loan_data);
                if(!$loan){
                    $ss = 7;
                    $error = 1;
                }

                //更新贷款申请表为已还完
                $check_data['ok_time'] = time();
                $check = Db::name('loan')->where('id',$request['loan_id'])->update($check_data);
                if(!$check){
                    $ss = 8;
                    $error = 1;
                }
            }

            if($error == 0){
                $this->model->commit();
                if($loan_count >= $loan['apply_amount']){
                    $admin_ = Db::name('admin')->where('admin_id',session('admin_id'))->field('cid,mobile')->find();
                    if($expire == 1){
                        $day = (strtotime(date('Ymd',time())) - $loan['stp_time']) / 864000;
                        if($day < 0){
                            $day_ = 1;
                        }else{
                            $day_ = (int)$day;
                        }
                        $body = '尊敬的'.$user['nickname'].'，您的红木贷，存在本（息）欠款'.$loan['apply_amount'].'元'.$day_.'天并已产生滞纳金，为避免影响您的信用记录，请即刻归还欠款。如已还款，请忽略。感谢理解和支持！';
                        $body_ = '您的客户'.$user['nickname'].'红木贷已发生逾期'.$loan['apply_amount'].'元，请您及时跟进还款，尽快解决逾期。';
                        $tit = '还款逾期';
                    }else{
                        $body = '尊敬的'.$user['nickname'].'，您的红木贷本期还款已完成。感谢您的支持';
                        $body_ = '您的客户'.$user['nickname'].'红木贷本期还款已完成。感谢您的支持';
                        $tit = '还款成功';
                    }
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
                    }
                    if(!empty($admin_['cid'])){
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
                    }
                    if(!empty($xd['cid'])){
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
                    if(!empty($user['mobile'])){
                        //发送短信给客户
                        send_sms($user['mobile'],$body);
                    }
                    //发送短信给自己
                    if(!empty($admin_['mobile'])){
                        send_sms($admin_['mobile'],$body_);
                    }
                    //发送短信给信贷
                    if(!empty($xd['mobile'])){
                        send_sms($xd['mobile'],$body_);
                    }
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

    function numberFormat($price){
        return number_format($price, 2, '.', '');
    }
}