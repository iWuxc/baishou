<?php 
namespace app\api\controller;    
use think\Db; 
use think\Controller;  
class Loan{ 
  // 定时任务 每天10:30 请求一次 查询还款还款时间  警告管理员
  // 30 10 * * * /usr/bin/curl http://bs.bestshowgroup.com/api/loan/tt_loan
    public function tt_loan(){
      $stime=microtime(true); //获取程序执行开始的时间
      $time = time();
      $filter = 'apply_amount > repay';
      $list = DB::name('loan')->where($filter)->select();
      // print_r($list);die;
      $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = 0; 
      $day = strtotime(date("Y-m-d"));//获取凌晨24：00时间戳 
      //你的客户XXX应于X月X日还款XXXX元，请于到期前15天、到期前1周、及到期前一天分4次及时提醒客户存入足额资金，避免逾期。
      //28 21 14 7 3 2 1 0 -
      //start foreach
      foreach ($list as $k => $v) {
         $num = ($v['stp_time'] - $day)/86400;
          if(in_array($num, [28,21,14,7,3,2,1,0])){
            $admin_dx_con = '客户待还款提醒：你的客户'.$v['user_name'].'应于'.date('Y年m月d日', $v['stp_time']).'需还款'.($v['apply_amount']-$v['repay']).'元，请提前提醒客户存入足额资金，避免逾期。贷款编号：'.$v['loan_number'];
            $admin_gt_tmc = json_encode(array('title'=>'客户待还款提醒','body'=>$admin_dx_con), JSON_UNESCAPED_UNICODE);
            $xd_gt_param = array(
                  'title' => '客户待还款提醒',
                  'body' => $admin_dx_con,
                  'transmissionContent' => $admin_gt_tmc,
                  'type'=>'boss' 
              );  
            $fk_gt_param = array(
                  'title' => '客户待还款提醒',
                  'body' => $admin_dx_con,
                  'transmissionContent' => $admin_gt_tmc,
                  'type'=>'boss' 
              );  

            $user_dx_con = '尊敬的'.$v['user_name'].'：您的红木通还有'.($v['apply_amount']-$v['repay']).'元需在'.date('Y年m月d日', $v['stp_time']).'还款，请您提前1天存入足额资金，以免逾期。贷款编号：'.$v['loan_number'];
            $user_gt_tmc = json_encode(array('title'=>'待还款提醒','body'=>$user_dx_con), JSON_UNESCAPED_UNICODE);
            $user_gt_param = array( 
                  'title' => '待还款提醒',
                  'body' => $user_dx_con,
                  'transmissionContent' => $user_gt_tmc,
                  'type'=>'client' 
              );  

            //msssage 添加记录 admin
            $admin_msg_content = array(
                'apply_amount'    => $v['apply_amount'],
                'user_name'       => $v['user_name'],
                 'user_id'        => $v['user_id'],
                'str_time'        => date('Y-m-d', $v['str_time']),
                'stp_time'        => date('Y-m-d', $v['stp_time']),
                'remarks'         => $v['remarks']
              ); 
             $admin_msg_type  = 2;
             $admin_msg_title = '客户待还款提醒';
            
            //msssage 添加记录 user
            $user_msg_content = array(
                      'loan_number'   => $v['loan_number'],
                      'user_name'     => $v['user_name'],
                      'user_id'       => $v['user_id'],
                      'apply_amount'  => $v['apply_amount'],
                      'repay'         => $v['repay'],
                      'rest_money'    => $v['apply_amount']-$v['repay'],
                      'str_time'      => date('Y-m-d', $v['str_time']),
                      'stp_time'      => date('Y-m-d', $v['stp_time']),
                      'over_day'      => abs($num), 
            );  
            $user_msg = array(
                      'type'          => 2,
                      'user_id'       => $v['user_id'],
                      'title'         => '待还款提醒',
                      'add_time'      => $time,
                      'content'       => serialize($user_msg_content)
            );
            // print_r($admin_msg_content);die;
          }elseif($num < 0){ 
            $admin_dx_con = '客户逾期还款提醒：您的客户'.$v['user_name'].'红木通还有'.($v['apply_amount']-$v['repay']).'元未还完，已发生逾期'.abs($num).'天，请您及时跟进还款，尽快解决逾期。贷款编号：'.$v['loan_number'];
            $admin_gt_tmc = json_encode(array('title'=>'客户逾期还款提醒','body'=>$admin_dx_con), JSON_UNESCAPED_UNICODE);
            $xd_gt_param = array( 
                  'title'                 => '客户逾期还款提醒',
                  'body'                  => $admin_dx_con,
                  'transmissionContent'   => $admin_gt_tmc,
                  'type'=>'boss' 
              );
            $fk_gt_param = array( 
                  'title'                 => '客户逾期还款提醒',
                  'body'                  => $admin_dx_con,
                  'transmissionContent'   => $admin_gt_tmc,
                  'type'=>'boss' 
              );
            $user_dx_con = '尊敬的'.$v['user_name'].'：您的红木通还有'.($v['apply_amount']-$v['repay']).'元未还完，已发生逾期'.abs($num).'天，为避免影响您的信用记录，请即刻归还欠款。贷款编号：'.$v['loan_number'];
            $user_gt_tmc = json_encode(array('title'=>'待还款提醒','body'=>$user_dx_con), JSON_UNESCAPED_UNICODE);
            $user_gt_param = array( 
                  'title'               => '逾期还款提醒',
                  'body'                => $user_dx_con,
                  'transmissionContent' => $user_gt_tmc,
                  'type'                =>'client' 
              );

            //msssage 添加记录 admin
            $admin_msg_content = array(
                      'loan_number'   => $v['loan_number'],
                      'user_name'     => $v['user_name'],
                      'user_id'       => $v['user_id'],
                      'apply_amount'  => $v['apply_amount'],
                      'repay'         => $v['repay'],
                      'rest_money'    => $v['apply_amount']-$v['repay'],
                      'str_time'      => date('Y-m-d', $v['str_time']),
                      'stp_time'      => date('Y-m-d', $v['stp_time']),
                      'over_day'      => abs($num), 
            );
            $admin_msg_type  = 1;
            $admin_msg_title = '客户逾期还款提醒';
            
            //msssage 添加记录 user
            $user_msg_content = array(
                        'loan_number'   => $v['loan_number'],
                        'user_name'     => $v['user_name'],
                        'user_id'       => $v['user_id'],
                        'apply_amount'  => $v['apply_amount'],
                        'repay'         => $v['repay'],
                        'rest_money'    => $v['apply_amount']-$v['repay'],
                        'str_time'      => date('Y-m-d', $v['str_time']),
                        'stp_time'      => date('Y-m-d', $v['stp_time']),
                        'over_day'      => abs($num), 
            );
            $user_msg = array(              
                        'type'          => 1,
                        'user_id'       => $v['user_id'],
                        'title'         => '逾期还款提醒',
                        'add_time'      => $time,
                        'content'       => serialize($user_msg_content),
            );
            // print_r($admin_msg_content);die;
          }
          else{
            continue;
          }  
          $admin_id = DB::name('users')->where('user_id = '.$v['user_id'])->value('xd_id');
          $admin_info = DB::name('admin')->field('admin_id, cid, mobile')->where('admin_id = '.$admin_id)->find();
          
          $user_info = DB::name('users')->field('user_id, cid, mobile')->where('user_id = '.$v['user_id'])->find();

          //msssage 添加记录
          // $user_msg['user_id']    = $user_info['user_id'];
          $admin_msg['admin_id']  = $admin_info['admin_id'];
          // print_r($user_msg);die;
           //（信贷经理) 
           $admin_msg = array(
                        'admin_id'    => $admin_info['admin_id'],
                        'type'        => $admin_msg_type,
                        'title'       => $admin_msg_title,
                        'add_time'    => $time, 
                        'content'     => serialize($admin_msg_content),
            );
          DB::name('message')->add($admin_msg);  
          //（客户自己）暂时没设计页面
          // DB::name('client_message')->add($user_msg);  
           //（所有风控) 
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
              foreach ($fk_list as $key => $value) { 
                  $fk_admin_msg[] = array(
                          'admin_id'    => $value['admin_id'], 
                          'type'        => $admin_msg_type,
                          'title'       => $admin_msg_title,
                          'add_time'    => $time, 
                          'content'     => serialize($admin_msg_content),
                );
              }
            }
          //admin_dx_con
          // $admin_dx_res = send_sms($admin_info['mobile'], $admin_dx_con);
          // $user_dx_res = send_sms($user_info['mobile'], $user_dx_con); 
          //发送失败
          if($admin_dx_res){
            $dx_suc_count += 1; 
          }else{
            $dx_err_count += 1; 
            $log['dx_err_list'][$k]['admin']['load_id'] = $v['id'];
            $log['dx_err_list'][$k]['admin']['mobile'] = $admin_info['mobile'];
            $log['dx_err_list'][$k]['admin']['content'] = $admin_dx_con; 
          }
          if($user_dx_res){
            $dx_suc_count += 1; 
          }else{
            $dx_err_count += 1; 
            $log['dx_err_list'][$k]['user']['load_id'] = $v['id'];
            $log['dx_err_list'][$k]['user']['mobile'] = $user_info['mobile'];
            $log['dx_err_list'][$k]['user']['content'] = $user_dx_con; 
          }
            // $p_res = pushMessageToSingle($param); 
          //推送
          $xd_gt_param['cid'] = $admin_info['cid'];
          $fk_gt_param['cids'] = Db::name('admin')->where('role_id = 2')->getField('cid', true);;
          $xd_gt_res = pushMessageToSingle($xd_gt_param); 
          $fk_gt_res = pushMessageToList($fk_gt_param);
          $user_gt_param['cid'] = $user_info['cid'];
          $user_gt_res = pushMessageToSingle($user_gt_param);  
          // $user_gt_res = json_decode($user_gt_res);
          // var_dump($user_gt_res);die;
          //推送失败 
          if('ok' == $user_gt_res['result']){
            $gt_suc_count += 1;  
          }else{
            $gt_err_count += 1; 
            $log['gt_err_list']['user'][$k] = $user_gt_param;
          } 
          if('ok' == $xd_gt_res['result']){
            $gt_suc_count += 1;  
          }else{
            $gt_err_count += 1; 
            $log['gt_err_list']['xd'][$k] = $xd_gt_param;
          } 
          if('ok' == $fk_gt_res['result']){
            $gt_suc_count += 1;  
          }else{
            $gt_err_count += 1; 
            $log['gt_err_list']['fk'][$k] = $fk_gt_param;
          } 
      }
      //end foreach
      if(!empty($fk_message)){
        DB::name('message')->insertALL($fk_admin_msg);
        // echo DB::name('message')->getlastsql();die; 
      }
          $log['dx_suc_count'] = $dx_suc_count;
          $log['dx_err_count'] = $dx_err_count;
          $log['gt_suc_count'] = $gt_suc_count;
          $log['gt_err_count'] = $gt_err_count; 
          // print_r($log);  
          $add['value'] = json_encode($log, JSON_UNESCAPED_UNICODE);

          $add['time'] = date('Y-m-d H:i:s', $time); 
          $etime=microtime(true);//获取程序执行结束的时间 
          $add['runtime'] = $etime-$stime;  //计算差值
 
          $add_id = DB::name('loan_timedtask_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){
            $error ='api/loan/tt_loan____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time); 
            $send_coder = send_sms('17611495523', $error); 
            if(false === $send_coder){
              send_sms('15652604229', $error); 
            }
          } 
        // print_r($add);
    }    
}