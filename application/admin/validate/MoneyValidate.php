<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/29
 * Time: 21:24
 */

namespace app\admin\validate;


use think\Validate;

class MoneyValidate extends Validate
{
    protected $rule = [
        ['type','require','请选择充值类型'],
        ['save_money','require|min:1','请输入操作数量|操作数量最小为1'],
        ['state','require','请选择操作状态'],
        ['uuid','require','用户信息不能为空'],
    ];

    protected $scene = [
        'edit_money'=>['save_money','type','state','uuid']
        ];
}