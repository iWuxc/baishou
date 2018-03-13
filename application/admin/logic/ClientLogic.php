<?php

/**
 * 信贷客户模型扩展类
 * Author: iWuxc
 * Date: 2017-12-04
 */

namespace app\admin\logic;

use think\Model;
use think\Db;

class ClientLogic extends Model
{

    /**
     * @param $condition
     * @param string $order
     * @param int $start
     * @param int $page_size
     * @param bool $is_all
     * @return mixed
     */
    public function getClientList($condition,$order='',$start=0,$page_size=10, $is_all = true){
        if($is_all){
            $res = M('Users')
                -> alias('u')
                -> join('__USER_DETAIL__ ud', 'u.user_id=ud.user_id', 'LEFT')
                -> field('u.user_id, u.client_no,u.name,u.xd_id,u.fk_id,u.sex,u.type,u.mobile,ud.credential_type,ud.id_number,ud.home_address,u.store_num,u.reg_time,u.is_approval')
                -> where($condition)
                -> order($order)
                ->limit("$start,$page_size")
                ->select();
        }else{
            $res = M('Users')
                -> alias('u')
                -> join('__USER_DETAIL__ ud', 'u.user_id=ud.user_id', 'LEFT')
                -> field('u.user_id, u.client_no,u.name,u.xd_id,u.fk_id,u.sex,u.type,u.mobile,ud.credential_type,ud.id_number,ud.home_address,u.store_num,u.reg_time,u.is_approval')
                -> where($condition)
                -> order($order)
                ->select();
        }
        return $res;
    }

    /**
     * 获取客户信息详情
     * @param $user_id
     * @return mixed
     */
    public function getClientInfo($user_id)
    {
        $user = M('users')->alias('u')
            ->join('__USER_DETAIL__ ud','u.user_id=ud.user_id','LEFT')
            ->where("u.user_id = $user_id")
            ->find();
        //户口城市
        $user['account_address2'] = $this->getAddressName($user['account_province'],$user['account_city'],$user['account_district']);
        $user['account_address2'] = $user['account_address2'].$user['account_address'];
        //家庭城市
        $user['home_address2'] = $this->getAddressName($user['home_province'],$user['home_city'],$user['home_district']);
        $user['home_address2'] = $user['home_address2'].$user['home_address'];
        //收件地址
        $user['mails_address2'] = $this->getAddressName($user['mails_province'],$user['mails_city'],$user['mails_district']);
        $user['mails_address2'] = $user['mails_address2'].$user['mails_address'];
        return $user;
    }

    /**
     * 获取企业列表
     * @param $condition
     * @param string $order
     * @param int $start
     * @param int $page_size
     * @return mixed
     */
    public function getEntList($condition,$order='',$start=0,$page_size=20){
        $res = M('user_enterprise')->where($condition)->limit("$start,$page_size")->order($order)->select();
        return $res;
    }

    /**
     * 获取客户企业信息
     * @param $user_id
     * @return mixed
     */
    public function getEnterpriseInfo($user_id){
        $enterprise = M('user_enterprise')->where("user_id = $user_id")->select();
        //查询企业股权信息
        foreach ($enterprise as $key => $val){
            $enterprise[$key]['stockHolder'] = $this -> getStockholderInfo($val['enterprise_id']);
        }
        return $enterprise;
    }

    /*
     * 企业信息详情
     */
    public function getEntDetail($ent_id){
        $enterprise = M('user_enterprise')->where("enterprise_id = $ent_id")->find();
        return $enterprise;
    }

    /**
     * 获取企业信息股权
     * @param $ent_id
     * @return mixed
     */
    public function getStockholderInfo($ent_id){
        $stock = M('user_stockholder') -> where('ent_id='.$ent_id) -> select();
        if($stock){
            return $stock;
        }
        return false;
    }

    /**
     * 获取某个客户相关门店
     * @param $user_id
     * @return mixed
     */
    public function getUserStoreList($user_id){
        $store = M('user_store') -> where('user_id='.$user_id) -> select();
        $storeInfo = array();
        foreach($store as $v){
            $v['address2'] = $this->getAddressName($v['province'],$v['city'],$v['district']);
            $v['address2'] = $v['address2'].$v['address'];
            $storeInfo[] = $v;
        }
        return $storeInfo;
    }

    /**
     * 获取某个用户名下门店的数量
     * @param $user_id
     * @return mixed
     */
    public function getUserStoreNum($user_id){
        $count = M('user_relation') -> where('user_id='.$user_id) -> count();
        return $count;
    }

    /**
     * 获取单个门店的具体信息
     * @param $store_id
     * @return mixed
     */
    public function getStoreDetail($store_id){
        $info = M('user_store') -> where(array('store_id'=>$store_id)) -> find();
        return $info;
    }

    /**
     * @param $condition 搜索条件
     * @param string $order 排序方式
     * @param int $start limit开始行
     * @param int $page_size 获取数量
     * @return mixed
     */
    public function getStoreList($condition,$order='',$start=0,$page_size=20){
        $res = M('user_store')->where($condition)->limit("$start,$page_size")->order($order)->select();
        return $res;
    }

