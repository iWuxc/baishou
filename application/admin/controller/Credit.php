<?php
/**
 * 授信额度管理类
 * Author: iWuxc
 * Date: 2017-11-30
 */

namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Request;
use think\Db;
use app\admin\logic\ClientLogic;
use app\admin\logic\CreditLogic;
use think\Loader;

class Credit extends Base{

    /**
     * 客户审核页面
     * @return mixed
     */
    public function index(){
        //搜索表单
        $condition = array();
        $keyType = I("keytype");
        $keywords = I('keywords','','trim');
        $is_approval = I('is_approval', 1);
        //获取当前管理员角色ID
        $role_id = session('admin_info')['role_id'];
        $this -> assign('role_id',$role_id);

        $name =  ($keyType && $keyType == 'name') ? $keywords : I('name','','trim');
        $name ? $condition['name'] = array('like',"%".trim($name)."%") : false;

        $client_no = ($keyType && $keyType == 'client_no') ? $keywords : I('client_no');
        $client_no ? $condition['client_no'] = trim($client_no) : false;

        I('type') != '' ? $condition['type'] = I('type') : false;
        if($is_approval == 9){
            $condition['is_approval'] = array('gt',0);
        }else{
            $condition['is_approval'] = $is_approval;
        }

        $count = M('users') ->where($condition) ->count();
        $Page = new Page($count, 10);

        $userInfo = M('users')
            -> where($condition)
            -> field('user_id, name, xd_id, client_no, type, mobile, store_num, update_time, is_approval')
            -> order('update_time desc')
            -> limit($Page->firstRow.','.$Page->listRows)
            -> select();
        foreach($userInfo as $key => $val){
            $r = M('credit_conclusion') -> where('user_id',$val['user_id']) -> find();
            if($r){
              $userInfo[$key]['is_credit'] = 1;
            }else{
                $userInfo[$key]['is_credit'] = 0;
            }
        }
        //var_dump($userInfo);exit;
        $show  = $Page->show();
        $this -> assign('list', $userInfo);
        $this -> assign('is_approval', $is_approval);
        $this -> assign('show',$show);
        $this -> assign('pager',$Page);
        $this -> assign('allAdminName', getAllAdmin());
        return $this -> fetch();
    }

    /**
     * 用户详情展示
     * @param $user_id
     * @return mixed
     */
    public function user_detail(){
        $user_id = I('user_id/d');
        $clientLogic = new ClientLogic();
        //查看是否已添加审批结论
        $credit = M('credit_conclusion') -> where('user_id='.$user_id) -> find();
        if(!empty($credit)){
            $credit['loan_con'] = unserialize($credit['loan_con']);
            $credit['money_plan'] = unserialize($credit['money_plan']);
            $credit['credit'] = number_format(($credit['credit']/10000),2,",",".");
            $this -> assign('credit', $credit);
        }
        $userInfo = $clientLogic -> getClientInfo($user_id);
        //配偶信息
        $spouse = M('user_spouse') -> where('user_id='.$user_id) -> find();
        //企业信息
        $ent = $clientLogic -> getEnterpriseInfo($user_id);
        $this -> assign('ent', $ent);
        //门店信息
        $store = $clientLogic -> getUserStoreList($user_id);

        $this -> assign('userInfo', $userInfo);
        $this -> assign('spouse', $spouse);
        $this -> assign('store', $store);
        $this -> assign('admin_id', session('admin_id'));
        $this -> assign('allAdminName', getAllAdmin());
        $this->assign('empty','<span class="empty">没有数据</span>');
        return $this -> fetch();
    }

    /**
     * 客户信息审批状态操作
     */
    public function credit_update() {
        $CreditLogic = new CreditLogic();
        $user_id = I('id');
        $data['is_approval'] = I('status');
        $data['remark'] = I('remark');
        $data['update_time'] = time();
        $r1 = Db::name('users') -> where('user_id',$user_id)->update($data);
        if(!$r1){
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        }
        //记录日志
        $r2 = $CreditLogic -> approveActionLog($user_id, '客户信息审核操作', '审核操作');
        if(!$r2){
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        }
        $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功", 'res'=>I('status')),'JSON');

    }

