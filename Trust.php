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
            'ContractAmount'        => 200000,
            'SignDate'              => '2018-02-26',
            'BeginDate'             => '2018-08-26',
            'SignRate'              => 0.3555,
            'RepaymentCycle'        => '0',
            'RepaymentMode'         => '2',
            'RepaymentPeriod'       => '6',
            'UniqueId'              => '68519de4887b489785747b9908102033',
            'RequestId'             => 'bsrequestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'PaymentBankCards'      => [
                'AccountNo'             => '6228480020790775677',
                'BankCode'              => 'C10103',
                'BranchBankName'        => 'tianj',
                'BankCardAttribution'   =>'1407',
                'Amount'                => 200000,
                'Type'                  => 0
            ],
        ];
        $yx = new \YunXin();
        $res = $yx->getYunXin('/EnterPriseLoan/CreateLoan',$data);
        Db::name('config')->where(['name'=>'bank_number'])->setInc('value');
        print_r($res);
    }

    public function fileUpload(){
        vendor('YunXin.YunXin');
        $file = request()->file('file');
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'loanCer');
            $cer = '/public/upload/loanCer/'.$info->getSaveName();
        }
        //echo $cer;die;
        $data = [
            'UniqueId'      => '68519de4887b489785747b9908102033',
            'RequestId'     => 'filerequestid-'.Db::name('config')->where(['name'=>'bank_number'])->value('value'),
            'LicenseNo'     => '91330205MA2AFH5R05',
            'FileContent'   => [
                [
                    'Content'   => base64_encode(file_get_contents('/home/wwwroot/repos'.$cer)),
                    'Type'      => 'EG'
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

    function imgToBase64($img_file) {

        $img_base64 = '';
        if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等

            //echo '<pre>' . print_r($img_info, true) . '</pre><br>';
            $fp = fopen($app_img_file, "r"); // 图片是否可读权限

            if ($fp) {
                $filesize = filesize($app_img_file);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码
                switch ($img_info[2]) {           //判读图片类型
                    case 1: $img_type = "gif";
                        break;
                    case 2: $img_type = "jpg";
                        break;
                    case 3: $img_type = "png";
                        break;
                }

                $img_base64 = 'data:image/' . $img_type . ';base64,' . $file_content;//合成图片的base64编码

            }
            fclose($fp);
        }

        return $img_base64; //返回图片的base64
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