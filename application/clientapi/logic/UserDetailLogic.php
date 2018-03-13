<?php
/**
 * 用户详情管理
 * Author: iWuxc
 * time 2017-12-22
 */
namespace app\clientapi\logic;

use app\bossapi\controller\User;
use think\Db;
use think\Model;
use app\clientapi\model\Users;
use app\clientapi\model\UserStore;

class UserDetailLogic extends Model{

    protected $model;

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this -> model = new Users();
    }

    /**
     * 个人信息
     * @param $userid
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function userDetail($userid, $type){
        $education = array(1=>'专科以下',2=>'专科',3=>'本科',4=>'硕士以上');
        $credential_type = array(1=>'公民身份证',2=>'港澳台居民身份证',3=>'护照',4=>'其他');
        $marriage = array(1=>'已婚有子女',2=>'已婚无子女',3=>'未婚',4=>'其他');
        $userid = $this -> getUserId($userid, $type);
        //查询个人信息
        $userinfo = Db::name('users')
            -> alias('u')
            -> join('__USER_DETAIL__ ud', 'u.user_id=ud.user_id','LEFT')
            -> where('u.user_id',$userid)
            //-> field('u.name,u.sex,ud.education,ud.live_year,ud.work_seniority,ud.account_province,ud.account_city,ud.account_district,ud.account_address,ud.home_address,ud.mails_address,ud.home_tel,ud.company_tel,u.mobile,
            //ud.credential_type,ud.id_number,ud.marriage,ud.own_property,ud.own_vehicle,ud.personal_income,ud.total_asset,ud.home_income,ud.total_liabilities')
            -> field('u.name,u.sex,ud.account_province,ud.account_city,ud.account_district,ud.account_address,u.mobile,
            ud.credential_type,ud.id_number')
            -> find();
        if($userinfo){
            //$userinfo['education'] = $education[$userinfo['education']];
            $userinfo['credential_type'] = $credential_type[$userinfo['credential_type']];
            //$userinfo['marriage'] = $marriage[$userinfo['marriage']];
            $userinfo['hk_address'] = $this -> getAddressName($userinfo['account_province'],$userinfo['account_city'],$userinfo['account_district'],'-');
            unset($userinfo['account_province'],$userinfo['account_city'],$userinfo['account_district']);
        }
        //配偶信息
        //$user_spouse = Db::name('user_spouse')->where('user_id',$userid)
        //    ->field('sp_name,sp_education,sp_mobile,sp_credential_type,sp_id_number,work_unit,duty,sp_icnome')
        //    ->find();
        //if($user_spouse){
        //    $user_spouse['sp_education'] = $education[$user_spouse['sp_education']];
        //    $user_spouse['sp_credential_type'] = $credential_type[$user_spouse['sp_credential_type']];
        //
        //    $userinfo['sp_name'] = $user_spouse['sp_name'];
        //    $userinfo['sp_education'] = $user_spouse['sp_education'];
        //    $userinfo['sp_mobile'] = $user_spouse['sp_mobile'];
        //    $userinfo['sp_credential_type'] = $user_spouse['sp_credential_type'];
        //    $userinfo['sp_id_number'] = $user_spouse['sp_id_number'];
        //    $userinfo['work_unit'] = $user_spouse['work_unit'];
        //    $userinfo['sp_icnome'] = $user_spouse['sp_icnome'];
        //}
        return $userinfo;
    }

    public function userEnterprise($userid, $type){
        $credential_type = array(1=>'公民身份证',2=>'港澳台居民身份证',3=>'护照',4=>'其他');
        $userid = $this -> getUserId($userid, $type);
        $user_enterprise = Db::name('user_enterprise')
            ->where('user_id',$userid)
            ->field('enterprise_id,name,legal_person,business_no,reg_capital,work_phone,main_business,operating_years,reg_address')
            ->find();
        if($user_enterprise){
            //$user_enterprise['con_cretype'] = $credential_type[$user_enterprise['con_cretype']];
            //$user_enterprise['credential_type'] = $credential_type[$user_enterprise['credential_type']];
        }
        return $user_enterprise;
    }

    /**
     * 门店列表
     * @param $userid
     * @param $type
     * @param int $p
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function storeList($userid, $type, $p=1){
        $userStore = new UserStore();
        $userid = $this -> getUserId($userid, $type);
        $param['where']['user_id'] = $userid;
        $param['parameter'] = $p;
        $param['order'] = 'addtime desc';
        $list = $userStore -> getStoreList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                $val['mix_address'] = $this -> getAddressName($val['province'],$val['city'],$val['district'],'').$val['address'];
                unset($val['province'],$val['city'],$val['district'],$val['address']);
            }
        }
        return $list['list'];
    }

    /**
     * 门店详情
     * @param $store_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function storeDetail($store_id){
        $userStore = new UserStore();
        $info = $userStore -> detail($store_id);
        $info['mix_address'] = $this -> getAddressName($info['province'],$info['city'],$info['district'],'').$info['address'];
        unset($info['province'],$info['city'],$info['district'],$info['address']);
        return $info;
    }

    /**
     * 获取地区名字
     * @param int $p
     * @param int $c
     * @param int $d
     * @param $type
     * @return string
     */
    public function getAddressName($p=0,$c=0,$d=0,$type){
        $p = M('region')->where(array('id'=>$p))->field('name')->find();
        $c = M('region')->where(array('id'=>$c))->field('name')->find();
        $d = M('region')->where(array('id'=>$d))->field('name')->find();
        return $p['name'].$type.$c['name'].$type.$d['name'];
    }

    /**
     * 获取登录源
     * @param $userid
     * @param $type
     * @return mixed
     */
    private function getUserId($userid, $type){
        if($type == 2){
            $user_id = Db::name('user_store_clerk') -> where("clerk_id",$userid) -> value('user_id');
            return $user_id;
        }
        return $userid;
    }
}