    /**
     * 授信额度审批结论查看页面
     * @return mixed
     */
    public function credit_conclusion_detail(){
        $CreditLogic = new CreditLogic();
        $user_id = I('user_id');
        $t = I('t');
        $concl = $CreditLogic -> get_credit_detail($user_id);
        if(!empty($concl['loan_con'])){
            $concl['loan_con'] = unserialize($concl['loan_con']);
            $concl['money_plan'] = unserialize($concl['money_plan']);
            $concl['credit'] = number_format($concl['credit'],2,",",".");
        }

        $this -> assign('data', $concl);
        $this -> assign('adminName',getAllAdmin());
        $this -> assign('t', $t);
        return $this -> fetch();
    }

    /**
     * 添加审批结论
     * @return mixed
     */
    public function add_credit_conclusion(){
        $user_id = I('get.user_id/d');
        $user = M('users') -> field('name,xd_id') -> where(array('user_id' => $user_id)) -> find();
        if(!empty($user)){
            $this -> assign('user_name', $user['name']);
        }
        //查找该用户下所有门店
        $stores = M('user_store') -> field('store_id, store_name') -> where(array('user_id'=>$user_id)) -> select();
        //查找信贷经理信息
        $xd_name = getAllAdmin()[$user['xd_id']];
        $this -> assign('xd_name',$xd_name);
        $this -> assign('xd_id', $user['xd_id']);
        $this -> assign("stores", $stores);
        $this -> assign('user_id', $user_id);
        return $this -> fetch();
    }

