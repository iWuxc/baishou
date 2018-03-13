<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Loan;
use think\Db;

class LoanLogic extends BaseLogic {

    protected $model;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Loan();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        check_param('admin_id,role_id');
        $param['where']['l.is_check'] = ['in','1,2,9'];
        if($request['role_id'] == 4){
            $param['where']['u.xd_id'] = $request['admin_id'];
        }
        $param['parameter'] = $request;
        $param['order'] = 'l.is_check desc,l.addtime desc';
        $list = $this->model->getList($param);
        //dump($this->model->getLastSql());die;
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['stp_time'] = date('Y-m-d',$val['stp_time']);
                //授信额度期限
                $list['list'][$key]['term'] = Db::name('credit_conclusion')->where('user_id',$val['user_id'])->value('term') ? : '';
                $list['list'][$key]['check'] = $val['is_check'];
                unset($list['list'][$key]['is_check']);
            }
        }
        return $list['list'];
    }

    /**
     * 单条数据
     * @param array $request
     * @return array|false|\PDOStatement|string|Model
     */
    public function findRow($request = array()){
        $param['where']['id'] = $request['id'];
        $row = $this->model->findRow($param);
        $row['str_time'] = date('Y-m-d',$row['str_time']);
        $row['stp_time'] = date('Y-m-d',$row['stp_time']);
        return $row;
    }


}