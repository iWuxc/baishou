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
class Store extends Model{

    protected $table = 'bs_user_store';

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
            ->field('store_id,store_name,address,addtime')
            ->where($param['where'])
            ->whereOr($param['whereOr'])
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
            ->field('store_id,imgs,store_name,user_name,established,address,business_area,opening_hours,
                    closeing_hours,store_mobile,property_right,rent_begintime,rent_endtime,rent,main_pro,inventory_count,inventory_year_avg,inventory_year_price')
            ->where($param['where'])
            ->find();
        return $row;
    }

}