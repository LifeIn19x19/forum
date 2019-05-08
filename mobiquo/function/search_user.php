<?php
defined('IN_MOBIQUO') or exit;

function search_user_func()
{
	global $user,$config,$auth,$db,$phpbb_root_path;
	// Start session management
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup(array('memberlist', 'groups'));
	
	if (!$auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel'))
	{
		if ($user->data['user_id'] != ANONYMOUS)
		{
			trigger_error('NO_VIEW_USERS');
		}
		
		trigger_error('LOGIN_EXPLAIN_MEMBERLIST');	
	}
	
	if ($config['load_search'] || $auth->acl_get('a_'))
	{
		$username	= request_var('username', '', true);
		$email		= strtolower(request_var('email', ''));
		
		$sql_where .= ($username) ? ' AND u.username_clean ' . $db->sql_like_expression(str_replace('*', $db->any_char, utf8_clean_string($username))) : '';
		$sql_where .= ($auth->acl_get('a_user') && $email) ? ' OR u.user_email ' . $db->sql_like_expression(str_replace('*', $db->any_char, $email)) . ' ' : '';		

	}
	else 
	{
		trigger_error('NO_VIEW_USERS');
	}
	
	$page   = request_var('page', 1);
	$per_page = request_var('perpage', 20);
	$start	= ($page -1) * $per_page;
	
	$default_key = 'c';
	$sort_key = request_var('sk', $default_key);
	$sort_dir = request_var('sd', 'a');
	
	$sort_key_sql = array('a' => 'u.username_clean', 'b' => 'u.user_from', 'c' => 'u.user_regdate', 'd' => 'u.user_posts', 'f' => 'u.user_website', 'g' => 'u.user_icq', 'h' => 'u.user_aim', 'i' => 'u.user_msnm', 'j' => 'u.user_yim', 'k' => 'u.user_jabber');
	// Sorting and order
	if (!isset($sort_key_sql[$sort_key]))
	{
		$sort_key = $default_key;
	}

	$order_by .= $sort_key_sql[$sort_key] . ' ' . (($sort_dir == 'a') ? 'ASC' : 'DESC');

	// Unfortunately we must do this here for sorting by rank, else the sort order is applied wrongly
	if ($sort_key == 'm')
	{
		$order_by .= ', u.user_posts DESC';
	}

	// Count the users ...
	if ($sql_where)
	{
		$sql = 'SELECT COUNT(u.user_id) AS total_users
			FROM ' . USERS_TABLE . " u
			WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
			$sql_where";
		$result = $db->sql_query($sql);
		$total_users = (int) $db->sql_fetchfield('total_users');
		$db->sql_freeresult($result);
	}
	else
	{
		$total_users = $config['num_users'];
	}

	// Get us some users :D
	$sql = "SELECT u.*
		FROM " . USERS_TABLE . " u
		WHERE u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ")
			$sql_where
		ORDER BY $order_by";
	$result = $db->sql_query_limit($sql, $per_page, $start);

	$user_list = array();
	while ($row = $db->sql_fetchrow($result))
	{
		$return_user_lists[] = new xmlrpcval(array(
                'username'     => new xmlrpcval(basic_clean($row['username']), 'base64'),
                'user_id'       => new xmlrpcval($row['user_id'], 'string'),
                'icon_url'      => new xmlrpcval(get_user_avatar_url($row['user_avatar'], $row['user_avatar_type']), 'string'),
        ), 'struct');
	}
	$db->sql_freeresult($result);
	
	$suggested_users = new xmlrpcval(array(
        'total' => new xmlrpcval($total_users, 'int'),
        'list'         => new xmlrpcval($return_user_lists, 'array'),
    ), 'struct');

    return new xmlrpcresp($suggested_users);
}
