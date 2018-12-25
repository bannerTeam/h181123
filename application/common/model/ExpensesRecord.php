<?php
namespace app\common\model;

use think\Db;

class ExpensesRecord extends Base
{

    // 设置数据表（不含前缀）
    protected $name = 'expenses_record';

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
        
        $list = Db::name('expenses_record')
            ->field($field)
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
        if(isset($data['id'])){
            
            $id = $data['id'];
            
            $where['id'] = $id;
            
            unset($data['id']);
            $res = $this->where($where)->update($data);
            
        }else{
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
     * @param unknown $where
     * @param unknown $field
     * @param number $num
     * @return unknown
     */
    public function updateSetInc($where,$field,$num = 1){
        return $this->where($where)->setInc($field, $num);
    }
    
    /**
     * 自减
     * @param unknown $where
     * @param unknown $field
     * @param number $num
     * @return unknown
     */
    public function updateSetDec($where,$field,$num = 1){
        return $this->where($where)->setDec($field, $num);
    }
}