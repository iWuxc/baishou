<?php
/**
 * Created by PhpStorm.
 * User: xxf
 * Date: 2017/12/18
 * Time: 11:01
 */

namespace app\bossapi\logic;

use app\bossapi\model\Pawn;
use app\bossapi\model\PawnImgs;
use app\bossapi\model\PawnOne;
use app\bossapi\model\Store;
use think\Db;

class PawnLogic extends BaseLogic {

    protected $model;

    protected $one;

    public function __construct($data = []) {
        parent::__construct($data);
        $this->model = new Pawn();
        $this->one = new PawnOne();
    }

    /**
     * 数据列表
     * @param array $request
     * @param int $flag 1获取抵押品列表，2获取出库信息列表
     * @return mixed
     */
    public function getList($request = array(),$flag = 1){
        check_param('role_id,admin_id');
        if($request['role_id'] == 4){ //信贷经理角色
            $param['where']['p.xd_id'] = $request['admin_id'];
        }
        if($flag == 2){ //抵押品列表
            $param['where']['p.status'] = ['in','3,5,2,4'];
        }else{ // 出库信息列表
            $param['where']['p.status'] = ['in','1,3,5'];
        }
        $param['parameter'] = $request;
        $param['order'] = 'p.addtime desc';
        $list = $this->model->getList($param);
        if(!empty($list['list'])){
            foreach ($list['list'] as $key=>$val){
                //默认图片
                $img = Db::name('pawn_imgs')->where('pawn_id',$val['pawn_id'])->value('img_url');
                $list['list'][$key]['cover'] = !empty($img) ? get_url().$img : '';
                if($flag == 1){
                    unset($list['list'][$key]['gsubmit_time']);
                }else{
                    $list['list'][$key]['gsubmit_time'] = date('Y-m-d H:i:s',$val['gsubmit_time']);
                }
            }
        }
        return $list['list'];
    }


    /**
     * 单条数据
     * @param array $request
     * @return array|false|\PDOStatement|string|Model
     */
    public function findRow($request = array()){
        check_param('pawn_id');
        $param['where']['pawn_id'] = $request['pawn_id'];
        $row = $this->model->findRow($param);
        if(!empty($row)){
            $row['pawn_rfid'] = empty($row['pawn_rfid']) ? '' : $row['pawn_rfid'];
            $row['green_remarks'] = empty($row['green_remarks']) ? '' : $row['green_remarks'];
            $row['wood_auxname'] = Db::name('pawn_wood')->where(['id'=>$row['wood_auxid']])->value('name');
            $imgs = Db::name('pawn_imgs')->where(['pawn_id'=>$row['pawn_id'],'pawn_type'=>1])->field('img_id,img_url')->select();
            if(!empty($imgs)){
                foreach ($imgs as $key=>$val){
                    $imgs[$key]['img_url'] = get_url().$val['img_url'];
                }
                $row['imgs'] = $imgs;
            }else{
                $row['imgs'] = [];
            }
            $row['pawn_auxarea'] = empty($row['pawn_auxarea']) ? '' : $row['pawn_auxarea'];
            $row['wood_auxname'] = empty($row['wood_auxname']) ? '' : $row['wood_auxname'];
            //获取门店地址
            $row['address'] = Db::name('user_store')->where('store_id',$row['store_id'])->value('address') ? : '';
        }
        return $row;
    }

