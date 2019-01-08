<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/4
 * Time: 14:24
 */

namespace app\api\validate;


use think\Validate;

class ApiValidate extends Validate
{
    protected $rule = [
    ['Sign','require','签名不能为空'],
    ['uuid','require','用户编号不能为空'],
    ['token','require','令牌不能为空'],
    ];
    protected $scene = [
        'index'=>['Sign','uuid','token'],
    ];
}