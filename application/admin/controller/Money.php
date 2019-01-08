<?php
/**
 * Created by PhpStorm.
 * User: lenovo
 * Date: 2018/11/28
 * Time: 1:29
 */

namespace app\admin\controller;


use app\admin\model\MemberModel;
use app\common\model\AdminLogModel;
use app\common\model\MoneyModel;
use app\common\model\MoneyPropose;
use app\common\model\MoneyRecharge;
use app\common\model\MoneyRegular;
use think\Db;

class Money extends Base
{
    /**
     * 充值申请列表
     */
    public function recharge_list(){
        $MoneyRecharge = new MoneyRecharge();
        $count = $MoneyRecharge->getRechargeCount();
        $this->assign('count',$count);
        return $this->fetch();
    }
    /**
     * 充值确认
     */
    public function recharge_confirm(){
        $id = input('post.id');
        $MoneyRecharge = new MoneyRecharge();
        $MemberModel = new MemberModel();
        $detail = $MoneyRecharge->getRechargeFind('order_number,uuid,number,FROM_UNIXTIME(add_time) as add_time,state,type',$id);
        $user_detail = Db::name('member')->where(['uuid'=>$detail['uuid']])->find();
        if($detail['state'] > 1 ){
            return  json(['code'=>1012,'msg'=>'当前订单不是未充值状态']);
        }
        $MoneyModel = new MoneyModel();
        //充值配置
        $config = ConfigCapital();
        $member_detail = $MemberModel->where('uuid',$detail['uuid'])->find();
        switch ($detail['type']){
            case 1://金币网  按比例进入 定期 增值
                Db::startTrans();
                try{
                    $res = $MoneyRecharge->getRechargeConfirm($id,$this->admin_id,$this->admin_name);
                    //充值可用
                    if($config['recharge_jinbi_keyong'] > 0){
                        $MoneyModel->getModifyMoney($detail['uuid'],3,1,($detail['number']*$config['recharge_jinbi_keyong']/100),'可用充值','',4,$this->admin_id,$this->admin_name);
                    }
                    //充值定期
                    if($config['recharge_jinbi_dingqi'] > 0){
                        if($member_detail['activation'] != 1 && ($detail['number']*$config['recharge_jinbi_dingqi']/100) >= $config['active_member']){
                            Db::name('member')->where('uuid',$member_detail['uuid'])->update(['activation'=>1]);
                            Db::query('CALL TeamNumber('.$member_detail['id'].')');
                        }
                        if($member_detail['team_state'] != 1 && ($detail['number']*$config['recharge_jinbi_dingqi']/100) >= $config['active_team']){
                            Db::name('member')->where('uuid',$member_detail['uuid'])->update(['team_state'=>1]);
                        }
                        $MoneyModel->getModifyMoney($detail['uuid'],1,1,($detail['number']*$config['recharge_jinbi_dingqi']/100),'可用充值，自动转换定期','',4,$this->admin_id,$this->admin_name);
                        $this->direct_distribution($user_detail['id'],($detail['number']*$config['recharge_jinbi_dingqi']/100));
                    }
                    //充值增值
                    if($config['recharge_jinbi_zengzhi'] > 0){
                        $MoneyModel->getModifyMoney($detail['uuid'],5,1,($detail['number']*$config['recharge_jinbi_zengzhi']/100),'可用充值，自动转换增值','',4,$this->admin_id,$this->admin_name);
                    }
                    $AdminLog = new AdminLogModel();
                    unset($detail['state']);
                    unset($detail['type']);
                    $data = json_encode($detail);
                    $AdminLog->getAddInfo('充值确认',1,$data,$this->admin_id,$this->admin_name);
                    Db::commit();
                    return json($res);
                }catch (\PDOException $exception){
                    Db::rollback();
                    return  json(['code'=>1012,'msg'=>'确认充值申请失败，'.$exception->getMessage()]);
                }

                break;
            case 2://乾坤网  直接进入可用值
                //充值可用资金
                Db::startTrans();
                try{
                    $res = $MoneyRecharge->getRechargeConfirm($id,$this->admin_id,$this->admin_name);
                    //充值可用
                    if($config['recharge_qiankun_keyong'] > 0){
                        $MoneyModel->getModifyMoney($detail['uuid'],3,1,($detail['number']*$config['recharge_qiankun_keyong']/100),'可用充值','',4,$this->admin_id,$this->admin_name);
                    }
                    //充值定期
                    if($config['recharge_qiankun_dingqi'] > 0){
                        if($member_detail['activation'] != 1 && ($detail['number']*$config['recharge_jinbi_dingqi']/100) >= $config['active_member']){
                            Db::name('member')->where('uuid',$member_detail['uuid'])->update(['activation'=>1]);
                            Db::query('CALL TeamNumber('.$member_detail['id'].')');
                        }
                        if($member_detail['team_state'] != 1 && ($detail['number']*$config['recharge_jinbi_dingqi']/100) >= $config['active_team']){
                            Db::name('member')->where('uuid',$member_detail['uuid'])->update(['team_state'=>1]);
                        }
                        $MoneyModel->getModifyMoney($detail['uuid'],1,1,($detail['number']*$config['recharge_qiankun_dingqi']/100),'可用充值，自动转换定期','',4,$this->admin_id,$this->admin_name);
                        $this->direct_distribution($user_detail['id'],($detail['number']*$config['recharge_qiankun_dingqi']/100));
                    }
                    //充值增值
                    if($config['recharge_qiankun_zengzhi'] > 0){
                        $MoneyModel->getModifyMoney($detail['uuid'],5,1,($detail['number']*$config['recharge_qiankun_zengzhi']/100),'可用充值，自动转换增值','',4,$this->admin_id,$this->admin_name);
                    }
                    $AdminLog = new AdminLogModel();
                    unset($detail['state']);
                    unset($detail['type']);
                    $data = json_encode($detail);
                    $AdminLog->getAddInfo('充值确认',1,$data,$this->admin_id,$this->admin_name);
                    Db::commit();
                    return json($res);
                }catch (\PDOException $exception){
                    Db::rollback();
                    return  json(['code'=>1012,'msg'=>'确认充值申请失败，'.$exception->getMessage()]);
                }
                break;
        }
    }
    /**
     * 充值驳回
     */
    public function recharge_reject(){
        $id = input('post.id');
        $info = input('post.info');
        $MoneyRecharge = new MoneyRecharge();
        $res = $MoneyRecharge->getRechargeReject($id,$info,$this->admin_id,$this->admin_name);
        $detail = $MoneyRecharge->getRechargeFind('order_number,uuid,number,FROM_UNIXTIME(add_time) as add_time',$id);
        $AdminLog = new AdminLogModel();
        $data = json_encode($detail);
        $AdminLog->getAddInfo('充值驳回',1,$data,$this->admin_id,$this->admin_name);
        return json($res);
    }
    /**
     * 充值申请纪录
     */
    public function available_log(){
        $key = input('key');
        $state = input('param.state');
        $order_number = input('param.order_number');
        $stare_time = input('param.stare_time');
        $end_time = input('param.end_time');
        $type = input('param.type');
        $recharge_type = input('param.recharge_type');
        $map = [];
        if($key&&$key!==""){
            $map['m.account|m.uuid'] = $key;
        }
        if($state === 0 || !empty($state)){
            $map['r.state'] = $state;
        }
        if(!empty($recharge_type)){
            $map['r.type'] = $recharge_type;
        }
        if(!empty($order_number)){
            $map['r.order_number|r.trade'] = $order_number;
        }
        if(!empty($stare_time)){
            $stare_time = $stare_time.' 00:00:00';
            $map['r.add_time'] = ['>= time',$stare_time];
        }
        if(!empty($end_time)){
            $end_time = $end_time.' 23:59:59';
            $map['r.add_time'] = ['<= time',$end_time];
        }
        if(!empty($stare_time) && !empty($end_time)){
            $map['r.add_time'] = ['between time',[$stare_time,$end_time]];
        }
        $page = input('get.page') ? input('get.page'):1;
        $rows = input('get.rows');// 获取总条数
        switch ($type){
            case 1://充值
                $MoneyRecharge = new MoneyRecharge();
                $info = $MoneyRecharge->getRechargeList('r.*,m.account,m.nickname',$map,$page,$rows,'r.state ASC,r.add_time DESC');
                break;
            case 2://提现
                $MoneyPropose = new MoneyPropose();
                $info = $MoneyPropose->getProposeList('r.*,m.account,m.nickname',$map,$page,$rows,'r.state ASC,r.add_time DESC');
                break;
        }
        foreach ($info['lists'] as $k=>$v){
            $info['lists'][$k]['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            if(!empty($v['end_time'])){
                $info['lists'][$k]['end_time'] = date('Y-m-d H:i:s',$v['end_time']);
            }
        }
        $data['list'] = $info['lists'];
        $data['count'] = $info['count'];
        $data['page'] = $page;
        return json($data);
    }

