<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/28
 * Time: 11:32
 */

namespace app\common\model;


use think\Model;

class AdminLogModel extends Model
{
    protected $name='admin_log';

    /**
     * 添加管理操作日志
     * @param $tiele
     * @param $type
     * @param $data
     * @param $admin_id
     * @param $admin_name
     */
    public function getAddInfo($tiele,$type,$data,$admin_id,$admin_name){
        $res = $this->insert(['title'=>$tiele,'type'=>$type,'data'=>$data,'admin_id'=>$admin_id,'admin_name'=>$admin_name,'add_time'=>time()]);
        if($res){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 查询管理操作日志
     * @param $field
     * @param $map
     * @param $page
     * @param $row
     * @param $order
     */
    public function getLogLists($field,$map,$page,$row,$order){
        $count = $this->field($field)->where($map)->count();
        $lists = $this->field($field)->where($map)->page($page,$row)->order($order)->select();
        foreach ($lists as $k=>$v){
            $lists[$k]['data'] =$this->getDataDictionaries($v['data']);
        }
        return ['count'=>$count,'lists'=>$lists];
    }

    /**
     * 数据字典
     * @param $data
     */
    public function getDataDictionaries($data){
        $dictionaries = [
            'account'=>'账号',
            'nickname'=>'姓名',
            'uuid'=>'用户编号',
            'order_number'=>'订单号',
            'add_time'=>'订单创建时间',
            'number'=>'操作数量',
            'state'=>'状态',
        ];
        foreach ($dictionaries as $k=>$v) {
            $data = str_replace($k,$v,$data);
        }
        $data = str_replace('{','',$data);
        $data = str_replace('}','',$data);
        $data = str_replace('"','',$data);
        return $data;
    }
}