    public function credit_conclu_handle(){
        if(Request::instance()->isPost() && I('is_ajax') == 1) {
            $data = Request::instance()->post();
            $validate = Loader::validate('Credit');
            if (!$validate->batch()->check($data)) {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );
                $this->ajaxReturn($return_arr);
            }
            //处理门店数量
            if(!isset($data['storeid'])){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '至少抵押一个门店',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            $money_plan = array();
            $sum_money = 0;
            if(!empty($data['start_time'])) {
                foreach ($data['start_time'] as $key => $value) {
                    $money_plan[$key]['start_time'] = trim($data['start_time'][$key]);
                    $money_plan[$key]['stop_time'] = trim($data['stop_time'][$key]);
                    $money_plan[$key]['use_amount'] = floatval($data['use_amount'][$key]);
                    $sum_money += floatval($data['use_amount'][$key]);
                }
                $money_plan = array_values(array_sort($money_plan, 'use_amount', 'asc'));
                if ($sum_money > $data['credit']) {
                    $return_arr = array(
                        'status' => -1,
                        'msg' => '用款计划金额不能超过授信额度金额',
                        'data' => '',
                    );
                    $this->ajaxReturn($return_arr);
                }
                if ($money_plan[0]['use_amount']!= '' && $money_plan[0]['use_amount'] <= 0) {
                    $return_arr = array(
                        'status' => -1,
                        'msg' => '请输入有效的金额',
                        'data' => '',
                    );
                    $this->ajaxReturn($return_arr);
                }
                $data['money_plan'] = serialize($money_plan);
            }
            //贷前条件
            if(!empty($data['loan_con'])){
                $data['loan_con'] = serialize($data['loan_con']);
            }

            //转化格式
            $data['draw_strtime'] = strtotime($data['draw_strtime']);
            $data['draw_stptime'] = strtotime($data['draw_stptime']);
            $data['fk_id'] = session('admin_id'); //风控审批ID
            $data['store_sum'] = count($data['storeid']);
            $data['pawn_store'] = implode(',', $data['storeid']);
            $data['add_time'] = time();
            $data['credit'] = $data['credit'] * 10000;

            if(!empty($data['assurer_id']) && $data['assurer_id'] != 0){
                $data['assurer_name'] = M('users') -> where('user_id',$data['assurer_id']) -> value('name');
            }

            $error = 0;
            Db::startTrans();
            try{
                //1 插入(授信)数据
                $r1 = M('credit_conclusion') -> add($data);
                !$r1 && $error = 1;

                //更新用户表中的风控ID
                $r2 = Db::name('users') -> where('user_id',$data['user_id']) -> update(array('fk_id'=>session('admin_id')));
                !$r2 && $error = 1;

                //3. 初始化个人信用表
                $condition = [];
                $condition['user_id'] = $data['user_id'];
                $condition['credit'] = $data['credit']; //审批额度, 参考值
                $condition['check_rate'] = $data['check_rate']; //审批抵押率
                $condition['store_total'] = $data['store_sum']; //抵押门店数量
                $condition['addtime'] = time();
                $r3 = Db::name('user_credit') -> insert($condition);
                !$r3 && $error = 1;

                //=================风控站内信-推送-短信=======================
                $fk_infos = Db::name('admin') -> field('admin_id,cid,user_name,mobile') -> where('role_id=2') -> select();
                foreach($fk_infos as $k => $v){
                    //站内信
                    $fk_msg = array(
                        'admin_id'  => $v['admin_id'],
                        'title'     => '申请授信',
                        'type'      => 4,
                        'content'   => serialize(array(
                            "type" => 0,
                            'apply_amount' => $data['credit'],
                            'credit_id' => $r1,
                            "user_id" => $data['user_id'],
                            "user_name" => $data['user_name'],
                            "reg_time" => date("Y-m-d H:i:s", $data['add_time']),
                            "remarks" => ''
                        )),
                        'add_time'  => time()
                    );
                    Db::name('message')->insert($fk_msg);
                    //推送
                    $pushMsg_fk = array(
                        'name' => $data['user_name'],
                    );
                    PushMessage(14, $v['cid'], '授信申请', $pushMsg_fk, 'boss');

                    //发短信
                    $fk_sms_msg = array(
                        'name' => $data['user_name'],
                    );
                    sendSms(14, $v['mobile'], $fk_sms_msg);
                }

                // 提交事务
                Db::commit();
                if($error == 1){
                    Db::rollback();
                    $return_arr = array(
                        'status' => -1,
                        'msg' => '操作失败',
                        'data' => '',
                    );
                    $this->ajaxReturn($return_arr);
                }
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('credit_id'=>$r1,'user_id'=>$data['user_id']),
                );
                $this->ajaxReturn($return_arr);
            }catch(\Exception $e){
                // 回滚事务
                Db::rollback();
                $return_arr = array(
                    'status' => -1,
                    'msg' => '操作失败',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }
        }
    }

    /**
     * 修改审批结论
     */
    public function edit_credit_conclusion(){
        $id = I('get.id/d');
        if(!$id){
            $this -> error('参数错误');
        }
        $info = M("credit_conclusion") -> where('id='.$id) -> find();
        //判断状态, 如果已经提交, 禁止修改
        if($info['status'] > 1){
            $this -> error('已进行过提交操作, 不能修改');
        }
        //信贷经理名称
        $addper_name = M('admin') -> field('user_name') -> where('admin_id='.$info['addperid']) -> find();
        $info['addper_name'] = $addper_name['user_name'];
        //查找该用户下所有门店
        $stores = M('user_store') -> field('store_id, store_name') -> where(array('user_id'=>$info['user_id'])) -> select();
        //处理门店
        $info['stores'] = explode(",", $info['pawn_store']);

        $this -> assign('list', $info);
        $this -> assign('stores', $stores);
        return $this -> fetch();
    }

