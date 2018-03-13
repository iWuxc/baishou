<?php  
namespace app\api\controller;    
use think\Controller; 
use think\Db;
// header("Content-type: text/html; charset=utf-8");
class Mt extends controller{ 
    private $model = 'store_dvr';  
    function getToken(){
      echo 'YmdHi_____: '.date("YmdHi", time("YmdHi")).'Lbb';
      echo '<br>';
      echo '<br>';
      echo '当前可用token_____: '. MD5(date("YmdHi", time()).'Lbb'); 
    }
    //token验证
    function check_token($token){
       $now = MD5(date("YmdHi", time("YmdHi")).'Lbb'); 
       if($token == $now){
        return true;
       }else{
         $front1 = MD5(date("YmdHi", time("YmdHi")-60).'Lbb');
         $front2 = MD5(date("YmdHi", time("YmdHi")-120).'Lbb');
         $front3 = MD5(date("YmdHi", time("YmdHi")-180).'Lbb');
         $front4 = MD5(date("YmdHi", time("YmdHi")-240).'Lbb');
          if($token == $front1 || $token == $front2 || $token == $front3 || $token == $front4){
              return true;
          }else{
              return false; 
          }
       } 
    } 
    // 获取所有硬盘录像机信息：编号 名称 设备IP 设备端口 用户名 密码 分类编号12345（方便以后录像机太多配置多程序监听）
    public function getDvrList(){
      if('POST' !== $_SERVER['REQUEST_METHOD']){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['msg'] = '错误的请求方式';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!isset($_POST['token']) || !isset($_POST['group'])){
          $data['result'] = 0;
          $data['err_code'] = 102;
          $data['msg'] = '参数不完整';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(false === SELF::check_token($_POST['token'])){
          $data['result'] = 0;
          $data['err_code'] = 103;
          $data['msg'] = 'token 错误';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!is_numeric($_POST['group'])){
          $data['result'] = 0;
          $data['err_code'] = 104;
          $data['msg'] = 'group 格式错误';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      $model = $this->model;
      $store_ids = DB::name('user_store')->where(array('is_check'=>1))->getField('store_id', true); 
      $store_ids = implode(',', $store_ids);
      $filter['status'] = 1;
      $filter['group'] = $_POST['group'];
      $filter['store_id'] = array('in', $store_ids);  
      $list = M($model) ->field('id,group,deploy_status,login_status,ip,port,login_account,login_password') ->where($filter) -> order('store_id desc, id desc') -> select(); 
      if($list){
          $data['result'] = 1;
          $data['err_code'] = 200;
          $data['msg'] = '获取成功';
          $data['data'] = $list;
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      }else{
          $data['result'] = 0;
          $data['err_code'] = 200;
          $data['msg'] = '暂无数据';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      } 
    } 
    public function badWarning(){  
      //test
      // $_POST['type'] = '2';
      // $_POST['ip'] = '192.168.1.81';
      // $_POST['msg'] = "信号异常，报警通道： 33 \ 36 \\";
      $stime=microtime(true); //获取程序执行开始的时间
      $time = time();   
      extract($_POST);
      if('POST' !== $_SERVER['REQUEST_METHOD']){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['msg'] = '错误的请求方式';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!isset($type) || !isset($ip) || !isset($msg)){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['time'] = $time;
          $data['msg'] = '参数不完整';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!is_numeric($type) || empty($msg)){
          $data['result'] = 0;
          $data['err_code'] = 102;
          $data['time'] = $time;
          $data['msg'] = '参数格式错误';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      } 
      if(!in_array($type, [2])){
          $data['result'] = 1;
          $data['err_code'] = 210;
          $data['msg'] = '该异常类型暂不报警';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));         
      }
      $passageway = str_replace('：',':',$msg);
      $passageway = preg_replace('/.*\:/','',$msg);
      $passageway = str_replace('\\',',', $passageway);
      $passageway = trim($passageway,','); 
      $passageway = preg_replace('/.*\：/','',$passageway); 
      if(empty($passageway)){
          $data['result'] = 1;
          $data['err_code'] = 211;
          $data['msg'] = '无异常通道';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      // echo $passageway;die; 
      // 执行所有推送报警 
        switch ($type)
        {
            case 2:
                $save['is_bad'] = 1;
                $bad_type = 2;
                $bad_desc = '信号丢失';
                break;
            default:
                $bad_type = 1000;
                $bad_desc = '其它未知报警信息';
        } 
      $save['bad_type'] = $type;
      $save['bad_time'] = $time; 
      $dvr_id = DB::name('store_dvr')->where("ip = '".$ip."' and status = 1")->getField('id');
      $result = DB::name('store_monitoring')->where("dvr_id = $dvr_id and passageway in ($passageway)")->save($save); 

      if(0 !== $result || false !== $result){ 

        $list = DB::name('store_monitoring')->where("dvr_id = $dvr_id and is_push = 1 and passageway in ($passageway)")->select(); 
        // echo DB::name('store_monitoring')->getlastsql();
        // print_r($list);die;
        //所有故障列表 判断故障 是否 依然存在  被修复修改状态
        $em_list = DB::name('equipment_wrong')->where(array('type' => 1, 'type2' => $bad_type, 'status' => 1))->getField('em_id', true); 
        $dx_suc_count = $dx_err_count = $gt_suc_count = $gt_err_count = $recove_count = 0;
        $suc_ids = $err_ids = '';
        foreach ($list as $k => $v) {
             $admin_info = DB::name('admin')->field('admin_id,cid,mobile')->where('admin_id = '.$v['admin_id'])->cache('admin_info')->find();

              $content = '您的客户'.$v['user_name'].'-'.$v['store_name'].'店铺内'.$v['alise_name'].',品牌为'.$v['brand'].'监控摄像头'.$bad_desc.',请您于尽快上门了解情况并及时汇报情况'.date('Y-m-d-H:i:s', $time);
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
              }else{
                DB::name('equipment_wrong')->where("em_id = ".$v['id'])->setField('time', time());
                // echo DB::name('equipment_wrong')->getlastsql(); die;
              }
        }
        // echo DB::name('store_monitoring')->getlastsql(); die;
        // var_dump($result);  die;  

          $add['bad_type'] = $bad_type;
          $add['ip'] = $ip;
          $add['passageway'] = $passageway;
          $add['result'] = $result; 
          $add['dx_suc_count'] = $dx_suc_count;
          $add['dx_err_count'] = $dx_err_count;
          $add['gt_suc_count'] = $gt_suc_count;
          $add['gt_err_count'] = $gt_err_count; 
          $add['time'] = date('Y-m-d H:i:s', $time);
          $add['runtime'] = microtime(true)-$stime;
          // print_r($add);
          // rfid_timedtask_log 添加记录
          $add_id = DB::name('bad_warning')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){ 
            $error = 'api/Mt/badWarning____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if('y' === SEND_STATUS){
              if(false === send_sms('17611495523', $error)){
                send_sms('15652604229', $error); 
              }
            }
          } 

