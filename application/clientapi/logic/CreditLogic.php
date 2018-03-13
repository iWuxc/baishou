<?php

/**
 * 首页额度信息
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\logic;

use think\Db;
use think\Loader;

class CreditLogic extends BaseLogic{

    public function __construct(array $data = [])
    {
        parent::__construct($data);
    }

    /**
     * 提款基本信息(首页)
     * @param $userid
     * @return array|bool|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCreditInfo($userid){
        $credit = Db::name('user_credit') ->where('user_id',$userid)->field('credit,credit_rest,check_rate,assess_total')->find();
        if(!empty($credit)){
            $credit['max_amount'] = $credit['assess_total'] * ($credit['check_rate'] / 100); //提款限额
            number_format($credit['max_amount'], 2, ".", "");
            number_format($credit['credit_rest'], 2, ".", "");
            number_format($credit['assess_total'], 2, ".", "");
            //申请提款页面的数据  (额度期限,利率,收款账号)
            $creditInfo = Db::name('credit_conclusion') -> field("term,inter_rate") -> where('user_id',$userid) -> find();
            if(!empty($creditInfo)){
                $credit['term'] = $creditInfo['term'];
                $credit['inter_rate'] = $creditInfo['inter_rate'];
            }
            $credit['creditcard'] = Db::name('users') -> where('user_id',$userid) -> value('creditcard');
            return $credit;
        }
        return false;
    }

    /**
     * 获取客户额度信息
     * @param $userid
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function creditDetail($userid){
        $info = Db::name('credit_conclusion')
            ->alias('cc')
            ->join('__USER_CREDIT__ uc','cc.user_id = uc.user_id','LEFT')
            ->field('uc.credit,uc.credit_rest,cc.term,cc.is_loop,cc.draw_strtime,cc.draw_stptime,cc.inter_rate,cc.store_sum,
                    uc.assess_total,uc.check_rate,uc.now_rate')
            ->where('cc.user_id',$userid)
            ->find();
        if(!empty($info)){
            $info['draw_strtime'] = date('Y-m-d',$info['draw_strtime']);
            $info['draw_stptime'] = date('Y-m-d',$info['draw_stptime']);
            $info['max_amount'] = $info['credit_rest']; //提款限额
            return $info;
        }else{
            return [];
        }
    }
}

