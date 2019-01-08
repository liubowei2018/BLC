<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 22:07
 */

namespace app\common\model;


use think\Model;

class UserAuth extends Model
{
    protected $name='user_auth';
    /**
     * 添加 实名认证信息
     * @param $uuid
     * @param $name
     * @param $idcard
     * @param $just_card
     * @param $back_card
     * @param $detail
     */
    public function getAddInfo($uuid,$name,$idcard,$just_card,$back_card,$detail=''){
        $this->startTrans();
        try{
            $data = [
                'uuid'=>$uuid,
                'name'=>$name,
                'idcard'=>$idcard,
                'just_card'=>$just_card,
                'back_card'=>$back_card,
                'state'=>1,
                'add_time'=>time(),
            ];
            $this->insert($data);
            $this->commit();
            return ['code'=>1011,'msg'=>'实名认证申请成功','data'=>$detail];
        }catch (\Exception $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>'实名认证申请失败','data'=>$detail];
        }
    }

    /**
     * 查询用户是否
     * @param $uuid
     * @param $field
     */
    public function getExamineState($uuid,$field='*'){
        $info = $this->field($field)->where(['uuid'=>$uuid])->order('id DESC')->find();
        switch ($info['state']){
            case 1://未审核
                return ['code'=>1013,'msg'=>'实名认证未审核','data'=>$info];
                break;
            case 2://已审核
                return ['code'=>1011,'msg'=>'已通过实名认证','data'=>$info];
                break;
            case 3://已驳回
                return ['code'=>1014,'msg'=>'实名认证申请已驳回','data'=>$info];
                break;
            default://未申请
                return ['code'=>1012,'msg'=>'暂无认证申请','data'=> ['name'=>'','idcard'=>'','just_card'=>'','back_card'=>'','info'=>'']];
        }
    }
    /**
     * 查询认证列表
     * @param $field
     * @param $map
     * @param $page
     * @param $row
     * @param $order
     */
    public function getQueryList($field,$map,$page,$row,$order){
        $count = $this->alias('u')->where($map)->join('member m','m.uuid=u.uuid')->count();
        $lists = $this->alias('u')->field($field)->where($map)->join('member m','m.uuid=u.uuid')->order($order)->paginate($row,false,['query'=>$map]);
        return ['count'=>$count,'lists'=>$lists];
    }

    /**
     * 查询用户审核状态
     * @param $uuid
     */
    public function getApplyState($uuid){
        $get_last_list = $this->where('uuid',$uuid)->order('id DESC')->find();
        if($get_last_list){
            if($get_last_list['state'] === 1){
                return 2;
            }else{
                return 0;
            }
        }else{
            return 0;
        }
    }
}