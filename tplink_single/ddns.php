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

/* 从路由器获取网卡的IP */
echo "Getting IP from Router ... ";
$nic_ip = tp_link_get_ip($nic_tplink_ip, $nic_tplink_username, $nic_tplink_password);
if (!$nic_ip)
	exit("Failed!");
else
	echo "{$nic_ip}\r\n";

/* 从DNSPod获取旧的设置 */
$nic_ip_old = dnspod_get_ip($nic_record_id);
if (empty($nic_ip_old))
	exit("Can not connect to DNSPod API");

/* 更新第一条线路IP记录 */
if ($nic_ip != $nic_ip_old) {
	echo "Update record from {$nic_ip_old} to {$nic_ip} ...";
	$result = dnspod_update_ip($nic_record_id, $nic_ip, $nic_line);
	echo $result ? "Successed\r\n" : "Failed\r\n";
}

echo "All done.";
?>