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
 * Date: 2015-09-14
 */


namespace app\admin\logic;
use app\admin\model\Loan;
use think\Model;
use think\Db;
use app\admin\controller\QiniuQs;
class PawnLogic extends Model
{
    
    
    /*
     * 家具操作日志
     * logtype:操作人类型:1后台人员ID,2用户ID
     * pawn_id:家具ID
     * action:动作(方法)
     * note:操作备注
     */
    public function pawnLog($logtype,$pawn_id,$action,$note=''){
          $furniture = M('pawn')->where(array('pawn_id'=>$pawn_id))->order('pawn_id desc')->find();
          $data['pawn_id'] = $pawn_id;             //家具ID
          $data['act_desc'] = $action;             //动作描述
          $data['log_note'] = $note;               //操作备注
          $data['log_time'] = time();
          if($logtype ==1){
            $data['log_type'] = 1;
            $data['log_user'] = session('admin_id'); 
          }
          if($logtype == 2){
            $data['log_type'] = 2;
            $data['log_user'] = $furniture['user_no']; 
          }
          $log = M('pawn_log');
          return $log->add($data);
    }

    /**
     * 当前抵押率插入数据
     */
    public  function now_rate($user_id)
    {
          $user_credit = M('user_credit')->where(array('user_id'=>$user_id))->order('id desc')->find();
          if(!$user_credit) $this->error('无此个人信用表');
          $credit_used = $user_credit['credit_used'];//已使用额度
          $assess_total = $user_credit['assess_total'];//评估总值
          $data['now_rate'] = round($credit_used/$assess_total*100 ,2).'%'; 

          $order_id = M('user_credit')->where(['user_id'=>$user_id])->update($data);
          return  $order_id;
    }
     
