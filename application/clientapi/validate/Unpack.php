<?php
namespace app\clientapi\validate;
use think\Validate;
use think\Db;
class Unpack extends Validate
{

    // 验证规则
    protected $rule = [
        'pawn_id'           => 'require',
        'sale_price'        => 'require',
        'arrive_time'       => 'require',
        'cause'             => 'require|max:50',
    ];
    //错误信息
    protected $message  = [
        'pawn_id.require'           => '家具对应门店ID不能为空',
        'sale_price.require'        => '销售价格不能为空',
        'arrive_time.require'       => '贷款偿还到账时间不能为空',
        'cause.require'             => '解压理由不能为空',
        'cause.max'                 => '解压理由不能超过50个字符',
    ];

    /** 弃用 */
    protected function checkPawnId($value){
        $res = Db::name('pawn_applygreen')->where('pawn_id',$value)->find();
        if($res){
            return '抵押品ID为：'.$res['pawn_id'].'的已提交过';
        }
        return true;
    }

}