<?php
defined('IN_MOBIQUO') or exit;
class mobi_ucp_register
{
	public $result = false;
	public $result_text = '';
	
	public function main()
	{	
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx,$mobiquo_config;
		//
		if ($config['require_activation'] == USER_ACTIVATION_DISABLE)
		{
			trigger_error('UCP_REGISTER_DISABLE');
		}
		
		include($phpbb_root_path . 'includes/functions_profile_fields.' . $phpEx);

		$user_lang		= request_var('lang', $user->lang_name);

		
		$cp = new custom_profile();
		$verify_result = false;
		$error = $cp_data = $cp_error = array();
		$is_dst = $config['board_dst'];
		$timezone = $config['board_timezone'];
		$email = request_var('email', '');
		if(isset($_POST['tt_token']) && isset($_POST['tt_code']))
		{
			if($mobiquo_config['sso_register'] == 0 )
			{
				trigger_error('UCP_REGISTER_DISABLE');
			}
			$result = tt_register_verify($_POST['tt_token'], $_POST['tt_code']);   	
			if($result->result && !empty($result->email) && (empty($email) || strtolower($email == strtolower($result->email))))
			{
				$verify_result = $result->result;
				$email = $result->email;
			}
			else if(!$result->result && empty($email) && !empty($result->email))
			{
				$email = $result->email;
			}						
		}
		
		$data = array(
			'username'			=> utf8_normalize_nfc(request_var('username', '', true)),
			'new_password'		=> request_var('new_password', '', true),
			'password_confirm'	=> request_var('password_confirm', '', true),
			'email'				=> strtolower($email),
			'email_confirm'		=> strtolower($email),
			'lang'				=> basename(request_var('lang', $user->lang_name)),
			'tz'				=> request_var('tz', (float) $timezone),
		);
		$error = validate_data($data, array(
			'username'			=> array(
				array('string', false, $config['min_name_chars'], $config['max_name_chars']),
				array('username', '')),
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

		// validate custom profile fields
		$cp->submit_cp_field('register', $user->get_iso_lang_id(), $cp_data, $error);

		if (!sizeof($error))
		{
			if ($data['new_password'] != $data['password_confirm'])
			{
				$error[] = $user->lang['NEW_PASSWORD_ERROR'];
			}

			if ($data['email'] != $data['email_confirm'])
			{
				$error[] = $user->lang['NEW_EMAIL_ERROR'];
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

			$group_id = $row['group_id'];
			if($config['require_activation'] == USER_ACTIVATION_NONE)
			{
				$group_id = isset($config['tapatalk_register_group']) ? $config['tapatalk_register_group'] : $row['group_id'];
				$user_type = USER_NORMAL;
				$user_actkey = '';
				$user_inactive_reason = 0;
				$user_inactive_time = 0;
			}
			if (($config['require_activation'] != USER_ACTIVATION_NONE) && !$verify_result)
			{
				$user_actkey = gen_rand_string(mt_rand(6, 10));
				$user_type = USER_INACTIVE;
				$user_inactive_reason = INACTIVE_REGISTER;
				$user_inactive_time = time();
			}
			if($verify_result && ($config['require_activation'] != USER_ACTIVATION_ADMIN))
			{
				$group_id = isset($config['tapatalk_register_group']) ? $config['tapatalk_register_group'] : $row['group_id'];
				$user_type = USER_NORMAL;
				$user_actkey = '';
				$user_inactive_reason = 0;
				$user_inactive_time = 0;
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

			// Register user...
			$user_id = user_add($user_row, $cp_data);

			// This should not happen, because the required variables are listed above...
			if ($user_id === false)
			{
				trigger_error('NO_USER', E_USER_ERROR);
			}
			if(!$verify_result)
			{
				$this->sendEmail($data, $user_id, $user_actkey);
				switch ($config['require_activation'])
				{
					case USER_ACTIVATION_SELF:
						$this->result_text = $user->lang['UCP_EMAIL_ACTIVATE'];
					break;
		
					case USER_ACTIVATION_ADMIN:
						$this->result_text = $user->lang['UCP_ADMIN_ACTIVATE'];
					break;
				}
			}
			$this->result = true;
		}
		else 
		{
			foreach ($error as $msg)
			{
				trigger_error($msg);
			}
		}
	}
	
	public function sendEmail($data,$user_id,$user_actkey)
	{
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx;
		$server_url = generate_board_url();
		if ($config['require_activation'] == USER_ACTIVATION_SELF && $config['email_enable'])
		{
			$message = $user->lang['ACCOUNT_INACTIVE'];
			$email_template = 'user_welcome_inactive';
		}
		else if ($config['require_activation'] == USER_ACTIVATION_ADMIN && $config['email_enable'])
		{
			$message = $user->lang['ACCOUNT_INACTIVE_ADMIN'];
			$email_template = 'admin_welcome_inactive';
		}
		else
		{
			$message = $user->lang['ACCOUNT_ADDED'];
			$email_template = 'user_welcome';
		}

		if ($config['email_enable'])
		{
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
		}
		
	}
}