    /**
     * 家具相册(单件版)
     */
    public  function pawnimgs222($pawn_id)
    {
        
         // 商品图片相册  图册
         $pawn_imgs = I('pawn_imgs/a');
         if(count($pawn_imgs) > 1)
         {                          
             array_pop($pawn_imgs); // 弹出最后一个             
             $goodsImagesArr = M('pawn_imgs')->where(['pawn_id' => $pawn_id])->getField('img_id,img_url'); // 查出所有已经存在的图片
             
             // 删除图片
             foreach($goodsImagesArr as $key => $val)
             {
                 if(!in_array($val, $pawn_imgs))
                     M('pawn_imgs')->where("img_id = {$key}")->delete(); // 
             }
             // 添加图片
             foreach($pawn_imgs as $key => $val)
             {
                 if($val == null)  continue;                                  
                 if(!in_array($val, $goodsImagesArr))
                 {                 
                        $data = array(
                            'pawn_id' => $pawn_id,
                            'img_url' => $val,
                        );
                      M("pawn_imgs")->insert($data);                    
                 }
             }
         }
    }
    /**
     * 家具相册(套件+单件+本地上传)
     * 参数说明
     * pawn_id:套件家具ID
     * pawn_type:家具类型:1套件家具,2单件家具
     */
    public  function pawnimgs11111111($pawn_id,$pawn_type)
    {
        
         // 商品图片相册  图册
         $pawn_imgs = I('pawn_imgs/a');
         if(count($pawn_imgs) > 1)
         {                          
             
             if($pawn_type == 1)
             {            
                 array_pop($pawn_imgs); // 弹出最后一个 
                 $goodsImagesArr = M('pawn_imgs')->where(['pawn_id' => $pawn_id,'pawn_type'=>1])->getField('img_id,img_url'); // 查出所有已经存在的图片
                 
                 // 删除图片
                 foreach($goodsImagesArr as $key => $val)
                 {
                     if(!in_array($val, $pawn_imgs))
                         M('pawn_imgs')->where("img_id = {$key}")->delete(); // 
                 }
                 // 添加图片
                 foreach($pawn_imgs as $key => $val)
                 {
                     if($val == null)  continue;                                  
                     if(!in_array($val, $goodsImagesArr))
                     {                 
                            $data = array(
                                'pawn_id' => $pawn_id,
                                'img_url' => $val,
                                'pawn_type'=>$pawn_type
                            );
                          M("pawn_imgs")->insert($data);                    
                     }
                 }
             }
             if($pawn_type == 2)
             {           
                array_pop($pawn_imgs); // 弹出最后一个 
                //此时$pawn_id为单件家具ID
                $pawn_one = M('pawn_one')->where(['one_id'=>$pawn_id])->order('one_id desc ')->find();
                $pawnid = $pawn_one['pawn_id'];
                 $goodsImagesArr = M('pawn_imgs')->where(['one_id' => $pawn_id])->getField('img_id,img_url'); // 查出所有已经存在的图片
                 // 删除图片
                 foreach($goodsImagesArr as $key => $val)
                 {
                     if(!in_array($val, $pawn_imgs))
                         M('pawn_imgs')->where("img_id = {$key}")->delete(); // 
                 }
                 // 添加图片
                 foreach($pawn_imgs as $key => $val)
                 {
                     if($val == null)  continue;                                  
                     if(!in_array($val, $goodsImagesArr))
                     {                 
                            $data = array(
                                'pawn_id' => $pawnid,
                                'img_url' => $val,
                                'pawn_type'=>$pawn_type,
                                'one_id' =>$pawn_id
                            );
                          M("pawn_imgs")->insert($data);                    
                     }
                 }
             }
             
         }
    }
    /**
     * 家具相册(套件+单件+七牛云存储)
     * 参数说明
     * pawn_id:套件家具ID
     * pawn_type:家具类型:1套件家具,2单件家具
     */
    public  function pawnimgs($pawn_id,$pawn_type)
    {
        $this -> rootPath = $_SERVER['DOCUMENT_ROOT'];
         // 商品图片相册  图册
         $pawn_imgs = I('pawn_imgs/a');
         if(count($pawn_imgs) > 1)
         {                          
             
             if($pawn_type == 1)
             {            
                 array_pop($pawn_imgs); // 弹出最后一个 
                 $goodsImagesArr = M('pawn_imgs')->where(['pawn_id' => $pawn_id,'pawn_type'=>1])->getField('img_id,img_url'); // 查出所有已经存在的图片
                 
                 // 删除图片
                 foreach($goodsImagesArr as $key => $val)
                 {
                     if(!in_array($val, $pawn_imgs))
                         M('pawn_imgs')->where("img_id = {$key}")->delete(); 
                         qiniuimgdel($val);
                 }
                 // 添加图片
                 foreach($pawn_imgs as $key => $val)
                 {
                     if($val == null)  continue;                                  
                     if(!in_array($val, $goodsImagesArr))
                     {                 
                        //套件家具图片上传到七牛云
                        $url = qiniuupload($this->rootPath.$val);  
                        // $url = $Qiniuimg->qiniuupload($this->rootPath.$val); 
                        $data = array
                        (
                            'pawn_id' => $pawn_id,
                            'img_url' => $url,
                            'pawn_type'=>$pawn_type
                        );
                        M("pawn_imgs")->insert($data);  
                        //同时删除本地图片
                        @unlink($this->rootPath.$val);                  
                     }
                 }
             }
             if($pawn_type == 2)
             {           
                array_pop($pawn_imgs); // 弹出最后一个 
                //此时$pawn_id为单件家具ID
                $pawn_one = M('pawn_one')->where(['one_id'=>$pawn_id])->order('one_id desc ')->find();
                $pawnid = $pawn_one['pawn_id'];
                 $goodsImagesArr = M('pawn_imgs')->where(['one_id' => $pawn_id])->getField('img_id,img_url'); // 查出所有已经存在的图片
                 // 删除图片
                 foreach($goodsImagesArr as $key => $val)
                 {
                     if(!in_array($val, $pawn_imgs))
                        M('pawn_imgs')->where("img_id = {$key}")->delete(); 
                        qiniuimgdel($val);
                 }
                 // 添加图片
                 foreach($pawn_imgs as $key => $val)
                 {
                     if($val == null)  continue;                                  
                     if(!in_array($val, $goodsImagesArr))
                     {                 
                        //单件家具图片上传到七牛云
                        $url = qiniuupload($this->rootPath.$val);
                        // $url = $Qiniuimg->qiniuupload($this->rootPath.$val);   
                        $data = array(
                            'pawn_id' => $pawnid,
                            'img_url' => $url,
                            'pawn_type'=>$pawn_type,
                            'one_id' =>$pawn_id
                        );
                        M("pawn_imgs")->insert($data);
                        //同时删除本地图片
                        @unlink($this->rootPath.$val);                     
                     }
                 }
             }
             
         }
    }
    /**
     * 批量计算评估总值(暂不用,保留)
     * 使用条件:评估师录入的评估值无变化(不爬取数据时);
     *  风控审核通过,开始累加评估总值
     *  累加初始评估值
     */
    public function total($pawn_id)
    {
        $condition = array();
        $condition['status'] = 1;
        $condition['pawn_id'] = ['in',implode(',',$pawn_id)];
        $pawn = M('pawn')->where($condition)
                         ->field('user_id,sum(pawn_value) as pawn_value')
                         ->group('user_id')
                         ->order('pawn_id desc')
                         ->select();
        foreach ($pawn as $key => $val) {
            $user_credit = M('user_credit')->order('id desc')->select();
            foreach ($user_credit as $key => $credit) {
                if($credit['user_id'] == $val['user_id']) 
                {
                   //评估值合计
                   $pawn_value = $val['pawn_value'];
                   $data['assess_total'] = ['exp', "assess_total + ${pawn_value}"];
                  $user_credit = M('user_credit')->where(['user_id'=>$val['user_id']])->update($data); 
                  if(!$user_credit)
                  {
                    return false;
                  }
                  //记录个人信息操作日志
                  $orderLogic = new LoanLogic();
                  $lognote = 
                  '新评估总值'.($credit['assess_total']+$pawn_value).
                  '原评估总值'.$credit['assess_total'].
                  '审核后累加'.$pawn_value;
                  $orderLogic->userCreditlog(1, $val['user_id'] ,'计算评估总值',$lognote);  
                   
                }     
            }                       
        } 
        return $user_credit; 
    }
     /**
     * 初始化红木价格指数表(暂不用)
     */
    public function start_priceindex($pawn_id)
    {
        //查询红木价格指数表
        $pawn_index = M('pawn_index')->where(['pawn_id'=>$pawn_id])->find();
        //查询抵押品列表
        $pawn = M('pawn')->field('user_id,store_id,pawn_id,wood_id,pawn_value,pawn_cost')->where(['pawn_id'=>$pawn_id])->order('pawn_id desc')->find();
        if($pawn == null)  continue; 
        $user_id    = $pawn['user_id'];
        $store_id   = $pawn['store_id'];
        $wood_id    = $pawn['wood_id'];
        $pawn_value = $pawn['pawn_value'];
        $pawn_cost  = $pawn['pawn_cost'];
        $first_costprice = $pawn_value - $pawn_cost;
        $woodlist = M('pawn_wood')->field('id,name,alarm_value')->where(['id'=>$wood_id])->find();
        $wood_name   = $woodlist['name'];
        $alarm_value = $woodlist['alarm_value'];
        //如果红木价格指数表不存在则添加
        if(!$pawn_index){
          $index['user_id'] = $user_id;//用户ID
          $index['store_id'] = $store_id;//门店ID
          $index['pawn_id'] = $pawn_id;//家具ID
          $index['wood_id'] = $wood_id;//木材ID
          $index['wood_name'] = $wood_name;//木材名称
          $index['first_value'] = $pawn_value;//初始评估值
          $index['first_costprice'] = $first_costprice;//初始成本价
          $index['first_laborcost'] = $pawn_cost;//初始人工费
          $index['first_alarmvalue'] = $alarm_value;//初始预警值
          $index['first_time'] = time();//初始化时间
          $index['new_value'] = $pawn_value;//最新评估值
          $index['new_costprice'] = $first_costprice;//最新成本价
          $index['new_lrr'] = 0;//环比涨跌幅
          $index['new_time'] = time();//最新抓取时间
          $index['first_index'] = 6000;//初始价格指数
          $index['new_index'] = $index['first_index'];//最新价格指数
          return M('pawn_index')->add($index);
        //如果红木价格指数表存在则更新
        }else{
          $index['user_id']  = $user_id;//用户ID
          $index['store_id'] = $store_id;//门店ID
          $index['pawn_id'] = $pawn_id;//家具ID
          $index['wood_id'] = $wood_id;//木材ID
          $index['wood_name'] = $wood_name;//木材名称
          $index['first_value'] = $pawn_value;//初始评估值
          $index['first_costprice'] = $first_costprice;//初始成本价
          $index['first_laborcost'] = $pawn_cost;//初始人工费
          $index['first_alarmvalue'] = $alarm_value;//初始预警值
          $index['first_time'] = time();//初始化时间
          $index['new_value'] = $pawn_value;//最新评估值
          $index['new_costprice'] = $first_costprice;//最新成本价
          $index['new_lrr'] = 0;//环比涨跌幅
          $index['new_time'] = time();//最新抓取时间
          $index['first_index'] = 1000;//初始价格指数
          $index['new_index'] = $index['first_index'];//最新价格指数
          $pawn_index = M('pawn_index')->where(['pawn_id'=>$pawn_id])->update($index); 
          return  $pawn_index;
        }        
    }
    /**
     * 评估审核通过,累加评估总值
     * 使用条件:评估师录入的评估值动态变化时
     * 风控审核通过,开始累加评估总值
     */
    public function pg_total($pawn_id)
    {
        $condition = array();
        $condition['status']  = 1;
        $condition['pawn_id'] = $pawn_id;
        //获取价格指数表
        $pawn = M('pawn')
                         ->where($condition)
                         ->field('user_id,pawn_value,alarm_value,new_value,new_lrr')
                         ->order('pawn_id desc')
                         ->find();
        $user_id     = $pawn['user_id'];     
        $pawn_value  = $pawn['pawn_value']; //初始评估值 
        $alarm_value = $pawn['alarm_value'];//初始预警值 
        $new_value   = $pawn['new_value'];  //最新评估值    
        $new_lrr     = $pawn['new_lrr'];   //最新环比涨跌幅
        //获取个人信用表
        $user_credit = M('user_credit')->where(['user_id'=>$user_id])->order('id desc')->find();
        $assess_total = $user_credit['assess_total'];
        //预警值小于浮动值,取评初始评估值
        if($alarm_value < $new_lrr)
        {
            $data['assess_total'] = ['exp', "assess_total + ${pawn_value}"];
            $user_credit = M('user_credit')->where(['user_id'=>$user_id])->update($data); 
            if($user_credit)
            {
               //记录个人信息操作日志
                $orderLogic = new LoanLogic();
                $lognote = 
                '新评估总值'.($assess_total+$pawn_value).
                '原评估总值'.$assess_total.
                '审核后累加'.$pawn_value;
                $orderLogic->userCreditlog(1, $user_id,'评估审核通过,累加评估总值',$lognote); 
            }
             
        }
        /**
         * 预警值大于等于浮动值
         * 取最新评估值,即评估值=成本价格*指数浮动比例+手工费
         */
        if($alarm_value >= $new_lrr)
        {
            $data['assess_total'] = ['exp', "assess_total + ${new_value}"];
            $user_credit = M('user_credit')->where(['user_id'=>$user_id])->update($data); 
            if(!$user_credit)
            {
              return false;
            }
            //记录个人信息操作日志
            $orderLogic = new LoanLogic();
            $lognote = 
            '新评估总值'.($assess_total+$new_value).
            '原评估总值'.$assess_total.
            '审核后累加'.$new_value;
            $orderLogic->userCreditlog(1, $user_id,'评估审核通过,累加到评估总值',$lognote);  
        }
        return $user_credit; 
    }
   
