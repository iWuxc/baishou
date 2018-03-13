<?php
/**
 * 用户模型类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;
use think\Page;

class Loan extends Base{

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /*
     * 获取提款记录列表
     * @param $param
     */
    public function getLoanList($param){
        $param['page_size'] = 10;
        /****存在分页的情况下使用****/
        $count = $this->where($param['where'])->count();
        $Page = new Page($count,$param['page_size'],$param['p']);
        $page_show = $Page->show();
        /****存在分页的情况下使用****/
        $list = Db::name('loan')
            -> field($param['field'])
            ->where($param['where'])
            ->order($param['order'])
            ->limit($Page->firstRow.','.$Page->listRows)//分页情况下
            ->select();
        if(empty($list)){
            return [];
        }
        return $list;
    }

    public function detail($where){
        $result = $this -> field('id loan_id,loan_number,apply_amount,str_time,stp_time,is_check')
            ->where($where) -> find();
        return $result;
    }
}