<?php
/**
 * 消息通知
 * Author: iWuxc
 * time 2017-12-25
 */
namespace app\handerapi\controller;

use think\Request;
use think\Db;
use app\handerapi\logic\IndexLogic;

class Index extends Base{

    protected $logic;

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $this -> logic = new IndexLogic();
    }
    //主页
    public function index(){
        // 店铺巡视数量
        $where = array();
        $where['is_check'] = 1;
        $where['task_status'] = 1;
        $check_count = M('user_store') -> where($where) -> count(); 
        //异常数量
        $wrong_count = M('equipment_wrong')-> group('store_name') -> count(); 
        $res = array();
        $res['check_count'] = $check_count;
        $res['wrong_count'] = $wrong_count;
        $res ? $this -> _toJson('1', '获取成功', $res) : $this -> _toJson('1', '暂无数据', []);
    }
    //所有铺列表
    public function check(){
        $where = array();
        $where['is_check'] = 1;
        $where['task_status'] = 1;
        $check =  M('user_store') ->where($where) -> field('store_id,store_name') -> select();
        $check ? $this -> _toJson('1', '获取成功', $check) : $this -> _toJson('1', '暂无数据', []);
    }
    //巡店店铺列表
    public function checklist(){
        $check_user_id = $_POST['check_id'];
        $where = array();
        $where['check_user_id'] = $check_user_id;
        $checklist = M('check_log') -> where($where) ->order('time desc')-> field('store_id,store_name,bad_pawn_id,time') -> select();
        foreach ($checklist as $key => $value) {
            $checklist[$key]['time'] = date('Y-m-d H:i:s',$value['time']);
        }
        $checklist ? $this -> _toJson('1', '获取成功', $checklist) : $this -> _toJson('1', '暂无数据', []);
    }
    //异常店铺列表
    public function wrong(){
        $where = array();
        $where['is_check'] = 1;
        $where['task_status'] = 1;
        $check =  M('user_store') ->where($where) -> field('store_id,store_name') -> select();
        $wrong = M('equipment_wrong')-> group('store_name') -> field('store_id,store_name') ->select(); 
        $wrong ? $this -> _toJson('1', '获取成功', $wrong) : $this -> _toJson('1', '暂无数据', []);
    }
    //巡店记录列表
    public function checklog(){
        $check_id = $_POST['check_id'];
        $where = array();
        $where['check_user_id'] = $check_id;
        $checklog = M('check_log') -> where($where)  ->order('time desc')-> field('store_name,store_id,time,bad_pawn_id')->select();
        foreach ($checklog as $key => $value) {
            $checklog[$key]['time'] = date('Y-m-d H:i:s',$value['time']);   
        }
        $checklog ? $this -> _toJson('1', '获取成功', $checklog) : $this -> _toJson('1', '暂无数据', []);
    }
    
    //所有抵押品列表
    public function moods(){
        $store_id = $_POST['store_id'];
        $where = array();
        $where['store_id'] = $store_id;
        $where['status'] = array('in',[1,2,4]);
        $moods1 = M('pawn') -> where($where) -> field('pawn_id')->select();
// echo M('pawn')->getlastsql();die;
        $moods2 = array();
        foreach ($moods1 as $key => $value) {
            foreach ($value as $k => $v) {
                $moods2[] = $v;
            }
        }
        
        $where2 = array();
        $where2['pawn_id'] = array('in',$moods2);
        $moods = M('pawn_one') -> where($where2) -> field('one_id as pawn_id,pawn_rfid,pawn_name')->select();
       
        $moods ? $this -> _toJson('1', '获取成功', $moods) : $this -> _toJson('1', '暂无数据', []);
    }

    //异常抵押品列表
    public function moods_wrong(){
        $store_id = $_POST['store_id'];
        $where = array();
        $where['store_id'] = $store_id;
        $moods_wrong = M('equipment_wrong') -> where($where) -> field('em_id,em_name')->select();

        $moods_wrong ? $this -> _toJson('1', '获取成功', $moods_wrong) : $this -> _toJson('1', '暂无数据', []);
    }

    //抵押品照片
    public function imgs(){
        $pawn_id = $_POST['pawn_id'];
        $where = array();
        $where['one_id'] = $pawn_id;
        $imgs = M('pawn_imgs') -> where($where) -> field('') -> select();
        $imgs ? $this -> _toJson('1', '获取成功', $imgs) : $this -> _toJson('1', '暂无数据', []);
    }

    public function checkend(){
        // $_POST['check_id'] = '5';
        // $_POST['store_id'] = '3';
        // $_POST['store_name'] = '1';
        // $_POST['bad_pawn_id'] = '8,9';
        // $_POST['result'] = '2';
        $stime = microtime(true); //获取程序执行开始的时间
        $time = time();
        if(empty($_POST['bad_pawn_id'])){
            $this -> _toJson('1', '数据为空');
        }

        $add = array();
        $add['check_user_id'] = $_POST['check_id'];
        $check_name  = M('check_user') -> where('check_id',$_POST['check_id']) -> field('check_name') -> select();
        $add['check_user_name'] = $check_name['check_name'];
        $add['store_id'] = $_POST['store_id'];
        $add['store_name'] = $_POST['store_name'];
        $add['bad_pawn_id'] = $_POST['bad_pawn_id'];
        $add['time'] = time();
        $add['result'] = $_POST['result'];
        $res = M('check_log') -> add($add);
        //给店铺表设定任务完成状态
        $data = array();
        $data['task_status'] = 2;
        $where = array();
        $where['store_id'] =$_POST['store_id'];
        M('user_store') -> where($where) -> save($data);

        if(!$res){
            $this -> _toJson('1', '巡店失败', []);
        }

        $bad_pawn_id = trim($_POST['bad_pawn_id'],','); 
        $filter['p.status'] = array('in', '1,2,4'); 
          $filter['o.cruise_shop_mode'] = array('in', '1,3'); //是否自动 
          $filter['o.one_id'] = array('in', $bad_pawn_id); 
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
            DB::name('equipment_wrong')->where("type = 2 and type2 = 21 and em_id in($status)")->update(array('normal_time'=>$time, 'status'=>2));
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
            //msssage 待办事项 添加记录
            $msg_content = array(
                'type'        => 2,
                'value'       => $v['pawn_rfid'],
                'user_name'   => $v['user_name'],
                'store_id'    => $v['store_id'],
                'store_name'  => $v['store_name'],
                'pawn_name'   => date('Y-m-d H:i:s',$time),
                'pawn_id'     => $v['pawn_id'],
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
            // print_r($message);
            DB::name('message')->add($message); 
            //（所有风控) 
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
                    'em_name'     => $v['pawn_name'],
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
        DB::name('message')->insertALL($fk_message);  
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
          //rfid_timedtask2_log 添加记录
          $add_id = DB::name('rfid_timedtask2_log')->add($add);
          if($log['dx_err_count'] > 0 or $log['gt_err_count'] > 0){
            $error = 'handerapi/index/checkend____id:'.$add_id.';dx_err_count:'.$log['dx_err_count'].';gt_err_count:'.$log['gt_err_count'].';time:'.date("Y-m-d-H:i:s", $time);
            if(1 == $v['rfid_push']){
              if('y' === SEND_STATUS){
                if(false === send_sms('17611495523', $error)){
                  send_sms('15652604229', $error); 
                }
              }
            }
          }

        $this -> _toJson('1', '巡店成功', $res);

    }
}