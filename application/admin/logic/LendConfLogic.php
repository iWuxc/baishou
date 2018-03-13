<?php

/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 授信额度逻辑类
 * Author: iWuxc
 * Date: 2017-12-9
 */

namespace app\admin\logic;

use think\Model;
use think\Db;

class LendConfLogic extends Model{

    protected $rootPath;
    protected $yx;//云信

    public function __construct($data = [])
    {
        parent::__construct($data);
        $this -> rootPath = $_SERVER['DOCUMENT_ROOT'];
        vendor('YunXin.YunXin');
        $this -> yx = new \YunXin();
    }

    /**
     * 云信创建企业信息接口配置
     * @param $user_id
     * @return array|bool
     */
    public function CreateEnterPrise($user_id){
        if(empty($user_id))
            return false;
        //查询配置信息
        $data = Db::name('yunxin_enterprise') -> where('user_id',$user_id) -> find();
        //解析结果
        $result = json_decode($this -> yx -> CreateEnterPrise($data));
        if($result -> Status -> IsSuccess == false){
            return array(
                'status' => false,
                'msg' => $result -> Status -> ResponseMessage,
            );
        }else{
            Db::name('yunxin_enterprise') -> where('user_id', $user_id) -> update(array('UniqueId'=>$result -> UniqueId));
            return array(
                'status' => true,
                'msg' => '操作成功',
            );
        }
    }


    public function fileUpload($user_id){
        if(empty($user_id))
            return false;
        //查询配置信息
        $params = Db::name('yunxin_certificate')
            -> alias('yc')
            -> join('__YUNXIN_ENTERPRISE__ ye', 'yc.user_id=ye.user_id', 'LEFT')
            -> field('yc.*,ye.UniqueId')
            -> where('yc.user_id',$user_id)
            -> find();
        //解析结果
        $result = json_decode($this -> yx -> fileUpload($params, $this->rootPath));
        if($result -> Status -> IsSuccess == false){
            Db::name('yunxin_certificate') -> where('user_id', $user_id) -> update(array('is_create'=>1));
            return array(
                'status' => false,
                'msg' => $result -> Status -> ResponseMessage,
            );
        }else{
            return array(
                'status' => true,
                'msg' => '操作成功',
            );
        }
    }
}