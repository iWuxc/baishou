<?php
namespace app\admin\validate;
use think\Validate;
use think\Db;
class LendConf extends Validate
{

    // 验证规则
    protected $rule = [
        'EnterPriseName'        => 'require',
        'RegisteredAddress'     => 'require',
        'Corporation'           => 'require',
        'CorporationIdCardNo'   => 'require',
        'RegisteredCapital'     => 'require',
        'LicenseStartDate'      => 'require',
        'LicenseEndDate'        => 'require',
        'TotalEnterpriseAssets' => 'require',
        'TotalEnterpriseDebt'   => 'require',
        'BeListed'              => 'require',
        'IsGroup'             => 'require',
        'ImportandexportFlag'   => 'require',
        'Address'               => 'require',
        'FinancePhone'          => 'require',
        'EA'                    => 'require',
        'EB'                    => 'require',
        'EC'                    => 'require',
        'ED'                    => 'require',
        'EE'                    => 'require',
        'EF'                    => 'require',
        'EH'                    => 'require',
        'ES'                    => 'require',
    ];
    //错误信息
    protected $message  = [
        'EnterPriseName'                => '企业名称不能为空',
        'reg_address'                   => '注册地址不能为空',
        'Corporation'                   => '企业法人不能为空',
        'CorporationIdCardNo'           => '法人身份证号码不能为空',
        'RegisteredCapital'             => '注册资本不能为空',
        'LicenseStartDate'              => '登记开始时间不能为空',
        'LicenseEndDate'                => '登记结束时间不能为空',
        'TotalEnterpriseAssets'         => '企业总资产不能为空',
        'TotalEnterpriseDebt'           => '企业总负债不能为空',
        'BeListed'                      => '是否上市必选',
        'IsGroup'                       => '是否集团必选',
        'ImportandexportFlag'           => '进出口标识必选',
        'Address'                       => '通讯地址必填',
        'FinancePhone'                  => '财务部电话必填',
        'EA'                            => '营业执照为必传项',
        'EB'                            => '借款申请书为必传项',
        'EC'                            => '提款借据为必传项',
        'ED'                            => '尽调报告为必传项',
        'EE'                            => '内部决议为必传项',
        'EF'                            => '法人身份证正面照为必传项',
        'EH'                            => '法人身份证反面照为必传项',
        'ES'                            => '主营业务为必传项',
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