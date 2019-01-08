<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 10:45
 */

namespace app\api\model;


use think\Model;

class MemberModel extends Model
{
    protected $name = 'member';
    /**
     * 查询用户账号 是否重复
     * @param $account
     * @return int|string
     */
    public function member_count($account){
        $count= $this->where('account',$account)->count();
        return $count;
    }

    /**
     * 查询用户信息
     * @param $uuid
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getUserDetail($uuid,$field='*'){
        $info = $this->field($field)->where('account|uuid',$uuid)->find();
        return $info;
    }

    /**
     * 根据id查询信息
     * @param $id
     */
    public function getUserOne($id){
        return $this->where('id',$id)->find();
    }
    /**
     * 获取用户信息
     * @param $id
     * @param string $field
     * @return array|false|\PDOStatement|string|Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getQueryIdDetail($id,$field='*'){
        $info = $this->field($field)->where('id',$id)->find();
        return $info;
    }
    /**
     * 用户注册
     * @param $uuid 唯一编号
     * @param $account 账号
     * @param $pid 推荐人id
     * @param $login_pwd 登陆密码
     * @param $pay_pwd 支付密码
     * @param $abroad 2 国外注册，1 国内注册
     * @return int|string
     */
    public function RegisterUser($uuid,$account,$pid,$login_pwd,$pay_pwd,$abroad){
        $data = [
            'uuid'=>$uuid,
            'account'=>$account,
            'pid'=>$pid,
            'password'=>$login_pwd,
            'pay_password'=>$pay_pwd,
            'mobile'=>$account,
            'create_time'=>time(),
            'status'=>1,
            'is_proving'=>0,
            'abroad'=>$abroad,
        ];
        $res = $this->insert($data);
        return $res;
    }

    /**
     * 修改用户登陆 二级密码
     * @param $uuid
     * @param $type
     * @param $password MD5
     * @return array
     * @throws \think\exception\PDOException
     */
    public function ChangePassword($uuid,$type,$password){
        $this->startTrans();
        try{
            switch ($type){
                case 1://登陆密码
                    $this->where('uuid',$uuid)->update(['password'=>$password]);
                    break;
                case 2://支付密码
                    $this->where('uuid',$uuid)->update(['pay_password'=>$password]);
                    break;
            }
            $this->commit();
            return ['code'=>1011,'msg'=>'修改成功','data'=>''];
        }catch (\Exception $exception){
            $this->rollback();
            return ['code'=>1012,'msg'=>$exception->getMessage(),'data'=>''];
        }
    }

    /**
     * 验证支付密码
     * @param $uuid
     * @param $pwd
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getValidatePwd($uuid,$pwd){
        $user = $this->where('uuid',$uuid)->find();
        $key = config('auth_key');
        if($user['pay_password'] == md5($pwd.$key)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 编辑用户姓名和身份证号
     * @param $uuid
     * @param $name
     * @param $idcard
     */
    public function getEditIdcard($uuid,$name,$idcard){
        $this->startTrans();
        try{
            $this->where('uuid',$uuid)->update(['nickname'=>$name,'idcard'=>$idcard,'is_proving'=>1]);
            $this->commit();
            return ['code'=>1011,'msg'=>'认证成功','data'=>''];
        }catch (\PDOException $e){
            $this->rollback();
            return ['code'=>1012,'msg'=>'认证失败','data'=>''];
        }
    }

    /**
     * 验证用户是否可以使用
     * @param $uuid
     * @param $data
     */
    public function getUserState($uuid,$data){
        $user = $this->where('uuid',$uuid)->find();
        if($user['status'] != 1){
            return ['code'=>1012,'msg'=>'账户已冻结','data'=>$data];
        }elseif ($user['is_proving'] != 1){
            return ['code'=>1012,'msg'=>'账户未实名认证','data'=>$data];
        }else{
            return true;
        }
    }

    /**
     * 查询自身队列
     * @param $userid
     * @param $page
     * @param $row
     */
    public function getTeamLists($userid,$page,$row){
        $count = $this->field('account,is_proving,activation,create_time,create_time')->where('pid',$userid)->count();
        $lists = $this->field('account,is_proving,activation,create_time,create_time')->where('pid',$userid)->page($page,$row)->order('activation DESC,is_proving DESC,create_time DESC ')->select();
        return ['count'=>$count,'lists'=>$lists];
    }
}