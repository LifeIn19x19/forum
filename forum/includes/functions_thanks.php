<?php
/** 
*
* @package phpBB3
* @version $Id: functions_thanks.php,v 125 2009-12-01 10:02:51 Палыч$
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License 
*
*/

/**
* @ignore
*/
if (!defined('IN_PHPBB'))
{
	exit; 
}
$user->add_lang('mods/thanks_mod');

// Output thanks list
function get_thanks($post_id)
{
	global $thankers;
	$return = '';
	$user_list = array();
	foreach($thankers as $key => $value)
	{
		if ($thankers[$key]['post_id'] == $post_id)
		{
			$user_list[$thankers[$key]['username_clean']] = array( 
				'username' => $thankers[$key]['username'], 
				'user_id' => $thankers[$key]['user_id'], 
				'user_colour' => $thankers[$key]['user_colour'], 
			);
		}
	}
	ksort($user_list);
	$comma = '';
	foreach($user_list as $key => $value)
	{
		$return .= $comma;
		$return .= get_username_string('full', $value['user_id'], $value['username'], $value['user_colour']);
		$comma = ', ';
	}
	$return = ($return == '') ? false : $return;
	return $return;
}

//get thanks number
function get_thanks_number($post_id)
{
	global $thankers;
	$i = 0;
	foreach($thankers as $key => $value)
	{
		if ($thankers[$key]['post_id'] == $post_id)
		{
			$i++;
		}
	}
	return $i;
}

