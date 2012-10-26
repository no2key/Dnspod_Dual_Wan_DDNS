<?php
function tp_link_get_ip($ip = '192.168.1.1', $username = 'admin', $password = 'admin') {
	$address = "http://{$ip}/userRpm/StatusRpm.htm";
	$userpwd = "{$username}:{$password}";

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $address);
	curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	$result = curl_exec($ch);
	curl_close($ch);

	$keyword = 'var wanPara = new Array(';
	$start_pos = strpos($result, $keyword) + strlen($keyword);
	$end_pos = strpos($result, ');', $start_pos);
	$content_length = $end_pos - $start_pos;
	$content = substr($result, $start_pos, $content_length);

	$content_array = explode(',', $content);
	$content_array = str_replace('"', '', $content_array);
	foreach ($content_array as $key => $value)
		$content_array[$key] = trim($value);

	$ip = $content_array[2];
	if ($ip == '0.0.0.0')
		return FALSE;
	else if (!ip2long($ip))
		return FALSE;
	else
		return $ip;
}

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
	if (ip2long($ip) !== false)
		return $ip;
	else
		return FALSE;
}

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
	$array_result = json_decode($json_result, TRUE);
	$result = $array_result['status']['code'];

	return $result == 1;
}

function curl_post($url, $post) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_TIMEOUT, 5);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_USERAGENT, 'PHP DDNS Client/0.4 (vibbow@gmail.com)');
	curl_setopt($ch, CURLOPT_INTERFACE, NETINTERFACE);
	curl_setopt($ch, CURLOPT_POST, TRUE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}
?>