<?php
namespace app\index\controller;

use think\Controller;
use think\Cookie;
use think\Request;
use login\ThinkOauth;
use app\index\event\LoginEvent;
define('THIRD_LOGIN_CALLBACK', 'http://' . $_SERVER['SERVER_NAME'] . '/index.php/user/logincallback/type/');

class User extends Base
{

    public function __construct()
    {
        parent::__construct();
        
        // 判断用户登录状态
        $ac = request()->action();
        
        if (in_array($ac, [
            'index',
            'login',
            'logout',
            'ajax_login',
            'reg',
            'findpass',
            'oauth',
            'callback'
        ])) {
            $this->assign('vod_browse', 0);
            if (empty($GLOBALS['user']['user_id'])) {
                // 判断是否有cookie
                $browsevod = Cookie::get('browsevod', 'h18_');
                if ($browsevod) {
                    $this->assign('vod_browse', 1);
                }
                $this->assign('user_title', '游客，注册即享更多看片权益');
            } else {
                $this->assign('user_title', $GLOBALS['user']['user_name']);
                if ($GLOBALS['user']['vip_exp_time'] > time()) {
                    $this->assign('user_vip', true);
                    $this->assign('vip_date', date('Y-m-d', $GLOBALS['user']['vip_exp_time']));
                }
                $this->assign('vod_browse', $GLOBALS['user']['user_vod_browse']);
            }
        } else {
            
            if ($GLOBALS['user']['user_id'] < 1) {
                model('User')->logout();
                return $this->error('未登录', url('user/login'));
            }
            
            $this->assign('user_title', $GLOBALS['user']['user_name']);
            if ($GLOBALS['user']['vip_exp_time'] > time()) {
                $this->assign('user_vip', true);
                $this->assign('vip_date', date('Y-m-d', $GLOBALS['user']['vip_exp_time']));
            }
            
            /*
             * $res = model('User')->checkLogin();
             * if($res['code']>1){
             * model('User')->logout();
             * return $this->error($res['msg'], url('user/login'));
             * }
             */
            $this->assign('obj', $GLOBALS['user']);
        }
        
        $this->assign('pageAction', strtolower($ac));
        $this->assign('pageUser', true);
    }

    public function ajax_login()
    {
        return $this->fetch('user/ajax_login');
    }

    public function ajax_info()
    {
        return $this->fetch('user/ajax_info');
    }

    /**
     * yh:收藏
     */
    public function ajax_collection()
    {
        $param = input();
        if ($param['ac'] == 'set') {
            $data = [];
            $data['ulog_mid'] = 1;
            // 视频id
            $data['ulog_rid'] = intval($param['id']);
            $data['ulog_type'] = 2;
            $data['ulog_sid'] = 0;
            $data['ulog_nid'] = 0;
            $data['ulog_points'] = 0;
            $data['user_id'] = $GLOBALS['user']['user_id'];
            
            $res = model('Ulog')->infoData($data);
            if ($res['code'] == 1) {
                $res['code'] = 1;
                $res['msg'] = '已收藏';
                return json($res);
            }
            $res = model('Ulog')->saveData($data);
            return json($res);
        } else if ($param['ac'] == 'del') {
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $where['ulog_type'] = 2;            
            //记录id
            $where['ulog_id'] = intval($param['id']);
            $return = model('Ulog')->delData($where);
            return json($return);
        }else if ($param['ac'] == 'delr') {
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $where['ulog_type'] = 2;
            //视频ID
            $where['ulog_rid'] = intval($param['id']);
            $return = model('Ulog')->delData($where);
            return json($return);
        }else if ($param['ac'] == 'get') {
            
            if ($GLOBALS['user']['user_id'] > 0) {
                
                $where = [];
                // 判断用户是否已经操作
                $uwhere['user_id'] = $GLOBALS['user']['user_id'];
                // 视频id
                $uwhere['ulog_rid'] = intval($param['id']);
                $uwhere['ulog_type'] = 2;
                $res = model('Ulog')->infoData($uwhere, 'ulog_id');
                $this->add_browse_record($uwhere);
            } else {
                $res = [
                    'code' => 1001,
                    'msg' => '未登录'
                ];
            }
            
            return json($res);
        }
    }
    
