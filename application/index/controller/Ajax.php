<?php
namespace app\index\controller;

use think\Controller;
use think\Config;

class Ajax extends Base
{

    var $_param;

    public function __construct()
    {
        parent::__construct();
        
        $this->_param = mac_param_url();
    }

    public function index()
    {}

    // 加载最多不超过20页数据，防止非法采集。每页条数可以是10,20,30
    public function data()
    {
        $mid = $this->_param['mid'];
        $limit = $this->_param['limit'];
        $page = $this->_param['page'];
        $type_id = $this->_param['tid'];
        if (! in_array($mid, [
            '1',
            '2',
            '3'
        ])) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        if (! in_array($limit, [
            '8',
            '10',
            '20',
            '30'
        ])) {
            $limit = 10;
        }
        if ($page < 1 || $page > 20) {
            $page = 1;
        }
        
        $mids = [
            1 => 'vod',
            2 => 'art',
            3 => 'topic'
        ];
        $pre = $mids[$mid];
        $order = $pre . '_time desc';
        $where = [];
        $where[$pre . '_status'] = [
            'eq',
            1
        ];
        if (! empty($type_id)) {
            if (in_array($mid, [
                '1',
                '2'
            ])) {
                $type_list = model('Type')->getCache('type_list');
                $type_info = $type_list[$type_id];
                if (! empty($type_info)) {
                    $ids = $type_info['type_pid'] == 0 ? $type_info['childids'] : $type_info['type_id'];
                    $where['type_id'] = [
                        'in',
                        $ids
                    ];
                }
            }
        }
        $field = '*';
        $res = model($pre)->listData($where, $order, $page, $limit, 0, $field);
        if ($res['code'] == 1) {
            foreach ($res['list'] as $k => &$v) {
                unset($v[$pre . '_time_hits'], $v[$pre . '_time_make']);
                $v[$pre . '_time'] = date('Y-m-d H:i:s', $v[$pre . '_time']);
                $v[$pre . '_time_add'] = date('Y-m-d H:i:s', $v[$pre . '_time_add']);
                if ($mid == '1') {
                    unset($v['vod_play_from'], $v['vod_play_server'], $v['vod_play_note'], $v['vod_play_url']);
                    unset($v['vod_down_from'], $v['vod_down_server'], $v['vod_down_note'], $v['vod_down_url']);
                    
                    $v['detail_link'] = mac_url_vod_detail($v);
                } elseif ($mid == '2') {
                    $v['detail_link'] = mac_url_art_detail($v);
                } elseif ($mid == '3') {
                    $v['detail_link'] = mac_url_topic_detail($v);
                }
                $v[$pre . '_pic'] = mac_url_img($v[$pre . '_pic']);
                $v[$pre . '_pic_thumb'] = mac_url_img($v[$pre . '_pic_thumb']);
                $v[$pre . '_pic_slide'] = mac_url_img($v[$pre . '_pic_slide']);
            }
        }
        return json($res);
    }

    public function suggest()
    {
        $mid = $this->_param['mid'];
        $wd = $this->_param['wd'];
        if (empty($wd) || ! in_array($mid, [
            '1',
            '2',
            '3'
        ])) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        $mids = [
            1 => 'vod',
            2 => 'art',
            3 => 'topic'
        ];
        $pre = $mids[$mid];
        
        $where = [];
        $where[$pre . '_name|' . $pre . '_en'] = [
            'like',
            '%' . $wd . '%'
        ];
        $order = $pre . '_id desc';
        $field = $pre . '_id as id,' . $pre . '_name as name,' . $pre . '_en as en,' . $pre . '_pic as pic';
        
        $url = mac_url_search([
            'wd' => 'mac_wd'
        ], $pre);
        
        $res = model($pre)->listData($where, $order, 1, 20, 0, $field);
        if ($res['code'] == 1) {
            foreach ($res['list'] as $k => $v) {
                $res['list'][$k]['pic'] = mac_url_img($v['pic']);
            }
        }
        $res['url'] = $url;
        return json($res);
    }

