<?php
use think\Db;
use think\Cache;
use Aliyun\Core\Config;  
use Aliyun\Core\Profile\DefaultProfile;  
use Aliyun\Core\DefaultAcsClient;  
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use think\Loader;
/**
 * 字符串截取，支持中文和其他编码
 */
function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true) {
	if (function_exists("mb_substr"))
		$slice = mb_substr($str, $start, $length, $charset);
	elseif (function_exists('iconv_substr')) {
		$slice = iconv_substr($str, $start, $length, $charset);
		if (false === $slice) {
			$slice = '';
		}
	} else {
		$re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
		$re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
		$re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
		$re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
		preg_match_all($re[$charset], $str, $match);
		$slice = join("", array_slice($match[0], $start, $length));
	}
	return $suffix ? $slice . '...' : $slice;
}



/**
 * 读取配置
 * @return array 
 */
function load_config(){
    $list = Db::name('config')->select();
    $config = [];
    foreach ($list as $k => $v) {
        $config[trim($v['name'])]=$v['value'];
    }

    return $config;
}


/**
* 验证手机号是否正确
* @author honfei
* @param number $mobile
*/
function isMobile($mobile) {
    if (!is_numeric($mobile)) {
        return false;
    }
    return preg_match('#^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$#', $mobile) ? true : false;
}


/** 
 * 阿里云云通信发送短息 
 * @param string $mobile    接收手机号 
 * @param string $tplCode   短信模板ID
 * @param array  $tplParam  短信内容
 * @return array 
 */  
function sendMsg($mobile,$tplCode,$tplParam){  
    if( empty($mobile) || empty($tplCode) ) return array('Message'=>'缺少参数','Code'=>'Error');  
    if(!isMobile($mobile)) return array('Message'=>'无效的手机号','Code'=>'Error');  
      
    require_once '../extend/aliyunsms/vendor/autoload.php';  
    Config::load();             //加载区域结点配置   
    $accessKeyId = config('alisms_appkey');  
    $accessKeySecret = config('alisms_appsecret');  
    if( empty($accessKeyId) || empty($accessKeySecret) ) return array('Message'=>'请先在后台配置appkey和appsecret','Code'=>'Error'); 
    $templateParam = $tplParam; //模板变量替换  
	
	//$signName = (empty(config('alisms_signname'))?'阿里大于测试专用':config('alisms_signname'));  
	$signName = config('alisms_signname');
    //短信模板ID 
    $templateCode = $tplCode;   
    //短信API产品名（短信产品名固定，无需修改）  
    $product = "Dysmsapi";  
    //短信API产品域名（接口地址固定，无需修改）  
    $domain = "dysmsapi.aliyuncs.com";  
    //暂时不支持多Region（目前仅支持cn-hangzhou请勿修改）  
    $region = "cn-hangzhou";     
    // 初始化用户Profile实例  
    $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);  
    // 增加服务结点  
    DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", $product, $domain);  
    // 初始化AcsClient用于发起请求  
    $acsClient= new DefaultAcsClient($profile);  
    // 初始化SendSmsRequest实例用于设置发送短信的参数  
    $request = new SendSmsRequest();  
    // 必填，设置雉短信接收号码  
    $request->setPhoneNumbers($mobile);  
    // 必填，设置签名名称  
    $request->setSignName($signName);  
    // 必填，设置模板CODE  
    $request->setTemplateCode($templateCode);  
    // 可选，设置模板参数     
    if($templateParam) {
        $request->setTemplateParam(json_encode($templateParam));
    }
    //发起访问请求  
    $acsResponse = $acsClient->getAcsResponse($request);   
    //返回请求结果  
    $result = json_decode(json_encode($acsResponse),true); 

    return $result;  
}



//生成网址的二维码 返回图片地址
function Qrcode($token, $url, $size = 8){
    vendor('phpqrcode.phpqrcode');
    $md5 = md5($token);
    $dir = date('Ymd'). '/' . substr($md5, 0, 10) . '/';
    $patch = 'qrcode/' . $dir;
    if (!file_exists($patch)){
        mkdir($patch, 0755, true);
    }
    $file = 'qrcode/' . $dir . $md5 . '.png';
    $fileName =  $file;
    if (!file_exists($fileName)) {

        $level = 'L';
        $data = $url;
        \QRcode::png($data, $fileName, $level, $size, 2, true);
    }
    return $file;
}



/**
 * 循环删除目录和文件
 * @param string $dir_name
 * @return bool
 */
function delete_dir_file($dir_name) {
    $result = false;
    if(is_dir($dir_name)){
        if ($handle = opendir($dir_name)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    if (is_dir($dir_name . DS . $item)) {
                        delete_dir_file($dir_name . DS . $item);
                    } else {
                        unlink($dir_name . DS . $item);
                    }
                }
            }
            closedir($handle);
            if (rmdir($dir_name)) {
                $result = true;
            }
        }
    }

    return $result;
}



