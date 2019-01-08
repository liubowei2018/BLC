<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/5
 * Time: 11:30
 */

namespace app\index\validate;


use think\Validate;

class LoginValidate extends Validate
{
    protected $rule = [
        ['account','require','手机号不能为空不'],
        ['login_pwd','require','登陆密码不能为空'],
        ['pay_pwd','require','支付密码不能为空'],
        ['verify_code','require','请输入短信验证码'],
        ['invite','require','请输入推荐人账号'],
    ];

    protected $scene = [
        'sms'=>['account','type'],
        'login'=>['account','login_pwd','pay_pwd','verify_code','invite'],
        'foreign_register'=>['account','login_pwd','pay_pwd','invite'],
    ];
}