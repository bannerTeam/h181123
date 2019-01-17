<?php
namespace app\common\model;
use think\Db;
use think\Cache;

class SendPhone extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'send_phone';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto       = [];
    protected $insert     = [];
    protected $update     = [];

    public function countData($where)
    {
        $total = $this->where($where)->count();
        return $total;
    }

    
    public function listData($where,$order='id asc',$field='*')
    {
        if(!is_array($where)){
            $where = json_decode($where,true);
        }
        
        $list = $this->field($field)->where($where)->order($order)->select();
        
        
        return ['code'=>1,'msg'=>'数据列表','list'=>$list];
    }
    
    public function findData($where,$field='*',$order='id desc')
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        
        $info = $this->field($field)->where($where)->order($order)->find();
                
        if (empty($info)) {
            return ['code' => 1002, 'msg' => '获取数据失败'];
        }
        
        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        
        if(!empty($data['id'])){            
            $where = [];
            $where['id'] = ['eq', $data['id']];
            $res = $this->where($where)->update($data);
        }else{
            $data['add_time'] = time();
            $res = $this->allowField(true)->insertGetId($data);
        }
        
        if(false === $res){
            return ['code'=>1002,'msg'=>'保存失败'];
        }
        return ['code'=>1,'msg'=>'保存成功','id'=>$res];
    }

    public function delData($where)
    {
        $res = $this->where($where)->delete();
        if($res===false){
            return ['code'=>1001,'msg'=>'删除失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'删除成功'];
    }

    
    public function getCount($where)
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        
        $info = $this->where($where)->count();
        
        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }
   

}