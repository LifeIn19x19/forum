<?php
defined('IN_MOBIQUO') or exit;

function ignore_user_func()
{
	global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx,$mobiquo_root_path;
	require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
	require($mobiquo_root_path .'include/mobi_ucp_zebra.php');
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup('ucp');
	
	// Only registered users can go beyond this point
	if (!$user->data['is_registered'])
	{
		trigger_error('LOGIN_EXPLAIN_UCP');
	}
	
	$ucp = new mobi_ucp_zebra();
	return $ucp->main('zebra','foes');
}