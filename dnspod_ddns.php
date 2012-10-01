<?php
define('USERNAME', '用户名');				//DNSPod 用户名
define('PASSWORD', '密码');					//DNSPod 密码
define('DOMAIN_ID', '域名ID');				//DNSPod 域名ID
define('SUB_DOMAIN', '主机记录');			//用于DDNS的主机记录  (例: abc.domain.com 的主机记录为 abc)
define('IP_API', 'http://ip.vsean.net/');	//用于获取IP地址的接口

$dx_interface = '电信网卡IP';							//电信线路 网卡IP
$lt_interface = '联通网卡IP';							//联通线路 网卡IP
$record_id = array('记录ID1', '记录ID2', '记录ID3');	//用于DDNS操作的三条记录ID（依次为默认线路，电信线路，联通线路）

/*  需要自定义的内容到此为止，以下内容均无需更改  */

//限制只能以命令行方式运行
if (PHP_SAPI != 'cli') die ('Shell only');
echo "DNSPod Dual Wan DDNS Client\r\n";

//cUrl扩展检测
if (!extension_loaded('curl')) die ("Need to enable php-curl extension");

//获取双线IP，如果均获取失败则退出
$dx_ip = get_ip($dx_interface);
$lt_ip = get_ip($lt_interface);
if (empty($dx_ip) && empty($lt_ip)) die ("Can not connect to IP API");

//设置默认IP以及默认网卡
$default_ip = !empty($dx_ip) ? $dx_ip : $lt_ip;
$default_interface = !empty($dx_ip) ? $dx_interface : $lt_interface;
define('NETINTERFACE', $default_interface);

//设置线路冗余
if (empty($dx_ip)) $dx_ip = $default_ip;
if (empty($lt_ip)) $lt_ip = $default_ip;

//获取DNSPod设置
$default_ip_old = dnspod_get_ip($record_id[0]);
$dx_ip_old = dnspod_get_ip($record_id[1]);
$lt_ip_old = dnspod_get_ip($record_id[2]);
if (empty($default_ip_old) && empty($dx_old_ip) && empty($lt_old_ip)) die ("Can not connect to DNSPod API");

//更新默认线路IP记录
if ($default_ip != $default_ip_old) {
	echo "Update default record from {$default_ip_old} to {$default_ip} ... ";
	$result = dnspod_update_ip($record_id[0], $default_ip, '默认');
	echo $result ? "Successed\r\n" : "Failed\r\n";
}

//更新电信线路IP记录
if ($dx_ip != $dx_ip_old) {
	echo "Update Telecom record from {$dx_ip_old} to {$dx_ip} ... ";
	$result = dnspod_update_ip($record_id[1], $dx_ip, '电信');
	echo $result ? "Successed\r\n" : "Failed\r\n";
}

//更新联通线路IP记录
if ($lt_ip != $lt_ip_old) {
	echo "Update Unicom record from {$lt_ip_old} to {$lt_ip} ... ";
	$result = dnspod_update_ip($record_id[2], $lt_ip, '联通');
	echo $result ? "Successed\r\n" : "Failed\r\n";
}

echo "All done.";

//指定网卡获取IP函数
//输入：网卡的内网静态IP地址
//输出：网卡的外网地址或者FALSE
function get_ip($interface) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, IP_API);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_INTERFACE, $interface);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP DDNS Client/0.3 (vibbow@gmail.com)');
	$result = curl_exec($ch);
	curl_close($ch);
	
	$ip = ip2long($result) === false ? false : $result;
	return $ip;
}

//获取DNSPod IP记录
//输入：DNSPod记录ID
//输出：记录ID对应的IP或者FALSE
function dnspod_get_ip($record_id) {
	$url = 'https://dnsapi.cn/Record.Info';
	$post = array(
		'login_email'		=>	USERNAME,
		'login_password'	=>	PASSWORD,
		'format'			=>	'json',
		'lang'				=>	'cn',
		'error_on_empty'	=>	'no',
		'domain_id'			=>	DOMAIN_ID,
		'record_id'			=>	$record_id,
	);
	$json_result = curl_post($url, $post);
	$array_result = json_decode($json_result, true);
	$ip = $array_result['record']['value'];
	if (ip2long($ip) !== false) {return $ip;}
	else {return false;}
}

//更新DNSPod IP记录
//输入：DNSPod记录ID，更新的IP地址，线路
//输出：更新结果（Bool）
function dnspod_update_ip($record_id, $ip, $line) {
	$url = 'https://dnsapi.cn/Record.Modify';
	$post = array(
		'login_email'		=>	USERNAME,
		'login_password'	=>	PASSWORD,
		'format'			=>	'json',
		'lang'				=>	'cn',
		'error_on_empty'	=>	'no',
		'domain_id'			=>	DOMAIN_ID,
		'sub_domain'		=>	SUB_DOMAIN,
		'record_id'			=>	$record_id,
		'record_type'		=>	'A',
		'record_line'		=>	$line,
		'value'				=>	$ip,
		'ttl'				=>	'120'
	);
	$json_result = curl_post($url, $post);
	$array_result = json_decode($json_result, true);
	$result = $array_result['status']['code'];
	return $result == 1;
}

//CURL POST操作函数
//输入：URL地址，POST数据
//输出，返回内容或者FALSE
function curl_post($url, $post) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP DDNS Client/0.3 (vibbow@gmail.com)');
	curl_setopt($ch, CURLOPT_INTERFACE, NETINTERFACE);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
?>