// add a user to the thanks list
function insert_thanks($post_id, $user_id)
{
	global $db, $user, $phpbb_root_path, $phpEx, $forum_id, $config;
	$to_id = request_var('to_id', 0);
	
	$sql_array = array(
		'SELECT'	=> 'p.post_id, p.poster_id',
		'FROM'		=> array (POSTS_TABLE => 'p'),
		'WHERE'		=> 'p.post_id ='. (int) $post_id );
	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	$row = $db->sql_fetchrow($result);	
	$db->sql_freeresult($result);	
	if ($user->data['user_type'] != USER_IGNORE && !empty($to_id))
	{
		if ($row['poster_id'] != $user_id && $row['poster_id'] == $to_id && !already_thanked($post_id, $user_id))
		{
			$sql = 'INSERT INTO ' . THANKS_TABLE . ' ' . $db->sql_build_array('INSERT', array(
				'user_id'	=> $user_id,
				'post_id'	=> $post_id,
				'poster_id'	=> $to_id
			));
			$db->sql_query($sql);
		
			$lang_act = 'GIVE';
			send_thanks_pm($user_id, $to_id, $send_pm = true, $post_id, $lang_act);
			if ($config['thanks_info_page'])
			{
				meta_refresh (1, append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id"));
				trigger_error($user->lang['THANKS_INFO_'.$lang_act] . '<br /><br /><a href="'.append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id").'">'.$user->lang['RETURN_POST'].'</a>');
			}
			else
			{
				redirect (append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id"));			
			}
		}
		else
		{
			trigger_error($user->lang['INCORRECT_THANKS'] . '<br /><br /><a href="'.append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id").'">'.$user->lang['RETURN_POST'].'</a>');	
		}
	}
	return;	
}

// remove a user's thanks
function delete_thanks($post_id, $user_id)
{
	global $db, $user, $phpbb_root_path, $phpEx, $forum_id, $config;
	$to_id = request_var('to_id', 0);
	// confirm
	$s_hidden_fields = build_hidden_fields(array(
		'to_id'		=> $to_id,
		'rthanks'	=> $post_id,
		)
	);
	
	if (confirm_box(true))
	{
		if ($user->data['user_type'] != USER_IGNORE && !empty($to_id))
		{
			$sql = "DELETE FROM " . THANKS_TABLE . '
				WHERE post_id ='. (int) $post_id ." AND user_id = " . $user->data['user_id'];
			$db->sql_query($sql);	
			$result = $db->sql_affectedrows($sql);	
			if ($result != 0)
			{
				$lang_act = 'REMOVE';
				send_thanks_pm($user_id, $to_id, $send_pm = true, $post_id, $lang_act);
				if ($config['thanks_info_page'])
				{
					meta_refresh (1, append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id"));
					trigger_error($user->lang['THANKS_INFO_'.$lang_act].'<br /><br /><a href="'.append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id").'">'.$user->lang['RETURN_POST'].'</a>');
				}
				else
				{
					redirect (append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id"));
				}
			}
			else
			{
				trigger_error($user->lang['INCORRECT_THANKS'] . '<br /><br /><a href="'.append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id") .'">'.$user->lang['RETURN_POST'].'</a>');		
			}
		}
	}
	else
	{
		confirm_box(false, 'REMOVE_THANKS', $s_hidden_fields);
		redirect(append_sid("{$phpbb_root_path}viewtopic.$phpEx", "f=$forum_id&amp;p=$post_id#p$post_id"));
	}
	return;
}

// display the text/image saying either to add or remove thanks
function get_thanks_text($post_id)
{
	global $db, $user, $postrow;
	if (already_thanked($post_id, $user->data['user_id']))
	{
		$postrow = array_merge($postrow, array(
			'THANK_ALT'		=> $user->lang['REMOVE_THANKS'],
			'THANKS_IMG'	=> 'removethanks-icon',
		));
		return;
	}
	$postrow = array_merge($postrow, array(
		'THANK_ALT'		=> $user->lang['THANK_POST'],
		'THANKS_IMG'	=> 'thanks-icon',
	));
	return;
}

// change the variable sent via the link to avoid odd errors
function get_thanks_link($post_id)
{
	global $db, $user;
	if (already_thanked($post_id, $user->data['user_id']))
	{
		return 'rthanks';
	}
	return 'thanks';
}

// check if the user has already thanked that post
function already_thanked($post_id, $user_id)
{
	global $db, $thankers;
	$thanked = false;
	foreach((array)$thankers as $key => $value)
	{
		if ($thankers[$key]['post_id'] == $post_id && $thankers[$key]['user_id'] == $user_id)
		{
			$thanked = true;
		}
	}
	return $thanked;
}

// gets the number of users that have thanked the poster
function get_user_count($poster_id, $receive)
{
	global $thankss, $thankeds;
	if ($receive)
	{
		$count = count(array_keys ($thankeds, $poster_id));
		return $count;
	}
	else
	{
		$count = count(array_keys ($thankss, $poster_id));
		return $count;
	}
}

// stuff goes here to avoid over-editing memberlist.php
function output_thanks_memberlist($user_id)
{
	global $db, $user, $row, $phpEx, $template, $phpbb_root_path, $config;
	
	$thankers_member = array();
	$thankered_member = array();
	$thanks = '';
	$thanked = '';
	$poster_receive_count = 0;
	$poster_give_count = 0;
	$poster_limit = $config['thanks_number'];
	
	$sql_array = array(
		'SELECT'	=> 't.*, u.username, u.user_colour',
		'FROM'		=> array(THANKS_TABLE => 't', USERS_TABLE => 'u'),
	);
	$sql_array['WHERE'] = 't.poster_id ='. (int) $user_id .' AND ';
	$sql_array['WHERE'] .= 'u.user_id = t.user_id';
	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$thankers_member[$poster_receive_count] = array(  
			'user_id' 		=> $row['user_id'], 
			'poster_id' 	=> $row['poster_id'], 
			'post_id' 		=> $row['post_id'], 
			'username'		=> $row['username'],
			'user_colour'	=> $row['user_colour'],
		);	
		$poster_receive_count++;
	}	
	$db->sql_freeresult($result);	
    $user_list = array();
	$post_list = array ();
	$i=0;
	foreach($thankers_member as $key => $value)
	{
		if ($thankers_member[$key]['poster_id'] == $user_id)
		{
			$i++;
			$user_list[$i] = array( 
				'username' 		=> $thankers_member[$key]['username'], 
				'user_id' 		=> $thankers_member[$key]['user_id'], 
				'user_colour' 	=> $thankers_member[$key]['user_colour'], 
				'post_id' 		=> $thankers_member[$key]['post_id'], 
			);
		}
	}
	unset ($value);
	ksort($user_list);
	$i = 0;
	foreach($user_list as $value)
	{
		if ($i > 0 and $i <= $poster_limit)
		{
			$thanked .= ', ';
		}
		$i++;
		if ($i <= $poster_limit)
		{
			$thanked .= get_username_string('full', $value['user_id'], $value['username'], $value['user_colour']) . ' &#8594; <a href="'. append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $value['post_id']. '#p' . $value['post_id']) . '">' . $user->lang['FOR_MESSAGE'] . '</a>';
		}	
	}
	unset ($value);

	$sql_array = array(
		'SELECT'	=> 't.*, u.username, u.user_colour',
		'FROM'		=> array(THANKS_TABLE => 't', USERS_TABLE => 'u'),
	);
	$sql_array['WHERE'] = 't.user_id ='. (int) $user_id . ' AND ';
	$sql_array['WHERE'] .= "u.user_id = t.poster_id";
	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$thankered_member[$poster_give_count] = array(  
			'user_id' 		=> $row['user_id'], 
			'poster_id' 	=> $row['poster_id'], 
			'post_id' 		=> $row['post_id'], 
			'username'		=> $row['username'],
			'user_colour'	=> $row['user_colour'],
		);
	$poster_give_count++;
	}
	$db->sql_freeresult($result);	
	
	$i=0;
	foreach($thankered_member as $key => $value)
	{
		if ($thankered_member[$key]['user_id'] == $user_id)
		{
			$i++;
			$post_list[$i] = array( 
				'postername' 		=> $thankered_member[$key]['username'], 
				'poster_id' 		=> $thankered_member[$key]['poster_id'], 
				'poster_colour' 	=> $thankered_member[$key]['user_colour'], 
				'post_id' 			=> $thankered_member[$key]['post_id'], 
			);
		}
	}
	unset ($value);
	ksort($user_list);
	$i = 0;
	foreach($post_list as $value)
	{
		if ($i > 0 and $i <= $poster_limit)
		{
			$thanks .= ', ';
		}
		$i++;
		if ($i <= $poster_limit)
		{
			$thanks .= get_username_string('full', $value['poster_id'], $value['postername'], $value['poster_colour']) . ' &#8592; <a href="'. append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $value['post_id']. '#p' . $value['post_id']) . '">' . $user->lang['FOR_MESSAGE'] . '</a>';
		}
	}
	unset ($value);	

	$template->assign_vars(array(
		'POSTER_RECEIVE_COUNT'	=> $poster_receive_count,
		'THANKS'				=> $thanks,
		'POSTER_GIVE_COUNT'		=> $poster_give_count,
		'THANKED'				=> $thanked,
		'THANKS_PROFILELIST_VIEW'	=>	$config['thanks_profilelist_view'],
	));
}

// stuff goes here to avoid over-editing viewtopic.php
function output_thanks($user_id)
{
	global $db, $user, $poster_id, $postrow, $row, $phpEx, $topic_data, $phpbb_root_path, $config, $forum_id;
	if (!empty($postrow))
	{
		get_thanks_text($row['post_id']);
		$postrow = array_merge($postrow, array(
			'THANKS'				=> get_thanks($row['post_id']),
			'THANKS_LINK'			=> append_sid("{$phpbb_root_path}viewtopic.$phpEx", "p={$row['post_id']}" . (($topic_data['topic_type'] == POST_GLOBAL) ? "&amp;f=$forum_id" : '') . "&amp;" . get_thanks_link($row['post_id']) . "={$row['post_id']}&amp;to_id=$poster_id"),
			'THANK_TEXT'			=> $user->lang['THANK_TEXT_1'],
			'THANK_TEXT_2'			=> (get_thanks_number($row['post_id']) != 1) ? sprintf($user->lang['THANK_TEXT_2pl'], get_thanks_number($row['post_id'])) : $user->lang['THANK_TEXT_2'],
			'THANKS_FROM'			=> $user->lang['THANK_FROM'],
			'POSTER_RECEIVE_COUNT'	=> get_user_count($poster_id, true),
			'POSTER_GIVE_COUNT'		=> get_user_count($poster_id, false),
			'S_IS_OWN_POST'			=> ($user->data['user_id'] == $poster_id) ? true : false,
			'S_POST_ANONYMOUS'		=> ($poster_id == ANONYMOUS) ? true : false,
			'THANK_IMG' 			=> (already_thanked($row['post_id'], $user->data['user_id'])) ? $user->img('removethanks', $user->lang['REMOVE_THANKS']. get_username_string('username', $poster_id, $row['username'], $row['user_colour'], $row['post_username'])) : $user->img('thankposts', $user->lang['THANK_POST']. get_username_string('username', $poster_id, $row['username'], $row['user_colour'], $row['post_username'])),
			'THANKS_POSTLIST_VIEW'	=> $config['thanks_postlist_view'],
			'THANKS_COUNTERS_VIEW'	=> $config['thanks_counters_view'],
			'S_ALREADY_THANKED'		=> already_thanked($row['post_id'], $user->data['user_id']),
			'S_REMOVE_THANKS'		=> $config['remove_thanks'],
			'S_FIRST_POST_ONLY'		=> $config['thanks_only_first_post'],
		));
	}
}

//refresh counts if post delete
function delete_post_thanks($post_id)
{
	global $db; 
	$sql = 'DELETE 
			FROM ' . THANKS_TABLE . "
			WHERE post_id =". (int) $post_id;
	$db->sql_query($sql);
}

//send pm
function send_thanks_pm($user_id, $to_id, $send_pm = true, $post_id = 0, $lang_act)
{
	global $phpEx, $phpbb_root_path, $config, $row, $forum_id, $user, $user_cache;
	
	if (!$user_cache[$to_id]['allow_thanks_pm'])
	{
		return;	
	}
	include($phpbb_root_path . 'includes/functions_privmsgs.' . $phpEx);
	$user->data['user_lang'] = (file_exists($phpbb_root_path . 'language/' . $user->data['user_lang'] . "/mods/thanks_mod.$phpEx")) ? $user->data['user_lang'] : $config['default_lang'];
	$user->add_lang('mods/thanks_mod');
	$massage = '<a href="' . append_sid("{$phpbb_root_path}viewtopic.$phpEx", 'p=' . $post_id .'#p' . $post_id) .'">'. $user->lang['THANKS_PM_MES_'. $lang_act] .'</a>';	
	$pm_data = array(
		'from_user_id'			=> $user->data['user_id'],
		'from_user_ip'			=> $user->ip,
		'from_username'			=> $user->data['username'],
		'enable_sig'			=> false,
		'enable_bbcode'			=> true,
		'enable_smilies'		=> false,
		'enable_urls'			=> false,
		'icon_id'				=> 0,
		'bbcode_bitfield'		=> '',
		'bbcode_uid'			=> '',
		'message'				=> $massage,
		'address_list'			=> array('u' => array($to_id => 'to')),	
	);
		generate_text_for_storage($pm_data['message'], $pm_data['bbcode_uid'], $pm_data['bbcode_bitfield'], $flags, $pm_data['enable_bbcode'], $pm_data['enable_urls'], $pm_data['enable_smilies']);	
	submit_pm('post', $user->lang['THANKS_PM_SUBJECT_'.$lang_act], $pm_data, false);
	return;
}

// create an array of all thanks info
function array_all_thanks($post_list)
{
	global $db, $post_list, $thankers, $thankss, $thankeds;
	$thankers = array();
	$thankss = array();
	$thankeds = array();

	$sql_array = array(
		'SELECT'	=> 't.*, u.username, u.username_clean, u.user_colour',
		'FROM'		=> array(THANKS_TABLE => 't', USERS_TABLE => 'u'),
	);
	$sql_array['WHERE'] = "u.user_id = t.user_id AND (";
	$sql_array['WHERE'] .= 't.post_id ='. (int) $post_list[0];
	for ($i = 1, $end = sizeof($post_list); $i < $end; ++$i)
	{
		$sql_array['WHERE'] .= ' OR t.post_id ='. (int) $post_list[$i];
	}
	$sql_array['WHERE'] .= ')';

	$sql = $db->sql_build_query('SELECT', $sql_array);
	$result = $db->sql_query($sql);
	$j = 0;
	while ($row = $db->sql_fetchrow($result))
	{
		$thankers[$j] = array(  
			'user_id' 			=> $row['user_id'], 
			'poster_id' 		=> $row['poster_id'], 
			'post_id' 			=> $row['post_id'], 
			'username'			=> $row['username'],
			'username_clean'	=> $row['username_clean'],
			'user_colour'		=> $row['user_colour'],
		);
		$j++;
	}
	$db->sql_freeresult($result);

	$sql_array = array(
		'SELECT'	=> 't.poster_id, t.post_id, t.user_id',
		'FROM'		=> array(THANKS_TABLE => 't', POSTS_TABLE => 'p'),
	);
	$sql_array['WHERE'] = "t.poster_id = p.poster_id AND (";
	$sql_array['WHERE'] .= 'p.post_id ='. (int) $post_list[0];
	for ($i = 1, $end = sizeof($post_list); $i < $end; ++$i)
	{
		$sql_array['WHERE'] .= ' OR p.post_id ='. (int) $post_list[$i];
	}
	$sql_array['WHERE'] .= ')';

	$sql = $db->sql_build_query('SELECT_DISTINCT', $sql_array);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$thankeds[] = $row['poster_id']; 
	}

	$db->sql_freeresult($result);

	$sql_array = array(
		'SELECT'	=> 't.user_id, t.post_id, t.poster_id',
		'FROM'		=> array(THANKS_TABLE => 't', POSTS_TABLE => 'p'),
	);
	$sql_array['WHERE'] = "p.poster_id = t.user_id AND (";
	$sql_array['WHERE'] .= 'p.post_id ='. (int) $post_list[0];
	for ($i = 1, $end = sizeof($post_list); $i < $end; ++$i)
	{
		$sql_array['WHERE'] .= ' OR p.post_id ='. (int) $post_list[$i];
	}
	$sql_array['WHERE'] .= ')';

	$sql = $db->sql_build_query('SELECT_DISTINCT', $sql_array);
	$result = $db->sql_query($sql);
	while ($row = $db->sql_fetchrow($result))
	{
		$thankss[] = $row['user_id'];
	}

	$db->sql_freeresult($result);
	return;
}
?>