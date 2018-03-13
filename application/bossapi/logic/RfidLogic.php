<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Rfid;
use think\Db;

class RfidLogic extends BaseLogic {

    protected $model;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Rfid();
    }

    /**
     * 数据列表
     * @param array $request
     * @param $type
     * @return mixed
     */
    public function getList($request = array(),$type){
        check_param('mon_id');
        $param['where']['em_id'] = $request['mon_id'];
        $param['where']['type'] = $type;
        $param['parameter'] = $request;
        $param['order'] = 'time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            $this->model->where('em_id',$request['mon_id'])->update(['status'=>1]);
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        return $list['list'];
    }

}