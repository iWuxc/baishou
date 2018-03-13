<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * 采用TP5助手函数可实现单字母函数M D U等,也可db::name方式,可双向兼容
 * ============================================================================
 * Author: 当燃      
 * Date: 2015-09-09
 */

namespace app\admin\controller;

use think\Page;
use think\Verify;
use think\Db;
use think\Session;

class StoreCheck extends Base {
    private $model = 'check_user'; 
    //巡店员列表
    public function index(){
        $where = array();
        $where['status'] = 1; 
        $count = M('check_user') -> where($where) -> count();
        $checks = M('check_user') -> where($where) -> select();
        $this -> assign('count',$count);
        $this -> assign('checks',$checks);
        return $this -> fetch();
    }
    public function add(){
        if(!IS_POST){
            return $this -> fetch();
        }else{
            $data = I('post.', array()); 
            if (!$_POST['check_name']) {
                $this->error('巡店员名不能为空');
            }
            if (!$_POST['id_number']) {
                $this->error('身份证号不能为空');
            }
            if (!$_POST['mobile']) {
                $this->error('手机号不能为空');
            }
            if (!$_POST['password']) {
                $this->error('密码不能为空');
            }
            $data['add_time'] = time();
            $data['status'] = 1;
            $data['password'] = encrypt($data['password']);
            $result = M('check_user')->add($data);
            if(0 !== $result || false !== $result){
                $this->success('添加成功', U('StoreCheck/index'));
            }else{
                $this->error('添加失败');
            }
        }
    }
    //巡店记录
    public function log(){
        // $url = 'http://p576csey0.bkt.clouddn.com/8b5ec201803091519522861.jpg';
        // $res = qiniuimgdel($url);
        // var_dump($res);die;
        // $res = qiniuupload('D:\host\1.png');
        // var_dump($res);die;
        $id = I('get.id',0); //序号
        $create_time = I('create_time'); //查找的时间段
        $create_time = str_replace('+', ' ',$create_time);
        $create_time2 = $create_time ? $create_time : date('Y-m-d', strtotime('-1 year')).' - ' . date('Y-m-d', strtotime('+1, day'));
        $create_time3 = explode(' - ', $create_time2);;
        $this -> assign('start_time', $create_time3[0]);
        $this -> assign('end_time', $create_time3[1]);
        $where['time'] =  array(array('gt', strtotime(strtotime($create_time3[0])), array('lt', strtotime($create_time3[1]))));
        if ($id) {
            $where['id'] = $id;
            $count = M('check_log') -> where($where) -> count();
            $data = M('check_log') -> where($where) -> select();  
        }else{
            $count = M('check_log') ->  count();
            $data = M('check_log') ->  select();  
        }
        $this ->assign('data',$data);
        $this ->assign('count',$count);
        return $this -> fetch();
    }
    //故障列表
    public function bad_list(){
        $admin_id = session('admin_id');
        $role = M('admin')->where(array('admin_id'=>$admin_id))->getField('role_id');
        if(!in_array($role, [1,2])){
            $filter['admin_id'] = $admin_id;
        }

        $store_id = I('store_id',0); //门店ID 
        $filter['store_id'] = $store_id;
        $filter['type'] = 2;
        $filter['status'] = 1;
        $model = 'equipment_wrong';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 10);
        $list = M($model) ->where($filter) -> order('id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();
        // print_r($list);die;
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
    //临时任务
    public function task(){
        $where = array();
        $where['status'] = 1; 
        // $where['over_time'] = array('>',time());
        $count = M('check_add_task') -> where($where) -> count();
        $data = M('check_add_task') -> where($where) -> select();
        $this ->assign('data',$data);
        $this ->assign('count',$count);
        return $this -> fetch();
    }
    public function del(){ 
        // if(!in_array($admin_info['role_id'], [1, 4])){
        //     $data['success'] = 2;
        //     $data['msg'] = '无删除权限！';
        //     $this->ajaxReturn($data); 
        // } 
        $id = I('id', 0);
        $save = array();
        $save['status'] = 2;
        $where = array();
        $where['check_id'] = $id;
        $result = M('check_user') -> where($where) -> save($save);
        if(!$result){
            $data['success'] = 2;
            $data['msg'] = '操作失败，请稍后再试！'.M($model)->getLastSql();
            $this->ajaxReturn($data);
        }
        $data['success'] = 1;
        $data['msg'] = '已删除！';
        $this->ajaxReturn($data);
    } 
    public function task_del(){
        // if(!in_array($admin_info['role_id'], [1, 4])){
        //     $data['success'] = 2;
        //     $data['msg'] = '无删除权限！';
        //     $this->ajaxReturn($data); 
        // } 
        $id = I('id', 0);
        $save = array();
        $save['status'] = 2;
        $where = array();
        $where['id'] = $id;
        $data = M('check_add_task') ->where($where) -> field('store_ids,old_status')->find();
        $data['store_ids'] = explode(',',$data['store_ids']);
        $data['old_status'] = explode(',',$data['old_status']);
        $where2 = array();
        $data2 = array();
        for ($i=0; $i < count($data['store_ids']) ; $i++) { 
            $where2['store_id'] = $data['store_ids'][$i];
            $data2['task_status'] = $data['old_status'][$i];
            M('user_store') ->where($where2) -> save($data2);
        }
        $result = M('check_add_task') -> where($where) -> save($save);
        if(!$result){
            $data['success'] = 2;
            $data['msg'] = '操作失败，请稍后再试！'.M($model)->getLastSql();
            $this->ajaxReturn($data);
        }
        $data['success'] = 1;
        $data['msg'] = '已删除！';
        $this->ajaxReturn($data);
    }
    public function task_add(){
        if(!IS_POST){
            $where = array();
            $where['is_check'] = 1;
            $check_name = M('check_user')->select();
            $storeName = M('user_store')->where($where) -> select();
            $this -> assign('check_name',$check_name);
            $this -> assign('storeName',$storeName);
            return $this -> fetch();
        }else{
            $data = I('post.', array()); 
            if (!$_POST['check_user_id']) {
                $this->error('必须选择一个巡店员');
            }
            if (!$_POST['store_ids']) {
                $this->error('必须选择一个门店');
            }
            $check_id = $_POST['check_user_id'];
            $check_name = M('check_user') ->where('check_id',$check_id) -> field('check_name') -> find();
            $data['check_name'] = $check_name['check_name'];
            $storeids = $data['store_ids'];
            $data['store_ids'] = implode(',',$data['store_ids']);
            $data['over_time'] = '每天23:59:59';
            $data['add_time'] = time();
            $data['status'] = 1;
            $where = array();
            $where['store_id'] = array('in',$storeids);
            $old_status1 = M('user_store') -> where($where) -> field('task_status') -> select();
            $old_status = array();
            foreach ($old_status1 as $key => $value) {
                foreach ($value as $k => $v) {
                    $old_status[] = $v;
                }
            }
            $data['old_status'] = implode(',',$old_status);
            $result = M('check_add_task')->add($data);

            if(0 !== $result || false !== $result){
                $save = array();
                $save['task_status'] = 1;
                M('user_store') -> where($where) -> save($save);
                $this->success('添加成功', U('StoreCheck/task'));

            }else{
                $this->error('添加失败');
            }
        }
    }
}