    /**
     * 获取地区名字
     * @param int $p
     * @param int $c
     * @param int $d
     * @return string
     */
    public function getAddressName($p=0,$c=0,$d=0){
        $p = M('region')->where(array('id'=>$p))->field('name')->find();
        $c = M('region')->where(array('id'=>$c))->field('name')->find();
        $d = M('region')->where(array('id'=>$d))->field('name')->find();
        return $p['name'].','.$c['name'].','.$d['name'].',';
    }

    /**
     * 客户编号生成规则算法
     * @param array $abbr 编号前边的城市地区简称
     * @param $type
     * @param $num 填充的位数
     * @return string
     */
    public function create_clientNo($abbr = array(), $type='', $num){

        if(empty($abbr) || $type == ''){
            return "参数错误";
        }
        //判断是客户编号还是门店编号
        if($type == 3){
            $MaxId = $this -> getMaxStoreId();
        }else{
            $MaxId = $this -> getMaxUserId();
        }
        if($MaxId < 0){
            return false;
        }
        $sortNo = str_pad((intval($MaxId)+1), $num, '0', STR_PAD_LEFT);
        $abbrType = $this -> getAbbr($abbr['id']);
        $str_abbr = M('user_industry_city') -> field('p_abbr,city_abbr') -> where('city_id',$abbr['city_id']) -> find();
        if($type == 3){
            $clientNo = $str_abbr['p_abbr'] . $str_abbr['city_abbr'] . $sortNo;
        }else{
            $clientNo = $str_abbr['p_abbr'] . $str_abbr['city_abbr'] . $abbrType . "0" . $type . $sortNo;
        }

        return $clientNo;
    }

    /**
     * 获取客户最大的Id号
     * @return mixed
     */
    public function getMaxUserId(){
        $MaxId = M('Users') -> field('Max(user_id) as MaxId') -> find();
        if($MaxId < 0){
            return $MaxId = 0; // 默认为第一个用户
        }
        return $MaxId['MaxId'];
    }

    /**
     * 获取门店的最大编号
     * @return int
     */
    public function getMaxStoreId(){
        $maxId = M('user_store') -> field('Max(store_id) as MaxId') -> find();
        if($maxId < 0){
            return $MaxId = 0; //默认为第一个门店
        }
        return $maxId['MaxId'];
    }

    /**
     * 获取产业类型编号
     * @param $id
     * @return int
     */
    public function getAbbr($id){
        $abbr = M('user_industries') -> field('abbr') -> find();
        if(empty($abbr)){
            return $abbr;
        }
        return $abbr['abbr'];
    }

    /**
     * 生成随机密码
     * @param int $len 生成的密码的长度
     * @param string $keyword 密码关键字
     * @return bool|mixed|string
     */
    public function create_rand_password($len = 8, $keyword = '') {
        if (strlen($keyword) > $len) {//关键字不能比总长度长
            return false;
        }
        $str = '';
        $chars = 'abcdefghijkmnpqrstuvwxyz23456789ABCDEFGHIJKMNPQRSTUVWXYZ'; //去掉1跟字母l防混淆
        if ($len > strlen($chars)) {//位数过长重复字符串一定次数
            $chars = str_repeat($chars, ceil($len / strlen($chars)));
        }
        $chars = str_shuffle($chars); //打乱字符串
        $str = substr($chars, 0, $len);
        if (!empty($keyword)) {
            $start = $len - strlen($keyword);
            $str = substr_replace($str, $keyword, mt_rand(0, $start), strlen($keyword)); //从随机位置插入关键字
        }
        return $str;
    }

    /**
     * 截取密码
     * @param $id_number
     * @param int $length
     * @return bool|string
     */
    public function get_passwd($id_number, $length = 0){
        if($length == 0){
            return false;
        }
        $len = strlen($id_number);
        if($len < $length){
            return $id_number;
        }else{
            return substr($id_number, -$length);
        }

    }

    /**
     * 企业信息股权数组操作
     * @param $stock
     * @return array
     */
    public function dealStock($stock){
        $stock_array = array();
        if(!empty($stock)){
            $investor   =   $stock['investor'];
            $invest_amo =   $stock['invest_amo'];
            $percentage =   $stock['percentage'];
            $relation   =   $stock['relation'];
            $sto_id     =   $stock['sto_id'];
            for($i=0; $i<count($investor); $i++){
                $stock_array[$i]['investor'] = $investor[$i];
                $stock_array[$i]['invest_amo'] = $invest_amo[$i];
                $stock_array[$i]['percentage'] = $percentage[$i];
                $stock_array[$i]['relation'] = $relation[$i];
                $stock_array[$i]['addtime'] = time();
                $stock_array[$i]['sto_id'] = $sto_id[$i];
            }
        }

        return $stock_array;
    }

