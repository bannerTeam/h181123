<?php
namespace app\admin\controller;

use think\Cache;

class Proxy extends Base
{

    var $_pre;

    public function __construct()
    {
        parent::__construct();
        $this->_pre = 'proxy';
    }

    public function index()
    {
        
        if (Request()->isPost()) {
            
            $param = input('post.');
            
            if (! in_array($param['status'], [
                1,
                2
            ])) {
                return $this->error('失败!');
            }
            $id = intval($param['id']);
            if (empty($id)) {
                return $this->error('参数错误');
            }
            
            
            $data['id'] = $id;
            $data['status'] = $param['status'];
            $res = model('Proxy')->saveData($data);
            
            return json($res);
        }
        
        
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
       
        if(isset($param['status']) && $param['status']!=''){
            $where['status'] = array('eq',$param['status']);
        }
        
        
        //结束时间
        if(isset($param['end_date']) && $param['end_date']!=''){
            $is_endtime = true;
            $enttime = strtotime($param['end_date'] . ' 23:23:59');
        }else{
            $param['end_date'] = date('Y-m-d');
            $enttime = time();
        }
        
        //开始时间
        if(isset($param['begin_date']) && $param['begin_date']!=''){
            $starttime = strtotime($param['begin_date'] . ' 00:00:00'); 
        }else{
            if($is_endtime){
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month",$enttime));
                $starttime = strtotime("-3 month",$enttime);
            }else{
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month"));
                $starttime = strtotime("-3 month");
            }            
        }                
        
        $where['add_time'] = array('between', array($starttime,$enttime));
        
        $order = 'id desc';
        
        
        $res = model('Proxy')->listData($where, $order, $param['page']);
        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);
        
        
        $this->assign('limit', $res['limit']);
        
        
        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param', $param);
        
