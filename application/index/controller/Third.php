<?php
namespace app\index\controller;
use think\Controller;


class Third extends Base
{
   
    /**
     * 跳转第三方
     * @return \think\response\Json
     */
    public function jump()
    {
        $param = input();
        $url = $param['url'];
        if(empty($url)){
            echo 'error';
            exit();
        }
        
        header('Location: http://www.lsj8.xyz/luoboka?url='.$url);
        exit();
    }
    
    

}
