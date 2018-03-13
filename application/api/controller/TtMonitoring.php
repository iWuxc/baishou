<?php  
namespace app\api\controller;    
use think\Controller; 
use think\Db;
class TtMonitoring extends controller{
    // 定时任务 5分钟请求一次 查找所有 已经修复的异常摄像头 报警提醒管理员
    // */5 * * * * /usr/bin/curl http://bs.bestshowgroup.com/api/TtMonitoring/index
    public function index(){
      $stime=microtime(true); //获取程序执行开始的时间
      $time = time(); 
      // 如果 长时间未继续收到监控故障报警 则修改状态 说明被修好了  
      $over_time = $time - 60*6; // 距离最后一次异常报警更新时间 6分钟 

      $mt_filter['bad_time'] = array('lt', $over_time);  
      $mt_filter['status'] = 1;  
      $store_ids = DB::name('user_store')->where(array('is_check'=>1))->getField('store_id', true); 
      $store_ids = implode(',', $store_ids); 
      $mt_filter['is_bad'] = 1;  
      $mt_filter['bad_type'] = array('in', '2');  
      $mt_filter['store_id'] = array('in', $store_ids);  
      $mt_result = DB::name('store_monitoring')->where($mt_filter)->update(array('is_bad'=>0,'bad_type'=>0,'bad_time'=>0));
       // echo DB::name('store_monitoring')->getlastsql();die; 

      $ew_filter['type'] = 1;  
      $ew_filter['type2'] = array('in', 2);  
      $ew_filter['time'] = array('lt', $over_time);   
      $ew_filter['status'] = 1;  
      $store_ids = DB::name('user_store')->where(array('is_check'=>1))->getField('store_id', true); 
      $store_ids = implode(',', $store_ids);  
      $ew_filter['store_id'] = array('in', $store_ids);   
// print_r($ew_result);die;
      $result = DB::name('equipment_wrong')->where($ew_filter)->select();  
// print_r($result);die;
      if($result){
        $ew_result = DB::name('equipment_wrong')->where($ew_filter)->update(array('normal_time'=>$time, 'status'=>2)); 
        // echo DB::name('equipment_wrong')->getlastsql();die;      
        $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = $recove_count = 0;
        $bad_desc = '故障已修复';
         foreach ($result as $k => $v) {
              $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['admin_id'])->cache('admin_info')->find();

              $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内'.$v['alise_name'].',品牌为'.$v['brand'].'监控摄像头最近一次监测故障时间为：'.$v['time'].',系统已自动更新状态为已修复，请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
              $title = $v['user_name'].$v['store_name'].$v['alise_name'].$bad_desc;
              $body = $content;
              $tmc = json_encode(array('title'=>'监控摄像头'.$bad_desc,'body'=>$content), JSON_UNESCAPED_UNICODE); 
              //提醒  发短信
              if('y' === SEND_STATUS){
                $s_res = send_sms($admin_info['mobile'], $content);
              }else{
                $s_res = true;
              }
              
              // var_dump($s_res);die; 
              //发送失败
              if($s_res){
                $dx_suc_count += 1; 
              }else{
                $dx_err_count += 1; 
                $log['dx_err_list'][$k]['mt_id'] = $v['id'];
                $log['dx_err_list'][$k]['mobile'] = $admin_info['mobile'];
                $log['dx_err_list'][$k]['content'] = $content; 
              }
              //推送
              $param = array(
                      'cid' => $admin_info['cid'],
                      'title' => '监控摄像头'.$bad_desc,
                      'body' => $body,
                      'transmissionContent' => $tmc,
                      'type'=>'boss' 
                ); 
              $p_res = pushMessageToSingle($param);  
              // print_r($p_res);die;
              // $p_res = json_decode($p_res, true); 
              //推送失败
              if('ok' == $p_res['result']){
                $gt_suc_count += 1;  
              }else{
                $gt_err_count += 1; 
                $log['gt_err_list'][$k]['mt_id'] = $v['id'];
                $log['gt_err_list'][$k]['cid'] = $admin_info['cid'];
                $log['gt_err_list'][$k]['title'] = $title;
                $log['gt_err_list'][$k]['body'] = $body;
                $log['gt_err_list'][$k]['transmissionContent'] = $tmc;
              }  
              //msssage 待办事项 添加记录
              $msg_content = array( 
                  'type'        => 7,
                  'value'       => $v['store_name'].$v['alise_name'],
                  'user_name'   => $v['user_name'],
                  'store_id'    => $v['store_id'],
                  'store_name'  => $v['store_name'],
                  'pawn_name'   => $time,
                  'remarks'     => $content,
                  'pawn_id'     => $v['pawn_id'],
                );
              $message = array(
                  'admin_id'    => $admin_info['admin_id'],
                  'type'        => 7, 
                  'title'       => '监控摄像头故障('.$bad_desc.')',
                  'add_time'    => $time,
                  'content'     => serialize($msg_content),
                );   
              DB::name('message')->add($message); 
         }
      }
 



          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['runtime'] = microtime(true)-$stime; 
          $add['mt_result'] = $mt_result;
          $add['ew_result'] = $ew_result;  
          $add['dx_err_count'] = $dx_err_count;
          $add['gt_err_count'] = $gt_err_count; 
          $add_id = DB::name('mt_timedtask_log')->add($add);
          // rfid_timedtask_log 添加记录
          if($dx_err_count > 0 or $gt_err_count > 0){ 
            $error = 'api/Mt/badWarning____id:'.$add_id.';dx_err_count:'.$dx_err_count.';gt_err_count:'.$gt_err_count.';time:'.date("Y-m-d-H:i:s", $time);
            if('y' === SEND_STATUS){
              if(false === send_sms('17611495523', $error)){
                send_sms('15652604229', $error); 
              }
            }
          } 

 
    }    
    // 定时任务 5分钟请求一次 查找所有异常摄像头 报警提醒管理员
    // */5 * * * * /usr/bin/curl http://bs.bestshowgroup.com/api/TtMonitoring/index
    public function index_back1(){
      $stime=microtime(true); //获取程序执行开始的时间
      $time = time(); 
      $over_time = $time - 60*6; // 距离最后一次异常报警更新时间 6分钟 

      $filter['status'] = 1;  
      $store_ids = DB::name('user_store')->where(array('is_check'=>1))->getField('store_id', true); 
      $store_ids = implode(',', $store_ids); 
      $filter['is_bad'] = 1;  
      $filter['bad_type'] = array('in', '2');  
      $filter['store_id'] = array('in', $store_ids);  
      $result = DB::name('store_monitoring')->where($filter)->select(); 
      // echo DB::name('store_monitoring')->getlastsql();
      // print_r($result);die;  
      //所有故障列表 判断故障 是否 依然存在  被修复修改状态
      $em_list = DB::name('equipment_wrong')->where(array('type' => 1, 'status' => 1))->getField('em_id', true); 
      $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = $recove_count = 0;
      $suc_ids = $err_ids = '';
      if($result){
        foreach ($result as $k => $v) {
          if($v['bad_time'] < $over_time){ 
            // 如果 长时间未继续收到监控故障报警 则修改状态 说明被修好了 
              DB::name('store_monitoring')->where("id = ".$v['id'])->update(array('is_bad'=>0,'bad_type'=>0,'bad_time'=>0));
              DB::name('equipment_wrong')->where(array('type' => 1, 'em_id' => $v['id'], 'status'=>1))->update(array('normal_time'=>$time, 'status'=>2)); 
              $recove_count += 1;
          }else{
            // 执行所有推送报警 
              switch ($v['bad_type'])
              {
                  case 2:
                      $bad_type = 2;
                      $bad_desc = '信号丢失';
                      break;
                  default:
                      $bad_type = 1000;
                      $bad_desc = '其它未知报警信息';
              } 
            $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['admin_id'])->cache('admin_info')->find();

            $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内'.$v['alise_name'].',品牌为'.$v['brand'].'监控摄像头'.$bad_desc.',请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
            $title = $v['user_name'].$v['store_name'].$v['alise_name'].'监控摄像头无网络连接';
            $body = $content;
            $tmc = json_encode(array('title'=>'监控摄像头'.$bad_desc,'body'=>$content), JSON_UNESCAPED_UNICODE); 
            //提醒  发短信
            if('y' === SEND_STATUS){
              $s_res = send_sms($admin_info['mobile'], $content);
            }else{
              $s_res = true;
            }
            
            // var_dump($s_res);die; 
            //发送失败
            if($s_res){
              $dx_suc_count += 1; 
            }else{
              $dx_err_count += 1; 
              $log['dx_err_list'][$k]['mt_id'] = $v['id'];
              $log['dx_err_list'][$k]['mobile'] = $admin_info['mobile'];
              $log['dx_err_list'][$k]['content'] = $content; 
            }
            //推送
            $param = array(
                    'cid' => $admin_info['cid'],
                    'title' => '监控摄像头'.$bad_desc,
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            $p_res = pushMessageToSingle($param);  
            // $p_res = json_decode($p_res, true); 
            //推送失败
            if('ok' == $p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $log['gt_err_list'][$k]['mt_id'] = $v['id'];
              $log['gt_err_list'][$k]['cid'] = $admin_info['cid'];
              $log['gt_err_list'][$k]['title'] = $title;
              $log['gt_err_list'][$k]['body'] = $body;
              $log['gt_err_list'][$k]['transmissionContent'] = $tmc;
            }  
            //msssage 待办事项 添加记录
            $msg_content = array( 
                'type'        => 1,
                'value'       => $v['store_name'].$v['alise_name'],
                'user_name'   => $v['user_name'],
                'store_id'    => $v['store_id'],
                'store_name'  => $v['store_name'],
                'pawn_name'   => $time,
                'remarks'     => $content,
                'pawn_id'     => $v['pawn_id'],
              );
            $message = array(
                'admin_id'    => $admin_info['admin_id'],
                'type'        => 7, 
                'title'       => '监控摄像头故障('.$bad_desc.')',
                'add_time'    => $time,
                'content'     => serialize($msg_content),
              );  
            DB::name('message')->add($message);  
            //bs_equipment_wrong 设备故障 添加记录
            if(!in_array($v['id'], $em_list)){

              $equipment_wrong = array(
                    'admin_id'    => $admin_info['admin_id'],
                    'em_id'       => $v['id'],
                    'em_name'     => $v['store_name'].$v['alise_name'],
                    'content'     => '监控摄像头('.$bad_desc.')',
                    'type'        => 1, 
                    'type2'       => $bad_type, 
                    'value'       => $v['alise_name'].'('.$v['brand'].')',
                    'user_id'     => $v['user_id'],
                    'user_name'   => $v['user_name'],
                    'store_id'    => $v['store_id'],
                    'store_name'  => $v['store_name'], 
                    'time'        => $time, 
                  );   
              DB::name('equipment_wrong')->add($equipment_wrong);
              // echo DB::name('equipment_wrong')->getlastsql();
              $equipment_wrong = array();
            }
          }
        }
          $log['dx_suc_count'] = $dx_suc_count;
          $log['dx_err_count'] = $dx_err_count;
          $log['gt_suc_count'] = $gt_suc_count;
          $log['gt_err_count'] = $gt_err_count; 
          // print_r($log);
          $add['value'] = json_encode($log, JSON_UNESCAPED_UNICODE);
      }
          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['runtime'] = microtime(true)-$stime;
          $add['result'] = count($result) - $recove_count;
          $add['dx_err_count'] = $log['dx_err_count'];
          $add['gt_err_count'] = $log['gt_err_count'];
          $add['recove_count'] = $recove_count;
          // print_r($add);
          // rfid_timedtask_log 添加记录
          $add_id = DB::name('mt_timedtask_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){ 
            $error = 'api/TtMonitoring/index____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if('y' === SEND_STATUS){
              if(false === send_sms('17611495523', $error)){
                send_sms('15652604229', $error); 
              }
            }
          }
    } 
    // 定时任务 20分钟请求一次 判断 监控摄像头是否在线 报警提醒管理员
    // */20 * * * * /usr/bin/curl http://bs.bestshowgroup.com/api/TtMonitoring/index
    public function index_back2(){
      $stime=microtime(true); //获取程序执行开始的时间
      $time = time(); 
      $filter['status'] = 1;  
      $store_ids = DB::name('user_store')->where(array('is_check'=>1))->getField('store_id', true); 
      $store_ids = implode(',', $store_ids);
      $filter['status'] = 1;  
      $filter['store_id'] = array('in', $store_ids);  
      $result = DB::name('store_monitoring')->where($filter)->select(); 
      // echo DB::name('store_monitoring')->getlastsql();die; 
      // print_r($result);die;  
      //所有故障列表 判断故障 是否 依然存在  被修复修改状态
      $em_list = DB::name('equipment_wrong')->where(array('type' => 1, 'status' => 1))->getField('em_id', true); 
      $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = 0;
      $suc_ids = $err_ids = '';
      if($result){
        foreach ($result as $k => $v) {
          if(false === pingAddress($v['dns'])){ 
            //拼接异常 ids
            $err_ids .= $v['id'].',';
            $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['admin_id'])->cache('admin_info')->find();

            $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内'.$v['alise_name'].',品牌为'.$v['brand'].'监控摄像头无网络连接,请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
            $title = $v['user_name'].$v['store_name'].$v['alise_name'].'监控摄像头无网络连接';
            $body = $content;
            $tmc = json_encode(array('title'=>'监控摄像头网络异常','body'=>$content), JSON_UNESCAPED_UNICODE); 
            //提醒  发短信
            if('y' === SEND_STATUS){
              $s_res = send_sms($admin_info['mobile'], $content);
            }else{
              $s_res = true;
            }
            
            // var_dump($s_res);die; 
            //发送失败
            if($s_res){
              $dx_suc_count += 1; 
            }else{
              $dx_err_count += 1; 
              $log['dx_err_list'][$k]['mt_id'] = $v['id'];
              $log['dx_err_list'][$k]['mobile'] = $admin_info['mobile'];
              $log['dx_err_list'][$k]['content'] = $content; 
            }
            //推送
            $param = array(
                    'cid' => $admin_info['cid'],
                    'title' => '监控摄像头网络异常',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            $p_res = pushMessageToSingle($param);  
            // $p_res = json_decode($p_res, true); 
            //推送失败
            if('ok' == $p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $log['gt_err_list'][$k]['mt_id'] = $v['id'];
              $log['gt_err_list'][$k]['cid'] = $admin_info['cid'];
              $log['gt_err_list'][$k]['title'] = $title;
              $log['gt_err_list'][$k]['body'] = $body;
              $log['gt_err_list'][$k]['transmissionContent'] = $tmc;
            }  
            //msssage 待办事项 添加记录
            $msg_content = array( 
                'type'        => 1,
                'value'       => $v['store_name'].$v['alise_name'],
                'user_name'   => $v['user_name'],
                'store_id'    => $v['store_id'],
                'store_name'  => $v['store_name'],
                'pawn_name'   => $time,
                'remarks'     => $content,
                'pawn_id'     => $v['pawn_id'],
              );
            $message = array(
                'admin_id'    => $admin_info['admin_id'],
                'type'        => 7, 
                'title'       => '设备故障',
                'add_time'    => $time,
                'content'     => serialize($msg_content),
              ); 
            DB::name('message')->add($message); 
            //bs_equipment_wrong 设备故障 添加记录
            if(!in_array($v['id'], $em_list)){

              $equipment_wrong = array(
                    'admin_id'    => $admin_info['admin_id'],
                    'em_id'       => $v['id'],
                    'em_name'     => $v['store_name'].$v['alise_name'],
                    'content'     => '监控摄像头网络异常',
                    'type'        => 1, 
                    'type2'       => 11, 
                    'value'       => $v['alise_name'].'('.$v['brand'].')',
                    'user_id'     => $v['user_id'],
                    'user_name'   => $v['user_name'],
                    'store_id'    => $v['store_id'],
                    'store_name'  => $v['store_name'], 
                    'time'        => $time, 
                  );   
              DB::name('equipment_wrong')->add($equipment_wrong);
              $equipment_wrong = array();
            }
          }else{
            //拼接异常 ids
            // $suc_ids .= $v['id'].',';
            // 如果是故障监控 则修改状态 说明被修好了
            if(in_array($v['id'], $em_list)){ 
              DB::name('store_monitoring')->where("id = ".$v['id'])->update(array('is_bad'=>0));
              DB::name('equipment_wrong')->where(array('type' => 1, 'em_id' => $v['id']))->update(array('normal_time'=>$time, 'status'=>2));
            }
          }
        }
          //修改所有异常 监控
          $err_ids = rtrim($err_ids, ',');
          DB::name('store_monitoring')->where(array('id' => array('in', $err_ids)))->update(array('is_bad'=>1));

          $log['dx_suc_count'] = $dx_suc_count;
          $log['dx_err_count'] = $dx_err_count;
          $log['gt_suc_count'] = $gt_suc_count;
          $log['gt_err_count'] = $gt_err_count; 
          // print_r($log);
          $add['value'] = json_encode($log, JSON_UNESCAPED_UNICODE);
      }
          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['runtime'] = microtime(true)-$stime;
          // print_r($add);
          //rfid_timedtask_log 添加记录
          $add_id = DB::name('mt_timedtask_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){ 
            $error = 'api/TtMonitoring/index____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if('y' === SEND_STATUS){
              if(false === send_sms('17611495523', $error)){
                send_sms('15652604229', $error); 
              }
            }
          }
    } 
} 