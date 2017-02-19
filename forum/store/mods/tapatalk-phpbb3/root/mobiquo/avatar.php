<?php
define('IN_MOBIQUO',1);
define('IN_PHPBB', true);
//error_reporting(0);
$forum_root = dirname(dirname(__FILE__));
$mobiquo_root = dirname(__FILE__);
if(!$forum_root)
{
	$forum_root = '.';
}
if(!$mobiquo_root)
{
	$mobiquo_root = '.';
}

include($mobiquo_root . '/config/config.php');
include($forum_root . '/common.' . $phpEx);
require_once $mobiquo_root . '/mobiquo_common.php';

if(!empty($_GET['user_id']))
{
	$uid = intval($_GET['user_id']);
}
else if(!empty($_GET['username']))
{
	$_GET['username'] = base64_decode($_GET['username']);
	$_GET['username'] = trim($_GET['username']);	
	$uid = get_user_id_by_name($_GET['username']);
}
else 
{
	die('Invalid params!');
}
$phpbb_home = generate_board_url().'/';
$user_info = tt_get_user_by_id($uid);
$icon_url = get_user_avatar_url($user_info['user_avatar'], $user_info['user_avatar_type']);
header("Location:$icon_url");