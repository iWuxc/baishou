<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 11:09
 */

namespace app\admin\logic;
use app\admin\model\Bank;
use think\Db;
use think\Model;

class BankLogic extends Model{

    private $model;

    function __construct($data = []){
        parent::__construct($data);
        $this->model = new Bank();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        if(!empty($request['admin_id'])){
            $param['where']['admin_id'] = $request['admin_id'];
        }
        $param['parameter'] = $request;
        $param['order'] = 'add_time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        return $list;
    }

}