    /**
     * 修改审批结论操作
     */
    public function edit_credit_handle(){
        $CreditLogic = new CreditLogic();
        if(Request::instance()->isPost()) {
            $data = Request::instance()->post();
            //再次判断是否有修改权限
            $info = M('credit_conclusion') -> field('status') -> where('id='.$data['id']) -> find();
            if($info['status'] > 1){
                $this -> error('已进行过提交操作, 不能修改');
            }

            //转化时间格式
            $data['str_time'] = strtotime($data['str_time']);
            $data['stp_time'] = strtotime($data['stp_time']);
            $data['draw_strtime'] = strtotime($data['draw_strtime']);
            $data['draw_stptime'] = strtotime($data['draw_stptime']);
            $data['addtime'] = time();
            $data['credit'] = $data['credit'] * 10000;

            $data['approver'] = session('admin_id'); //审批人ID
            //处理门店数量
            if(isset($data['storeid']) && count($data['storeid']) < 1){
                $this -> error("抵押的门店至少选择一个!");
            }
            $data['store_sum'] = count($data['storeid']);
            $data['pawn_store'] = implode(',', $data['storeid']);
            $res = M('credit_conclusion') -> where('id='.$data['id']) -> update($data);

            if($res){
                $user_id = M('credit_conclusion') -> field('user_id') -> where('id='.$data['id']) -> find();
                $CreditLogic -> approveActionLog($user_id['user_id'], '审批结论修改操作', '审批结论修改成功');
                $this -> success('修改成功');
            }else{
                $this -> error('添加失败');
            }
        }
    }

