<?php

/**
 * 个人信用动态变更
 * Author: iWuxc
 * time 2017-12-27
 */
namespace app\clientapi\logic;

use think\Db;
use think\Exception;
use think\Loader;
use app\clientapi\model\UserCredit;

class UserCreditLogic extends BaseLogic{

    protected $model;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this -> model = new UserCredit();
    }

    /**
     * 获取个人信用详情
     * @param $user_id
     * @return mixed
     */
    public function getRow($user_id){
        $res = $this -> model -> detail($user_id);
        return $res;
    }
}