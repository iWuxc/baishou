<?php
namespace app\admin\controller;
use think\Db;

class Test extends Base{ 
    function rtsp(){
        return $this->fetch();
    }
    function reid(){  
        $mobile = '17611495523';
        $content = rand(100,10000); 
        $result = send_sms($mobile, $content); 
        var_dump($result); 

    } 
    function test_sms(){  
        $mobile = '17611495523';
        $content = rand(100,10000); 
        $result = send_sms($mobile, $content); 
        var_dump($result); 

    } 
    function index(){  
        $cid = '6cdedffb008155de93b4b23c0128faaf';
        $title = '测试 title';
        $body = '测试 body';
        $transmissionContent = '测试 透传内容';
        echo $res = pushMessageToSingle($cid, $title, $body, $transmissionContent);die;
        // aaa();
        // echo '/vendor/getui/IGt.Push.php';
        //getUserStatus();

        //stoptask();

        //setTag();

        //getUserTags();

        //pushMessageToSingle();

        //pushMessageToSingleBatch();

        //getPersonaTags;

        //getUserCountByTags;

        //pushMessageToList();

        // pushMessageToApp();

        //getPushMessageResult;
    }
}