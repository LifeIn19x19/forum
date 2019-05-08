<?php
defined('IN_MOBIQUO') or exit;

function prefetch_account_func()
{
	global $db;
	$user = tt_get_user_by_email(trim($_POST['email']));
	if(empty($user['user_id']))
	{
		trigger_error("Can't find the user");
	}
	$result = array(
		'result'            => new xmlrpcval(true, 'boolean'),
		'result_text'       => new xmlrpcval('', 'base64'),
		'user_id'           => new xmlrpcval($user['user_id'], 'string'),
		'login_name'        => new xmlrpcval(basic_clean($user['username']), 'base64'),
		'display_name'      => new xmlrpcval(basic_clean($user['username']), 'base64'),
		'avatar'            => new xmlrpcval(get_user_avatar_url($user['user_avatar'], $user['user_avatar_type']), 'string'),
	);
	return new xmlrpcresp(new xmlrpcval($result, 'struct'));
}
