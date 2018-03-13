<?php
namespace app\bossapi\controller;
use app\bossapi\model\Pawn;
use think\Db;
class Crontab extends Base {

    public function index(){
        //设置运行时间:永不超时
        set_time_limit (0);
        //开启缓冲
        ob_implicit_flush ();
        //IP地址
        $ip = "192.168.1.99";
        //监听端口
        $port = 7200;
        //创建socket
        $socket = socket_create (AF_INET, SOCK_STREAM, 0);
        if (!$socket){
            die("创建socket失败！").socket_strerror(socket_last_error());
        }
        //绑定socket
        $bind = socket_bind ($socket, $ip, $port);
        if (!$bind){
            die("绑定.socket失败！").socket_strerror ($bind);
        }
        //监听socket
        $listen = socket_listen ($socket);
        if (!$listen){

            die("监听失败！").socket_strerror ($listen);
        }
        echo "{$port}端口监听成功!";
    }

    public function grab1(){
        $this->grabPawn('刺猬紫檀',1);
    }

    public function grab2(){
        $this->grabPawn('交趾黄檀',2);
    }

    public function grab3(){
        $this->grabPawn('微凹黄檀',3);
    }

    public function grab4(){
        $this->grabPawn('大果紫檀',4);
    }

    public function grab5(){
        $this->grabPawn('非洲崖豆木',5);
    }

    public function grab6(){
        $this->grabPawn('阔叶黄檀',6);
    }

    public function grab7(){
        $this->grabPawn('奥氏黄檀',7);
    }

    public function grabPawn($keyword,$wood_id){
        $url = "http://www.yuzhuprice.com/findProductPriceByCondition.jspx?categoryName=红木&prName={$keyword}&market=ORG01&t1=&t2=";
        $output = self::sendCurl( $url );
        //匹配表格内容
        $tbody = explode( '<tbody>',$output );
        file_put_contents('table.html',$tbody);
        //表格列表
        $tbody_list = substr($tbody[2],strpos($tbody[2],'<tr>'),strripos($tbody[2],'</tbody>'));
        preg_match_all('#<td.+/td>#isU', $tbody_list, $r);
        $table_arr = array_map('trim', array_map('strip_tags', $r[0]));
        //$arr = array_chunk(array_slice($table_arr,14),7);
        if($table_arr[6] == date('Y-m-d',time())){
            $data = [
                'type'      => 1,
                'wood_id'   => $wood_id,
                'is_price'  => 2,
                'wood_name' => $keyword,
                'new_index' => $table_arr[3],
                'new_time'  => strtotime($table_arr[6]),
                'create_time' => time()
            ];
            $res = Db::name('pawn_price')->insert($data);
            if($res){
                //获取昨日的价格
                $yseterday = Db::name('pawn_price')->whereTime('create_time','yesterday')->value('new_index');
                //获取最新一条家具数据-----最新评估值=人工费+成本价*(1+new_lrr)
                $pawn = Db::name('pawn')->where('wood_id',$wood_id)->order('addtime desc')->field('pawn_price,pawn_cost')->find();
                if(!empty($yseterday) && !empty($table_arr[3])){
                    //计算涨跌幅 (今日价格-初始价格)/初始价格
                    $new_lrr = ($table_arr[3] - $pawn['pawn_price']) / $pawn['pawn_price'];
                    //计算最新评估值
                    $new_value = $pawn['pawn_price'] + $pawn['pawn_cost'] *(1+$new_lrr);
                    //更新家具表
                    $data_ = [
                        'new_value' => $new_value,
                        'new_time'  => time(),
                        'new_lrr'   => $new_lrr
                    ];
                }elseif(!empty($table_arr[3])){
                    $new_value = $pawn['pawn_price'] + $pawn['pawn_cost'];
                    $data_ = [
                        'new_value' => $new_value,
                        'new_time'  => time(),
                        'new_lrr'   => 0
                    ];
                }
                Db::name('pawn')->where('wood_id',$wood_id)->update($data_);
            }
        }
    }

    /**
     * 涨跌幅异常报警
     */
    public function updateAlarm(){
        $list = Db::name('pawn')->where(['status'=>['in','1,2']])
            ->field('pawn_id,user_id,new_lrr,alarm_value,new_value,xd_id,store_name,pawn_name')
            ->select();
        $model = new Pawn();
        if(!empty($list)){
            $data = array();
            //将数据切割处理，每次更新100条
            $arr = array_chunk($list,100);
            foreach ($arr as $key=>$val){
                $row = array();
                foreach ($val as $v){
                    //new_lrr(涨跌幅) >= alarm_value(预警值),alarm_status(报警状态)从1改为2
                    if($v['alarm_status'] == 1 && $v['new_lrr'] >= $v['alarm_value']){
                        $data[] = [
                            'pawn_id'   => $v['pawn_id'],
                            'alarm_status'  => 2
                        ];
                        $row = [
                            'xd_id'     => $v['xd_id'],
                            'store_name'=> $v['store_name'],
                            'pawn_name' => $v['pawn_name']
                        ];
                    }
                }
                if(!empty($data)){
                    $result = $model->saveAll($data);
                    if($result){
                        //批量推送报警消息
                        if($row['xd_id'] != 0){
                            $admin = Db::name('admin')->where('admin_id',$row['xd_id'])->field('cid,mobile,nickname')->find();
                            $body = $row.['store_name'].'店铺的家具'.$row['pawn_name'].'涨跌幅异常，请及时查看';
                            pushMessageToSingle(
                                array(
                                    'cid' => $admin['cid'],
                                    'title' => '涨跌幅异常',
                                    'body' => $body,
                                    'transmissionContent' => json_encode([
                                        'title' => '涨跌幅异常',
                                        'body' => $body,
                                    ],JSON_UNESCAPED_UNICODE),
                                    'type'=>'boss'
                                )
                            );
                            if(!empty($admin['mobile'])){
                                send_sms($admin['mobile'],$body);
                            }
                        }
                    }
                    unset($data,$v,$row);
                }
            }
        }
    }

