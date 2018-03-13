<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/8
 * Time: 10:00
 */
namespace app\bossapi\controller;
use think\Db;
use think\Request;
use think\AjaxPage;
use think\Page;
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
            'LoanContractNumber'    => 'ERR2016071524827',
            'ContractAmount'        => 10000,
            'SignDate'              => '2018-02-26',
            'BeginDate'             => '2018-08-26',
            'SignRate'              => 0.3555,
            'RepaymentCycle'        => '0',
            'RepaymentMode'         => '2',
            'RepaymentPeriod'       => '6',
            'UniqueId'              => '3cfcd8e9c2aa44d19715f8b6d484398d',
            'RequestId'             => 'bsrequestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'PaymentBankCards'      => [
                'AccountNo'             => '6228480020790775670',
                'BankCode'              => 'C10103',
                'BranchBankName'        => '天津',
                'BankCardAttribution'   =>'1407',
                'AccountName'           => '李四',
                'Amount'                => 10000,
                'Type'                  => 0
            ],
        ];
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/CreateLoan',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    //必传文件EA,EB,EC,ED,EE,EF,EG,EH,ES
    public function fileUpload(){
        vendor('YunXin.YunXin');
//        $file = request()->file('file');
//        if($file){
//            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'loanCer');
//            $cer = '/public/upload/loanCer/'.$info->getSaveName();
//        }

        $data = [
            'UniqueId'      => 'f3e763885c72471e98a766e6faf08ffa',
            'RequestId'     => 'filerequestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'LicenseNo'     => '91330205MA2AFH5R05',
            'FileContent'   => [
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EA'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EB'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EC'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'ED'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EE'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EF'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/f72c7e99a3b5602f642e2a1317fb932c.pdf')),
                    'Type'      => 'EG'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'EH'
                ],
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos/public/upload/loanCer/20180227/fa4f72ce44ea79ec3bc47266c355e78e.jpg')),
                    'Type'      => 'ES'
                ]
            ],
            'Remark'        => 'sadsada',
        ];
        //echo json_encode($data);die;
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/FileUpload',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    public function repayVoucher(){
        $data = [
            'RequestId'     => 'repayvoucherquestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'ActionType'    => 0,
            'VoucherType'   => 2,
            'VoucherAmount' => 5000.00,
            'ProductCode'   => 'HD6400',
            'ReceivedAccountNo'=>'6228480020790775671',
            'PaymentAccountNo'=> '6228480020790775670',
            'BankTransactionNo'=>719999980852001,
            'PaymentName'=>'李四',
            'PaymentBank'=>'C10103',
            'VoucherDetails' => [
                [
                    'UniqueId' => '3cfcd8e9c2aa44d19715f8b6d484398d',
                    'Amount'=>5000.00,
                    'RepayPrincipal'=> 5000.00,
                    'RepayProfit'=>0.00,
                    'Payer'=> 0
                ],
            ]
        ];
        vendor('YunXin.YunXin');
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/RepayVoucher',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    /**
     * 查询放款
     */
    public function queryBatchTradingStatus(){
        vendor('YunXin.YunXin');
        $data = [
            'UniqueIds'     => [
                '3cfcd8e9c2aa44d19715f8b6d484398d',
            ],
            'RequestId'     => 'batchrequestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
        ];
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/QueryBatchTradingStatus',$data);
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
                'UniqueId' => 'f813804b27cd40528d8525d3d4b561e3',//合同唯一编号
                'RepaySchedules' => [//还款计划集合
                    [
                        'PartnerScheduleNo' => 'RepayScheduleIds-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),//每期还款计划的唯一标识
                        'RepayDate' => '2018-06-30',//还款时间
                        'RepayPrincipal' => 500000.00, //还款本金
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
}