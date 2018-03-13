<?php
/**
 * 信贷客户管理
 * Author: iWuxc
 * Date: 2017-11-30
 */

namespace app\admin\controller;
use app\admin\logic\CreditLogic;
use think\AjaxPage;
use think\Exception;
use think\Page;
use think\Request;
use think\Db;
use app\admin\logic\ClientLogic;
use think\Loader;

class Users extends Base {

    /**
     * 客户列表
     * @return mixed
     */
    public function index(){
        //判断是否有信息导出权限
        if(session('admin_id') == 1){
            $this -> assign('is_export', 1);
        }
        $this -> assign('admin_info', session('admin_info'));
        $this -> ajaxClientList();
        return $this -> fetch();
    }

    public function ajaxClientList(){
        $ClientLogic = new ClientLogic();

        // 搜索条件
        $condition = array();
        $keyType = I("keytype");
        $keywords = I('keywords','','trim');
        $admin_id = I('admin_id') ? I('admin_id') : session('admin_id');
        $is_approval = I('is_approval', 9);
        $export = I('export');
        $user_ids = I('user_ids'); //导出

        if($export == 1 && empty($user_ids)){
            $is_all = false;
        }else{
            $is_all = true;
        }

        $name =  ($keyType && $keyType == 'name') ? $keywords : I('name','','trim');
        $name ? $condition['u.name'] = array('like',"%".trim($name)."%") : false;
        $client_no = ($keyType && $keyType == 'client_no') ? $keywords : I('client_no');
        $client_no ? $condition['u.client_no'] = trim($client_no) : false;

        I('type') != '' ? $condition['u.type'] = I('type') : false;
        $is_approval != 9 ? $condition['u.is_approval'] = I('is_approval') : false;
        if($user_ids){
            $condition['u.user_id'] = ['in', $user_ids];
        }

        //如果是信贷经理,则只能看到自己的用户信息
        if($admin_id){
            $role_id = M('admin') -> where(array('admin_id'=>$admin_id)) -> value('role_id');
            if(!in_array($role_id, getRoles())){
                $condition['u.xd_id'] = $admin_id;
            }
        }

        if($export == 1 && $admin_id && ($admin_id != 1)){
            $condition['u.xd_id'] = $admin_id;
        }

        $user_order = I('order_by','user_id').' '.I('sort','DESC');

        empty($where) && $where = " 1 = 1 ";
        $count = M('users') -> alias('u') -> join('__USER_DETAIL__ ud', 'u.user_id=ud.user_id', 'LEFT') ->where($condition) ->count();
        $Page  = new Page($count,10);

        $clientList = $ClientLogic -> getClientList($condition, $user_order, $Page->firstRow, $Page->listRows, $is_all);
        foreach ($clientList as $key => $val){
            if($val['type'] == 2){
                $clientList[$key]['name'] = Db::name('user_enterprise') -> where('user_id',$val['user_id']) -> value('name');
            }
        }
        if($export == 1){
            $strTable ='<table width="1000" border="1">';
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">序号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">客户编号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">姓名</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="100">证件类型</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">证件号码</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">手机号码</td>';
            $strTable .= '</tr>';
            if(is_array($clientList)){
                $cre_type = array(1=>'公民身份证', 2=>'港澳台居民身份证', 3=>'护照', 4=>'其他');
                foreach($clientList as $k=>$val){
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['user_id'].'</td>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['client_no'].' </td>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['name'].'</td>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.$cre_type[$val['credential_type']].'</td>';
                    $strTable .= '<td style="text-align:center;font-size:12px;vnd.ms-excel.numberformat:@;">'.$val['id_number'].'</td>';
                    $strTable .= '<td style="text-align:center;font-size:12px;">'.number_format($val['mobile'], 0, '', '').'</td>';
                    $strTable .= '</tr>';
                }
            }
            $strTable .='</table>';
            unset($userList);
            downloadExcel($strTable,'客户列表明细');
            exit();
        }

        $show = $Page->show();
        $this -> assign('clientList',$clientList);
        $this -> assign('allAdminName', getAllAdmin());
        $this -> assign('show',$show);// 赋值分页输出
        $this -> assign('pager',$Page);
        $this -> assign('is_approval', $is_approval);
    }