    /**
     * yh:浏览记录
     */
    public function ajax_browse()
    {
        $param = input();
        if ($param['ac'] == 'del') {
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $where['ulog_type'] = 1;
            //记录id
            $where['ulog_id'] = intval($param['id']);
            $return = model('Ulog')->delData($where);
            return json($return);
        }else if ($param['ac'] == 'delr') {
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $where['ulog_type'] = 1;
            //视频ID
            $where['ulog_rid'] = intval($param['id']);
            $return = model('Ulog')->delData($where);
            return json($return);
        }
    }

    /**
     * 添加浏览记录
     *
     * @return \think\response\Json
     */
    private function add_browse_record($ulogData)
    {
        $data = [];
        $data['ulog_mid'] = 1;
        // 视频id
        $data['ulog_rid'] = $ulogData['ulog_rid'];
        $data['ulog_type'] = 1;
        $data['ulog_sid'] = 0;
        $data['ulog_nid'] = 0;
        $data['ulog_points'] = 0;
        $data['user_id'] = $ulogData['user_id'];
        
        $res = model('Ulog')->infoData($data);
        if ($res['code'] == 1) {
            $data['ulog_id'] = $res['info']['ulog_id'];
        }
        model('Ulog')->saveData($data);
        
        $w['vod_id'] = $ulogData['ulog_rid'];
        model('Vod')->updateSetInc($w, 'vod_hits', 1);
    }

    /**
     * yh: 顶数/顶数
     * type 包含1浏览;2收藏;3想看（顶）;4点播;5下载; 6.不想看（踩）
     */
    public function ajax_up_down()
    {
        $param = input();
        if ($param['ac'] == 'get') {
            
            $vod_id = intval($param['id']);
            
            $res['up'] = 0;
            $res['down'] = 0;
            
            // 判断用户是否已经操作
            $uwhere['user_id'] = $GLOBALS['user']['user_id'];
            $uwhere['ulog_rid'] = $vod_id;
            
            $uwhere['ulog_type'] = 3;
            $res1 = model('Ulog')->infoData($uwhere, 'ulog_id');
            if ($res1['code'] === 1) {
                $res['up'] = 1;
            }
            $uwhere['ulog_type'] = 6;
            $res2 = model('Ulog')->infoData($uwhere, 'ulog_id');
            if ($res2['code'] === 1) {
                $res['down'] = 1;
            }
            
            return json($res);
        } else if ($param['ac'] == 'set') {
            
            $res['code'] = 0;
            
            $vod_id = intval($param['id']);
            
            $type = $param['type'];
            if (! in_array($type, [
                3,
                6
            ])) {
                $res['msg'] = '参数错误';
                return json($res);
            }
            
            $vwhere['vod_id'] = $vod_id;
            $vres = model('Vod')->findData($vwhere, 'vod_up,vod_down');
            if ($vres['code'] > 1) {
                $res['msg'] = '视频不存在';
                return json($res);
            }
            
            $vod_info = $vres['info'];
            $vod_up = $vod_info['vod_up'];
            $vod_down = $vod_info['vod_down'];
            
            $data = [];
            $data['ulog_mid'] = 1;
            // 视频id
            $data['ulog_rid'] = $vod_id;
            $data['ulog_type'] = $type;
            $data['ulog_sid'] = 0;
            $data['ulog_nid'] = 0;
            $data['ulog_points'] = 0;
            $data['user_id'] = $GLOBALS['user']['user_id'];
            
            // 判断用户是否已经操作
            $uwhere['ulog_rid'] = $vod_id;
            $uwhere['ulog_type'] = $type;
            $uwhere['user_id'] = $GLOBALS['user']['user_id'];
            $res = model('Ulog')->infoData($uwhere);
            
            if ($res['code'] === 1) {
                $res['code'] = 2;
                $res['msg'] = '已有记录';
                return json($res);
            } else if ($res['code'] == 1002) {
                
                // 不存在数据 就行插入
                $ret2 = model('Ulog')->saveData($data);
                
                if ($type == 3) {
                    $col = 'vod_up';
                    $val = $vod_up + 1;
                } else {
                    $col = 'vod_down';
                    $val = $vod_down + 1;
                }
                // 更新视频数据
                model('Vod')->fieldData($vwhere, $col, $val);
                
                $res['code'] = 1;
                $res['msg'] = '成功';
                return json($res);
            }
            
            return json($res);
        }
    }

