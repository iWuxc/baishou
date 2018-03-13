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

class Admin extends Base {

    public function index(){
    	$list = array();
    	$keywords = I('keywords/s');
    	if(empty($keywords)){
    		$res = D('admin')->select();
    	}else{
			$res = DB::name('admin')->where('user_name','like','%'.$keywords.'%')->order('admin_id')->select();
    	}
    	$role = D('admin_role')->getField('role_id,role_name');
    	if($res && $role){
    		foreach ($res as $val){
    			$val['role'] =  $role[$val['role_id']];
    			$val['add_time'] = date('Y-m-d H:i:s',$val['add_time']);
    			$list[] = $val;
    		}
    	}
  
    	$this->assign('list',$list);
        return $this->fetch();
    }
    
    /**
     * 修改管理员密码
     * @return \think\mixed
     */
    public function modify_pwd(){
        $admin_id = I('admin_id/d',0);
        $oldPwd = I('old_pw/s');
        $newPwd = I('new_pw/s');
        $new2Pwd = I('new_pw2/s');
       
        if($admin_id){
            $info = D('admin')->where("admin_id", $admin_id)->find();
            $info['password'] =  "";
            $this->assign('info',$info);
        }
        
         if(IS_POST){
            //修改密码
            $enOldPwd = encrypt($oldPwd);
            $enNewPwd = encrypt($newPwd);
            $admin = M('admin')->where('admin_id' , $admin_id)->find();
            if(!$admin || $admin['password'] != $enOldPwd){
                exit(json_encode(array('status'=>-1,'msg'=>'旧密码不正确')));
            }else if($newPwd != $new2Pwd){
                exit(json_encode(array('status'=>-1,'msg'=>'两次密码不一致')));
            }else{
                $row = M('admin')->where('admin_id' , $admin_id)->save(array('password' => $enNewPwd));
                if($row){
                    exit(json_encode(array('status'=>1,'msg'=>'修改成功')));
                }else{
                    exit(json_encode(array('status'=>-1,'msg'=>'修改失败')));
                }
            }
        }
        return $this->fetch();
    }
    
    public function admin_info(){
    	$admin_id = I('get.admin_id/d',0);
    	if($admin_id){
    		$info = D('admin')->where("admin_id", $admin_id)->find();
			$info['password'] =  "";
    		$this->assign('info',$info);
    	}
    	$act = empty($admin_id) ? 'add' : 'edit';
    	$this->assign('act',$act);
    	$role = D('admin_role')->select();
    	$this->assign('role',$role);
    	return $this->fetch();
    }
    
    public function adminHandle(){
    	$data = I('post.'); 
        $pwd  = $data['password'];
    	if(empty($data['password'])){
    		unset($data['password']);
    	}else{
    		$data['password'] = encrypt($data['password']);
    	}
    	if($data['act'] == 'add'){
    		unset($data['admin_id']);    		
    		$data['add_time'] = time();
    		if(D('admin')->where("mobile", $data['mobile'])->count()){
    			$this->error("此手机号已被注册，请更换",U('Admin/Admin/admin_info'));
    		}else{
    			$r = D('admin')->add($data);
                if($r)
                {
                    //新添加一个管理员后,给被添加的管理员发短信
                    $this->send_adminSMS($r,$pwd);
                }
    		}
    	}
    	
    	if($data['act'] == 'edit'){
    		$r = D('admin')->where('admin_id', $data['admin_id'])->save($data);
    	}
    	
        if($data['act'] == 'del' && $data['admin_id']>1){
    		$r = D('admin')->where('admin_id', $data['admin_id'])->delete();
    		exit(json_encode(1));
    	}
    	
    	if($r){
    		$this->success("操作成功",U('Admin/Admin/index'));
    	}else{
    		$this->error("操作失败",U('Admin/Admin/index'));
    	}
    }

    /**
     * 调用极验验证
     */
    public function startCaptchaServlet(){
        /** 引入核心类 */
        Vendor("JiYan.GeetestLib");
        $GtSdk = new \GeetestLib(C('CAPTCHA_ID'),C('PRIVATE_KEY'));
        $data = array(
            "user_id" => "test", # 网站用户id
            "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
            "ip_address" => GetHostByName($_SERVER['SERVER_NAME']), # 请在此处传输用户请求验证时所携带的IP
        );
        $status = $GtSdk->pre_process($data, 1);
        session('gtserver', $status);
        session('gt_user_id', $data['user_id']);
        echo $GtSdk->get_response_str();
    }

