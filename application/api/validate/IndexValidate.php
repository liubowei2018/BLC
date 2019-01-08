<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 9:43
 */
namespace app\api\validate;
use think\Validate;
class IndexValidate extends Validate
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