//时间格式化1
function formatTime($time) {
    $now_time = time();
    $t = $now_time - $time;
    $mon = (int) ($t / (86400 * 30));
    if ($mon >= 1) {
        return '一个月前';
    }
    $day = (int) ($t / 86400);
    if ($day >= 1) {
        return $day . '天前';
    }
    $h = (int) ($t / 3600);
    if ($h >= 1) {
        return $h . '小时前';
    }
    $min = (int) ($t / 60);
    if ($min >= 1) {
        return $min . '分钟前';
    }
    return '刚刚';
}


//时间格式化2
function pincheTime($time) {
     $today  =  strtotime(date('Y-m-d')); //今天零点
      $here   =  (int)(($time - $today)/86400) ; 
      if($here==1){
          return '明天';  
      }
      if($here==2) {
          return '后天';  
      }
      if($here>=3 && $here<7){
          return $here.'天后';  
      }
      if($here>=7 && $here<30){
          return '一周后';  
      }
      if($here>=30 && $here<365){
          return '一个月后';  
      }
      if($here>=365){
          $r = (int)($here/365).'年后'; 
          return   $r;
      }
     return '今天';
}


function getRandomString($len, $chars=null){
    if (is_null($chars)){
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }  
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
        $str .= $chars[mt_rand(0, $lc)];  
    }
    return $str;
}


function random_str($length){
    //生成一个包含 大写英文字母, 小写英文字母, 数字 的数组
    $arr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
 
    $str = '';
    $arr_len = count($arr);
    for ($i = 0; $i < $length; $i++)
    {
        $rand = mt_rand(0, $arr_len-1);
        $str.=$arr[$rand];
    }
 
    return $str;
}
/**
 * @param $uuid  用户uuid
 * @param $nickname  用户昵称
 * @param $info   详情
 * @param $state   状态
 */
function member_log($uuid,$nickname,$info,$state){
    $data['uuid'] = $uuid;
    $data['nickname'] = $nickname;
    $data['info'] = $info;
    $data['state'] = $state;
    $data['ip'] = request()->ip();
    $data['time'] = time();
    $log = Db::name('member_log')->insert($data);
}

function getSign($arr)
{
    $key = '0f4137ed1502b5045d6083aa258b5c42';
    //去除数组中的空值
    foreach ($arr as $k=>$v){
        if($v == ''){
            unset($arr[$k]);
        }
    }
    //如果数组中有签名删除签名
    if(isset($arr['Sign']))
    {
        unset($arr['Sign']);
    }
    //按照键名字典排序
    ksort($arr);
    //生成URL格式的字符串
    //http_build_query()中文自动转码需要处理下
    $str1 = http_build_query($arr);
    $str1 = urldecode($str1).'&key='.$key;
    return  md5($str1);
}
//URL解码为中文
function arrToUrl($str){
    return urldecode($str);
}
//返回毫秒时间
function getcurrentmilis(){
    $mill_time = microtime();
    $timeInfo = explode(' ', $mill_time);
    $milis_time = sprintf('%d%03d',$timeInfo[1],$timeInfo[0] * 1000);
    return $milis_time;

}

/**
 * 获取随机一条uuid 并修改信息状态
 */
function unique_num(){
    $num = Db::query('SELECT * FROM think_unique_number WHERE id >= ((SELECT MAX(id) FROM think_unique_number)-(SELECT MIN(id) FROM think_unique_number)) * RAND() + (SELECT MIN(id) FROM think_unique_number)AND state=1 LIMIT 1');
    $num = $num[0];
    Db::name('unique_number')->where('id',$num['id'])->update(['state'=>2]);
    return $num['number'];
}
/**
 * 获取域名和端口
 */
function GetDomainName(){
    $str = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"];
    return $str;
}

/**
 * 查询父级账号
 * @param $pid
 * @return mixed
 */
function QueryParent($pid){
    $parent = Db::name('member')->where('id',$pid)->value('account');
    return $parent;
}

function ConfigCapital(){
    $array = Cache::get('ConfigCapital');
    if($array){
        return $array;
    }else{
        $list = Db::name('config_capital')->select();
        $config = [];
        foreach ($list as $k => $v) {
            $config[trim($v['name'])]=$v['value'];
        }
        Cache::set('ConfigCapital',$config,7200);
        return $config;
    }
}
/*
 * curl post请求 访问https
 *
 * */
function curl_post_https($url,$data){ // 模拟提交数据函数
    $curl = curl_init(); // 启动一个CURL会话
    curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2); // 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 0); // 使用自动跳转
    curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
    curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
    curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
    curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
    $tmpInfo = curl_exec($curl); // 执行操作
    if (curl_errno($curl)) {
        echo 'Errno'.curl_error($curl);//捕抓异常
    }
    curl_close($curl); // 关闭CURL会话
    return $tmpInfo; // 返回数据，json格式
}