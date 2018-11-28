<?php
namespace app\index\controller;
use think\Model;
use think\Db;


class Index extends Base
{
    public function index()
    {  
        
        
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
