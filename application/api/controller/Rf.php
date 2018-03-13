<?php
namespace app\api\controller;    
use think\Controller; 
use think\Db;
class Rf extends controller{   
    //REID 客户店铺端 读写设备 发送过来的信号 更新时间
    //测试数据
    // $_REQUEST['token'] = 123; 
    // $_REQUEST['rf_ids'] = '000000060101,000000040101,000000080101'; 
    public function check(){
      $stime=microtime(true); //获取程序执行开始的时
      $time = time();
      // if('POST' !== $_SERVER['REQUEST_METHOD']){
      //     $data['result'] = 0;
      //     $data['err_code'] = 100;
      //     $data['runtime'] = microtime(true)-$stime;
      //     $data['time'] = $time;
      //     $data['msg'] = '错误的请求方式';
      //     exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      // }
      //参数
      // print_r($_REQUEST);die;
      if(!isset($_REQUEST['rf_ids'])){
          $log['time'] = date('Y-m-d H:i:s', $time); 
          $log['runtime'] = microtime(true)-$stime;
          $log['ip'] = $_SERVER['REMOTE_ADDR']; 
          $log['result'] = 0; 
          $log['err_code'] = 101; 
          $log['value'] = $_REQUEST['rf_ids']; 
          DB::name('rfid_log')->add($log);

          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['runtime'] = microtime(true)-$stime;
          $data['time'] = $time;
          $data['msg'] = '无参数';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }

      $rf_ids = trim($_REQUEST['rf_ids'], ',');
      $rf_ids = str_replace(' ', '', $rf_ids);
      $rf_ids = str_replace('，', ',', $rf_ids);
      $rf_ids = explode(',', $rf_ids);
      //参数格式
      foreach ($rf_ids as $k => $v) { 
        $tearup = mb_substr($v, 8, 2, 'utf-8');
        $energy = mb_substr($v, 10, 2, 'utf-8');
        if(('00' != $tearup && '01' != $tearup) || ('00' != $energy && '01' != $energy)){

            $log['time'] = date('Y-m-d H:i:s', $time); 
            $log['runtime'] = microtime(true)-$stime;
            $log['ip'] = $_SERVER['REMOTE_ADDR']; 
            $log['result'] = 0; 
            $log['err_code'] = 102; 
            $log['value'] = $_REQUEST['rf_ids']; 
            DB::name('rfid_log')->add($log);

            $data['result'] = 0;
            $data['err_code'] = 102;
            $data['runtime'] = microtime(true)-$stime;
            $data['time'] = $time;
            $data['msg'] = '参数格式错误';
            exit(json_encode($data, JSON_UNESCAPED_UNICODE));
        }
      }
      
      $rfids = array();
      $dx_err_count = $gt_err_count = 0;
      $tearup_ids = $dx_err_ids = $gt_err_ids = $energy_ids = $full_ids = '';
      //所有故障列表 判断故障 是否 依然存在  被修复修改状态
      $em_list = DB::name('equipment_wrong')->where(array('type' => 2, 'type2' => 22, 'status' => 1))->getField('em_id', true);
      // print_r($rf_ids);die;
      // print_r($em_list);die;
      foreach ($rf_ids as $k => $v) {
        $rf = mb_substr($v, 0, 8, 'utf-8');
        $tearup = mb_substr($v, 8, 2, 'utf-8');
        $energy = mb_substr($v, 10, 2, 'utf-8');
        $rfids[$k] = $rf;
        if('00' === $energy){
          $energy_ids .= "'$rf',";
        }else{
          $full_ids .= "'$rf',";
        }
        if('01' === $tearup){
          $tearup_ids .= "'$rf',";
          $pawn_info = DB::name('pawn')
                      ->alias('p')->join('bs_pawn_one o','p.pawn_id = o.pawn_id','right')
                      ->field('p.pawn_id,p.pawn_name,p.user_id,p.user_name,p.store_id,p.store_name,p.xd_id,o.one_id,o.pawn_name as one_name,o.pawn_rfid,o.rfid_push')
                      ->where("pawn_rfid = '$rf'")
                      ->find(); 
                      
            $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$pawn_info['xd_id'])->cache('admin_info')->find();
            $content = '您的客户'.$pawn_info['user_name'].'-'.$pawn_info['store_name'].'店铺内家具'.$pawn_info['pawn_name'].$pawn_info['one_name'].'RFID标签编号为'.$pawn_info['pawn_rfid'].'被撕毁,请您于尽快处理'.date('Y-m-d-H:i:s', $time);
            $title = $pawn_info['user_name'].$pawn_info['store_name'].$pawn_info['pawn_name'].$pawn_info['pawn_rfid'].'被撕毁';
            $body = $content;
            $tmc = json_encode(array('title'=>'REID被撕毁','body'=>$content), JSON_UNESCAPED_UNICODE);
            // echo $admin_info['mobile'].' ' .$content;
            if(1 == $pawn_info['rfid_push']){
              //提醒  发短信
              if('y' === SEND_STATUS){
                $s_res = send_sms($admin_info['mobile'], $content);
              }else{
                $s_res = true;
              }
            }
            // var_dump($s_res);die;

            //发送失败
            if(!$s_res){
              $dx_err_count += 1; 
              $dx_err_ids .= $pawn_info['one_id'].'_'; 
            } 
            //推送 信贷经理
            $param = array(
                    'cid' => $admin_info['cid'],
                    'title' => 'REID被撕毁',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $pawn_info['rfid_push']){
              //提醒  推送
              if('y' === SEND_STATUS){
                $p_res = pushMessageToSingle($param);  
              }else{
                $p_res['result'] = 'ok';
              }
            }
            
            //推送失败 信贷
            if('ok' == $p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $gt_err_ids .= $pawn_info['one_id'].'_'; 
            }

            //推送 所有风控
            $fk_cids = Db::name('admin')->where('role_id = 2')->getField('cid', true);
            $fk_param = array(
                    'cids' => $fk_cids,
                    'title' => 'REID被撕毁',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              );
            if(1 == $pawn_info['rfid_push']){
              //提醒  推送
              if('y' === SEND_STATUS){
                $fk_p_res = pushMessageToList($fk_param);  
              }else{
                $fk_p_res['result'] = 'ok';
              }
            }
            
            //推送失败 所有风控
            if('ok' == $fk_p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $gt_err_ids .= $pawn_info['one_id'].'_'; 
            }

            //msssage 待办事项 添加记录 (信贷)
            $msg_content = array(
                'type'        => 2,
                'value'       => $pawn_info['pawn_rfid'],
                'user_name'   => $pawn_info['user_name'],
                'store_id'    => $pawn_info['store_id'],
                'store_name'  => $pawn_info['store_name'],
                'pawn_name'   => date('Y-m-d H:i:s',$time),
                'pawn_id'     => $pawn_info['pawn_id'],
                'one_id'      => $pawn_info['one_id'],
                'remarks'     => $content,
              );
            $message = array(
                'admin_id'    => $admin_info['admin_id'],
                'type'        => 7, 
                'title'       => 'RFID故障(被撕毁）',
                'add_time'    => $time,
                'content'     => serialize($msg_content),
              ); 
            DB::name('message')->add($message);  
            // echo $pawn_info['one_id'];
            // print_r($em_list);die;
            //（风控) 
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
              foreach ($fk_list as $key => $value) {
                $fk_message[] = array(
                    'admin_id'    => $value['admin_id'],
                    'type'        => 7, 
                    'title'       => 'RFID故障(被撕毁）',
                    'add_time'    => $time,
                    'content'     => serialize($msg_content),
                  ); 
              }
            } 
            
            // echo DB::name('message')->getlastsql();die;  
            // echo $pawn_info['one_id'];
            // print_r($em_list);die;
            //bs_equipment_wrong 设备故障 添加记录
            if(!in_array($pawn_info['one_id'], $em_list)){
              DB::name('equipment_wrong')->where(array('type' => 2, 'type2' => 22, 'em_id' => $pawn_info['pawn_id']))->update(array('normal_time'=>$time, 'status'=>2));
                $equipment_wrong = array(
                      'admin_id'    => $admin_info['admin_id'],
                      'value'       => $pawn_info['pawn_rfid'],
                      'em_id'       => $pawn_info['one_id'],
                      'em_name'     => $pawn_info['pawn_name'].'（'.$pawn_info['one_name'].'）',
                      'content'     => 'REID被撕毁',
                      'type'        => 2, 
                      'type2'       => 22, 
                      'value'       => $pawn_info['pawn_rfid'],
                      'user_id'     => $pawn_info['user_id'],
                      'user_name'   => $pawn_info['user_name'],
                      'store_id'    => $pawn_info['store_id'],
                      'store_name'  => $pawn_info['store_name'], 
                      'time'        => $time, 
                    );
                DB::name('equipment_wrong')->add($equipment_wrong); 
            } 
        }
      }
      // print_r($fk_message);die;
      if(!empty($fk_message)){
        DB::name('message')->insertALL($fk_message);
        // echo DB::name('message')->getlastsql();die; 
      }
      if($dx_err_count > 0 or $gt_err_count > 0){
        $error = 'api/rf/check_(rfid_被撕毁提醒)dx_err_ids:'.$dx_err_ids.';gt_err_ids:'.$gt_err_ids.'time:'.date("Y-m-d-H:i:s", $time);
        if(1 == $pawn_info['rfid_push']){
          if('y' === SEND_STATUS){
            if(false === send_sms('17611495523', $error)){
              send_sms('15652604229', $error); 
            }
          }
        }
      }
      //修改被撕毁状态
      if($tearup_ids){
        $tearup_ids = rtrim($tearup_ids,',');
        DB::name('pawn_one')->where("pawn_rfid in ($tearup_ids)")->setField('rfid_tearup', 1); 
      } 
      //修改低电量状态
      $energy_ids = rtrim($energy_ids, ',');
      if($energy_ids){
        DB::name('pawn_one')->where("pawn_rfid in ($energy_ids)")->setField('rfid_energy', '0');
      }
      //修改正常电量状态
      $full_ids = rtrim($full_ids, ',');

      if($full_ids){
        DB::name('pawn_one')->where("pawn_rfid in ($full_ids)")->setField('rfid_energy', 1);
        // echo DB::name('pawn_one')->getlastsql();die;
      }
      $rfids = implode("','", $rfids);
      if($rfids){
        $rfids = "'$rfids'";
        $result = DB::name('pawn_one')->where("pawn_rfid in ($rfids)")->setField('rf_time', $time);
        // echo DB::name('pawn')->getlastsql();die;
        // var_dump($result);die;
        if(0 !== $result || false !== $result){

            $log['time'] = date('Y-m-d H:i:s', $time); 
            $log['runtime'] = microtime(true)-$stime;
            $log['ip'] = $_SERVER['REMOTE_ADDR']; 
            $log['result'] = 1; 
            $log['err_code'] = 201; 
            $log['value'] = $_REQUEST['rf_ids']; 
            DB::name('rfid_log')->add($log);

          $data['result'] = 1;
          $data['err_code'] = 201;
          $data['runtime'] = microtime(true)-$stime;
          $data['time'] = $time;
          $data['msg'] = '请求成功';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
        }else{
          sleep(2);
          $result2 = DB::name('pawn_one')->where("pawn_rfid in ($rfids)")->setField('rf_time', $time);
          if(0 !== $result2 || false !== $result2){

            $log['time'] = date('Y-m-d H:i:s', $time); 
            $log['runtime'] = microtime(true)-$stime;
            $log['ip'] = $_SERVER['REMOTE_ADDR']; 
            $log['result'] = 1; 
            $log['err_code'] = 202; 
            $log['value'] = $_REQUEST['rf_ids']; 
            DB::name('rfid_log')->add($log);

            $data['result'] = 1;
            $data['err_code'] = 202;
            $data['runtime'] = microtime(true)-$stime;
            $data['time'] = $time;
            $data['msg'] = '请求成功';
            exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
          }else{

            $log['time'] = date('Y-m-d H:i:s', $time); 
            $log['runtime'] = microtime(true)-$stime;
            $log['ip'] = $_SERVER['REMOTE_ADDR']; 
            $log['result'] = 0; 
            $log['err_code'] = 500; 
            $log['value'] = $_REQUEST['rf_ids']; 
            DB::name('rfid_log')->add($log);

            $data['result'] = 0;
            $data['err_code'] = 500;
            $data['runtime'] = microtime(true)-$stime;
            $data['time'] = $time;
            $data['msg'] = '服务器繁忙';
            exit(json_encode($data, JSON_UNESCAPED_UNICODE));            
          } 
        } 
      } 
    } 
} 