    /**
     * 客户信息添加
     * @return mixed
     */
    public function add_user(){
        $clientLogic = new ClientLogic();
        if(Request::instance()->isPost()){
            $data = Request::instance() -> post();
            if(isset($data['type']) && $data['type'] == 2){
                if(intval($data['ent_id']) < 0){
                    $this -> error("您选择的是企业客户类型,请补全企业信息");
                }
            }
            if(empty($data['industries']) || empty($data['industries_province']) || empty($data['industries_city'])){
                $this -> error('请选择产业类型');
            }
            $industries = array(
                'id' => $data['industries'],
                'province_id' => $data['industries_province'],
                'city_id' => $data['industries_city'],
            );

            if($data['marriage'] != 2){
                $data['kids'] = 0;
            }

            //生成客户编号
            $data['client_no'] = $clientLogic -> create_clientNo($industries, $data['type'], 5); //用户编号
            //生成客户随机密码 -- 这一块需要在后台注册成功之后进行信息发送
            $passport = $clientLogic -> get_passwd($data['id_number'], 6);
            $data['password'] = encrypt($passport);

            //开始过滤字段
            $data['xd_id'] = session("admin_id"); //默认当前登录管理员为负责信贷经理
            $data['add_type'] && $data['is_approval'] = 2;
            $data['nickname'] = $data['name']; //默认昵称为真实姓名
            $data['reg_time'] =  time(); //注册时间
            $data['home_tel'] = $data['home_qh'] . '-' . $data['home_tel'];
            $data['company_tel'] = $data['company_qh'] . '-' . $data['company_tel'];
            //开启事务
            $error = 0;
            Db::startTrans();
            try{
                //开始添加数据
                $user_id = Db::name('users') -> add($data);
                if(!$user_id){
                    throw new Exception("添加失败,请重新添加");
                }
                if($user_id){//录入详情信息
                    $data['user_id'] = $user_id;
                    $r1 = Db::name('user_detail') -> insert($data);
                    !$r1 && $error = 1;
                }
                //开始组装配偶信息
                $sp = array();
                if(!empty($data['sp_name'])){
                    $sp['sp_name'] = $data['sp_name'];
                    $sp['user_id'] = $user_id; //对接刚添加的客户ID
                    $sp['sp_education'] = $data['sp_education'];
                    $sp['sp_mobile'] = $data['sp_mobile'];
                    $sp['sp_credential_type'] = $data['sp_credential_type'];
                    $sp['sp_id_number'] = $data['sp_id_number'];
                    $sp['work_unit'] = $data['work_unit'];
                    $sp['duty'] = $data['duty'];
                    $sp['sp_icnome'] = $data['sp_icnome'];
                    $sp['addtime'] = time();
                    //执行添加
                    $r2 = Db::name('user_spouse') -> insert($sp);
                    !$r2 && $error = 1;
                }
                //进行更新同步上传的企业信息
                if($data['type'] == 2){
                    if(!empty($data['ent_ids'])) {
                        $ent_ids = implode(',', $data['ent_ids']);
                        $r3 = Db::name('user_enterprise')->where("enterprise_id in (" . $ent_ids . ")")->update(array('user_id' => $user_id));
                        !$r3 && $error = 1;
                    }
                }
                //记录日志
                $r4 = $clientLogic -> userActionLog($user_id, "添加客户操作", '成功添加客户');
                !$r4 && $error = 1;

                $r5 = $clientLogic -> setTempPasswd($user_id, $passport, $data['mobile']);
                !$r5 && $error = 1;

                Db::commit();
                if($error == 0){
                    //4. 开始发送短信
                    $param['name'] = $data['name'];
                    $param['mobile'] = $data['mobile'];
                    $param['password'] = $passport;
                    sendSms("1", $data['mobile'], $param); //用户注册
                    $this->success('添加客户信息成功',U("Admin/Users/index",array('user_id'=>$user_id)));
                }
                $this->success('添加客户信息成功',U("Admin/Users/index",array('user_id'=>$user_id)));
            }catch(Exception $e){
                //回滚事务
                $this -> error($e -> getMessage());
                Db::rollback();
            }
            exit;
        }
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //获取产业类型
        $industries = M('user_industries') -> field('id, name') -> where('status = 1') -> select();
        $this -> assign("industriesType", $industries);
        $this->assign('province',$province);
        I('get.add_type') && $this -> assign('add_type', I('get.add_type'));
        return $this -> fetch();
    }

