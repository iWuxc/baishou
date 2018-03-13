<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\User;
use think\Db;

class UserLogic extends BaseLogic {

    protected $model;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new User();
    }

    /**
     * 数据列表
     * @param array $request
     * @return mixed
     */
    public function getList($request = array()){
        check_param('admin_id');
        if($request['role_id'] == 4){
            $param['where']['xd_id'] = $request['admin_id'];
        }
        $param['parameter'] = $request;
        $param['order'] = 'reg_time desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                if($val['type'] == 2){
                    $list['list'][$key]['user_name'] = Db::name('user_enterprise')->where('user_id',$val['user_id'])->value('name');
                }
            }
        }
        return $list['list'];
    }

    /**
     * 个人信息
     * @param array $request
     */
    public function userInfo($request = array()){
        check_param('user_id');
        $where['u.user_id'] = $request['user_id'];
        $user = Db::table('bs_users')->alias('u')
            ->field('u.name,u.sex,ud.education,ud.live_year,work_seniority,account_address,home_address,mails_address,home_tel,company_tel,u.mobile,
            credential_type,id_number,marriage')
            ->join('__USER_DETAIL__ ud','ud.user_id = u.user_id')
            ->where($where)
            ->find();
        if(!empty($user)){
            if($user['education'] == 1)  $user['education'] = '专科以下';
            elseif($user['education'] == 2) $user['education'] = '专科';
            elseif($user['education'] == 3) $user['education'] = '本科';
            elseif($user['education'] == 4) $user['education'] = '硕士以上';
            else $user['education'] = '--';

            if($user['credential_type'] == 1) $user['credential_type'] = '公民身份证';
            elseif($user['credential_type'] == 2)  $user['credential_type'] = '港澳台居民身份证';
            elseif($user['credential_type'] == 3)  $user['credential_type'] = '护照';
            elseif($user['credential_type'] == 4) $user['credential_type'] = '其他';
            else $user['credential_type'] = '--';

            if($user['marriage'] == 1) $marriage = '已婚有子女';
            elseif($user['marriage'] == 2) $marriage = '已婚无子女';
            elseif($user['marriage'] == 3) $marriage = '未婚';
            elseif($user['marriage'] == 4) $marriage = '其他';
            else $marriage = '--';
            $user['marriage'] = $marriage;
        }
        //配偶信息
        $user_spouse = Db::name('user_spouse')->where('user_id',$request['user_id'])
            ->field('sp_name,sp_education,sp_mobile,sp_credential_type,sp_id_number,work_unit,duty,sp_icnome')
            ->find();
        if(!empty($user_spouse)){
            if($user_spouse['sp_credential_type'] == 1) $sp_credential_type = '公民身份证';
            elseif($user_spouse['sp_credential_type'] == 2) $sp_credential_type = '港澳台居民身份证';
            elseif($user_spouse['sp_credential_type'] == 3) $sp_credential_type = '护照';
            elseif($user_spouse['sp_credential_type'] == 4) $sp_credential_type = '其他';
            else $sp_credential_type = '--';
            $user_spouse['sp_credential_type'] = $sp_credential_type;
        }
        $user['sp_name'] = $user_spouse['sp_name'];
        $user['sp_education'] = $user_spouse['sp_education'];
        $user['sp_mobile'] = $user_spouse['sp_mobile'];
        $user['sp_credential_type'] = $user_spouse['sp_credential_type'];
        $user['sp_id_number'] = $user_spouse['sp_id_number'];
        $user['work_unit'] = $user_spouse['work_unit'];
        $user['sp_icnome'] = $user_spouse['sp_icnome'];
        api_response_('1','',(object)$user);
    }

    /**
     * 企业信息
     * @param array $request
     */
    public function userEnterprise($request = array()){
        check_param('user_id');
        $where['user_id'] = $request['user_id'];
        $row =  $user_enterprise = Db::name('user_enterprise')->where($where)
            ->field('enterprise_id,name,reg_capital,reg_address,business_no,founding_time,main_business,operating_years,legal_person,credential_type,id_number,work_phone,
            mobile,control_people,con_cretype,con_idnumber,con_workmobile,con_mobile,con_homemobile,link_man,link_mobile,staff_number')
            ->find();
        if(!empty($row)){
            $row['founding_time'] = date('Y-m-d',$row['founding_time']);

            if($row['con_cretype'] == 1){
                $row['con_cretype'] = '公民身份证';
                $row['credential_type'] = '公民身份证';
            }elseif($row['con_cretype'] == 2){
                $row['con_cretype'] = '港澳台居民身份证';
                $row['credential_type'] = '港澳台居民身份证';
            }elseif($row['con_cretype'] == 3){
                $row['con_cretype'] = '护照';
                $row['credential_type'] = '护照';
            }elseif($row['con_cretype'] == 4){
                $row['con_cretype'] = '其他';
                $row['credential_type'] = '其他';
            }else{
                $row['con_cretype'] = '--';
                $row['credential_type'] = '--';
            }
        }
        api_response('1','',(object)$row);
    }
}