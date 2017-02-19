<?php
defined('IN_MOBIQUO') or exit;
class mobi_ucp_profile
{
	public $result = false;
	public $result_text = '';

	public function main()
	{
		global $config, $db, $user, $auth, $template, $phpbb_root_path, $phpEx;
		$verify_result = false;
		$user->add_lang('posting');

		$error = $data = array();
		$s_hidden_fields = '';
		
		if(!empty($_POST['tt_token']) && !empty($_POST['tt_code']))
		{
			$result = tt_register_verify($_POST['tt_token'], $_POST['tt_code']); 
			if($result->result && $result->email)
			{
				$verify_result = true;
				$email = $result->email;
			}
			else
			{
				$this->result = false;
				$this->result_text = "No permission to update your profie info";
			}
		}
		if(!empty($email))
		{
			$sql = 'SELECT user_id, username,username_clean, user_password, user_email_hash,user_passchg, user_pass_convert, user_email, user_type, user_login_attempts
				FROM ' . USERS_TABLE . "
				WHERE user_email = '" . $db->sql_escape($email) . "'";
			$result = $db->sql_query($sql);
			$row = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);
			if(!empty($row))
			{
				$user->data = $row;
				$auth->acl($user->data);
			}
			else 
			{
				$this->result = false;
				$this->result_text = 'username not exist!';
				return ;
			}
			 
		}
		$data = array(
			'username'			=> utf8_normalize_nfc(request_var('username', $user->data['username'], true)),
			'email'				=> strtolower(request_var('email', $user->data['user_email'])),
			'email_confirm'		=> strtolower(request_var('email_confirm', $user->data['user_email'])),
			'new_password'		=> request_var('new_password', '', true),
			'cur_password'		=> request_var('cur_password', '', true),
			'password_confirm'	=> request_var('password_confirm', '', true),
		);
		
		// Do not check cur_password, it is the old one.
		$check_ary = array(
			'new_password'		=> array(
				array('string', true, $config['min_pass_chars'], $config['max_pass_chars']),
				array('password')),
			'password_confirm'	=> array('string', true, $config['min_pass_chars'], $config['max_pass_chars']),
			'email'				=> array(
				array('string', false, 6, 60),
				array('email')),
			'email_confirm'		=> array('string', true, 6, 60),
		);

		if ($auth->acl_get('u_chgname') && $config['allow_namechange'])
		{
			$check_ary['username'] = array(
				array('string', false, $config['min_name_chars'], $config['max_name_chars']),
				array('username'),
			);
		}

		$error = validate_data($data, $check_ary);
		if ($auth->acl_get('u_chgemail') && $data['email'] != $user->data['user_email'] && $data['email_confirm'] != $data['email'])
		{
			$error[] = ($data['email_confirm']) ? 'NEW_EMAIL_ERROR' : 'NEW_EMAIL_CONFIRM_EMPTY';
		}

		if ($auth->acl_get('u_chgpasswd') && $data['new_password'] && $data['password_confirm'] != $data['new_password'])
		{
			$error[] = ($data['password_confirm']) ? 'NEW_PASSWORD_ERROR' : 'NEW_PASSWORD_CONFIRM_EMPTY';
		}

		// Only check the new password against the previous password if there have been no errors
		if (!sizeof($error) && $auth->acl_get('u_chgpasswd') && $data['new_password'] && phpbb_check_hash($data['new_password'], $user->data['user_password']))
		{
			$error[] = 'SAME_PASSWORD_ERROR';
		}

		if (!$verify_result && !phpbb_check_hash($data['cur_password'], $user->data['user_password']))
		{
			$error[] = ($data['cur_password']) ? 'CUR_PASSWORD_ERROR' : 'CUR_PASSWORD_EMPTY';
		}