    /**
     * 提现申请
     */
    public function propose_list(){
        $MoneyPropose = new MoneyPropose();
        $count = $MoneyPropose->getProposeCount();
        $this->assign('count',$count);
        return $this->fetch();
    }

    /**
     * 提现确认
     */
    public function  propose_confirm(){
        $id = input('post.id');
        $MoneyPropose = new MoneyPropose();
        //订单详情
        $detail = $MoneyPropose->getProposeFind('order_number,uuid,number,FROM_UNIXTIME(add_time) as add_time',$id);
        //确认订单
        $res = $MoneyPropose->getProposeConfirm($id,$this->admin_id,$this->admin_name);
        //添加操作日志
        $AdminLog = new AdminLogModel();
        $AdminLog->getAddInfo('提现申请确认',2,json_encode($detail),$this->admin_id,$this->admin_name);
        return json($res);
    }

    /**
     * 提现申请驳回
     */
    public function propose_reject(){
        $id =  input('post.id');
        $info =  input('post.info');
        $MoneyPropose = new MoneyPropose();
        $MoneyModel = new MoneyModel();
        $AdminLog = new AdminLogModel();
        //订单详情
        $detail = $MoneyPropose->getProposeFind('order_number,state,uuid,number,FROM_UNIXTIME(add_time) as add_time',$id);
        if($detail['state'] > 0){
            return json( ['code'=>1012,'msg'=>'订单状态异常，不是未审核订单']);
        }
        Db::startTrans();
        try{
            //驳回订单
            $res = $MoneyPropose->getProposeReject($id,$info,$this->admin_id,$this->admin_name);
            //返回可用资金
            $MoneyModel->getModifyMoney($detail['uuid'],3,1,$detail['number'],'提现申请驳回','',5,$this->admin_id,$this->admin_name);
            $AdminLog->getAddInfo('提现申请驳回',2,json_encode($detail),$this->admin_id,$this->admin_name);
            Db::commit();
            return json($res);
        }catch (\PDOException $exception){
            Db::rollback();
            return json( ['code'=>1012,'msg'=>'驳回提现申请失败，'.$exception->getMessage()]);
        }


    }
    /**
     * 定期列表
     */
    public function regular_list(){
        $MoneyRegular = new MoneyRegular();
        $count = $MoneyRegular->getRegularCount();
        $this->assign('count',$count);
        return $this->fetch();
    }

