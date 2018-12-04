<?php
namespace app\index\controller;
use think\Model;
use think\Db;


class Index extends Base
{
    public function index()
    {  
        
        //判断手机访问直接跳转 分类页面
        $ua = strtolower($_SERVER['HTTP_USER_AGENT']);
        $uachar = "/(nokia|sony|ericsson|mot|samsung|sgh|lg|philips|panasonic|alcatel|lenovo|meizu|cldc|midp|iphone|wap|mobile|android)/i";
        if((preg_match($uachar, $ua))) {
            $this->redirect('/vod/type/id/23');
            exit;
        }
        
        $this->assign('typeAll', 23);
        $this->assign('typeParent', 1);        
        $this->assign('vod_type',23);
        
        $this->assign('pageHome',true);
        
        
        return $this->fetch( 'index/index');
    }
    

    public function wap_index()
    {
        $param = mac_param_url();
        return $this->fetch( 'index/index');
    }

}
