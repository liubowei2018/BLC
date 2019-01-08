<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 9:43
 */
namespace app\api\validate;
use think\Validate;
class MoneyValidate extends Validate
{
    protected $rule = [
        ['Sign','require','签名不能为空'],
        ['uuid','require','用户编号不能为空'],
        ['token','require','令牌不能为空'],
        ['number','require','数量不能为空'],
        ['img_path','require','凭证不能为空'],
        ['trade','require','交易ID不能为空'],
        ['type','require','类型不能为空'],
        ['pay_pwd','require','交易密码不能为空'],
    ];
    protected $scene = [
        'abc_recharge'=>['Sign','uuid','token','number','img_path','trade','type','pay_pwd'],
    ];
}