<?php
namespace app\index\controller;

use think\Controller;
use think\Request;
use think\Cookie;

class Vod extends Base
{

    public function __construct()
    {
        parent::__construct();
    }

    private function assign_param()
    {
        $param = mac_param_url();
        $by = $param['by'];
        if (empty($by)) {
            $by = 'time';
        }
        $this->assign('sort', $by);
        
        $timeadd = $param['timeadd'];
        if (empty($by)) {
            $timeadd = 0;
        }
        
        $typeid = $param['id'];
        if (empty($typeid)) {
            $type = '';
        }
        $this->assign('typeid', $typeid);
        
        $wd = $param['wd'];
        if (empty($wd)) {
            $wd = '';
        }
        $this->assign('wd', $wd);
        
        $this->assign('timeadd', $timeadd);
        
        return $param;
    }

    public function index()
    {
        $param = $this->assign_param();
        
        $this->assign('vodindex', '1');
        
        if ($param['timeadd'] == 1) {
            $this->assign('title', '今日更新');
        } else if ($param['timeadd'] == 7) {
            $this->assign('title', '发现');
        }
                
        
        return $this->fetch('vod/index');
    }

    public function type()
    {
        
        $this->assign_param();
        
        $info = $this->label_type();
        
        $this->assign('title', $info['type_name']);
        
        if(isset($info['parent'])){
            if($info['parent']['childids']){
                $this->assign('childids', $info['parent']['childids']);
                $this->assign('typeAll', $info['parent']['type_id']);
            }
        }else{
            $this->assign('childids', $info['childids']);            
            $this->assign('typeAll', $info['type_id']);
            $this->assign('typeParent', 1);
            
        }
        
        
        return $this->fetch(mac_tpl_fetch('vod', $info['type_tpl'], 'type'));
    }

    public function show()
    {
        $info = $this->label_type();
        
        $this->assign('obj', $info);
        
        $tpl = 'vod/show';
        
        return $this->fetch($tpl);
    }

    public function ajax_show()
    {
        $info = $this->label_type();
        return $this->fetch('vod/ajax_show');
    }

    public function search()
    {
        $param = $this->assign_param();
        
        $this->check_search($param);
        $this->assign('param', $param);
        
        $this->assign('title', $param['wd']);
        
        return $this->fetch('vod/search');
    }

    public function detail()
    {
        $info = $this->label_vod_detail();
        return $this->fetch(mac_tpl_fetch('vod', $info['vod_tpl'], 'detail'));
        
        $info = $this->label_vod_play('play');
        
        return $this->fetch(mac_tpl_fetch('vod', $info['vod_tpl_play'], 'play'));
    }

    public function ajax_detail()
    {
        $info = $this->label_vod_detail();
        return $this->fetch('vod/ajax_detail');
    }

    public function role()
    {
        $info = $this->label_vod_role();
        return $this->fetch('vod/role');
    }

    public function play()
    {
       
        $this->assign('videoplay', true);
        $info = $this->label_vod_play('play');
        
        return $this->fetch(mac_tpl_fetch('vod', $info['vod_tpl_play'], 'play'));
    }

    public function down()
    {
        $info = $this->label_vod_play('down');
        return $this->fetch(mac_tpl_fetch('vod', $info['vod_tpl_down'], 'down'));
    }

    public function player()
    {
        $info = $this->label_vod_play('play', [], 0, 1);
        return $this->fetch();
    }

    public function rss()
    {
        $info = $this->label_vod_detail();
        return $this->fetch('vod/rss');
    }

    /**
     * 获取影片 的 上一部，下一部
     */
    public function ajax_front_after()
    {
        if (Request::instance()->isAjax()) {
            
            $res['code'] = 0;
            $res['front'] = '';
            $res['after'] = '';
            
            $input = input();
            
            $vod_id = intval($input['id']);
            
            // 获取上一条
            $where['vod_id'] = [
                '<',
                $vod_id
            ];
            $front = model('Vod')->findData($where, 'vod_id');
            if ($front['code'] === 1) {
                $res['front'] = $front['info']['vod_id'];
            }
            
            // 获取下一条
            $where['vod_id'] = [
                '>',
                $vod_id
            ];
            $after = model('Vod')->findData($where, 'vod_id', 'vod_id asc');
            if ($after['code'] === 1) {
                $res['after'] = $after['info']['vod_id'];
            }
            
            return json($res);
        }
    }

