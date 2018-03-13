<?php
define('SUCCESS_INFO','操作成功');
define('ERROR_INFO','网络异常，请稍后再试');
define('PARAM_INFO','参数不完整');
return [
    // 默认控制器名
    'default_controller'     => 'Loanctr',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    'key' => 'BS@lebaobei',

    'message' => [
        [
            'app_id'    => 1,
            'app_name'  => '客户逾期报警',
            'img'       => '/public/upload/message/yq.png'
        ],
        [
            'app_id'    => 2,
            'app_name'  => '还款到期通知',
            'img'       => '/public/upload/message/dq.png'
        ],
        [
            'app_id'    => 3,
            'app_name'  => '提款审批通知',
            'img'       => '/public/upload/message/tksp.png'
        ],
        [
            'app_id'    => 4,
            'app_name'  => '额度授信通知',
            'img'       => '/public/upload/message/ed.png'
        ],
        [
            'app_id'    => 5,
            'app_name'  => '抵押品出库通知',
            'img'       => '/public/upload/message/ck.png'
        ],
        [
            'app_id'    => 6,
            'app_name'  => '提款结清通知',
            'img'       => '/public/upload/message/jq.png'
        ],
        [
            'app_id'    => 7,
            'app_name'  => '设备异常',
            'img'       => '/public/upload/message/yc.png'
        ]
    ]
];