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
class Pawn extends Model{

    protected $table = 'bs_pawn';

    /**
     * 获取列表数据
     * @param array $param
     * @return array
     */
    public function getList($param = array()){
        $param['page_size'] = 15;
        $count =  Db::table($this->table)->alias('p')->join('__USER_STORE__ us','us.store_id = p.store_id')->where($param['where'])->count();
        $Page = new Page($count,$param['page_size'],$param['parameter']);
        $page_show = $Page->show();
        $list = Db::table($this->table)
            ->alias('p')
            ->field('p.pawn_id,p.pawn_name,p.pawn_no,p.status,p.gsubmit_time,us.store_name')
            ->join('__USER_STORE__ us','us.store_id = p.store_id')
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
            ->alias('p')
            ->field('p.pawn_id,p.store_id,p.store_name,p.pawn_name,p.pawn_no,p.pawn_model,p.pawn_num,pw.name as wood_name,p.pawn_area,p.pawn_value,p.pawn_cost,
                    u.check_rate,u.now_rate,p.pawn_price,p.new_value as pawn_value,p.green_remarks,p.status,p.user_name,p.user_id,p.pawn_auxarea,p.wood_auxname,p.pawn_num,p.wood_auxid')
            ->join('__PAWN_WOOD__ pw','pw.id = p.wood_id')
            ->join('__USER_CREDIT__ u','u.user_id = p.user_id')
            ->where($param['where'])
            ->find();
        return $row;
    }
}