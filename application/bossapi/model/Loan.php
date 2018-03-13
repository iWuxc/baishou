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
class Loan extends Model{

    protected $table = 'bs_loan';

    /**
     * 获取列表数据
     * @param array $param
     * @return array
     */
    public function getList($param = array()){
        $param['page_size'] = 15;
        $count =  Db::table($this->table)->alias('l')
            ->join('__USERS__ u','u.user_id = l.user_id')
            ->where($param['where'])->count();
        $Page = new Page($count,$param['page_size'],$param['parameter']);
        $page_show = $Page->show();
        $list = Db::table($this->table)
            ->alias('l')
            ->field('l.id loan_id,l.user_id,l.loan_number,l.apply_amount,l.stp_time,l.is_check')
            ->join('__USERS__ u','u.user_id = l.user_id')
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
            ->field('id loan_id,user_id,user_name,loan_number,apply_amount,str_time,stp_time,is_check')
            ->where($param['where'])
            ->find();
        return $row;
    }
}