    public function ajax_ulog()
    {
        // type 包含1浏览;2收藏;3想看（顶）;4点播;5下载; 6.不想看（踩）
        $param = input();
        if ($param['ac'] == 'set') {
            $data = [];
            $data['ulog_mid'] = intval($param['mid']);
            $data['ulog_rid'] = intval($param['id']);
            $data['ulog_type'] = intval($param['type']);
            $data['ulog_sid'] = intval($param['sid']);
            $data['ulog_nid'] = intval($param['nid']);
            $data['user_id'] = $GLOBALS['user']['user_id'];
            
            if ($data['ulog_mid'] == 1 && $data['ulog_type'] > 3) {
                $where2 = [];
                $where2['vod_id'] = $data['ulog_rid'];
                $res = model('Vod')->infoData($where2);
                if ($res['code'] > 1) {
                    return $res;
                }
                $flag = $data['ulog_type'] == 4 ? 'play' : 'down';
                $data['ulog_points'] = $res['info']['vod_points_' . $flag];
            }
            $data['ulog_points'] = intval($data['ulog_points']);
            $res = model('Ulog')->infoData($data);
            if ($res['code'] == 1) {
                return json($res);
            }
            if ($data['ulog_points'] == 0) {
                $res = model('Ulog')->saveData($data);
            } else {
                $res = [
                    'code' => 2001,
                    'msg' => '收费收据需单独记录'
                ];
            }
        } else {
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
            $param['limit'] = intval($param['limit']) < 1 ? 10 : intval($param['limit']);
            $order = 'ulog_id desc';
            $res = model('Ulog')->listData($where, $order, $param['page'], $param['limit']);
        }
        return json($res);
    }

    public function ajax_buy_popedom()
    {
        $param = input();
        $data = [];
        $data['ulog_mid'] = 1;
        $data['ulog_rid'] = intval($param['id']);
        $data['ulog_sid'] = intval($param['sid']);
        $data['ulog_nid'] = intval($param['nid']);
        
        if (! in_array($param['type'], [
            '4',
            '5'
        ]) || empty($data['ulog_rid']) || empty($data['ulog_sid']) || empty($data['ulog_nid'])) {
            return json([
                'code' => 2001,
                'msg' => '参数错误'
            ]);
        }
        $data['ulog_type'] = $param['type'];
        $data['user_id'] = $GLOBALS['user']['user_id'];
        
        $where = [];
        $where['vod_id'] = $data['ulog_rid'];
        $res = model('Vod')->infoData($where);
        if ($res['code'] > 1) {
            return json([
                $res
            ]);
        }
        $col = 'vod_points_' . ($param['type'] == '4' ? 'play' : 'down');
        $data['ulog_points'] = intval($res['info'][$col]);
        
        $res = model('Ulog')->infoData($data);
        if ($res['code'] == 1) {
            return json([
                'code' => 1,
                'msg' => '您已经购买过此条数据，无需再次支付，请刷新页面重试'
            ]);
        }
        
        if ($data['ulog_points'] > $GLOBALS['user']['user_points']) {
            return json([
                'code' => 2002,
                'msg' => '对不起,查看此页面数据需要[' . $data['ulog_points'] . ']积分，您还剩下[' . $GLOBALS['user']['user_points'] . ']积分，请先充值！'
            ]);
        } else {
            $update = [];
            $update['user_points'] = $GLOBALS['user']['user_points'] - $data['ulog_points'];
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $res = model('User')->where($where)->update($update);
            if ($res === false) {
                return json([
                    'code' => 2003,
                    'msg' => '对不起,更新用户积分信息失败，请刷新重试！'
                ]);
            }
            $res = model('Ulog')->saveData($data);
            return json($res);
        }
    }

    public function index()
    {
        return $this->fetch('user/index');
    }

