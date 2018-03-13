<?php
/**
 * 用户模型类
 * Author: iWuxc
 * time 2017-12-15
 */
namespace app\clientapi\model;

use think\Db;
use think\Model;

class Clerk extends Model {

    protected $table = 'user_store_clerk';

    /**
     * 获取用户信息
     * @param $userid
     * @param $type
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getSessInfo($userid){
        $info = Db::name($this->table)
            -> field('clerk_id, clerk_name, nickname, sex, mobile, head_img')
            -> where('clerk_id='.$userid)
            -> find();
        return $info;
    }

    /**
     * 性别修改
     * @param $userid
     * @param $sex
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateSex($userid, $sex){
        $result = M('user_store_clerk')
            -> where('clerk_id', $userid)
            -> update(['sex'=>$sex, 'update_time'=>time()]);
        return $result;
    }

    /**
     * 修改昵称
     * @param $userid
     * @param $nickname
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function updateNickname($userid, $nickname){
        $result = Db::name($this->table)
            -> where('clerk_id', $userid)
            -> update(['nickname'=>$nickname, 'update_time'=>time()]);
        return $result;
    }

    /**
     * 修改手机号
     * @param $userid
     * @param $mobile
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function modifyMobile($userid, $mobile){
        $result = Db::name($this->table)
            -> where('clerk_id', $userid)
            -> update(['mobile'=>$mobile, 'update_time'=>time()]);
        return $result;
    }

    /**
     * @param $userid
     * @param $passwd
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function modifyPasswd($userid, $passwd){
        $result = Db::name($this->table)
            -> where('clerk_id', $userid)
            -> update(['password'=>encrypt($passwd), 'update_time'=>time()]);
        return $result;
    }

    /**
     * 获取密码信息
     * @param $userid
     * @return mixed
     */
    public function getPasswd($userid){
        $result = Db::name($this->table) -> where('clerk_id', $userid) -> value('password');
        return $result;
    }

    /**
     * 获取店员数据
     * @param $userid
     * @return bool|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getClerkList($userid){
        $clerks_arr = array();
        $id_type = array(1=>"身份证", 2=>"港澳台居民身份证", 3=>"护照", 4=>"其他");
        $where['user_id'] = $userid;
        $where['is_lock'] = 1;
        $clerks = Db::table('bs_user_store_clerk') -> where($where) -> select();
        if($clerks){
            foreach($clerks as $v){
                $v['credential_type'] = $id_type[$v['credential_type']];
                $clerks_arr[] = $v;
            }
            return $clerks_arr;
        }else{
            return false; //无数据
        }
    }

    /**
     * 添加店员
     * @param string $clerk
     * @param $userid
     * @return array
     */
    public function addClerk($clerk = '', $userid){
        $id_type = array("身份证"=>1, "港澳台居民身份证"=>2, "护照"=>3, "其他"=>4);
        $data = array();
        $res = array();
        if(empty($clerk)){
            return array('info'=>"非法提交"); //非法提交
        }
        if($clerk['user_type'] == 2){
            return array('info'=>"用户类型不是boss");
        }elseif(empty($clerk['c_mobile'])){
            return array('info'=>"手机号必填");
        }elseif(empty($clerk['c_name'])){
            return array("info"=>"姓名必填");
        }elseif(empty($clerk['id_number'])){
            return array("info"=>"证件号码必填");
        }elseif(empty($clerk['belong_to_store'])){
            return array('info'=>"归属门店必选");
        }else{
            //开始组装数据
            $data['user_id'] = $userid;
            $data['clerk_name'] = $clerk['c_name'];
            $data['mobile'] = $clerk['c_mobile'];
            $data['nickname'] = $clerk['c_name'];
            $data['id_number'] = $clerk['id_number'];
            $data['add_time'] = time();
            $data['credential_type'] = $id_type[$clerk['credential_type']];
            $data['double_check'] = $clerk['double_check'];
            $data['belong_to_store'] = $clerk['belong_to_store'];
            $data['password'] = encrypt(get_passwd($clerk['id_number'], 6));
            $res = M('user_store_clerk') -> add($data);
            if($res){
                return array("info"=>"添加成功","id"=>$res);
            }else{
                return array("info"=>"添加失败");
            }
        }
    }

    /**
     * 修改店员归属门店信息
     * @param $clerk_id
     * @param $store_ids
     */
    public function modifyStore($clerk_id,$store_ids){
        $ids = array();
        if(!$clerk_id || empty($store_ids)){
            return false;
        }
        if(!empty($store_ids) && is_array($store_ids)){
            $ids = implode(",", $store_ids);
        }
        $where['belong_to_store'] = $ids ? $ids : $store_ids;
        $where['update_time'] = time();
        $res = M('user_store_clerk') -> where('clerk_id', $clerk_id) -> update($where);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 修改店员权限
     * @param $clerk_id
     * @param $double_check
     * @return bool
     */
    public function modifyAuth($clerk_id,$double_check){
        $ids = array();
        if(!intval($clerk_id) || !intval($double_check)){
            return false;
        }
        $where['double_check'] = $double_check;
        $where['update_time'] = time();
        $res = M('user_store_clerk') -> where('clerk_id', $clerk_id) -> update($where);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 更改店员激活状态
     * @param $clerk_id
     * @param $is_lock
     * @return bool
     */
    public function lockClerk($clerk_id,$is_lock){
        if(!intval($clerk_id) || !intval($is_lock)){
            return false;
        }
        $where['is_lock'] = $is_lock;
        $where['update_time'] = time();
        $res = M('user_store_clerk') -> where('clerk_id', $clerk_id) -> update($where);
        if($res){
            return true;
        }else{
            return false;
        }
    }

}