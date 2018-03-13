<?php
/**
 * 云信接口
 * Author: iWuxc
 * Date: 2017-11-30
 */

namespace app\admin\controller;

use app\admin\logic\LendConfLogic;
use think\Exception;
use think\Request;
use think\Db;
use app\admin\logic\ClientLogic;
use think\Loader;

class LendConf extends Base{

    protected $logic;

    public function __construct()
    {
        parent::__construct();
        $this -> logic = new LendConfLogic();
    }

    public function lendEdit(){
        $user_id = $_REQUEST['user_id'];
        empty($user_id) && $this -> error('参数错误');
        $this -> assign('user_id', $user_id);
        return $this -> fetch();
    }

    public function ajaxLend(){
        $ClientLogic = new ClientLogic();
        $pay_code = $_REQUEST['type'];
        $user_id = $_REQUEST['user_id'];
        if(empty($pay_code) || empty($user_id)){
            return false;
        }
        //查询客户企业信息
        $enterprise_info = Db::name('user_enterprise') -> where('user_id',$user_id) -> find();
        if($enterprise_info){
            $this -> assign('list', $enterprise_info);
        }
        //!$enterprise_info && $this -> error('企业信息不完善，禁止贷款');

        //===================云信接口字段配置开始=========================
        //匹配法人信息证件号码，确定银行归属地字段BankCardAttribution
        //if(empty($enterprise_info['id_number'])){
        //    $this -> error('法人信息不完善，请先完善！');
        //}
        $id_str = substr($enterprise_info['id_number'], 0, 4);
        $is_match = Db::name('yunxin_region') -> where('RegionCode',$id_str) -> value('RegionCode');
        if(!$is_match){
            $is_match = '310115';
        }
        $enterprise_info['regon_address'] = $ClientLogic -> getAddressName($enterprise_info['reg_province'],$enterprise_info['reg_city'],$enterprise_info['reg_district']).$enterprise_info['reg_address'];
        $this -> assign('RegionCode', $is_match);
        $conf_ent_info = Db::name('yunxin_enterprise') -> where('user_id',$user_id) -> find();
        $this -> assign('lending', $conf_ent_info);
        $cert_ent_info = Db::name('yunxin_certificate') -> where('user_id',$user_id) -> find();
        $this -> assign('cert_ent_info', $cert_ent_info);
        //===================云信接口字段配置结束=========================
        return $this -> fetch($pay_code);
    }

    public function handle(){
        $lend = new LendConfLogic();
        if(Request::instance()->isPost() && I('is_ajax') == 1){
            $data = Request::instance() -> post();

            //禁止再次提交
            $is_submit = Db::name('yunxin_enterprise') -> where('user_id',$data['user_id']) -> find();
            if($is_submit){
                $return_arr = array(
                    'status' => -1,
                    'msg' => '放款配置信息已存在，禁止提交',
                    'data' => '',
                );
                $this->ajaxReturn($return_arr);
            }

            $res = $this -> logic -> CreateEnterPrise($data['user_id']);
            $temp_data = array_merge($data, $data['enterprise']);
            $validate = Loader::validate('LendConf');
            if (!$validate->batch()->check($temp_data)) {
                $error = $validate->getError();
                $error_msg = array_values($error);
                $return_arr = array(
                    'status' => -1,
                    'msg' => $error_msg[0],
                    'data' => $error,
                );
                $this->ajaxReturn($return_arr);
            }
            if($data['lending_type'] == 'yunxin'){
                $data['enterprise']['user_id'] = $data['user_id'];
                //开启事务
                $error = 0;
                Db::startTrans();
                try{
                    //1. 创建企业信息
                    $r1 = Db::name('yunxin_enterprise') -> add($data);
                    !$r1 && $error = 1;

                    //2. 证件上传
                    $r2 = Db::name('yunxin_certificate') -> add($data['enterprise']);
                    !$r1 && $error = 2;

                    //3. 请求企业信息
                    $cep = $lend -> CreateEnterPrise($data['user_id']);//创建企业信息
                    if($cep['status'] == true){
                        $fileUpload = $lend -> fileUpload($data['user_id']);
                        if($fileUpload['status'] == false){
                            $return_arr = array(
                                'status' => -1,
                                'msg' => $fileUpload['msg'],
                                'data' => '',
                            );
                        }else{
                            $return_arr = array(
                                'status' => 1,
                                'msg' => '操作成功',
                                'data' => '',
                            );
                        }
                    }else{
                        $return_arr = array(
                            'status' => -1,
                            'msg' => $res['msg'],
                            'data' => '',
                        );
                    }
                    Db::commit();
                    if($cep['status'] == false || $fileUpload['status'] == false){
                        Db::rollback();
                    }
                    $this->ajaxReturn($return_arr);
                } catch (Exception $e) {
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
    }

    /**
     * 搜索银行
     */
    public function bankCodeSearch(){
        $search_key = trim(I('search_key'));
        $user = Db::name('yunxin_bank')
            ->field('BankCode,BankName')
            ->where('BankName','like',"%$search_key%")
            ->select();
        foreach($user as $key => $val)
        {
            echo "<option value='{$val['BankCode']}'>{$val['BankName']}</option>";
        }
    }

    /**
     * 行政区划搜索
     */
    public function AreaKeySearch(){
        $search_key = trim(I('search_key'));
        $area = Db::name('yunxin_administrative_area')
            ->field('code,name')
            ->where('name','like',"%$search_key%")
            ->select();
        foreach($area as $key => $val)
        {
            echo "<option value='{$val['code']}'>{$val['name']}</option>";
        }
    }

    public function cc(){
        //vendor('YunXin.YunXin');
        //$data = Db::name('yunxin_enterprise')->where('user_id=6')->find();
        //$yx = new \YunXin();
        //$yx -> CreateEnterPrise($data);
        $a = $this -> logic -> fileUpload(4);
        var_dump($a);
    }
}