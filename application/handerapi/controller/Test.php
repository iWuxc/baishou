<?php 
namespace app\handerapi\controller;  
use think\Cache;
use think\Db;
class Test{  
   function test(){ 
      $time = time();
      if('POST' !== $_SERVER['REQUEST_METHOD']){
          $data['result'] = 0;
          $data['err_code'] = 100;
          $data['msg'] = '错误的请求方式';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      //参数
      if(!isset($_POST['rf_ids'])){
          $data['result'] = 0;
          $data['err_code'] = 101;
          $data['msg'] = '无效参数';
          exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 
      }
      $data['result'] = 1;
      $data['err_code'] = 202;
      $data['msg'] = '请求成功';
      exit(json_encode($data, JSON_UNESCAPED_UNICODE)); 

   } 
}