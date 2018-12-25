<?php
namespace app\index\controller;
use think\Controller;

/**
 * 代理，三级分销
 * @author DEFAULT
 *
 */
class Proxy extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    
    /**
     * 代理，三级分销
     * @return mixed|string
     */
    public function index()
    {
        return $this->fetch('proxy/index');
    }
    
    /**
     * 申请
     * @return mixed|string
     */
    public function apply()
    {        
        if(request()->isAjax()){
            if (empty($GLOBALS['user']['user_id'])) {
                return $this->error('未登录', url('user/login'));
            }            
            $param = input();
            $param['user_id'] = $GLOBALS['user']['user_id'];
            $param['ip'] = request()->ip();
            $param['status'] = 0;
            $res = model('ProxyApply')->saveData($param);
            return json($res);
            
        }
        
        
        
        return $this->fetch('proxy/apply');
    }

    
    /**
     * 什么是三级分销，关于三级分销
     * @return mixed|string
     */
    public function about()
    {
       
        return $this->fetch('proxy/about');
    }
    
    
    /**
     * 申请结果，包括 进度，状态，申请信息
     * @return mixed|string
     */
    public function result()
    {
        
        if (empty($GLOBALS['user']['user_id'])) {
            return $this->error('未登录', url('user/login'));
        } 
        
        $pageTitle = '';
        $param = input();
        $id = $param['id'];
        if($id){
            //申请流程跳转
            $where['id'] = $param['id'];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            
            $res = model('ProxyApply')->findData($where);
            if($res['code'] == 1){
                $info = $res['info'];
                $this->assign('obj', $info);
                
                if(empty($info['status'])){
                    $pageTitle = '申请中';
                }else if($info['status'] == 1){
                    $pageTitle = '申请通过';
                }else if($info['status'] == 2){
                    $pageTitle = '申请失败';
                }
                
            }
        }
        
        $this->assign('pageTitle', $pageTitle);
        
        return $this->fetch('proxy/result');
    }
    
    
    /**
     * 推广链接 
     * @return mixed|string
     */
    public function popularize()
    {
        $user_id = $GLOBALS['user']['user_id'];
        
        $invite_code = $GLOBALS['user']['invite_code'];
        // 不存在推荐码，就生成
        if (empty($invite_code)) {
            $len = 6 - strlen($user_id);
            if ($len > 0) {
                $rand = $this->getInviteRand($len);
                $invite_code = $rand . $user_id;
            } else {
                $invite_code = $user_id;
            }
            $where['user_id'] = $user_id;
            model('User')->fieldData($where, 'invite_code', $invite_code);
        }
        
        $this->assign('invite', $invite_code);
        
        return $this->fetch('proxy/popularize');
    }
   
    /**
     * 佣金方案
     * @return mixed|string
     */
    public function brokerage ()
    {
        return $this->fetch('proxy/brokerage');
    }
    
    /**
     * 推广业绩
     * @return mixed|string
     */
    public function performance ()
    {         
        return $this->fetch('proxy/performance');
    }
        
    
    /**
     * 我的佣金
     * @return mixed|string
     */
    public function mybrokerage ()
    {
        if (empty($GLOBALS['user']['user_id'])) {
            return $this->error('未登录', url('user/login'));
        }
        
        if (Request()->isPost()) {
            
            $param = input('post.');
            $acount = $param['amount'];
            
            $res = model('ProxyWithdraw')->apply($GLOBALS['user']['user_id'],$acount);
            
            
            return json($res);
        }
        
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $res = model('Proxy')->findData($where,'amount');
        
        
        $info =  $res['info'];
        $this->assign('info',$info);
        
        
        return $this->fetch('proxy/mybrokerage');
    }
    
    /**
     * 佣金记录
     * @return mixed|string
     */
    public function brokerage_record ()
    {
        if (empty($GLOBALS['user']['user_id'])) {
            return $this->error('未登录', url('user/login'));
        }
        
        if (Request()->isAjax()) {
            
            $param = input();
            
            $page = intval($param['page']) ? 1 : $param['page'];
            
            $order = 'id desc';
            
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $res = model('ProxyWithdraw')->listData($where, $order, $page);
            $r = [
                'code' => 1,
                'msg' => '',
                'list' => $res['list'],
                'limit' => $res['limit'],
                'pagecount'=>$res['pagecount'],
                'page' => $res['page']
            ];
            
            return (json($r));
        }
        
        
        return $this->fetch('proxy/brokerage_record');
    }

}