		if (!sizeof($error))
		{
			$sql_ary = array(
				'username'			=> ($auth->acl_get('u_chgname') && $config['allow_namechange']) ? $data['username'] : $user->data['username'],
				'username_clean'	=> ($auth->acl_get('u_chgname') && $config['allow_namechange']) ? utf8_clean_string($data['username']) : $user->data['username_clean'],
				'user_email'		=> ($auth->acl_get('u_chgemail')) ? $data['email'] : $user->data['user_email'],
				'user_email_hash'	=> ($auth->acl_get('u_chgemail')) ? phpbb_email_hash($data['email']) : $user->data['user_email_hash'],
				'user_password'		=> ($auth->acl_get('u_chgpasswd') && $data['new_password']) ? phpbb_hash($data['new_password']) : $user->data['user_password'],
				'user_passchg'		=> ($auth->acl_get('u_chgpasswd') && $data['new_password']) ? time() : 0,
			);

			if ($auth->acl_get('u_chgname') && $config['allow_namechange'] && $data['username'] != $user->data['username'])
			{
				add_log('user', $user->data['user_id'], 'LOG_USER_UPDATE_NAME', $user->data['username'], $data['username']);
			}

			if ($auth->acl_get('u_chgpasswd') && $data['new_password'] && !phpbb_check_hash($data['new_password'], $user->data['user_password']))
			{
				$user->reset_login_keys();
				add_log('user', $user->data['user_id'], 'LOG_USER_NEW_PASSWORD', $data['username']);
			}

			if ($auth->acl_get('u_chgemail') && $data['email'] != $user->data['user_email'])
			{
				add_log('user', $user->data['user_id'], 'LOG_USER_UPDATE_EMAIL', $data['username'], $user->data['user_email'], $data['email']);
			}

			$message = 'PROFILE_UPDATED';

			if ($auth->acl_get('u_chgemail') && $config['email_enable'] && $data['email'] != $user->data['user_email'] && $user->data['user_type'] != USER_FOUNDER && ($config['require_activation'] == USER_ACTIVATION_SELF || $config['require_activation'] == USER_ACTIVATION_ADMIN))
			{
				$message = ($config['require_activation'] == USER_ACTIVATION_SELF) ? 'ACCOUNT_EMAIL_CHANGED' : 'ACCOUNT_EMAIL_CHANGED_ADMIN';

				include_once($phpbb_root_path . 'includes/functions_messenger.' . $phpEx);

				$server_url = generate_board_url();

				$user_actkey = gen_rand_string(mt_rand(6, 10));

				$messenger = new messenger(false);

				$template_file = ($config['require_activation'] == USER_ACTIVATION_ADMIN) ? 'user_activate_inactive' : 'user_activate';
				$messenger->template($template_file, $user->data['user_lang']);

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
					'USERNAME'		=> htmlspecialchars_decode($data['username']),
					'U_ACTIVATE'	=> "$server_url/ucp.$phpEx?mode=activate&u={$user->data['user_id']}&k=$user_actkey")
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
							'U_USER_DETAILS'	=> "$server_url/memberlist.$phpEx?mode=viewprofile&u={$user->data['user_id']}",
							'U_ACTIVATE'		=> "$server_url/ucp.$phpEx?mode=activate&u={$user->data['user_id']}&k=$user_actkey")
						);

						$messenger->send($row['user_notify_type']);
					}
					$db->sql_freeresult($result);
				}

				user_active_flip('deactivate', $user->data['user_id'], INACTIVE_PROFILE);

				// Because we want the profile to be reactivated we set user_newpasswd to empty (else the reactivation will fail)
				$sql_ary['user_actkey'] = $user_actkey;
				$sql_ary['user_newpasswd'] = '';
			}

			if (sizeof($sql_ary))
			{
				$sql = 'UPDATE ' . USERS_TABLE . '
					SET ' . $db->sql_build_array('UPDATE', $sql_ary) . '
					WHERE user_id = ' . $user->data['user_id'];
				$db->sql_query($sql);
			}

			// Need to update config, forum, topic, posting, messages, etc.
			if ($data['username'] != $user->data['username'] && $auth->acl_get('u_chgname') && $config['allow_namechange'])
			{
				user_update_name($user->data['username'], $data['username']);
			}

			// Now, we can remove the user completely (kill the session) - NOT BEFORE!!!
			if (!empty($sql_ary['user_actkey']))
			{
				// Because the user gets deactivated we log him out too, killing his session
				$user->session_kill();
			}
		}

		// Replace "error" strings with their real, localised form
		$error = preg_replace('#^([A-Z_]+)$#e', "(!empty(\$user->lang['\\1'])) ? \$user->lang['\\1'] : '\\1'", $error);
		if(!empty($error))
		{
			$this->result_text = $error[0];
			$this->result = false;
			return ;
		}

		$this->result = true;
	}

}