<?php
namespace app\common\model;

use think\Db;

class Brokerage extends Base
{

    // 设置数据表（不含前缀）
    protected $name = 'brokerage';

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

    public function listData($where, $order = 'id desc', $page = 1, $limit = 20, $start = 0, $field = 'b.*,u.group_id', $addition = 1, $totalshow = 1)
    {
        if (! is_array($where)) {
            $where = json_decode($where, true);
        }
        
        $limit_str = ($limit * ($page - 1) + $start) . "," . $limit;
        if ($totalshow == 1) {
            $total = Db::name('brokerage')->alias('b')
                ->join('__USER__ u', 'b.user_id = u.user_id', 'left')
                ->where($where)
                ->count();
        }
        
        $list = Db::name('brokerage')->alias('b')
            ->field($field)
            ->join('__USER__ u', 'b.user_id = u.user_id', 'left')
            ->where($where)
            ->order($order)
            ->limit($limit_str)
            ->select();
        
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
     * 自增
     * 
     * @param unknown $where            
     * @param unknown $field            
     * @param number $num            
     * @return unknown
     */
    public function updateSetInc($where, $field, $num = 1)
    {
        return $this->where($where)->setInc($field, $num);
    }

    /**
     * 自减
     * 
     * @param unknown $where            
     * @param unknown $field            
     * @param number $num            
     * @return unknown
     */
    public function updateSetDec($where, $field, $num = 1)
    {
        return $this->where($where)->setDec($field, $num);
    }

    /**
     * 修改派发状态
     */
    public function updateUseStatus($data)
    {
        if(empty($data['id']) || empty($data['use_status']) ){
            return [
                'code' => -4,
                'msg' => '参数错误'
            ];
        }
        
        //获取派发信息
        $where['id'] = $data['id'];
        $res = self::findData($where);
        $info = $res['info'];
        //判断数据是否操作，且状态为 等待派发
        if(empty($info) || $info['use_status'] > 0){
            return [
                'code' => -6,
                'msg' => '数据失效'
            ];
        }
        
        // 拒绝
        if ($data['use_status'] == 2) {
            return $this->saveData($data);
        }
        
        // 获取代理信息
        $w1['id'] = $info['proxy_id'];
        $r1 = model('Proxy')->findData($w1, 'id,name,amount,status');
        $i1 = $r1['info'];
        
        if($i1['status'] > 1){
            return [
                'code' => -8,
                'msg' => '代理被冻结不能进行派发'
            ];
        }
                
        // 通过
        $res = $this->saveData($data);        
        if ($res['code'] === 1) {
            
            // 总提成
            $amount = $info['amount_1'] + $info['amount_2'] + $info['amount_3'];
           
            // 插入佣金派发记录
            $param['user_id'] = $info['user_id'];
            $param['proxy_id'] = $info['proxy_id'];
            $param['name'] = $i1['name'];
            //派发金额
            $param['amount'] = $amount;
            //派发前（目前余额）
            $param['before_amount'] = $i1['amount'];
            //派发后
            $param['after_amount'] = $i1['amount'] + $amount;
            $param['add_time'] = time();
            $param['check_time'] = time();
            $param['admin_id'] = $info['admin_id'];
            $param['ip'] = request()->ip();
            $param['status'] = 1;
            $param['type'] = 2;
            $res = model('ProxyWithdraw')->saveData($param);
            if($res['code'] === 1){
                $w2['id'] = $info['proxy_id'];
                model('Proxy')->updateSetInc($w2,'amount',$amount); 
            }
            
        }
        
        return $res;
    }
}