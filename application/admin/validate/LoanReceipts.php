<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class LoanReceipts extends Validate
{

    // 验证规则
    protected $rule = [
        'user_name'               => 'require',
        'loan_amount'           => 'require',
        'str_time'              => 'require',
        'stp_time'              => 'require',
    ];
    //错误信息
    protected $message  = [
        'user_name.require'       => '客户名称不能为空',
        'loan_amount.require'   => '放款金额不能为空',
        'str_time.require'      => '贷款开始时间不能为空',
        'stp_time.require'      => '贷款结束时间不能为空',
    ];


}