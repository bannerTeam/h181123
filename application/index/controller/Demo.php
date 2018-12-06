<?php
namespace app\index\controller;
use think\Controller;
use think\Config;
use think\Request;

class Demo extends Base
{
    public function __construct()
    {
        parent::__construct();
    }
    
    public function user(){
        $user_id = intval(cookie('user_id'));
        $user_name = cookie('user_name');
        $user_check = cookie('user_check');
        
        echo '===$user_id='.$user_id;
        echo '===$user_name='.$user_name;
        echo '===$user_check='.$user_check;
        
        $user = ['user_id'=>0,'user_name'=>'游客','user_portrait'=>'static/images/touxiang.png','group_id'=>1,'points'=>0];
        if(!empty($user_id) || !empty($user_name) || !empty($user_check)){
            echo '===$checkLogin=';
            $res = model('User')->checkLogin();
            var_dump($res);
            if($res['code'] == 1){
                $user = $res['info'];
            }
        }
        else{
            echo '===$else=';
            $group_list = model('Group')->getCache();
            $user['group'] = $group_list[1];
            var_dump($group_list[1]);
        }
        
        
    }
    
    public function index(){
        echo ("0"+1);
        exit;
        
        header("Cache-Control: no-store, no-cache, must-revalidate");//强制不缓存
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");//禁止本页被缓存
        
        $return_array = $this->forWindows();
        $temp_array = array();
        foreach ( $return_array as $value ){
            
            if (
                preg_match("/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i",$value,
                    $temp_array ) ){
                        $mac_addr = $temp_array[0];
                        break;
            }
            
        }
        unset($temp_array);
        echo $mac_addr;
        exit;
        
        $request = Request::instance();
        
        print_r($request);
                 
        echo $request->ip();
       
        exit();
        
        return $this->fetch('demo/index');
    }
    
    function forLinux(){exit;
        @exec("ifconfig -a", $this->return_array);
        return $this->return_array;
    } 
    
    public function forWindows(){exit;
        @exec("ipconfig /all", $this->return_array);
        if ( $this->return_array )
            return $this->return_array;
            else{
                $ipconfig = $_SERVER["WINDIR"]."\system32\ipconfig.exe";
                if ( is_file($ipconfig) )
                    @exec($ipconfig." /all", $this->return_array);
                    else
                        @exec($_SERVER["WINDIR"]."\system\ipconfig.exe /all", $this->return_array);
                        return $this->return_array;
            }
    } 
    
    
    //方法3：
    function getRealIp()
    {
        $ip=false;
        if(!empty($_SERVER["HTTP_CLIENT_IP"])){
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode (", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) { array_unshift($ips, $ip); $ip = FALSE; }
            for ($i = 0; $i < count($ips); $i++) {
                if (!eregi ("^(10│172.16│192.168).", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }
    
    
    /**
     * 获取真实IP
     * @param int $type
     * @param bool $client
     * @return mixed
     */
    function get_client_ip($type = 0,$client=true)
    {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($client){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // 防止IP伪造
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
        
    }
    
     public function ckplay()
    {
        return $this->fetch('demo/ckplay');
    }
    
    public function ckplay520()
    {
        return $this->fetch('demo/ckplay520');
    }
    
    public function ckplayer()
    {
        return $this->fetch('demo/ckplayer');
    }
    
    public function video()
    {
        return $this->fetch('demo/video');
    }
    
    public function collect_ds()
    {
        
        return $this->fetch('demo/collect_ds');
    }
    
    public function collect_union()
    {
        
        return $this->fetch('demo/collect_union');
    }
    
     public function submit()
    {
        
        return $this->fetch('demo/submit');
    }
    public function ajax(){
    	return $this->fetch('demo/ajax');
    }
    
    
    public function mail(){
             
        $to = 'lucaowan@outlook.com';
        $title = '测试邮件';
        $body = '平台开发测试邮件';
        
        $config = config('maccms.email');
        
        vendor('phpmailer.src.PHPMailer');       
        vendor('phpmailer.src.SMTP');
        $mail = new \PHPMailer\PHPMailer\PHPMailer();
        
        //$mail->SMTPDebug = 2;
        $mail->isSMTP();
        $mail->CharSet = "UTF-8";
        $mail->Host = $config['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $config['username'];
        $mail->Password = $config['password'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = $config['port'];
        $mail->setFrom($config['username']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $title;
        $mail->Body    = $body;
        //unset($config);
        $r = $mail->send();
        var_dump($r);
        exit;
    }
}
