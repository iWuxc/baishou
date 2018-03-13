<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/13
 * Time: 20:05
 */
namespace app\admin\model;
use think\Model;
use think\Db;
use think\Page;

class UserCredit extends Model {

    protected $table = 'bs_user_credit';

    /**
     * 获取列表数据
     * @param array $param
     * @return array
     */
    public function getList($param = array()){
        $param['page_size'] = 10;
        $count =  Db::table($this->table)->alias('uc')->join('__USERS__ u','uc.user_id = u.user_id')->where($param['where'])->count();
        $Page = new Page($count,$param['page_size'],$param['parameter']);
        $page_show = $Page->show();
        $list = Db::table($this->table)
                ->alias('uc')
                ->field('uc.id,uc.user_id,uc.credit,uc.credit_used,uc.credit_rest,uc.assess_total,uc.check_rate,uc.grand_total,uc.store_total,u.name user_name')
                ->join('__USERS__ u','uc.user_id = u.user_id')
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
            ->alias('uc')
            ->field('uc.id,uc.user_id,uc.credit_total,uc.credit_used,uc.credit_rest,uc.assess_total,uc.check_rate,uc.grand_total,uc.store_total,u.name user_name')
            ->join('__USERS__ u','uc.user_id = u.user_id')
            ->where($param['where'])
            ->find();
        return $row;
    }

}