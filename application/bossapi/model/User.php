<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:21
 */
namespace app\bossapi\Model;
use \think\Model;
use think\Db;
use think\Page;
class User extends Model{

    protected $table = 'bs_users';

    /**
     * 获取列表数据
     * @param array $param
     * @return array
     */
    public function getList($param = array()){
        $param['page_size'] = 15;
        $count =  Db::table($this->table)->where($param['where'])->count();
        $Page = new Page($count,$param['page_size'],$param['parameter']);
        $page_show = $Page->show();
        $list = Db::table($this->table)
            ->field('user_id,name user_name,reg_time,type')
            ->where($param['where'])
            ->order($param['order'])
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        return array('list'=>$list,'page'=>$page_show,'page_total'=>$Page->totalRows);
    }

    /**
     * 获取单条数据
     * @param array $param
     * @return array|false|\PDOStatement|string|Model
     */
    public function findRow($param = array()){
        $row = Db::table($this->table)
            ->where($param['where'])
            ->find();
        return $row;
    }
}