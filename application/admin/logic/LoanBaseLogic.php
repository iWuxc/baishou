<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 11:09
 */

namespace app\admin\logic;
use think\Db;
use think\Model;

class LoanBaseLogic extends Model{

    function __construct($data = []){
        parent::__construct($data);
    }

    /**
     * 贷款状态
     * @param $status
     * @return string
     */
    public function getStatusName($status){
        switch ($status){
            case 4 : return '否决';break;
            case 5 : return '续议';break;
            case 1 : return '已审核';break;
            case 3 : return '待审核';break;
            case 2 : return '已还完'; break;
            case 9 : return '还款逾期'; break;
            default : return''; break;
        }
    }

    /**
     * 云信放款状态
     * @param $status
     * @return string
     */
    public function getYxStatusName($status){
        switch ($status){
            case 1 : return '放款成功';break;
            case 2 : return '放款失败';break;
            case 3 : return '取消贷款';break;
            case 0 : return '未放款';break;
            default : return''; break;
        }
    }

    /**
     * 还款状态
     * @param $status
     * @return string
     */
    public function getRStatusName($status){
        switch ($status){
            case 1 : return '已还款';break;
            case 2 : return '未还款';break;
            case 3 : return '已逾期';break;
            default : return'逾期还款'; break;
        }
    }


}