    /**
     * 重新计算评估总值
     */
    public function updateCredit(){
        $list = Db::name('pawn')->where(['status'=>['in','1,2']])
            ->field('pawn_id,user_id,new_lrr,alarm_value,new_value,pawn_name,user_name,store_id,store_name,xd_id')
            ->group('user_id')
            ->select();
        if(!empty($list)){
            $error = array();
            foreach ($list as $key=>$val){
                $credit = Db::name('user_credit')->where('user_id',$val['user_id'])->field('credit,credit_used,check_rate,credit_rest')->find();
                //获取当前用户的评估总值
                $assess_total = Db::name('pawn')->where('user_id',$val['user_id'])->sum('new_value');
                //计算剩余可用额度(评估总值*审批抵押率-已使用额度)
                $credit_rest = $assess_total * ($credit['check_rate'] / 100) - $credit['credit_used'];
                //重新计算当前抵押率(已使用贷款本金/评估总值)
                $now_rate = $credit['credit_used'] / $assess_total;
                if($credit_rest < $credit['credit']){
                    $data['credit_rest'] = $credit_rest;
                    $data['now_rate'] = $now_rate;
                    $data['assess_total'] = $assess_total;
                    $data['updtime'] = time();
                    //抵押率状态(1正常,2报警:当前抵押率>=审批抵押率)
                    if($now_rate >= $credit['check_rate']){
                        $data['rate_status'] = 2;
                        $error[] = [
                            'user_id'   => $val['user_id'],
                            'pawn_name' => $val['pawn_name'],
                            'user_name' => $val['user_name'],
                            'store_id'  => $val['store_id'],
                            'store_name'=> $val['store_name'],
                            'xd_id'     => $val['xd_id']
                        ];
                    }
                    Db::name('user_credit')->where('user_id',$val['user_id'])->update($data);
                    if(floor($credit_rest) > 0){
                        $data_['user_id'] = $val['user_id'];
                        $data_['act_desc'] = '抓取数据';
                        $data_['log_note'] = '剩余可用额度从'.$credit['credit_rest'].'变更为'.numberFormat($credit_rest);
                        $data_['log_time'] = time();
                        $data_['log_type'] = 1;
                        $data_['log_user'] = $val['xd_id'];
                        Db::name('user_credit_log')->insert($data_);
                    }
                }
            }
            if(!empty($error)){
                $datas = array();
                $ids = array();
                foreach ($error as $key=>$val){
                    //发送站内信
                    $serializes = [
                        'type'          => 3,
                        'value'         => $val['pawn_name'],
                        'user_name'     => $val['user_name'],
                        'store_id'      => $val['store_id'],
                        'store_name'    => $val['store_name'],
                        'pawn_name'     => date('Y-m-d H:i:s',time()),
                        'remarks'       => '',
                    ];
                    $datas[] = [
                        'admin_id'  => $val['xd_id'],
                        'title'     => '抵押率异常',
                        'type'      => 7,
                        'content'   => serialize($serializes),
                        'add_time'  => time()
                    ];
                    $ids[] = $val['xd_id'];
                }
                $messages = Db::name('message')->insertAll($datas);
                if($messages){
                    //抵押率异常报警推送
                    foreach ($error as $val){
                        $admin__ = Db::name('admin')->where('admin_id',$val['xd_id'])->field('cid,mobile,nickname')->find();
                        $tpl = '抵押率异常';
                        $tpl_ = $val['store_name'].'店铺的'.$val['pawn_name'].'家具抵押率异常';
                        if(!empty($admin_['cid'])){
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
                        if(!empty($admin['cid'])){
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
                }
            }
        }
    }


    /**
     * @param $url
     * @return mixed
     */
    public static function sendCurl ( $url ) {
        $curl = curl_init();
        curl_setopt( $curl,CURLOPT_URL,$url );
        curl_setopt( $curl,CURLOPT_RETURNTRANSFER,true );
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//规避证书
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 防止302 盗链
        $output = curl_exec( $curl );
        curl_close( $curl );
        //去除回车、空格等
        $result=str_replace(array("\r\n","\n","\r","\t",chr(9),chr(13),"amp;",""),'',$output);
        return $result;
    }

}