    /**
     * 设置视频浏览
     * @return \think\response\Json
     */
    public function ajax_browse()
    {
        
        $request = Request::instance();
        $res['code'] = 0;
        if (! $request->isAjax()) {
            return json($res);
        }
        
        $param = $request->param();        
        // 视频ID
        $vod_id = intval($param['id']);        
        if(empty($vod_id)){
            return json($res);
        }
                
        // 當前登錄用戶id
        $user_id = $GLOBALS['user']['user_id'];
        if (empty($user_id)) {
            $user_id = 0;
        }else{
            return $this->set_user_vod_browse();
        }
        
        // 判断是否有cookie
        $browsevod = Cookie::get('browsevod', 'h18_');
        if ($browsevod && empty($user_id)) {
            return json($res);
        }
       
        
        $ip = $request->ip();
        
        // 获得时间戳
        $now_time = time();
        
        // 获得当日凌晨的时间戳
        $today = strtotime(date("Y-m-d"), $now_time);
        
        // 獲取距離今天結束的秒
        $diff = $today + 60 * 60 * 24 - $now_time;
                
        // 设置Cookie 有效期为 秒
        Cookie::set('browsevod', 1, [
            'prefix' => 'h18_',
            'expire' => $diff
        ]);
        
        //插入浏览记录
        $data['vod_id'] = $vod_id;
        $data['ip'] = $ip;
        $data['user_id'] = $user_id;
        model('VodBrowse')->saveData($data);
        
        // 查询 当前IP ,今天浏览的次数
        $where['add_time'] = [
            '>',
            $today
        ];
        $where['ip'] = $ip;
        $count = model('VodBrowse')->countData($where);
        if ($count > 10) {
            $res['code'] = - 1;
            $res['msg'] = 'IP访问超过上限';
            return json($res);
        }
        
        $res['code'] = 1;
        return json($res);
    }

    /**
     * 设置会员的浏览次数
     */
    private function set_user_vod_browse()
    {
        // 當前登錄用戶id
        $user_id = $GLOBALS['user']['user_id'];
        $where['user_id'] = $user_id;
        //查询当前会员的浏览次数
        $rd = model('User')->infoData($where,'user_id,user_vod_browse,user_vod_lasttime,vip_start_time,vip_exp_time');
               
        $user = $rd['info'];
        
        //判断 如果是 vip 没过期就没有限制 
        if($user['vip_exp_time'] > time()){
            $res['code'] = 1;
            
            return json($res);
        }
        
        // 判断是否有cookie
        $browsevod = Cookie::get('browsevodu'.$user_id, 'h18_');
        if ($browsevod) {
            $res['code'] = -3;
            return json($res);
        }
        
        //设置普通会员只能看2部
        $max_number = 2;
        
        // 获得时间戳
        $now_time = time();
        
        // 获得当日凌晨的时间戳
        $today = strtotime(date("Y-m-d"), $now_time);
        
        // 獲取距離今天結束的秒
        $diff = $today + 60 * 60 * 24 - $now_time;
        
                
        //判断最后看片的时间是否是今天
        if(intval($user['user_vod_lasttime']) > $today){
            
            if(intval($user['user_vod_browse']) >= $max_number ){
                $res['code'] = -2;
                $res['msg'] = '每日观看已达上限,请升级VIP或进行推广';
                
                // 设置Cookie 有效期为 秒
                Cookie::set('browsevodu'.$user_id, 1, [
                    'prefix' => 'h18_',
                    'expire' => $diff
                ]);
                
                return json($res);
            }
            
            //观看次数
            $sData['user_vod_browse'] = $user['user_vod_browse'] + 1;
            //最后观看时间
            $sData['user_vod_lasttime']= time();
            //更新数据库
            model('User')->fieldDatas($where, $sData);            
        }else{
            //观看次数
            $sData['user_vod_browse'] = 1;
            //最后观看时间
            $sData['user_vod_lasttime']= time();
            //更新数据库
            model('User')->fieldDatas($where, $sData);
            
        }
        $res['code'] = 1;
        return json($res);
        
    }
}
