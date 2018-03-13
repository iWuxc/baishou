<?php
/**
 * 消息模型类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;
use think\Page;

class Message extends Base{

    protected $table = 'bs_client_message';

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /*
     * 获取列表数据
     */
    public function getList($param = array()){
        $param['page_size'] = 10;
        $param['where']['is_del'] = 1;
        $count =  Db::table($this->table)->where($param['where'])->count();
        $Page = new Page($count,$param['page_size'],$param['p']);
        $page_show = $Page->show();
        $list = Db::table($this->table)
            ->field('id msg_id,type app_id,title,content,add_time')
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
            ->field('id msg_id,title,content,add_time,status')
            ->find();
        return $row;
    }

}