<?php

/**
 * 用户类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\controller;

use think\Request;
use think\Db;

class Test extends Base {

    public function uploadImage(){
        $url = '/home/wwwroot/repos/public/upload/yunxin/2018/03-06/0fe7b96124110ab4feba8d1467f7ce44.png';
        //$res = qiniuuploadv($url,'StoreImage');
        @unlink($url);
        //var_dump($res);
    }
}