    public function desktop()
    {
        $name = $this->_param['name'];
        $url = $this->_param['url'];
        
        $config = config('maccms.site');
        if (empty($name)) {
            $name = $config['site_name'];
            $url = "http://" . $config['site_url'];
        }
        if (substr($url, 0, 4) != "http") {
            $url = "http://" . $url;
        }
        $Shortcut = "[InternetShortcut]
        URL=" . $url . "
        IDList=
        IconIndex=1
        [{000214A0-0000-0000-C000-000000000046}]
        Prop3=19,2";
        header("Content-type: application/octet-stream");
        if (strpos($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
            header("Content-Disposition: attachment; filename=" . urlencode($name) . ".url;");
        } else {
            header("Content-Disposition: attachment; filename=" . $name . ".url;");
        }
        echo $Shortcut;
    }

    public function hits()
    {
        $id = $this->_param['id'];
        $mid = $this->_param['mid'];
        $type = $this->_param['type'];
        if (empty($id) || ! in_array($mid, [
            '1',
            '2',
            '3'
        ])) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        $mids = [
            1 => 'vod',
            2 => 'art',
            3 => 'topic',
            4 => 'actor',
            5 => 'role'
        ];
        $pre = $mids[$mid];
        $where = [];
        $where[$pre . '_id'] = $id;
        $field = $pre . '_hits,' . $pre . '_hits_day,' . $pre . '_hits_week,' . $pre . '_hits_month,' . $pre . '_time_hits';
        $model = model($pre);
        
        $res = $model->infoData($where, $field);
        if ($res['code'] > 1) {
            return json($res);
        }
        $info = $res['info'];
        
        if ($type == 'update') {
            // 初始化值
            $update[$pre . '_hits'] = $info[$pre . '_hits'];
            $update[$pre . '_hits_day'] = $info[$pre . '_hits_day'];
            $update[$pre . '_hits_week'] = $info[$pre . '_hits_week'];
            $update[$pre . '_hits_month'] = $info[$pre . '_hits_month'];
            $new = getdate();
            $old = getdate($info[$pre . '_time_hits']);
            // 月
            if ($new['year'] == $old['year'] && $new['mon'] == $old['mon']) {
                $update[$pre . '_hits_month'] ++;
            } else {
                $update[$pre . '_hits_month'] = 1;
            }
            // 周
            $weekStart = mktime(0, 0, 0, $new["mon"], $new["mday"], $new["year"]) - ($new["wday"] * 86400);
            $weekEnd = mktime(23, 59, 59, $new["mon"], $new["mday"], $new["year"]) + ((6 - $new["wday"]) * 86400);
            if ($info[$pre . '_time_hits'] >= $weekStart && $info[$pre . '_time_hits'] <= $weekEnd) {
                $update[$pre . '_hits_week'] ++;
            } else {
                $update[$pre . '_hits_week'] = 1;
            }
            // 日
            if ($new['year'] == $old['year'] && $new['mon'] == $old['mon'] && $new['mday'] == $old['mday']) {
                $update[$pre . '_hits_day'] ++;
            } else {
                $update[$pre . '_hits_day'] = 1;
            }
            // 更新数据库
            $update[$pre . '_hits'] = $update[$pre . '_hits'] + 1;
            $update[$pre . '_time_hits'] = time();
            $model->where($where)->update($update);
            
            $data['hits'] = $update[$pre . '_hits'];
            $data['hits_day'] = $update[$pre . '_hits_day'];
            $data['hits_week'] = $update[$pre . '_hits_week'];
            $data['hits_month'] = $update[$pre . '_hits_month'];
        } else {
            $data['hits'] = $info[$pre . '_hits'];
            $data['hits_day'] = $info[$pre . 'hits_day'];
            $data['_hits_week'] = $info[$pre . '_hits_week'];
            $data['hits_month'] = $info[$pre . 'hits_month'];
        }
        return json([
            'code' => 1,
            'msg' => '操作成功！',
            'data' => $data
        ]);
    }

    public function digg()
    {
        $id = $this->_param['id'];
        $mid = $this->_param['mid'];
        $type = $this->_param['type'];
        
        if (empty($id) || ! in_array($mid, [
            '1',
            '2',
            '3',
            '4'
        ])) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        
        $mids = [
            1 => 'vod',
            2 => 'art',
            3 => 'topic',
            4 => 'actor',
            5 => 'role',
            6 => 'comment'
        ];
        $pre = $mids[$mid];
        $where = [];
        $where[$pre . '_id'] = $id;
        $field = $pre . '_up,' . $pre . '_down';
        $model = model($pre);
        
        if ($type) {
            $cookie = $pre . '-digg-' . $id;
            if (! empty(cookie($cookie))) {
                return json([
                    'code' => 1002,
                    'msg' => '您已参与过了'
                ]);
            }
            if ($type == 'up') {
                $model->where($where)->setInc($pre . '_up');
                cookie($cookie, 't', 30);
            } elseif ($type == 'down') {
                $model->where($where)->setInc($pre . '_down');
                cookie($cookie, 't', 30);
            }
        }
        
        $res = $model->infoData($where, $field);
        if ($res['code'] > 1) {
            return json($res);
        }
        $info = $res['info'];
        if ($info) {
            $data['up'] = $info[$pre . '_up'];
            $data['down'] = $info[$pre . '_down'];
        } else {
            $data['up'] = 0;
            $data['down'] = 0;
        }
        return json([
            'code' => 1,
            'msg' => '操作成功！',
            'data' => $data
        ]);
    }

    public function score()
    {
        $id = $this->_param['id'];
        $mid = $this->_param['mid'];
        $score = $this->_param['score'];
        
        if (empty($id) || ! in_array($mid, [
            '1',
            '2',
            '3'
        ])) {
            return json([
                'code' => 1001,
                'msg' => '参数错误'
            ]);
        }
        
        $mids = [
            1 => 'vod',
            2 => 'art',
            3 => 'topic',
            4 => 'actor',
            5 => 'role'
        ];
        $pre = $mids[$mid];
        $where = [];
        $where[$pre . '_id'] = $id;
        $field = $pre . '_score,' . $pre . '_score_num,' . $pre . '_score_all';
        $model = model($pre);
        
        $res = $model->infoData($where, $field);
        if ($res['code'] > 1) {
            return json($res);
        }
        $info = $res['info'];
        
        if ($info) {
            if ($score) {
                $cookie = $pre . '-score-' . $id;
                if (! empty(cookie($cookie))) {
                    return json([
                        'code' => 1002,
                        'msg' => '您已评分'
                    ]);
                }
                $update = [];
                $update[$pre . '_score_num'] = $info[$pre . '_score_num'] + 1;
                $update[$pre . '_score_all'] = $info[$pre . '_score_all'] + $score;
                $update[$pre . '_score'] = number_format($update[$pre . '_score_all'] / $update[$pre . '_score_num'], 1, '.', '');
                $model->where($where)->update($update);
                
                $data['score'] = $update[$pre . '_score'];
                $data['score_num'] = $update[$pre . '_score_num'];
                $data['score_all'] = $update[$pre . '_score_all'];
                
                cookie($cookie, 't', 30);
            } else {
                $data['score'] = $info[$pre . '_score'];
                $data['score_num'] = $info[$pre . '_score_num'];
                $data['score_all'] = $info[$pre . '_score_all'];
            }
        } else {
            $data['score'] = 0.0;
            $data['score_num'] = 0;
            $data['score_all'] = 0;
        }
        return json([
            'code' => 1,
            'msg' => '感谢您的参与，评分成功！',
            'data' => $data
        ]);
    }

    public function get_vod_more()
    {
        
        // 返回 格式 默认 数据html
        $format = $_GET['format'];
        
        // 页码
        $page = $this->_param['page'];
        if (empty($page)) {
            $page = 1;
        }
        
        // 分类id
        $type = $this->_param['tid'];
        // 每页显示条数
        $limit = $this->_param['limit'];
        if (empty($limit)) {
            $limit = 10;
        }
        
        // 搜索关键字
        $wd = $this->_param['wd'];
        
        // 排序字段
        $by = $this->_param['by'];
        
        // 时间范围
        $timeadd = $this->_param['timeadd'];
        /**
         * 时间范围检索 单位 天
         */
        if (! empty($timeadd)) {
            $start_time = strtotime('-' . $timeadd . ' day');
            $where['vod_time_add'] = [
                'gt',
                $start_time
            ];
        }
        
        // 是否统计
        $is_total = $_GET['total'];
        
        $by_arr = [
            'time',
            'hits',
            'score',
            'up',
            'hits_week'
        ];
        
        if (! in_array($by, $by_arr)) {
            $by = 'time';
        }
        
        $order = 'vod_' . $by . ' desc';
        
        $tmp_arr = explode(',', $type);
        $type_list = model('Type')->getCache('type_list');
        $type = [];
        foreach ($type_list as $k2 => $v2) {
            if (in_array($v2['type_id'] . '', $tmp_arr) || in_array($v2['type_pid'] . '', $tmp_arr)) {
                $type[] = $v2['type_id'];
            }
        }
        $type = array_unique($type);
        $where['type_id'] = [
            'in',
            implode(',', $type)
        ];
        $where['vod_status'] = [
            'eq',
            1
        ];
        
        $total_pages = 0;
        if ($is_total == 'true') {
            $total = model("Vod")->countData($where);
            $total = ($total == 0) ? 1 : $total;
            $total_pages = ceil($total / $limit);
        }
        
        $res = model("Vod")->listData($where, $order, $page, $limit);
        
        if ($format == 'json') {
            ;
        } else {
            
            $this->assign('datalist', $res['list']);
            $html = $this->fetch('index/ajax_vod_more');
        }
        
        $r = [
            'code' => 1,
            'msg' => '',
            'page' => $page,
            'total_pages' => $total_pages,
            'list' => $res['list'],
            'html' => $html,
            'count' => count($res['list'])
        ];
        
        return (json($r));
    }

    /**
     * 视频列表
     *
     * @return \think\response\Json
     */
    public function get_vod_list()
    {
        
        // 页码
        $page = $this->_param['page'];
        if (empty($page)) {
            $page = 1;
        }
        
        // 分类id
        $type = $this->_param['tid'];
        if (! empty($type)) {
            
            $param['id'] = $type;
            $type_info = mac_label_type($param);
            if ($type_info['childids']) {
                $where['type_id'] = [
                    'in',
                    explode(",", $type_info['childids'])
                ];
            } else {
                $where['type_id'] = $type;
            }
        }
        
        // 每页显示条数
        $limit = $this->_param['limit'];
        if (empty($limit)) {
            $limit = 10;
        }
        
        // 搜索关键字
        $wd = $this->_param['wd'];
        if (! empty($wd)) {
            $where['vod_name|vod_en|vod_sub'] = [
                'like',
                '%' . $wd . '%'
            ];
        }
        
        // 排序字段
        $by = $this->_param['by'];
        
        // 时间范围 天数
        $timeadd = $this->_param['timeadd'];
        /**
         * 时间范围检索 单位 天
         */
        if (! empty($timeadd)) {
            $start_time = strtotime('-' . $timeadd . ' day');
            $where['vod_time_add'] = [
                'gt',
                $start_time
            ];
        }
        
        $by_arr = [
            'time',
            'hits',
            'score',
            'up',
            'hits_week'
        ];
        
        if (! in_array($by, $by_arr)) {
            $by = 'time';
        }
        
        $order = 'vod_' . $by . ' desc';
        
        $where['vod_status'] = [
            'eq',
            1
        ];
        
        // 返回总条数
        $total = model("Vod")->countData($where);
        $total = ($total == 0) ? 1 : $total;
        $total_pages = ceil($total / $limit);
        
        $res = model("Vod")->listData($where, $order, $page, $limit);
        
        $r = [
            'code' => 1,
            'msg' => '',
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'list' => $res['list'],
            'limit' => $limit
        ];
        
        return (json($r));
    }

    /**
     * 相同视频列表（无分页）
     *
     * @return \think\response\Json
     */
    public function get_vod_relevant()
    {
        
        // 分类id
        $type = $this->_param['tid'];
        if (! empty($type)) {
            $where['type_id'] = $type;
        }
        
        // 父类分类id
        $ptype = $this->_param['tpid'];
        if (! empty($ptype)) {
            unset($where['type_id']);
            $where['type_id_1'] = $ptype;
        }
        
        // 每页显示条数
        $limit = $this->_param['limit'];
        if (empty($limit)) {
            $limit = 10;
        }
        
        // 第几页
        $page = $this->_param['page'];
        if (empty($page)) {
            $page = 1;
        }
        
        // 排序字段
        $by = $this->_param['by'];
        
        // 时间范围 天数
        $timeadd = $this->_param['timeadd'];
        /**
         * 时间范围检索 单位 天
         */
        if (! empty($timeadd)) {
            $start_time = strtotime('-' . $timeadd . ' day');
            $where['vod_time_add'] = [
                'gt',
                $start_time
            ];
        }
        
        $by_arr = [
            'time',
            'hits',
            'score',
            'up',
            'hits_week'
        ];
        
        if (! in_array($by, $by_arr)) {
            $by = 'time';
        }
        
        $order = 'vod_' . $by . ' desc';
        
        $where['vod_status'] = [
            'eq',
            1
        ];
        
        $res = model("Vod")->listData($where, $order, $page, $limit);
        
        $r = [
            'code' => 1,
            'msg' => '',
            'list' => $res['list'],
            'limit' => $limit,
            'pagecount' => $res['pagecount'],
            'page' => $page
        ];
        
        return (json($r));
    }

    /**
     * 相同视频列表（无分页）
     *
     * @return \think\response\Json
     */
    public function get_favs()
    {
        $param = input();
        $page = intval($param['page']) < 1 ? 1 : intval($param['page']);
        $limit = intval($param['limit']) < 20 ? 20 : intval($param['limit']);
        
        $where = [];
        $where['user_id'] = $GLOBALS['user']['user_id'];
        $where['ulog_type'] = 2;
        $order = 'ulog_id desc';
        $res = model('Ulog')->listData($where, $order, $page, $limit);
        
        // 返回总条数
        $total = $res['total'];
        $total = ($total == 0) ? 1 : $total;
        $total_pages = ceil($total / $limit);
        
        $r = [
            'code' => 1,
            'msg' => '',
            'total' => $total,
            'page' => $page,
            'total_pages' => $total_pages,
            'list' => $res['list'],
            'limit' => $limit
        ];
        
        return (json($r));
    }

    /**
     * 获取播放器的广告
     *
     * @return \think\response\Json
     */
    public function get_playbanner()
    {
        $res = file_get_contents('./data/adv.txt');
        
        return json(json_decode($res));
    }

    /**
     * 验证用户名
     */
    public function check_user_name()
    {
        $r = [
            'code' => 0,
            'msg' => '错误'
        ];
        $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : "";
        
        $res = model('User')->checkUsername($user_name);
        if ($res['code'] === 1) {
            
            $token = md5(date('Y-m-d').$user_name);            
            return json([
                'token' => $token,
                'code' => 1,
                'msg' => '成功'
            ]);
            
        } else {
            
            return json($res);
        }
    }

    /**
     * 获取手机号验证码
     */
    public function get_phone_code()
    {
        $global = Config::get('global');
        
        if ($global['sendPhone'] !== true) {
            return json($r = [
                'code' => 0,
                'msg' => '手错误'
            ]);
        }
        
        $r = [
            'code' => 0,
            'msg' => '手机号错误'
        ];
        
        $phone = isset($_POST['user_phone']) ? $_POST['user_phone'] : "";
        $user_name = isset($_POST['user_name']) ? $_POST['user_name'] : "";
        
        $token = isset($_POST['token']) ? $_POST['token'] : "";
        
        if(md5(date('Y-m-d').$user_name) != $token){
            $r['msg'] = '页面过期,重新注册';
            return json($r);
        }
        
        $res = preg_match("/^1\d{10}$/", $phone);
        
        if ($res) {
            
            $ip = request()->ip();
            
            // cookie 判断发送次数
            $number = intval(cookie('send_phone'));
            if ($number >= 3) {
                $r['msg'] = '今日发送次数已用完';
                return json($r);
            }
            
            // 判断手机是否存在
            $w['user_phone'] = $phone;
            $res = model('User')->infoData($w, 'user_id', false);
            if ($res['code'] === 1) {
                $r['msg'] = '手机号已存在';
                return json($r);
            }
            
            // 判断 今天手机号发送次数
            $w = [];
            $w['phone'] = $phone;
            $w['add_time'] = [
                '>',
                strtotime(date('Y-m-d'))
            ];
            $res = model('SendPhone')->getCount($w);
            if ($res['code'] > 1 || $res['info'] >= 3) {
                $r['msg'] = '今日发送次数已达上限';
                return json($r);
            }
            // 判断IP发送次数
            
            // 验证码
            $code = rand(111111, 999999);
            
            // 发送短信
            $data['user_name'] = $user_name;
            $data['phone'] = $phone;
            $data['code'] = $code;
            
            // 有效期 半小时
            $data['exp_time'] = time() + (60 * 30);
            $data['type'] = 1;
            $data['ip'] = $ip;
            $res = model('SendPhone')->saveData($data);
            
            if ($res['code'] === 1) {
                
                $data = [];
                
                $id = $res['id'];
                
                // 11位手机号
                $data['mobile'] = $phone;
                // 6位数验证码
                $data['code'] = $code;
                
                // 秘钥
                $auth = 'yunpian13038732225';
                // key 生成规则：md5( 当前日期 + 手机号 + 验证码 + 秘钥)
                $data['key'] = md5(date('Ymd') . $data['mobile'] . $data['code'] . $auth);
                
                $result = self::get_post_curl('http://api.f99.space/send.php', $data);
                
                // 如果发送失败，把状态码保存数据库方便调试
                if ($result != 'success') {
                    $sd['id'] = $id;
                    $sd['remark'] = $result;
                    model('SendPhone')->saveData($sd);
                }
                cookie('send_phone', $number + 1, 60 * 60 * 2);
                
                $r = [
                    'code' => 1,
                    'msg' => '发送成功',
                    'result' => $result
                ];
            }
        }
        
        return (json($r));
    }

    /**
     * php模拟post请求
     */
    private function get_post_curl($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false); // required as of PHP 5.6.0
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch); /* 释放 */
        return $result;
    }
}