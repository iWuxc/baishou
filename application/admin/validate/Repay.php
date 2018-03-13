<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class Repay extends Validate
{

    // 验证规则
    protected $rule = [
        'bank_name'             => 'checkBank',
        'actual_amount'         => 'require',
        'account'               => 'require',
        'actual_date'           => 'require',
    ];
    //错误信息
    protected $message  = [
        'actual_amount.require' => '还款金额不能为空',
        'account.require'       => '还款账号不能为空',
        'actual_date.require'   => '还款日期不能为空',
    ];

    protected function checkBank($value){
        if(empty($value)){
            return '请选择银行';
        }
        return true;
    }

}