    /**
     * 定期记录
     */
    public function regular_log(){
        $key = input('key');
        $state = input('param.state');
        $order_number = input('param.order_number');
        $stare_time = input('param.stare_time');
        $end_time = input('param.end_time');
        $map = [];
        if($key&&$key!==""){
            $map['m.account|m.uuid'] = $key;
        }
        if($state === 0 || !empty($state)){
            $map['r.state'] = $state;
        }
        if(!empty($order_number)){
            $map['r.order_number'] = $order_number;
        }
        if(!empty($stare_time)){
            $stare_time = $stare_time.' 00:00:00';
            $map['r.add_time'] = ['>= time',$stare_time];
        }
        if(!empty($end_time)){
            $end_time = $end_time.' 23:59:59';
            $map['r.add_time'] = ['<= time',$end_time];
        }
        if(!empty($stare_time) && !empty($end_time)){
            $map['r.add_time'] = ['between time',[$stare_time,$end_time]];
        }
        $page = input('get.page') ? input('get.page'):1;
        $rows = input('get.rows');// 获取总条数
        $MoneyRegular = new MoneyRegular();
        $info = $MoneyRegular->getRegularLists("r.*,FROM_UNIXTIME(r.add_time, '%Y-%m-%d %H:%i:%S') as addtime,m.account,m.nickname",$map,$page,$rows,'r.state ASC,r.add_time DESC');
        $data['list'] = $info['lists'];
        $data['count'] = $info['count'];
        $data['page'] = $page;
        return json($data);
    }

