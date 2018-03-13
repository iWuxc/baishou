<?php
/**
 * 监控管理类
 * Author: dl
 * Date: 2017-12-14
 */
namespace app\admin\controller;
use think\AjaxPage;
use think\Page;
use think\Request;
use think\Verify;
use think\Db;
use think\Loader; 
class Monitoring extends Base{
    private $model = 'store_monitoring'; 
    /**
     * 监控摄像头列表 
    */
    public function index(){
        $admin_id = session('admin_id');
        $role = M('admin')->where(array('admin_id'=>$admin_id))->getField('role_id');
        if(!in_array($role, [1,2,5])){
            $filter['admin_id'] = $admin_id;
        }

        //用户名字搜索
        $user_name = I('user_name', '', 'trim');
        if($user_name){
            $filter['user_name'] = array('like', "%$user_name%");
            $search['user_name'] = $user_name;
        } 

        //用户类型搜索
        $user_type = I('user_type', '', 'trim');
        if($user_type){
            $filter['user_type'] = $user_type;
            $search['user_type'] = $user_type;
        } 

        //用户编号搜索
        $user_no = I('user_no', '', 'trim');
        if($user_no){
            $filter['user_no'] = array('like', "%$user_no%");
            $search['user_no'] = $user_no;
        } 

        //门店名称搜索
        $store_name = I('store_name', '', 'trim');
        if($store_name){
            $filter['store_name'] = array('like', "%$store_name%");
            $search['store_name'] = $store_name;
        } 

        //摄像头品牌或型号搜索
        $brand = I('brand', '', 'trim');
        if($user_name){
            $filter['brand'] = array('like', "%$brand%");
            $search['brand'] = $brand;
        } 

        $filter['status'] = 1;
        $model = $this->model;
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 10);
        $list = M($model) ->where($filter) -> order('store_id desc, id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();  

        $this -> assign('list', $list);
        $show  = $Page->show();
        $this -> assign('show',$show);
        $this -> assign('pager',$Page);
        $this -> assign('search',$search);
        $this -> assign('role',$role);
        return $this -> fetch();
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
            $list = M('user_store')->where(" store_no like '%$search_key%' or store_name like '%$search_key%' ")->where($condition)->select();   
            if(empty($list)){
                exit("<option value='0'>请选择</option>");
            }       
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['store_id']}'>{$val['store_name']} - {$val['store_no']}</option>";
            }                        
        }      
       
    }
    /**
     * 搜索dvr
     * 1,此项为信贷经理操作
     * 2,信贷经理只能搜索到自己客户下的所有门店
     */
    public function search_dvr()
    {
        $search_key = trim(I('search_key'));        
        if($search_key)    
        { 
            //查询条件:该门店下所有的dvr
            $condition['store_id'] = $search_key;
            $list = M('store_dvr')->where($condition)->select();  
            if(empty($list)){
                exit("<option value='0'>请选择</option>");
            }      
            foreach($list as $key => $val)
            {
                echo "<option value='{$val['id']}'>{$val['id']} - {$val['title']}</option>";
            }                        
        }
       
    }
    //添加监控 
    public function add(){ 
        $model = $this->model;
        $admin_id = session('admin_id');
        $admin_info = M('admin') -> field('admin_id, user_name, role_id') -> where(array('admin_id' => $admin_id)) -> find();

        if(empty($admin_info)){
            $this -> assign('管理员不存在');
        }

        // if(!in_array($admin_info['role_id'], [1, 4])){
        //     $this -> error('无添加权限！');
        // }

        if(!IS_POST){
            //总管理员 返回 所有用户 风控经理返回自己的用户
            $where = array();
            if(4 == $admin_info['role_id']){ 
                $where['xd_id'] = $admin_id;
            }            
            $users = M('users') -> field('user_id, client_no, name') -> where($where) ->order('user_id desc') -> select();
            $this -> assign('users', $users); 

            $action_url = U('monitoring/add');
            $this->assign('action_url', $action_url);
            return $this -> fetch('add'); 
        }else{
            //入库操作
            $data = I('post.', array()); 
            extract($data);

            //店铺信息
            $store_info = M('user_store')->field('store_id, store_no, user_id, store_name, province, city, district, address')->where("store_id = $store_id")->find(); 
            $data['store_no'] = $store_info['store_no'];
            $data['store_name'] = $store_info['store_name'];

            //用户信息
            $user_info = M('users') ->field('user_id, client_no, type, name')->where("user_id = ".$store_info['user_id'])->find();  
            $data['user_id'] = $user_info['user_id'];
            $data['user_no'] = $user_info['client_no'];
            $data['user_name'] = $user_info['name'];
            $data['user_type'] = $user_info['type'];
            //拼接地址
            $address = M('region')->where(array('id'=>$store_info['province']))->getField('name');
            if(!in_array($store_info['city'],[338, 10543, 31929])){
                $address .= M('region')->where(array('id'=>$store_info['city']))->getField('name');
            }
            $address .= M('region')->where(array('id'=>$store_info['district']))->getField('name');
            $address .= $store_info['address'];
            $data['store_address'] = $address;

            //添加人信息
            $data['admin_id'] = $admin_info['admin_id'];
            $data['admin_name'] = $admin_info['user_name'];

            $data['add_time'] = time();
            // $data['ip'] = $_SERVER['REMOTE_ADDR'];
            $data['ip'] = get_user_ip(); 
            $result = M($model)->add($data); 
            if(0 !== $result || false !== $result){
                //添加日志
                $log['action'] = 1;
                $log['admin_id'] = $admin_info['admin_id'];
                $log['admin_name'] = $admin_info['user_name'];
                $log['monitoring_id'] = $result;
                $log['monitoring_brand'] = $data['brand'];
                $log['user_id'] = $user_id;
                $log['user_no'] = $user_info['client_no'];
                $log['user_name'] = $user_info['name'];
                $log['store_id'] = $store_id;
                $log['store_no'] = $store_info['store_no'];
                $log['store_name'] = $store_info['store_name'];
                $log['time'] = time();
                $log['ip'] = $_SERVER['REMOTE_ADDR'];
                $log['real_ip'] = get_user_ip();                
                M('store_monitoring_log')->add($log);
                $this->success('添加成功', U('monitoring/index'));
            }else{
                $this->error('添加失败');
            }

        }
    } 

    //编辑
    public function edit(){
        $model = $this->model;
        $admin_id = session('admin_id');
        $admin_info = M('admin') -> field('admin_id, user_name, role_id') -> where(array('admin_id' => $admin_id)) -> find();

        if(empty($admin_info)){
            $this -> assign('管理员不存在');
        }

        // if(!in_array($admin_info['role_id'], [1, 4])){
        //     $this -> error('无修改权限！');
        // } 
        $id = I('id', 0);

        $info = M($model)->where(array('id'=>$id))->find();
 
        if(!$info){
            $this->error('该监控信息不存在');
        }
        if(!IS_POST){ 
            $info['title'] = M('store_dvr')->where(array('id'=>$info['dvr_id']))->getField('title');
            $action_url = U('monitoring/edit'); 
            //总管理员 返回 所有用户 风控经理返回自己的用户
            $where = array();
            if(4 == $admin_info['role_id']){ 
                $where['xd_id'] = $admin_id;
            }            
            $users = M('users') -> field('user_id, client_no, name') -> where($where) ->order('user_id desc') -> select();
            $stores = M('user_store') -> field('store_id, store_no, store_name') -> where('user_id='.$info['user_id']) ->order('user_id desc') -> select(); 
            $dvr_list = M('store_dvr') -> field('id, title') -> where(['store_id'=>$info['store_id']])->select();
            $this -> assign('dvr_list', $dvr_list); 
            $this -> assign('action_url', $action_url); 
            $this -> assign('info', $info); 
            $this -> assign('users', $users);  
            $this -> assign('stores', $stores);  
            return $this -> fetch('add'); 
        }else{
            //更新操作 
            $data = I('post.', array()); 
            extract($data); 

            //店铺信息
            if($store_id != $info['store_id']){ 
                $store_info = M('user_store')->field('store_id, store_no, user_id, store_name, province, city, district, address')->where("store_id = $store_id")->find(); 
                $data['store_no'] = $store_info['store_no'];
                $data['store_name'] = $store_info['store_name'];

                //用户信息
                $user_info = M('users') ->field('user_id, client_no, type, name')->where("user_id = ".$store_info['user_id'])->find(); 
                $data['user_id'] = $user_info['user_id'];
                $data['user_no'] = $user_info['client_no'];
                $data['user_name'] = $user_info['name'];
                $data['user_type'] = $user_info['type'];

                //拼接地址
                $address = M('region')->where(array('id'=>$store_info['province']))->getField('name');
                if(!in_array($store_info['city'],[338, 10543, 31929])){
                    $address .= M('region')->where(array('id'=>$store_info['city']))->getField('name');
                }
                $address .= M('region')->where(array('id'=>$store_info['district']))->getField('name');
                $address .= $store_info['address'];
                $data['store_address'] = $address; 
            } 
            $result = M($model)->where('id='.$id)->save($data); 
            if(0 !== $result || false !== $result){
                //添加日志
                $info = M($model)->where(array('id'=>$id))->find();
                $log['action'] = 2;
                $log['admin_id'] = $info['admin_id'];
                $log['admin_name'] = $info['admin_name'];
                $log['monitoring_id'] = $info['id'];
                $log['monitoring_brand'] = $info['brand'];
                $log['user_id'] = $info['user_id'];
                $log['user_no'] = $info['user_no'];
                $log['user_name'] = $info['user_name'];
                $log['store_id'] = $info['store_id'];
                $log['store_no'] = $info['store_no'];
                $log['store_name'] = $info['store_name'];
                $log['time'] = time();
                $log['ip'] = $_SERVER['REMOTE_ADDR'];
                $log['real_ip'] = get_user_ip();  
                M('store_monitoring_log')->add($log); 
                $this->success('更新成功', U('monitoring/index'));
            }else{
                $this->error('更新失败');
            }

        }

    }

    //详情
    public function _empty(){
        $model = $this->model;
        $admin_id = session('admin_id');
        $admin_info = M('admin') -> field('admin_id, user_name, role_id') -> where(array('admin_id' => $admin_id)) -> find();

        if(empty($admin_info)){
            $this -> assign('管理员不存在');
        }

        // if(!in_array($admin_info['role_id'], [1, 4])){
        //     $this -> error('无查看权限！');
        // } 
        $id = I('id'); 

        if(!ctype_digit($id)){
            $this->error('该监控信息不存在');
        }
        $info = M($model)->where(array('id'=>$id))->find();
        $list = M($model)->where(array('store_id'=>$info['store_id']))->select();
        $info['add_time'] = date('Y-m-d H:i:s', $info['add_time']);
        foreach ($list as $k => $v) {
            $list[$k]['add_time'] = date('Y-m-d H:i:s', $v['add_time']);
        }
        
        // print_r($list);die;.
// echo M($model)->getLastSql();die;
        $this->assign('info', $info); 
        $this->assign('list', $list);  
        $this->assign('down_vlc1', SITE_URL.'/public/down/vlc-2.2.8-win32.exe'); 
        $this->assign('down_vlc2', 'https://www.videolan.org/'); 
        return $this -> fetch();  
    }
    //获取该用户下所有门店
    public function get_user_stores(){ 
        $id = I('get.id/d');
        $stores = M('user_store') -> field('store_id, store_no, store_name') -> where(array('user_id'=>$id)) ->order('store_id desc') -> select();
        $this->ajaxReturn($stores);
    } 
    //获取该用户下所有门店
    public function del(){ 
        $model = $this->model;
        $admin_id = session('admin_id');
        $admin_info = M('admin') -> field('admin_id, user_name, role_id') -> where(array('admin_id' => $admin_id)) -> find();

        if(empty($admin_info)){ 
            $data['success'] = 2;
            $data['msg'] = '管理员不存在';
            $this->ajaxReturn($data);
        }

        // if(!in_array($admin_info['role_id'], [1, 4])){
        //     $data['success'] = 2;
        //     $data['msg'] = '无删除权限！';
        //     $this->ajaxReturn($data); 
        // } 
        $id = I('id', 0);

        $info = M($model)->where(array('id'=>$id))->find();
 
        if(!$info){
            $data['success'] = 2;
            $data['msg'] = '该监控信息不存在';
            $this->ajaxReturn($data); 
        }

        $result = M($model) -> where(array('id'=>$id))->setField('status', 2);
        if(!$result){
            $data['success'] = 2;
            $data['msg'] = '操作成功失败，请稍后再试！'.M($model)->getLastSql();;
            $this->ajaxReturn($data);
        }
        //添加日志
        $info = M($model)->where(array('id'=>$id))->find();
        $log['action'] = 3;
        $log['admin_id'] = $info['admin_id'];
        $log['admin_name'] = $info['admin_name'];
        $log['monitoring_id'] = $info['id'];
        $log['monitoring_brand'] = $info['brand'];
        $log['user_id'] = $info['user_id'];
        $log['user_no'] = $info['user_no'];
        $log['user_name'] = $info['user_name'];
        $log['store_id'] = $info['store_id'];
        $log['store_no'] = $info['store_no'];
        $log['store_name'] = $info['store_name'];
        $log['time'] = time();
        $log['ip'] = $_SERVER['REMOTE_ADDR'];
        $log['real_ip'] = get_user_ip();  
        M('store_monitoring_log')->add($log); 

        $data['success'] = 1;
        $data['msg'] = '已删除！';
        $this->ajaxReturn($data);
    } 

    /**
     * 操作日志 
    */
    public function log(){
        $admin_id = session('admin_id');
        $role = M('admin')->where(array('admin_id'=>$admin_id))->getField('role_id');
        // if(!in_array($role, [1,2,4])){            
        //     $this->error('无查看权限！');
        // }


        //操作类型类型搜索
        $action = I('action', '', 'trim');
        if($action){
            $filter['action'] = $action;
            $search['action'] = $action;
        }  
        //管理员名字搜索
        $admin_name = I('admin_name', '', 'trim');
        if($admin_name){
            $filter['admin_name'] = array('like', "%$admin_name%");
            $search['admin_name'] = $admin_name;
        } 
        //用户名字搜索
        $user_name = I('user_name', '', 'trim');
        if($user_name){
            $filter['user_name'] = array('like', "%$user_name%");
            $search['user_name'] = $user_name;
        } 

        //用户编号搜索
        $user_no = I('user_no', '', 'trim');
        if($user_no){
            $filter['user_no'] = array('like', "%$user_no%");
            $search['user_no'] = $user_no;
        } 

        //门店名称搜索
        $store_name = I('store_name', '', 'trim');
        if($store_name){
            $filter['store_name'] = array('like', "%$store_name%");
            $search['store_name'] = $store_name;
        } 
        //门店编号搜索
        $store_no = I('store_no', '', 'trim');
        if($store_no){
            $filter['store_no'] = array('like', "%$store_no%");
            $search['store_no'] = $store_no;
        } 

        //摄像头品牌或型号搜索
        $monitoring_brand = I('monitoring_brand', '', 'trim');
        if($user_name){
            $filter['monitoring_brand'] = array('like', "%$monitoring_brand%");
            $search['monitoring_brand'] = $monitoring_brand;
        } 
 
        $model = 'store_monitoring_log';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 10);
        $list = M($model) ->where($filter) -> order('id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();  

        $this -> assign('list', $list);
        $show  = $Page->show();
        $this -> assign('show',$show);
        $this -> assign('pager',$Page);
        $this -> assign('search',$search);
        return $this -> fetch();
    }
    /**
     * 监控摄像头 故障 列表 
    */
    public function bad_list(){
        $admin_id = session('admin_id');
        $role = M('admin')->where(array('admin_id'=>$admin_id))->getField('role_id');
        if(!in_array($role, [1,2,5])){
            $filter['admin_id'] = $admin_id;
        }

        //故障类型
        // $type2 = I('type2', '', 'trim');
        // if($type2){
        //     $filter['type2'] = $type2;
        //     $search['type2'] = $type2;
        // } 
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

        //监控型号或名称
        $value = I('value', '', 'trim');
        if($value){
            $filter['value'] = array('like', "%$value%");
            $search['value'] = $value;
        } 

        $filter['type'] = 1;
        $filter['status'] = 1;
        $model = 'equipment_wrong';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 10);
        $list = M($model) ->where($filter) -> order('id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();
        foreach ($list as $key => $value) {
               $list[$key]['pawn_detail'] = '/index.php/Admin/Monitoring/_empty/id/'.$value['em_id'];
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
     * 监控摄像头 故障 已修复 列表 
    */
    public function recovery_list(){
        $admin_id = session('admin_id');
        $role = M('admin')->where(array('admin_id'=>$admin_id))->getField('role_id');
        if(!in_array($role, [1,2,5])){
            $filter['admin_id'] = $admin_id;
        }

        //故障类型
        // $type2 = I('type2', '', 'trim');
        // if($type2){
        //     $filter['type2'] = $type2;
        //     $search['type2'] = $type2;
        // } 
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

        //监控型号或名称
        $value = I('value', '', 'trim');
        if($value){
            $filter['value'] = array('like', "%$value%");
            $search['value'] = $value;
        } 

        $filter['type'] = 1;
        $filter['status'] = 2;
        $model = 'equipment_wrong';
        $count = M($model) ->where($filter) -> count();
        $Page = new Page($count, 10);
        $list = M($model) ->where($filter) -> order('id desc') -> limit($Page->firstRow.','.$Page->listRows) -> select();
        foreach ($list as $key => $value) { 
               $list[$key]['cut_store_name'] = cut_str($value['store_name'], 15);
               $list[$key]['cut_value'] = cut_str($value['value'], 12);
               $list[$key]['pawn_detail'] = '/index.php/Admin/Monitoring/_empty/id/'.$value['em_id'];
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