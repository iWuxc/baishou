<?php 
namespace app\api\controller;  
use think\Cache;
use think\Db;
use think\Query;
class Test{  
   function aaa(){
header('Location:  ');
   }
   function gt(){

    $fk_cids[0]='a9eb7a165f92b33cbc6ab93c30989f61';
    $fk_cids[1]='ed210d89b19bbc2c47740c6ed38bdac9';

   $param = array(
      'cids' => $fk_cids,
            'title' => 'test IOS title',
            'body' => 'test IOS body',
            'transmissionContent' => json_encode(array('title'=>'客户待还款提醒','body'=>'lkdsjfksdjfk'), JSON_UNESCAPED_UNICODE),
            'type'=>'boss'
          ); 
   // print_r($param);die;
      $p_res = pushMessageToList($param); 
var_dump($p_res);
   }
   function monitor(){
      //设置运行时间:永不超时
      set_time_limit (0);
      //开启缓冲
      ob_implicit_flush ();
      //IP地址
      $ip = "192.168.1.83";
      //监听端口
      $port = 8000;             
      //创建socket
      $socket = socket_create (AF_INET, SOCK_STREAM, 0);      
      if (!$socket){
        die("创建socket失败！").socket_strerror(socket_last_error());
      }
      //绑定socket
      $bind = socket_bind ($socket, $ip, $port);             
      if (!$bind){
          die("绑定.socket失败！").socket_strerror ($bind);  
      }
      //监听socket
      $listen = socket_listen ($socket);                
      if (!$listen){
        
        die("监听失败！").socket_strerror ($listen);
      }
      echo "{$port}端口监听成功!";
   }

















