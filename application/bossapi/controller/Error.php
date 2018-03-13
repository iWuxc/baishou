<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 10:41
 */
namespace app\bossapi\controller;

class Error extends Base {

    public function index(){
        api_response('1','404');
    }

}