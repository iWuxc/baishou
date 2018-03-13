<?php

/**
 * 店员管理
 * Author: iWuxc
 * time 2017-12-23
 */
namespace app\clientapi\logic;
use app\clientapi\model\ClerkManage;
use think\Db;

class ClerkManageLogic extends BaseLogic{

    protected $model;

    public function __construct(array $data = [])
    {
        parent::__construct($data);
        $this -> model = new ClerkManage();
        $this -> isBoss(request()->param("user_type"));
    }

    /**
     * 获取店员列表
     * @param $userid
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getClerkList($userid){
        $result = $this -> model -> getClerkList($userid);
        return $result;
    }

    /**
     * 店员详情
     * @param $clerk_id
     * @return array|bool
     */
    public function detail($clerk_id,$userid){
        $id_type = array(1=>"身份证", 2=>"港澳台居民身份证", 3=>"护照", 4=>"其他");
        $where['clerk_id'] = $clerk_id;
        $where['user_id'] = $userid;
        $result = $this -> model -> detail($where);
        if($result){
            $result['credential_type'] = $id_type[$result['credential_type']];
            return $result;
        }else{
            return false; //无数据
        }
    }

    /**
     * 修改店员所属门店
     * @param $clerk_id
     * @param $store_ids
     * @param $userid
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function modifyStore($clerk_id,$store_ids,$userid){
        $ids = '';
        $where['user_id'] = $userid;
        $where['clerk_id'] = $clerk_id;
        if(!empty($store_ids) && is_array($store_ids)){
            $ids = implode(",", $store_ids);
        }
        $data['belong_to_store'] = $ids ? $ids : $store_ids;
        $data['update_time'] = time();
        $res = Db::name('user_store_clerk') -> where($where) -> update($data);
        $res ? toJson("1", "修改成功") : toJson("0", "修改失败");
    }

    /**
     * 修改店员权限
     * @param $clerk_id
     * @param $double_check
     * @param $userid
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function modifyAuth($clerk_id,$double_check,$userid){
        $where['user_id'] = $userid;
        $where['clerk_id'] = $clerk_id;
        $data['double_check'] = $double_check;
        $data['update_time'] = time();
        $res = Db::name('user_store_clerk') -> where($where) -> update($data);
        $res ? toJson("1", "修改成功") : toJson("0", "修改失败");
    }

    /**
     * 更改店员状态
     * @param $clerk_id
     * @param $is_lock
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function lockClerk($clerk_id,$is_lock){
        $data['is_lock'] = $is_lock;
        $data['update_time'] = time();
        $res = Db::name('user_store_clerk') -> where('clerk_id', $clerk_id) -> update($data);
        $res ? toJson("1", "修改成功") : toJson("0", "修改失败");
    }

    /**
     * 添加员工
     * @param $param
     * @param $userid
     */
    public function addClerk($param, $userid){
        //$id_type = array("身份证"=>1, "港澳台居民身份证"=>2, "护照"=>3, "其他"=>4);
        //验证证件类型是否正确, 只有身份证
        if($param['credential_type'] != 1){
            toJson("0", "非法证件类型");
        }
        if(!in_array($param['double_check'], array(1,2))){
            toJson('0', '非法权限!');
        }
        /*
         * 证件号码验证
         */
        if(!validation_filter_id_card($param['id_number'])){
            toJson('0', '非法证件号码!');
        }
        $data = array();
        //开始组装数据
        $data['user_id'] = $userid;
        $data['clerk_name'] = trim($param['c_name']);
        $data['mobile'] = trim($param['c_mobile']);
        $data['nickname'] = trim($param['c_name']);
        $data['id_number'] = trim($param['id_number']);
        $data['add_time'] = time();
        $data['credential_type'] = $param['credential_type'];
        $data['double_check'] = $param['double_check'];
        $data['belong_to_store'] = $param['belong_to_store'];
        $data['password'] = encrypt(get_passwd($param['id_number'], 6));
        $res = M('user_store_clerk') -> insert($data);
        if($res){
            //开始发送注册短信
            $sms_param = array(
                'name' => trim($param['c_name']),
                'mobile' => trim($param['c_mobile']),
                'password' => get_passwd($param['id_number'], 6),
            );
            sendSms(1, trim($param['c_mobile']), $sms_param);
        }
        $res ? toJson("1", "添加成功") : toJson("0", "添加失败");
    }

    /**
     * 获取抵押门店
     * @param $userid
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getStoreList($userid){
        //获取审批列表中已抵押的门店ID
        $ids = Db::name("credit_conclusion") ->field('pawn_store') -> where('user_id', $userid) -> find();
        empty($ids) && toJson("0", "无抵押门店");
        $stores = Db::name("user_store") -> field('store_id,store_name') -> where("store_id in(".$ids['pawn_store'].")") -> select();
        toJson("1", "获取成功", $stores);
    }

    /**
     * 判断是否是boss登录
     * @param $type
     */
    private function isBoss($type){
        $type != 1 && $this->toJson("0", "非法用户类型");
    }
}