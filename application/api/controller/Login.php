<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 9:23
 */

namespace app\api\controller;


use think\Controller;
use think\Cache;
use think\Db;
use app\api\model\MemberModel;
class Login extends Controller
{
    /**
     * 登陆
     */
    public function login(){
        $data = input('post.');
        $key = config('auth_key');
        $result = $this->validate($data,'UserValidate.login');
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        $Sign = getSign($data);
        if($Sign != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);
        $user = Db::name('member')->field('uuid,account,status,is_proving,nickname')->where(['account'=>$data['account'],'password'=>md5($data['login_pwd'].$key)])->find();
        $config = ConfigCapital();
        if($config['app_state'] != 1){
            return json(['code'=>1002,'msg'=>'系统维护中','data'=>[]]);
        }
        if($user){
            if($user['status'] != 1)   return json(['code'=>1002,'msg'=>'账号冻结中','data'=>[]]);
            $token = md5($user['uuid'].$key.time());
            Cache::set($user['uuid'],['token'=>$token],7200);
            $user['token']=$token;
            member_log($user['uuid'],$user['nickname'],'用户登录',1);
            return json(['code'=>1001,'msg'=>config('code.1001'),'data'=>$user]);
        }else{
            return json(['code'=>1002,'msg'=>'手机号或密码错误','data'=>[]]);
        }
    }
    /**
     *注册
     * 手机号 推荐人 验证码 一级密码  二级密码
     */
    public function register(){
        $data = input('post.');
        $key = config('auth_key');
        $result = $this->validate($data,'UserValidate.reg');
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        $Sign = getSign($data);
        if($Sign != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);
        if($data['verify_code'] != Cache::pull('zc_'.$data['account'])){
            return json(['code'=>1007,'msg'=>config('code.1007'),'data'=>'']);
        }
        $ConfigCapital = ConfigCapital();
        if($ConfigCapital['register_state'] != 1){
            return json(['code'=>1012,'msg'=>'注册暂未开放','data'=>'']);
        }
        $member = new MemberModel();
        $member_count = $member->member_count($data['account']);
        if($member_count >0 ) return json(['code'=>1012,'msg'=>'账号已注册','data'=>'']);
        $referee_user = Db::name('member')->where('account',$data['referee'])->find();
        if(!$referee_user) return json(['code'=>1012,'msg'=>'推荐人不存在','data'=>'']);
        Db::startTrans();
        try{
            //添加用户表
            $uuid = unique_num();
            $member->RegisterUser($uuid,$data['account'],$referee_user['id'],md5($data['login_pwd'].$key),md5($data['pay_pwd'].$key),1);
            $only_uuid = Db::query("SELECT REPLACE(UUID(), '-', '') as only");
            $money_path = Qrcode(time(),$only_uuid[0]['only']);
            Db::name('money')->insert(['uuid'=>$uuid]);
            Db::name('member_other')->insert(['uuid'=>$uuid,'wallet'=>$only_uuid[0]['only'],'money_path'=>$money_path,'share_path'=>'']);
            Db::commit();
            return json(['code'=>1011,'msg'=>'注册成功','data'=>'']);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>'']);
        }
    }
    /**
     *国外注册注册 无需验证码
     * 手机号 推荐人  一级密码  二级密码
     */
    public function abroad_register(){
        $data = input('post.');
        $key = config('auth_key');
        $result = $this->validate($data,'UserValidate.reg_word');
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        $Sign = getSign($data);
        if($Sign != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);
        $member = new MemberModel();
        $member_count = $member->member_count($data['account']);
        if($member_count >0 ) return json(['code'=>1012,'msg'=>'账号已注册','data'=>'']);
        $referee_user = Db::name('member')->where('account',$data['referee'])->find();
        if(!$referee_user) return json(['code'=>1012,'msg'=>'推荐人不存在','data'=>'']);
        Db::startTrans();
        try{
            //添加用户表
            $uuid = unique_num();
            $member->RegisterUser($uuid,$data['account'],$referee_user['id'],md5($data['login_pwd'].$key),md5($data['pay_pwd'].$key),2);
            $only_uuid = Db::query("SELECT REPLACE(UUID(), '-', '') as only");
            $money_path = Qrcode(time(),$only_uuid[0]['only']);
            Db::name('money')->insert(['uuid'=>$uuid]);
            Db::name('member_other')->insert(['uuid'=>$uuid,'wallet'=>$only_uuid[0]['only'],'money_path'=>$money_path,'share_path'=>'']);
            Db::commit();
            return json(['code'=>1011,'msg'=>'注册成功','data'=>'']);
        }catch (\Exception $exception){
            Db::rollback();
            return json(['code'=>1012,'msg'=>$exception->getMessage(),'data'=>'']);
        }
    }
    /**
     * 忘记密码
     * 手机号   验证码  密码
     */
    public function forget_login(){
        $data = input('post.');
        $result = $this->validate($data,'SafetyValidate.forget_pwd');//数据验证
        if($result !== true) return json(['code'=>1015,'msg'=>$result]);
        $Sign = getSign($data);//签名验证
        if($Sign != $data['Sign']) return json(['code'=>1013,'msg'=>config('code.1013')]);
        //正式数据
        if($data['verify_code'] != Cache::pull('pwd_'.$data['account'])) return json(['code'=>1007,'msg'=>config('code.1007')]);
        $user = Db::name('member')->where(['account'=>$data['account']])->find();
        if(!$user) return json(['code'=>1012,'msg'=>'未查询到用户信息','data'=>[]]);
        //验证通过
        $key = config('auth_key');
        $member = new MemberModel();
        $res = $member->ChangePassword($user['uuid'],1,md5($data['new_pwd'].$key));
        return json($res);
    }

}