    /**
     * 初始化最新评估值
     */
    public function pawn_newvalue($pawn_id)
    {
        //查询抵押品列表
        $pawn = M('pawn')->field('wood_id,pawn_value')->where(['pawn_id'=>$pawn_id])->order('pawn_id desc')->find();
        $wood_id    = $pawn['wood_id'];
        $pawn_value = $pawn['pawn_value'];
        //查询材料列表
        $woodlist = M('pawn_wood')->field('alarm_value')->where(['id'=>$wood_id])->find();
        $alarm_value = $woodlist['alarm_value'];  
        //初始化最新评估值等信息
        $index['new_value'] = $pawn_value;//最新评估值
        $index['new_lrr'] = 0;//最新环比涨跌幅
        $index['new_time'] = time();//最新发布时间
        $index['alarm_value'] = $alarm_value;//初始预警值
        $pawn_update = M('pawn')->where(['pawn_id'=>$pawn_id])->update($index); 
        return  $pawn_update;
    } 
    /**
     * 短信报警
     * type短信类型
     * user_id客户ID
     */
   public function send_BJSMS($type,$pawn_id)
   {
      
      //查询抵押品信息
      $pawn = M('pawn')->where(['pawn_id'=>$pawn_id])->field('user_id,pawn_no,green_time')->order('pawn_id desc')->find();
      $user_id = $pawn['user_id'];
      $pawn_no = $pawn['pawn_no'];
      $green_time = date("YmdHis", $pawn['green_time']);
      //查询用户信息
      $users = M('users')->field('name,mobile,xd_id,fk_id')->where(['user_id'=>$user_id])->find();
      $username = $users['name'];
      $usermobile = $users['usermobile'];
      $xd_id    = $users['xd_id'];
      $fk_id    = $users['fk_id'];
      //查询个人授信信息
      $credit = M('user_credit')->field('now_rate,check_rate')->where(['user_id'=>$user_id])->find();
      $now_rate = $credit['now_rate'];
      $check_rate = $credit['check_rate'];
      //查询管理员信息
      $admin = M('admin')->field('user_name,mobile')->where(['admin_id'=>$xd_id])->find();
      $xdname   = $admin['user_name'];
      $xdmobile = $admin['mobile'];
      
      //拼接短信参数
      $sender = $xdmobile;//接受短信的手机号码
      // $sender = 13439918307;
      $params['name']       = $xdname; //信贷姓名
      $params['username']   = $username;//客户姓名
      $params['usermobile'] = $usermobile;//客户电话
      $params['now_rate']   = $now_rate.'%';//当前抵押率
      $params['check_rate'] = $check_rate.'%';//审批抵押率
      if($type == 2)
      {
          $params['pawn_no']    = $pawn_no;//抵押品编号
          $params['green_time'] = $green_time;//人工解压时间
      }
      $result = send_BJSMS($type, $sender, $params);
      return $result;
   }
}
