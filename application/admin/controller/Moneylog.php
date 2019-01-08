<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 9:18
 */

namespace app\admin\controller;


use app\common\model\AdminLogModel;
use app\common\model\MoneyLogModel;

class Moneylog extends Base
{
    /**
     * 可用和提现值流水
     */
    public function index(){

        return $this->fetch();
    }
    //分红值记录
    public function index1(){

        return $this->fetch();
    }
    //查询记录
    public function query_log(){
        $key = input('key');
        $state = input('param.state');
        $type = input('param.type');
         $classify = input('param.classify');
        $stare_time = input('param.stare_time');
        $end_time = input('param.end_time');
        $map = [];
        if($key&&$key!==""){
            $map['m.account|m.uuid'] = $key;
        }
        if($state === 0 || !empty($state)){
            $map['r.state'] = $state;
        }
        if(!empty($type)){
            $map['r.type'] = $type;
        }else{
            $map['r.type'] = ['<>',4];
        }
       if(!empty($classify)){
            $map['r.classify'] = $classify;
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
       
        $MoneyLog = new MoneyLogModel();
        $info = $MoneyLog->getQueryLog('r.*,FROM_UNIXTIME(r.add_time) as add_time,m.account,m.nickname',$map,$page,$rows,'add_time DESC');
        $data['list'] = $info['lists'];
        $data['count'] = $info['count'];
        $data['page'] = $page;
        return json($data);
    }
    public function query_log2(){
        $key = input('key');
        $state = input('param.state');
        $type = input('param.type');
        $classify = input('param.classify');
        $stare_time = input('param.stare_time');
        $end_time = input('param.end_time');
        $map = [];
        if($key&&$key!==""){
            $map['m.account|m.uuid'] = $key;
        }
        if(!empty($classify)){
            $map['r.classify'] = $classify;
        }
        if(!empty($type)){
            $map['r.type'] = $type;
        }else{
             $map['r.type'] = 4;
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
       
        $MoneyLog = new MoneyLogModel();
        $info = $MoneyLog->getQueryLog('r.*,FROM_UNIXTIME(r.add_time) as add_time,m.account,m.nickname',$map,$page,$rows,'add_time DESC');
        $data['list'] = $info['lists'];
        $data['count'] = $info['count'];
        $data['page'] = $page;
        return json($data);
    }
    /**
     * 管理操作记录
     */
    public function admin_log(){

        return $this->fetch();
    }
    //查询记录
    public function query_admin_log(){
        $key = input('key');
        $state = input('param.state');
        $type = input('param.type');
        $stare_time = input('param.stare_time');
        $end_time = input('param.end_time');
        $map = [];
        if($key&&$key!==""){
            $map['account|m.uuid'] = $key;
        }
        if($state === 0 || !empty($state)){
            $map['state'] = $state;
        }
        if(!empty($type)){
            $map['type'] = $type;
        }
        if(!empty($stare_time)){
            $stare_time = $stare_time.' 00:00:00';
            $map['add_time'] = ['>= time',$stare_time];
        }
        if(!empty($end_time)){
            $end_time = $end_time.' 23:59:59';
            $map['add_time'] = ['<= time',$end_time];
        }
        if(!empty($stare_time) && !empty($end_time)){
            $map['add_time'] = ['between time',[$stare_time,$end_time]];
        }
        $page = input('get.page') ? input('get.page'):1;
        $rows = input('get.rows');// 获取总条数

        $MoneyLog = new AdminLogModel();
        $info = $MoneyLog->getLogLists('*,FROM_UNIXTIME(add_time) as add_time',$map,$page,$rows,'add_time DESC');
        $data['list'] = $info['lists'];
        $data['count'] = $info['count'];
        $data['page'] = $page;
        return json($data);
    }
}