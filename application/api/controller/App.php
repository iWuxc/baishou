<?php  
namespace app\api\controller;    
use think\Controller; 
use think\Db;
class App extends controller{
    public function clien_apk(){
        $equipment = get_device_type();
        if('ios' == $equipment){
            exit('ios暂无');
            // $url = $_SERVER['HTTP_HOST'].'/'.Db::name('config')->where('name','client_apk')->getField('value');
        }else{
            $url = 'http://'.$_SERVER['HTTP_HOST'].Db::name('config')->where('name','client_apk')->getField('value');
        }
        header("Location: $url");
    }
    public function manage_apk(){
        $equipment = get_device_type();
        if('ios' == $equipment){
            exit('ios暂无');
            // $url = $_SERVER['HTTP_HOST'].'/'.Db::name('config')->where('name','manage_apk')->getField('value');
        }else{
            $url = 'http://'.$_SERVER['HTTP_HOST'].Db::name('config')->where('name','manage_apk')->getField('value');
        header("Location: $url");
        }
    }
} 