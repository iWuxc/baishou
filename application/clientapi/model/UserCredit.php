<?php
/**
 * 个人信用模型类
 * Author: iWuxc
 * time 2017-12-27
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;
use think\Page;

class UserCredit extends Base{

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * 获取个人信用详情
     * @param $user_id
     * @return bool
     */
    public function detail($user_id){
        if(!$user_id){
            return false;
        }
        $result = $this -> where(array('user_id'=>$user_id)) -> find() -> getData();
        return $result;
    }
}

