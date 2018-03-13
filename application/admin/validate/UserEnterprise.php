<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class UserEnterprise extends Validate
{

    // 验证规则
    protected $rule = [
        //'user_id'               => 'number|gt:0',
        'name'                  => 'require',
        'reg_address'           => 'require',
        'business_no'           => 'isBusinessNo|BusinessNoRepeat',
        'main_business'         => 'require',
        'id_number'             => 'require|validateIDCard',
        'legal_person'          => 'require',
        //'work_phone'            => 'require',
        'mobile'                => 'require|isMobile',
        'con_idnumber'          => 'require|validateIDCard',
        'control_people'        => 'require',
        //'con_workmobile'        => 'require',
        'con_mobile'            => 'require|isMobile',
        'link_mobile'           => 'isMobile',
    ];
    //错误信息
    protected $message  = [
        'name'                          => '企业名称不能为空',
        'reg_address'                   => '注册地址不能为空',
        //'business_no.require'           => '营业执照不能为空',
        'business_no.isBusinessNo'      => '营业执照格式错误',
        'business_no.BusinessNoRepeat'  => '该企业已被录入过',
        'main_business'                 => '主营业务不能为空',
        'id_number.require'             => '证件号码不能为空',
        'id_number.validateIDCard'      => '身份证号格式错误',
        'legal_person'                  => '法定代表人不能为空',
        //'work_phone'                    => '办公电话不能为空',
        'mobile.require'                => '手机号码不能为空',
        'mobile.isMobile'               => '手机号码格式错误',
        'control_people'                => '实际控制人名称不能为空',
        'con_idnumber.require'          => '实际控制人证件号码不能为空',
        'con_idnumber.validateIDCard'   => '实际控制人证件号码格式错误',
        'con_mobile.require'            => '实际控制人手机必填',
        'con_mobile.isMobile'           => '实际控制人手机格式错误',
        //'con_workmobile'                => '电话不能为空',
        'link_mobile.isMobile'          => '联系人手机格式错误',
    ];

    /*
     * 验证手机号格式
     */
    protected function isMobile($mobile){
        return preg_match('/^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$/', $mobile) ? true : false;
    }

    /*
     * 营业执照验证
     */
    public function isBusinessNo($business_no){
        return preg_match('/(^(?:(?![IOZSV])[\dA-Z]){2}\d{6}(?:(?![IOZSV])[\dA-Z]){10}$)|(^\d{15}$)/', $business_no) ? true : false;
    }

    /*
     * 营业执照验证重复验证
     */
    public function BusinessNoRepeat($business_no){
        $res = Db::name('user_enterprise') -> where('business_no', $business_no) -> find();
        return $res ? false : true;
    }

    //验证身份证是否有效
    protected function validateIDCard($IDCard) {
        if (strlen($IDCard) == 18) {
            return $this -> check18IDCard($IDCard);
        } elseif ((strlen($IDCard) == 15)) {
            $IDCard = $this -> convertIDCard15to18($IDCard);
            return $this -> check18IDCard($IDCard);
        } else {
            return false;
        }
    }

    //计算身份证的最后一位验证码,根据国家标准GB 11643-1999
    protected function calcIDCardCode($IDCardBody) {
        if (strlen($IDCardBody) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $code = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;

        for ($i = 0; $i < strlen($IDCardBody); $i++) {
            $checksum += substr($IDCardBody, $i, 1) * $factor[$i];
        }
        return $code[$checksum % 11];
    }

    // 将15位身份证升级到18位
    protected function convertIDCard15to18($IDCard) {
        if (strlen($IDCard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($IDCard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $IDCard = substr($IDCard, 0, 6) . '18' . substr($IDCard, 6, 9);
            } else {
                $IDCard = substr($IDCard, 0, 6) . '19' . substr($IDCard, 6, 9);
            }
        }
        $IDCard = $IDCard . $this -> calcIDCardCode($IDCard);
        return $IDCard;
    }

    // 18位身份证校验码有效性检查
    protected function check18IDCard($IDCard)
    {
        if (strlen($IDCard) != 18) {
            return false;
        }

        $IDCardBody = substr($IDCard, 0, 17); //身份证主体
        $IDCardCode = strtoupper(substr($IDCard, 17, 1)); //身份证最后一位的验证码

        if ($this -> calcIDCardCode($IDCardBody) != $IDCardCode) {
            return false;
        } else {
            return true;
        }
    }
}