<?php
/* * * * * * * * * * * * * * * * * * *
 * DNSPod Dual Wan DDNS TP-Link专版
 * Version: 0.4
 * Author: vibbow
 * Email: vibbow@gmail.com
 * * * * * * * * * * * * * * * * * * */

echo "DNSPod Dual Wan DDNS Client\r\n";
echo "--------------------\r\n";

/* 检测只能以cli的方式运行 */
if (PHP_SAPI != 'cli')
	exit("This client can only run in shall only");

/* 检测curl扩展是否存在 */
if (!extension_loaded('curl'))
	exit("Need to enable php-curl extension");

require('config.php');
require('function.base.php');

/* 从路由器获取网卡A的IP */
echo "Getting NIC 1 IP from Router ... ";
$nic_a_ip = tp_link_get_ip($nic_a_tplink_ip, $nic_a_tplink_username, $nic_a_tplink_password);
if (!$nic_a_ip)
	echo "Failed!\r\n";
else
	echo "{$nic_a_ip}\r\n";

/* 从路由器获取网卡B的IP */
echo "Getting NIC 2 IP from Router ... ";
$nic_b_ip = tp_link_get_ip($nic_b_tplink_ip, $nic_b_tplink_username, $nic_b_tplink_password);
if (!$nic_b_ip)
	echo "Failed!\r\n";
else
	echo "{$nic_b_ip}\r\n";

if (!$nic_a_ip && !$nic_b_ip)
	exit("Seems you didn't connect to Internet");

/* 设置默认IP以及默认网卡 */
$default_ip = $nic_a_ip ? $nic_a_ip : $nic_b_ip;
$default_interface = $nic_a_ip ? $nic_a_lan_ip : $nic_b_lan_ip;
define('NETINTERFACE', $default_interface);

/* 设置线路冗余 */
if (!$nic_a_ip)
	$nic_a_ip = $default_ip;
if (!$nic_b_ip)
	$nic_b_ip = $default_ip;

/* 从DNSPod获取旧的设置 */
$nic_a_ip_old = dnspod_get_ip($nic_a_record_id);
$nic_b_ip_old = dnspod_get_ip($nic_b_record_id);
if (empty($nic_a_ip_old) && empty($nic_b_ip_old))
	exit("Can not connect to DNSPod API");

/* 更新第一条线路IP记录 */
if ($nic_a_ip != $nic_a_ip_old) {
	echo "Update Line A record from {$nic_a_ip_old} to {$nic_a_ip} ...";
	$result = dnspod_update_ip($nic_a_record_id, $nic_a_ip, $nic_a_line);
	echo $result ? "Successed\r\n" : "Failed\r\n";
}

/* 更新第二条线路IP记录 */
if ($nic_b_ip != $nic_b_ip_old) {
	echo "Update Line B record from {$nic_b_ip_old} to {$nic_b_ip} ...";
	$result = dnspod_update_ip($nic_b_record_id, $nic_b_ip, $nic_b_line);
	echo $result ? "Successed\r\n" : "Failed\r\n";
}

echo "All done.";
?>