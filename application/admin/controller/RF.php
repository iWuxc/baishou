<?php
/**
 * RFID
 * Author: dl
 * Date: 2018-01-10
 */
namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Loader; 
class RF extends Base{
    private $model = 'store_monitoring'; 
    /**
     * RFID 故障 列表 
    */
    public function bad_list(){
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
        $Page = new Page($count, 10);
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
     * RFID 修复 列表 
    */
    public function recovery_list(){
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
        $filter['status'] = 2;
        $model = 'equipment_wrong';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 10);
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
}