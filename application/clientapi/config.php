<?php
define('SUCCESS_INFO','操作成功');
define('ERROR_INFO','网络异常，请稍后再试');
define('PARAM_INFO','参数不完整');
$token_config = [
    // +----------------------------------------------------------------------
    // | token设置
    // +----------------------------------------------------------------------
	//加密字符串
	'key' => 'BS@lebaobei',

    // +----------------------------------------------------------------------
    // | 消息类型通知设置
    // +----------------------------------------------------------------------
    'message' => [
        [
            'app_id'    => 6,
            'app_name'  => '还款通知',
            'img'       => '/public/upload/message/tksp.png'
        ],
        [
            'app_id'    => 5,
            'app_name'  => '抵押品出库',
            'img'       => '/public/upload/message/ck.png'
        ],
        [
            'app_id'    => 3,
            'app_name'  => '提款通知',
            'img'       => '/public/upload/message/tk.png'
        ],
    ]
];

return $token_config;