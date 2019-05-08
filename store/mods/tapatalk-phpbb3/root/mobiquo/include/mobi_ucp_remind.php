<?php
defined('IN_MOBIQUO') or exit;
class mobi_ucp_remind
{
	public $result = false;
	public $result_text = '';
	public $verify = false;

	public function main()
	{
		global $config, $phpbb_root_path, $phpEx;
		global $db, $user, $auth, $template;

		$username	= request_var('username', '', true);
		
		
		$sql = 'SELECT user_id, username, user_permissions, user_email, user_jabber, user_notify_type, user_type, user_lang, user_inactive_reason
			FROM ' . USERS_TABLE . "
			WHERE  username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'";
		$result = $db->sql_query($sql);
		$user_row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);
		if (!$user_row)
		{
			trigger_error('NO_EMAIL_USER');
		}

		if ($user_row['user_type'] == USER_IGNORE)
		{
			trigger_error('NO_USER');
		}

		if ($user_row['user_type'] == USER_INACTIVE)
		{
			if ($user_row['user_inactive_reason'] == INACTIVE_MANUAL)
			{
				trigger_error('ACCOUNT_DEACTIVATED');
			}
			else
			{
				trigger_error('ACCOUNT_NOT_ACTIVATED');
			}
		}

		// Check users permissions
		$auth2 = new auth();
		$auth2->acl($user_row);

		if (!$auth2->acl_get('u_chgpasswd'))
		{
			trigger_error('NO_AUTH_PASSWORD_REMINDER');
		}
		$result = tt_register_verify($_POST['tt_token'], $_POST['tt_code']);   	
		if($result->result && ($user_row['user_email'] == $result->email))
		{
			$this->result = true;
			$this->verify = true;
			return ;
		}
		
		$this->result = false;
		$this->result_text = 'Sorry, you can only retrieve your password from browser.';
		return ;
	}
}