    /**
     * 编辑用户信息
     * @return mixed
     */
    public function edit_user(){
        $user = array();
        if(Request::instance()->isPost()){
            $clientLogic = new ClientLogic();
            $data = Request::instance() -> post();
            //判断是否有修改权限
            $userInfo = M('users') -> field('is_approval, client_no') -> where('user_id='.$data['user_id']) -> find();
            if($userInfo['is_approval'] != 0){
                $this -> error("该信息已有过提交操作, 不可更改", U('Admin/users/index'));
                return false;
            }
            //开始过滤字段
            $data['update_time'] = time(); //更新时间
            $data['home_tel'] = $data['home_qh'] . '-' . $data['home_tel'];
            $data['company_tel'] = $data['company_qh'] . '-' . $data['company_tel'];

            if($data['marriage'] != 2){
                $data['kids'] = 0;
            }

            Db::startTrans();
            try{
                //开始修改数据
                $r1 = Db::name('Users')->where('user_id='.$data['user_id']) -> update($data);
                if(!$r1)
                    throw new Exception('修改失败,请重新尝试');
                $r2 = Db::name('user_detail')->where('user_id='.$data['user_id']) -> update($data);
                if(!$r2)
                    throw new Exception('修改失败,请重新尝试');
                //开始组装配偶信息
                $sp = array();
                if(!empty($data['sp_name'])){
                    $sp['sp_name'] = $data['sp_name'];
                    $sp['sp_education'] = $data['sp_education'];
                    $sp['sp_mobile'] = $data['sp_mobile'];
                    $sp['sp_credential_type'] = $data['sp_credential_type'];
                    $sp['sp_id_number'] = $data['sp_id_number'];
                    $sp['work_unit'] = $data['work_unit'];
                    $sp['duty'] = $data['duty'];
                    $sp['sp_icnome'] = $data['sp_icnome'];
                    $sp['addtime'] = time();

                    //执行添加
                    if($data['sp_id']){
                        $r3 = Db::name('user_spouse') -> where('user_id='.$data['user_id']) -> update($sp);
                    }else{
                        $sp['user_id'] = $data['user_id'];
                        $r3 = Db::name('user_spouse') -> add($sp);
                    }

                    if(!$r3)
                        throw new Exception('修改失败,请重新尝试');
                    $r4 = $clientLogic -> userActionLog($data['user_id'], "修改客户操作", '成功修改客户信息');
                    if(!$r4)
                        throw new Exception('修改失败,请重新尝试');
                    Db::commit();
                    $this->success('修改客户信息成功',U("Admin/Users/index",array('user_id'=>$data['user_id'])));
                    exit;
                }
            }catch (Exception $e){
                $this -> error($e -> getMessage());
                Db::rollback();
            }

        }

        $user_id = I('user_id');
        if(!$user_id){
            $this -> error('非法操作');
        }
        $userLogic = new ClientLogic();
        //查询数据
        $users = $userLogic -> getClientInfo($user_id);
        $home_tel_arr = explode('-', $users['home_tel']);
        $users['home_qh'] = $home_tel_arr[0];
        $users['home_tel'] = $home_tel_arr[1];
        $company_tel_arr = explode('-', $users['company_tel']);
        $users['company_qh'] = $company_tel_arr[0];
        $users['company_tel'] = $company_tel_arr[1];

        $this -> assign('users', $users);
        //查询配偶信息
        $spouse = M('user_spouse') -> where(array('user_id' => $user_id)) -> find();
        $this -> assign('spouse', $spouse);
        if(!isset($user_id) || empty($user_id)){
            $this -> error('参数错误', U('Admin/users/index'));
        }
        //  获取户口省份
        $account_province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取户口订单城市
        $account_city =  M('region')->where(array('parent_id'=>$users['account_province'],'level'=>2))->select();
        //  获取户口订单地区
        $account_area =  M('region')->where(array('parent_id'=>$users['account_city'],'level'=>3))->select();
        //  获取家庭住址省份
        $home_province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取家庭住址城市
        $home_city =  M('region')->where(array('parent_id'=>$users['home_province'],'level'=>2))->select();
        //  获取家庭住址地区
        $home_area =  M('region')->where(array('parent_id'=>$users['home_city'],'level'=>3))->select();
        //  获取收件住址省份
        $mails_province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取收件住址城市
        $mails_city =  M('region')->where(array('parent_id'=>$users['mails_province'],'level'=>2))->select();
        //  获取手机拿住址地区
        $mails_area =  M('region')->where(array('parent_id'=>$users['mails_city'],'level'=>3))->select();
        //获取产业类型
        $industries = M('user_industries') -> field('id, name') -> where('status = 1') -> select();
        $this -> assign("industriesType", $industries);
        $this->assign('a_province',$account_province);
        $this->assign('a_city',$account_city);
        $this->assign('a_area',$account_area);
        //家庭
        $this->assign('h_province',$home_province);
        $this->assign('h_city',$home_city);
        $this->assign('h_area',$home_area);

        //收件
        $this->assign('m_province',$mails_province);
        $this->assign('m_city',$mails_city);
        $this->assign('m_area',$mails_area);
        return $this -> fetch();
    }