    /**
     * 删除用户信息
     * @param $user_id
     * @return array
     */
    public function delUser($user_id){
        $user = M('users') -> where(array('user_id'=>$user_id)) -> find();
        if(empty($user)){
           return [
               'status' => -1,
                'msg' => '用户信息不存在'
           ];
        }
        //判断用户审核状态
        if($user['is_approval'] != 0){
            return ['status' => -1, 'msg' => '该用户已提交审核, 不能删除'];
        }
        //先查出数据关系
        $info = M('users') -> where(array('user_id' => $user_id)) -> find();
        if($info){
            //删除门店
            $r1 = M('user_store') -> where(array('user_id' => $user_id)) -> delete();
            //找到所有名下企业信息
            $entInfo = M('user_enterprise') -> field('enterprise_id') -> where(array('user_id' => $user_id)) -> select();
            if($entInfo){
                $ent_ids = '';
                foreach ($entInfo as $key => $val){
                    $ent_ids .= $val['enterprise_id'];
                }
                //删除企业股权关系
                $r2 = M('user_stockholder') -> where("ent_id in (".trim($ent_ids,',').")") -> delete();
            }
            //删除企业
            $r3 = M('user_enterprise') -> where(array('user_id' => $user_id)) -> delete();
            //删除关系表
            $r4 = M('user_relation') -> where(array('user_id' => $user_id)) -> delete();
            //删除配偶信息
            $r5 = M('user_spouse') -> where(array('user_id' => $user_id)) -> delete();
            //删除个人详情信息
            $r6 =M('user_detail') -> where(array('user_id' => $user_id)) -> delete();
            //删除个人信息
            $r7 = M('users') -> where(array('user_id' => $user_id)) -> delete();
            if(empty($r1) && empty($r2) && empty($r3) && empty($r4) && empty($r5) && empty($r6) && empty($r7)){
                return ['status' => -1, 'msg' => '用户删除失败'];
            }
        }
        return ['status' => 1, 'msg' => '用户删除成功'];
    }

    /**
     * 删除某个门店
     * @param $store_id
     * @return array
     */
    public function delStore($store_id){
        $data = array();
        $storeInfo = M('user_store') -> field('user_id,imgs') -> where(array('store_id'=>$store_id)) -> find();
        //查询用户信息
        $userInfo = M('users') -> field('is_approval,store_num') -> where('user_id='.$storeInfo['user_id']) -> find();

        //判断状态选择删除方式(未审核:彻底删除   审核通过: 进行假删除)
        if($userInfo['is_approval'] == 0){
            //查询出所有的图片路径
            $img_urls = M('store_images') -> where(array('store_id' => $store_id)) -> select();
            foreach ($img_urls as $key => $val){
                @unlink('.'.$val['image_url']);
            }
            $r1 = M('store_images') -> where(array('store_id'=>$store_id)) -> delete();
            $del_store = M('user_store') -> where(array('store_id' => $store_id)) -> delete();
            @unlink(".".$storeInfo['imgs']);
            //修改用户门店数量
            if($del_store){
                Db::name('users') -> where('user_id='.$storeInfo['user_id']) -> update(array('store_num'=>($userInfo['store_num']-1)));
            }
        } else {
            $data['status'] = 2;
            $del_store = M('user_store') -> where(array('store_id' => $store_id)) -> update($data);
        }

        if($del_store){
            return ['status' => 1, 'msg' => '删除成功'];
        }else{
            return ['status' => 1, 'msg' => '删除失败'];
        }
    }

    /**
     * 客户信息日志操作
     * @param $user_id
     * @param $action
     * @param string $note
     * @return mixed
     */
    public function userActionLog($user_id,$action,$note=''){
        $user = Db::name('users')->where(array('user_id'=>$user_id))->find();
        $data['user_id'] = $user_id;
        $data['user_no'] = $user['client_no'];;
        $data['action_user'] = session('admin_id');
        $data['action_note'] = $note;
        $data['user_status'] = $user['is_approval'];
        $data['log_time'] = time();
        $data['status_desc'] = $action;
        return Db::name('user_action')->add($data);//客户操作记录
    }

    /**
     * 获取管理员名称
     * @param $admin_id
     * @return mix
     */
    public function getAdminUser($admin_id){
        if(!$admin_id){
            return '';
        }
        $name = M('admin') -> where(array('admin_id'=>$admin_id)) -> value('user_name');
        return $name;
    }

    /*
     * 获取客户审批结论状态
     */
    public function getCredit($user_id){
        $credit = M('credit_conclusion') -> where('user_id',$user_id) -> find();
        if(!$credit){
            return false;
        }
        return $credit['status'];
    }

    /**
     * 用户密码临时数据,在审批结论审批通过的时候进行发送并删除
     * @param $user_id
     * @param $password
     * @param $mobile
     * @return bool
     */
    public function setTempPasswd($user_id,$password,$mobile){
        if(!$user_id || empty($user_id)){
            return false;
        }
        $param['user_id'] = $user_id;
        $param['password'] = $password;
        $param['mobile'] = $mobile;
        $param['add_time'] = time();
        $res = M('user_temp_password') -> add($param);
        return $res;
    }

}