   function abc(){
    $d['a'] = 8;
    $d['ab'] = 8;
    $d['ac'] = 8;
return json_encode($d);

   } 
   function test(){  
    $re = httpRequest("http://bs.bestshowgroup.com/index.php/api/test/abc" ,"GET");
    print_r($re);die;
echo round((17+1)/4) ;die;
   $a = array(
      'a'=>'aa',
      'b'=>'bb',
      'value'=>'cc',
    );
   print_r($a);die;  
 for ($i=1001; $i < 2000; $i++) { 
   

 
   $result = Db::execute("INSERT INTO `bs`.`bs_pawn` (`pawn_id`, `pawn_rfid`, `rfid_tearup`, `rfid_energy`, `rfid_push`, `rf_time`, `status`, `pawn_no`, `store_id`, `store_no`, `user_id`, `user_no`, `user_name`, `store_name`, `pawn_name`, `pawn_model`, `wood_id`, `wood_name`, `pawn_area`, `pawn_num`, `pawn_price`, `pawn_cost`, `pawn_remarks`, `pawn_value`, `new_value`, `new_lrr`, `new_time`, `alarm_value`, `alarm_status`, `addtime`, `addtype`, `updtime`, `is_del`, `xd_id`, `pg_id`, `pg_time`, `check_id`, `check_remarks`, `check_time`, `crefuse_time`, `green_id`, `green_remarks`, `green_time`, `grefuse_time`, `gsubmint_remarks`, `gsubmit_time`, `autogreen_time`) VALUES ('".$i."', '".$i."', '0', '01', '1', '1516708957', '1', '201801231957446369', '5', 'GDZSHM03000005', '3', 'GDZSHM0200001', '李娜', '香飘万里', '非黄', '99*63*80', '1', '刺猬紫檀 ', '西非', '2', '20000.00', '100000.00', '海南黄花梨已基本绝版，成器的家具几乎都已进入拍卖收藏市场。一件家具价格都是几十万、上百万人民币。', '100000.00', '48000.00', '-0.72', '1516716602', '10.00', '1', '1516708664', '1', '1516713127', '1', '20', '23', '1516708957', '22', '', '1516709208', NULL, NULL, NULL, '0', '0', '', '0', '0')");
        dump($result);
         }   die;
    // if($list){
    //     $this->assign('list', $list );
    //     $this->display();
    // } else {
    //     $this->error($Dao->getError());
    // }



    //INSERT INTO `bs`.`bs_pawn` (`pawn_id`, `pawn_rfid`, `rfid_tearup`, `rfid_energy`, `rfid_push`, `rf_time`, `status`, `pawn_no`, `store_id`, `store_no`, `user_id`, `user_no`, `user_name`, `store_name`, `pawn_name`, `pawn_model`, `wood_id`, `wood_name`, `pawn_area`, `pawn_num`, `pawn_price`, `pawn_cost`, `pawn_remarks`, `pawn_value`, `new_value`, `new_lrr`, `new_time`, `alarm_value`, `alarm_status`, `addtime`, `addtype`, `updtime`, `is_del`, `xd_id`, `pg_id`, `pg_time`, `check_id`, `check_remarks`, `check_time`, `crefuse_time`, `green_id`, `green_remarks`, `green_time`, `grefuse_time`, `gsubmint_remarks`, `gsubmit_time`, `autogreen_time`) VALUES ('35', '00000001', '1', '01', '1', '1516708957', '1', '201801231957446369', '5', 'GDZSHM03000005', '3', 'GDZSHM0200001', '李娜', '香飘万里', '非黄', '99*63*80', '1', '刺猬紫檀 ', '西非', '2', '20000.00', '100000.00', '海南黄花梨已基本绝版，成器的家具几乎都已进入拍卖收藏市场。一件家具价格都是几十万、上百万人民币。', '100000.00', '48000.00', '-0.72', '1516716602', '10.00', '1', '1516708664', '1', '1516713127', '1', '20', '23', '1516708957', '22', '', '1516709208', NULL, NULL, NULL, '0', '0', '', '0', '0');







    $tmc = json_encode(array('title'=>'4444','body'=>'55555'), JSON_UNESCAPED_UNICODE);
    $param = array(
            'cids' => array('322e61e939e9728668b54dd272cf537a','0f298cf42df7bc40af8bb3cbed805fe7','c52c492215faa6c06f7e2bad10250164','0e399e8e9a3747464651bf08fa0bd38b','cefca553bc2777eb87c066ad027af0bf','de8cd8d650e68070b53b3a92320de5e8'),
            'title' => 'title1111111111',
            'body' => 'bod22222222222',
            'transmissionContent' => $tmc,
            'type'=>'boss' 
      );  
     $res = pushMessageToList($param);  
     print_r($res);
   }
   function sss(){

echo md5('admin');die;
// cache::set('key',null);die;
	Db::name('admin')->cache('key')->update(['admin_id'=>10,'nickname'=>'wangyuanhao10']); 
   	$c = Db::name('admin')->cache('key', 15)->select();
   	echo Db::name('admin')->getlastsql();
	// Db::name('admin')->cache(true)->find(1);  
	// Db::name('admin')->cache('admin')->select([1,9,10]);
   	$c = cache::get('key');
   	print_r($c);die;
   }
   function tt_rfid(){
    $tmc = json_encode(array('title'=>'标签无信号','body'=>'sdlkjfksldjfk算了快递费快递放假'), JSON_UNESCAPED_UNICODE);
     $param = array(
                    'cid' => 'cefca553bc2777eb87c066ad027af0bf',
                    'title' => '少时诵诗书所所所所所所所',
                    'body' => '$body',
                    'transmissionContent' => $tmc,
                    'type'=>'boss' 
              );
            // print_r($param);die;
            $p_res = pushMessageToSingle($param); 
            print_r($p_res);die;

   }
   function ccc(){

    // $c = Db::name('rfid_timedtask_log')->cache('key111', 8)->where('id<100')->limit(1,5)->select();
$add['type'] = '21';
          $add['time'] = date('Y-m-d H:i:s', time()); 
          $add['dx_err_count'] = 1;
          $add['gt_err_count'] = 2;
          $add['runtime'] = 444;
          // print_r($add);
          //rfid_timedtask_log 添加记录
          DB::name('rfid_timedtask_log')->cache('cc')->add($add);


    $c = Db::name('rfid_timedtask_log')->cache('cc', 3600)->order('id desc')->limit(1,10)->select();

echo Db::name('rfid_timedtask_log')->getlastsql();
 
        print_r($c);die;
echo 11111111;
    Db::name('loan_timedtask_log')->cache('key',7)->update(['id'=>47,'value'=>'335']); 
    // $c = Db::name('admin')->cache('key', 5)->select();
$d = cache::get('key');
    // echo Db::name('loan_timedtask_log')->getlastsql();
    // 
    print_r($d);die;
   	 // cache::set('key','null');
  Db::name('admin')->cache('key')->update(['admin_id'=>10,'nickname'=>'wangyuanhao10']); 
    $c = Db::name('admin')->cache('key', 15)->select();
    // echo Db::name('admin')->getlastsql();
  // Db::name('admin')->cache(true)->find(1);  
  // Db::name('admin')->cache('admin')->select([1,9,10]);
    $c = cache::get('key');
    print_r($c);die;

   }
    function telnet_test(){ 
      // $output = [3,4,5];
      // $output[(count($output)-2)] = 2;
      // print_r($output);die;
      // $ex = "telnet chuanwan.f3322.net 554";
      //     echo $ex;
      //     echo '<br>'; 
      //     exec($ex, $output);  
      //     print_r($output);
      // $ex = "telnet baitutan.f3322.net 554";
      //     echo $ex;
      //     echo '<br>'; 
      //     exec($ex, $output);  
      //     print_r($output);


      //     die;
      $stime=microtime(true); //获取程序执行开始的时间
      $result = Db::name('store_monitoring')->select(); 
      print_r($result);
      echo '<br>'; 
      foreach ($result as $k => $v) {
          $ex = "telnet ".$v['dns']. ' '.$v['port'];
          echo $ex;
          echo '<br>'; 
          exec($ex, $output);  
          print_r($output);
          echo '<br>'; 
          if($output[(count($output)-2)] == 'Connected to '.$v['dns'].'.'){
            echo $v['id'].' ';
          }else{
            echo 'N ';
          }
      }
      $etime=microtime(true);//获取程序执行结束的时间 
      echo $etime-$stime;  //计算差值
die; 
    }
}