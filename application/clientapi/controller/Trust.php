<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 10:00
 */
namespace app\clientapi\controller;
use think\Db;
use think\Request;
use think\AjaxPage;
use think\Page;
//2a2fa2c8cf8544ca91b7eac75b90bb00
class Trust extends Base{

    protected $logic;

    protected $product_code = '';

    public function _initialize(){
        parent::_initialize();
        $this->product_code = 'HD6400';
    }

    public function cc(){
        vendor('YunXin.YunXin');
        $data = array(
            'RequestId'         => 'baishou-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'ProductCode'       => $this->product_code,
            'EnterPriseFullName'=> 'baishoujinrong',
            'RoleType'          => 1,
            'OrganizationCode'  => '55991087-3',
            'BusinessLicense'   => '91330205MA2AFH5R05',
            'TaxRegistration'   => '91330205MA2AFH5R05',
            'TaxRegistrationType'=> '2',
            'CreditCode'        => '4420010006788189',
            'BankCardNo'        => '6228480020790775677',
            'BankCode'          => 'C10103',
            'BranchBankName'    => 'tianj',
            'BankCardAttribution'=> '1407',
            'EnterPriseKind'    => '2',
            'EnterPriseType'    => 3,
            'IndustryType'      => '4',
            'IndustryDetail'    => 'a01',
            'Area'              => '11',
            'InvestorsElements' => 'A02',
            'RegistrationType'  => '120',
            'OrganizationDetail'=> '10',
            'RegistrationKind'  => '1',
            'RegisteredCapital' => 30000000,
            'Corporation'       => '马昕',
            'CorporationIdCardNo'=> '210403197505261823',
            'LicenseStartDate'  => '2017-01-01',
            'LicenseEndDate'    => '2017-03-21',
            'RegisteredAddress' => 'beijing',
            'TotalEnterpriseAssets'=> 30000000,
            'TotalEnterpriseDebt'   => 0,
            'BeListed'          => '2',
            'IsGroup'           => '2',
            'RelationType'      => '0',
            'ImportandexportFlag'=> '2',
            'Address'           => 'beijing',
            'FinancePhone'      => '88386098',
            'Telephone'         => '15510923628',
            'ZipCode'           => '300000',
            'EconomyType'       => '12'
        );
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/CreateEnterPrise',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }


    public function loan(){
        vendor('YunXin.YunXin');
        $data = [
            'LoanContractNumber'    => 'ZQ2016071524826',
            'ContractAmount'        => 200000,
            'SignDate'              => '2018-02-26',
            'BeginDate'             => '2018-08-26',
            'SignRate'              => 0.3555,
            'RepaymentCycle'        => '0',
            'RepaymentMode'         => '2',
            'RepaymentPeriod'       => '6',
            'UniqueId'              => '08327ca62caa4aecaaa4a2c99424d563',
            'RequestId'             => 'bsrequestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'PaymentBankCards'      => [
                'AccountNo'             => '6228480020790775677',
                'BankCode'              => 'C10103',
                'BranchBankName'        => '天津',
                'BankCardAttribution'   =>'1407',
                'AccountName'           => '李四',
                'Amount'                => 200000,
                'Type'                  => 0
            ],
        ];
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/CreateLoan',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    //EG,EI,EJ,EN,EO,EP,ER,ES,ET,EU,EV,EW,EZ
    public function fileUpload(){
        vendor('YunXin.YunXin');
//        $file = request()->file('file');
//        if($file){
//            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'loanCer');
//            $cer = '/public/upload/loanCer/'.$info->getSaveName();
//        }

        $data = [
            'UniqueId'      => '3cfcd8e9c2aa44d19715f8b6d484398d',
            'RequestId'     => 'filerequestids-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'LicenseNo'     => '91330205MA2AFH5R05',
            'FileContent'   => [
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/f72c7e99a3b5602f642e2a1317fb932c.pdf')),
                    'Type'      => 'EG'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EI'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EJ'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EN'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EO'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EP'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'ER'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'ES'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'ET'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EU'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EV'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EW'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EZ'
                ],
            ],
            'Remark'        => 'sadsada',
        ];
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/FileUpload',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    /**
     * 创建还款计划
     */
    public function createRepaySchedule(){
        vendor('YunXin.YunXin');
        $data = [
            'RequestId' => 'RepayScheduleId-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'LoanRepaySchedules' => [
                'UniqueId' => '2a2fa2c8cf8544ca91b7eac75b90bb00',//合同唯一编号
                'RepaySchedules' => [//还款计划集合
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                        'RepayDate' => '2018-09-26',//还款时间
                        'RepayPrincipal' => 20000.00, //还款本金
                        'RepayProfit' => 0.35,//还款利息
                        'TechMaintenanceFee' => 5000.00,//技术服务费
                        'OtherFee' => 0,//其他费用
                        'LoanServiceFee' => 0,//贷款服务费
                    ],
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                        'RepayDate' => '2018-10-26',//还款时间
                        'RepayPrincipal' => 40000.00, //还款本金
                        'RepayProfit' => 0.35,//还款利息
                        'TechMaintenanceFee' => 5000.00,//技术服务费
                        'OtherFee' => 0,//其他费用
                        'LoanServiceFee' => 0,//贷款服务费
                    ],
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                        'RepayDate' => '2018-11-26',//还款时间
                        'RepayPrincipal' => 20000.00, //还款本金
                        'RepayProfit' => 0.35,//还款利息
                        'TechMaintenanceFee' => 5000.00,//技术服务费
                        'OtherFee' => 0,//其他费用
                        'LoanServiceFee' => 0,//贷款服务费
                    ],
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                        'RepayDate' => '2018-12-26',//还款时间
                        'RepayPrincipal' => 40000.00, //还款本金
                        'RepayProfit' => 0.35,//还款利息
                        'TechMaintenanceFee' => 5000.00,//技术服务费
                        'OtherFee' => 0,//其他费用
                        'LoanServiceFee' => 0,//贷款服务费
                    ],
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                        'RepayDate' => '2019-01-26',//还款时间
                        'RepayPrincipal' => 40000.00, //还款本金
                        'RepayProfit' => 0.35,//还款利息
                        'TechMaintenanceFee' => 5000.00,//技术服务费
                        'OtherFee' => 0,//其他费用
                        'LoanServiceFee' => 0,//贷款服务费
                    ],
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                        'RepayDate' => '2019-02-26',//还款时间
                        'RepayPrincipal' => 40000.00, //还款本金
                        'RepayProfit' => 0.35,//还款利息
                        'TechMaintenanceFee' => 5000.00,//技术服务费
                        'OtherFee' => 0,//其他费用
                        'LoanServiceFee' => 0,//贷款服务费
                    ],

                ],
            ],
        ];
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/CreateRepaySchedule',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    /**
     * 还款计划批量查询
     */
    public function QueryBatchRepaySchedule(){
        vendor('YunXin.YunXin');
        $data = [
            'RequestId' => 'RepayScheduleId-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'UniqueIds' => [
                '2a2fa2c8cf8544ca91b7eac75b90bb00',
            ],
            'ProductCode' => $this->product_code,
            'RepayDate' => '',
        ];
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/QueryBatchRepaySchedule',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    /**
     * 获取合同文件
     */
    public function GetContractFile(){
        vendor('YunXin.YunXin');
        $data = [
            'RequestId' => 'RepayScheduleId-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'UniqueId' => '2a2fa2c8cf8544ca91b7eac75b90bb00',//合同唯一编号
            //'IsGetFileContent' => '',
        ];
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/GetContractFile',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    /**
     * 更新还款计划
     */
    public function UpdateRepaySchedule(){
        vendor('YunXin.YunXin');
        $data = [
            'RequestId' => 'RepayScheduleId-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'UniqueId' => '2a2fa2c8cf8544ca91b7eac75b90bb00',//合同唯一编号
            'ChangeReason' => '2',//0项目结清 1提前部分还款 2错误更正
            'RepaySchedules' => [//还款计划集合
                [
                    'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                    'RepayDate' => '2018-09-26',//还款时间
                    'RepayPrincipal' => 10000.00, //还款本金
                    'RepayProfit' => 0.35,//还款利息
                    'TechMaintenanceFee' => 5000.00,//技术服务费
                    'OtherFee' => 0,//其他费用
                    'LoanServiceFee' => 0,//贷款服务费
                ],
                [
                    'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                    'RepayDate' => '2018-10-26',//还款时间
                    'RepayPrincipal' => 20000.00, //还款本金
                    'RepayProfit' => 0.35,//还款利息
                    'TechMaintenanceFee' => 5000.00,//技术服务费
                    'OtherFee' => 0,//其他费用
                    'LoanServiceFee' => 0,//贷款服务费
                ],
                [
                    'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                    'RepayDate' => '2018-11-26',//还款时间
                    'RepayPrincipal' => 30000.00, //还款本金
                    'RepayProfit' => 0.35,//还款利息
                    'TechMaintenanceFee' => 5000.00,//技术服务费
                    'OtherFee' => 0,//其他费用
                    'LoanServiceFee' => 0,//贷款服务费
                ],
                [
                    'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                    'RepayDate' => '2018-12-26',//还款时间
                    'RepayPrincipal' => 40000.00, //还款本金
                    'RepayProfit' => 0.35,//还款利息
                    'TechMaintenanceFee' => 5000.00,//技术服务费
                    'OtherFee' => 0,//其他费用
                    'LoanServiceFee' => 0,//贷款服务费
                ],
                [
                    'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                    'RepayDate' => '2019-01-26',//还款时间
                    'RepayPrincipal' => 50000.00, //还款本金
                    'RepayProfit' => 0.35,//还款利息
                    'TechMaintenanceFee' => 5000.00,//技术服务费
                    'OtherFee' => 0,//其他费用
                    'LoanServiceFee' => 0,//贷款服务费
                ],
                [
                    'PartnerScheduleNo' => 'RepayScheduleIds-'.time().rand(00000,99999),//每期还款计划的唯一标识
                    'RepayDate' => '2019-02-26',//还款时间
                    'RepayPrincipal' => 50000.00, //还款本金
                    'RepayProfit' => 0.35,//还款利息
                    'TechMaintenanceFee' => 5000.00,//技术服务费
                    'OtherFee' => 0,//其他费用
                    'LoanServiceFee' => 0,//贷款服务费
                ],

            ],
        ];
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/UpdateRepaySchedule',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }
}