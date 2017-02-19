<?php
/**
*
* @copyright (c) 2009, 2010, 2011 Quoord Systems Limited
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License (GPLv2)
*
*/

defined('IN_MOBIQUO') or exit;

function mark_pm_unread_func($xmlrpc_params)
{
	global $db, $auth, $user, $config;
	
	$params = php_xmlrpc_decode($xmlrpc_params);
	$msg_id = intval($params[0]);
	$user->setup('ucp');
	
	$message_row = array();

    // Get Message user want to see
    $sql = 'SELECT t.*, p.*, u.*
            FROM ' . PRIVMSGS_TO_TABLE . ' t, ' . PRIVMSGS_TABLE . ' p, ' . USERS_TABLE . ' u
            WHERE t.user_id = ' . $user->data['user_id'] . "
            AND p.author_id = u.user_id
            AND t.msg_id = p.msg_id
            AND p.msg_id = $msg_id";
    $result = $db->sql_query($sql);
    $message_row = $db->sql_fetchrow($result);
    $db->sql_freeresult($result);
	
    $folder_id = $message_row['folder_id'];
    $user_id = $user->data['user_id'];
    
    if (!$message_row) trigger_error('NO_MESSAGE');
    
	$sql = 'UPDATE ' . PRIVMSGS_TO_TABLE . "
		SET pm_unread = 1
		WHERE msg_id = $msg_id
			AND user_id = $user_id
			AND folder_id = $folder_id";
	$db->sql_query($sql);

	$sql = 'UPDATE ' . USERS_TABLE . "
		SET user_unread_privmsg = user_unread_privmsg + 1
		WHERE user_id = $user_id";
	$db->sql_query($sql);

	if ($user->data['user_id'] == $user_id)
	{
		$user->data['user_unread_privmsg']++;

		// Try to cope with previous wrong conversions...
		if ($user->data['user_unread_privmsg'] < 0)
		{
			$sql = 'UPDATE ' . USERS_TABLE . "
				SET user_unread_privmsg = 0
				WHERE user_id = $user_id";
			$db->sql_query($sql);

			$user->data['user_unread_privmsg'] = 0;
		}
	}
	
	return xmlresptrue();
}