<?php

/**
 * 消息管理
 * Author: iWuxc
 * time 2017-12-26
 */
namespace app\clientapi\logic;

use think\Db;
use app\clientapi\model\Message;
use think\config;

class MessageLogic extends BaseLogic{

    protected $model;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this -> model = new Message();
    }

    public function noticeList($request = array(), $userid){
        $array = array();
        $list = Config::get('message');
        //过滤消息类型
        foreach ($list as $k => $v) {
            if(get_type() == 2){
                if ($v['app_id'] == 5) {
                    $array[] = $v;
                    continue;
                }
            }
        }
        $list = get_type() == 2 ? $array : $list;
        foreach ($list as $key=>$val){
            $list[$key]['img'] = get_url().$val['img'];
            $row = $this->model->where(array('type'=>$val['app_id'],'user_id'=>$userid))->field('user_id,add_time,title,content,status,add_time')->order('add_time desc')->find();
            if($row){
                $list[$key]['value'] = $row['title'];
                $list[$key]['add_time'] = date('Y-m-d H:i:s',$row['add_time']);
                $list[$key]['status'] = $row['status'];//直接没有未读消息
            }else{
                $list[$key]['value'] = '';
                $list[$key]['status'] = 1;
                $list[$key]['add_time'] = '';
            }
            $list[$key]['count'] = $this->model->where(['type'=>$val['app_id'],'status'=>0,'user_id'=>$userid])->count();
        }
        return $list;
    }

    /**
     * 具体消息列表
     * @param $userid
     * @param array $request
     * @return mixed
     */
    public function getLists($userid, $request = array()){
        if(isset($request['user_type']) && get_type() == 2){
            $userid = Db::name('user_store_clerk') -> where('clerk_id',$userid) -> value('user_id');
        }
        $param['where']['user_id'] = $userid;
        $param['where']['type'] = $request['app_id'];
        $param['p'] = $request ? $request : 1;
        $param['order'] = 'add_time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            $this->model->where(['type'=>$request['app_id'],'status'=>0,'user_id'=>$userid])->update(['status'=>1]);
            foreach ($list['list'] as $key=>$val){
                $list['list'][$key]['content'] = unserialize($val['content']);
                $list['list'][$key]['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
            }
        }
        return $list['list'];
    }

    public function pawnInfo($pawn_id,$userid){
        if(get_type() == 2){
            $userid = DB::name('user_store_clerk') -> where('clerk_id',$userid) -> value('user_id');
        }
        $row = Db::name('pawn_applygreen') -> alias('pa')
            -> join('__PAWN__ p', 'pa.pawn_id=p.pawn_id')
            -> field('p.pawn_id,p.user_id,p.store_id,p.pawn_name,p.pawn_no,p.pawn_model,p.wood_name,p.pawn_area,p.wood_auxname,p.pawn_auxarea,p.pawn_num,p.new_value,p.pawn_cost,pa.sub_id,pa.sub_name,pa.remark')
            -> where(array('pa.pawn_id'=>$pawn_id,'pa.user_id'=>$userid))
            -> find();
        if(!empty($row)){
            //门店名称
            $row['store_name'] = Db::name('user_store') -> where('store_id',$row['store_id']) -> value('store_name');
            $rate = Db::name('user_credit') -> field('check_rate,now_rate') -> where('user_id',$row['user_id']) -> find();
            if($rate){
                $row['check_rate'] = $rate['check_rate'];
                $row['now_rate'] = $rate['now_rate'];
            }
            unset($row['store_id']);
            $row = filter_null($row);
            $imgs = Db::name('pawn_imgs') -> field('img_url') -> where(array('pawn_id'=>$row['pawn_id'],'pawn_type'=>1)) -> limit(5) -> select();
            empty($imgs) && $row['imgs'] = [];
            foreach ($imgs as $key=>$val){
                $row['imgs'][] = get_url() . $val['img_url'];
            }
        }
        return $row;
    }

    /*
     * 内部审批通过
     */
    public function agreed($userid, $pawn_id){
        $check_rate = $this -> is_check_rate($userid);
        if($check_rate == false){
            toJson('0', '当前抵押率已大于审批抵押率,禁止再提交出库');
        }
        $status = $this -> deal_outbound($userid, $pawn_id);

        /** 消息推送用到  */
        $pawnInfo = $this -> getPawnInfo($pawn_id);
        $subInfo = Db::name('pawn_applygreen')
            -> field('sub_id,sub_name')
            -> where(array('pawn_id'=>$pawn_id))
            -> order('add_time desc')
            -> find();//寻找提交人
        $userInfo = $this -> getUserInfo($subInfo['sub_id'], 2);

        //开启事务
        $error = 0;
        Db::transaction();
        if($status['res'] == 1){//自动解压
            try{
                //1. 直接更改为平台审核通过
                $r1 = Db::name('pawn_applygreen') -> where('pawn_id', $pawn_id) -> update(array('status'=>4,'update_time'=>time()));
                !$r1 && $error = 1;

                //2. 更改家具表的状态
                $r2 = Db::name('pawn') -> where('pawn_id', $pawn_id) -> update(array('status'=>5,'updtime'=>time()));
                !$r2 && $error = 1;

                //3. 更改评估总值
                $d_arr['assess_total'] = $status['assess_total'];
                $d_arr['now_rate'] = $status['cur_rate'];
                $d_arr['credit_used'] = $status['credit_used'];
                $d_arr['credit_rest'] = $status['credit_rest'];
                $d_arr['updtime'] = time();
                $r3 = Db::name('user_credit') -> where('user_id',$userid) -> update($d_arr);
                !$r3 && $error = 1;

                //4. 日志
                $r4 = $this -> pawnLog(2, $pawn_id, '抵押品出库', "自动出库,评估总值变更为{$status['assess_total']}元,当前抵押率变更为{$status['cur_rate']}%,剩余可使用额度{$status['credit_rest']}元");
                !$r4 && $error = 1;

                //5. 修改上一条状态
                $r5 = $res = Db::name('client_message') -> where('pawn_id',$pawn_id) -> update(array('is_del'=>9));
                !$r5 && $error = 1;

                //6. 消息列表 - 客户
                $this -> _msg = array(
                    'type' => 5,
                    'user_id' => $userid,
                    'pawn_id' => $pawn_id,//更新消息用到
                    'title' => '出库成功',
                    'content' => array(
                        'pawn_id' => $pawnInfo['pawn_id'],
                        'pawn_name' => $pawnInfo['pawn_name'],
                        'pawn_no' => $pawnInfo['pawn_no'],
                        'pawn_img' => $pawnInfo['pawn_pic'],
                        'ckerk_name' => Db::name('pawn_applygreen') -> where(array('pawn_id'=>$pawn_id)) -> order('add_time desc') -> value('sub_name'),
                        'remark' => '自动审核通过',
                        'status' => 4
                    ),
                );
                $r6 = $this -> insertPushMsg($this->_msg);
                !$r6 && $error = 1;

                //7. 推送消息 - 客户
                $pushMsg = array(
                    'name' => $userInfo['sub_name'],
                    'date' => date('m月d日'), //同一天发送
                    'pawn_no' => $pawnInfo['pawn_no'],
                );
                PushMessage(2, $userInfo['cid'], '出库成功', $pushMsg);

                //8 发短信 - 客户
                $xd_sms_msg = array(
                    'name' => $subInfo['sub_name'],
                    'date' => date('m月d日'),
                    'new_value' => $pawnInfo['new_value'],
                );
                sendSms(5, $userInfo['mobile'], $xd_sms_msg);

                //9 站内信 - 信贷
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
                $r9 = Db::name('message')->insert($xd_msg);
                !$r9 && $error = 1;

                //10 推送消息
                $pushMsg_xd = array(
                    'name' => $pawnInfo['user_name'],
                    'date' => date('m月d日'),
                    'new_value' => $pawnInfo['new_value'],
                );
                $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id', $userInfo['xd_id']) -> find();
                PushMessage(5, $xd_info['cid'], '出库成功', $pushMsg_xd, 'boss');

                //11. 发送短信
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

                Db::commit();

                if($error == 0){
                    tojson('1', '自动审核成功');
                }
                tojson('0', '操作失败');
            } catch(Exception $e){
                //回滚事务
                Db::rollback();
                toJson('0', '操作失败');
            }
        }elseif($status['res'] == 2){//人工审核(4为待审核阶段)
            try{
                //1. 更改状态为内部待审批
                $r1 = Db::name('pawn_applygreen') -> where('pawn_id', $pawn_id) -> update(array('status'=>3,'update_time'=>time()));
                !$r1 && $error = 1;
                //2. 更改家具表的状态
                $r2 = Db::name('pawn') -> where('pawn_id', $pawn_id) -> update(array('status'=>2,'updtime'=>time()));
                !$r2 && $error = 1;

                $r3 = $this -> pawnLog(2, $pawn_id, '抵押品解压审核', '平台进入人工审核');
                !$r3 && $error = 1;

                //4. 推送消息 - 客户端
                $this -> _msg = array(
                    'type' => 5,
                    'user_id' => $userid,
                    'pawn_id' => $pawn_id,
                    'title' => '平台待审核',
                    'content' => array(
                        'pawn_id' => $pawnInfo['pawn_id'],
                        'pawn_name' => $pawnInfo['pawn_name'],
                        'pawn_no' => $pawnInfo['pawn_no'],
                        'pawn_img' => $pawnInfo['pawn_pic'],
                        'ckerk_name' => Db::name('pawn_applygreen') -> where('pawn_id', $pawn_id) -> order('add_time desc') -> value('sub_name'),
                        'remark' => '平台待审核',
                        'status' => 3
                    ),
                );
                $r4 = $this -> insertPushMsg($this->_msg);
                !$r4 && $error = 1;

                //5. 修改状态
                $r5 = Db::name('client_message') -> where('pawn_id',$pawn_id) -> update(array('is_del'=>9));
                !$r5 && $error = 1;

                //6. 推送消息-信贷
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
                $r6 = Db::name('message')->insert($xd_msg);
                !$r6 && $error = 1;

                //7 推送消息
                $pushMsg_xd = array(
                    'name' => $pawnInfo['user_name'],
                );
                $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id', $userInfo['xd_id']) -> find();
                PushMessage(13, $xd_info['cid'], '出库申请', $pushMsg_xd, 'boss');

                //7. 发送短信
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
                    PushMessage(13, $v['cid'], '出库申请', $pushMsg_fk, 'boss');

                    //发短信
                    $fk_sms_msg = array(
                        'name' => $pawnInfo['user_name'],
                    );
                    sendSms(13, $v['mobile'], $fk_sms_msg);
                }

                Db::commit();
                if($error == 0){
                    tojson('1', '已成功提交审核');
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
    }

    /*
     * 内部否决
     */
    public function refused($userid, $request=array()){
        /** 消息推送用到  */
        $pawnInfo = $this -> getPawnInfo($request['pawn_id']);
        $subInfo  = Db::name('pawn_applygreen')
            -> field('sub_id,sub_name')
            -> where(array('pawn_id'=>$request['pawn_id']))
            -> order('add_time desc')
            -> find();//寻找提交人
        $userInfo = $this -> getUserInfo($subInfo['sub_id'], 2);

        $where['pawn_id'] = $request['pawn_id'];
        $condition['remark'] = $request['remark'];
        $condition['update_time'] = time();
        $condition['status'] = 2;
        //开启事务
        $error = 0;
        Db::transaction();
        try{
            //1. 更改
            $r1 = Db::name('pawn_applygreen') -> where($where) -> update($condition);
            !$r1 && $error = 0;

            //2. 消息列表 - 客户
            $this -> _msg = array(
                'type' => 5,
                'user_id' => $userid,
                'pawn_id' => $request['pawn_id'],
                'title' => '平台拒绝',
                'content' => array(
                    'pawn_id' => $pawnInfo['pawn_id'],
                    'pawn_name' => $pawnInfo['pawn_name'],
                    'pawn_no' => $pawnInfo['pawn_no'],
                    'pawn_img' => $pawnInfo['pawn_pic'],
                    'ckerk_name' => Db::name('pawn_applygreen') -> where('pawn_id', $request['pawn_id']) -> order('add_time desc') -> value('sub_name'),
                    'remark' => $request['remark'],
                    'status' => 2
                ),
            );
            $r2 = $this -> insertPushMsg($this->_msg);
            !$r2 && $error = 1;

            //3. 更改状态
            $r3 = $res = Db::name('client_message') -> where('pawn_id',$request['pawn_id']) -> update(array('is_del'=>9));
            !$r3 && $error = 1;

            //4. 推送消息 - 客户
            $pushMsg = array(
                'name' => $userInfo['sub_name'],
                'date' => date('m月d日'), //同一天发送
            );
            PushMessage(6, $userInfo['cid'], '出库失败', $pushMsg);

            //5. 发送短信 - 客户
            $sms_msg = array(
                'name' => $subInfo['sub_name'],
                'date' => date('m月d日'),
            );
            sendSms(7, $userInfo['mobile'], $sms_msg);

            //6. 消息列表 - 信贷
            $xd_msg = array(
                'admin_id'  => $userInfo['xd_id'],
                'comm_id' => $pawnInfo['pawn_id'],
                'title'     => '出库失败',
                'type'      => 5,
                'content'   => serialize(array(
                    "type" => 2,
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
            $r6 = Db::name('message')->insert($xd_msg);
            !$r6 && $error = 1;

            //7. 推送消息 - 信贷
            $pushMsg_xd = array(
                'name' => $pawnInfo['user_name'],
                'date' => date('m月d日'),
            );
            //找到对应的信贷经理
            $xd_id = Db::name('users') -> where('user_id',$userid) -> value('xd_id');
            $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id', $xd_id) -> find();
            PushMessage(7, $xd_info['cid'], '出库失败', $pushMsg_xd, 'boss');

            //8. 发送短信 - 信贷
            $xd_sms_msg = array(
                'name' => $pawnInfo['user_name'],
                'date' => date('m月d日'),
            );
            sendSms(8, $xd_info['mobile'], $xd_sms_msg);

            //给所有风控推送
            $fk_infos = Db::name('admin') -> field('admin_id,cid,user_name,mobile') -> where('role_id=2') -> select();
            foreach($fk_infos as $k => $v){
                //站内信
                $fk_msg = array(
                    'admin_id'  => $v['admin_id'],
                    'comm_id' => $pawnInfo['pawn_id'],
                    'title'     => '出库失败',
                    'type'      => 5,
                    'content'   => serialize(array(
                        "type" => 2,
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
                );
                PushMessage(7, $v['cid'], '出库失败', $pushMsg_fk, 'boss');

                //发短信
                $fk_sms_msg = array(
                    'name' => $pawnInfo['user_name'],
                    'date' => date('m月d日'),
                );
                sendSms(8, $v['mobile'], $fk_sms_msg);
            }

            Db::commit();

            if($error == 0){
                toJson('1', '操作成功');
            }
            tojson('0', '操作失败');
        } catch(Exception $e){
            //回滚事务
            Db::rollback();
            toJson('0', '操作失败');
        }
    }
}