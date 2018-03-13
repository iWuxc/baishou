<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 11:09
 */

namespace app\admin\logic;
use app\admin\model\Loan;
use think\Db;
use think\Model;

class LoanLogic extends LoanBaseLogic {

    private $model;

    function __construct($data = []){
        parent::__construct($data);
        $this->model = new Loan();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array(),$status){
        //贷款编号
        if(!empty($request['loan_number']) && is_numeric($request['loan_number'])){
            $param['where']['loan_number'] = $request['loan_number'];
        }
        if(!empty($request['user_name'])){
            $param['where']['user_name'] = $request['user_name'];
        }
        if(isset($request['check'])){
            $param['where']['is_check'] = $request['check'];
        }

        //距离还款日期还剩3天的
        if(!empty($request['expire'])){

        }
        //时间段查询
        if(!empty($request['add_time_begin']) && !empty($request['add_time_end'])){
            $param['where']['addtime'] = ['between time',[$request['add_time_begin'],$request['add_time_end']]];
        }
        $param['where']['status'] = $status;
        $param['where']['is_del'] = 1;
        $param['parameter'] = $request;
        $param['order'] = 'status asc,addtime desc,id desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
                $list['list'][$key]['str_time'] = date('Y-m-d',$val['str_time']);
                $list['list'][$key]['stp_time'] = date('Y-m-d',$val['stp_time']);
                $row = Db::name('user_credit')->where('user_id',$val['user_id'])->field('credit,credit_used,grand_total')->find();
                //获取授信额度
                $list['list'][$key]['credit_total'] = $row['credit'];
                //已使用授信额度
                $list['list'][$key]['credit_used'] = $row['credit_used'];
                //累计贷款金额
                $list['list'][$key]['grand_total'] = $row['grand_total'];
                //状态
                $list['list'][$key]['status_name'] = $this->getStatusName($val['is_check']);
                //银行状态
                $bank = Db::name('loan_receipt')->where('loan_id',$val['id'])->field('types,yunxin_status')->find();
                if(!empty($bank)){
                    $list['list'][$key]['types'] = $bank['types'];
                    //放款平台
                    if($bank['types'] == 1){
                        $list['list'][$key]['types_name'] = '平台默认';
                        $list['list'][$key]['yunyin_status'] = '--';
                    }elseif($bank['types'] == 2){
                        $list['list'][$key]['types_name'] = '云南信托';
                        $list['list'][$key]['yunyin_status'] = $this->getYxStatusName($bank['yunxin_status']);
                    }

                }else{
                    $list['list'][$key]['types'] = 1;
                    $list['list'][$key]['types_name'] = '平台默认';
                    $list['list'][$key]['yunyin_status'] = '--';
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
        $row['addtime'] = date('Y-m-d H:i:s',$row['addtime']);
        $row['str_time'] = date('Y-m-d',$row['str_time']);
        $row['stp_time'] = date('Y-m-d',$row['stp_time']);
        $info = Db::name('user_credit')->where('user_id',$row['user_id'])->field('credit,credit_used,grand_total')->find();
        //获取授信额度
        $row['credit_total'] = $info['credit'];
        //已使用授信额度
        $row['credit_used'] = $info['credit_used'];
        //累计贷款金额
        $row['grand_total'] = $info['grand_total'];
        return $row;
    }

    /**
     * 否决贷款操作 （半年内不可再次提交）
     * @param array $request
     * @return array
     */
    public function cancel($request = array(),$status){
        $error = 0;
        //启动事务
        $this->model->startTrans();
        try{
            $loan =$this->model->where('id',$request['id'])->update(['status'=>$status]);
            if(!$loan){
                $error = 1;
            }

            $apply_amount = $this->model->where('id',$request['id'])->value('apply_amount');
            //减少已使用授信额度、增加剩余授信额度、减少累积历史总贷款金额
            $sql = "UPDATE __PREFIX__user_credit SET credit_used=credit_used-$apply_amount,
                      credit_rest=credit_rest+$apply_amount,grand_total=grand_total-$apply_amount WHERE user_id={$request['user_id']}";
            $query = DB::execute($sql);
            if(!$query){
                $error = 1;
            }

            if($error == 0){
                $this->model->commit();
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

    /*
     * 个人信用操作日志
     * logtype:操作人类型:1后台人员ID,2前台用户ID
     * user_id:用户ID
     * action:动作(方法)
     * note:操作详情
     */
    public function userCreditlog($logtype,$user_id,$action,$note=''){
          $users = M('user_credit')->where(array('user_id'=>$user_id))->order('user_id desc')->find();
          $data['user_id'] = $user_id;             
          $data['act_desc'] = $action;             
          $data['log_note'] = $note;               
          $data['log_time'] = time();
          if($logtype ==1){
            $data['log_type'] = 1;
            $data['log_user'] = session('admin_id'); 
          }
          if($logtype == 2){
            $data['log_type'] = 2;
            $data['log_user'] = $users['user_id']; 
          }
          $log = M('user_credit_log');
          return $log->add($data);
    }

}