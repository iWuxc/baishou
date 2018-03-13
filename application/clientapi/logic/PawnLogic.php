<?php

/**
 * 抵押品管理
 * Author: iWuxc
 * time 2017-12-25
 */
namespace app\clientapi\logic;

use think\Db;
use think\Exception;
use think\Loader;
use app\clientapi\model\Pawn;

class PawnLogic extends BaseLogic {

    protected $model;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this -> model = new Pawn();
    }

    /*
     * 抵押品列表
     */
    public function getPawnList($userid, $type, $status = '', $p=1){
        $where['param']['status'] = array('EGT',1);
        $where['order'] = "addtime desc";
        $where['p'] = $p;
        if(!empty($status) && $status == 1){
            $where['param']['status'] = intval($status);
        }
        if($type == 1){//boss登录
            $where['param']['user_id'] = $userid;
            $result = $this -> model -> getPawnList($where);
        }else{//员工登录
            //先查出关联的门店ID
            $store_ids = Db::name('user_store_clerk') -> where('clerk_id',$userid) -> value('belong_to_store');
            if(empty($store_ids)){
                toJson('0', '该店员没有归属门店');
            }
            $where['param']['store_id'] = ['in',$store_ids];
            $result = $this -> model -> getPawnList($where);
        }
        return $result;
    }


    /*
     * 抵押品详情
     */
    public function detail($pawn_id){
        $data = $this -> model -> detail($pawn_id);
        if($data){
            //查询抵押率
            $credit = Db::name('user_credit') -> field('check_rate,now_rate') -> where('user_id',$data['user_id']) -> find();
            $data['check_rate'] = $credit['check_rate'];
            $data['now_rate'] = $credit['now_rate'];
            $imgs = Db::name('pawn_imgs') -> field('img_url') -> where(array('pawn_id'=>$data['pawn_id'],'pawn_type'=>1)) -> limit(5) -> select();
            empty($imgs) && $data['imgs'] = [];
            foreach ($imgs as $key=>$val){
                $data['imgs'][] = get_url() . $val['img_url'];
            }
            return $data;
        }
        return [];
    }

    /*
     * 组件家具列表
     */
    public function getPawnOneList($pawn_id){
        $data = Db::name('pawn_one') -> field('one_id,pawn_name') -> where('pawn_id',$pawn_id) -> select();
        return $data;
    }

    /*
     * 组件家具详情
     */
    public function pawnOneDetail($pawn_id, $one_id){
        $data = Db::name('pawn_one') -> field('pawn_name,pawn_rfid') -> where('one_id',$one_id) -> find();
        if($data){
            $imgs = Db::name('pawn_imgs') -> field('img_url') -> where(array('pawn_id'=>$pawn_id,'one_id'=>$one_id)) -> limit(5) -> select();
            empty($imgs) && $data['imgs'] = [];
            foreach ($imgs as $key=>$val){
                $data['imgs'][] = get_url() . $val['img_url'];
            }
            return $data;
        }
        return [];
    }

    /*
     * 搜索
     */
    public function search($userid, $type, $keyword, $p){
        if($type == 1){//boss登录
            $result = $this -> model -> search($userid, null, $keyword, $p);
        }else{//员工登录
            //先查出关联的门店ID
            $store_ids = Db::name('user_store_clerk') -> where('clerk_id',$userid) -> value('belong_to_store');
            if(empty($store_ids)){
                toJson('0', '该店员没有归属门店', (object)array());
            }
            $result = $this -> model -> search(null, $store_ids, $keyword, $p);
        }
        return $result;
    }

    /*
     * 解压
     */
    public function unpack($userid, $request=array()){
        //验证数据的完整性
        $validate = Loader::validate('Unpack');
        $data = $request;
        if(!$validate->batch()->check($request)){
            $error = $validate->getError();
            $error_msg = array_values($error);
            toJson("0",$error_msg[0]);
        }
        //判断是否已经解压过
        $is_unpack = Db::name('pawn_applygreen')->where(array('pawn_id'=>$data['pawn_id'],'status'=>4))->value('pawn_id');
        if($is_unpack){
            toJson('0', '该家具已出库,禁止再提交!');
        }

        //判断出库限制
        if(get_type() == 2){
            $boss_id = $this -> getBossId($userid);
        }
        $check_rate = isset($boss_id) ? $this->is_check_rate($boss_id) : $this -> is_check_rate($userid);
        if($check_rate == false){
            toJson('0', '当前抵押率已大于等于审批抵押率,禁止提交,您可以选择抵押品出库进行销售还款或增加其他抵押品');
        }
        /** 消息推送用到  */
        $pawnInfo = $this -> getPawnInfo($data['pawn_id']);
        $userInfo = $this -> getUserInfo($userid);

        $data['arrive_time'] = strtotime($request['arrive_time']);
        $data['add_time'] = time();
        //判断提交的来源, 如果为boss, 需要判断是否进入人工审核
        $error = 0;
        if(get_type() == 1){
            $data['sub_id'] = $userid;
            $data['sub_name'] = M('users') -> where(['user_id'=>$userid]) -> value('name');
            $data['user_id'] = $userid;
            $data['submit_type'] = 1;
            $status = $this -> deal_outbound($userid, $data['pawn_id']);
            //开启事务
            Db::transaction();
            if($status['res'] == 1){//走自动审核
                $data['status']  = 4; //直接通过
                try{
                    //1. 录入解压表申请信息
                    $r1 = Db::name('pawn_applygreen') -> insert($data);
                    !$r1 && $error = 1;

                    //2. 更改家具表的状态
                    $r2 = Db::name('pawn') -> where('pawn_id', $data['pawn_id']) -> update(array('status'=>5,'updtime'=>time()));
                    !$r2 && $error = 1;

                    //3. 更改评估总值和当前抵押率
                    $d_arr['assess_total'] = $status['assess_total'];
                    $d_arr['now_rate'] = $status['cur_rate'];
                    $d_arr['credit_used'] = $status['credit_used'];
                    $d_arr['credit_rest'] = $status['credit_rest'];
                    $d_arr['updtime'] = time();
                    $r3 = Db::name('user_credit') -> where('user_id',$userid) -> update($d_arr);
                    !$r3 && $error = 1;

                    //4. 记录平台日志
                    $r4 = $this -> pawnLog(2, $data['pawn_id'], '抵押品自动出库', "自动出库,评估总值变更为{$status['assess_total']}元,当前抵押率变更为{$status['cur_rate']}%,剩余可使用额度{$status['credit_rest']}元");
                    !$r4 && $error = 1;

                    //5. 消息列表 - 客户
                    $this -> _msg = array(
                        'type' => 5,
                        'user_id' => $userid,
                        'pawn_id' => $pawnInfo['pawn_id'],//更新消息用到
                        'title' => '申请通过',
                        'content' => array(
                            'pawn_id' => $pawnInfo['pawn_id'],
                            'pawn_name' => $pawnInfo['pawn_name'],
                            'pawn_no' => $pawnInfo['pawn_no'],
                            'pawn_img' => $pawnInfo['pawn_pic'],
                            'ckerk_name' => $data['sub_name'],
                            'remark' => '自动审核通过',
                            'status' => 4
                        ),
                    );
                    $r5 = $this -> insertPushMsg($this->_msg);
                    !$r5 && $error = 1;

                    //8. 消息列表(直接成功贷款) - 管理端 信贷
                    $xd_msg = array(
                        'admin_id'  => $userInfo['xd_id'],
                        'comm_id' => $pawnInfo['pawn_id'],
                        'title'     => '出库成功',
                        'type'      => 5,
                        'content'   => serialize(array(
                            "type" => 1,
                            "store_id" => $pawnInfo['store_id'],
                            "user_id" => $pawnInfo['user_id'],
                            "pawn_id" => $pawnInfo['pawn_id'],
                            "store_name" => $pawnInfo['store_name'],
                            "store_no" => $pawnInfo['store_no'],
                            "stp_time" => date('Y-m-d H:i:s'),
                            "pawn_name" => $pawnInfo['store_name'],
                        )),
                        'add_time'  => time()
                    );
                    $r8 = Db::name('message')->insert($xd_msg);
                    !$r8 && $error = 1;

                    Db::commit();

                    //6. 推送消息 - 客户端
                    $pushMsg = array(
                        'name' => $data['sub_name'],
                        'date' => date('m月d日'), //同一天发送
                        'new_value' => $pawnInfo['new_value'],
                    );
                    PushMessage(4, $userInfo['cid'], '出库成功', $pushMsg);

                    //7. 发送短信 - 客户
                    $sms_msg = array(
                        'name' => $data['sub_name'],
                        'date' => date('m月d日'),
                        'new_value' => $pawnInfo['new_value'],
                    );
                    sendSms(5, $userInfo['mobile'], $sms_msg);

                    //9.推送消息 - 信贷
                    $pushMsg_xd = array(
                        'name' => $pawnInfo['user_name'],
                        'date' => date('m月d日'),
                        'new_value' => $pawnInfo['new_value'],
                    );
                    $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id', $userInfo['xd_id']) -> find();
                    PushMessage(5, $xd_info['cid'], '出库成功', $pushMsg_xd, 'boss');

                    //10.发短信 - 信贷
                    $xd_sms_msg = array(
                        'name' => $pawnInfo['user_name'],
                        'date' => date('m月d日'),
                        'new_value' => $pawnInfo['new_value']
                    );
                    sendSms(6, $xd_info['mobile'], $xd_sms_msg);

                    //给所有风控推送
                    $fk_infos = Db::name('admin') -> field('admin_id,cid,user_name,mobile') -> where('role_id=2') -> select();
                    foreach($fk_infos as $k => $v){
                        //站内信
                        $fk_msg = array(
                            'admin_id'  => $v['admin_id'],
                            'comm_id' => $pawnInfo['pawn_id'],
                            'title'     => '出库成功',
                            'type'      => 5,
                            'content'   => serialize(array(
                                "type" => 1,
                                "store_id" => $pawnInfo['store_id'],
                                "user_id" => $pawnInfo['user_id'],
                                "pawn_id" => $pawnInfo['pawn_id'],
                                "store_name" => $pawnInfo['store_name'],
                                "store_no" => $pawnInfo['store_no'],
                                "stp_time" => date('Y-m-d H:i:s'),
                                "pawn_name" => $pawnInfo['store_name'],
                            )),
                            'add_time'  => time()
                        );
                        Db::name('message')->insert($fk_msg);

                        //推送
                        $pushMsg_fk = array(
                            'name' => $pawnInfo['user_name'],
                            'date' => date('m月d日'),
                            'new_value' => $pawnInfo['new_value'],
                        );
                        PushMessage(5, $v['cid'], '出库成功', $pushMsg_fk, 'boss');

                        //发短信
                        $fk_sms_msg = array(
                            'name' => $pawnInfo['user_name'],
                            'date' => date('m月d日'),
                            'new_value' => $pawnInfo['new_value']
                        );
                        sendSms(6, $v['mobile'], $fk_sms_msg);
                    }

                    if($error == 0){
                        if($data['is_collateral'] == 1){
                            tojson('1', '自动审核成功',array('type'=>1));//选择增加抵押品
                        }
                        tojson('1', '自动审核成功',array('type'=>2));
                    }
                    tojson('0', '操作失败');

                } catch(Exception $e){
                    //回滚事务
                    Db::rollback();
                    toJson('0', '操作失败');
                }
            }elseif($status['res'] == 2){
                $data['status']  = 3; //人工审核(4为待审核阶段)
                //判断是否选择用于还款或者增加抵押品,否则禁止提交
                if($data['is_paydebt']==2 && $data['is_collateral']==2){
                    toJson('0', '当前抵押率大于等于审批抵押率');
                }
                try{
                    //1. 录入解压表申请信息
                    $r1 = Db::name('pawn_applygreen') -> insert($data);
                    !$r1 && $error = 1;

                    //2. 更改家具表的状态
                    $r2 = Db::name('pawn') -> where('pawn_id', $data['pawn_id']) -> update(array('status'=>2,'updtime'=>time()));
                    !$r2 && $error = 1;

                    //3. 记录平台日志
                    $r3 = $this -> pawnLog(2, $data['pawn_id'], '抵押品解压审核', '平台进入人工审核');
                    !$r3 && $error = 1;

                    //4. 推送消息-客户端
                    $this -> _msg = array(
                        'type' => 5,
                        'user_id' => $userid,
                        'pawn_id' => $pawnInfo['pawn_id'],//更新消息用到
                        'title' => '平台待审核',
                        'content' => array(
                            'pawn_id' => $pawnInfo['pawn_id'],
                            'pawn_name' => $pawnInfo['pawn_name'],
                            'pawn_no' => $pawnInfo['pawn_no'],
                            'pawn_img' => $pawnInfo['pawn_pic'],
                            'ckerk_name' => $data['sub_name'],
                            'remark' => '',
                            'status' => 3
                        ),
                    );
                    $r4 = $this -> insertPushMsg($this->_msg);
                    !$r4 && $error = 1;

                    //6. 消息列表-信贷
                    $xd_msg = array(
                        'admin_id'  => $userInfo['xd_id'],
                        'comm_id' => $pawnInfo['pawn_id'],
                        'title'     => '出库申请',
                        'type'      => 5,
                        'content'   => serialize(array(
                            "type" => 0,
                            "store_id" => $pawnInfo['store_id'],
                            "user_id" => $pawnInfo['user_id'],
                            "pawn_id" => $pawnInfo['pawn_id'],
                            "store_name" => $pawnInfo['store_name'],
                            "store_no" => $pawnInfo['store_no'],
                            "stp_time" => date('Y-m-d H:i:s'),
                            "pawn_name" => $pawnInfo['store_name'],
                        )),
                        'add_time'  => time()
                    );
                    $r5 = Db::name('message')->insert($xd_msg);
                    !$r5 && $error = 1;

                    //6 推送消息
                    $pushMsg_xd = array(
                        'name' => $pawnInfo['user_name'],
                    );
                    $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id', $userInfo['xd_id']) -> find();
                    PushMessage(13, $xd_info['cid'], '出库申请', $pushMsg_xd, 'boss');

                    //6. 发送短信
                    $xd_sms_msg = array(
                        'name' => $pawnInfo['user_name'],
                    );
                    sendSms(13, $xd_info['mobile'], $xd_sms_msg);

                    //给所有风控推送
                    $fk_infos = Db::name('admin') -> field('admin_id,cid,user_name,mobile') -> where('role_id=2') -> select();
                    foreach($fk_infos as $k => $v){
                        //站内信
                        $fk_msg = array(
                            'admin_id'  => $v['admin_id'],
                            'comm_id' => $pawnInfo['pawn_id'],
                            'title'     => '出库申请',
                            'type'      => 5,
                            'content'   => serialize(array(
                                "type" => 0,
                                "store_id" => $pawnInfo['store_id'],
                                "user_id" => $pawnInfo['user_id'],
                                "pawn_id" => $pawnInfo['pawn_id'],
                                "store_name" => $pawnInfo['store_name'],
                                "store_no" => $pawnInfo['store_no'],
                                "stp_time" => date('Y-m-d H:i:s'),
                                "pawn_name" => $pawnInfo['store_name'],
                            )),
                            'add_time'  => time()
                        );
                        Db::name('message')->insert($fk_msg);

                        //推送
                        $pushMsg_fk = array(
                            'name' => $pawnInfo['user_name'],
                        );
                        PushMessage(13, $v['cid'], '出库成功', $pushMsg_fk, 'boss');

                        //发短信
                        $fk_sms_msg = array(
                            'name' => $pawnInfo['user_name'],
                        );
                        sendSms(13, $v['mobile'], $fk_sms_msg);
                    }
                    Db::commit();
                    if($error == 0){
                        if($data['is_collateral'] == 1){
                            tojson('1', '已成功提交审核',array('type'=>1));//选择增加抵押品
                        }
                        tojson('1', '已成功提交审核',array('type'=>2));
                    }
                    tojson('0', '操作失败');
                } catch(Exception $e){
                    //回滚事务
                    Db::rollback();
                    toJson('0', '操作失败');
                }
            }else{
                toJson('0', '该用户信用值有错误');
            }
        }else {//员工提交
            $clerkInfo = M('user_store_clerk')->field('clerk_name,user_id,double_check')->where(['clerk_id' => $userid])->find();
            $data['sub_id'] = $userid;
            $data['sub_name'] = $clerkInfo['clerk_name'];
            $data['user_id'] = $clerkInfo['user_id'];
            $data['submit_type'] = 2;
            //判断是否有解压权限
            if ($clerkInfo['double_check'] == 1) {//有解压复核权限
                //直接走平台审核判断
                $status = $this->deal_outbound($clerkInfo['user_id'], $data['pawn_id']);
                if ($status['res'] == 1) {//走自动审核
                    $data['status'] = 4; //直接通过
                    try {
                        //1. 录入解压表申请信息
                        $r1 = Db::name('pawn_applygreen')->insert($data);
                        !$r1 && $error = 1;

                        //2. 更改家具表的状态
                        $r2 = Db::name('pawn')->where('pawn_id', $data['pawn_id'])->update(array('status' => 5, 'updtime' => time()));
                        !$r2 && $error = 1;

                        //3. 更改评估总值和当前抵押率
                        $d_arr['assess_total'] = $status['assess_total'];
                        $d_arr['now_rate'] = $status['cur_rate'];
                        $d_arr['credit_used'] = $status['credit_used'];
                        $d_arr['credit_rest'] = $status['credit_rest'];
                        $d_arr['updtime'] = time();
                        $r3 = Db::name('user_credit')->where('user_id', $clerkInfo['user_id'])->update($d_arr);
                        !$r3 && $error = 1;

                        //4. 记录平台日志
                        $r4 = $this->pawnLog(2, $data['pawn_id'], '抵押品解压审核', "自动出库,评估总值变更为{$status['assess_total']}元,当前抵押率变更为{$status['cur_rate']}%,剩余可使用额度{$status['credit_rest']}元");
                        !$r4 && $error = 1;

                        //5. 消息列表 - 客户
                        $this->_msg = array(
                            'type' => 5,
                            'user_id' => $userid,
                            'pawn_id' => $pawnInfo['pawn_id'],//更新消息用到
                            'title' => '申请通过',
                            'content' => array(
                                'pawn_id' => $pawnInfo['pawn_id'],
                                'pawn_name' => $pawnInfo['pawn_name'],
                                'pawn_no' => $pawnInfo['pawn_no'],
                                'pawn_img' => $pawnInfo['pawn_pic'],
                                'ckerk_name' => $data['sub_name'],
                                'remark' => '自动审核通过',
                                'status' => 4
                            ),
                        );
                        $r5 = $this->insertPushMsg($this->_msg);
                        !$r5 && $error = 1;

                        //8. 消息列表(直接成功贷款) - 管理端 信贷
                        $xd_msg = array(
                            'admin_id' => $userInfo['xd_id'],
                            'comm_id' => $pawnInfo['pawn_id'],
                            'title' => '出库成功',
                            'type' => 5,
                            'content' => serialize(array(
                                "type" => 1,
                                "store_id" => $pawnInfo['store_id'],
                                "user_id" => $pawnInfo['user_id'],
                                "pawn_id" => $pawnInfo['pawn_id'],
                                "store_name" => $pawnInfo['store_name'],
                                "store_no" => $pawnInfo['store_no'],
                                "stp_time" => date('Y-m-d H:i:s'),
                                "pawn_name" => $pawnInfo['store_name'],
                            )),
                            'add_time' => time()
                        );
                        $r8 = Db::name('message')->insert($xd_msg);
                        !$r8 && $error = 1;

                        Db::commit();

                        //6. 推送消息 - 客户端
                        $pushMsg = array(
                            'name' => $data['sub_name'],
                            'date' => date('m月d日'), //同一天发送
                            'pawn_no' => $pawnInfo['pawn_no'],
                        );
                        PushMessage(4, $userInfo['cid'], '出库成功', $pushMsg);

                        //7. 发送短信 - 客户
                        $sms_msg = array(
                            'name' => $data['sub_name'],
                            'date' => date('m月d日'),
                            'new_value' => $pawnInfo['new_value'],
                        );
                        sendSms(5, $userInfo['mobile'], $sms_msg);

                        //9.推送消息 - 信贷
                        $pushMsg_xd = array(
                            'name' => $pawnInfo['user_name'],
                            'date' => date('m月d日'),
                            'new_value' => $pawnInfo['new_value'],
                        );
                        $xd_info = Db::name('admin')->field('cid,mobile')->where('admin_id', $userInfo['xd_id'])->find();
                        PushMessage(5, $xd_info['cid'], '出库成功', $pushMsg_xd, 'boss');

                        //10.发短信 - 信贷
                        $xd_sms_msg = array(
                            'name' => $pawnInfo['user_name'],
                            'date' => date('m月d日'),
                            'new_value' => $pawnInfo['new_value']
                        );
                        sendSms(6, $xd_info['mobile'], $xd_sms_msg);

                        //给所有风控推送
                        $fk_infos = Db::name('admin')->field('admin_id,cid,user_name,mobile')->where('role_id=2')->select();
                        foreach ($fk_infos as $k => $v) {
                            //站内信
                            $fk_msg = array(
                                'admin_id' => $v['admin_id'],
                                'comm_id' => $pawnInfo['pawn_id'],
                                'title' => '出库成功',
                                'type' => 5,
                                'content' => serialize(array(
                                    "type" => 1,
                                    "store_id" => $pawnInfo['store_id'],
                                    "user_id" => $pawnInfo['user_id'],
                                    "pawn_id" => $pawnInfo['pawn_id'],
                                    "store_name" => $pawnInfo['store_name'],
                                    "store_no" => $pawnInfo['store_no'],
                                    "stp_time" => date('Y-m-d H:i:s'),
                                    "pawn_name" => $pawnInfo['store_name'],
                                )),
                                'add_time' => time()
                            );
                            Db::name('message')->insert($fk_msg);

                            //推送
                            $pushMsg_fk = array(
                                'name' => $pawnInfo['user_name'],
                                'date' => date('m月d日'),
                                'new_value' => $pawnInfo['new_value'],
                            );
                            PushMessage(5, $v['cid'], '出库成功', $pushMsg_fk, 'boss');

                            //发短信
                            $fk_sms_msg = array(
                                'name' => $pawnInfo['user_name'],
                                'date' => date('m月d日'),
                                'new_value' => $pawnInfo['new_value']
                            );
                            sendSms(6, $v['mobile'], $fk_sms_msg);
                        }

                        if ($error == 0) {
                            if ($data['is_collateral'] == 1) {
                                tojson('1', '自动审核成功', array('type' => 1));//选择增加抵押品
                            }
                            tojson('1', '自动审核成功', array('type' => 2));
                        }
                        tojson('0', '操作失败');

                    } catch (Exception $e) {
                        //回滚事务
                        Db::rollback();
                        toJson('0', '操作失败');
                    }
                } elseif ($status['res'] == 2) {
                    $data['status'] = 3; //人工审核(4为待审核阶段)
                    //判断是否选择用于还款或者增加抵押品,否则禁止提交
                    if ($data['is_paydebt'] == 2 && $data['is_collateral'] == 2) {
                        toJson('0', '提款受限,用于还款和增加抵押品至少选择一项');
                    }
                    try {
                        //1. 录入解压表申请信息
                        $r1 = Db::name('pawn_applygreen')->insert($data);
                        !$r1 && $error = 1;

                        //2. 更改家具表的状态
                        $r2 = Db::name('pawn')->where('pawn_id', $data['pawn_id'])->update(array('status' => 2, 'updtime' => time()));
                        !$r2 && $error = 1;

                        //3. 记录平台日志
                        $r3 = $this->pawnLog(2, $data['pawn_id'], '抵押品解压审核', '平台进入人工审核');
                        !$r3 && $error = 1;

                        //4. 推送消息-客户端
                        $this->_msg = array(
                            'type' => 5,
                            'user_id' => $userid,
                            'pawn_id' => $pawnInfo['pawn_id'],//更新消息用到
                            'title' => '平台待审核',
                            'content' => array(
                                'pawn_id' => $pawnInfo['pawn_id'],
                                'pawn_name' => $pawnInfo['pawn_name'],
                                'pawn_no' => $pawnInfo['pawn_no'],
                                'pawn_img' => $pawnInfo['pawn_pic'],
                                'ckerk_name' => $data['sub_name'],
                                'remark' => '',
                            ),
                        );
                        $r4 = $this->insertPushMsg($this->_msg);
                        !$r4 && $error = 1;

                        //6. 消息列表-信贷
                        $xd_msg = array(
                            'admin_id' => $userInfo['xd_id'],
                            'comm_id' => $pawnInfo['pawn_id'],
                            'title' => '出库申请',
                            'type' => 5,
                            'content' => serialize(array(
                                "type" => 0,
                                "store_id" => $pawnInfo['store_id'],
                                "user_id" => $pawnInfo['user_id'],
                                "pawn_id" => $pawnInfo['pawn_id'],
                                "store_name" => $pawnInfo['store_name'],
                                "store_no" => $pawnInfo['store_no'],
                                "stp_time" => date('Y-m-d H:i:s'),
                                "pawn_name" => $pawnInfo['store_name'],
                            )),
                            'add_time' => time()
                        );
                        $r5 = Db::name('message')->insert($xd_msg);
                        !$r5 && $error = 1;

                        //6 推送消息
                        $pushMsg_xd = array(
                            'name' => $pawnInfo['user_name'],
                        );
                        $xd_info = Db::name('admin')->field('cid,mobile')->where('admin_id', $userInfo['xd_id'])->find();
                        PushMessage(13, $xd_info['cid'], '出库申请', $pushMsg_xd, 'boss');

                        //6. 发送短信
                        $xd_sms_msg = array(
                            'name' => $pawnInfo['user_name'],
                        );
                        sendSms(13, $xd_info['mobile'], $xd_sms_msg);

                        //给所有风控推送
                        $fk_infos = Db::name('admin')->field('admin_id,cid,user_name,mobile')->where('role_id=2')->select();
                        foreach ($fk_infos as $k => $v) {
                            //站内信
                            $fk_msg = array(
                                'admin_id' => $v['admin_id'],
                                'comm_id' => $pawnInfo['pawn_id'],
                                'title' => '出库申请',
                                'type' => 5,
                                'content' => serialize(array(
                                    "type" => 0,
                                    "store_id" => $pawnInfo['store_id'],
                                    "user_id" => $pawnInfo['user_id'],
                                    "pawn_id" => $pawnInfo['pawn_id'],
                                    "store_name" => $pawnInfo['store_name'],
                                    "store_no" => $pawnInfo['store_no'],
                                    "stp_time" => date('Y-m-d H:i:s'),
                                    "pawn_name" => $pawnInfo['store_name'],
                                )),
                                'add_time' => time()
                            );
                            Db::name('message')->insert($fk_msg);

                            //推送
                            $pushMsg_fk = array(
                                'name' => $pawnInfo['user_name'],
                            );
                            PushMessage(13, $v['cid'], '出库成功', $pushMsg_fk, 'boss');

                            //发短信
                            $fk_sms_msg = array(
                                'name' => $pawnInfo['user_name'],
                            );
                            sendSms(13, $v['mobile'], $fk_sms_msg);
                        }
                        Db::commit();
                        if ($error == 0) {
                            if ($data['is_collateral'] == 1) {
                                tojson('1', '已成功提交审核', array('type' => 1));//选择增加抵押品
                            }
                            tojson('1', '已成功提交审核', array('type' => 2));
                        }
                        tojson('0', '操作失败');
                    } catch (Exception $e) {
                        //回滚事务
                        Db::rollback();
                        toJson('0', '操作失败');
                    }
                } else {
                    toJson('0', '该用户信用值有错误');
                }
            }
            //无解压权限只能走内部审核
            try {
                $data['status'] = 1;

                //插入数据
                $r1 = Db::name('pawn_applygreen')->insert($data);
                !$r1 && $error = 1;

                $this->_msg = array(
                    'type' => 5,
                    'user_id' => $clerkInfo['user_id'],
                    'pawn_id' => $pawnInfo['pawn_id'],//更新消息用到
                    'title' => '内部待审批',
                    'content' => array(
                        'pawn_id' => $data['pawn_id'],
                        'pawn_name' => $pawnInfo['pawn_name'],
                        'pawn_no' => $pawnInfo['pawn_no'],
                        'pawn_img' => $pawnInfo['pawn_pic'],
                        'ckerk_name' => $data['sub_name'],
                        'remark' => '',
                        'status' => 1,
                    ),
                );
                $r2 = $this->insertPushMsg($this->_msg);
                !$r2 && $error = 1;

                //3. 推送消息 - 客户端-员工
                $pushMsg = array(
                    'name' => $data['sub_name'],
                    'date' => date('m月d日'), //同一天发送
                );
                PushMessage(8, $clerkInfo['cid'], '出库申请', $pushMsg);

                $u_info = Db::name('users')->field('cid,mobile')->where('user_id', $clerkInfo['user_id'])->find();
                pushMessageToSingle(array(//推给老板
                    'cid' => $u_info['cid'],
                    'title' => '出库申请',
                    'body' => '您有新的抵押品出库信息，请及时查看',
                    'transmissionContent' => json_encode(array(
                        'title' => '出库申请',
                        'body' => '您有新的抵押品出库信息，请及时查看',
                    ), JSON_UNESCAPED_UNICODE),
                    'type' => 'client'
                ));

                send_sms($u_info['mobile'], '您有新的抵押品出库信息，请及时查看');//发给老板

                if ($data['is_collateral'] == 1) {
                    tojson('1', '操作成功', array('type' => 1));//选择增加抵押品
                }
                tojson('1', '操作成功', array('type' => 2));

            } catch (Exception $e) {
                //回滚事务
                Db::rollback();
                toJson('0', '操作失败');
            }
        }
    }

    /*
     * 当前抵押率判断
     */
    public function unpackLimit($userid){
        $user_type = get_type();
        if($user_type == 2){
            $userid = Db::name('user_store_clerk') -> where('clerk_id',$userid) -> value('user_id');
        }
        $rate_check = $this -> rate_check($userid);
        $rate_check ? toJson('1', '操作成功', array('status'=>1)) : toJson('1', '操作成功', array('status'=>2));
    }

    /*
     * 查找店员老板
     */
    public function getBossId($clerk_id){
        $user_id = Db::name('user_store_clerk') -> where('clerk_id',$clerk_id) -> value('user_id');
        return $user_id;
    }
}