    /**
     * 授信额度状态更改
     */
    public function set_credit_conclu_status(){
        $CreditLogic = new CreditLogic();
        if (Request::instance()->isAjax() && Request::instance()->isPost() && (I('is_ajax') ==1) ){
            $id = I('post.id/d');
            $condition['status'] = I('status');
            if($id < 0){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '未知参数',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            $res = M('credit_conclusion') -> where('id='.$id) -> save($condition);
            if(!$res){
                $user_id = M('credit_conclusion') -> field('user_id') -> where('id='.$id) -> find();
                $CreditLogic -> approveActionLog($user_id['user_id'], '审批结论审核操作', '审批结论审核');
                $return_arr = array(
                    'status' => -1,
                    'msg' => '提交失败, 请尝试重新提交',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            $return_arr = array(
                'status' => 1,
                'msg' => '提交成功, 请耐心等待审核反馈',
                'data' => '/index.php/Admin/credit/credit_conclusion_detail',
            );
            $this->ajaxReturn($return_arr);
        }
    }

    /**
     * 审批结论审核页面
     */
    public function examination_approval(){
        $status = I('status', 1);
        $this -> assign('status', $status);
        $this -> get_credit_conclusoin($status);
        return $this -> fetch();
    }

    public function get_credit_conclusoin($status=''){
        //搜索表单
        $user_name = I('user_name'); // 用户名称
        $approver = I('approver_name'); //审批人昵称

        /**
        $create_time = I('create_time'); //查找的时间段
        $create_time = str_replace('+', ' ',$create_time);
        $create_time2 = $create_time ? $create_time : date('Y-m-d', strtotime('-1 year')).' - ' . date('Y-m-d', strtotime('+1, day'));
        $create_time3 = explode(' - ', $create_time2);
        $this -> assign('start_time', $create_time3[0]);
        $this -> assign('end_time', $create_time3[1]);
        $where['add_time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
         **/

        $status = empty($status) ? I('status') : $status;
        if(!empty($status)){
            $where['status'] = $status;
        }
        $user_name && $where['user_name'] = array('like','%'.$user_name.'%');
        empty($where) && $where = "1 = 1";

        $count = Db::name('credit_conclusion') ->where($where) ->count();
        $Page  = new Page($count,10);
        $list = Db::name('credit_conclusion')
            ->where($where)
            ->order("id desc")
            ->limit($Page->firstRow.','.$Page->listRows)
            ->select();

        $show  = $Page->show();
        $this -> assign('status', $status);
        $this -> assign('allAdminName', getAllAdmin());
        $this->assign('show',$show);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
    }

    /**
     * 授信额度详情
     */
    public function credit_fh_detail(){
        $user_id = I('user_id/d');
        $clientLogic = new ClientLogic();
        //查看是否已添加审批结论
        $credit = M('credit_conclusion') -> where('user_id='.$user_id) -> find();
        if(!empty($credit)){
            $credit['loan_con'] = unserialize($credit['loan_con']);
            $credit['money_plan'] = unserialize($credit['money_plan']);
            $credit['credit'] = number_format(($credit['credit']/10000),2,",",".");
            $this -> assign('credit', $credit);
        }
        $userInfo = $clientLogic -> getClientInfo($user_id);
        //配偶信息
        $spouse = M('user_spouse') -> where('user_id='.$user_id) -> find();
        //企业信息
        $ent = $clientLogic -> getEnterpriseInfo($user_id);
        $this -> assign('ent', $ent);
        //门店信息
        $store = $clientLogic -> getUserStoreList($user_id);

        $this -> assign('userInfo', $userInfo);
        $this -> assign('spouse', $spouse);
        $this -> assign('store', $store);
        $this -> assign('admin_id', session('admin_id'));
        $this -> assign('allAdminName', getAllAdmin());
        $this->assign('empty','<span class="empty">没有数据</span>');
        return $this -> fetch();
    }

    /**
     * 审批结论复核
     */
    public function conclusion_update(){
        $data = array();
        $CreditLogic = new CreditLogic();
        $id = I('id');
        $data['status'] = I('status');
        $data['fh_name'] = session('admin_info')['user_name'];
        $data['fh_id'] = session('admin_id');
        $data['com_remarks'] = I('remark');
        $data['update_time'] = time();
        if(session('admin_id') != 1){
            //查询权限
            $info = Db::name('credit_conclusion') -> where('id',$id) -> find();
            if($info['fk_id'] == session('admin_id')){
                $this -> ajaxReturn(array('status'=>0, 'msg'=>'禁止审批自己提交的审批结论,请联系其他审批经理'), 'JSON');
            }
        }
        $error = 0;
        Db::startTrans();
        try{
            $infos = M('credit_conclusion')->field('id,user_id,user_name,status,xd_id,credit,add_time,com_remarks')->where('id', $id)->find();

            //1. 修改状态
            $r1 = Db::name('credit_conclusion')->where('id', $id)->update($data);
            !$r1 && $error = 1;

            //2. 更改用户表中的信息
            $is_approval = I('status') == 2 ? 2 : 3;
            $r2 = Db::name('users')->where('user_id', $infos['user_id'])->update(array('is_approval'=>$is_approval));
            !$r2 && $error = 1;

            //2. 插入日志
            $note = I('status') == 2 ? '授信复核通过' : '授信复核驳回';
            $r3 = $CreditLogic -> approveActionLog($infos['user_id'], '审批结论审核操作', $note);
            !$r3 && $error = 1;

            //3. 录入个人信用
            /**
            if(I('status') == 2){
                $r4 = $CreditLogic -> credit_add_action($infos['user_id']);
                !$r4 && $error = 1;
            }*/

            //=================信贷站内信-推送-短信=======================
            $xd_msg = array(
                'admin_id'  => $infos['xd_id'],
                'title'     => I('status') == 2 ? '授信通过' : '授信驳回',
                'type'      => 4,
                'content'   => serialize(array(
                    "type" => I('status') == 2 ? 1 : 2,
                    'apply_amount' => $infos['credit'],
                    'credit_id' => $infos['id'],
                    "user_id" => $infos['user_id'],
                    "user_name" => $infos['user_name'],
                    "reg_time" => date("Y-m-d H:i:s", $infos['add_time']),
                    "remarks" => $infos['com_remarks']
                )),
                'add_time'  => time()
            );
            $r5 = Db::name('message')->insert($xd_msg);
            !$r5 && $error = 1;

            $xd_pushMsg = array(
                'name' => $infos['user_name'],
            );
            $xd_info = Db::name('admin') -> field('cid,mobile') -> where('admin_id',$infos['xd_id']) -> find();
            I('status') == 2 ? PushMessage(9, $xd_info['cid'], '授信通过', $xd_pushMsg, 'boss') :  PushMessage(11, $xd_info['cid'], '授信驳回', $xd_pushMsg, 'boss');

            $sms_msg = array(
                'name' => $infos['user_name'],
            );
            I('status') == 2 ? sendSms(9, $xd_info['mobile'], $sms_msg) : sendSms(11, $xd_info['mobile'], $sms_msg);
            //=================风控站内信-推送-短信=======================
            $fk_infos = Db::name('admin') -> field('admin_id,cid,user_name,mobile') -> where('role_id=2') -> select();
            foreach($fk_infos as $k => $v){
                //站内信
                $fk_msg = array(
                    'admin_id'  => $v['admin_id'],
                    'title'     => I('status') == 2 ? '授信通过' : '授信驳回',
                    'type'      => 4,
                    'content'   => serialize(array(
                        "type" => I('status') == 2 ? 1 : 2,
                        'apply_amount' => $infos['credit'],
                        'credit_id' => $infos['id'],
                        "user_id" => $infos['user_id'],
                        "user_name" => $infos['user_name'],
                        "reg_time" => date("Y-m-d H:i:s", $infos['add_time']),
                        "remarks" => $infos['com_remarks']
                    )),
                    'add_time'  => time()
                );
                Db::name('message')->insert($fk_msg);

                //推送
                $pushMsg_fk = array(
                    'name' => $infos['user_name'],
                );
                I('status') == 2 ? PushMessage(10, $v['cid'], '授信通过', $pushMsg_fk, 'boss') : PushMessage(12, $v['cid'], '授信通过', $pushMsg_fk, 'boss');

                //发短信
                $fk_sms_msg = array(
                    'name' => $infos ['user_name'],
                );
                I('status') == 2 ? sendSms(10, $v['mobile'], $fk_sms_msg) : sendSms(12, $v['mobile'], $fk_sms_msg);
            }
            // 提交事务
            Db::commit();
            if($error == 1){
                Db::rollback();
                $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功",'res'=>$infos['status']),'JSON');
        }catch(Exception $e){
            // 回滚事务
            Db::rollback();
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        }
    }

    /**
     * 审批操作日志
     * @return mixed
     */
    public function approval_log(){
        $timegap_begin = urldecode(I('timegap_begin'));
        $timegap_end = urldecode(I('timegap_end'));
        $begin = strtotime($timegap_begin);
        $end = strtotime($timegap_end);
        $condition = array();
        $log =  M('approval_action');
        if($begin && $end){
            $condition['log_time'] = array('between',"$begin,$end");
        }
        $admin_id = I('admin_id');
        if($admin_id >0 ){
            $condition['action_user'] = $admin_id;
        }
        $user_no = I('user_no');
        if(!empty($user_no)){
            $condition['user_no'] = $user_no;
        }
        $count = $log->where($condition)->count();
        $Page = new Page($count,10);

        foreach($condition as $key=>$val) {
            $Page->parameter[$key] = urlencode($begin.'_'.$end);
        }

        $show = $Page->show();
        $list = $log->where($condition)->order('action_id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('timegap_begin',$timegap_begin);
        $this->assign('timegap_end',$timegap_end);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);
        $admin = M('admin')->getField('admin_id,user_name');
        $this->assign('admin',$admin);
        return $this->fetch();
    }

    public function composite_list(){
        return $this -> fetch();
    }

    /**
     * 搜索用户名
     * 搜索条件 手机/客户编号/姓名
     * 备注: 跨控制器去获取,不好使啊,待解决
     */
    public function search_user(){
        $search_key = trim(I('search_key'));
        $user = Db::name('users')
            ->alias('u')
            ->field('u.user_id,u.name,ud.id_number')
            ->join('__USER_DETAIL__ ud','u.user_id=ud.user_id','LEFT')
            ->whereOr("u.mobile",$search_key)
            ->whereOr('u.client_no',$search_key)
            ->whereOr('u.name','like',"%$search_key%")
            ->select();
        foreach($user as $key => $val)
        {
            echo "<option value='{$val['user_id']}'>{$val['name']}-{$val['id_number']}</option>";
        }
    }

}