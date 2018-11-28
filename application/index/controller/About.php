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
        return $this->fetch('about/website');
    }

    
    /**
     * 合作
     * @return mixed|string
     */
    public function cooperation()
    {
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
   

}
