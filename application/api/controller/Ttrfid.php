<?php  
namespace app\api\controller;    
use think\Controller; 
use think\Db;
class Ttrfid extends controller{
    // 定时任务 10分钟请求一次 判断RFID 最后一次更新时间 
    // */10 * * * * /usr/bin/curl http://bs.bestshowgroup.com/api/ttrfid/rfid
    public function rfid(){
      $stime = microtime(true); //获取程序执行开始的时间
      $time = time();
      $over_time = $time - 60*10; // 未有收到信号时间 10分钟 
      $filter['p.status'] = array('in', '1,2,4'); 
      $filter['o.cruise_shop_mode'] = array('in', '2,3'); //是否自动
      $filter['o.rf_time'] = array('lt', $over_time);
      $result = DB::name('pawn')
        ->alias('p')->join('bs_pawn_one o','p.pawn_id = o.pawn_id','right')
        ->field('p.pawn_id,p.pawn_name,p.user_id,p.user_name,p.store_id,p.store_name,p.xd_id,o.one_id,o.pawn_name as one_name,o.pawn_rfid,o.rfid_push')
        ->where($filter)
        ->select(); 
      // echo DB::name('pawn')->getlastsql();
      // print_r($result);die; 
      //所有故障列表 判断故障 是否 依然存在  em 表只记录一条 
      $em_list = DB::name('equipment_wrong')->where(array('type' => 2, 'type2' => 21, 'status' => 1))->getField('em_id', true);
      // print_r($em_list);die;
      if($em_list && $result){
        $err_rfid = DB::name('pawn')->alias('p')->join('bs_pawn_one o','p.pawn_id = o.pawn_id','right')->where($filter)->getField('one_id', true);
        // print_r($err_rfid);die;
        $status = '';
        foreach ($em_list as $key => $value) {
          if(!in_array($value, $err_rfid)){ 
            $status .= "'$value',";
          }
        }
      } 
      if($status){
        $status = rtrim($status,',');
        DB::name('equipment_wrong')->where("type = 2 and type2 = 21 and status = 1 and em_id in($status)")->update(array('normal_time'=>$time, 'status'=>2));
        // echo DB::name('equipment_wrong')->getlastsql();die; 
      }
      if($result){
        $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = 0; 
        foreach ($result as $k => $v) {
            $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['xd_id'])->cache('admin_info')->find();
            $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内家具'.$v['pawn_name'].$v['one_name'].'RFID标签编号为'.$v['pawn_rfid'].'标签无信号,请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
            $title = '标签被撕毁'.$v['user_name'].$v['store_name'].$v['pawn_name'].$v['pawn_rfid'];
            $body = $content;
            $tmc = json_encode(array('title'=>'标签无信号','body'=>$content), JSON_UNESCAPED_UNICODE);
            // echo $admin_info['mobile'].' ' .$content;
            //提醒  发短信
            if(1 == $v['rfid_push']){
              //提醒  发短信
              if('y' === SEND_STATUS){
                $s_res = send_sms($admin_info['mobile'], $content);
              }else{
                $s_res = true;
              }
            }
            // var_dump($s_res);die;

            //发送失败
            if($s_res){
              $dx_suc_count += 1; 
            }else{
              $dx_err_count += 1; 
              $log['dx_err_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['dx_err_list'][$k]['mobile'] = $admin_info['mobile'];
              $log['dx_err_list'][$k]['content'] = $content; 
            }
            //推送 信贷
            $param = array(
                    'cid' => $admin_info['cid'],
                    'title' => 'REID故障(标签无信号)',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $v['rfid_push']){
              //提醒  推送
              if('y' === SEND_STATUS){
                $p_res = pushMessageToSingle($param);  
              }else{
                $p_res['result'] = 'ok';
              }
            }
            //推送失败
            if('ok' == $p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $log['gt_err_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['gt_err_list'][$k]['cid'] = $admin_info['cid'];
              $log['gt_err_list'][$k]['title'] = $title;
              $log['gt_err_list'][$k]['body'] = $body;
              $log['gt_err_list'][$k]['transmissionContent'] = $tmc;
            }  
            //推送 所有风控
            $fk_cids = Db::name('admin')->where('role_id = 2')->getField('cid', true);
            $fk_param = array(
                    'cids' => $fk_cids,
                    'title' => 'REID故障(标签无信号)',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $v['rfid_push']){
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
              $log['gt_err_fk_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['gt_err_fk_list'][$k]['cid'] = $fk_cids;
              $log['gt_err_fk_list'][$k]['title'] = $title;
              $log['gt_err_fk_list'][$k]['body'] = $body;
              $log['gt_err_fk_list'][$k]['transmissionContent'] = $tmc;
            }  
            //msssage 待办事项 添加记录(信贷经理)
            $msg_content = array(
                'type'        => 2,
                'value'       => $v['pawn_rfid'],
                'user_name'   => $v['user_name'],
                'store_id'    => $v['store_id'],
                'store_name'  => $v['store_name'],
                'pawn_id'     => $v['pawn_id'],
                'pawn_name'   => date('Y-m-d H:i:s',$time),
                'one_id'      => $v['one_id'],
                'remarks'     => $content,
              );
            $message = array(
                'admin_id'    => $admin_info['admin_id'],
                'comm_id'     => $v['one_id'],
                'type'        => 7, 
                'title'       => 'RFID故障(标签无信号)',
                'add_time'    => $time,
                'content'     => serialize($msg_content),
              );
            DB::name('message')->add($message); 
            //（所有风控 ）
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
              foreach ($fk_list as $key => $value) {
                $fk_message[] = array(
                    'admin_id'    => $value['admin_id'],
                    'type'        => 7, 
                    'title'       => 'RFID故障(标签无信号）',
                    'add_time'    => $time,
                    'content'     => serialize($msg_content),
                  ); 
              }
            }  
            
            //bs_equipment_wrong 设备故障 添加记录
            if(!in_array($v['one_id'], $em_list)){
              $equipment_wrong = array(
                    'admin_id'    => $admin_info['admin_id'],
                    'value'       => $v['pawn_rfid'],
                    'em_id'       => $v['one_id'],
                    'em_name'     => $v['pawn_name'].'-'.$v['one_name'],
                    'content'     => 'RFID故障(标签无信号)',
                    'type'        => 2, 
                    'type2'       => 21, 
                    'value'       => $v['pawn_rfid'],
                    'user_id'     => $v['user_id'],
                    'user_name'   => $v['user_name'],
                    'store_id'    => $v['store_id'],
                    'store_name'  => $v['store_name'], 
                    'time'        => $time, 
                  );
              DB::name('equipment_wrong')->add($equipment_wrong); 
            }
          }
        
        if(!empty($fk_message)){
          DB::name('message')->insertALL($fk_message);
        }
        
          $log['dx_suc_count'] = $dx_suc_count;
          $log['dx_err_count'] = $dx_err_count;
          $log['gt_suc_count'] = $gt_suc_count;
          $log['gt_err_count'] = $gt_err_count; 
          // print_r($log);
          $add['value'] = json_encode($log, JSON_UNESCAPED_UNICODE);
      }  
          $add['type'] = '21';
          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['result'] = count($result);
          $add['dx_err_count'] = $dx_err_count;
          $add['gt_err_count'] = $gt_err_count;
          $add['runtime'] = microtime(true)-$stime;
          // print_r($add);
          //rfid_timedtask_log 添加记录
          $add_id = DB::name('rfid_timedtask_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){
            $error = 'api/Ttrfid/energy____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if(1 == $v['rfid_push']){
              if('y' === SEND_STATUS){
                if(false === send_sms('17611495523', $error)){
                  send_sms('15652604229', $error); 
                }
              }
            }
          }
    }






