<?php
$arr =	array(

    //-----------------系统---------------------
	'system'=>array('name'=>'系统','child'=>array(
        
        // array('name' => '设置','child' => array(
        //     array('name'=>'短信模板','act'=>'index','op'=>'SmsTemplate'),
        //     array('name'=>'网站设置','act'=>'index','op'=>'System'),

        // )),

        array('name' => '待办事项','child'=>array(
            array('name' => '待办列表', 'act'=>'welcome', 'op'=>'Admin'),
        )),

        array('name' => '权限','child'=>array(
            array('name' => '管理员列表', 'act'=>'index', 'op'=>'Admin'),
            array('name' => '角色管理', 'act'=>'role', 'op'=>'Admin'),
            array('name'=>  '权限列表','act'=>'right_list','op'=>'System'),
            array('name' => '管理员日志', 'act'=>'log', 'op'=>'Admin'),
        )),
						
	)),

    //-----------------用户管理---------------------
	'users'=>array('name'=>'用户管理','child'=>array(

        array('name' => '客户','child'=>array(
            array('name'=>'客户列表','act'=>'index','op'=>'Users'),
            array('name'=>'添加门店','act'=>'add_store','op'=>'Users'),
            array('name'=>'客户操作日志','act'=>'user_log','op'=>'Users'),
        )),

	)),


    //-----------------贷款管理---------------------
    'loan'=>array('name'=>'贷款管理','child'=>array(

        array('name' => '额度审批','child'=>array(
            array('name'=>'客户授信管理','act'=>'index','op'=>'Credit'),
            array('name'=>'审批复核列表','act'=>'examination_approval','op'=>'Credit'),
            array('name'=>'审批操作日志','act'=>'approval_log','op'=>'Credit'),
        )),

        array('name' => '贷款','child'=>array(
            array('name'=>'发放贷款','act'=>'receipts','op'=>'LoanReceipt'),
            array('name'=>'未放款','act'=>'index','op'=>'Loanctr'),
            array('name'=>'已放款','act'=>'okList','op'=>'Loanctr'),
            array('name' =>'待还款列表','act'=>'index','op'=>'LoanReceipt'),
            array('name' =>'已还款列表','act'=>'okList','op'=>'LoanReceipt'),
            //array('name' =>'还款记录','act'=>'index','op'=>'LoanRepay'),
            array('name' =>'操作日志','act'=>'index','op'=>'LoanLog'),
        )),
        array('name' => '授信额度','child'=>array(
            array('name'=>'个人授信列表','act'=>'index','op'=>'UserCredit'),
            //array('name'=>'已授信用户列表','act'=>'creditIndex','op'=>'Users'),
            array('name'=>'个人授信操作日志','act'=>'userCreditlog','op'=>'Loanctr'),
        )),
        array('name' => '银行卡','child'=>array(
            array('name'=>'银行卡列表','act'=>'index','op'=>'Bank'),
        )),
    )),


    //-----------------抵押品管理---------------------
	'furniture'=>array('name'=>'抵押品管理','child'=>array(
        array('name' => '抵押品','child'=>array(
                    array('name'=>'新增抵押品列表','act'=>'pawnlist','op'=>'Pawn'),
                    array('name'=>'抵押品列表','act'=>'greenlist','op'=>'Pawn'),
                    array('name' =>'抵押品操作日志','act'=>'pawn_log','op'=>'Pawn'),
            )),

        array('name' => '材料管理','child'=>array(
                    array('name' =>'家具材料表','act'=>'woodlist','op'=>'Pawn'),
            )),
    )),



    //-----------------监控管理---------------------
    'monitoring'=>array('name'=>'监控管理','child'=>array(
    	array('name' => '摄像头','child'=>array(
            array('name'=>'硬盘录像机(DVR)','act'=>'index','op'=>'Dvr'),
            array('name'=>'摄像头列表','act'=>'index','op'=>'Monitoring'),
            array('name'=>'摄像头故障列表','act'=>'bad_list','op'=>'Monitoring'),
            array('name'=>'故障修复记录','act'=>'recovery_list','op'=>'Monitoring'),
    		array('name'=>'操作记录','act'=>'log','op'=>'Monitoring'),
		)),
		array('name' => 'RF ID','child'=>array(
            array('name'=>'RFID故障列表','act'=>'bad_list','op'=>'RF'),
			array('name'=>'故障修复记录','act'=>'recovery_list','op'=>'RF'),
		)),
        array('name' => '巡店管理','child'=>array(
            array('name'=>'巡店员列表','act'=>'index','op'=>'StoreCheck'),
            array('name'=>'巡店记录','act'=>'log','op'=>'StoreCheck'),
            array('name'=>'临时任务','act'=>'task','op'=>'StoreCheck'),
        )),
	)),	

			
);


    // $admin_id = session('admin_id');
    // $role = M('admin')->where(array())->getField('role_id');
    // if(1 === $role){
    //  $arr['monitoring']['child'][0]['child'][1] = array('name'=>'操作记录','act'=>'log','op'=>'Monitoring');
    // }
    // 超超级管理员
	/*if(2 === session('admin_id')){
		$arr['monitoring']['child'][3] = 
        array('name' => '定时任务','child'=>array(
            array('name'=>'RFID读写器接受信号','act'=>'check','op'=>'Timedtask'),
            array('name'=>'RFID信号监测','act'=>'rfid','op'=>'Timedtask'),
            array('name'=>'监控摄像头网络监测','act'=>'log','op'=>'Timedtask'),
            array('name'=>'待还款/逾期提醒','act'=>'monitoring','op'=>'Timedtask'),
        ));
	}*/
	
	return $arr;