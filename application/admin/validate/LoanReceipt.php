<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class LoanReceipt extends Validate
{

    // 验证规则
    protected $rule = [
        //'bank_name'             => 'checkBank',
        'account'               => 'require',
        'loan_amount'           => 'require',
        'str_time'              => 'require',
        'stp_time'              => 'require',
    ];
    //错误信息
    protected $message  = [
        'account.require'       => '收款账号不能为空',
        'loan_amount.require'   => '放款金额不能为空',
        'str_time.require'      => '贷款开始时间不能为空',
        'stp_time.require'      => '贷款结束时间不能为空',
    ];

    protected function checkBank($value){
        if(empty($value)){
            return '请选择银行';
        }
        return true;
    }

}