    // 定时任务 10分钟请求一次 判断RFID 撕毁状态 报警提醒管理员
    // 被撕毁的标签在被撕毁的时候就已经添加过了 这里只通知
    // */10 * * * * /usr/bin/curl http://bs.bestshowgroup.com/api/ttrfid/tearup
    public function tearup(){
      $stime = microtime(true); //获取程序执行开始的时间
      $time = time(); 
      $filter['p.status'] = array('in', '1,2,4'); 
      $filter['o.cruise_shop_mode'] = array('in', '2,3'); //是否自动
      $filter['o.rfid_tearup'] = 1;
      $result = DB::name('pawn')->alias('p')->join('bs_pawn_one o','p.pawn_id = o.pawn_id','right')->field('p.pawn_id,p.pawn_name,p.user_id,p.user_name,p.store_id,p.store_name,p.xd_id,o.one_id,o.pawn_name as one_name,o.pawn_rfid,o.rfid_push')->where($filter)->select(); 
      // echo DB::name('pawn')->getlastsql();die; 
      // print_r($result);die; 
      //所有故障列表 判断故障 是否 依然存在  em 表只记录一条
      $em_list = DB::name('equipment_wrong')->where(array('type' => 2, 'type2' => 22, 'status' => 1))->getField('em_id', true);
      // print_r($em_list);die;
      /*if($em_list){
        $err_rfid = DB::name('pawn')->where($filter)->getField('pawn_id', true);
      // print_r($err_rfid);die;
        if($err_rfid){
          $status = '';
          foreach ($em_list as $key => $value) {
            if(!in_array($value, $err_rfid)){ 
              $status .= $value.','; 
            }
          }
        }   
      }
      if($status){
        $status = rtrim($status,','); 
        DB::name('equipment_wrong')->where("em_id in($status)")->update(array('normal_time'=>$time, 'status'=>2));
        // echo DB::name('equipment_wrong')->getlastsql();die; 
      }*/
      if($result){
        $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = 0; 
        foreach ($result as $k => $v) {
            $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['xd_id'])->cache('admin_info')->find();
            $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内家具'.$v['pawn_name'].$v['one_name'].'RFID标签编号为'.$v['pawn_rfid'].'标签被撕毁,请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
            $title = '标签被撕毁'.$v['user_name'].$v['store_name'].$v['pawn_name'].$v['pawn_rfid'];
            $body = $content;
            $tmc = json_encode(array('title'=>'标签被撕毁','body'=>$content), JSON_UNESCAPED_UNICODE);
            // echo $admin_info['mobile'].' ' .$content;
            //提醒  发短信
            if(1 == $v['rfid_push']){
              //提醒  发短信
              if('y' === SEND_STATUS){
                $s_res = send_sms($admin_info['mobile'], $content);
              }else{
                $s_res = true;
              }
            }
            // var_dump($s_res);die;

            //发送失败
            if($s_res){
              $dx_suc_count += 1; 
            }else{
              $dx_err_count += 1; 
              $log['dx_err_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['dx_err_list'][$k]['mobile'] = $admin_info['mobile'];
              $log['dx_err_list'][$k]['content'] = $content; 
            }
            //推送
            $param = array(
                    'cid' => $admin_info['cid'],
                    'title' => 'REID故障(标签被撕毁)',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $v['rfid_push']){
              //提醒  推送
              if('y' === SEND_STATUS){
                $p_res = pushMessageToSingle($param);  
              }else{
                $p_res['result'] = 'ok';
              }
            }
            //推送失败
            if('ok' == $p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $log['gt_err_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['gt_err_list'][$k]['cid'] = $admin_info['cid'];
              $log['gt_err_list'][$k]['title'] = $title;
              $log['gt_err_list'][$k]['body'] = $body;
              $log['gt_err_list'][$k]['transmissionContent'] = $tmc;
            }  
            //推送 所有风控
            $fk_cids = Db::name('admin')->where('role_id = 2')->getField('cid', true);
            $fk_param = array(
                    'cids' => $fk_cids,
                    'title' => 'REID故障(标签被撕毁)',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $v['rfid_push']){
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
              $log['gt_err_fk_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['gt_err_fk_list'][$k]['cid'] = $fk_cids;
              $log['gt_err_fk_list'][$k]['title'] = $title;
              $log['gt_err_fk_list'][$k]['body'] = $body;
              $log['gt_err_fk_list'][$k]['transmissionContent'] = $tmc;
            }  
            //msssage 待办事项 添加记录)(信贷经理)
            $msg_content = array(
                'type'        => 2,
                'value'       => $v['pawn_rfid'],
                'user_name'   => $v['user_name'],
                'store_id'    => $v['store_id'],
                'store_name'  => $v['store_name'],
                'pawn_id'     => $v['pawn_id'],
                'pawn_name'   => date('Y-m-d H:i:s',$time),
                'one_id'      => $v['one_id'],
                'remarks'     => $content,
              );
            $message = array(
                'admin_id'    => $admin_info['admin_id'],
                'comm_id'     => $v['one_id'],
                'type'        => 7, 
                'title'       => 'RFID故障(标签被撕毁)',
                'add_time'    => $time,
                'content'     => serialize($msg_content),
              );
            DB::name('message')->add($message); 
            //（所有风控 ）
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
              foreach ($fk_list as $key => $value) {
                $fk_message[] = array(
                    'admin_id'    => $value['admin_id'],
                    'type'        => 7, 
                    'title'       => 'RFID故障(标签被撕毁）',
                    'add_time'    => $time,
                    'content'     => serialize($msg_content),
                  ); 
              }
            }  

            //bs_equipment_wrong 设备故障 添加记录
            if(!in_array($v['one_id'], $em_list)){
              $equipment_wrong = array(
                    'admin_id'    => $admin_info['admin_id'],
                    'value'       => $v['pawn_rfid'],
                    'em_id'       => $v['one_id'],
                    'em_name'     => $v['pawn_name'].'-'.$v['one_name'],
                    'content'     => 'RFID故障(标签被撕毁)',
                    'type'        => 2, 
                    'type2'       => 22, 
                    'value'       => $v['pawn_rfid'],
                    'user_id'     => $v['user_id'],
                    'user_name'   => $v['user_name'],
                    'store_id'    => $v['store_id'],
                    'store_name'  => $v['store_name'], 
                    'time'        => $time, 
                  );
              DB::name('equipment_wrong')->add($equipment_wrong); 
            }
          }
        
        if(!empty($fk_message)){
          DB::name('message')->insertALL($fk_message);
        }
        
          $log['dx_suc_count'] = $dx_suc_count;
          $log['dx_err_count'] = $dx_err_count;
          $log['gt_suc_count'] = $gt_suc_count;
          $log['gt_err_count'] = $gt_err_count; 
          // print_r($log);
          $add['value'] = json_encode($log, JSON_UNESCAPED_UNICODE);
      }  
          $add['type'] = '22';
          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['result'] = count($result);
          $add['dx_err_count'] = $dx_err_count;
          $add['gt_err_count'] = $gt_err_count;
          $add['runtime'] = microtime(true)-$stime;
          // print_r($add);
          //rfid_timedtask_log 添加记录
          $add_id = DB::name('rfid_timedtask_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){
            $error = 'api/Ttrfid/tearup____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if(1 == $v['rfid_push']){
              if('y' === SEND_STATUS){
                if(false === send_sms('17611495523', $error)){
                  send_sms('15652604229', $error); 
                }
              }
            }
          }
    }


 