          $data['result'] = 1;
          $data['err_code'] = 201;
          $data['time'] = $time;
          $data['msg'] = '请求成功';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }else{
          $data['result'] = 1;
          $data['err_code'] = 500;
          $data['msg'] = '服务器繁忙，请稍后再试！';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      } 

    } 
    //更新登录状态 
    public function setLoginStatus(){
      if('POST' !== $_SERVER['REQUEST_METHOD']){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['msg'] = '错误的请求方式';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!isset($_POST['token']) || !isset($_POST['id']) || !isset($_POST['login_status'])){
          $data['result'] = 0;
          $data['err_code'] = 102;
          $data['msg'] = '参数不完整';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      } 
      if(false === SELF::check_token($_POST['token'])){
          $data['result'] = 0;
          $data['err_code'] = 103;
          $data['msg'] = 'token 错误';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!is_numeric($_POST['id']) || !is_numeric($_POST['login_status'])){
          $data['result'] = 0;
          $data['err_code'] = 104;
          $data['msg'] = '参数格式错误';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      $model = $this->model;
      $filter['id'] = $_POST['id'];
      $filter['status'] = 1;
      $login_status = M($model) ->where($filter) ->getField('login_status'); 
      if(empty($login_status)){
          $data['result'] = 0;
          $data['err_code'] = 401;
          $data['msg'] = '录像机不存在';
          $data['data'] = $list;
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      }
      if(1 == $login_status && 2 == $_POST['login_status']){
        if('y' === SEND_STATUS){
          $content = "store_dvr.id=".$_POST['id']."登录失败。时间：".date("Y-m-d-H:i:s", time());
          send_sms('15652604229', $content);
        }
      }
      if(1 == $_POST['login_status']){
        $save['last_login_time'] = time();
      }
        $save['login_status'] = $_POST['login_status'];
        $result = M($model) ->where($filter) -> save($save); 
        if(0 !== $result || false !== $result){
          $data['result'] = 1;
          $data['err_code'] = 200;
          $data['msg'] = '状态更新成功';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
        }else{
          $data['result'] = 1;
          $data['err_code'] = 500;
          $data['msg'] = '服务器繁忙，请稍后再试！';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
        } 
      
    }
    //更新布防状态
    public function setDeployStatus(){
      if('POST' !== $_SERVER['REQUEST_METHOD']){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['msg'] = '错误的请求方式';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!isset($_POST['token']) || !isset($_POST['id']) || !isset($_POST['deploy_status'])){
          $data['result'] = 0;
          $data['err_code'] = 102;
          $data['msg'] = '参数不完整';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      } 
      if(false === SELF::check_token($_POST['token'])){
          $data['result'] = 0;
          $data['err_code'] = 103;
          $data['msg'] = 'token 错误';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!is_numeric($_POST['id']) || !is_numeric($_POST['deploy_status'])){
          $data['result'] = 0;
          $data['err_code'] = 104;
          $data['msg'] = '参数格式错误';
          $data['data'] = [];
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      $model = $this->model;
      $filter['id'] = $_POST['id'];
      $filter['status'] = 1;
      $deploy_status = M($model) ->where($filter) ->getField('deploy_status'); 
      if(empty($deploy_status)){
          $data['result'] = 0;
          $data['err_code'] = 401;
          $data['msg'] = '录像机不存在';
          $data['data'] = $list;
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      }
      if(1 == $deploy_status && 2 == $_POST['deploy_status']){
        if('y' === SEND_STATUS){
          $content = "store_dvr.id=".$_POST['id']."登录失败。时间：".date("Y-m-d-H:i:s", time());
          send_sms('15652604229', $content);
        }
      }
      if($deploy_status == $_POST['deploy_status']){
          $data['result'] = 1;
          $data['err_code'] = 200;
          $data['msg'] = '状态更新成功';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      }else{
        $result = M($model) ->where($filter) -> setField('deploy_status',$_POST['deploy_status']); 
        if(0 !== $result || false !== $result){
          $data['result'] = 1;
          $data['err_code'] = 200;
          $data['msg'] = '状态更新成功';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
        }else{
          $data['result'] = 1;
          $data['err_code'] = 500;
          $data['msg'] = '服务器繁忙，请稍后再试！';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
        } 
      }
    }
    public function badWarning_back(){  
      //test
      // $_POST['type'] = '2';
      // $_POST['ip'] = '192.168.1.81';
      // $_POST['msg'] = "信号异常，报警通道： 33 \ 36 \\";
      $time = time();   
      extract($_POST);
      if('POST' !== $_SERVER['REQUEST_METHOD']){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['msg'] = '错误的请求方式';
          // exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!isset($type) || !isset($ip) || !isset($msg)){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['time'] = $time;
          $data['msg'] = '参数不完整';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      if(!is_numeric($type) || empty($msg)){
          $data['result'] = 0;
          $data['err_code'] = 102;
          $data['time'] = $time;
          $data['msg'] = '参数格式错误';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      } 

      $passageway = str_replace('：',':',$msg);
      $passageway = preg_replace('/.*\:/','',$msg);
      $passageway = str_replace('\\',',', $passageway);
      $passageway = trim($passageway,','); 
      $passageway = preg_replace('/.*\：/','',$passageway); 

      // echo $passageway;die; 
      $save['bad_type'] = $type;
      $save['bad_time'] = $time;
      if(2 == $type){
        $save['is_bad'] = 1;
      } 
      $dvr_id = DB::name('store_dvr')->where("ip = '".$ip."' and status = 1")->getField('id');
      $result = DB::name('store_monitoring')->where("dvr_id = $dvr_id and passageway in ('".$passageway."')")->save($save); 

      // echo DB::name('store_monitoring')->getlastsql(); die;
      // var_dump($result);  die; 
      
      if(0 !== $result || false !== $result){
          $data['result'] = 1;
          $data['err_code'] = 201;
          $data['time'] = $time;
          $data['msg'] = '请求成功';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }else{
          $data['result'] = 1;
          $data['err_code'] = 500;
          $data['msg'] = '服务器繁忙，请稍后再试！';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE));
      } 

    } 
} 