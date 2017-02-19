<?php
defined('IN_MOBIQUO') or exit;
require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
$_COOKIE = array();
$user->session_begin();
$auth->acl($user->data);
$user->setup('ucp');

function sign_in_func()
{
    global $config, $mobiquo_config,$db, $user, $auth, $template, $phpbb_root_path, $phpEx,$user_info,$register;
    
	if($mobiquo_config['native_register'] == 0)
	{
		trigger_error('UCP_REGISTER_DISABLE');
	}
	if ($config['require_activation'] == USER_ACTIVATION_DISABLE)
	{
		trigger_error('UCP_REGISTER_DISABLE');
	}
	
	include($phpbb_root_path . 'includes/functions_profile_fields.' . $phpEx);

	$user_lang		= request_var('lang', $user->lang_name);

	
	//$cp = new custom_profile();
	$verify_result = false;
	$error = array();
	$is_dst = $config['board_dst'];
	$timezone = $config['board_timezone'];
	
	$token = trim($_POST['token']);
	$code = trim($_POST['code']);
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);
	$post_email = trim($_POST['email']);
	$status = '';
	if(isset($token) && isset($code))
	{
	    $return = tt_register_verify($token, $code);
		if($return->result && !empty($return->email))
		{
			$email = $return->email;
		    if(!empty($post_email) && $post_email != $email)
			{
				$status = 3;
			}
			else if($user_info = tt_get_user_by_email($email))
			{
				if(!empty($username) && strtolower($username) != strtolower($user_info['username']))
				{
					$status = 3;
				}
				else 
				{
					$register = 0;
					return tt_login_success();
				}		
			}
			else if(!empty($username) && !empty($email))
			{
				$data = array(
					'username'			=> utf8_normalize_nfc(request_var('username', '', true)),
					'new_password'		=> request_var('password', '', true),
					'password_confirm'	=> request_var('password', '', true),
					'email'				=> strtolower($email),
					'email_confirm'		=> strtolower($email),
					'lang'				=> basename(request_var('lang', $user->lang_name)),
					'tz'				=> request_var('tz', (float) $timezone),
				);
				//check username 
				if($result_username = validate_username($username))
				{
					if($result_username == 'USERNAME_TAKEN')
					{
						$status = 1;
					}
					else 
					{
						$error[] = (empty($user->lang[$result_username . '_' . strtoupper('username')])) ? $result_username : $result_username . '_' . strtoupper('username');
					}
				}
				else 
				{
					if($mobiquo_config['sso_signin'] == 0)
					{
						trigger_error('UCP_REGISTER_DISABLE');
					}
					$error = validate_data($data, array(
						'new_password'		=> array(
							array('string', false, $config['min_pass_chars'], $config['max_pass_chars']),
							array('password')),
						'password_confirm'	=> array('string', false, $config['min_pass_chars'], $config['max_pass_chars']),
						'email'				=> array(
							array('string', false, 6, 60),
							array('email')),
						'email_confirm'		=> array('string', false, 6, 60),
						'tz'				=> array('num', false, -14, 14),
						'lang'				=> array('language_iso_name'),
					));
					
					// Replace "error" strings with their real, localised form
					$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);
					// DNSBL check
					if ($config['check_dnsbl'])
					{
						if (($dnsbl = $user->check_dnsbl('register')) !== false)
						{
							$error[] = sprintf($user->lang['IP_BLACKLISTED'], $user->ip, $dnsbl[1]);
						}
					}
				
					
					if (!sizeof($error))
					{
				
						// Which group by default?
						$group_name = 'REGISTERED';
						
						$sql = 'SELECT group_id
							FROM ' . GROUPS_TABLE . "
							WHERE group_name = '" . $db->sql_escape($group_name) . "'
								AND group_type = " . GROUP_SPECIAL;
						$result = $db->sql_query($sql);
						$row = $db->sql_fetchrow($result);
						$db->sql_freeresult($result);
				
						if (!$row)
						{
							trigger_error('NO_GROUP');
						}
				
						$group_id = isset($config['tapatalk_register_group']) ? $config['tapatalk_register_group'] : $row['group_id'];
						$user_type = USER_NORMAL;
						$user_actkey = '';
						$user_inactive_reason = 0;
					    $user_inactive_time = 0;
						if ($config['require_activation'] == USER_ACTIVATION_ADMIN && $config['email_enable'])
						{
							$user_type = USER_INACTIVE;
							$user_inactive_reason = INACTIVE_REGISTER;
							$user_inactive_time = time();
						}
					    
						$user_row = array(
							'username'				=> $data['username'],
							'user_password'			=> phpbb_hash($data['new_password']),
							'user_email'			=> $data['email'],
							'group_id'				=> (int) $group_id,
							'user_timezone'			=> (float) $data['tz'],
							'user_dst'				=> $is_dst,
							'user_lang'				=> $data['lang'],
							'user_type'				=> $user_type,
							'user_actkey'			=> $user_actkey,
							'user_ip'				=> $user->ip,
							'user_regdate'			=> time(),
							'user_inactive_reason'	=> $user_inactive_reason,
							'user_inactive_time'	=> $user_inactive_time,
						);
						
						if ($config['new_member_post_limit'])
						{
							$user_row['user_new'] = 1;
						}
						
						if(!empty($return->profile))
						{
							$profile = $return->profile;
							if(!empty($profile->birthday) && $config['allow_birthdays'])
							{
								$birth_arr = explode('-', $profile->birthday);
								$user_row['user_birthday'] = sprintf('%2d-%2d-%4d', $birth_arr[2], $birth_arr[1], $birth_arr[0]);
							}
							
							$user_row['user_from'] = $profile->location;
							$user_row['user_website'] = $profile->link;
							$user_row['user_sig'] = $profile->signature;
							
						}
					
						// Register user...
						$user_id = user_add($user_row);	
						//copy avatar
						tt_copy_avatar($user_id, $profile->avatar_url);
						// This should not happen, because the required variables are listed above...
						if ($user_id === false)
						{
							trigger_error('NO_USER', E_USER_ERROR);
						}
						else 
						{
							if ($config['require_activation'] == USER_ACTIVATION_ADMIN && $config['email_enable'])
							{
								$message = $user->lang['ACCOUNT_INACTIVE_ADMIN'];
								$email_template = 'admin_welcome_inactive';
								$server_url = generate_board_url();
								include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);

								$messenger = new messenger(false);
					
								$messenger->template($email_template, $data['lang']);
					
								$messenger->to($data['email'], $data['username']);
								
								if(!method_exists($messenger, 'anti_abuse_headers'))
								{
									$messenger->headers('X-AntiAbuse: Board servername - ' . $config['server_name']);
									$messenger->headers('X-AntiAbuse: User_id - ' . $user->data['user_id']);
									$messenger->headers('X-AntiAbuse: Username - ' . $user->data['username']);
									$messenger->headers('X-AntiAbuse: User IP - ' . $user->ip);
								}
								else 
								{
									$messenger->anti_abuse_headers($config, $user);
								}
								
								$messenger->assign_vars(array(
									'WELCOME_MSG'	=> htmlspecialchars_decode(sprintf($user->lang['WELCOME_SUBJECT'], $config['sitename'])),
									'USERNAME'		=> htmlspecialchars_decode($data['username']),
									'PASSWORD'		=> htmlspecialchars_decode($data['new_password']),
									'U_ACTIVATE'	=> "$server_url/ucp.$phpEx?mode=activate&u=$user_id&k=$user_actkey")
								);
					
					
								$messenger->send(NOTIFY_EMAIL);
					
								if ($config['require_activation'] == USER_ACTIVATION_ADMIN)
								{
									// Grab an array of user_id's with a_user permissions ... these users can activate a user
									$admin_ary = $auth->acl_get_list(false, 'a_user', false);
									$admin_ary = (!empty($admin_ary[0]['a_user'])) ? $admin_ary[0]['a_user'] : array();
					
									// Also include founders
									$where_sql = ' WHERE user_type = ' . USER_FOUNDER;
					
									if (sizeof($admin_ary))
									{
										$where_sql .= ' OR ' . $db->sql_in_set('user_id', $admin_ary);
									}
					
									$sql = 'SELECT user_id, username, user_email, user_lang, user_jabber, user_notify_type
										FROM ' . USERS_TABLE . ' ' .
										$where_sql;
									$result = $db->sql_query($sql);
					
									while ($row = $db->sql_fetchrow($result))
									{
										$messenger->template('admin_activate', $row['user_lang']);
										$messenger->to($row['user_email'], $row['username']);
										$messenger->im($row['user_jabber'], $row['username']);
					
										$messenger->assign_vars(array(
											'USERNAME'			=> htmlspecialchars_decode($data['username']),
											'U_USER_DETAILS'	=> "$server_url/memberlist.$phpEx?mode=viewprofile&u=$user_id",
											'U_ACTIVATE'		=> "$server_url/ucp.$phpEx?mode=activate&u=$user_id&k=$user_actkey")
										);
					
										$messenger->send($row['user_notify_type']);
									}
									$db->sql_freeresult($result);
								}
								trigger_error('UCP_ADMIN_ACTIVATE');
							}
							$user_info['user_id'] = $user_id;
							$register = 1;
							return tt_login_success();
						}
						
					}
					else 
					{
						foreach ($error as $msg)
						{
							trigger_error($msg);
						}
					}
				}
			}
			else 
			{
				$status = 2;
			}
		}
		else if(!$return->result)
		{
			if(!empty($return->result_text))
			{
				trigger_error($return->result_text);
			}
			else 
			{
				trigger_error("Tapatalk ID verify faile!");
			}
		}

		if(!empty($status))
		{
			$response = new xmlrpcval(array(
		        'result'            => new xmlrpcval(0, 'boolean'),
		        'result_text'       => new xmlrpcval('', 'base64'),
			 	'status'          => new xmlrpcval($status, 'string'),
			 ), 'struct');
			return new xmlrpcresp($response);
		}
	}
	else
	{
		trigger_error("Invlaid params!");
	}	
}