    // 定时任务 30分钟请求一次 判断RFID 电量状态 报警提醒管理员
    // */30 * * * * /usr/bin/curl http://bs.bestshowgroup.com/api/ttrfid/energy
    public function energy(){
      $stime = microtime(true); //获取程序执行开始的时间
      $time = time(); 
      $filter['p.status'] = array('in', '1,2,4');
      $filter['o.cruise_shop_mode'] = array('in', '2,3'); //是否自动
      $filter['o.rfid_energy'] = 0;
      $result = DB::name('pawn')->alias('p')->join('bs_pawn_one o','p.pawn_id = o.pawn_id','right')->field('p.pawn_id,p.pawn_name,p.user_id,p.user_name,p.store_id,p.store_name,p.xd_id,o.one_id,o.pawn_name as one_name,o.pawn_rfid,o.rfid_push')->where($filter)->select(); 
      // echo DB::name('pawn')->getlastsql();die; 
      // print_r($result);die; 
      //所有故障列表 判断故障 是否 依然存在  em 表只记录一条 
      $em_list = DB::name('equipment_wrong')->where(array('type' => 2, 'type2' => 23, 'status' => 1))->getField('em_id', true);
      // print_r($em_list);die;
      if($em_list && $result){
        $err_rfid = DB::name('pawn')->alias('p')->join('bs_pawn_one o','p.pawn_id = o.pawn_id','right')->where($filter)->getField('one_id', true);
        // print_r($err_rfid);die;
        $status = '';
        foreach ($em_list as $key => $value) {
          if(!in_array($value, $err_rfid)){ 
            $status .= "'$value',";
          }
        }
      }
      if($status){
        $status = rtrim($status,','); 
        DB::name('equipment_wrong')->where("type = 2 and type2 = 23 and status = 1 and em_id in($status)")->update(array('normal_time'=>$time, 'status'=>2));
        // echo DB::name('equipment_wrong')->getlastsql();die; 
      }
      if($result){
        $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = 0; 
        foreach ($result as $k => $v) {
            $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['xd_id'])->cache('admin_info')->find();
            $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内家具'.$v['pawn_name'].$v['one_name'].'RFID标签编号为'.$v['pawn_rfid'].'标签低电量,请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
            $title = '标签被撕毁'.$v['user_name'].$v['store_name'].$v['pawn_name'].$v['pawn_rfid'];
            $body = $content;
            $tmc = json_encode(array('title'=>'标签低电量','body'=>$content), JSON_UNESCAPED_UNICODE);
            // echo $admin_info['mobile'].' ' .$content;
            //提醒  发短信
            if(1 == $v['rfid_push']){
              //提醒  发短信
              if('y' === SEND_STATUS){
                $s_res = send_sms($admin_info['mobile'], $content);
              }else{
                $s_res = true;
              }
            }
            // var_dump($s_res);die;

            //发送失败
            if($s_res){
              $dx_suc_count += 1; 
            }else{
              $dx_err_count += 1; 
              $log['dx_err_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['dx_err_list'][$k]['mobile'] = $admin_info['mobile'];
              $log['dx_err_list'][$k]['content'] = $content; 
            }
            //推送
            $param = array(
                    'cid' => $admin_info['cid'],
                    'title' => 'REID故障(标签低电量)',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $v['rfid_push']){
              //提醒  推送
              if('y' === SEND_STATUS){
                $p_res = pushMessageToSingle($param);  
              }else{
                $p_res['result'] = 'ok';
              }
            }
            //推送失败
            if('ok' == $p_res['result']){
              $gt_suc_count += 1;  
            }else{
              $gt_err_count += 1; 
              $log['gt_err_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['gt_err_list'][$k]['cid'] = $admin_info['cid'];
              $log['gt_err_list'][$k]['title'] = $title;
              $log['gt_err_list'][$k]['body'] = $body;
              $log['gt_err_list'][$k]['transmissionContent'] = $tmc;
            } 
            //推送 所有风控
            $fk_cids = Db::name('admin')->where('role_id = 2')->getField('cid', true);
            $fk_param = array(
                    'cids' => $fk_cids,
                    'title' => 'REID故障(标签低电量)',
                    'body' => $body,
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              ); 
            if(1 == $v['rfid_push']){
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
              $log['gt_err_fk_list'][$k]['rf_id'] = $v['pawn_rfid'];
              $log['gt_err_fk_list'][$k]['cid'] = $fk_cids;
              $log['gt_err_fk_list'][$k]['title'] = $title;
              $log['gt_err_fk_list'][$k]['body'] = $body;
              $log['gt_err_fk_list'][$k]['transmissionContent'] = $tmc;
            }   
            //msssage 待办事项 添加记录
            $msg_content = array(
                'type'        => 2,
                'value'       => $v['pawn_rfid'],
                'user_name'   => $v['user_name'],
                'store_id'    => $v['store_id'],
                'store_name'  => $v['store_name'],
                'pawn_id'     => $v['pawn_id'],
                'pawn_name'   => date('Y-m-d H:i:s',$time),
                'one_id'      => $v['one_id'],
                'remarks'     => $content,
              );
            $message = array(
                'admin_id'    => $admin_info['admin_id'],
                'comm_id'     => $v['one_id'],
                'type'        => 7, 
                'title'       => 'RFID故障(标签低电量)',
                'add_time'    => $time,
                'content'     => serialize($msg_content),
              );
            DB::name('message')->add($message); 
            //（所有风控) 
            $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
            if(!empty($fk_list)){
              foreach ($fk_list as $key => $value) {
                $fk_message[] = array(
                    'admin_id'    => $value['admin_id'],
                    'type'        => 7, 
                    'title'       => 'RFID故障(标签低电量)',
                    'add_time'    => $time,
                    'content'     => serialize($msg_content),
                  ); 
              }
            }

            //bs_equipment_wrong 设备故障 添加记录
            if(!in_array($v['one_id'], $em_list)){
              $equipment_wrong = array(
                    'admin_id'    => $admin_info['admin_id'],
                    'value'       => $v['pawn_rfid'],
                    'em_id'       => $v['one_id'],
                    'em_name'     => $v['pawn_name'].'-'.$v['one_name'],
                    'content'     => 'RFID故障(标签低电量)',
                    'type'        => 2, 
                    'type2'       => 23, 
                    'value'       => $v['pawn_rfid'],
                    'user_id'     => $v['user_id'],
                    'user_name'   => $v['user_name'],
                    'store_id'    => $v['store_id'],
                    'store_name'  => $v['store_name'], 
                    'time'        => $time, 
                  );
              DB::name('equipment_wrong')->add($equipment_wrong); 
            }
        }
        if(!empty($fk_message)){
          DB::name('message')->insertALL($fk_message);
        }          
          $log['dx_suc_count'] = $dx_suc_count;
          $log['dx_err_count'] = $dx_err_count;
          $log['gt_suc_count'] = $gt_suc_count;
          $log['gt_err_count'] = $gt_err_count; 
          // print_r($log);
          $add['value'] = json_encode($log, JSON_UNESCAPED_UNICODE);
      }  
          $add['type'] = '23';
          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['result'] = count($result);
          $add['dx_err_count'] = $dx_err_count;
          $add['gt_err_count'] = $gt_err_count;
          $add['runtime'] = microtime(true)-$stime;
          // print_r($add);
          //rfid_timedtask_log 添加记录
          $add_id = DB::name('rfid_timedtask_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){
            $error = 'api/Ttrfid/energy____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if(1 == $v['rfid_push']){
              if('y' === SEND_STATUS){
                if(false === send_sms('17611495523', $error)){
                  send_sms('15652604229', $error); 
                }
              }
            }
          }
    }
} 