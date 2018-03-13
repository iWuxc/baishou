<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class Credit extends Validate
{
    // 验证规则
    protected $rule = [
        'credit'                => 'require',
        'check_rate'            => 'require',
        'term'                  => 'require',
        'is_loop'               => 'require',
        'draw_strtime'          => 'require',
        'draw_stptime'          => 'require',
        'service_rate'          => 'require',
        'inter_rate'            => 'require',
        'price_rate'            => 'require',
    ];
    //错误信息
    protected $message  = [
        'credit'                => '授信额度必填',
        'check_rate'            => '审批抵押率必填',
        'term'                  => '额度期限必填',
        'is_loop'               => '是否循环使用必填',
        'draw_strtime'          => '单笔提款期限日期必填',
        'draw_stptime'          => '单笔提款期限结束时间必填',
        'service_rate'          => '服务费率必填',
        'inter_rate'            => '利率必填',
        'price_rate'            => '价格必填',
    ];
}