    /*
     * 管理员登陆
     */
    public function login(){
        if(session('?admin_id') && session('admin_id')>0){
            $this->error("您已登录",U('Admin/Index/index'));
        }

        if(IS_POST){
            $condition['mobile'] = trim(I('post.mobile/s'));
            $condition['password'] = I('post.password/s');
            /** 引入核心类 */
            Vendor("JiYan.GeetestLib");
            $GtSdk = new \GeetestLib(C('CAPTCHA_ID'),C('PRIVATE_KEY'));
            $gt_data = array(
                "user_id" => \session('gt_user_id'), # 网站用户id
                "client_type" => "web", #web:电脑上的浏览器；h5:手机上的浏览器，包括移动应用内完全内置的web_view；native：通过原生SDK植入APP应用的方式
                "ip_address" => GetHostByName($_SERVER['SERVER_NAME']) # 请在此处传输用户请求验证时所携带的IP
            );
            if ($_SESSION['gtserver'] == 1) {   //服务器正常
                $result = $GtSdk->success_validate($_POST['geetest_challenge'], $_POST['geetest_validate'], $_POST['geetest_seccode'], $gt_data);
                if (!$result) {
                    exit(json_encode(array('status'=>0,'msg'=>'验证失败')));
                }
            }else{  //服务器宕机,走failback模式
                if (!$GtSdk->fail_validate($_POST['geetest_challenge'],$_POST['geetest_validate'],$_POST['geetest_seccode'])) {
                    exit(json_encode(array('status'=>0,'msg'=>'验证失败')));
                }
            }


            if(!empty($condition['mobile']) && !empty($condition['password'])){
                $condition['password'] = encrypt($condition['password']);
                $admin_info = M('admin')->join(PREFIX.'admin_role', PREFIX.'admin.role_id='.PREFIX.'admin_role.role_id','INNER')->where($condition)->find();
                if(is_array($admin_info)){
                    session('admin_info', $admin_info); //write by iWuxc //后期需要详细信息
                    session('admin_id',$admin_info['admin_id']);
                    session('role_id',$admin_info['role_id']);
                    session('act_list',$admin_info['act_list']);
                    M('admin')->where("admin_id = ".$admin_info['admin_id'])->save(array('last_login'=>time(),'last_ip'=>  request()->ip()));
                    session('last_login_time',$admin_info['last_login']);
                    session('last_login_ip',$admin_info['last_ip']);
                    adminLog('后台登录');
                    $url = session('from_url') ? session('from_url') : U('Admin/Index/index');
                    exit(json_encode(array('status'=>1,'url'=>$url)));
                }else{
                    exit(json_encode(array('status'=>0,'msg'=>'账号密码不正确')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }

        return $this->fetch();
    }
    public function login_bak(){
        if(session('?admin_id') && session('admin_id')>0){
             $this->error("您已登录",U('Admin/Index/index'));
        }

        if(IS_POST){
            $verify = new Verify();
           if (!$verify->check(I('post.vertify'), "admin_login")) {
           	exit(json_encode(array('status'=>0,'msg'=>'验证码错误')));
           }
            $condition['mobile'] = trim(I('post.mobile/s'));
            $condition['password'] = I('post.password/s');
            if(!empty($condition['mobile']) && !empty($condition['password'])){
                $condition['password'] = encrypt($condition['password']);
               	$admin_info = M('admin')->join(PREFIX.'admin_role', PREFIX.'admin.role_id='.PREFIX.'admin_role.role_id','INNER')->where($condition)->find();
                if(is_array($admin_info)){
                    session('admin_info', $admin_info); //write by iWuxc //后期需要详细信息
                    session('admin_id',$admin_info['admin_id']);
                    session('role_id',$admin_info['role_id']);
                    session('act_list',$admin_info['act_list']);
                    M('admin')->where("admin_id = ".$admin_info['admin_id'])->save(array('last_login'=>time(),'last_ip'=>  request()->ip()));
                    session('last_login_time',$admin_info['last_login']);
                    session('last_login_ip',$admin_info['last_ip']);
                    adminLog('后台登录');
                    $url = session('from_url') ? session('from_url') : U('Admin/Index/index');
                    exit(json_encode(array('status'=>1,'url'=>$url)));
                }else{
                    exit(json_encode(array('status'=>0,'msg'=>'账号密码不正确')));
                }
            }else{
                exit(json_encode(array('status'=>0,'msg'=>'请填写账号密码')));
            }
        }

       return $this->fetch();
    }
    
    /**
     * 退出登陆
     */
    public function logout(){
        session_unset();
        session_destroy();
		session::clear();
        $this->success("退出成功",U('Admin/Admin/login'));
    }
    
    /**
     * 验证码获取
     */
    public function vertify()
    {
        $config = array(
            'fontSize' => 30,
            'length' => 4,
            'useCurve' => false,
            'useNoise' => false,
        	'reset' => false
        );    
        $Verify = new Verify($config);
        $Verify->entry("admin_login");
        exit();
    }
    
    public function role(){
    	$list = D('admin_role')->order('role_id desc')->select();
    	$this->assign('list',$list);
    	return $this->fetch();
    }
    
    public function role_info(){
    	$role_id = I('get.role_id/d');
    	$detail = array();
    	if($role_id){
    		$detail = M('admin_role')->where("role_id",$role_id)->find();
    		$detail['act_list'] = explode(',', $detail['act_list']);
    		$this->assign('detail',$detail);
    	}
		$right = M('system_menu')->order('id')->select();
		foreach ($right as $val){
			if(!empty($detail)){
				$val['enable'] = in_array($val['id'], $detail['act_list']);
			}
			$modules[$val['group']][] = $val;
		}
		//权限组
		$group = array(
            'system' =>'系统设置',
            'member' =>'客户管理',  
            'loan'   =>'贷款管理',
            'pawn'   =>'抵押品管理',
            'monitor'=>'监控管理',
            'check'  =>'审核管理'  
		);
		$this->assign('group',$group);
		$this->assign('modules',$modules) ;
    	return $this->fetch();
    }
    
    public function roleSave(){
    	$data = I('post.');
    	$res = $data['data'];
    	$res['act_list'] = is_array($data['right']) ? implode(',', $data['right']) : '';
        if(empty($res['act_list']))
            $this->error("请选择权限!");        
    	if(empty($data['role_id'])){
			$admin_role = Db::name('admin_role')->where(['role_name'=>$res['role_name']])->find();
			if($admin_role){
				$this->error("已存在相同的角色名称!");
			}else{
				$r = D('admin_role')->add($res);
			}
    	}else{
			$admin_role = Db::name('admin_role')->where(['role_name'=>$res['role_name'],'role_id'=>['<>',$data['role_id']]])->find();
			if($admin_role){
				$this->error("已存在相同的角色名称!");
			}else{
				$r = D('admin_role')->where('role_id', $data['role_id'])->save($res);
			}
    	}
		if($r){
			adminLog('管理角色');
			$this->success("操作成功!",U('Admin/Admin/role_info',array('role_id'=>$data['role_id'])));
		}else{
			$this->error("操作失败!",U('Admin/Admin/role'));
		}
    }
    
    public function roleDel(){
    	$role_id = I('post.role_id/d');
    	$admin = D('admin')->where('role_id',$role_id)->find();
    	if($admin){
    		exit(json_encode("请先清空所属该角色的管理员"));
    	}else{
    		$d = M('admin_role')->where("role_id", $role_id)->delete();
    		if($d){
    			exit(json_encode(1));
    		}else{
    			exit(json_encode("删除失败"));
    		}
    	}
    }
    
    public function log(){
    	$p = I('p/d',1);
    	$logs = DB::name('admin_log')->alias('l')->join('__ADMIN__ a','a.admin_id =l.admin_id')->order('log_time DESC')->page($p.',20')->select();
    	$this->assign('list',$logs);
    	$count = DB::name('admin_log')->count();
    	$Page = new Page($count,20);
    	$show = $Page->show();
		$this->assign('pager',$Page);
		$this->assign('page',$show);
    	return $this->fetch();
    }


	/**
	 * 供应商列表
	 */
	public function supplier()
	{
		$supplier_count = DB::name('suppliers')->count();
		$page = new Page($supplier_count, 10);
		$show = $page->show();
		$supplier_list = DB::name('suppliers')
				->alias('s')
				->field('s.*,a.admin_id,a.user_name')
				->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
				->limit($page->firstRow, $page->listRows)
				->select();
		$this->assign('list', $supplier_list);
		$this->assign('page', $show);
		return $this->fetch();
	}

	/**
	 * 供应商资料
	 */
	public function supplier_info()
	{
		$suppliers_id = I('get.suppliers_id/d', 0);
		if ($suppliers_id) {
			$info = DB::name('suppliers')
					->alias('s')
					->field('s.*,a.admin_id,a.user_name')
					->join('__ADMIN__ a','a.suppliers_id = s.suppliers_id','LEFT')
					->where(array('s.suppliers_id' => $suppliers_id))
					->find();
			$this->assign('info', $info);
		}
		$act = empty($suppliers_id) ? 'add' : 'edit';
		$this->assign('act', $act);
		$admin = M('admin')->field('admin_id,user_name')->select();
		$this->assign('admin', $admin);
		return $this->fetch();
	}

	/**
	 * 供应商增删改
	 */
	public function supplierHandle()
	{
		$data = I('post.');
		$suppliers_model = M('suppliers');
		//增
		if ($data['act'] == 'add') {
			unset($data['suppliers_id']);
			$count = $suppliers_model->where("suppliers_name", $data['suppliers_name'])->count();
			if ($count) {
				$this->error("此供应商名称已被注册，请更换", U('Admin/Admin/supplier_info'));
			} else {
				$r = $suppliers_model->insertGetId($data);
				if (!empty($data['admin_id'])) {
					$admin_data['suppliers_id'] = $r;
					M('admin')->where(array('suppliers_id' => $admin_data['suppliers_id']))->save(array('suppliers_id' => 0));
					M('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
				}
			}
		}
		//改
		if ($data['act'] == 'edit' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->save($data);
			if (!empty($data['admin_id'])) {
				$admin_data['suppliers_id'] = $data['suppliers_id'];
				$suppliers = $suppliers_model->where('suppliers_id',$data['suppliers_id'])->find();
				$admin_data['city_id'] = $suppliers['city_id'];
				$admin_data['province_id'] = $suppliers['province_id'];
				M('admin')->where(array('admin_id' => $data['admin_id']))->save($admin_data);
			}
		}
		//删
		if ($data['act'] == 'del' && $data['suppliers_id'] > 0) {
			$r = $suppliers_model->where('suppliers_id', $data['suppliers_id'])->delete();
			M('admin')->where(array('suppliers_id' => $data['suppliers_id']))->save(array('suppliers_id' => 0));
		}

		if ($r !== false) {
			$this->success("操作成功", U('Admin/Admin/supplier'));
		} else {
			$this->error("操作失败", U('Admin/Admin/supplier'));
		}
	}
     /**
     * 发送短信
     * 场景:新增一个管理员后,给被添加管理员发送短信
     * type短信类型
     * admin_id管理员ID
     * pwd 登陆密码
     */
   public function send_adminSMS($admin_id,$pwd)
   {
      
      //查询被添加人信息
      $admin = M('admin')->field('user_name,mobile,role_id')->where(['admin_id'=>$admin_id])->find();
      $adminname   = $admin['user_name'];
      $adminmobile = $admin['mobile'];
      $role_id = $admin['role_id'];
      //查询被添加人角色名称
      $role = M('admin_role')->field('role_name')->where(['role_id'=>$role_id])->find();
      $role_name = $role['role_name'];
      //给添加人发短信
        //拼接短信参数
        // $sender = $xdmobile;//接受短信的手机号码
        $mobile = $adminmobile;
        $content = '尊敬的'.$adminname.'，您的账号为'.$adminmobile.'，密码为'.$pwd.'已被添加为:'.$role_name.'，请登陆后修改密码!';
        
        $result = send_sms($mobile, $content);
        return $result;
      
   }
   //代办事项
    public function welcome(){
        //抵押品待评估数量
        $count['dpg_pawn'] = M('pawn')->where(['status'=>-3])->order('pawn_id')->count();
        //抵押品评估待审核数量
        $count['pgdsh_pawn'] = M('pawn')->where(['status'=>0])->order('pawn_id')->count();
        //抵押品解押待审核数量
        $count['jydsh_pawn'] = M('pawn')->where(['status'=>2])->order('pawn_id')->count();
        //审批结论待复核的数量
        $count['spjl_users'] = M('credit_conclusion')->where(['status'=>1])->order('user_id')->count();
        //贷款待审核数量
        $count['dk_loan'] = M('loan')->where(['is_check'=>3])->order('user_id')->count();
        $this->assign('count',$count);
        $this->assign('sys_info',$this->get_sys_info());//系统信息
        $this->assign('role_id',session('admin_info')["role_id"]);
        return $this->fetch();
    }
    public function get_sys_info(){
        $sys_info['os']             = PHP_OS;
        $sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
        $sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off       
        $sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $sys_info['curl']           = function_exists('curl_init') ? 'YES' : 'NO';  
        $sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
        $sys_info['phpv']           = phpversion();
        $sys_info['ip']             = GetHostByName($_SERVER['SERVER_NAME']);
        $sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
        $sys_info['max_ex_time']    = @ini_get("max_execution_time").'s'; //脚本最大执行时间
        $sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $sys_info['domain']         = $_SERVER['HTTP_HOST'];
        $sys_info['memory_limit']   = ini_get('memory_limit');                                  
        $sys_info['version']        = file_get_contents(APP_PATH.'admin/conf/version.php');
        $mysqlinfo = Db::query("SELECT VERSION() as version");
        $sys_info['mysql_version']  = $mysqlinfo[0]['version'];
        if(function_exists("gd_info")){
            $gd = gd_info();
            $sys_info['gdinfo']     = $gd['GD Version'];
        }else {
            $sys_info['gdinfo']     = "未知";
        }
        return $sys_info;
    }

}