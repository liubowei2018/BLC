<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 13:26
 */

namespace app\api\validate;


use think\Validate;

class SafetyValidate extends Validate
{
    protected $rule = [
        ['Sign','require','签名不能为空'],
        ['token','require','令牌不能为空'],
        ['account','require','手机号不能为空'],
        ['uuid','require','用户编号不能为空'],
        ['new_pwd','require','新密码不能为空'],
        ['old_pwd','require','原始密码不能为空'],
        ['type','require','类型不能为空'],
        ['money','require','转换金币不能为空'],
        ['page','require','分页不能为空'],
        ['path','require','钱包地址不能为空'],
        ['verify_code','require','验证码不能为空'],
        ['number','require','操作数量不能为空']
    ];
    protected $scene = [
        'revise'=>['Sign','token','uuid','new_pwd','old_pwd','type'],
        'modify_pwd'=>['Sign','token','uuid','new_pwd','old_pwd'],
        'forget_pwd'=>['Sign','new_pwd','account'],
        'user'=>['Sign','token','uuid'],
        'convert'=>['Sign','token','uuid','money'],
        'article'=>['Sign','token','uuid','type','page'],
        'currency'=>['Sign','token','uuid'],//通用验证  TimeStamp
        'abc_path'=>['Sign','token','uuid','path'],//通用验证  TimeStamp
        'abc_tixian'=>['Sign','token','uuid','verify_code','number']//可用体现
    ];
}