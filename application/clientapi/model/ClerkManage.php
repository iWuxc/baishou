<?php
/**
 * 店员信息模型类
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;
use think\Config;

class ClerkManage extends Model{

    protected $table = "user_store_clerk";

    /**
     * 获取店员数据
     * @param $userid
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getClerkList($userid){
        $where['user_id'] = $userid;
        $where['is_lock'] = 1;
        $clerks = Db::name($this->table) -> field('clerk_id,clerk_name') -> where($where) -> select();
        return $clerks;
    }

    /**
     * 店员详情
     * @param $where
     * @return $this
     */
    public function detail($where){
        $clerk = Db::name($this->table)
            -> field('clerk_name,credential_type,id_number,belong_to_store,double_check')
            -> where($where) -> find();
        return $clerk;
    }
}