<?php
namespace app\admin\controller;
use app\admin\logic\PawnLogic;
use app\admin\logic\LoanLogic;
use think\AjaxPage;
use think\Page;
use think\Db;
class Pawn extends Base { 
    /**
     * 抵押品状态:status
     * -3待评估
     * -2无效作废
     * -1审核失败
     * 0待审核
     * 1审核通过
     * 2解押待审核
     * 3解押通过
     * 4解押失败
     * 5自动解押
     */
    
    /**
     * 新增抵押品列表
     */
    public function pawnlist()
    {   
        $this->get_pawn_list();
        $role_id = session('admin_info')["role_id"];
        //信贷经理(市场总监)默认显示:待评估
        if($role_id == 4 || $role_id ==1 || $role_id ==5)
        {
            $status = I('status',-3);
        }
        //评估师默认显示:待评估
        if($role_id == 3 || $role_id ==1)
        {
            $status = I('status',-3);
        }
        //风控总监默认显示:待审核
        if($role_id == 2 || $role_id ==1)
        {
            $status = I('status',0);
        } 
        //下载家具导入模板
        $this->assign('download',SITE.'/public/download/pawntpl.xlsx');
        $this->assign('status',$status);
        $this->assign('role_id',$role_id);
        return $this->fetch();
    }
    /**
     *  抵押品列表
     */
    public function greenlist(){
        $role_id = session('admin_info')["role_id"];
        //信贷经理(市场总监)默认显示:未解押
        if($role_id == 4 || $role_id ==1 || $role_id ==5)
        {
            $status = I('status',1);
        }
        //风控总监默认显示:解押待审核
        if($role_id == 2 || $role_id ==1)
        {
            $status = I('status',2);
        } 
        $this->assign('status',$status);
        $this->get_pawn_list($status);
        $this->assign('role_id',$role_id);
        return $this->fetch();
    }
    /**
     * 获取抵押品
     */
    public function get_pawn_list($status=''){
        $user_no = I('user_no/d');
        $store_no = I('store_no/d');
        $pawn_rfid = I('pawn_rfid/d');
        $store_name = I('store_name');
        $pawn_name = I('pawn_name');
        $timegap = urldecode(I('timegap'));
        
        $where = array();
        if($timegap){
            $gap = explode(',', $timegap);
            $begin = $gap[0];
            $end   = $gap[1];
            $where['addtime'] = array('between',array(strtotime($begin),strtotime($end)));
        }
        $status = empty($status) ? I('status') : $status;
        //新增抵押品列表:status<= 0
        if($status <= 0)
        {
            
            //判断:评估师可以看到所有待评估家具,和自己已评估过的家具
            if(session('admin_info')["role_id"] ===  3)
            {
                if($status == -3)
                {
                    $where['status'] =  $status;
                }
                if($status != -3)
                {
                   $where['status'] =  $status;
                   $where['pg_id']   = session('admin_id'); 
                }
                
            }else{
                $where['status'] =  $status;
            }
            
        }
        //抵押品列表:status>0
        if($status > 0) 
        {
            $where['status'] = $status;
        }
        //判断:信贷经理只能看到自己的客户  
        if(session('admin_info')["role_id"] === 4)
        {
            
            $where['xd_id']   = session('admin_id');
        }
        $user_no && $where['user_no'] = $user_no;
        $store_no && $where['store_no'] = $store_no;
        $pawn_rfid && $where['pawn_rfid'] = $pawn_rfid;
        $store_name && $where['store_name'] = array('like','%'.$store_name.'%');
        $pawn_name && $where['pawn_name'] = array('like','%'.$pawn_name.'%');
        $export = I('export'); 
        if($export){
            $strTable ='<table width="500" border="1">';
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">客户编号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">门店编号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">客户名称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">门店名称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">家具名称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">规格型号</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">所用材料</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">产地</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">数量</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">定价</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">备注</td>';
            $strTable .= '</tr>';
            $orderList = Db::name('pawn')->field("*,FROM_UNIXTIME(addtime,'%Y-%m-%d') as create_time")->limit(1)->order('pawn_id desc')->select();
            foreach ($orderList as $key => $val) {
                $woodlist = M('pawn_wood')->where("id = {$val['wood_id']}")->find(); 
                    $orderList[$key]['wood_name'] = $woodlist['name'];   
            }
            if($orderList){
                foreach($orderList as $k=>$val){
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_no'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['store_no'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_name'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['store_name'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pawn_name'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pawn_model'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['wood_name'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pawn_area'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pawn_num'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pawn_price'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['pawn_remarks'].'</td>';
                    $strTable .= '</tr>';
                }
            }
             $strTable .='</table>';
            unset($orderList);
            downloadExcel($strTable,'HM');
            exit();
        }
        $count = Db::name('pawn')->where($where)->count(); 
        $Page  = new Page($count,10);
        $list = Db::name('pawn')->where($where)->order("pawn_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($list as $key => $val) {
            //获取当前抵押率和审批抵押率
             $user_credit = M('user_credit')->order('id desc')->select();
             foreach ($user_credit as $k => $v){
                 if($v['user_id'] == $val['user_id']){
                    $list[$key]['now_rate']   = $v['now_rate'];
                    $list[$key]['check_rate'] = $v['check_rate'];
                 }
             }
            //获取材料名称
            $woodlist = M('pawn_wood')->select();
            foreach ($woodlist as $k => $v) {
                if($v['id'] == $val['wood_id']){
                     $list[$key]['wood_name'] = $v['name'];  
                }
            }
            //获取 套件中单件的个数
            $pawn_one = M('pawn_one')->order('one_id desc')->select();
            foreach ($pawn_one as $k => $v) {
                if($v['pawn_id'] == $val['pawn_id'])
                {
                    $list[$key]['one_count'] = M('pawn_one')->where(['pawn_id'=>$v['pawn_id']])->count();
                }
            }
        }

        $show  = $Page->show();
        $this->assign('show',$show);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        C('TOKEN_ON',false);
    }  
    /**
     * 抵押品详情detail
     */
    public function detail($pawn_id){
        //获取抵押品列表
        $order = M('pawn')->where(array('pawn_id'=>$pawn_id))->order('pawn_id desc')->find();
        //获取单件抵押品信息
        $one_list = M('pawn_one')->where(['pawn_id'=>$pawn_id])->order('one_id desc ')->select(); 
        //获取主材料和辅材料名称
        $woodid = M('pawn_wood')->where("id = {$order['wood_id']}")->find(); 
        $woodauxid = M('pawn_wood')->where("id = {$order['wood_auxid']}")->find(); 
        $order['wood_name'] = $woodid['name'];   
        $order['wood_auxname'] = $woodauxid['name'];   
        //抵押品相册
        $pawnimgs = M("pawn_imgs")->where(array('pawn_id'=>$pawn_id,'pawn_type'=>1))->select();
        //获取关联门店信息
        $storeinfo = M('user_store')->where(['store_id'=>$order['store_id']])->order('store_id desc')->find();
        //获取关联客户信息
        $userinfo = M('users')->where(['user_id'=>$order['user_id']])->order('user_id desc')->find(); 
        //获取客户详情user_detail
        $userdetail = M('user_detail')->where(['user_id'=>$order['user_id']])->order('user_id desc')->find();
        //获取当前抵押率和审批抵押率
        $user_credit = M('user_credit')->where(['user_id'=>$order['user_id']])->order('id desc')->find();
        //操作人
        $xd_name = M('admin')->field('user_name')->where(array('admin_id'=>$order['xd_id']))->find();
        $pg_name = M('admin')->field('user_name')->where(array('admin_id'=>$order['pg_id']))->find();
        // 获取操作记录
        $pawnlog = M('pawn_log')->where(array('pawn_id'=>$pawn_id))->order('log_time desc')->select();
        foreach ($pawnlog as $key => $val) {
            if($val['log_type'] == 2){
                $user = M('users')->field('name')->where(['client_no' => $val['log_user']] )->find();
                $pawnlog[$key]['user_name'] = $user['name'];
            }
            if($val['log_type'] == 1){
                $admin = M('admin')->field('user_name')->where(array('admin_id'=>$val['log_user']))->find();
                $pawnlog[$key]['user_name'] = $admin['user_name'];
            }
        }
        $this->assign('pawn_id', $pawn_id);
        $this->assign('pawnimgs', $pawnimgs);
        $this->assign('storeinfo', $storeinfo);
        $this->assign('userinfo', $userinfo); 
        $this->assign('userdetail', $userdetail);
        $this->assign('user_credit', $user_credit);
        $this->assign('xd_name',$xd_name['user_name']);  
        $this->assign('pg_name',$pg_name['user_name']);  
        $this->assign('order',$order);
        $this->assign('status',$order['status']);
        $this->assign('one_list', $one_list);
        $this->assign('pawnlog',$pawnlog);
        $this->assign('role_id',session('admin_info')["role_id"]);
        return $this->fetch();
    }
    /**
     * 添加套件家具
     */
    public function add_pawn()
    {
        $order = I('post.');  
        $orderLogic = new PawnLogic();
        if(IS_POST)
        {  
            
            //判断:门店信息是否存在
            $storeinfo = M('user_store')->where(['store_id'=>I('store_id')])->order('store_id desc')->find();
            if(!$storeinfo)
            {
                $this->error('门店信息不存在,请先上传门店信息',U("Admin/Pawn/pawnlist"));
                exit;
            }
            //判断2:审批结论复核通过后,才能添加.
            $user_id = $storeinfo['user_id'];
             $users = M('users')->field('is_approval')->where(['user_id'=>$user_id])->order('user_id desc')->find();
                    if($users['is_approval'] != 2)
                    {
                        $this->error('此客户审批结论复核未通过,请核实!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }
            //关联门店信息
            $order['store_id'] = $storeinfo['store_id'];//门店ID
            $order['store_no'] = $storeinfo['store_no'];//门店编号
            $order['store_name'] = $storeinfo['store_name'];//门店名称
            $order['user_id'] = $storeinfo['user_id'];//客户ID
            $order['user_no'] = $storeinfo['user_no'];//客户编号
            $order['user_name'] = $storeinfo['user_name'];//客户名称
            $order['addtime'] = time();  
			$order['updtime'] = time(); 
            $order['addtype'] = 2;  //添加类型:1excel自动导入,2手动上传
            $order['xd_id'] = session('admin_id');//上传人ID 
            $order['pawn_no'] = date('YmdHis').mt_rand(1000,9999);//家具编号
            // $order['pawn_no'] = $storeinfo['store_no'].$this->get_pawnno($storeinfo['store_id'],4);
            //获取主材料和辅材料名称
            $wood_id = I('wood_id');
            $wood_auxid = I('wood_auxid');
            $woodid = M('pawn_wood')->where(['id'=>$wood_id])->find();
            $woodauxid = M('pawn_wood')->where(['id'=>$wood_auxid])->find();
            $order['wood_name'] = $woodid['name'];
            $order['wood_auxname'] = $woodauxid['name'];
            //抵押品表add添加数据
            // $imgs = SITE.($order['pawn_imgs'][0]);
            // $imgs = 'http://bs.bestshowgroup.com/public/upload/pawn_imgs/2018/03-08/12a942744626c3027278c7aa9bb49271.jpg';
            // $names= '12a942744626c3027278c7aa9bb49271.jpg';
            // qiniuupload($imgs,$names);die;
            $pawn_id = M('pawn')->add($order);
  
            //插入照片
           $orderLogic->pawnimgs($pawn_id,1) ;
            //操作日志
            $orderLogic->pawnLog(1,$pawn_id,'上传套件家具', I('log_note'));    
            if($pawn_id)
            {
                $this->success('添加成功',U("Admin/Pawn/pawnlist"));
                exit();
            }
            else{
                $this->error('添加失败');
            }                
        }    
        //抵押品材料
        $woodlist = M('pawn_wood')->select(); 
        $this->assign('woodlist',$woodlist);
        //抵押品相册
        $pawnimgs = M("pawn_imgs")->where(array('pawn_id'=>I('GET.pawn_id', 0)))->order('img_id desc')->select();
        // $this->assign('pawnimgs', $pawnimgs);  // 商品相册 
        $this->assign('role_id',session('admin_info')["role_id"]);    
        return $this->fetch();
    }
    /**
     * 修改套件家具
     */
    public function edit_pawn(){
        $data = I('param.'); 
        $pawn_id = I('param.pawn_id'); 
        $orderLogic = new PawnLogic();
        $pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->find();
        if($pawn['status'] >= 1){
            $this->error('评估审核通过的家具不允许修改');
            exit;
        }
        if(IS_POST)
        { 
            
            $data['updtime'] = time(); 
            $order_id = M('pawn')->where(['pawn_id'=>$pawn_id])->update($data); 
            $new_pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->find();
            $new_pawnid =$new_pawn['pawn_id'];       
           //插入照片
           $orderLogic->pawnimgs($new_pawnid,1) ;
           //操作日志
            $orderLogic->pawnLog(1,$new_pawnid,'修改套件家具', I('log_note'));    
            if($order_id)
            {
                $this->success('添加成功',U("Admin/Pawn/pawnlist"));
                exit();
            }
            else{
                $this->error('添加失败');
            }              
            
        }
        //抵押品材料
        $woodlist = M('pawn_wood')->select(); 
        $this->assign('woodlist',$woodlist);  
        //抵押品相册
        $pawnimgs = M("pawn_imgs")->where(array('pawn_id'=>I('GET.pawn_id', 0),'pawn_type'=>1))->order('img_id desc')->select();
        $this->assign('pawnimgs', $pawnimgs);  // 商品相册
        $this->assign('order',$pawn);
        $this->assign('role_id',session('admin_info')["role_id"]);
        return $this->fetch();
    } 
    /**
     * 抵押品操作日志
     */
    public function pawn_log(){       
        $timegap = urldecode(I('timegap'));
        if($timegap){
            $gap = explode('-', $timegap);
            $timegap_begin = $gap[0];
            $timegap_end = $gap[1];
            $begin = strtotime($timegap_begin);
            $end = strtotime($timegap_end);
        }else{
            //@new 兼容新模板
            $timegap_begin = urldecode(I('timegap_begin'));
            $timegap_end = urldecode(I('timegap_end'));
            $begin = strtotime($timegap_begin);
            $end = strtotime($timegap_end);
        }
        //搜索条件
        $condition = array();
        $log =  M('pawn_log');
        if($begin && $end){
            $condition['log_time'] = array('between',"$begin,$end");
        }
        $admin_id = I('admin_id');
        if($admin_id >0 ){
            $condition['log_user'] = $admin_id;
        }
        I('pawn_id') != '' ? $condition['pawn_id'] = I('pawn_id') : false;
        I('log_user') != '' ? $condition['log_user'] = I('log_user') : false;
        $count = $log->where($condition)->count();
        $Page = new Page($count,10);

        foreach($condition as $key=>$val) {
            $Page->parameter[$key] = urlencode($begin.'_'.$end);
        }
        $show = $Page->show();
        $list = $log->where($condition)->order('id desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        //操作人
        $admin = M('admin')->getField('admin_id,user_name');
        //获取前台用户名
        foreach ($list as $key =>$val) {
            if($val['log_type'] == 2){
			$user = M('users')->field('name')->where(['client_no' => $val['log_user']] )->find();
                $list[$key]['user_name'] = $user['name'];
            }
        }
        $this->assign('timegap_begin',$timegap_begin);
        $this->assign('timegap_end',$timegap_end);
        $this->assign('list',$list);
        $this->assign('pager',$Page);
        $this->assign('page',$show);    
        $this->assign('admin',$admin);      
        return $this->fetch();
    }
    /**
     * 搜索门店
     * 1,此项为信贷经理操作
     * 2,信贷经理只能搜索到自己客户下的所有门店
     */
    public function search_store()
    {
        $search_key = trim(I('search_key'));        
        if($search_key)    
        {
            $xd_id = $_SESSION['admin_id']; 
            $users = M('users')->field('user_id')->where(['xd_id'=>$xd_id])->select();
            foreach ($users as  $val) {
                $user_id[] = $val['user_id'];
            }
            //查询条件:信贷经理自己的客户下的所有门店
            $condition['user_id'] = ['in',implode(',',$user_id)];
            $list = M('user_store')->where(" store_no like '%$search_key%' ")->where($condition)->select();        
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['store_id']}'>{$val['store_name']} - {$val['store_no']}</option>";
            }                        
        }      
       
    } 
    /**
     * 删除抵押品
     */
    public function delpawn()
    {
        $order_id = I('post.order_id/d',0);
        // $model = M("pawn");
        // $model->where('pawn_id ='.$_GET['id'])->delete();
        // $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   
        // $this->ajaxReturn($return_arr);

        $order = M('pawn')->where(array('pawn_id'=>$order_id))->find();
        if(empty($order)){
            return ['status'=>-1,'msg'=>'不存在'];
        };
        $del_order = M('pawn')->where(array('pawn_id'=>$order_id))->delete();
        
        if(empty($del_order)){
            return ['status'=>-1,'msg'=>'删除失败'];
        };
        $return_arr = array('status' => 1,'msg' => '操作成功','data'  =>'',);   
        $this->ajaxReturn($return_arr);
    }
    /**
     * 评估师评估
     */
    public function pg_pawn()
    {
        $pawn_id = I('pawn_id');
        $pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->find();
        if($pawn['status'] >= 1){
            $this->error('审核通过的家具不允许再次评估');
            exit;
        }
        if(IS_POST)
        {
            //判断:没有单件家具,不能评估
            $onecount = Db::name('pawn_one')->where(['pawn_id'=>$pawn_id])->count(); 
            if($onecount <= 0)
            {
                $this->error('没有单件家具,不能评估!请添加!');exit;
            }
            //判断:已添加的单件家具个数与指定的单件家具个数不相等
            if($onecount != $pawn['pawn_num'])
            {
                $this->error('单件家具未添满,不能评估!请添满单件家具!');exit;
            }
            //判断:没有家具相册,不能评估
            $pawnimgs_count = Db::name('pawn_imgs')->where(['pawn_id'=>$pawn_id])->count(); 
            if($pawnimgs_count <= 0)
            {
                $this->error('没有家具相册,不能评估!请添加!');exit;
            }
            //判断:家具相册不全,不能评估
            if($pawnimgs_count != ($pawn['pawn_num']+1))
            {
                $this->error('家具相册未添满,不能评估!请添满家具相册!');exit;
            }
            //判断:摄像头不存在不能评估
            $is_store_monitoring = Db::name('store_monitoring')->where(['is_bad'=>0,'status'=>1,'store_id'=>$pawn['store_id']])->value('store_id');
            if(!$is_store_monitoring)
            {
                $this->error('门店摄像头不存在,不能评估!请添加!');exit;
            }
            //提交评估信息
            $data['pawn_value'] = I('pawn_value');
            $data['pawn_cost']  = I('pawn_cost');
            $data['pg_id']      = $_SESSION['admin_id'];
            $data['pg_time']    = time();
            $data['status']     = 0;
            $res = Db::name('pawn')->where(['pawn_id' => $pawn_id])->update($data);
            if($res)
            {
                $orderLogic = new PawnLogic();
                //初始化最新评估值
                $orderLogic->pawn_newvalue($pawn_id);
                //记录操作日志
                $orderLogic->pawnLog(1,$pawn_id,'评估师评估', '评估师评估');
                $this->success('提交成功',U("Admin/Pawn/pawnlist"));
                exit();
            }
            else{
                $this->error('提交成功');
            }     
        }
        $this->assign('order',$pawn);
        $this->assign('role_id',session('admin_info')["role_id"]);
        return $this->fetch();
    }
    /**
     * 家具材料列表
     */
    public function woodlist()
    {
        $export = I('export');
        if($export)
        {
            $strTable ='<table width="500" border="1">';
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">材料ID</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">材料名称</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">木材价格指数阈值</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">添加时间</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;" width="*">修改时间</td>';
            $strTable .= '</tr>';
            $orderList = Db::name('pawn_wood')->field("*,FROM_UNIXTIME(addtime,'%Y-%m-%d') as create_time,FROM_UNIXTIME(updtime,'%Y-%m-%d') as update_time")->order('id')->select();
            if($orderList){
                foreach($orderList as $k=>$val){
                    $strTable .= '<tr>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['id'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['name'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['alarm_value'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['create_time'].'</td>';
                    $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['update_time'].'</td>';
                    $strTable .= '</tr>';
                }
            }
             $strTable .='</table>';
            unset($orderList);
            downloadExcel($strTable,'HM_WOOD');
            exit();
        }
        $woodlist = M('pawn_wood')->select();
        $this->assign('woodlist',$woodlist);      
        return $this->fetch();
    }

    /**
     * 添加材料
     */
    public function add_wood()
    {
        $post = I('post.');
        if(IS_POST)
        {  
            $wood['name']        = I('name');//材料名称
            $wood['alarm_value'] = I('alarm_value');;//木材价格指数阈值     
            $wood['addtime']     = time();  
            $wood['updtime']     = time(); 
            //材料表添加数据
            $wood_id = M('pawn_wood')->add($wood);
            //操作日志
            $orderLogic = new PawnLogic();
            $orderLogic->pawnLog(1,$wood_id,'添加家具材料', I('log_note'));    
            if($wood_id)
            {
                $this->success('添加成功',U("Admin/Pawn/woodlist"));
                exit();
            }
            else{
                $this->error('添加失败');
            }                
        } 
         return $this->fetch();           
    }
    /**
     * 编辑材料
     */
    public function edit_wood()
    {
        $wood_id = I('id');
        if(IS_POST)
        {    
            $wood['name']        = I('name');//材料名称
            $wood['alarm_value'] = I('alarm_value');;//木材价格指数阈值   
            $wood['updtime']     = time(); 
            //材料表修改数据
            $wood_id = M('pawn_wood')->where(['id'=>$wood_id])->update($wood);
            //操作日志
            $orderLogic = new PawnLogic();
            $orderLogic->pawnLog(1,$wood_id,'添加家具材料', I('log_note'));    
            if($wood_id)
            {
                $this->success('编辑成功',U("Admin/Pawn/woodlist"));
                exit();
            }
            else{
                $this->error('编辑失败');
            }                
        }
        //抵押品材料
        $woodlist = M('pawn_wood')->where(['id' => $wood_id] )->find(); 
        $this->assign('wood',$woodlist);  
        return $this->fetch();           
    }
    /**
     * 删除家具相册图
     */
    public function del_pawn_imgs()
    {
        $path = I('filename','');
        //删除七牛云图片
        qiniuimgdel($path);
        //删除数据库记录
        M('pawn_imgs')->where("img_url = '$path'")->delete();
    }

    /**
     *  [评估审核]
     *  评估师已提交的评估值
     *  后台风控人员人工审核
     */
    public function pg_check(){
        $loanLogic = new LoanLogic();
        $pawnLogic = new PawnLogic(); 
        $pawn_id  = implode(',' , I('id/a'));
        $data['status']=$status = I('status');
        $data['check_remarks'] = I('remark');
        $data['check_id']      = session('admin_id');
        if($status == 1) $data['check_time'] = time();
        if($status != 1) $data['crefuse_time'] = time();
        $res = M('pawn')->where(['pawn_id'=>$pawn_id])->update($data);
        if(!$res){
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        }  
        if($res){
            /**
             * 1,记录抵押品操作日志
             */
            if($status == 1) $pawnLogic->pawnLog(1, $pawn_id ,'评估审核通过',I('remark'));  
            if($status != 1) $pawnLogic->pawnLog(1, $pawn_id ,'评估审核否决',I('remark'));
            /**
             * 2,评估审核通过累加评估总值
             */
            if($status == 1)
            {
                $condition = array();
                $condition['status']  = 1;
                $condition['pawn_id'] = $pawn_id;
                //获取价格指数表
                $pawn = M('pawn')->where($condition)
                                 ->field('user_id,pawn_value,alarm_value,new_value,new_lrr,alarm_status')
                                 ->order('pawn_id desc')
                                 ->find();
                $user_id     = $pawn['user_id'];     
                $pawn_value  = $pawn['pawn_value']; //初始评估值 
                $alarm_value = $pawn['alarm_value'];//初始预警值 
                $new_value   = $pawn['new_value'];  //最新评估值    
                $new_lrr     = $pawn['new_lrr'];   //最新环比涨跌幅
                $alarm_status= $pawn['alarm_status']; //报警状态
                //获取个人信用表
                $user_credit = M('user_credit')->where(['user_id'=>$user_id])->order('id desc')->find();
                $assess_total = $user_credit['assess_total'];
                //预警值小于浮动值,取初始评估值累加到评估总值
                if($alarm_status == 1)
                {
                    $data['assess_total'] = ['exp', "assess_total + ${pawn_value}"];
                    $user_credit = M('user_credit')->where(['user_id'=>$user_id])->update($data);
                    //3,累加评估总值,记录个人授信操作日志
                        $lognote = 
                        '新评估总值'.($assess_total+$pawn_value).
                        '原评估总值'.$assess_total.
                        '审核后累加'.$pawn_value;
                        $loanLogic->userCreditlog(1, $user_id,'评估审核通过,累加评估总值',$lognote);  
                    if($user_credit)
                    {
                       /**
                         * 5,更新当前抵押率
                         * 最新当前抵押率>=审核抵押率时,发送报警消息
                         */
                        $credit = M('user_credit')->where(['user_id'=>$user_id])->find();
                        $new_assess_total = $credit['assess_total'];
                        $new_credit_used  = $credit['credit_used'];
                        $check_rate       = $credit['check_rate'];
                        //计算最新当前抵押率,并更新数据库
                        $update_now_rate['now_rate'] = ($new_credit_used / $new_assess_total)*100;
                        //计算最新剩余可使用额度,并更新到数据库
                        $credit_rest = ($new_assess_total*$check_rate)/100 - $new_credit_used;
                        if($credit_rest >= 0)
                        {
                            $update_now_rate['credit_rest'] = $credit_rest;
                        }
                        M('user_credit')->where(['user_id'=>$user_id])->update($update_now_rate);
                        $user_credit = M('user_credit')->where(['user_id'=>$user_id])->find();
                        //查询最新当前计算抵押率
                        $check_rate = $user_credit['check_rate'];
                        $now_rate   = $user_credit['now_rate'];
                        //如果最新当前抵押率大于等于审核抵押率,抵押率状态更新为2并报警
                        if($now_rate >= $check_rate)
                        {
                            $rate['rate_status'] = 2;
                            $rate_status = M('user_credit')->where(['user_id'=>$user_id])->update($rate);
                            if($rate_status){
                                //报警:您的当前抵押率已大于审批抵押率???
                                //发送短信+推送
                                //1发短信
                                $pawnLogic->send_BJSMS(1, $pawn_id);
                                //2推送 
                                
                                //6,当前抵押率异常,记录个人授信操作日志
                                $lognote = 
                                '当前抵押率'.($now_rate.'%').
                                '审批抵押率'.($check_rate.'%');
                                $loanLogic->userCreditlog(1, $user_id,'当前抵押率异常',$lognote); 
                            }
                        }
                        //如果最新当前抵押率小于审核抵押率,抵押率状态更新为1
                        if($now_rate < $check_rate)
                        {
                            $rate['rate_status'] = 1;
                            $rate_status = M('user_credit')->where(['user_id'=>$user_id])->update($rate);
                            if($rate_status){
                                //5,当前抵押率变化,记录个人授信操作日志
                                $lognote = 
                                '当前抵押率'.($now_rate.'%').
                                '审批抵押率'.($check_rate.'%');
                                $loanLogic->userCreditlog(1, $user_id,'当前抵押率改变',$lognote); 
                            }
                        }
                    }           
                }
                /**
                 * 预警值大于等于浮动值
                 * 取最新评估值,即评估值=成本价格*指数浮动比例+手工费
                 */
                if($alarm_status != 1)
                {
                    $data['assess_total'] = ['exp', "assess_total + ${new_value}"];
                    $user_credit = M('user_credit')->where(['user_id'=>$user_id])->update($data);
                    //3,累加评估总值,记录个人授信操作日志
                    $lognote = 
                    '新评估总值'.($assess_total+$new_value).
                    '原评估总值'.$assess_total.
                    '审核后累加'.$new_value;
                    $loanLogic->userCreditlog(1, $user_id,'评估审核通过,累加到评估总值',$lognote);  
                    if($user_credit)
                    {
                        /**
                         * 4,更新当前抵押率
                         * 最新当前抵押率>=审核抵押率时,发送报警消息
                         */
                        $credit = M('user_credit')->where(['user_id'=>$user_id])->find();
                        $new_assess_total = $credit['assess_total'];
                        $new_credit_used  = $credit['credit_used'];
                        $check_rate       = $credit['check_rate'];
                        //计算最新当前抵押率,并更新数据库
                        $update_now_rate['now_rate'] = ($new_credit_used / $new_assess_total)*100;
                        //计算最新剩余可使用额度,并更新到数据库
                        $credit_rest = ($new_assess_total*$check_rate)/100 - $new_credit_used;
                        if($credit_rest >= 0)
                        {
                            $update_now_rate['credit_rest'] = $credit_rest;
                        }
                        M('user_credit')->where(['user_id'=>$user_id])->update($update_now_rate);
                        $data = M('user_credit')->where(['user_id'=>$user_id])->find();
                        //查询最新当前计算抵押率
                        $check_rate = $data['check_rate'];
                        $now_rate   = $data['now_rate'];
                        
                        //如果最新当前抵押率大于等于审核抵押率,抵押率状态更新为2,并报警
                        
                        if($now_rate >= $check_rate)
                        {
                            $ratedata['rate_status'] = 2;
                            $rate_status = M('user_credit')->where(['user_id'=>$user_id])->update($ratedata);
                            if($rate_status){
                                //报警:您的当前抵押率已大于审批抵押率???
                                //发短信+推送
                                //1发短信
                                $pawnLogic->send_BJSMS(1, $pawn_id);
                                //2推送
                                
                                //5,当前抵押率异常,记录个人授信操作日志
                                $lognote = 
                                '当前抵押率'.($now_rate.'%').
                                '审批抵押率'.($check_rate.'%');
                                $loanLogic->userCreditlog(1, $user_id,'当前抵押率异常',$lognote);

                            }
                        }

                        //如果最新当前抵押率小于审核抵押率,抵押率状态更新为1
                        if($now_rate < $check_rate)
                        {
                            $ratedata['rate_status'] = 1;
                            $rate_status = M('user_credit')->where(['user_id'=>$user_id])->update($ratedata);

                            if($rate_status){
                                //5,当前抵押率变化,记录个人授信操作日志
                                $lognote = 
                                '当前抵押率'.($now_rate.'%').
                                '审批抵押率'.($check_rate.'%');
                                $loanLogic->userCreditlog(1, $user_id,'当前抵押率改变',$lognote); 
                            }

                        }

                    } 

                }  

            }
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
        }  
    }
    /**
     *  人工解押审核
     *  人工审核通过后进入人工解押列表
     *  更改最新抵押率
     */
    public function green_check(){
        $loanLogic = new LoanLogic();
        $pawnLogic = new PawnLogic(); 
        $pawn_id  = implode(',' , I('id/a'));
        $status = I('status');
        $data['status'] = I('status');
        $data['green_remarks'] = I('remark');
        $data['green_id'] = session('admin_id'); 
        if($status == 3) $data['green_time']   = time();
        if($status != 3) $data['grefuse_time'] = time();
        $res = M('pawn')->where(['pawn_id'=>$pawn_id])->update($data);
        if(!$res){
            $this->ajaxReturn(array('status'=>0,'msg'=>"操作失败"),'JSON');
        } 
        if($res)
        {
            
            //1,记录抵押品操作日志,并修改申请解押表的状态
            if($status == 3)
            {
                //申请解押表的状态改为4
                M('pawn_applygreen')->where('pawn_id',$pawn_id)->update(array('status'=>4));
                //记录抵押品操作日志
                $pawnLogic->pawnLog(1, $pawn_id ,'解押审核通过',I('remark'));
            }   
            if($status != 3)
            {
                //申请解押表的状态改为5
                M('pawn_applygreen') -> where('pawn_id',$pawn_id)->update(array('status'=>5));
                //记录抵押品操作日志
                $pawnLogic->pawnLog(1, $pawn_id ,'解押审核否决',I('remark'));
            } 
            //2,人工解押审核通过后,评估总值中减去已解押的评估值
            if($status == 3)
            {
                $condition = array();
                $condition['status']  = 3;
                $condition['pawn_id'] = $pawn_id;
                $pawn = M('pawn')->where($condition)->field('user_id,pawn_value,alarm_value,new_value,new_lrr,pawn_no,green_time')->order('pawn_id desc')->find();
                $user_id  = $pawn['user_id'];
                $new_value   = $pawn['new_value'];  //最新评估值
                //查询个人信用表
                $user_credit = M('user_credit')->where(['user_id'=>$user_id])->order('id desc')->find();
                $assess_total = $user_credit['assess_total'];
                //表达式,减去该评估值
                $data['assess_total'] = ['exp', "assess_total - ${new_value}"];
                $user_credit = M('user_credit')->where(['user_id'=>$user_id])->update($data); 
                //查询最新的个人授信表
                $credit = M('user_credit')->where(['user_id'=>$user_id])->find();
                $new_assess_total = $credit['assess_total'];
                $new_credit_used  = $credit['credit_used'];
                //判断:如果最新评估总值为0,则当前抵押率,剩余可使用额度置为0
                if($new_assess_total <= 0)
                {
                    $data['now_rate'] = $data['credit_rest'] = 0;
                    M('user_credit')->where(['user_id'=>$user_id])->update($data);
                    //记录日志
                     $lognote = 
                    '新评估总值'.($assess_total - $new_value).
                    '原评估总值'.$assess_total.
                    '审核后减去'.$new_value;
                    $loanLogic->userCreditlog(1, $user_id,'人工解押通过,减去评估总值',$lognote);
                    /**
                     * 发短信,推送,站内信
                     */
                    //1发短信
                    $pawnLogic->send_BJSMS(2, $pawn_id); 
                    $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
                }
                //3,抵押品出库,记录个人授信操作日志
                    $lognote = 
                    '新评估总值'.($assess_total - $new_value).
                    '原评估总值'.$assess_total.
                    '审核后减去'.$new_value;
                    $loanLogic->userCreditlog(1, $user_id,'人工解押通过,减去评估总值',$lognote); 
                
                /**
                 * 4发短信,推送,站内信
                 */
                //1发短信
                $pawnLogic->send_BJSMS(2, $pawn_id);
                //2推送
                if($user_credit)
                {
                    /**
                     * 5,计算最新:当前抵押率,计算最新:剩余可使用额度
                     * 
                     */
                    //查询个人授信表
                    $credit = M('user_credit')->where(['user_id'=>$user_id])->find();
                    $new_assess_total = $credit['assess_total'];
                    $new_credit_used  = $credit['credit_used'];
                    $check_rate       = $credit['check_rate'];
                    //得到最新当前抵押率,并更新到数据库
                    $update_now_rate['now_rate'] = ($new_credit_used / $new_assess_total)*100;
                    //得到最新剩余可使用额度,并更新到数据库
                    $credit_rest = ($new_assess_total*$check_rate)/100 - $new_credit_used;
                    if($credit_rest >= 0)
                    {
                        $update_now_rate['credit_rest'] = $credit_rest;
                    }
                    
                    M('user_credit')->where(['user_id'=>$user_id])->update($update_now_rate);
                    $user_credit = M('user_credit')->where(['user_id'=>$user_id])->find();
                    //查询:最新当前计算抵押率
                    $check_rate = $user_credit['check_rate'];
                    $now_rate   = $user_credit['now_rate'];
                    //最新当前抵押率大于等于审核抵押率,抵押率状态改为2并报警
                    if($now_rate >= $check_rate)
                    {
                        $rate['rate_status'] = 2;
                        $rate_status = M('user_credit')->where(['user_id'=>$user_id])->update($rate);
                        if($rate_status)
                        {
                            /**
                             *报警信息:您的当前抵押率已大于审批抵押率
                             */
                            /**
                             * 发短信,推送,站内信
                             */
                            //1发短信
                            $pawnLogic->send_BJSMS(1, $pawn_id);
                            //2推送
                            
                            //6,当前抵押率异常,记录个人授信操作日志
                            $lognote = 
                            '当前抵押率'.($now_rate.'%').
                            '审批抵押率'.($check_rate.'%');
                            $loanLogic->userCreditlog(1, $user_id,'当前抵押率异常',$lognote); 
                        }
                    } 
                    //最新当前抵押率小于审核抵押率,抵押率状态改为1
                    if($now_rate < $check_rate)
                    {
                        $rate['rate_status'] = 1;
                        $rate_status = M('user_credit')->where(['user_id'=>$user_id])->update($rate);
                        if($rate_status)
                        {
                            //6,当前抵押率改变,记录个人授信操作日志
                            $lognote = 
                            '当前抵押率'.($now_rate.'%').
                            '审批抵押率'.($check_rate.'%');
                            $loanLogic->userCreditlog(1, $user_id,'当前抵押率改变',$lognote); 
                        }
                    }                       
                }             
            }
            $this->ajaxReturn(array('status'=>1,'msg'=>"操作成功"),'JSON');
        } 
    }

   
    /**
     * 单件家具列表
     * 
     */
    public function onelist()
    {
        $pawn_id = I('pawn_id');
        $where['pawn_id']  = $pawn_id;
        $pawn = M('pawn')->where($where)->order('pawn_id desc ')->find();  
        $count = Db::name('pawn_one')->where($where)->count(); 
        $Page  = new Page($count,20);
        $list = M('pawn_one')->where($where)->order('one_id desc ')->limit($Page->firstRow.','.$Page->listRows)->select();
        $show  = $Page->show();
        $this->assign('pawn_id', $pawn_id);
        $this->assign('list',$list);
        $this->assign('status',$pawn['status']);
        $this->assign('role_id',session('admin_info')["role_id"]);
        $this->assign('pager',$Page);
        return $this->fetch();
    }
    /**
     * 单件家具详情
     */
    public function onedetail($one_id){
        //获取抵押品列表
        $list = M('pawn_one')->where(['one_id'=>$one_id])->order('one_id desc ')->find();  
        $pawn = M('pawn')->where(['pawn_id'=>$list['pawn_id']])->order('pawn_id desc ')->find();  
        //抵押品相册
        $pawnimgs = M("pawn_imgs")->where(array('one_id'=>$one_id))->select();
        
        $this->assign('one_id', $one_id);
        $this->assign('pawnimgs', $pawnimgs);
        $this->assign('list',$list);
        $this->assign('pawn',$pawn);
        $this->assign('status',$pawn['status']);
        $this->assign('role_id',session('admin_info')["role_id"]);
        return $this->fetch();
    }
    /**
     * 上传单件家具
     */
    public function add_one()
    {
        $data = I('param.');  
        $pawn_id = I('param.pawn_id');
        $orderLogic = new PawnLogic();
        if(IS_POST)
        {  
            $pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->find();
            $onecount = Db::name('pawn_one')->where(['pawn_id'=>$pawn_id])->count();
            //判断:已添加的单件家具个数与指定的单件家具个数相等了,则不能再添加
            if($onecount >= $pawn['pawn_num'])
            {
                $this->error('单件家具已添满!请核实!');exit;
            }

            $rfid = I('param.pawn_rfid');
            //判断:rfid不能重复
            $is_rfid = Db::name('pawn_one')->field('pawn_rfid')->select();
            if($is_rfid)
            {
                foreach ($is_rfid as $key => $val) {
                    $rfids[] = $val['pawn_rfid'];
                }
                if (in_array($rfid, $rfids))
                {
                    $this->error('RFID不能重复!',U("Admin/Pawn/pawnlist"));
                    exit;
                }
            }
            //获取user_store表中字段:巡店模式,并插入pawn_one表
            if($rfid)
            {
                $pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->find();
                $store_id = $pawn['store_id'];
                $user_store = M('user_store')->where(['store_id'=>$store_id])->find();
                if(!$user_store)
                {
                    $data['cruise_shop_mode'] = null;
                }
                $data['cruise_shop_mode'] = $user_store['cruise_shop_mode'];
            }
            // $data['one_no']  = $this->get_oneno($pawn_id,3);
            $data['addtime'] = time();  
            $data['updtime'] = time(); 
            //抵押品表add添加数据
            $one_add = M('pawn_one')->add($data);
            //插入照片(单件)
           $orderLogic->pawnimgs($one_add,2) ;
            //操作日志
            $orderLogic->pawnLog(1,$pawn_id,'上传单件家具', I('log_note'));    
            if($one_add)
            {
                $this->success('添加成功',U("Admin/Pawn/pawnlist"));
                exit();
            }
            else{
                $this->error('添加失败');
            }                
        }    
        //抵押品材料
        $woodlist = M('pawn_wood')->select(); 
        $this->assign('woodlist',$woodlist);
        //抵押品相册
        $pawnimgs = M("pawn_imgs")->where(array('pawn_id'=>I('GET.pawn_id', 0)))->order('img_id desc')->select();
        // $this->assign('pawnimgs', $pawnimgs);  
        $this->assign('pawn_id', $pawn_id);
        $this->assign('role_id',session('admin_info')["role_id"]);    
        return $this->fetch();
    }
    /**
     * 修改单件家具
     */
    public function edit_one(){  
        $data = I('param.'); 
        $one_id = I('param.one_id');
        $pawn_id= I('param.pawn_id');
        $orderLogic = new PawnLogic();
        $pawn_one = M('pawn_one')->where(['one_id'=>$one_id])->find();
        if(IS_POST)
        { 
            
            $rfid = I('param.pawn_rfid');
            //判断:rfid不能重复
            $is_rfid = Db::name('pawn_one')->field('pawn_rfid')->select();
            if($is_rfid)
            {
                foreach ($is_rfid as $key => $val) {
                    $rfids[] = $val['pawn_rfid'];
                }
                if (in_array($rfid, $rfids))
                {
                    $this->error('RFID不能重复!',U("Admin/Pawn/pawnlist"));
                    exit;
                }
            }
            //获取user_store表中字段:巡店模式,并插入pawn_one表
            if($rfid)
            {
                $pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->find();
                $store_id = $pawn['store_id'];
                $user_store = M('user_store')->where(['store_id'=>$store_id])->find();
                if(!$user_store)
                {
                    $data['cruise_shop_mode'] = null;
                }
                $data['cruise_shop_mode'] = $user_store['cruise_shop_mode'];
            }
            $data['updtime'] = time();
            $update = M('pawn_one')->where(['one_id'=>$one_id])->update($data); 
            $new_one = M('pawn_one')->where(['one_id'=>$one_id])->find();
            $new_pawnid =$new_one['pawn_id'];   
           //插入照片
           $orderLogic->pawnimgs($one_id,2) ;
           //操作日志
            $orderLogic->pawnLog(1,$new_pawnid,'修改单件家具', I('log_note'));    
            if($update)
            {
                $this->success('添加成功',U("Admin/Pawn/pawnlist"));
                exit();
            }
            else{
                $this->error('添加失败');
            }              
            
        }
        //抵押品材料
        $woodlist = M('pawn_wood')->select(); 
        $this->assign('woodlist',$woodlist);  
        //抵押品相册
        $pawnimgs = M("pawn_imgs")->where(array('one_id'=>I('GET.one_id', 0)))->order('img_id desc')->select();
        $this->assign('pawnimgs', $pawnimgs);  // 商品相册
        $this->assign('order',$pawn_one);
        $this->assign('role_id',session('admin_info')["role_id"]);
        return $this->fetch();
    } 
    /**
     * 单件家具编号生产规则
     * :从001开始以此增加
     */
    public function get_oneno($pawn_id,$num)
    {
        $one_count = M('pawn_one')->where(['pawn_id'=>$pawn_id])->count();
        if($one_count < 0 )
        {   
            $count = 0;
        }else{
            $count = $one_count;
        }
        $oneno = str_pad((intval($count)+1), $num, '0', STR_PAD_LEFT);
        return $oneno;
    }
    /**
     * 套件家具编号生产规则
     * (添加套件家具)
     */
    public function get_pawnno($store_id,$num)
    {
        $pawn_count = M('pawn')->where(['store_id'=>$store_id])->count();
        if($pawn_count < 0 )
        {   
            $count = 0;
        }else{
            $count = $pawn_count;
        }
        $oneno = str_pad((intval($count)+1), $num, '0', STR_PAD_LEFT);
        return $oneno;
    }
    /**
     * Excel导入家具展示
     */  
    public function importpawn()
    {     
        return $this->fetch();
    }
    /**
     * Excel导入家具专用:
     * 获取单件数据表要插入数据的二维数组
     */
    public  function get_importpawn($arr)
    {
       $data = array();
        foreach($arr as $k => $v)
        {
            $b = explode(',', $v);
            foreach($b as $val)
            {
                $data[] = array(
                    'pawn_id' => $k,
                    'pawn_name' => $val,
                    'addtime' =>time(),
                    'updtime' =>time(),
                );
            }
        }
        return $data;
    }
    /**
     * 
     * Excel导入家具动作[重要]
     * 1,excel格式:Excel2007
     * 2,字段,严格按照下载模板
     */
    public function import_pawn()
    {
        $order = I('post.'); 
        if(IS_POST)
        {    

            /**
             * 引入插件,excel导入
             */
            Vendor("PHPExcel.PHPExcel");
            Vendor("PHPExcel.PHPExcel.IOFactory");  
            Vendor("PHPExcel.PHPExcel.Reader.Excel5");  
            Vendor("PHPExcel.PHPExcel.Reader.Excel2007"); 
            //获取表单上传文件
            $file = request()->file('excel');
            $info = $file->validate(['ext' => 'xlsx'])->move(ROOT_PATH . 'public' . DS . 'pawn_excel');
            if ($info) {
                $exclePath = $info->getSaveName();  //获取文件名
                $file_name = ROOT_PATH . 'public' . DS . 'pawn_excel' . DS . $exclePath;   //上传文件的地址
                //dump($file_name);die;
                $objReader =\PHPExcel_IOFactory::createReader('Excel2007');
                $obj_PHPExcel =$objReader->load($file_name, $encode = 'utf-8');  //加载文件内容,编码utf-8
                $excel_array=$obj_PHPExcel->getsheet(0)->toArray();   //转换为数组格式
                array_shift($excel_array);  //删除第一个数组(标题);
                $data = [];
                foreach($excel_array as $k=>$v) { 
                    //excel表格要导入的字段
                    $data[$k]['user_no'] = $v[0];     //客户编号
                    $data[$k]['store_no'] = $v[1];    //门店编号
                    $data[$k]['user_name'] = $v[2];     //客户名称
                    $data[$k]['store_name'] = $v[3];    //门店名称
                    $data[$k]['pawn_name'] = $v[4];     //家具名称
                    $data[$k]['wood_name'] = $v[5];     //主材料
                    $data[$k]['pawn_area'] = $v[6];     //主产地
                    $data[$k]['wood_auxname'] = $v[7];     //辅材料
                    $data[$k]['pawn_auxarea'] = $v[8];     //辅产地
                    $data[$k]['pawn_model'] = $v[9];   //规格型号
                    $data[$k]['pawn_price'] = $v[10];  //定价
                    $data[$k]['pawn_num'] = $v[11];    //数量
                    $data[$k]['pawn_remarks'] = $v[12];  //备注
                    $data[$k]['one_name'] = $v[13];  //单件名称数组
                    //客户编号,门店编号,材料名称
                    $user_no = trim($data[$k]['user_no']);
                    $store_no = trim($data[$k]['store_no']);
                    $wood_name = trim($data[$k]['wood_name']);
                    $wood_auxname = trim($data[$k]['wood_auxname']);
                    $one_name = trim($data[$k]['one_name']);
                    //判断1:客户信息是否存在.
                    $users = M('users')->where(['client_no'=>$user_no])->order('user_id desc')->find();
                    if(!$users)
                    {
                        $this->error('客户编号不存在,请核实!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }
                    //判断2:审批结论复核通过后,才能excel导入.
                    if($users['is_approval'] != 2)
                    {
                        $this->error('此客户审批结论复核未通过,请核实!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }
                    //判断3: 客户经理只能导入属于自己客户
                    if($users['xd_id'] !=  $_SESSION['admin_id'])
                    {
                        $this->error('客户经理只能导入属于自己客户,请核实!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }    
                    //通过客户编号找到客户ID,并存入user_id
                    $data[$k]['user_id']= $users['user_id'];
                    //判断4:门店信息是否存在
                    $stores = M('user_store')->where(['store_no'=>$store_no])->order('store_id desc')->find();
                    if(!$stores)
                    {
                        $this->error('门店编号不存在,请核实门店信息!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }
                    //判断5:客户编号和门店编号是同一客户名下
                    if($stores['user_no'] != $user_no || $stores['store_no'] != $store_no)
                    {
                        $this->error('门店编号与所属的客户编号不一致!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }
                    //判断6:单件家具数组为空,即没有单件家具名称时,不能导入
                    if(!$one_name)
                    {
                         $this->error('单件家具为空,不能导入!请补全单件家具名称!',U("Admin/Pawn/pawnlist"));
                        exit;
                    }
                    //通过门店编号找到门店ID,并插入store_id
                    $data[$k]['store_id']= $stores['store_id'];
                    //通过主材料名称匹配主材料ID,并插入wood_id
                    $woods = M('pawn_wood')->where(" name like '%$wood_name%' ")->select(); 
                    //判断7:主材料必须是在材料表中存在的,并且名称保持一致
                    if(!$woods)
                    {
                         $this->error('材料表不存在该主材料!请核实主材料名称!',U("Admin/Pawn/pawnlist"));exit;
                    }
                    foreach ($woods as  $val) {
                       $data[$k]['wood_id']= $val['id'];
                    }
                    //通过辅材料名称匹配辅材料ID,并插入wood_auxid
                    $auxwoods = M('pawn_wood')->where(" name like '%$wood_auxname%' ")->select(); 
                    //判断8:辅材料必须是在材料表中存在的,并且名称保持一致
                    if(!$auxwoods)
                    {
                         $this->error('材料表不存在该辅材料!请核实辅材料名称!',U("Admin/Pawn/pawnlist"));exit;
                    }
                    foreach ($auxwoods as  $val) {
                       $data[$k]['wood_auxid']= $val['id'];
                    }
                    //客户姓名取查询users表的客户姓名
                    //门店名称取查询user_store表的门店名称
                    $data[$k]['user_name'] = $users['name'];
                    $data[$k]['store_name'] = $stores['store_name'];
                    $data[$k]['addtime'] = time();  
                    $data[$k]['updtime'] = time(); 
                    $data[$k]['addtype'] = 1;  //添加类型:1excel自动导入,2手动上传
                    $data[$k]['xd_id'] = session('admin_id');//上传人ID 
                    $data[$k]['pawn_no']=date('YmdHis').mt_rand(1000,9999);//家具编号
                    //批量插入pawn并返回主键ID数组
                    $newid[] = Db::name('pawn')->add($data[$k]);
                    $getLastID= DB::getLastInsID();

                } 
                //根据批量插入pawn并返回主键ID数组,得到所有的单件家具名称
                $condition['pawn_id'] = ['in',implode(',',$newid)];
                $pawn = M('pawn')->where($condition)->getField('pawn_id,one_name');
                //切割单件家具名称数组,得到单个家具的名称二维数组
                $data = $this->get_importpawn($pawn);
                //插入单件家具表
                Db::name('pawn_one')->insertAll($data);        
                //操作日志
                $orderLogic = new PawnLogic();
                $orderLogic->pawnLog(1,$getLastID,'Excel导入抵押品',I('log_note'));
                $this->success('导入成功',U("Admin/Pawn/pawnlist"));
            } else {
               $this->error('导入失败',U("Admin/Pawn/pawnlist"));
            }
        }
    }  
    /**
     * 实时监控当前抵押率异常
     * 发现异常时,给此类用户和信贷经理发送报警信息(短信+推送)
     * 难点:批量报警
     */
    public function a()
    {
        //查询个人信用表
        // $user_credit = M('user_credit')->field('user_id')->where(['rate_status'=>2])->order('id desc')->select();
        // if(!$user_credit) exit;
        // foreach ($user_credit as $v) 
        // {
        //     $user_id[] = $v['user_id'];
        // }
        // $user_id = array_unique($user_id);
        // //查询用户信息表
        // $condition['user_id'] = ['in',implode(',',$user_id)];
        // $users = M('users')->field('xd_id')->where($condition)->order('user_id desc')->select();
        // foreach ($users as $v) 
        // {
        //     $xd_id[] = $v['xd_id'];
        // }
        // $xd_id = array_unique($xd_id);
        // //查询管理员信息
        // $where['admin_id'] = ['in',implode(',',$xd_id)];
        // $admin = M('admin')->field('user_name,mobile')->where($where)->order('admin_id desc')->select();
        // foreach ($admin as $v) 
        // {
        //     $xdmobile[] = $v['mobile'];
        // }
        // $xdmobile = array_unique($xdmobile);
        // $xdmobile = implode(',',$xdmobile);
        // dump($xdmobile);die;
        
        //当前抵押率异常
        // $sender = [13439918307];
        // $sender = [13439918307,18500913545];
        // foreach($sender as $value){
        // } 
        // $sender = implode(',',$sender);
        // dump($sender);die;
        // $sender = $sender;
        // $sender = 13439918307;
        // $params['name'] = '信贷A';
        // $params['username'] = '山茶树';
        // $params['usermobile'] = 18820182018;
        // $params['now_rate'] = '15%';
        // $params['check_rate'] = '18%';
        // $a = send_BJSMS("1", $sender, $params);
        // var_dump($a);
        // 
        $this -> rootPath = $_SERVER['DOCUMENT_ROOT']; 
        
        $url = 'public/upload/pawn_imgs/2018/03-09/dfc0c3ef4f1f5f1ec7b28dbf567b128b.jpg';
        // $Qiniuimg = new QiniuQs();
        $this->qiniuupload($this->rootPath.$url);
    } 
    
    
   
}   