    /**
     * 门店列表
     * @param array $request
     * @return mixed
     */
    public function store($request = array()){
        check_param('admin_id,role_id');
        if($request['role_id'] == 4){
            //获取当前信贷的家具
            $pawn_ids = Db::name('pawn')->where(['status'=>['in','-3,-2,-1,0'],'xd_id'=>$request['admin_id']])->getField('store_id',true);
            $param['where']['store_id'] = ['in',array_unique($pawn_ids)];
        }elseif($request['role_id'] == 3){
            $pawn_ids = Db::name('pawn')->where(['status'=>['in','-3,-1']])->getField('store_id',true);
            $param['where']['store_id'] = ['in',array_unique($pawn_ids)];
        }elseif($request['role_id'] == 2){
            //风控获取待审核的家具的门店列表
            $pawn_ids = Db::name('pawn')->where('status',0)->getField('store_id',true);
            $param['where']['store_id'] = ['in',array_unique($pawn_ids)];
        }
        if(!empty($request['keywords'])){
            $param['where']['store_name|user_name'] = ['LIKE','%'.$request['keywords'].'%'];
        }

        $param['order'] = 'addtime desc';
        $store = new Store();
        $list = $store->getList($param)['list'];
        if(!empty($list)){
            foreach ($list as $key=>$val){
                //默认封面图
                $cover = Db::name('store_images')->where('store_id',$val['store_id'])->value('image_url');
                if(!empty($cover)){
                    $list[$key]['cover'] = get_url().$cover;
                }else{
                    $list[$key]['cover'] = '';
                }
                $list[$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
            }
        }
        return $list;
    }

    /**
     * 门店下的家具
     * @param array $request
     */
    public function pawnList($request = array()){
        check_param('admin_id,role_id,store_id');
        if($request['role_id'] == 4){
            $param['where']['p.status'] = -3;
        }elseif ($request['role_id'] == 2){
            $param['where']['p.status'] = -3;
        }
        $param['where']['p.store_id'] = $request['store_id'];

        $param['parameter'] = $request;
        $param['order'] = 'p.addtime desc';
        $list = $this->model->getList($param)['list'];
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
                unset($list[$k]['gsubmit_time'],$list[$k]['pawn_no'],$list[$k]['status'],$list[$k]['store_name']);
            }
        }
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 套件下的单件家具列表
     * @param array $request
     */
    public function pawnOne($request = array()){
        check_param('admin_id,role_id,pawn_id');
        $param['where']['pawn_id'] = $request['pawn_id'];
        $param['parameter'] = $request;
        $param['order'] = 'addtime desc';
        $list = $this->one->getList($param)['list'];
        if(!empty($list)){
            foreach ($list as $k=>$v){
                $list[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
            }
        }
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }

    /**
     * 组件详情
     * @param array $request
     * @return array|false|\PDOStatement|string|\think\Model
     */
    public function findRowOne($request = array()){
        check_param('one_id');
        $param['where']['one_id'] = $request['one_id'];
        $row = $this->one->findRow($param);
        if(!empty($row)){
            $row['pawn_rfid'] = empty($row['pawn_rfid']) ? '' : $row['pawn_rfid'];
            $imgs = Db::name('pawn_imgs')->where(['one_id'=>$request['one_id'],'pawn_type'=>2])->field('img_id,img_url')->select();
            if(!empty($imgs)){
                foreach ($imgs as $key=>$val){
                    $imgs[$key]['img_url'] = get_url().$val['img_url'];
                }
                $row['imgs'] = $imgs;
            }else{
                $row['imgs'] = [];
            }
        }
        return $row;
    }

    /**
     * 评估师评估历史
     * @param array $request
     */
    public function history($request = array()){
        check_param('admin_id');
        $pawn_ids = Db::name('pawn')->where(['status'=>['not in','-3'],'pg_id'=>$request['admin_id']])->getField('store_id',true);
        $param['where']['store_id'] = ['in',array_unique($pawn_ids)];
        $param['order'] = 'addtime desc';
        $store = new Store();
        $list = $store->getList($param)['list'];
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $list[$key]['addtime'] = date('Y-m-d H:i:s',$val['addtime']);
                //获取门店下的家具
                $pawn_list = Db::name('pawn')->where(['store_id'=>$val['store_id']])->field('pawn_id,pawn_name,addtime')->select();
                if(!empty($pawn_list)){
                    foreach ($pawn_list as $k=>$v){
                        $pawn_list[$k]['addtime'] = date('Y-m-d H:i:s',$v['addtime']);
                    }
                    $list[$key]['pawn_list'] = $pawn_list;
                }else{
                    $list[$key]['pawn_list'] = [];
                }
            }
        }
        !empty($list) ? api_response('1','',$list) : api_response_('1','暂无数据');
    }


    /**
     * 上传家具图片
     * @param array $request
     */
    public function uploadImg($request = array()){
        check_param('pawn_id');
        //门店存在图片，先删除
        if($request['pawn_type'] == 1){
            Db::name('pawn_imgs')->where(['pawn_id'=>$request['pawn_id']])->delete();
        }else{
            Db::name('pawn_imgs')->where(['one_id'=>$request['pawn_id']])->delete();
        }
        $files = request()->file('image');
        $img = array();
        //dump($files);die;
        if(!empty($files)){
            foreach($files as $file){
                //$info = $file->validate()->move('/home/wwwroot/bs_images/pawn/');
                $info = $file->validate()->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'store');
                if($info){
                    $img[] = '/public/upload/store/'.$info->getSaveName();
                }
            }
            $arr = array();
            if(!empty($img)){
                if($request['pawn_type'] == 1){
                    foreach ($img as $val){
                        $arr[] = [
                            'pawn_type' => $request['pawn_type'],
                            'pawn_id'   => $request['pawn_id'],
                            'img_url'   => $val
                        ];
                    }
                }else{
                    foreach ($img as $val){
                        $arr[] = [
                            'pawn_type' => $request['pawn_type'],
                            'one_id'   => $request['pawn_id'],
                            'img_url'   => $val
                        ];
                    }
                }
            }
            $pm = new PawnImgs();
            $result =$pm->saveAll($arr);
            if($result){
                if($request['pawn_type'] == 2){
                    $pawn = Db::name('pawn')->where(['pawn_id'=>$request['pawn_id']])->field('store_id')->find();
                    $user_store = Db::name('user_store')->where(['store_id'=>$pawn['store_id']])->field('cruise_shop_mode')->find();
                    //更新RFID
                    $where['one_id'] = $request['pawn_id'];
                    Db::name('pawn_one')->where($where)->update(['pawn_rfid'=>$request['pawn_rfid'],'cruise_shop_mode'=>$user_store['cruise_shop_mode'],'updtime'=>time()]);
                }
                api_response('1',SUCCESS_INFO);
            }else{
                api_response('1',ERROR_INFO);
            }
        }else{
            api_response('0','请上传图片');
        }
    }

    /**
     * 出库否决操作
     * @param array $request
     * @param $status
     */
    public function okOrCancel($request = array(),$status){
        check_param('pawn_id,admin_id');
        $this->model->startTrans();
        $error = 0;
        $rote = 0;
        try{
            $where['pawn_id'] = $request['pawn_id'];
            $xd_id = $this->model->where($where)->value('xd_id') ? : 0;
            $admin = Db::name('admin')->where('admin_id',$xd_id)->field('cid,mobile,nickname')->find();
            $admin_ = Db::name('admin')->where('admin_id',$request['admin_id'])->field('cid,mobile,nickname')->find();
            $pawn = Db::name('pawn')->where('pawn_id',$request['pawn_id'])->field('store_id,store_no,store_name,store_no,user_id,pawn_name,new_value')->find();
            $data = [
                'status'    => $status,
                'green_id'  => $request['admin_id'],
                'green_remarks' => $request['remarks'],
                'green_time' => time()
            ];
            if($status == 4){
                if(empty($request['remarks'])){
                    api_response('0','请输入否决原因');
                }
            }
            $result = $this->model->where($where)->update($data);
            if(!$result){
                $error = 1;
            }
            //记录日志
            $data_ = [
                'pawn_id'   => $request['pawn_id'],
                'log_type'  => 1,
                'log_user'  => $request['admin_id'],
                'log_time'  => time(),
                'act_desc'  => 'APP端操作'
            ];
            if($status == 3){
                $data_['log_note'] = '出库成功';
                $data_['check_time'] = time();
            }else{
                $data_['log_note'] = '出库否决';
                $data_['crefuse_time'] = time();
            }
            $log = Db::name('pawn_log')->add($data_);
            if(!$log){
                $error = 1;
            }
            $uu = Db::name('message')->where(['type'=>5,'comm_id'=>$request['pawn_id'],'admin_id'=>$request['admin_id'],'is_del'=>1])->value('is_del');
            if(!empty($uu)){
                $u = Db::name('message')->where(['type'=>5,'comm_id'=>$request['pawn_id'],'admin_id'=>$request['admin_id']])->update(['is_del'=>9]);
                if(!$u){
                    $error = 1;
                }
            }
            if(!empty($xd_id)){
                //发送站内信
                $serialize = [
                    'store_id'      => $pawn['store_id'],
                    'user_id'       => $pawn['user_id'],
                    'pawn_id'       => $request['pawn_id'],
                    'store_name'    => $pawn['store_name'],
                    'store_no'      => $pawn['store_no'],
                    'stp_time'      => date('Y-m-d H:i:s',time()),
                    'pawn_name'     => $pawn['pawn_name']
                ];
                if($status == 3){
                    $serialize['type'] = 1;
                }else{
                    $serialize['type'] = 2;
                }

                $data__ = [
                    [
                        'admin_id'  => $xd_id,
                        'comm_id'   => $request['pawn_id'],
                        'title'     => $status == 3 ? '出库成功' : '出库否决',
                        'type'      => 5,
                        'content'   => serialize($serialize),
                        'add_time'  => time()
                    ]
                ];
                //获取全部分控
                $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
                if(!empty($fk_list)){
                    foreach ($fk_list as $key=>$val){
                        $data__ []= [
                            'admin_id'  => $val['admin_id'],
                            'comm_id'   => $request['pawn_id'],
                            'title'     => $status == 3 ? '出库成功' : '出库否决',
                            'type'      => 5,
                            'content'   => serialize($serialize),
                            'add_time'  => time()
                        ];
                    }
                }
                $message = Db::name('message')->insertAll($data__);
                if(!$message){
                    $error = 1;
                }

                //出库时减少评估总值从新计算剩余可用额度和审批抵押率
                if($status == 3){
                    //获取家具信息
                    $pawn = $this->model->where($where)->field('user_id,pawn_value,pawn_name,user_name')->find();
                    //出库后减去当前家具的评估值
                    $assess_total = Db::name('user_credit')->where('user_id',$pawn['user_id'])->setDec('assess_total',$pawn['pawn_value']);
                    if(!$assess_total){
                        $error = 1;
                    }
                    //获取用户最新评估值
                    $user_credit = Db::name('user_credit')->where('user_id',$pawn['user_id'])->field('credit,assess_total,check_rate,credit_used,credit_rest')->find();
                    $u_log = $this->userCreditlog($request['admin_id'],1,$pawn['user_id'],'家具出库',
                        '新评估总值'.$user_credit['assess_total'].'原评估总值'.numberFormat($user_credit['assess_total'] + $pawn['pawn_value']).'出库后减少'.$pawn['pawn_value'].'');
                    if(!$u_log){
                        $error = 1;
                    }
                    if($user_credit['assess_total'] > 0){
                        //重新计算剩余可用额度
                        $credit_rest = $user_credit['assess_total'] * ($user_credit['check_rate'] / 100) - $user_credit['credit_used'];
                        if($credit_rest < $user_credit['credit']){
                            $new_credit_rest = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['credit_rest'=>$credit_rest,'updtime'=>time()]);
                            if(!$new_credit_rest){
                                $error = 1;
                            }
                            $u_logs = $this->userCreditlog($request['admin_id'],1,$pawn['user_id'],'家具审核',
                                '剩余可用额度从'.$user_credit['credit_rest'].'变更为'.numberFormat($credit_rest).'');
                            if(!$u_logs){
                                $error = 1;
                            }
                        }
                        //更新当前抵押率(已使用贷款本金/评估总值)
                        $now_rate_ = $user_credit['credit_used'] / $user_credit['assess_total'];
                        //当前抵押率大于审批抵押率报警
                        if($now_rate_ > $user_credit['check_rate']){
                            $rate_status = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['rate_status'=>2,'updtime'=>time()]);
                            if(!$rate_status){
                                $error = 1;
                            }
                            //发送站内信
                            $serializes = [
                                'type'          => 3,
                                'value'         => $pawn['pawn_name'],
                                'user_name'     => $pawn['user_name'],
                                'store_id'      => $pawn['store_id'],
                                'store_name'    => $pawn['store_name'],
                                'pawn_name'     => date('Y-m-d H:i:s',time()),
                                'remarks'       => '',
                            ];
                            $datas = [
                                [
                                    'admin_id'  => $xd_id,
                                    'title'     => '抵押率异常',
                                    'type'      => 7,
                                    'content'   => serialize($serializes),
                                    'add_time'  => time()
                                ]
                            ];
                            if(!empty($fk_list)){
                                foreach ($fk_list as $key=>$val){
                                    $datas []= [
                                        'admin_id'  => $val['admin_id'],
                                        'comm_id'   => $request['pawn_id'],
                                        'title'     => '抵押率异常',
                                        'type'      => 7,
                                        'content'   => serialize($serialize),
                                        'add_time'  => time()
                                    ];
                                }
                            }
                            $messages = Db::name('message')->insertAll($datas);
                            if(!$messages){
                                $error = 1;
                            }
                            $rote = 1;
                        }else{
                            if(floor($now_rate_) > 0){
                                $now_rate = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['now_rate'=>numberFormat($now_rate_)]);
                                if(!$now_rate){
                                    $error = 1;
                                }
                            }
                        }
                    }else{
                        $new_credit_rest = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['credit_rest'=>0,'now_rate'=>0,'updtime'=>time()]);
                        if(!$new_credit_rest){
                            $error = 1;
                        }
                        $u_logs = $this->userCreditlog($request['admin_id'],1,$pawn['user_id'],'家具审核',
                            '剩余可用额度从'.$user_credit['credit_rest'].'变更为0');
                        if(!$u_logs){
                            $error = 1;
                        }
                    }
                }
            }
            if($error == 0){
                $this->model->commit();
                $body = '';
                if(!empty($admin['cid'])){
                    $title = $status == 3 ? '抵押品出库成功' : '抵押品出库失败';
                    if($status == 3){
                        $body = '您的客户'.$admin['nickname'].'，本次抵押品出库已成功出库，金额为'.Db::name('pawn')->where('pawn_id',$request['pawn_id'])->value('new_value').'元。';
                    }else{
                        $body = '尊敬的'.$admin['nickname'].'，您的本次抵押品出库未能成功出库，请您联系客户经理。';
                    }
                    //消息推送
                    if(!empty($admin['cid'])){
                        pushMessageToSingle(
                            array(
                                'cid' => $admin['cid'],
                                'title' => $title,
                                'body' => $body,
                                'transmissionContent' => json_encode([
                                    'title' => $title,
                                    'body'  => $body
                                ],JSON_UNESCAPED_UNICODE),
                                'type'=>'boss'
                            )
                        );
                    }
                    if(!empty($admin_['cid'])){
                        pushMessageToSingle(
                            array(
                                'cid' => $admin_['cid'],
                                'title' => $title,
                                'body' => $body,
                                'transmissionContent' =>  json_encode([
                                    'title' => $title,
                                    'body'  => $body
                                ],JSON_UNESCAPED_UNICODE),
                                'type'=>'boss'
                            )
                        );
                    }
                }
                if(!empty($admin['mobile'])){
                    //发送短信给信贷
                    send_sms($admin['mobile'],$body);
                }
                if(!empty($admin_['mobile'])){
                    //发送短信给风控
                    send_sms($admin_['mobile'],$body);
                }
                //抵押率异常
                if($rote == 1){
                    $tpl = '抵押率异常';
                    $tpl_ = $pawn['store_name'].'店铺的'.$pawn['pawn_name'].'家具抵押率异常';
                    if(!empty($admin_['cid'])){
                        pushMessageToSingle(
                            array(
                                'cid' => $admin_['cid'],
                                'title' => $tpl,
                                'body' => $tpl_,
                                'transmissionContent' =>  json_encode([
                                    'title' => $tpl,
                                    'body'  => $tpl_
                                ],JSON_UNESCAPED_UNICODE),
                                'type'=>'boss'
                            )
                        );
                    }
                    if(!empty($admin['cid'])){
                        pushMessageToSingle(
                            array(
                                'cid' => $admin['cid'],
                                'title' => $tpl,
                                'body' => $tpl_,
                                'transmissionContent' =>  json_encode([
                                    'title' => $tpl,
                                    'body'  => $tpl_
                                ],JSON_UNESCAPED_UNICODE),
                                'type'=>'boss'
                            )
                        );
                    }
                    if(!empty($admin['mobile'])){
                        //发送短信给信贷
                        send_sms($admin['mobile'],$tpl_);
                    }
                    if(!empty($admin_['mobile'])){
                        //发送短信给风控
                        send_sms($admin_['mobile'],$tpl_);
                    }
                }
                api_response('1',SUCCESS_INFO);
            }else{
                $this->model->rollback();
                api_response('0',ERROR_INFO);
            }
        }catch(\Exception $e){
            $this->rollback();
            api_response('0',ERROR_INFO);
        }
    }

    /**
     * 确认家具审核
     * @param array $request
     */
    public function checkConfirm($request = array()){
        check_param('pawn_id,admin_id');
        $this->model->startTrans();
        $error = 0;
        $rote = 0;
        try{
            $where['pawn_id'] = $request['pawn_id'];
            $data['check_id'] = $request['admin_id'];
            if(!empty($request['remarks'])){
                $data['check_remarks'] = $request['remarks'];
            }
            $data['check_time'] = time();
            $data['status'] = 1;
            $result = $this->model->where($where)->update($data);
            $pg_id = $this->model->where($where)->value('pg_id') ? : 0;
            if(!$result){
                $error = 1;
                $s = 1;
            }

            //获取家具信息
            $pawn = $this->model->where($where)->field('user_id,pawn_value,xd_id,pawn_name,user_name,store_id,store_name')->find();
            //累加评估值
            $assess_total = Db::name('user_credit')->where('user_id',$pawn['user_id'])->setInc('assess_total',$pawn['pawn_value']);
            if(!$assess_total){
                $error = 1;
                $s = 2;
            }
            //获取用户最新评估值
            $user_credit = Db::name('user_credit')->where('user_id',$pawn['user_id'])->field('credit,assess_total,check_rate,credit_used,credit_rest')->find();
            $u_log = $this->userCreditlog($request['admin_id'],1,$pawn['user_id'],'家具审核',
                '新评估总值'.$user_credit['assess_total'].'原评估总值'.numberFormat($user_credit['assess_total'] - $pawn['pawn_value']).'审核后累加'.$pawn['pawn_value'].'');
            if(!$u_log){
                $error = 1;
                $s = 3;
            }
            if($user_credit['assess_total'] > 0){
                //重新计算剩余可用额度
                $credit_rest = $user_credit['assess_total'] * ($user_credit['check_rate'] / 100) - $user_credit['credit_used'];
                if($credit_rest < $user_credit['credit']){
                    $new_credit_rest = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['credit_rest'=>$credit_rest,'updtime'=>time()]);
                    if(!$new_credit_rest){
                        $error = 1;
                        $s = 4;
                    }
                    $u_logs = $this->userCreditlog($request['admin_id'],1,$pawn['user_id'],'家具审核',
                        '剩余可用额度从'.$user_credit['credit_rest'].'变更为'.numberFormat($credit_rest).'');
                    if(!$u_logs){
                        $error = 1;
                        $s = 5;
                    }
                }
                //更新当前抵押率(已使用贷款本金/评估总值)
                $now_rate_ = $user_credit['credit_used'] / $user_credit['assess_total'];
                //当前抵押率大于审批抵押率报警
                if($now_rate_ > $user_credit['check_rate']){
                    $rate_status = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['rate_status'=>2,'updtime'=>time()]);
                    if(!$rate_status){
                        $error = 1;
                    }
                    //发送站内信
                    $serializes = [
                        'type'          => 3,
                        'value'         => $pawn['pawn_name'],
                        'user_name'     => $pawn['user_name'],
                        'store_id'      => $pawn['store_id'],
                        'store_name'    => $pawn['store_name'],
                        'pawn_name'     => date('Y-m-d H:i:s',time()),
                        'remarks'       => '',
                    ];
                    $datas = [
                        [
                            'admin_id'  => $pawn['xd_id'],
                            'title'     => '抵押率异常',
                            'type'      => 7,
                            'content'   => serialize($serializes),
                            'add_time'  => time()
                        ]
                    ];
                    //获取全部分控
                    $fk_list = Db::name('admin')->where(['role_id'=>2])->field('admin_id')->select();
                    if(!empty($fk_list)){
                        foreach ($fk_list as $key=>$val){
                            $datas []= [
                                'admin_id'  => $val['admin_id'],
                                'title'     => '抵押率异常',
                                'type'      => 7,
                                'content'   => serialize($serializes),
                                'add_time'  => time()
                            ];
                        }
                    }
                    $messages = Db::name('message')->insertAll($datas);
                    if(!$messages){
                        $error = 1;
                        $s = 6;
                    }
                    $rote = 1;
                }else{
                    if(floor($now_rate_) > 0){
                        $now_rate = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['now_rate'=>numberFormat($now_rate_)]);
                        if(!$now_rate){
                            $error = 1;
                            $s = 7;
                        }
                    }
                }
            }else{
                $new_credit_rest = Db::name('user_credit')->where('user_id',$pawn['user_id'])->update(['credit_rest'=>0,'now_rate'=>0,'updtime'=>time()]);
                if(!$new_credit_rest){
                    $error = 1;
                }
                $u_logs = $this->userCreditlog($request['admin_id'],1,$pawn['user_id'],'家具审核',
                    '剩余可用额度从'.$user_credit['credit_rest'].'变更为0');
                if(!$u_logs){
                    $error = 1;
                }
            }

            //推送给评估师
            if(!empty($pg_id)){
                //发送站内信
                $serialize = [
                    'type'          => 1,
                    'pawn_id'       => $request['pawn_id'],
                    'store_id'      => $request['store_id'],
                    'admin_id'      => $request['admin_id'],
                    'create_time'   => date('Y-m-d H:i:s',time()),
                ];
                $data__ = [
                    'admin_id'  => $pg_id,
                    'title'     => '评估报告通过',
                    'comm_id'   => $request['pawn_id'],
                    'type'      => 9,
                    'content'   => serialize($serialize),
                    'add_time'  => time()
                ];
                $message = Db::name('message')->add($data__);
                if(!$message){
                    $error = 1;
                    $s = 8;
                }
            }
            if($error == 0){
                $this->model->commit();
                $admin = Db::name('admin')->where('admin_id',$pg_id)->field('cid,mobile,nickname')->find();
                $user = Db::name('users')->where('user_id',$pawn['user_id'])->field('cid,mobile,nickname')->find();
                //消息推送给评估师
                $body = $user['nickname'].'的家具评估报告通过';
                if(!empty($admin['cid'])){
                    pushMessageToSingle(
                        array(
                            'cid' => $admin['cid'],
                            'title' => $body,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $body,
                                'body' => $body,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                }
                if(!empty($user['cid'])){
                   //推送消息给客户
                   pushMessageToSingle(
                       array(
                           'cid' => $user['cid'],
                           'title' => $body,
                           'body' => $body,
                           'transmissionContent' => json_encode([
                               'title' => $body,
                               'body' => $body,
                           ],JSON_UNESCAPED_UNICODE),
                           'type'=>'client'
                       )
                   );
                }
                if(!empty($admin['mobile'])){
                    //发送短信给评估师
                    send_sms($admin['mobile'],$body);
                }
                if(!empty($user['mobile'])){
                    //发送消息给客户
                    send_sms($user['mobile'],$body);
                }

                //抵押率异常
                if($rote == 1){
                    $admin__ = Db::name('admin')->where('admin_id',$pawn['xd_id'])->field('cid,mobile,nickname')->find();
                    $admin_ = Db::name('admin')->where('admin_id',$request['admin_id'])->field('cid,mobile,nickname')->find();
                    $tpl = '抵押率异常';
                    $tpl_ = $pawn['store_name'].'店铺的'.$pawn['pawn_name'].'家具抵押率异常';
                    if(!empty($admin__['cid'])){
                        pushMessageToSingle(
                            array(
                                'cid' => $admin__['cid'],
                                'title' => $tpl,
                                'body' => $tpl_,
                                'transmissionContent' =>  json_encode([
                                    'title' => $tpl,
                                    'body'  => $tpl_
                                ],JSON_UNESCAPED_UNICODE),
                                'type'=>'boss'
                            )
                        );
                    }
                    if(!empty($admin_['cid'])){
                        pushMessageToSingle(
                            array(
                                'cid' => $admin_['cid'],
                                'title' => $tpl,
                                'body' => $tpl_,
                                'transmissionContent' =>  json_encode([
                                    'title' => $tpl,
                                    'body'  => $tpl_
                                ],JSON_UNESCAPED_UNICODE),
                                'type'=>'boss'
                            )
                        );
                    }
                    if(!empty($admin__['mobile'])){
                        //发送短信给信贷
                        send_sms($admin__['mobile'],$tpl_);
                    }
                    if(!empty($admin_['mobile'])){
                        //发送短信给风控
                        send_sms($admin_['mobile'],$tpl_);
                    }
                }
                api_response('1',SUCCESS_INFO);
            }else{
                $this->model->rollback();
                api_response('0',ERROR_INFO);
            }
        }catch (\Exception $e){
            $this->model->rollback();
            api_response('0',ERROR_INFO);
        }
    }

    /**
     * 评估师评估
     * @param array $request
     */
    public function estimate($request = array()){
        check_param('pawn_id,admin_id,pawn_value,pawn_cost');
        $pawn = Db::name('pawn')->where('pawn_id',$request['pawn_id'])->field('pawn_id,wood_id,store_id,store_no,store_name,pawn_name,pawn_num,xd_id')->find();
        //判断该家具是否存在图片
        $pawn_img = Db::name('pawn_imgs')->where(['pawn_id'=>$request['pawn_id'],'pawn_type'=>1])->value('pawn_id');
        if(empty($pawn_img)){
            api_response('0','该家具未上传图片不能进行评估');
        }
        //查询是否存在摄像头
        $is_store_monitoring = Db::name('store_monitoring')->where(['is_bad'=>0,'status'=>1,'store_id'=>$pawn['store_id']])->value('store_id');
        if(empty($is_store_monitoring)){
            api_response('0','请先绑定门店摄像头');
        }
        //组件数量
        $group_count = Db::name('pawn_one')->where(['pawn_id'=>$request['pawn_id']])->count() ? : 0;
        if($pawn['pawn_num'] != $group_count){
            api_response('0','请先完善组件图片信息或RFID信息');
        }
        //组件家具图片是否存在
        $group = Db::name('pawn_one')->where(['pawn_id'=>$request['pawn_id']])->field('one_id')->select();
        $is_img = 0;
        if(!empty($group)){
            foreach ($group as $val){
                $curr = Db::name('pawn_imgs')->where(['one_id'=>$val['one_id'],'pawn_type'=>2])->value('img_url');
                if(empty($curr)){
                    $is_img = 1;
                }
            }
        }else{
            $is_img = 1;
        }
        if($is_img == 1){
            api_response('0','请先完善该家具的组件图片');
        }

        $this->model->startTrans();
        $error = 0;
        try{
            $data = [
                'pg_id'     => $request['admin_id'],
                'pg_time'   => time(),
                'pawn_value'=> $request['pawn_value'],
                'new_value' => $request['pawn_value'],
                'alarm_value'=> Db::name('pawn_wood')->where('id',$pawn['wood_id'])->value('alarm_value'),
                'pawn_cost' => $request['pawn_cost'],
                'rf_time'   => time(),
                'status'    => 0
            ];
            $where['pawn_id'] = $request['pawn_id'];
            $result = $this->model->where($where)->update($data);
            if(!$result){
                $error = 1;
            }
            //发送站内信
            $serialize = [
                'type'          => 0,
                'pawn_id'       => $request['pawn_id'],
                'store_id'      => $request['store_id'],
                'admin_id'      => $request['admin_id'],
                'create_time'   => date('Y-m-d H:i:s',time()),
            ];
            $data__ = [
                'admin_id'  => $request['admin_id'],
                'title'     => '提交评估成功',
                'type'      => 9,
                'content'   => serialize($serialize),
                'add_time'  => time()
            ];
            $message = Db::name('message')->add($data__);
            if(!$message){
                $error = 1;
            }

            if($error == 0){
                $this->model->commit();
                $admin = Db::name('admin')->where('admin_id',$request['admin_id'])->field('cid,mobile')->find();
                $admin_ = Db::name('admin')->where('admin_id',$pawn['xd_id'])->field('cid,mobile')->find();
                $tit = '提交评估成功';
                $body = $pawn['store_name'].'门店编号为'.$pawn['store_no'].'下的家具'.$pawn['pawn_name'].'提交评估成功';
                if(!empty($admin['cid'])){
                    //消息推送
                    pushMessageToSingle(
                        array(
                            'cid' => $admin['cid'],
                            'title' => $tit,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                    pushMessageToSingle(
                        array(
                            'cid' => $admin_['cid'],
                            'title' => $tit,
                            'body' => $body,
                            'transmissionContent' => json_encode([
                                'title' => $tit,
                                'body' => $body,
                            ],JSON_UNESCAPED_UNICODE),
                            'type'=>'boss'
                        )
                    );
                    //批量推送所有风控
                    $admin_list = Db::name('admin')->where('role_id',2)->field('cid,mobile')->select();
                    if(!empty($admin_list)){
                        foreach ($admin_list as $key=>$val){
                            pushMessageToSingle(
                                array(
                                    'cid' => $val['cid'],
                                    'title' => $tit,
                                    'body' => $body,
                                    'transmissionContent' => json_encode([
                                        'title' => $tit,
                                        'body' => $body,
                                    ],JSON_UNESCAPED_UNICODE),
                                    'type'=>'boss'
                                )
                            );
                        }
                    }
                }
                if(!empty($admin['mobile'])){
                    //发送短信
                    send_sms($admin['mobile'],$body);
                }
                if(!empty($admin_['mobile'])){
                    //发送信贷短信
                    send_sms($admin_['mobile'],$body);
                }
                api_response('1',SUCCESS_INFO);
            }else{
                $this->model->rollback();
                api_response('0',ERROR_INFO);
            }
        }catch (\Exception $e){
            $this->model->rollback();
            api_response('0',ERROR_INFO);
        }
    }

}