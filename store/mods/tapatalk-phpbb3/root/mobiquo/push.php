<?php
error_reporting(E_ALL & ~E_NOTICE);
$phpbb_root_path = dirname(dirname(__FILE__)).'/';
define('IN_PHPBB', 1);
$phpEx = 'php';
define('IN_MOBIQUO', 1);
require_once 'xmlrpcresp.' . $phpEx;
include($phpbb_root_path . 'common.php');
include 'push_hook.php';
$return_status = tt_do_post_request(array('test' => 1,'key' => $config['tapatalk_push_key']),true);
if(empty($config['tapatalk_push_key']))
{
	$return_status = 'Please set Tapatalk API Key at forum option/setting';
}
$server_ip = tt_do_post_request(array('ip' => 1),true);
$board_url = generate_board_url();
$table_exist = push_table_exists();
if(isset($_GET['checkcode']))
{
	$string = file_get_contents($phpbb_root_path . 'includes/functions_posting.php');
	echo 'push code have been added in phpbb : ' . (strstr($string , 'tapatalk_push_reply($data);') ? 'yes' : 'no' ). '<br/>';
	exit;
}

echo '<b>Tapatalk Push Notification Status Monitor</b><br/>';
echo '<br/>Push notification test: ' . (($return_status === '1') ? '<b>Success</b>' : 'Failed('.$return_status.')');
echo '<br/>Current server IP: ' . $server_ip;
echo '<br/>Current forum url: ' . $board_url;
echo '<br/>Tapatalk user table existence: ' . ($table_exist ? 'Yes' : 'No');
echo '<br/><br/><a href="http://tapatalk.com/api/api.php" target="_blank">Tapatalk API for Universal Forum Access</a> | <a href="http://tapatalk.com/mobile.php" target="_blank">Tapatalk Mobile Applications</a><br>
    For more details, please visit <a href="http://tapatalk.com" target="_blank">http://tapatalk.com</a>';



