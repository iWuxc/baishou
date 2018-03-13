<?php
namespace app\clientapi\validate;
use think\Validate;
class Loan extends Validate
{

    // 验证规则
    protected $rule = [
        'apply_amount'       => 'require',
        'str_time'           => 'require',
        'stp_time'           => 'require',
        'month_amount'       => 'require',
        'account'            => 'require',
        'uses'               => 'require|max:50',
    ];
    //错误信息
    protected $message  = [
        'apply_amount.require'     => '贷款金额不能为空',
        'str_time.require'         => '还款期限开始时间不能为空',
        'stp_time.require'         => '还款期限结束时间不能为空',
        'month_amount.require'     => '每月价格不能为空',
        'account.require'          => '提款账号不能为空',
        'uses.require'             => '用途不能为空',
        'uses.max'                 => '用途字符数不能超过50个',
    ];


}