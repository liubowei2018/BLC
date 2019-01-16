<?php
use think\Db;

/**
 * 将字符解析成数组
 * @param $str
 */
function parseParams($str)
{
    $arrParams = [];
    parse_str(html_entity_decode(urldecode($str)), $arrParams);
    return $arrParams;
}


/**
 * 子孙树 用于菜单整理
 * @param $param
 * @param int $pid
 */
function subTree($param, $pid = 0)
{
    static $res = [];
    foreach($param as $key=>$vo){

        if( $pid == $vo['pid'] ){
            $res[] = $vo;
            subTree($param, $vo['id']);
        }
    }

    return $res;
}


/**
 * 记录日志
 * @param  [type] $uid         [用户id]
 * @param  [type] $username    [用户名]
 * @param  [type] $description [描述]
 * @param  [type] $status      [状态]
 * @return [type]              [description]
 */
function writelog($uid,$username,$description,$status)
{

    $data['admin_id'] = $uid;
    $data['admin_name'] = $username;
    $data['description'] = $description;
    $data['status'] = $status;
    $data['ip'] = request()->ip();
    $data['add_time'] = time();
    $log = Db::name('Log')->insert($data);

}


/**
 * 整理菜单树方法
 * @param $param
 * @return array
 */
function prepareMenu($param)
{
    $parent = []; //父类
    $child = [];  //子类

    foreach($param as $key=>$vo){

        if($vo['pid'] == 0){
            $vo['href'] = '#';
            $parent[] = $vo;
        }else{
            $vo['href'] = url($vo['name']); //跳转地址
            $child[] = $vo;
        }
    }

    foreach($parent as $key=>$vo){
        foreach($child as $k=>$v){

            if($v['pid'] == $vo['id']){
                $parent[$key]['child'][] = $v;
            }
        }
    }
    unset($child);
    return $parent;
}


/**
 * 格式化字节大小
 * @param  number $size      字节数
 * @param  string $delimiter 数字和单位分隔符
 * @return string            格式化后的带单位的大小
 */
function format_bytes($size, $delimiter = '') {
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    for ($i = 0; $size >= 1024 && $i < 5; $i++) {
        $size /= 1024;
    }
    return $size . $delimiter . $units[$i];
}

/**
 * 获得表格
 * @param $array
 * @throws PHPExcel_Exception
 * @throws PHPExcel_Reader_Exception
 * @throws PHPExcel_Writer_Exception
 */
function getExcelList($array){
    //Excel测试
    //$path = dirname(__FILE__); //找到当前脚本所在路径
    \think\Loader::import('PHPExcel.Classes.PHPExcel');//手动引入PHPExcel.php
    \think\Loader::import('PHPExcel.Classes.PHPExcel.IOFactory.PHPExcel_IOFactory');//引入IOFactory.php 文件里面的PHPExcel_IOFactory这个类
    $PHPExcel = new \PHPExcel();//实例化
    $PHPSheet = $PHPExcel->getActiveSheet();
    $PHPSheet->setTitle("demo"); //给当前活动sheet设置名称
    /*        $PHPSheet->setCellValue("A1","姓名")->setCellValue("B1","分数");//表格数据
            $PHPSheet->setCellValue("A2","张三")->setCellValue("B2","2121");//表格数据*/
    $PHPSheet->fromArray($array);
    $PHPWriter = \PHPExcel_IOFactory::createWriter($PHPExcel,"Excel2007");//创建生成的格式
    header('Content-Disposition: attachment;filename="表单数据.xlsx"');//下载下来的表格名
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $PHPWriter->save("php://output"); //表示在$path路径下面生成demo.xlsx文件
}

/**
 * 转换表格所需参数
 * @param $strArray 行
 * @param $array    列
 * @return array
 */
function getExcelArray($strArray,$array){
    $nbspArray = [];
    $two_array = array();
    $two_array[0] = $nbspArray;
    $two_array[1] = $strArray;
    foreach($array as $k=>$v){
        $two_array[$k+2] = $v;
    }
    return $two_array;
}