        $this->assign('title', '代理管理');
        return $this->fetch('admin@proxy/index');
    }

    /**
     * 代理详情
     * 
     * @return unknown
     */
    public function info()
    {
        $param = input();
        
        $id = intval($param['id']);
        
        $this->assign('id', $id);
        
        $where['id'] = $id;
        $res = model('Proxy')->findData($where);
        
        $info = $res['info'];
        
        $this->assign('info', $info);
        
        $this->assign('title', '代理详情');
        return $this->fetch('admin@proxy/info');
    }

    /**
     * 申请代理审批
     * 
     * @return unknown
     */
    public function apply()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        
        if(isset($param['status']) && $param['status']!=''){
            $where['status'] = array('eq',$param['status']);
        }
        
        
        //结束时间
        if(isset($param['end_date']) && $param['end_date']!=''){
            $is_endtime = true;
            $enttime = strtotime($param['end_date'] . ' 23:23:59');
        }else{
            $param['end_date'] = date('Y-m-d');
            $enttime = time();
        }
        
        //开始时间
        if(isset($param['begin_date']) && $param['begin_date']!=''){
            $starttime = strtotime($param['begin_date'] . ' 00:00:00');
        }else{
            if($is_endtime){
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month",$enttime));
                $starttime = strtotime("-3 month",$enttime);
            }else{
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month"));
                $starttime = strtotime("-3 month");
            }
        }
        
        $where['add_time'] = array('between', array($starttime,$enttime));
        
        $order = 'id desc';
        
       
        $res = model('ProxyApply')->listData($where, $order, $param['page']);
        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);
        
        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param', $param);
        
        $this->assign('title', '代理审批管理');
        return $this->fetch('admin@proxy/apply');
    }

    /**
     * 代理提现审批
     * 
     * @return unknown
     */
    public function withdraw()
    {
        if (Request()->isPost()) {
            
            $param = input('post.');
            
            if (! in_array($param['status'], [
                1,
                2
            ])) {
                return $this->error('失败!');
            }
            $id = intval($param['id']);
            if (empty($id)) {
                return $this->error('参数错误');
            }
            if ($param['status'] == 2) {
                $res = model('ProxyWithdraw')->refuse($this->_admin['admin_id'], $id);
            } else {
                $res = model('ProxyWithdraw')->complete($this->_admin['admin_id'], $id);
            }
            return json($res);
        }
        
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        if(isset($param['status']) && $param['status']!=''){
            $where['status'] = array('eq',$param['status']);
        }
        
        
        //结束时间
        if(isset($param['end_date']) && $param['end_date']!=''){
            $is_endtime = true;
            $enttime = strtotime($param['end_date'] . ' 23:23:59');
        }else{
            $param['end_date'] = date('Y-m-d');
            $enttime = time();
        }
        
        //开始时间
        if(isset($param['begin_date']) && $param['begin_date']!=''){
            $starttime = strtotime($param['begin_date'] . ' 00:00:00');
        }else{
            if($is_endtime){
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month",$enttime));
                $starttime = strtotime("-3 month",$enttime);
            }else{
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month"));
                $starttime = strtotime("-3 month");
            }
        }
        
        $where['add_time'] = array('between', array($starttime,$enttime));
        
        $res = model('ProxyWithdraw')->listData($where);
        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);
        
        
        $this->assign('param', $param);
        
        $this->assign('title', '代理提现审批');
        return $this->fetch('admin@proxy/withdraw');
    }

    /**
     * 申请详情
     * 
     * @return unknown
     */
    public function apply_info()
    {
        $param = input();
        
        $id = intval($param['id']);
        
        if (Request()->isPost()) {
            
            $param = input('post.');
            $res = model('ProxyApply')->saveData($param);
            if ($res['code'] > 1) {
                return $this->error('保存失败!' . $res['msg']);
            }
            
            return $this->success('保存成功!');
        }
        
        $this->assign('id', $id);
        
        $where['id'] = $id;
        $res = model('ProxyApply')->findData($where);
        
        $info = $res['info'];
        
        $this->assign('info', $info);
        
        $this->assign('title', '代理申请');
        return $this->fetch('admin@proxy/apply_info');
    }

    /**
     * 代理申请通过
     */
    public function apply_pass()
    {
        $param = input();
        
        $id = intval($param['id']);
        $user_id = intval($param['user_id']);
        if (! is_numeric($id) || ! is_numeric($user_id)) {
            return $this->error('通过失败，请重试!');
        }
        
        $uWhere['user_id'] = $user_id;
        $uRes = model('User')->infoData($uWhere, 'user_name,inviter_user_id');
        if ($uRes['code'] > 1) {
            return $this->error('用户不存在!');
        }
        $uInfo = $uRes['info'];
        
        $where['user_id'] = $user_id;
        $res = model('Proxy')->findData($where, 'id');
        if ($res['code'] === 1) {
            return $this->error('已经是代理,无需开通!');
        }
        unset($where);
        
        $data['id'] = $id;
        $data['status'] = 1;
        $res = model('ProxyApply')->saveData($data);
        
        if ($res['code'] === 1) {
            $where['id'] = $id;
            
            $proxy_id = 0;
            //邀请人会员ID/推荐人
            $inviter_user_id = $uInfo['inviter_user_id'];                      
            if($inviter_user_id){
                $pWhere['user_id'] = $inviter_user_id;
                $pRes = model('Proxy')->findData($pWhere, 'id');
                if ($pRes['code'] === 1) {
                    $proxy_id  = $pRes['info']['id'];
                }                
            }
                        
            unset($param['id']);
            $pdata = $param;
            //上级代理ID
            $pdata['pid'] = $proxy_id;
            $pdata['name'] = $uInfo['user_name'];
            $pdata['user_id'] = $user_id;
            $pdata['amount'] = 0;
            $pdata['status'] = 1;
            $pdata['admin_id'] = $this->_admin['admin_id'];
            
            $res = model('Proxy')->saveData($pdata);
            
            return $this->success('通过成功!');
        }
        return $this->error('通过失败，请重试');
    }

    /**
     * 代理申请拒绝
     */
    public function apply_refuse()
    {
        $param = input();
        
        $id = intval($param['id']);
        if (! is_numeric($id)) {
            return $this->error('拒绝失败，请重试!');
        }
        
        $data['id'] = $id;
        $data['status'] = 2;
        $res = model('ProxyApply')->saveData($data);
        
        if ($res['code'] === 1) {
            return $this->success('拒绝成功!');
        }
        return $this->error('拒绝失败，请重试');
    }

    public function del()
    {
        $param = input();
        
        $adv_id = intval($param['id']);
        if (! is_numeric($adv_id)) {
            return $this->error('删除失败，请重试!');
        }
        
        $where['id'] = $adv_id;
        $res = model('Adv')->delData($where);
        
        if ($res['code'] === 1) {
            return $this->success('删除成功!');
        }
        return $this->error('删除失败，请重试');
    }

    public function field()
    {
        $param = input();
        $ids = $param['ids'];
        $col = $param['col'];
        $val = $param['val'];
        
        if (! empty($ids) && in_array($col, [
            'parse_status',
            'status'
        ])) {
            $list = config($this->_pre);
            
            foreach ($list as $k => &$v) {
                $v[$col] = $val;
            }
            $res = mac_arr2file(APP_PATH . 'extra/' . $this->_pre . '.php', $list);
            if ($res === false) {
                return $this->error('保存失败，请重试!');
            }
            return $this->success('保存成功!');
        }
        return $this->error('参数错误');
    }
    
    /**
     * 消费记录
     * @return unknown
     */
    public function expenses_record()
    {
        $where = [];
        $res  = model('Group')->listData($where);
        $this->assign('group_list',$res['list']);
        
       
        $res  = model('Proxy')->listAllData($where);
        $this->assign('proxy_list',$res['list']);
        
        
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        
        // $where['status'] = array('neq',1);
        
        $order = 'er.id desc';
        
        
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        
       
        if(isset($param['group_id']) && $param['group_id']!=''){
            $where['group_id'] = array('eq',$param['group_id']);
        }
        
        if(isset($param['proxy_id']) && $param['proxy_id']!=''){
            $where['proxy_id'] = array('eq',$param['proxy_id']);
        }
        
        if(isset($param['proxy_pid']) && $param['proxy_pid']!=''){
            $where['proxy_pid'] = array('eq',$param['proxy_pid']);
        }
        
        //结束时间
        if(isset($param['end_date']) && $param['end_date']!=''){
            $is_endtime = true;
            $enttime = strtotime($param['end_date'] . ' 23:23:59');
        }else{
            $param['end_date'] = date('Y-m-d');
            $enttime = time();
        }
        
        //开始时间
        if(isset($param['begin_date']) && $param['begin_date']!=''){
            $starttime = strtotime($param['begin_date'] . ' 00:00:00');
        }else{
            if($is_endtime){
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month",$enttime));
                $starttime = strtotime("-3 month",$enttime);
            }else{
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month"));
                $starttime = strtotime("-3 month");
            }
        }
        
        $where['add_time'] = array('between', array($starttime,$enttime));
        
        $res = model('ExpensesRecord')->listData($where, $order, $param['page']);
        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);
        
        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param', $param);
        
        $this->assign('title', '会员消费记录');
        return $this->fetch('admin@proxy/expenses_record');
    }
    
    /**
     * 佣金
     * @return unknown
     */
    public function brokerage()
    {
        
        
        if (Request()->isPost()) {
            
            $param = input('post.');
            
            $use_status = $param['status'];
            if(!in_array($use_status, [1,2])){
                return $this->error('失败');
            }
            $id = intval($param['id']);
            if(empty($id)){
                return $this->error('不存在');
            }
            
            $data['id']= $id;
            $data['use_status']= $use_status;
            $data['use_type']= 2;
            $data['use_status']= $use_status;
            $data['use_time']= time();
            $data['admin_id']= $this->_admin['admin_id'];
            //更新对应状态，进行操作
            $res = model('Brokerage')->updateUseStatus($data);
            if ($res['code'] === 1) {
                return $this->success('保存成功!');
            }
            return $this->error($res['msg']);
            
        }
        
        
        $where = [];
               
        
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        
        
        $order = 'id desc';
        
        
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : $param['page'];
        
        if(isset($param['use_status']) && $param['use_status']!=''){
            $where['use_status'] = array('eq',$param['use_status']);
        }
        
        //派发方式( 1.系统自动  2.手动派发)
        if(isset($param['use_type']) && $param['use_type']!=''){
            $where['use_type'] = array('eq',$param['use_type']);
        }
        
        
        //结束时间
        if(isset($param['end_date']) && $param['end_date']!=''){
            $is_endtime = true;
            $enttime = strtotime($param['end_date'] . ' 23:23:59');
        }else{
            $param['end_date'] = date('Y-m-d');
            $enttime = time();
        }
        
        //开始时间
        if(isset($param['begin_date']) && $param['begin_date']!=''){
            $starttime = strtotime($param['begin_date'] . ' 00:00:00');
        }else{
            if($is_endtime){
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month",$enttime));
                $starttime = strtotime("-3 month",$enttime);
            }else{
                $param['begin_date'] = date('Y-m-d',strtotime("-3 month"));
                $starttime = strtotime("-3 month");
            }
        }
        
        $where['settle_time'] = array('between', array($starttime,$enttime));
        
        
        $res = model('Brokerage')->listData($where, $order, $param['page']);
        $this->assign('list', $res['list']);
        $this->assign('total', $res['total']);
        $this->assign('page', $res['page']);
        $this->assign('limit', $res['limit']);
        
        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param', $param);
        
        $this->assign('title', '佣金派发');
        return $this->fetch('admin@proxy/brokerage');
    }
}

