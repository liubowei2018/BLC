<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 9:43
 */
namespace app\api\validate;
use think\Validate;
class UserValidate extends Validate
{
    protected $rule = [
        ['Sign','require','签名不能为空'],
        ['referee','require','推荐人不能为空'],
        ['account','require','登录账号不能为空'],
        ['login_pwd','require','登录密码不能为空'],
        ['pay_pwd','require','支付密码不能为空'],
        ['nick_name','require','用户名称不能为空'],
        ['idcard','require','身份证号不能为空'],
		['phone','require','手机号不能为空'],
        ['verify_code','require','验证码不能为空'],
        ['type','require','信息类型不能为空'],
        ['uuid','require','用户编号不能为空'],
        ['token','require','令牌不能为空'],
        ['name','require','请输入姓名'],
        ['idcard','require','请输入身份证号'],
        ['just_card','require','请上传身份证正面'],
        ['back_card','require','请上传身份证反面'],
    ];
    protected $scene = [
        'reg'=>['account','login_pwd','pay_pwd','referee','verify_code','Sign'],//用户注册
		'reg_word'=>['account','login_pwd','pay_pwd','referee','Sign'],//用户国外注册
        'login'=>['account','login_pwd','Sign'],
        'sms'=>['phone','type','Sign'],
        'add_user_auth'=>['name','idcard','just_card','back_card'],
        'common'=>['uuid','token','Sign'],
        'real_name'=>['uuid','token','Sign','name','idcard','just_card','back_card'],
    ];
}