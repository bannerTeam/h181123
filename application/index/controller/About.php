<?php
namespace app\index\controller;
use think\Controller;

/**
 * 一些单页面
 * @author DEFAULT
 *
 */
class About extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 站点
     * @return mixed|string
     */
    public function website()
    {
        $this->assign('aboutWebsite',1);
        return $this->fetch('about/website');
    }

    
    /**
     * 合作
     * @return mixed|string
     */
    public function cooperation()
    {
        $this->assign('aboutCooperation',1);
        return $this->fetch('about/cooperation');
    }
    
    
    /**
     * 关于我们
     * @return mixed|string
     */
    public function about()
    {
        return $this->fetch('about/about');
    }
    
    
    /**
     * 意见反馈
     * @return mixed|string
     */
    public function feedback()
    {
        return $this->fetch('about/feedback');
    }
   
    /**
     * 加入VIP 介绍
     * @return mixed|string
     */
    public function vip()
    {
        
        $res = model('Vip')->listData();
        
        $this->assign('list',$res['list']);
        
        $this->assign('aboutVip',1);
        
        return $this->fetch('about/vip');
    }

}