    public function login()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('User')->login($param);
            return json($res);
        }
        if (! empty(cookie('user_id') && ! empty(cookie('user_name')))) {
            return redirect('user/index');
        }
        return $this->fetch('user/login');
    }

    public function logout()
    {
        $res = model('User')->logout();
        
        if (request()->isAjax()) {
            return json($res);
        } else {
            return redirect('user/index');
        }
    }

    public function oauth($type = '')
    {
        empty($type) && $this->error('参数错误');
        // 加载ThinkOauth类并实例化一个对象
        $sns = ThinkOauth::getInstance($type);
        // 跳转到授权页面
        $this->redirect($sns->getRequestCodeURL());
    }

    // 授权回调地址
    public function logincallback($type = '', $code = '')
    {
        if (empty($type) || empty($code)) {
            return $this->error('参数错误');
        }
        // 加载ThinkOauth类并实例化一个对象
        $sns = ThinkOauth::getInstance($type);
        $extend = null;
        
        // 请妥善保管这里获取到的Token信息，方便以后API调用
        $token = $sns->getAccessToken($code, $extend);
        // 获取当前登录用户信息
        if (is_array($token)) {
            $loginEvent = new LoginEvent();
            $res = $loginEvent->$type($token);
            if ($res['code'] == 1) {
                
                $col = 'user_openid_' . $type;
                // 如果已登录,是否需要重新绑定
                $check = model('User')->checkLogin();
                if ($check['code'] == 1) {
                    
                    if ($check['info'][$col] == $res['info']['openid']) {
                        // 无需再次绑定
                        return json([
                            'code' => 1001,
                            'msg' => '已经绑定该账号'
                        ]);
                    } else {
                        // 解除原有绑定
                        $where = [];
                        $where[$col] = $res['info']['openid'];
                        $update = [];
                        $update[$col] = '';
                        model('User')->where($where)->update($update);
                        // 新绑定
                        $where = [];
                        $where['user_id'] = $GLOBALS['user']['user_id'];
                        $update = [];
                        $update[$col] = $res['info']['openid'];
                        model('User')->where($where)->update($update);
                        return json([
                            'code' => 1,
                            'msg' => '绑定成功'
                        ]);
                    }
                }
                
                $where = [];
                $where[$col] = $res['openid'];
                $res2 = model('User')->infoData($where);
                // 未绑定的需要先创建用户并绑定
                if ($res2['code'] > 1) {
                    $data = [];
                    $data['user_name'] = htmlspecialchars(urldecode(trim($res['info']['name'])));
                    $data['user_pwd'] = date('YmdH');
                    $data['user_pwd2'] = date('YmdH');
                    $reg = model('User')->register($data, 0);
                    if ($reg['code'] > 1) {
                        // 注册失败
                        return $this->error('同步信息注册失败，请联系管理员');
                    }
                }
                // 直接登录。。。
                $login = model('User')->login([
                    'col' => $col,
                    'openid' => $res['info']['openid']
                ]);
                if ($login['code'] > 1) {
                    return $this->error($login['msg']);
                }
                $this->redirect('user/index');
            } else {
                return $this->error($res['msg']);
            }
        } else {
            return $this->error('获取第三方用户信息失败，请重试');
        }
    }

    public function bindmsg()
    {
        $param = input();
        $res = model('User')->bindmsg($param);
        return json($res);
    }

    public function bind()
    {
        $param = input();
        if (Request()->isPost()) {
            $res = model('User')->bind($param);
            return json($res);
        }
        
        if (empty($param['ac'])) {
            $param['ac'] = 'email';
        }
        $this->assign('ac', $param['ac']);
        return $this->fetch('user/bind');
    }

    public function unbind()
    {
        $param = input();
        if (Request()->isPost()) {
            $res = model('User')->unbind($param);
            return json($res);
        }
        return $this->fetch('user/unbind');
    }

    public function info()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('User')->info($param);
            if ($res['code'] == 1) {
                $this->success($res['msg']);
                exit();
            }
            $this->error($res['msg']);
            exit();
        }
        return $this->fetch('user/info');
    }

    public function regcheck()
    {
        $param = input();
        $t = htmlspecialchars(urldecode(trim($param['t'])));
        $str = htmlspecialchars(urldecode(trim($param['str'])));
        $res = model('User')->regcheck($t, $str);
        if ($res['code'] > 1) {
            return $str;
        }
        return json($res);
    }

    public function reg()
    {
        if (Request()->isPost()) {
            
            $param = input();
            
            $param['inviter_user_code'] = '';
            
            // 判断是否有 邀请码 cookie
            $invite_code = Cookie::get('invite_code', 'h18_');
            
            if ($invite_code) {
                $param['inviter_user_code'] = $invite_code;
            }
            
            $res = model('User')->register($param);
            
            // 注册成功删除邀请码
            if ($res['code'] === 1) {
                Cookie::delete('invite_code', 'h18_');
            }
            
            return json($res);
        }
        
        // 邀请码
        $code = $_GET['code'];
        if (strlen($code) > 5 && strlen($code) < 10) {
            // 设置Cookie 有效期为 秒
            Cookie::set('invite_code', $code, [
                'prefix' => 'h18_'
            ]);
        }
        
        return $this->fetch('user/reg');
    }

    public function portrait()
    {
        if ($GLOBALS['config']['user']['portrait_status'] == 0) {
            return json([
                'code' => 0,
                'msg' => '未开启自定义头像功能！'
            ]);
        }
        
        $file = request()->file('file');
        if (empty($file)) {
            return json([
                'code' => 0,
                'msg' => '未找到上传的文件(原因：表单名可能错误，默认表单名“file”)！'
            ]);
        }
        if ($file->getMime() == 'text/x-php') {
            return json([
                'code' => 0,
                'msg' => '禁止上传php,html文件！'
            ]);
        }
        
        $upload_image_ext = 'jpg,png,gif';
        if ($file->checkExt($upload_image_ext)) {
            $type = 'image';
        } else {
            return json([
                'code' => 0,
                'msg' => '非系统允许的上传格式！'
            ]);
        }
        
        $uniq = $GLOBALS['user']['user_id'] % 10;
        // 上传附件路径
        $_upload_path = ROOT_PATH . 'upload' . '/user/' . $uniq . '/';
        // 附件访问路径
        $_save_path = 'upload' . '/user/' . $uniq . '/';
        $_save_name = $GLOBALS['user']['user_id'] . '.jpg';
        
        if (! file_exists($_save_path)) {
            mac_mkdirss($_save_path);
        }
        
        $upfile = $file->move($_upload_path, $_save_name);
        if (! is_file($_upload_path . $_save_name)) {
            return json([
                'code' => 0,
                'msg' => '文件上传失败！'
            ]);
        }
        $file = $_save_path . str_replace('\\', '/', $_save_name);
        $config = [
            'thumb_type' => 6,
            'thumb_size' => $GLOBALS['config']['user']['portrait_size']
        ];
        
        $new_thumb = $GLOBALS['user']['user_id'] . '.jpg';
        $new_file = $_save_path . $new_thumb;
        try {
            
            $image = \think\Image::open('./' . $file);
            $t_size = explode('x', strtolower($GLOBALS['config']['user']['portrait_size']));
            if (! isset($t_size[1])) {
                $t_size[1] = $t_size[0];
            }
            $res = $image->thumb($t_size[0], $t_size[1], 6)->save('./' . $new_file);
            
            $update = [];
            $update['user_portrait'] = $new_file;
            $where = [];
            $where['user_id'] = $GLOBALS['user']['user_id'];
            $res = model('User')->where($where)->update($update);
            if ($res === false) {
                return json([
                    'code' => 0,
                    'msg' => '更新会员头像信息失败！'
                ]);
            }
            return json([
                'code' => 1,
                'msg' => 'ok',
                'file' => 'http:' . MAC_PATH . $new_file . '?' . mt_rand(1, 9999)
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '生成缩放头像图片文件失败！'
            ]);
        }
    }

    public function findpass()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('User')->findpass($param);
            return json($res);
        }
        return $this->fetch('user/findpass');
    }

    public function buy()
    {
        $param = input();
        if (Request()->isPost()) {
            $flag = input('param.flag');
            if ($flag == 'card') {
                $card_no = htmlspecialchars(urldecode(trim($param['card_no'])));
                $card_pwd = htmlspecialchars(urldecode(trim($param['card_pwd'])));
                
                $res = model('Card')->useData($card_no, $card_pwd, $GLOBALS['user']);
                return json($res);
            } else {
                $price = input('param.price');
                if (empty($price)) {
                    return json([
                        'code' => 1001,
                        'msg' => '参数错误'
                    ]);
                }
                
                if ($price < $GLOBALS['config']['pay']['min']) {
                    return json([
                        'code' => 1002,
                        'msg' => '最小充值金额不能低于' . $GLOBALS['config']['pay']['min'] . '元'
                    ]);
                }
                
                $data = [];
                $data['user_id'] = $GLOBALS['user']['user_id'];
                $data['order_code'] = 'PAY' . mac_get_uniqid_code();
                $data['order_price'] = $price;
                $data['order_time'] = time();
                $data['order_points'] = intval($GLOBALS['config']['pay']['scale'] * $price);
                $res = model('Order')->saveData($data);
                $res['data'] = $data;
                return json($res);
            }
        }
        $this->assign('config', $GLOBALS['config']['pay']);
        return $this->fetch('user/buy');
    }

    public function pay()
    {
        $param = input();
        $order_code = htmlspecialchars(urldecode(trim($param['order_code'])));
        $where = [];
        $where['order_code'] = $order_code;
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $res = model('Order')->infoData($where);
        if ($res['code'] > 1) {
            return $this->error($res['msg']);
        }
        $this->assign('config', $GLOBALS['config']['pay']);
        $this->assign('info', $res['info']);
        return $this->fetch('user/pay');
    }

    public function gopay()
    {
        $param = input();
        
        $order_code = htmlspecialchars(urldecode(trim($param['order_code'])));
        $order_id = intval((trim($param['order_id'])));
        $payment = strtolower(htmlspecialchars(urldecode(trim($param['payment']))));
        
        if (empty($order_code) && empty($order_id) && empty($payment)) {
            return $this->error('参数错误');
        }
        
        if ($GLOBALS['config']['pay'][$payment]['appid'] == '') {
            return $this->error('该支付选项未开启');
        }
        
        // 核实订单
        $where['order_id'] = $order_id;
        $where['order_code'] = $order_code;
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $res = model('Order')->infoData($where);
        if ($res['code'] > 1) {
            return $this->error('获取单据失败');
        }
        // 跳转到相应页面
        model('Pay' . $payment)->submit($this->user, $res['info'], $param);
    }

    public function upgrade()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('User')->upgrade($param);
            return json($res);
        }
        
        $group_list = model('Group')->getCache();
        $this->assign('group_list', $group_list);
        
        return $this->fetch('user/upgrade');
    }

    public function popedom()
    {
        $type_tree = model('Type')->getCache('type_tree');
        $this->assign('type_tree', $type_tree);
        
        $n = 1;
        $ids = [
            1 => '列表页',
            2 => '内容页',
            3 => '播放页',
            4 => '下载页',
            '5' => '试看'
        ];
        foreach ($type_tree as $k1 => $v1) {
            unset($type_tree[$k1]['type_extend']);
            foreach ($ids as $a => $b) {
                $n ++;
                if ($v1['type_mid'] == 2 && $a > 2) {
                    break;
                }
                $type_tree[$k1]['popedom'][$b] = model('User')->popedom($v1['type_id'], $a, $GLOBALS['user']['group_id']);
            }
            foreach ($v1['child'] as $k2 => $v2) {
                unset($type_tree[$k1]['child'][$k2]['type_extend']);
                foreach ($ids as $a => $b) {
                    $n ++;
                    if ($v2['type_mid'] == 2 && $a > 2) {
                        break;
                    }
                    $type_tree[$k1]['child'][$k2]['popedom'][$b] = model('User')->popedom($v2['type_id'], $a, $GLOBALS['user']['group_id']);
                }
            }
        }
        
        $this->assign('type_tree', $type_tree);
        
        return $this->fetch('user/popedom');
    }

    public function plays()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['ulog_mid'] = 1;
        $where['ulog_type'] = 4;
        $order = 'ulog_id desc';
        $res = model('Ulog')->listData($where, $order, $param['page'], $param['limit']);
        
        $this->assign('list', $res['list']);
        $this->assign('title', '我的点播');
        $pages = mac_page_param($res['total'], $param['limit'], $param['page'], url('user/plays', [
            'page' => 'PAGELINK'
        ]));
        $this->assign('__PAGING__', $pages);
        return $this->fetch('user/plays');
    }

    public function downs()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['ulog_mid'] = 1;
        $where['ulog_type'] = 5;
        $order = 'ulog_id desc';
        $res = model('Ulog')->listData($where, $order, $param['page'], $param['limit']);
        
        $this->assign('list', $res['list']);
        $this->assign('title', '我的下载');
        $pages = mac_page_param($res['total'], $param['limit'], $param['page'], url('user/downs', [
            'page' => 'PAGELINK'
        ]));
        $this->assign('__PAGING__', $pages);
        return $this->fetch('user/downs');
    }

    /**
     * 收藏
     *
     * @return mixed|string
     */
    public function favs()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['ulog_type'] = 2;
        $order = 'ulog_id desc';
        $res = model('Ulog')->listData($where, $order, $param['page'], $param['limit']);
        
        $this->assign('list', $res['list']);
        
        $this->assign('title', '我的收藏');
        $pages = mac_page_param($res['total'], $param['limit'], $param['page'], url('user/favs', [
            'page' => 'PAGELINK'
        ]));
        $this->assign('__PAGING__', $pages);
        
        $this->assign('pageUser', 0);
        $this->assign('pageFavs', 1);
        
        return $this->fetch('user/favs');
    }

    /**
     * 观看记录。浏览历史
     *
     * @return mixed|string
     */
    public function browse()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['ulog_type'] = 1;
        $order = 'ulog_time desc';
        $res = model('Ulog')->listData($where, $order, $param['page'], $param['limit']);
        
        $this->assign('list', $res['list']);
        
        $this->assign('title', '观看记录');
        $pages = mac_page_param($res['total'], $param['limit'], $param['page'], url('user/favs', [
            'page' => 'PAGELINK'
        ]));
        $this->assign('__PAGING__', $pages);
        
        $this->assign('pageUser', 0);
        $this->assign('pageBrowse', 1);
        
        return $this->fetch('user/browse');
    }

    public function ulog_del()
    {
        $param = input();
        $ids = $param['ids'];
        $type = $param['type'];
        $all = $param['all'];
        
        if (! in_array($type, array(
            '1',
            '2',
            '3',
            '4',
            '5'
        ))) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        
        if (empty($ids) && empty($all)) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        
        $arr = [];
        $ids = explode(',', $ids);
        foreach ($ids as $k => $v) {
            $v = intval(abs($v));
            $arr[$v] = $v;
        }
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['ulog_type'] = $type;
        if ($all != '1') {
            $where['ulog_id'] = array(
                'in',
                implode(',', $arr)
            );
        }
        $return = model('Ulog')->delData($where);
        return json($return);
    }

    public function orders()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['o.user_id'] = $GLOBALS['user']['user_id'];
        
        $order = 'o.order_id desc';
        $res = model('Order')->listData($where, $order, $param['page'], $param['limit']);
        
        $pages = mac_page_param($res['total'], $param['limit'], $param['page'], url('user/orders', [
            'page' => 'PAGELINK'
        ]));
        $this->assign('__PAGING__', $pages);
        
        $this->assign('list', $res['list']);
        return $this->fetch('user/orders');
    }

    public function cards()
    {
        $param = input();
        $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['card_use_status'] = 1;
        
        $order = 'card_id desc';
        $res = model('Card')->listData($where, $order, $param['page'], $param['limit']);
        
        $pages = mac_page_param($res['total'], $param['limit'], $param['page'], url('user/cards', [
            'page' => 'PAGELINK'
        ]));
        $this->assign('__PAGING__', $pages);
        
        $this->assign('list', $res['list']);
        return $this->fetch('user/cards');
    }

    /**
     * 升级VIP
     */
    public function vip()
    {
        $res = model('Vip')->listData();
        
        $this->assign('list', $res['list']);
        
        return $this->fetch('user/vip');
    }

    /**
     * 选择支付
     */
    public function vip_payment()
    {
        $param = input();
        $id = $param['id'];
        
        $where['id'] = $id;
        $rs = model('Vip')->findData($where);
        if ($rs['code'] === 1) {
            $this->assign('vipinfo', $rs['info']);
        }
        
        return $this->fetch('user/vip_payment');
    }

    /**
     * 卡密充值
     */
    public function kalman()
    {
        if (Request()->isPost()) {
            $param = input();
            
            $card_no = htmlspecialchars(urldecode(trim($param['card_no'])));
            $card_pwd = htmlspecialchars(urldecode(trim($param['card_pwd'])));
            
            $res = model('Card')->useData($card_no, $card_pwd, $GLOBALS['user']);
            return json($res);
        }
        
        return $this->fetch('user/kalman');
    }

    /**
     * 修改密码
     */
    public function password()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('User')->changepass($GLOBALS['user']['user_id'], $param);
            return json($res);
        }
        return $this->fetch('user/password');
    }

    /**
     * 安全中心
     */
    public function security()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = model('User')->changepass($GLOBALS['user']['user_id'], $param);
            return json($res);
        }
        return $this->fetch('user/security');
    }

    /**
     * 绑定修改邮箱
     */
    public function email()
    {
        if (Request()->isPost()) {
            $param = input();
            $res = ['code'=>1003,'msg'=>'邮箱发送失败!'];
            if ($param['ac'] == 'send') {
                $email = $param['email'];               
                if(mac_check_email($email)){
                    
                    $site_name = $GLOBALS['config']['site']['site_name'];
                    
                    $code = mac_get_rndstr(8);
                    
                    $data['user_id'] = $GLOBALS['user']['user_id'];
                    $data['email'] = $email;
                    $data['code'] = $code;
                    // 30分钟过期
                    $data['exp_time'] = time() + (60 * 30);
                    // 1.找回密码 2.绑定邮箱 3.修改邮箱
                    $data['type'] = 2;
                    $res = model('SendEmail')->saveData($data);
                    if($res['code'] === 1){
                        $to = $email;
                        $title = '帐户邮箱绑定';
                        $body = '您好!<p>欢迎绑定邮箱!</p>您的验证码是:' . $code;
                        $result = mac_send_mail($to, $title, $body);
                        if($result === false){
                            $res = ['code'=>1002,'msg'=>'邮箱发送失败'];
                        }else{
                            $res = ['code'=>1,'msg'=>'保存成功'];
                        }
                    }
                }else{
                    $res = ['code'=>1005,'msg'=>'邮箱格式错误'];
                }
               
                
                return json($res);
            }
            
           return json($res);
        }
        
       
        
        return $this->fetch('user/email');
    }

    /**
     * 生成邀请码
     */
    public function share()
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
        
        return $this->fetch('user/share');
    }

       
   
    /**
     * 消息
     *
     * @return mixed|string
     */
    public function news()
    {       
        
        if (request()->isAjax()) {
            
            $param = input();
            
            if(isset($param['ac']) && $param['ac']=='already'){
                
                $id = intval($param['id']);
                if(empty($id)){
                    $res['code'] = '1001';
                    $res['msg'] = '不存在';
                    return json($res);
                }
                $where['id'] = $id;
                $where['receive_user_id'] = $GLOBALS['user']['user_id'];
                
                $data['id'] = $id;
                $data['status'] = 1;
                
                $res = model('News')->saveData($data, $where);
                
                return json($res);
            }
            
            $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
            $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
            
            $where = [];
           
            $where['receive_user_id'] = $GLOBALS['user']['user_id'];
            
            $order= 'send_time desc';
            
            $res = model('News')->listData($where, $order, $param['page'], $param['limit']);
            
            return json($res);
        } else {
            return $this->fetch('user/news');
        }
        
    }
    
    /**
     * 公告
     *
     * @return mixed|string
     */
    public function notice()
    {
        
        if (request()->isAjax()) {
            
            $param = input();
            $param['page'] = intval($param['page']) < 1 ? 1 : intval($param['page']);
            $param['limit'] = intval($param['limit']) < 1 ? 20 : intval($param['limit']);
            
            $where = [];
            $where['send_time'] = ['<',time()];
            
            $order= 'status desc,send_time desc';
            
            $res = model('Notice')->listData($where, $order, $param['page'], $param['limit']);
                        
            return json($res);
        } else {
            return $this->fetch('user/notice');
        }
        
        
        
    }
    
    /**
     * 获取邀请随机数
     */
    private function getInviteRand($len)
    {
        $codeSet = '2345678abcdefhijkmnpqrstuvwxyz';
        $code = '';
        for ($i = 0; $i < $len; $i ++) {
            $code = $code . $codeSet[mt_rand(0, 29)];
        }
        return $code;
    }
}
