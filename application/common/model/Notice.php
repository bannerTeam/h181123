<?php
namespace app\common\model;
use think\Db;
use think\Cache;

class Notice extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'notice';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto       = [];
    protected $insert     = [];
    protected $update     = [];

    public function getStatusTextAttr($val,$data)
    {
        $arr = [0=>'禁用',1=>'启用'];
        return $arr[$data['status']];
    }

   

    public function countData($where)
    {
        $total = $this->where($where)->count();
        return $total;
    }

    public function listData($where,$order,$page=1,$limit=20,$start=0,$field='*',$addition=1,$totalshow=1)
    {
        if(!is_array($where)){
            $where = json_decode($where,true);
        }
        $limit_str = ($limit * ($page-1) + $start) .",".$limit;
        if($totalshow==1) {
            $total = $this->where($where)->count();
        }
        $list = Db::name('Notice')->field($field)->where($where)->order($order)->limit($limit_str)->select();
        

        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }
    

    

    public function infoData($where,$field='*',$cache=0)
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $info = $this->field($field)->where($where)->find();
        if (empty($info)) {
            return ['code' => 1002, 'msg' => '获取数据失败'];
        }
        $info = $info->toArray();
                  
        
        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        
        $start_time = $data['start_time'];
        $end_time = $data['end_time'];
        

        if(!preg_match("/^\d{4}-\d{2}-\d{2}/i",$start_time))        
        {
            return ['code'=>1001,'msg'=>'开始时间格式错误'];
        }
        if(!preg_match("/^\d{4}-\d{2}-\d{2}/i",$end_time))
        {
            return ['code'=>1002,'msg'=>'结束时间格式错误'];
        }
        
        
        $start_time = strtotime($start_time.' 00:00:00');
        $end_time = strtotime($end_time.' 23:59:59');
        
        if($start_time > $end_time){
            return ['code'=>1003,'msg'=>'开始时间不能大于结束时间'];
        }
       
        $data['start_time'] = $start_time;
        $data['end_time'] = $end_time;
        
        if(!empty($data['id'])){
            
            
            $where=[];
            $where['id'] = ['eq',$data['id']];
            
            unset($data['id']);
            
            $res = $this->allowField(true)->where($where)->update($data);
        }
        else{
            
            $res = $this->allowField(true)->insert($data);
        }
        if(false === $res){
            return ['code'=>1002,'msg'=>'保存失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function delData($where)
    {
     
        
        $res = $this->where($where)->delete();
        if($res===false){
            return ['code'=>1001,'msg'=>'删除失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'删除成功'];
    }

    public function fieldData($where,$col,$val)
    {
        if(!isset($col) || !isset($val)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }

        $data = [];
        $data[$col] = $val;
        $res = $this->allowField(true)->where($where)->update($data);
        if($res===false){
            return ['code'=>1001,'msg'=>'设置失败：'.$this->getError() ];
        }

        $list = $this->field('art_id,art_name,art_en')->where($where)->select();
        foreach($list as $k=>$v){
            $key = 'art_detail_'.$v['art_id'];
            Cache::rm($key);
            $key = 'art_detail_'.$v['art_en'];
            Cache::rm($key);
        }

        return ['code'=>1,'msg'=>'设置成功'];
    }

    public function updateToday($flag='art')
    {
        $today = strtotime(date('Y-m-d'));
        $where = [];
        $where['art_time'] = ['gt',$today];
        if($flag=='type'){
            $ids = $this->where($where)->column('type_id');
        }
        else{
            $ids = $this->where($where)->column('art_id');
        }
        if(empty($ids)){
            $ids = [];
        }else{
            $ids = array_unique($ids);
        }
        return ['code'=>1,'msg'=>'获取成功','data'=> join(',',$ids) ];
    }

}