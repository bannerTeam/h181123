<?php
namespace app\admin\controller;
use think\Db;

class Card extends Base
{
    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        $param = input();
        $param['page'] = intval($param['page']) <1 ? 1 : $param['page'];
        $param['limit'] = intval($param['limit']) <1 ? $this->_pagesize : $param['limit'];

        $where=[];
        if(in_array($param['sale_status'],['0','1'],true)){
            $where['card_sale_status'] = ['eq',$param['sale_status']];
        }
        if(in_array($param['use_status'],['0','1'],true)){
            $where['card_use_status'] = ['eq',$param['use_status']];
        }
        if(!empty($param['wd'])){
            $where['card_no'] = ['like','%'.$param['wd'].'%'];
        }


        $order='card_id desc';
        $res = model('Card')->listData($where,$order,$param['page'],$param['limit']);

        $this->assign('list',$res['list']);
        $this->assign('total',$res['total']);
        $this->assign('page',$res['page']);
        $this->assign('limit',$res['limit']);

        $param['page'] = '{page}';
        $param['limit'] = '{limit}';
        $this->assign('param',$param);
        $this->assign('title','充值卡管理');
        return $this->fetch('admin@card/index');
    }

    public function info()
    {
        if (Request()->isPost()) {
            $param = input('post.');

            if(empty($param['num']) || empty($param['money']) || empty($param['point']) ){
                return $this->error('参数错误');
            }

            $res = model('Card')->saveAllData(intval($param['num']),intval($param['money']),intval($param['point']),intval($param['vip_id']));
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        
        
        $where=[];
        $res = model('Vip')->listData($where);
        $this->assign('vips',$res['list']);
       
        $id = input('id');
        $where=[];
        $where['card_id'] = ['eq',$id];
        $res = model('Card')->infoData($where);

        $this->assign('info',$res['info']);

        return $this->fetch('admin@card/info');
    }
    
    /**
     * 卡密导出
     */
    public function export(){
        
        if (Request()->isPost()) {
            
            $param = input();
            
            //添加的时间
            $card_add_time = $param['add_time'];            
            $add_time = strtotime($card_add_time);
            if(empty($add_time)){
                return ['code' => 1001, 'msg' => '请选择添加时间'];
            }
            //VIP
            $vip_id = $param['vip'];
            if(empty($vip_id)){
                return ['code' => 1002, 'msg' => '请选择VIP卡类型'];
            }
            
            //VIP
            $status = intval($param['status']);
            if(!in_array($status, [1,2,3])){
                return ['code' => 1003, 'msg' => '请选择状态'];
            }
            
            if($status === 1){
                $where['user_id'] = 0;
            }else if($status === 2){
                $where['user_id'] = array('>',0);
            }
            
            $where['vip_id'] = $vip_id;
            
            $where['card_add_time'] = array(['>',strtotime(date('Y-m-d',$add_time))],
                ['<',strtotime(date('Y-m-d',$add_time).' 23:59:59')],'and');
            
            
            $order='card_id desc';
            //查询卡密
            $res = model('Card')->listAllData($where,$order);
            if(count($res['list']) == 0){
                return ['code' => 1003, 'msg' => '没有符合条件的数据'];
            }
            if($res['code'] === 1){
                //目录
                $folder = "./upload/cart/".date('Ym');
                //文件名称
                $file_name = date('YmdHis');
                //创建目录
                mac_mkdirss($folder);
                //文件路径
                $file_path = $folder.'/'.$file_name.".txt";
                //打开文件
                $myfile = fopen($file_path, "w");
                $list = $res['list'];
                foreach ($list as $k => $v) {
                    $txt = $v['card_no'] .' '. $v['card_pwd']."\n";
                    fwrite($myfile, $txt);
                }
                fclose($myfile);
                
                return ['code' => 1, 'msg' => '成功','file'=> '/'.$file_path,'name'=> '/'.$file_name.".txt"];
                
            }
            
            return $this->success($res['msg']);
        }
        
              
        $where=[];
        $res = model('Vip')->listData($where);
        $this->assign('vips',$res['list']);
        
        return $this->fetch('admin@card/export');
    }

    public function del()
    {
        $param = input();
        $ids = $param['ids'];
        $all = $param['all'];

        if(!empty($ids)){
            $where=[];
            $where['card_id'] = ['in',$ids];
            if($all==1){
                $where['card_id'] = ['gt',0];
            }

            $res = model('Card')->delData($where);
            if($res['code']>1){
                return $this->error($res['msg']);
            }
            return $this->success($res['msg']);
        }
        return $this->error('参数错误');
    }

}