function tt_login_success()
{
	global $config, $db, $user, $phpbb_root_path, $phpEx,$user_info,$auth,$register;
	header('Set-Cookie: mobiquo_a=0');
    header('Set-Cookie: mobiquo_b=0');
    header('Set-Cookie: mobiquo_c=0');
	$result = $user->session_create($user_info['user_id'], 0, true, 1);
	if($result)
	{
		$usergroup_id = array();
        $auth->acl($user->data);
        //add tapatalk_users here,for push service
        if(push_table_exists())
        {
        	global $table_prefix;
        	$sql = "SELECT * FROM " . $table_prefix . "tapatalk_users where userid = '".$user->data['user_id']."'";
	        $result = $db->sql_query($sql);
	        $userInfo = $db->sql_fetchrow($result);
	        $db->sql_freeresult($result);
	        $time = time();
        	if(empty($userInfo))
        	{
	        	$sql_data[$table_prefix . "tapatalk_users"]['sql'] = array(
	        		'userid' => $user->data['user_id'],
	        		'announcement' => 1,
	        		'pm' => 1,
	        		'subscribe' => 1,
	        		'quote' => 1,
	        		'tag' => 1,
	        		'newtopic' => 1,
	        		'updated' => time()
	        	);
	        	$sql = 'INSERT INTO ' . $table_prefix . "tapatalk_users" . ' ' .
				$db->sql_build_array('INSERT', $sql_data[$table_prefix . "tapatalk_users"]['sql']);
				$db->sql_query($sql);    	
	        }
        }
        
        // Compatibility with mod NV who was here
        if (file_exists($phpbb_root_path . 'includes/mods/who_was_here.' . $phpEx))
        {
            include_once($phpbb_root_path . 'includes/mods/who_was_here.' . $phpEx);
            if (class_exists('phpbb_mods_who_was_here') && method_exists('phpbb_mods_who_was_here', 'update_session'))
            {
                @phpbb_mods_who_was_here::update_session();
            }
        }
        
        if ($config['max_attachments'] == 0) $config['max_attachments'] = 100;
    
	    $usergroup_id[] = new xmlrpcval($user->data['group_id']);
	    $can_readpm = $config['allow_privmsg'] && $auth->acl_get('u_readpm') && ($user->data['user_allow_pm'] || $auth->acl_gets('a_', 'm_') || $auth->acl_getf_global('m_'));
	    $can_sendpm = $config['allow_privmsg'] && $auth->acl_get('u_sendpm') && ($user->data['user_allow_pm'] || $auth->acl_gets('a_', 'm_') || $auth->acl_getf_global('m_'));
	    $can_upload = ($config['allow_avatar_upload'] && file_exists($phpbb_root_path . $config['avatar_path']) && (function_exists('phpbb_is_writable') ? phpbb_is_writable($phpbb_root_path . $config['avatar_path']) : 1) && $auth->acl_get('u_chgavatar') && (@ini_get('file_uploads') || strtolower(@ini_get('file_uploads')) == 'on')) ? true : false;
	    $can_search = $auth->acl_get('u_search') && $auth->acl_getf_global('f_search') && $config['load_search'];
	    $can_whosonline = $auth->acl_gets('u_viewprofile', 'a_user', 'a_useradd', 'a_userdel');
	    $max_filesize   = ($config['max_filesize'] === '0' || $config['max_filesize'] > 10485760) ? 10485760 : $config['max_filesize'];
	    
	    $userPushType = array('pm' => 1,'newtopic' => 1,'sub' => 1,'tag' => 1,'quote' => 1);
	    $push_type = array();
	    
	 	foreach ($userPushType as $name=>$value)
	    {
	    	$push_type[] = new xmlrpcval(array(
	            'name'  => new xmlrpcval($name,'string'),
	    		'value' => new xmlrpcval($value,'boolean'),                    
	            ), 'struct');
	    }   
	    
	    $response = new xmlrpcval(array(
	        'result'        => new xmlrpcval(true, 'boolean'),
	        'user_id'       => new xmlrpcval($user->data['user_id'], 'string'),
	        'username'      => new xmlrpcval(basic_clean($user->data['username']), 'base64'),
	    	'email'         => new xmlrpcval($user->data['user_email'], 'base64'),
			'user_type'     => check_return_user_type($user->data['username']),
	        'usergroup_id'  => new xmlrpcval($usergroup_id, 'array'),
	        'ignored_uids'  => new xmlrpcval(implode(',', tt_get_ignore_users($user->data['user_id'])),'string'),
	        'icon_url'      => new xmlrpcval(get_user_avatar_url($user->data['user_avatar'], $user->data['user_avatar_type']), 'string'),
	        'post_count'    => new xmlrpcval($user->data['user_posts'], 'int'),
	        'can_pm'        => new xmlrpcval($can_readpm, 'boolean'),
	        'can_send_pm'   => new xmlrpcval($can_sendpm, 'boolean'),
	        'can_moderate'  => new xmlrpcval($auth->acl_get('m_') || $auth->acl_getf_global('m_'), 'boolean'),
	        'max_attachment'=> new xmlrpcval($config['max_attachments'], 'int'),
	        'max_png_size'  => new xmlrpcval($max_filesize, 'int'),
	        'max_jpg_size'  => new xmlrpcval($max_filesize, 'int'),
	        'can_search'    => new xmlrpcval($can_search, 'boolean'),
	        'can_whosonline'    => new xmlrpcval($can_whosonline, 'boolean'),
	        'can_upload_avatar' => new xmlrpcval($can_upload, 'boolean'),
	    	'register'          => new xmlrpcval($register, "boolean"),
	    	'push_type'         => new xmlrpcval($push_type, 'array'), 
	    
	    ), 'struct');
	    
	    return new xmlrpcresp($response);
	}
	
}

function tt_copy_avatar($uid,$avatar_url)
{
	global $config,$phpbb_root_path,$db,$user, $phpEx;
	$can_upload = $config['allow_avatar_remote'];
	if($can_upload && !empty($avatar_url))
	{
		$avatar['user_id'] = $uid;
		$avatar['uploadurl'] = '';
		$avatar['remotelink'] = $avatar_url;
		$avatar['width'] = $config['avatar_max_width'];
		$avatar['height'] = $config['avatar_max_height'];
		$error = array();
		$upload_response = avatar_remote($avatar, $error);

		if(empty($error))
		{
			list($sql_ary['user_avatar_type'], $sql_ary['user_avatar'], $sql_ary['user_avatar_width'], $sql_ary['user_avatar_height']) = $upload_response;
			$sql = 'UPDATE ' . USERS_TABLE . '
			SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
			WHERE user_id = ' . $uid;
			$db->sql_query($sql);
		}	
	}
}