    /**
     * 删除用户信息
     */
    public function delete_user(){
        $ClientLogic = new ClientLogic();
        $user_id = I('post.user_id/d', 0);
        //开启事务
        Db::startTrans();
        try{
            $ClientLogic -> userActionLog($user_id, '删除客户操作', '删除客户'); //先记录日志
            $del = $ClientLogic -> delUser($user_id);
            Db::commit();
        }catch(Exception $e){
            Db::rollback();
        }
        $this -> ajaxReturn($del);
    }

    /**
     * 企业列表
     * @return mixed
     */
    public function entList(){
        $clientLogic = new ClientLogic();
        // 搜索条件
        $condition = array();
        $keywords = I('keywords','','trim');
        $keywords ? $condition['name'] = array('like', "%$keywords%") : false;

        //如果没有用户ID,默认是信贷经理以上管理员查看
        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        $sort_order = I('order_by','enterprise_id').' '.I('sort');
        $count = M('user_enterprise')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        $show = $Page->show();

        //获取企业列表
        $entList = $clientLogic->getEntList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $this->assign('entList',$entList);

        I('user_id') && $this -> assign("user_id", I('user_id'));
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * 企业信息添加
     */
    public function addEnterprise(){
        if(Request::instance()->isPost() && I('is_ajax') == 1){
            $data = Request::instance() -> post();
            $validate = Loader::validate('UserEnterprise');
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

            $data['founding_time'] = strtotime($data['founding_time']); //转化成时间戳
            $data['addtime'] = time();
            $data['work_phone'] = $data['work_qh'] . '-' . $data['work_phone'];
            $data['con_workmobile'] = $data['con_workmobile_qh'] . '-' . $data['con_workmobile'];
            $data['con_homemobile'] = $data['con_homemobile_qh'] . '-' . $data['con_homemobile'];

            //执行添加
            $ent_id = M('user_enterprise') -> add($data);
            if(!$ent_id){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '操作失败',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            //股权信息开始入库
            $ClientLogic = new ClientLogic();
            if(!empty($data['stock'])){
               $stock = $ClientLogic -> dealStock($data['stock']);
               foreach($stock as $v){
                   $v['ent_id'] = $ent_id;
                   $stock_id[] = M('user_stockholder') -> add($v);
               }
            }
            if(!empty($stock_id)){
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('type' => 1, 'ent_id' => $ent_id),
                );
                $this->ajaxReturn($return_arr);
            }
            //执行添加完成之后,将ID返回到个人信息添加页面
            //$this -> redirect('Admin/Users/add_user', array('ent_id' => $ent_id, 'type' => 2), '企业信息添加成功, 页面跳转中...');
        }
        $key = I('get.key');
        $key && $this -> assign('key', $key);
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        $this->assign('province',$province);
        I('get.user_id') && $this -> assign("user_id", I('user_id'));
        return $this -> fetch();
    }

    /**
     * 企业信息编辑
     */
    public function editEnterprise(){
        $ClientLogic = new ClientLogic();
        if(Request::instance()->isPost() && I('is_ajax') == 1){
            $data = Request::instance() -> post();
            $validate = Loader::validate('UserEnterprise');
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
            //组装数据
            $data['founding_time'] = strtotime($data['founding_time']); //转化成时间戳
            $data['addtime'] = time();
            $data['work_phone'] = $data['work_qh'] . '-' . $data['work_phone'];
            $data['con_workmobile'] = $data['con_workmobile_qh'] . '-' . $data['con_workmobile'];
            $data['con_homemobile'] = $data['con_homemobile_qh'] . '-' . $data['con_homemobile'];
            //执行修改
            $ent_id = M('user_enterprise') -> where(array('enterprise_id'=>$data['enterprise_id'])) -> update($data);
            if(!$ent_id){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '操作失败',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            //股权信息开始入库
            $ClientLogic = new ClientLogic();
            //先删除再添加
            M('user_stockholder') -> where(array('ent_id' => $data['enterprise_id'])) -> delete();
            if(!empty($data['stock'])){
                $stock = $ClientLogic -> dealStock($data['stock']);
                foreach($stock as $v){
                    $v['ent_id'] = $data['enterprise_id'];
                    $stock_id[] = M('user_stockholder') -> add($v);
                }
            }
            if(!empty($stock_id)){
                $return_arr = array(
                    'status' => 1,
                    'msg' => '操作成功',
                    'data' => array('type' => 1, 'ent_id' => $ent_id),
                );
                $this->ajaxReturn($return_arr);
            }
            //执行添加完成之后,将ID返回到个人信息添加页面
            //$this -> redirect('Admin/Users/add_user', array('ent_id' => $ent_id, 'type' => 2), '企业信息添加成功, 页面跳转中...');
        }
        $ent_id = I('ent_id/d');
        if(!$ent_id){
            $this -> error('参数错误', U("Admin/Users/index"));
        }
        //查询企业信息
        $ent_info = $ClientLogic -> getEntDetail($ent_id);
        if(!$ent_info){
            $this -> error('企业信息不存在');
        }
        if($ent_info){
            $work_phone_arr = explode('-', $ent_info['work_phone']);
            $ent_info['work_qh'] = $work_phone_arr[0];
            $ent_info['work_phone'] = $work_phone_arr[1];
            $con_workmobile_arr = explode('-', $ent_info['con_workmobile']);
            $ent_info['con_workmobile'] = $con_workmobile_arr[0];
            $ent_info['con_workmobile_qh'] = $con_workmobile_arr[1];
            $con_homemobile_arr = explode('-', $ent_info['con_homemobile']);
            $ent_info['con_homemobile'] = $con_homemobile_arr[0];
            $ent_info['con_homemobile_qh'] = $con_homemobile_arr[1];
            //  获取省份
            $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
            //  获取城市
            $city =  M('region')->where(array('parent_id'=>$ent_info['reg_province'],'level'=>2))->select();
            //  获取地区
            $area =  M('region')->where(array('parent_id'=>$ent_info['reg_city'],'level'=>3))->select();
            $this -> assign('province',$province);
            $this -> assign('city', $city);
            $this -> assign('area', $area);
            $this -> assign('enterprise', $ent_info);
            $stock =  $ClientLogic -> getStockholderInfo($ent_info['enterprise_id']);
            if($stock){
                $this -> assign('stock', $stock);
            }
        }
        $key = I('key') ? I('key') : '';
        if(!empty($key)){
            $this -> assign('key', $key);
        }
        return $this -> fetch();
    }

    /**
     * 门店列表
     * @return mixed
     */
    public function storeList(){
        $user_id = I('user_id');
        $this -> assign('user_id', $user_id);
        return $this -> fetch();
    }

    /**
     * 获取门店信息列表
     */
    public function ajaxStoreList(){
        $clientLogic = new ClientLogic();
        // 搜索条件
        $condition = array();
        $keyType = I("keytype");
        $keywords = I('keywords','','trim');

        $store_name =  ($keyType && $keyType == 'store_name') ? $keywords : I('store_name','','trim');
        $store_name ? $condition['store_name'] = array('like', "%$store_name%") : false;

        //$condition['order_prom_type'] = array('lt',5);
        $store_no = ($keyType && $keyType == 'store_no') ? $keywords : I('store_no') ;
        $store_no ? $condition['store_no'] = trim($store_no) : false;

        I('status') != '' ? $condition['status'] = I('status') : 1;

        //如果没有用户ID,默认是信贷经理以上管理员查看
        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        $sort_order = I('order_by','DESC').' '.I('sort');
        $count = M('user_store')->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        $show = $Page->show();

        //获取门店列表
        $storeList = $clientLogic->getStoreList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        $this->assign('storeList',$storeList);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('pager',$Page);
        return $this->fetch();
    }

    /**
     * 添加门店
     * @return mixed
     */
    public function add_store(){
        $user_id = I('user_id') ? I('user_id') : 0;
        $this -> assign('user_id', $user_id);
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        $this->assign('province',$province);
        //获取产业类型
        $industries = M('user_industries') -> field('id, name') -> where('status = 1') -> select();
        $this -> assign("industriesType", $industries);
        return $this -> fetch();
    }

    public function add_store_handle(){
        $clientLogic = new ClientLogic();
        $userStore = new \app\admin\model\UserStore();
        $data = Request::instance() -> post();
        //判断是哪个入口进来的
        if(isset($data['search_user_id']) && $data['search_user_id'] > 0){
            $data['user_id'] = $data['search_user_id'];
        }
        //生成门店编号
        $industries = array(
            'id' => $data['industries'],
            'province_id' => $data['industries_province'],
            'city_id' => $data['industries_city'],
        );
        $data['store_no'] = $clientLogic -> create_clientNo($industries, 3, 4);
        $data['addtime'] = time();
        //查询客户姓名 编号
        $userInfo = M('users') -> field('name, client_no, store_num') -> where("user_id=".$data['user_id']) -> find();
        $data['user_name'] = $userInfo['name'];
        $data['user_no'] = $userInfo['client_no'];
        $data['established'] = strtotime($data['established']);
        $data['rent_begintime'] = strtotime($data['rent_begintime']);
        $data['rent_endtime'] = strtotime($data['rent_endtime']);
        $data['store_mobile'] = $data['store_mobile_qh'] . '-' . $data['store_mobile'];
        if($data['property_right'] == 1){
            $data['rent_begintime'] = 0;
            $data['rent_endtime'] = 0;
            $data['rent'] = 0;
            $data['rent_long'] = 0;
            $data['rent_note'] = 0;
        }
        //执行添加
        $store_id = M('user_store') -> add($data);
        if(!$store_id){
            $this -> error("添加失败, 请重新输入!");
        }
        //插入图集
        $userStore -> afterSave($store_id);
        //更新门店数量
        Db::name('users') -> where(array('user_id'=>$data['user_id'])) -> update(array('store_num'=>($userInfo['store_num']+1)));
        //开始写入关系表
        $rel['user_id'] = $data['user_id'];
        $entinfo = M('user_enterprise') -> field('enterprise_id') -> where('user_id='.$data['user_id']) -> find();
        $rel['ent_id'] = $entinfo['enterprise_id'] ? : 0;
        $rel['store_id'] = $store_id;
        $res = M('user_relation') -> add($rel);

        if($res){
            $clientLogic -> userActionLog($data['user_id'], '新增客户门店操作', '增添客户门店信息');
            $this -> success("添加成功");
        }else{
            $this -> error('添加失败');
        }
    }

    /**
     * 编辑门店
     */
    public function edit_store(){
        if(Request::instance()->isPost()){
            $clientLogic = new ClientLogic();
            $userStore = new \app\admin\model\UserStore();
            $data = Request::instance() -> post();
            //查看是否有修改权限(审核状态过程)
            $user_status = M('users') -> field('is_approval') -> where(array('user_id'=>$data['user_id'])) -> find();
            if($user_status['is_approval'] != 0){
                $this -> error('客户信息已进行提交操作,不能修改');
            }
            if(!$data['store_id']){
                $this -> error("参数错误");
            }
            $data['addtime'] = time();
            $data['established'] = strtotime($data['established']);
            $data['rent_begintime'] = strtotime($data['rent_begintime']);
            $data['rent_endtime'] = strtotime($data['rent_endtime']);
            $data['store_mobile'] = $data['store_mobile_qh'] . '-' . $data['store_mobile'];
            if($data['property_right'] == 1){
                $data['rent_begintime'] = 0;
                $data['rent_endtime'] = 0;
                $data['rent'] = 0;
                $data['rent_long'] = 0;
                $data['rent_note'] = '';
            }
            //执行添加
            $res = M('user_store') -> where("store_id=".$data['store_id']) -> update($data);
            $userStore -> afterSave($data['store_id']);
            if($res){
                $this -> success("修改成功");
            }else{
                $this -> error("添加失败, 请重新尝试修改!");
            }
        }
        $ClientLogic = new ClientLogic();
        $store_id = I('store_id') ? I('store_id') : 0;
        $store = $ClientLogic -> getStoreDetail($store_id);
        $store_mobile_arr = explode('-', $store['store_mobile']);
        $store['store_mobile_qh'] = $store_mobile_arr[0];
        $store['store_mobile'] = $store_mobile_arr[1];
        //var_dump($store);exit;
        !$store_id && $this -> error("参数错误");
        //  获取省份
        $province = M('region')->where(array('parent_id'=>0,'level'=>1))->select();
        //  获取城市
        $city =  M('region')->where(array('parent_id'=>$store['province'],'level'=>2))->select();
        //  获取地区
        $area =  M('region')->where(array('parent_id'=>$store['city'],'level'=>3))->select();
        $this -> assign('province',$province);
        $this -> assign('city', $city);
        $this -> assign('area', $area);
        $this -> assign('store', $store);
        $StoreImages = M("StoreImages")->where('store_id =' . $store_id)->select();
        $this->assign('StoreImages', $StoreImages);  // 门店相册
        return $this -> fetch();
    }

    /**
     * 删除单个门店
     */
    public function delete_store(){
        $store_id = I('post.store_id/d', 0);
        $ClientLogic = new ClientLogic();
        $del = $ClientLogic -> delStore($store_id);
        $this -> ajaxReturn($del);
    }

    /**
     * 产业类型 -- 省份
     */
    public function get_industries_province(){
        $industries_id = I('get.industries_id/d');
        $condition = array(
            'ind_id' => $industries_id,
            'status' => 1,
        );
        $data = M('user_industry_province')-> field("province_id, province") -> where($condition)->select();
        $html = '';
        if($data){
            foreach($data as $h){
                $html .= "<option value='{$h['province_id']}'>{$h['province']}</option>";
            }
        }
        if(empty($html)){
            echo '0';
        }else{
            echo $html;
        }
    }

    /**
     * 产业类型 -- 城市
     */
    public function get_industries_city(){
        $p_id = I('get.p_id');
        $condition = array(
            'p_id' => $p_id,
            'status' => 1,
        );
        $data = M('user_industry_city')-> field("city_id, city_name") -> where($condition)->select();
        $html = '';
        if($data){
            foreach($data as $h){
                $html .= "<option value='{$h['city_id']}'>{$h['city_name']}</option>";
            }
        }
        if(empty($html)){
            echo '0';
        }else{
            echo $html;
        }
    }

    /**
     * 搜索用户名
     * 搜索条件 手机/客户编号/姓名
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

    /**
     * 门店搜索导出操作
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function export_store()
    {
        //搜索条件
        $user_id = I('user_id');
        $status =  I('status');
        $store_ids = I('store_ids');
        $where = array();//搜索条件
        if($status){
            $where['status'] = $status;
        }
        if($user_id){
            $where['user_id'] = $user_id;
        }
        if($store_ids){
            $where['store_id'] = ['in', $store_ids];
        }
        $storeList = Db::name('user_store')->field("*,FROM_UNIXTIME(addtime,'%Y-%m-%d') as create_time")->where($where)->order('user_id')->select();
        $strTable ='<table width="1000" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">序号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;width:120px;">门店编号</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">门店权属人</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="200">门店名称</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">门店号码</td>';
        $strTable .= '<td style="text-align:center;font-size:12px;" width="*">产权</td>';
        $strTable .= '</tr>';
        if(is_array($storeList)){
            $property_right = array(1=>'自由', 2=>'租赁');
            foreach($storeList as $k=>$val){
                $strTable .= '<tr>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['store_id'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['store_no'].' </td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['user_name'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['store_name'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'.$val['store_mobile'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">'. $property_right[$val['property_right']] .'</td>';
                $strTable .= '</tr>';
            }
        }
        $strTable .='</table>';
        unset($storeList);
        downloadExcel($strTable,'客户门店列表明细');
        exit();
    }

    /**
     * 客户详情信息
     * @param $user_id 客户ID
     * @return mixed
     */
    public function detail($user_id){
        $clientLogic = new ClientLogic();
        $userInfo = $clientLogic -> getClientInfo($user_id);
        //配偶信息
        $spouse = M('user_spouse') -> where('user_id='.$user_id) -> find();
        //企业信息
        $ent = $clientLogic -> getEnterpriseInfo($user_id);
        $this -> assign('ent', $ent);
        //门店信息
        $store = $clientLogic -> getUserStoreList($user_id);

        //查看是否已添加审批结论
        $credit = M('credit_conclusion') -> where('user_id='.$user_id) -> find();

        if(!empty($credit)){
            $credit['loan_con'] = unserialize($credit['loan_con']);
            $credit['money_plan'] = unserialize($credit['money_plan']);
            $credit['credit'] = number_format(($credit['credit']/10000),2,",",".");
            $this -> assign('credit', $credit);
        }

        $this -> assign('userInfo', $userInfo);
        $this -> assign('spouse', $spouse);
        $this -> assign('store', $store);
        $this -> assign('user_id',$user_id);
        $this -> assign('admin_id', session('admin_id'));
        $this -> assign('allAdminName', getAllAdmin());
        $this->assign('empty','<span class="empty">没有数据</span>');
        return $this -> fetch();
    }

    /**
     * 客户详情 -- 门店信息（ajax）
     * @return mixed
     */
    public function store_list(){
        $clientLogic = new ClientLogic();

        $store_id = I('store_id');
        //if(I('is_ajax') != 1){
        //    $this -> ajaxReturn('参数错误');
        //}
        //查询对应门店
        $store = M('user_store') -> where('store_id='.$store_id) -> find();

        $store['address2'] = $clientLogic->getAddressName($store['province'],$store['city'],$store['district']);
        $store['address2'] = $store['address2'].$store['address'];
        $this -> assign('store', $store);
        return $this -> fetch();
    }

    /**
     * 客户信息授信申请动作
     */
    public function set_approval(){
        $ClientLogic = new ClientLogic();
        if (Request::instance()->isAjax() && Request::instance()->isPost() && (I('is_ajax') ==1) ){
            $condition['is_approval'] = I('is_approval');
            $condition['update_time'] = time();
            $user_id = intval(I('user_id'));
            $admin_id = I('admin_id');
            if($user_id < 0){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '未知参数',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }
            //查看门店数量(若无门店, 禁止提交)
            $user_store = $ClientLogic -> getUserStoreNum($user_id);
            if($user_store <= 0){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '提交失败, 该用户先没有门店, 请先添加门店',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            $res = M('users') -> where('user_id='.$user_id) -> save($condition);
            if(!$res){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '提交失败, 请尝试提交',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }
            $return_arr = array(
                'status' => 1,
                'msg' => '提交成功, 请耐心等待审核反馈',
                'data' => '/index.php/Admin/Users/index',
            );
            $this->ajaxReturn($return_arr);
        }
    }

    /**
     * 客户日志
     * @return mixed
     */
    public function user_log(){
        $timegap_begin = urldecode(I('timegap_begin'));
        $timegap_end = urldecode(I('timegap_end'));
        $begin = strtotime($timegap_begin);
        $end = strtotime($timegap_end);
        $condition = array();
        $log =  M('user_action');
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

    /**
     * 客户添加 -- 企业反显
     */
    public function getEntInfo(){
        $html = '';
        $id = I('post.id') ? I('post.id') : 0;
        if(empty($id) || $id == 0){
            $this->ajaxReturn(0);
        }
        //$ent_info = M('user_enterprise') -> where("enterprise_id in(".$ids.")") -> select();
        $ent_info = M('user_enterprise') -> where("enterprise_id", $id) -> find();
        /**
        foreach($ent_info as $k=>$v){
            $html .= "<tr class='trSelected'>";
	        $html .= "<td align='left' axis='col3'><div style='text-align: left; width: 200px;' class=''>{$v['name']}</div></td>";
            $html .= "<td align='left' axis='col3'><div style='text-align: left; width: 100px;' class=''>{$v['legal_person']}</div></td>";
            $html .= "<td align='center' abbr='article_time' axis='col6' class=''><div style='text-align: center; width: 250px;'>";
            $html .= "<a class='btn green' href='javascript:void(0);' onclick=\"editEntInfo({$v['enterprise_id']})\"><i class=\"fa fa-list-alt\"></i>查看</a>&nbsp;&nbsp;&nbsp;&nbsp;";
            $html .= "<a class='btn red' href='javascript:void(0);' id='ent-{$v['enterprise_id']}' onclick=\"del_entinfo({$v['enterprise_id']})\"><i class=\"fa fa-trash-o\"></i>删除</a>";
            $html .= "</div></td></tr>";
        }
         */
        $html .= "<tr class='trSelected'>";
        $html .= "<input type='hidden' name='ent_ids[]' value='{$ent_info['enterprise_id']}'>";
        $html .= "<td align='left' axis='col3'><div style='text-align: left; width: 200px;' class=''>{$ent_info['name']}</div></td>";
        $html .= "<td align='left' axis='col3'><div style='text-align: left; width: 100px;' class=''>{$ent_info['legal_person']}</div></td>";
        $html .= "<td align='center' abbr='article_time' axis='col6' class=''><div style='text-align: center; width: 250px;'>";
        $html .= "<a class='btn green' href='javascript:void(0);' onclick=\"editEntInfo({$ent_info['enterprise_id']})\"><i class=\"fa fa-list-alt\"></i>查看</a>&nbsp;&nbsp;&nbsp;&nbsp;";
        $html .= "<a class='btn red' href='javascript:void(0);' id='ent-{$ent_info['enterprise_id']}' onclick=\"del_entinfo({$ent_info['enterprise_id']})\"><i class=\"fa fa-trash-o\"></i>删除</a>";
        $html .= "</div></td></tr>";
        echo $html;
    }

    /**
     * 客户添加 -- 企业删除动作
     */
    public function del_entinfo(){
        $id = I('post.id');
        $res = M('user_enterprise') -> where('enterprise_id='.$id) -> delete();
        if($res){
            echo 1;
        }else{
            echo 0;
        }
    }

    /**
     * 手机号码唯一性验证
     */
    public function checkMobile(){
        $mobile = trim(I('mobile'));
        $m = Db::name('users')->field('mobile')->where('mobile',$mobile)->find();
        if(!$m){
            $m = Db::name('user_store_clerk')->field('mobile')->where('mobile',$mobile)->find();
            if(!$m) {
                $this -> ajaxReturn(1); //可以注册
            }
            $this -> ajaxReturn(2);
        }
        $this -> ajaxReturn(2);
    }

    public function tabulation(){
        return $this -> fetch();
    }

}