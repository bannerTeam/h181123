<?php
namespace app\common\model;

use think\Db;

class ProxyWithdraw extends Base
{

    // 设置数据表（不含前缀）
    protected $name = 'proxy_withdraw';

    // 定义时间戳字段名
    protected $createTime = '';

    protected $updateTime = '';

    // 自动完成
    protected $auto = [];

    protected $insert = [];

    protected $update = [];

    public function countData($where)
    {
        $total = $this->where($where)->count();
        return $total;
    }

    public function listData($where, $order = 'id desc', $page = 1, $limit = 20, $start = 0, $field = '*', $addition = 1, $totalshow = 1)
    {
        if (! is_array($where)) {
            $where = json_decode($where, true);
        }
        
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        if ($totalshow == 1) {
            $total = $this->where($where)->count();
        }
        
        $list = Db::name('proxy_withdraw')->field($field)
            ->where($where)
            ->order($order)
            ->limit($limit_str)
            ->select();
        
        foreach ($list as $k => $v) {
            $list[$k]['status_format'] = mac_withdraw_status($v['status']);
            $list[$k]['type_format'] = mac_withdraw_type($v['type']);
        }
        
        return [
            'code' => 1,
            'msg' => '数据列表',
            'page' => $page,
            'pagecount' => ceil($total / $limit),
            'limit' => $limit,
            'total' => $total,
            'list' => $list
        ];
    }

    public function findData($where, $field = '*', $order = 'id desc')
    {
        if (empty($where) || ! is_array($where)) {
            return [
                'code' => 1001,
                'msg' => '参数错误'
            ];
        }
        
        $info = $this->field($field)
            ->where($where)
            ->order($order)
            ->find();
        
        if (empty($info)) {
            return [
                'code' => 1002,
                'msg' => '获取数据失败'
            ];
        }
        
        return [
            'code' => 1,
            'msg' => '获取成功',
            'info' => $info
        ];
    }

    public function saveData($data)
    {
        $id = 0;
        if (isset($data['id'])) {
            
            $id = $data['id'];
            
            $where['id'] = $id;
            
            unset($data['id']);
            $res = $this->where($where)->update($data);
        } else {
            $data['add_time'] = time();
            $res = $this->allowField(true)->insertGetId($data);
            $id = $res;
        }
        if (false === $res) {
            return [
                'code' => 1002,
                'msg' => '保存失败：' . $this->getError()
            ];
        }
        return [
            'code' => 1,
            'msg' => '保存成功',
            'id' => $id
        ];
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if ($res === false) {
            return [
                'code' => 1001,
                'msg' => '删除失败：' . $this->getError()
            ];
        }
        return [
            'code' => 1,
            'msg' => '删除成功'
        ];
    }

    /**
     * 提现申请
     *
     * @param unknown $user_id            
     * @param unknown $amount            
     * @return unknown
     */
    public function apply($user_id, $amount)
    {
        if (empty($user_id)) {
            return [
                'code' => - 1,
                'msg' => '失败'
            ];
        }
        
        if (! is_numeric($amount)) {
            return [
                'code' => - 1,
                'msg' => '金额必须整数'
            ];
        }
        
        $where['user_id'] = $user_id;
        $res = model('Proxy')->findData($where, 'id,name,amount,status');
        if ($res['code'] !== 1) {
            return [
                'code' => - 1,
                'msg' => '不存在'
            ];
        }
        $info = $res['info'];
        
        if ($info['status'] == 2 || $info['status'] == 3) {
            return [
                'code' => - 2,
                'msg' => '您的账户已被冻结，请联系平台客服'
            ];
        }
        
        $old_amount = $info['amount'];
        if ($amount > $old_amount) {
            return [
                'code' => - 3,
                'msg' => '账户余额不足'
            ];
        }
        $data['user_id'] = $user_id;
        $data['name'] = $info['name'];
        $data['amount'] = $amount;
        $data['before_amount'] = $old_amount;
        $data['after_amount'] = $old_amount - $amount;
        $data['ip'] = request()->ip();
        $data['status'] = 0;
        $data['type'] = 1;
        $res = $this->saveData($data);
        if ($res['code'] === 1) {
            
            $proxy_id = $info['id'];
            // 更新金额 以及 正在申请的金额
            $result = Db::execute('update mac_proxy set amount = amount-' . $amount . ',amount_apply = amount_apply+' . $amount . ' where id = ' . $proxy_id . ' and amount >=' . $amount);
            
            return [
                'code' => 1,
                'msg' => '申请成功',
                'info' => $result
            ];
        }
        return [
            'code' => 1,
            'msg' => '申请失败',
            'info' => $result
        ];
    }

    /**
     * 通过
     *
     * @param unknown $admin_id            
     * @param unknown $id            
     */
    public function complete($admin_id, $id)
    {
        $data['id'] = $id;
        $data['admin_id'] = $admin_id;
        $data['status'] = 1;
        $data['check_time'] = time();
        
        $where['id'] = $id;
        $where['status'] = 0;
        $ret = $this->findData($where);
        if($ret['code'] !== 1){
            return [
                'code' => -1,
                'msg' => '失败',
                'info' => $result
            ];
        }
        
        $info = $ret['info'];        
        $amount = $info['amount'];
        $user_id  = $info['user_id'];
        // 更新金额 以及 正在申请的金额
        $result = Db::execute('update mac_proxy set amount_apply = amount_apply-' . $amount . ',amount_complete = amount_complete+' . $amount . ' where user_id = ' . $user_id . ' and amount_apply >=' . $amount);
                
        return $this->saveData($data);
    }

    /**
     * 拒绝
     *
     * @param unknown $admin_id            
     * @param unknown $id            
     */
    public function refuse($admin_id, $id)
    {
        $data['id'] = $id;
        $data['admin_id'] = $admin_id;
        $data['status'] = 2;
        $data['check_time'] = time();
        
        
        $where['id'] = $id;
        $where['status'] = 0;
        $ret = $this->findData($where);
        if($ret['code'] !== 1){
            return [
                'code' => -1,
                'msg' => '失败',
                'info' => $result
            ];
        }
        
        $info = $ret['info'];
        $amount = $info['amount'];
        $user_id  = $info['user_id'];
        // 更新金额 以及 正在申请的金额
        $result = Db::execute('update mac_proxy set amount_apply = amount_apply-' . $amount . ',amount = amount+' . $amount . ' where user_id = ' . $user_id . ' and amount_apply >=' . $amount);
        
        
        return $this->saveData($data);
    }
}