    /**
     * 用户资金
     */
    public function member_money(){
        $uuid = input('get.uuid');
        $MoneyModel = new MoneyModel();
        $info = $MoneyModel->getUserMoney($uuid,'*');
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 修改用户资金
     */
    public function operation_save(){
        $param = input('post.');
        $result = $this->validate($param,'MoneyValidate.edit_money');
        if($result !== true){
            $this->error($result);
        }else{
            $MoneyModel = new MoneyModel();
            $user_money = $MoneyModel->getUserMoney($param['uuid']);
            switch ($param['type']){
                case 1://可用
                    if($param['state'] == 1){//增加

                        $res = $MoneyModel->getModifyMoney($param['uuid'],3,1,$param['save_money'],$param['detail'],'',4,$this->admin_id,$this->admin_name);
                    }elseif ($param['state'] == 2){ //减少
                        if($user_money['abc_coin'] >= $param['save_money']){
                            $res = $MoneyModel->getModifyMoney($param['uuid'],3,2,$param['save_money'],$param['detail'],'',4,$this->admin_id,$this->admin_name);
                        }else{
                            $res = ['code'=>1012,'msg'=>'可用金额不足'];
                        }
                    }
                    break;
                case 2://提速
                    if($param['state'] == 1){ //增加
                        $MemberModel = new MemberModel();
                        $ConfigCapital = ConfigCapital();
                        $user_detail = $MemberModel->where('uuid',$param['uuid'])->find();
                        if($user_detail['activation'] != 1 &&  $param['save_money'] >= $ConfigCapital['active_member']){
                            Db::name('member')->where('uuid',$user_detail['uuid'])->update(['activation'=>1]);
                            Db::query('CALL TeamNumber('.$user_detail['id'].')');
                        }
                        $res = $MoneyModel->getModifyMoney($param['uuid'],2,1,$param['save_money'],$param['detail'],'',4,$this->admin_id,$this->admin_name);
                    }elseif ($param['state'] == 2){ //减少

                        if($user_money['current'] >= $param['save_money']){
                        $res = $MoneyModel->getModifyMoney($param['uuid'],2,2,$param['save_money'],$param['detail'],'',4,$this->admin_id,$this->admin_name);
                        }else{
                            $res = ['code'=>1012,'msg'=>'提速金额不足'];
                        }
                    }
                    break;
                case 3://增值
                    if($param['state'] == 1){ //增加
                        $res = $MoneyModel->getModifyMoney($param['uuid'],5,1,$param['save_money'],$param['detail'],'',4,$this->admin_id,$this->admin_name);
                    }elseif ($param['state'] == 2){ //减少

                        if($user_money['current'] >= $param['save_money']){
                            $res = $MoneyModel->getModifyMoney($param['uuid'],5,2,$param['save_money'],$param['detail'],'',4,$this->admin_id,$this->admin_name);
                        }else{
                            $res = ['code'=>1012,'msg'=>'增值金额不足'];
                        }
                    }
                    break;
            }
            if($res['code'] == 1011){
                $this->success($res['msg']);
            }else{
                $this->error($res['msg']);
            }
        }
    }

    /**
     * 一代二代分润
     * @param $id 用户id
     * @param $money 进入定期金额
     */
    public function direct_distribution($id,$money){
        $member = new MemberModel();
        $MoneyModel = new MoneyModel();
        $config = ConfigCapital();
        $user_detail = $member->getOneMember($id);
        if ($user_detail['pid'] > 0){
            //一代
            $two_user = $member->getOneMember($user_detail['pid']);
            //符合 条件 发放  未冻结  账号激活
            if($two_user && $two_user['status'] == 1 && $two_user['is_proving'] == 1 && $two_user['activation']){
                //查询直推人数
                $two_count = Db::name('member')->where(['pid'=>$two_user['id'],'is_proving'=>1,'activation'=>1])->count();
                if($two_count >= $config['directpush_number']){
                    $two_release = $money*$config['directpush_bonus']/100;
                    $MoneyModel->getModifyMoney($two_user['uuid'],4,1,$two_release,'直接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6',$this->admin_id,$this->admin_name);

                }
            }
            //二代
            if($two_user && $two_user['pid'] > 0){
                $three_user = $member->getOneMember($two_user['pid']);
                if($three_user && $three_user['status'] == 1 && $two_user['is_proving'] == 1 && $three_user['activation']){
                    //查询直推人数
                    $three_count = Db::name('member')->where(['pid'=>$three_user['id'],'is_proving'=>1,'activation'=>1])->count();
                    if($three_count >= $config['indirect_number']){
                        $three_release = $money * $config['indirect_bonus']/100;
                        $MoneyModel->getModifyMoney($three_user['uuid'],4,1,$three_release,'直接推荐人'.$user_detail['account'].'买入定期分红',$user_detail['uuid'],'6',$this->admin_id,$this->admin_name);

                    }
                }
            }
        }

    }
}