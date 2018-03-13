<?php
namespace app\bossapi\validate;
use think\Validate;
use think\Db;
class CreditConclusion extends Validate
{

    // 验证规则
    protected $rule = [
        'user_name'             => 'require',
        'credit'                => 'require',
        'str_time'              => 'require',
        'stp_time'              => 'require',
        'price_rate'            => 'require',
        'service_rate'          => 'require',
        'inter_rate'            => 'require',
    ];
    //错误信息
    protected $message  = [
        'user_name.require'     => '授信申请人不能为空',
        'credit.require'        => '授信额度不能为空',
        'str_time.require'      => '授信时间不能为空',
        'stp_time.require'      => '授信时间不能为空',
        'price_rate.require'    => '服务率不能为空',
        'service_rate.require'  => '服务率不能为空',
        'inter_rate.require'    => '利率不能为空',
    ];


}