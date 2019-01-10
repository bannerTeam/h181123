<?php
namespace app\common\model;
use think\Db;

class Card extends Base {
    // 设置数据表（不含前缀）
    protected $name = 'card';

    // 定义时间戳字段名
    protected $createTime = '';
    protected $updateTime = '';

    // 自动完成
    protected $auto       = [];
    protected $insert     = [];
    protected $update     = [];

    public function getCardUseStatusTextAttr($val,$data)
    {
        $arr = [0=>'未使用',1=>'已使用'];
        return $arr[$data['card_use_status']];
    }

    public function getCardSaleStatusTextAttr($val,$data)
    {
        $arr = [0=>'未销售',1=>'已销售'];
        return $arr[$data['card_sale_status']];
    }

    public function listData($where,$order,$page,$limit=20)
    {
        $total = $this->where($where)->count();
        $list = Db::name('Card')->where($where)->order($order)->page($page)->limit($limit)->select();
        foreach($list as $k=>$v){
            if($v['user_id'] >0){
                $user = model('User')->infoData(['user_id'=>$v['user_id']]);
                $list[$k]['user'] = $user['info'];
            }
        }
        return ['code'=>1,'msg'=>'数据列表','page'=>$page,'pagecount'=>ceil($total/$limit),'limit'=>$limit,'total'=>$total,'list'=>$list];
    }
    
    public function listAllData($where,$order)
    {
       
        $list = Db::name('Card')->where($where)->order($order)->select();
        
        return ['code'=>1,'msg'=>'数据列表','list'=>$list];
    }

    public function infoData($where,$field='*')
    {
        if(empty($where) || !is_array($where)){
            return ['code'=>1001,'msg'=>'参数错误'];
        }
        $info = $this->field($field)->where($where)->find();

        if(empty($info)){
            return ['code'=>1002,'msg'=>'获取数据失败'];
        }
        $info = $info->toArray();

        return ['code'=>1,'msg'=>'获取成功','info'=>$info];
    }

    public function saveData($data)
    {
        $validate = \think\Loader::validate('Card');
        if(!$validate->check($data)){
            return ['code'=>1001,'msg'=>'参数错误：'.$validate->getError() ];
        }

        if(!empty($data['card_id'])){
            $where=[];
            $where['card_id'] = ['eq',$data['card_id']];
            $res = $this->allowField(true)->where($where)->update($data);
        }
        else{
            $data['card_add_time'] = time();
            $res = $this->allowField(true)->insert($data);
        }
        if(false === $res){
            return ['code'=>1002,'msg'=>'保存失败：'.$this->getError() ];
        }
        return ['code'=>1,'msg'=>'保存成功'];
    }

    public function saveAllData($num,$money,$point,$vip_id = 0)
    {
        $data=[];
        for($i=1;$i<=$num;$i++){
            $card_no = mac_get_rndstr(16);
            $data[$card_no] = ['card_no'=>$card_no,'card_pwd'=>mac_get_rndstr(8),'card_money'=>$money,'vip_id'=>$vip_id,'card_points'=>$point,'card_add_time'=>time()];
        }
        $data = array_values($data);
        $res = $this->allowField(true)->insertAll($data);
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
        return ['code'=>1,'msg'=>'设置成功'];
    }

    public function useData($card_no,$card_pwd,$user_info)
    {
        if (empty($card_no) || empty($card_pwd) || empty($user_info)) {
            return ['code' => 1001, 'msg' => '参数错误'];
        }

        $where=[];
        $where['card_no'] = ['eq',$card_no];
        $where['card_pwd'] = ['eq',$card_pwd];
        //$where['card_sale_status'] = ['eq',1];
        $where['card_use_status'] = ['eq',0];

        $info = $this->where($where)->find();
        if(empty($info)){
            return ['code' => 1002, 'msg' => '充值卡信息有误，请重试'];
        }
        
        $card_money = $info['card_money'];
        
        //判断卡密是否有 VIP 会员
        $vip_id = 0;
        if(intval($info['vip_id']) > 0){
            $vip_id = $info['vip_id'];
        }        
        //卡密 VIP会员天数
        $vipDuration = 0;
        $vipName = '';
        if($vip_id){
            $where3['id']= $vip_id;
            $vipData = model('Vip')->findData($where3);
            if($vipData['code'] === 1){
                //会员天数
                $vipDuration = $vipData['info']['duration'];     
                $vipName =  $vipData['info']['name'];     
            }else{
                return ['code' => 1004, 'msg' => 'VIP不存在'];
            }
        }        
        
        $where2=[];
        $where2['user_id'] = $user_info['user_id'];
        $userData = model('User')->infoData($where2);
        if($userData['code'] > 1){
            return ['code' => 1003, 'msg' => '会员不存在，请重试'];
        }
        
        $update=[];
        if($vipDuration){
            //会员Vip结束时间
            $vip_exp_time = $userData['info']['vip_exp_time'];
            //判断过期时间 是否大于当前时间，如果大于 时间就 追加
            if($vip_exp_time > time()){
                //未到期追加时间
                $vip_exp_time = $vip_exp_time + (60*60*24) * $vipDuration;
            }else{
                $vip_exp_time = time() + (60*60*24) * $vipDuration;
                $ymd = date('Y-m-d',$vip_exp_time);
                $vip_exp_time = strtotime($ymd.' 23:59:59');
                $vip_start_time = strtotime(date('Y-m-d',time()));
                $update['vip_start_time'] = $vip_start_time;
            }
            $update['vip_exp_time'] = $vip_exp_time;
        }
        $update['user_points'] = $user_info['user_points'] + $info['card_points'];
        $res = model('User')->where($where2)->update($update);
        if($res===false){
            return ['code' => 1003, 'msg' => '更新用户点数失败，请重试'];
        }        

        $update=[];
        $update['card_sale_status'] = 1;
        $update['card_use_status'] = 1;
        $update['card_use_time'] = time();
        $update['user_id'] = $user_info['user_id'];
        $res = $this->where($where)->update($update);
        if($res===false){
            return ['code' => 1005, 'msg' => '更新充值卡状态失败，请重试'];
        }
        
        $proxy_id = 0;
        $proxy_pid = 0;
        //邀请人会员ID/推荐人
        $inviter_user_id = $user_info['inviter_user_id'];
        $pWhere['user_id'] = $inviter_user_id;
        //根据推荐人获取上级代理
        $pRes = model('proxy')->findData($pWhere);
        if($pRes['code'] === 1){
            
            $pData = $pRes['info'];
            
            $proxy_id = $pData['id'];
            $proxy_pid = $pData['pid'];
            
            //会员消费记录
            $erData['user_id']= $user_info['user_id'];
            $erData['user_name']= $user_info['user_name'];
            $erData['proxy_id']= $proxy_id;//上级代理
            $erData['proxy_pid']= $proxy_pid;//上上级代理
            $erData['project']= $vipName;//*消费项目(例：包年VIP)
            $erData['amount']= $card_money;
            $erData['recharge']= '卡密';//*充值方式（例：在线-微信）
            $erData['add_time']= time();
            //写入消费记录
            model('ExpensesRecord')->saveData($erData);
        }
       
        
        
      
        
        
        
        
        return ['code' => 1, 'msg' => '充值成功，增加积分【'.$info['card_points'].'】'];
    }
}