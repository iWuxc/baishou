<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/13
 * Time: 20:09
 */

namespace app\admin\logic;
use app\admin\model\UserCredit;
use think\Db;
use think\Model;

class UserCreditLogic extends Model{

    private $model;

    function __construct($data = []){
        parent::__construct($data);
        $this->model = new UserCredit();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        if(!empty($request['user_id'])){
            $param['where']['uc.user_id'] = $request['user_id'];
        }
        $param['parameter'] = $request;
        $param['order'] = 'uc.id desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['grand_count'] = Db::name('loan')->where('user_id',$val['user_id'])->count() ? : 0;
            }
        }
        return $list;
    }

    /**
     * 单条数据
     * @param array $request
     * @return array|false|\PDOStatement|string|Model
     */
    public function findRow($request = array()){
        $param['where']['id'] = $request['id'];
        $row = $this->model->findRow($param);
        return $row;
    }


}