<?php
/**
 * 定时任务执行记录
 * Author: dl
 * Date: 2018-01-25
 */
namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Loader; 
class Timedtask extends Base{
    /**
     * RFID 信号接受
    */
    public function check(){ echo 2;die;
        $admin_id = session('admin_id');
        $role = M('admin')->where(array('admin_id'=>$admin_id))->getField('role_id');
        if(!in_array($role, [1,2])){
            $filter['admin_id'] = $admin_id;
        }

        //故障类型
        $type2 = I('type2', '', 'trim');
        if($type2){
            $filter['type2'] = $type2;
            $search['type2'] = $type2;
        } 
        //用户名字搜索
        $user_name = I('user_name', '', 'trim');
        if($user_name){
            $filter['user_name'] = array('like', "%$user_name%");
            $search['user_name'] = $user_name;
        } 
        //门店名称搜索
        $store_name = I('store_name', '', 'trim');
        if($store_name){
            $filter['store_name'] = array('like', "%$store_name%");
            $search['store_name'] = $store_name;
        } 

        //rfid编号
        $em_name = I('em_name', '', 'trim');
        if($em_name){
            $filter['em_name'] = array('like', "%$em_name%");
            $search['em_name'] = $em_name;
        } 

        $filter['type'] = 2;
        $filter['status'] = 1;
        $model = 'equipment_wrong';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 20);
        $list = M($model) ->where($filter) -> order('id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();
        foreach ($list as $key => $value) {
               $list[$key]['pawn_detail'] = '/index.php/Admin/Pawn/detail/pawn_id/'.$value['em_id'];
               $list[$key]['store_detail'] = '/index.php/Admin/Users/edit_store/store_id/'.$value['store_id'];
           }    
        $this -> assign('list', $list);
        $show  = $Page->show();
        $this -> assign('show',$show);
        $this -> assign('pager',$Page);
        $this -> assign('search',$search);
        $this -> assign('role',$role);
        return $this -> fetch();
    } 
    /**
     * RFID 监测信号
    */
    public function rfid(){ 
        if(2 !== session('admin_id')){
            exit('无权限,少年请回吧！');
        }

        //故障类型 
        $type = I('type', '', 'trim');
        if($type){
            $filter['type'] = $type;
            $search['type'] = $type;
        } 
        //0所有数据无故障 否则为故障数量
        $result = I('result', '', 'trim');
        if($result){
            $filter['result'] = $result;
            $search['result'] = $result;
        }
        //短信通知失败数量
        $dx_err_count = I('dx_err_count', '', 'trim');
        if($dx_err_count){
            $filter['dx_err_count'] = $dx_err_count;
            $search['dx_err_count'] = $dx_err_count;
        }
        //个推通知失败数量
        $gt_err_count = I('gt_err_count', '', 'trim');
        if($gt_err_count){
            $filter['gt_err_count'] = $gt_err_count;
            $search['gt_err_count'] = $gt_err_count;
        }
        //value搜索
        $value = I('value', '', 'trim');
        if($value){
            $filter['value'] = array('like', "%$value%");
            $search['value'] = $value;
        }

        $model = 'rfid_timedtask_log';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 20);
        $list = M($model) ->where($filter) -> order('id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();
        

        $this -> assign('list', $list);
        $show  = $Page->show();
        $this -> assign('show',$show);
        $this -> assign('pager',$Page);
        $this -> assign('search',$search);
        return $this -> fetch();
    } 
}