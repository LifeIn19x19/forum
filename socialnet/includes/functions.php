<?php
/**
 *
 * @package phpBB3 Social Network
 * @version 0.6.1
 * @copyright (c) 2011 Kamahl & Culprit http://socialnetwork.phpbb3hacks.com
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

/**
 * @ignore
 */
if (!defined('SOCIALNET_INSTALLED'))
{
	return;
}

$snFunctions_avatars = array();

/**
 * Funkce pro Social Network
 *
 * @package Socialnet
 *
 * @author Culprit
 * @since 0.5.0
 * @version 1.0.0
 * @copyright CC BY-NB {@link http://creativecommons.org/licenses/by-nd/3.0/}
 */
class snFunctions
{

	var $users_online = array();
	var $onlineUsers = array();
	var $onlineUsersLoaded = false;

	/**
	 * Sepis vsechny online uzivatele
	 * @access private
	 * @return void
	 */
	function onlineUsers($all_online = false)
	{
		global $socialnet_root_path, $phpbb_root_path, $user;

		if ($this->onlineUsersLoaded)
		{
			return $this->onlineUsers;
		}

		$this->onlineUsers = $this->onlineSelect($all_online);

		if (sizeOf($this->onlineUsers) == 0)
		{
			return array();
		}

		$this->onlineUsersLoaded = true;
		// $this->items['onlineUsers']
		return $this->onlineUsers;
	}

	/**
	 * Vyber vsechny online uzivatele
	 * @access private
	 * @return array Pole online uzivatelu, stejne jako z dbal::sql_fetchrowset
	 */
	function onlineSelect($all_online = false)
	{
		global $db, $user, $template, $phpbb_root_path;

		$where_in_only_friends = '';
		$online_time = 300;

		$time = (time() - (intval($this->config['load_online_time']) * 60));
		$time_away = (time() - (intval($this->config['load_online_time']) * 45));
		$users_online = $this->obtain_users_online($time);

		if ($users_online['total_online'] == 0 || ($user->data['user_im_online'] == 0 && !$all_online))
		{
			return array();
		}

		// Extend online users
		$sql_ary = array(
			'SELECT'	 => 'sn_u.user_id, sn_u.user_im_online, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour',
			'FROM'		 => array(
				SN_USERS_TABLE => 'sn_u',
				USERS_TABLE => 'u'
			),
			'WHERE'		 => 'sn_u.user_id = u.user_id AND ' . $db->sql_in_set('sn_u.user_id', $users_online['online_users_ids']),
			'ORDER_BY'	 => 'u.username ASC'
		);

		$sql = $db->sql_build_query('SELECT', $sql_ary);
		$rs = $db->sql_query($sql);
		$rows = $db->sql_fetchrowset($rs);
		$db->sql_freeresult($rs);

		$imagesPath = "./styles/{$user->theme['imageset_path']}/imageset/socialnet/";
		$online_users = array();
		for ($j = 0; $j < count($rows) && isset($rows[$j]); $j++)
		{

			$row = $rows[$j];
			$user_id = $row['user_id'];

			if ($row['user_im_online'] != 1 && !$all_online)
			{
				unset($users_online['online_users'][$user_id]);
				unset($users_online['visible_users'][$user_id]);
				$users_online['visible_online']--;
			}
			else
			{
				if ($this->config['im_only_friends'] == 1)
				{
					$b_friend = in_array($user_id, $this->friends['user_id']);
				}
				else
				{
					$b_friend = true;
				}

				if ($b_friend)
				{
					$row['user_online_status'] = $users_online['online_users'][$user_id]['online'] > $time_away ? 2 : 1;
					$row['user_img_online'] = "{$imagesPath}im_" . ($row['user_online_status'] == 2 ? 'online' : 'away') . ".png";

					$img_avatar = $this->get_user_avatar_resized($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height'], 22);
					$img_avatar = $this->absolutePath($img_avatar);

					$online_users[$user_id] = array(
						'user_id'		 => $user_id,
						'userName'		 => $this->get_username_string($this->config['im_colour_username'], 'no_profile', $user_id, $row['username'], $row['user_colour']),
						'userProfile'	 => $this->get_username_string($this->config['im_colour_username'], 'full', $user_id, $row['username'], $row['user_colour']),
						'userClean'		 => $row['username'],
						'avatar'		 => $img_avatar,
						'online'		 => $row['user_img_online'],
						'status'		 => $row['user_online_status'],
						'im_online'		 => $row['user_im_online'],
					);
				}
				else
				{
					$users_online['visible_online']--;
				}
			}
		}

		return $this->users_online = $online_users;

	}

	/**
	 * Queries the session table to get information about online users
	 * phpBB 3 function - pridana filtrace uzivatele samotneho a nenacitani guests
	 * @access private
	 * @return array An array containing the ids of online, hidden and visible users, as well as statistical info
	 */
	function obtain_users_online($time)
	{
		global $db, $user;

		$online_users = array(
			'online_users_ids'	 => array(),
			'online_users'		 => array(),
			'hidden_users'		 => array(),
			'visible_users'		 => array(),
			'total_online'		 => 0,
			'visible_online'	 => 0,
			'hidden_online'		 => 0
		);

		// a little discrete magic to cache this for 30 seconds
		$sql = 'SELECT s.session_user_id, s.session_ip, s.session_viewonline, (s.session_time) AS s_time
				FROM ' . SESSIONS_TABLE . ' AS s LEFT OUTER JOIN ' . USERS_TABLE . ' AS u ON s.session_user_id = u.user_id
				WHERE u.user_type <> 2
				 	AND s.session_time >= ' . ($time - ((int) ($time % 30))) . '
					AND s.session_user_id <> ' . $user->data['user_id'];

		$result = $db->sql_query($sql);
		$rowset = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);

		for ($i = 0; isset($rowset[$i]); $i++)
		{
			$row = $rowset[$i];
			// Skip multiple sessions for one user
			if (!isset($online_users['online_users'][$row['session_user_id']]))
			{
				$online_users['online_users_ids'][] = (int) $row['session_user_id'];
				$online_users['online_users'][$row['session_user_id']] = array('user_id' => (int) $row['session_user_id'], 'online' => (int) $row['s_time']);
				if ($row['session_viewonline'])
				{
					$online_users['visible_users'][$row['session_user_id']] = (int) $row['session_user_id'];
					$online_users['visible_online']++;
				}
				else
				{
					$online_users['hidden_users'][$row['session_user_id']] = (int) $row['session_user_id'];
					$online_users['hidden_online']++;
				}
			}
		}
		$online_users['total_online'] = $online_users['visible_online'] + $online_users['hidden_online'];

		return $online_users;
	}

	function online_users($json = false)
	{
		global $template, $user;

		if (!$this->config['sn_block_online_users'])
		{
			return;
		}

		//		$rows = $this->onlineUsers($block);
		$rows = $this->onlineSelect($this->config['block_uo_all_users']);

		if ($json)
		{
			header('Content-type: application/json');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			die(json_encode(array('onlineUsers' => $rows, 'user_online' => $user->data['user_im_online'])));
		}

		if (count($rows) == 0)
		{
			return;
		}

		foreach ($rows as $user_id => $usr)
		{
			$template->assign_block_vars('block_user_online', array(
				'USER_ID'		 => $usr['user_id'],
				'USERNAME'		 => $usr['userName'],
				'USERNAME_CLEAN' => $usr['userClean'],
				'U_USER_PROFILE' => $usr['userProfile'],
				'ONLINE'		 => $usr['online'],
				'AVATAR'		 => $usr['avatar'],
				'B_IS_ONLINE'	 => $usr['im_online'],
			));
		}

	}

	function fms_users_sqls($mode, $user_id)
	{
		global $db, $user, $cache;

		$fms_user_sqls = array();
		switch ($mode)
		{
			case 'friendProfile':
				$fms_user_sqls = array_merge($fms_user_sqls, array('mode_short' => 'friend'));
			case 'friend':
				/**
				 * Pratele / priatelia / friends
				 */
				$fms_user_sqls = array_merge($fms_user_sqls, array(
					'sql_pagination' => 'SELECT COUNT(z.user_id) AS total
										FROM ' . ZEBRA_TABLE . ' z
										WHERE z.user_id = ' . $user_id . "
										AND z.friend = 1",
					'sql_content'	 => 'SELECT z.*, u.username, u.username_clean, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
										FROM ' . ZEBRA_TABLE . ' z, ' . USERS_TABLE . ' u
										WHERE z.user_id = ' . $user_id . "
											AND z.friend = 1
											AND u.user_id = z.zebra_id
										ORDER BY u.username_clean ASC",
				));
				break;
			case 'approve':
				/**
				 * Udelene zadosti / udelene ziadosti / granted requests
				 */
				$fms_user_sqls = array_merge($fms_user_sqls, array(
					'user_id_field'	 => 'user_id',
					'sql_pagination' => 'SELECT COUNT(z.user_id) AS total
										FROM ' . ZEBRA_TABLE . ' z
										WHERE z.zebra_id = ' . $user_id . "
											AND z.approval = 1",
					'sql_content'	 => 'SELECT z.*, u.username, u.username_clean, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
										FROM ' . ZEBRA_TABLE . ' z, ' . USERS_TABLE . ' u
										WHERE z.zebra_id = ' . $user_id . "
											AND z.approval = 1
											AND u.user_id = z.user_id
										ORDER BY u.username_clean ASC",
				));
				break;
			case 'cancel':
				/**
				 * Uzivatelovy zadosti / uzivatelovi ziadosti / Users requests
				 */
				$fms_user_sqls = array_merge($fms_user_sqls, array(
					'sql_pagination' => 'SELECT COUNT(z.user_id) AS total
										FROM ' . ZEBRA_TABLE . ' z
										WHERE z.user_id = ' . $user_id . "
											AND z.approval = 1",
					'sql_content'	 => 'SELECT z.*, u.username, u.username_clean, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
										FROM ' . ZEBRA_TABLE . ' z, ' . USERS_TABLE . ' u
										WHERE z.user_id = ' . $user_id . "
											AND z.approval = 1
											AND u.user_id = z.zebra_id
										ORDER BY u.username_clean ASC",
				));
				break;
			case 'mutual':
				/**
				 * Spolecni pratele / spolocni priatelia / mutual friends
				 */
				if (count($this->friends['user_id']))
				{
					$sql_in_set = ' AND ' . $db->sql_in_set('z.zebra_id', $this->friends['user_id']);
				}
				else
				{
					$sql_in_set = ' AND 1 = 0';
				}

				$fms_user_sqls = array_merge($fms_user_sqls, array(
					'sql_pagination' => "SELECT COUNT(z.zebra_id) AS total
											FROM " . ZEBRA_TABLE . " AS z
											WHERE z.user_id = {$user_id} AND z.friend = 1 {$sql_in_set}",
					'sql_content'	 => "SELECT DISTINCT z.*, u.username, u.username_clean, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
											FROM " . ZEBRA_TABLE . " z, " . USERS_TABLE . " u
											WHERE z.user_id = {$user_id} AND u.user_id = z.zebra_id AND z.friend = 1 {$sql_in_set}
											ORDER BY u.username_clean ASC",
				));
				break;
			case 'suggestion':
				/**
				 * Doporuceni pratele / Odporucanie priatelia / suggested friends
				 */
				$cache_people_to_know = $this->friendsCacheNameMutual . $user->data['user_id'];
				$rowset = $cache->get($cache_people_to_know);

				$fms_user_sqls = array_merge($fms_user_sqls, array(
					'user_id_field'	 => 'user_id',
					'rowset'		 => $rowset,
					'total'			 => count($rowset),
				));
				break;
		}

		return $fms_user_sqls;
	}

	/**
	 * @var array $fms_users_default Default values for this::fms_users function
	 *
	 * @property string		mode			- Mode of block. Default: 'friend'.
	 * @property integer	user_id			- User id. 0 is set to be actual logged used. Default: 0.
	 * @property integer	fmsf			- Pagination start. Default 0.
	 * @property integet	limit			- Limit per page. 0 is set to be actual $config['fas_friendlist_limit']. -1 set to be unlimited. Default: 0.
	 * @property string		checkbox		- Name of the checkbox used in user list. '' is checkbox not used. Default: ''.
	 * @property boolean	ajax_load		- is loaded using AJAX. Default: false.
	 * @property boolean	slider			- Instead standard phpBB pagination could be used jQuery UI slider. Default: true.
	 * @property integer	avatar_size 	- Size of avatar. Default: 50.
	 * @property boolean	add_friend_link	- Add add friend link to box if user is not my friend. Default: false.
	 * @property integer	total			- Total count of users for block. Default: 0.
	 * @property array		rowset			- Rowset of available users for block. Default: null.	
	 * @property string		sql_pagination	- Sql to select total count for pagination. If sets override param total. Default: ''.
	 * @property string		sql_content		- Sql to select users for display. If sets override rowset. Default: ''.
	 * @property string		user_id_field	- What is current ID of displayed users. Default: 'zebra_id'.
	 * @property boolean	random			- Random list for select. Default: false.
	 * @property string		tpl_name		- Specific template. Default: 'socialnet/block_fms_users'.
	 */
	var $fms_users_default = array(
		'mode'				 => 'friend',
		'user_id'			 => 0,
		'fmsf'				 => 0,
		'limit'				 => 0,
		'checkbox'			 => '',
		'ajax_load'			 => false,
		'slider'			 => true,
		'avatar_size'		 => 50,
		'add_friend_link'	 => false,
		'total'				 => 0,
		'rowset'			 => null,
		'sql_pagination'	 => '',
		'sql_content'		 => '',
		'user_id_field'		 => 'zebra_id',
		'random'			 => false,
		'tpl_name'			 => 'socialnet/block_fms_users',
		'profile_link'		 => true,
	);

	/**
	 * snFMSUSers :: fms_users
	 * This function generate pagination and block with users dependent on parameters.
	 *
	 * @param array $params Parameter array. For valid values look to @var $fms_users_default
	 *
	 * @return array
	 * @property string		pagination		- Current pagination string
	 * @property string		content			- Current content of block
	 * @property boolean	is_not_empty	- Is not empty current block
	 *
	 * Automatickly are filled these template variables
	 *	'SN_FMS_BLOCK_' . UPPER($mode) . '_PAGINATION_STRING'	- pagination string
	 *	'SN_FMS_BLOCK_' . UPPER($mode) '_CONTENT'				- content of the block
	 *	'SN_FMS_BLOCK_' . UPPER($mode) '_IS_NOT_EMPTY'			- if the block is/isnt empty
	 *
	 */
	function fms_users($params = array())
	{
		global $db, $user, $template, $cache;

		if (is_array($params))
		{
			$params = array_merge($this->fms_users_default, $params);
			foreach ($params as $idx => $value)
			{
				$$idx = $value;
			}
		}
		else
		{
			return;
		}

		if (!isset($mode_short))
		{
			$mode_short = $mode;
		}

		$user_id = ($user_id == 0) ? $user->data['user_id'] : $user_id;

		if (!empty($sql_pagination))
		{
			$rs = $db->sql_query($sql_pagination);
			$row = $db->sql_fetchrow($rs);
			$db->sql_freeresult($rs);
			$total = $row['total'];
		}

		$template->assign_vars(array(
			'SN_FMS_BLOCK_USER_PAGINATION_MODE'	 => $mode,
			'SN_FMS_BLOCK_USER_AJAX_LOAD'		 => $ajax_load,
			'SN_FMS_BLOCK_USER_USE_SLIDER'		 => $slider,
			'SN_FMS_BLOCK_USER_B_CHECKBOX'		 => $checkbox != '',
			'SN_FMS_BLOCK_USER_CHECKBOX_NAME'	 => $checkbox,
			'SN_FMS_BLOCK_PROFILE_LINK'			 => $profile_link,
		));

		$pagination = $this->_fms_users_pagination($mode_short, $total, $fmsf, $limit, $user_id, $tpl_name, $profile_link);

		$limit = ($limit == 0) ? $this->config['fas_friendlist_limit'] : $limit;
		if (!empty($sql_content))
		{
			if ($limit != - 1 && !$random)
			{
				$result = $db->sql_query_limit($sql_content, $limit, $fmsf);
			}
			else
			{
				$result = $db->sql_query($sql_content);
			}
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);
		}

		if ($random)
		{
			shuffle($rowset);
		}

		$block_content = $this->_fms_users_fill($rowset, $user_id_field, $limit, $avatar_size, $add_friend_link, $tpl_name);

		$lmode = strtoupper($mode);
		$lmodeshort = strtoupper($mode_short);
		if (!$block_content['is_not_empty'])
		{
			$block_content['content'] = isset($user->lang['FAS_' . $lmodeshort . '_NO_TOTAL']) ? $user->lang['FAS_' . $lmodeshort . '_NO_TOTAL'] : '{ FAS ' . $lmodeshort . ' NO TOTAL }';
			$pagination = '';
		}

		$template->assign_vars(array(
			'SN_FMS_BLOCK_' . $lmode . '_PAGINATION_STRING' => $pagination,
			'SN_FMS_BLOCK_' . $lmode . '_CONTENT'			 => $block_content['content'],
			'SN_FMS_BLOCK_' . $lmode . '_IS_NOT_EMPTY'		 => $block_content['is_not_empty'],
		));

		return array('pagination' => $pagination, 'content' => $block_content['content'], 'is_not_empty' => $block_content['is_not_empty']);
	}

	function _fms_users_pagination($mode, $total, $start, $limit, $user_id, $tpl_name, $profile_link = true)
	{
		global $user, $template;

		$limit = ($limit == 0) ? $this->config['fas_friendlist_limit'] : $limit;
		$user_id = ($user_id == 0) ? $user->data['user_id'] : $user_id;

		$total_pages = ceil($total / $limit);
		$on_page = floor($start / $limit) + 1;

		$start_cnt = min(max(1, $on_page - 2), max(1, $total_pages - 4));
		$end_cnt = max(min($total_pages, $on_page + 2), min($total_pages, 5));

		$lmode = strtoupper($mode);

		$pagination_total_lang = 'FAS_' . $lmode;
		$pagination_total_lang .= ($total == 0) ? '_NO' : '';
		$pagination_total_lang .= ($total > 1) ? 'S' : '';
		$pagination_total_lang .= '_TOTAL';

		$template->assign_vars(array(
			'SN_FMS_BLOCK_USER_PAGINATION_START_CNT'	 => $start_cnt,
			'SN_FMS_BLOCK_USER_PAGINATION_END_CNT'		 => $end_cnt,
			'SN_FMS_BLOCK_USER_PAGINATION_ON_PAGE'		 => $on_page,

			'SN_FMS_BLOCK_USER_PAGINATION_USER_ID'		 => $user_id,
			'SN_FMS_BLOCK_USER_PAGINATION_TOTAL_PAGES'	 => $total_pages,
			'SN_FMS_BLOCK_USER_PAGINATION_TOTAL_PAGES_1' => $total_pages - 1,
			'SN_FMS_BLOCK_USER_PAGINATION_LIMIT'		 => $limit,

			'SN_FMS_BLOCK_USER_PAGINATION_CURRENT_PAGE'	 => sprintf($user->lang['PAGE_OF'], $on_page, $total_pages),

			'SN_FMS_BLOCK_USER_PAGINATION_TOTAL'		 => sprintf(@$user->lang[$pagination_total_lang], $total),
			'SN_FMS_BLOCK_PROFILE_LINK'					 => $profile_link?'1':'0'
		));

		if ($total_pages > 1)
		{
			if ($start_cnt != 1)
			{
				$template->assign_block_vars('sn_fms_block_user_pagination', array(
					'PAGE_START'	 => 0,
					'PAGE_NUMBER'	 => '1',
					'PAGE_ACTIVE'	 => $on_page == 1,
				));
			}

			for ($i = $start_cnt; $i <= $end_cnt; $i++)
			{
				$template->assign_block_vars('sn_fms_block_user_pagination', array(
					'PAGE_START'	 => ($i - 1) * $limit,
					'PAGE_NUMBER'	 => $i,
					'PAGE_ACTIVE'	 => $on_page == $i,
				));

			}

			if ($end_cnt < $total_pages)
			{
				$template->assign_block_vars('sn_fms_block_user_pagination', array(
					'PAGE_START'	 => ($total_pages - 1) * $limit,
					'PAGE_NUMBER'	 => $total_pages,
					'PAGE_ACTIVE'	 => $on_page == $total_pages,
				));

			}
		}
		$template->set_filenames(array('sn_fms_block_user_pagination' => $tpl_name . '_pagination.html'));

		$return = $this->get_page('sn_fms_block_user_pagination');
		$template->destroy_block_vars('sn_fms_block_user_pagination');

		return $return;
	}

	function _fms_users_fill($rowset, $user_id_field, $limit, $avatar_size, $add_friend_link, $tpl_name)
	{
		global $db, $template, $phpbb_root_path, $phpEx, $config, $user;

		$is_not_empty = false;
		if (!empty($rowset))
		{
			$counter = 0;
			foreach ($rowset as $idx => $row)
			{
				if ($counter >= $limit && $limit != - 1)
				{
					break;
				}

				$img_avatar = $this->get_user_avatar_resized($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height'], $avatar_size);

				$u_add_friend = '';
				if ($add_friend_link && !in_array($row[$user_id_field], $this->friends['user_id']))
				{
					$u_add_friend = append_sid("{$phpbb_root_path}ucp.{$phpEx}", "i=zebra&amp;add={$row['username']}");
				}

				$is_not_empty = true;
				$template->assign_block_vars('sn_fms_block_user', array(
					'USER_ID'			 => $row[$user_id_field],
					'USERNAME'			 => $this->get_username_string($config['fas_colour_username'], 'no_profile', $row[$user_id_field], $row['username'], $row['user_colour']),
					'USER_PROFILE'		 => $this->get_username_string($config['fas_colour_username'], 'full', $row[$user_id_field], $row['username'], $row['user_colour']),
					'USERNAME_NO_COLOR'	 => $row['username'],
					'U_PROFILE'			 => append_sid("{$phpbb_root_path}memberlist.{$phpEx}?mode=viewprofile&amp;u={$row[$user_id_field]}"),
					'U_ADD_FRIEND'		 => $u_add_friend,
					'AVATAR'			 => $img_avatar,
				));
				$counter++;
			}
		}
		$template->set_filenames(array('sn_fms_block_user_content' => $tpl_name . '.html'));
		$content = $this->get_page('sn_fms_block_user_content');
		$template->destroy_block_vars('sn_fms_block_user');

		return array('content' => $content, 'is_not_empty' => $is_not_empty);
	}

	/**
	 * Load block using block function
	 *
	 * @param string $block_name Name of Block that should be load
	 * @return mixed
	 * @since 0.6.1
	 * @author Culprit
	 */
	function block($block_name)
	{
		return $this->$block_name();
	}

	/**
	 * Load blocks using block function
	 *
	 * @param array $blocks Name of Blocks that should be load
	 * @return mixed
	 * @since 0.6.1
	 * @author Culprit
	 */
	function blocks($blocks)
	{
		if (is_array($blocks) && !empty($blocks))
		{
			$return = array();
			foreach ($blocks as $idx => $block_name)
			{
				$block_name = trim($block_name);
				$return[$block_name] = $this->$block_name();
			}
		}
		else
		{
			$blocks = explode('', $blocks);
			$return = $this->blocks($blocks);
		}

		return $return;
	}

	/**
	 * Load login block id necessary
	 *
	 * @access public
	 * @since 0.5.2
	 * @return void
	 */
	function login()
	{
		global $phpbb_root_path, $phpEx;
		global $user, $template, $db, $auth;

		if ($user->data['user_type'] != USER_IGNORE)
		{
			return false;
		}

		if ($this->config['mp_hide_for_guest'])
		{
			header('Location: index.php');
		}

		if (!class_exists('phpbb_captcha_factory'))
		{
			include($phpbb_root_path . 'includes/captcha/captcha_factory.' . $phpEx);
		}

		$err = '';

		// Make sure user->setup() has been called
		if (empty($user->lang))
		{
			$user->setup();
		}

		if (isset($_POST['login']))
		{
			$user->add_lang('ucp');
			$username = request_var('username', '', true);
			$password = request_var('password', '', true);
			$autologin = (!empty($_POST['autologin'])) ? true : false;
			$viewonline = (!empty($_POST['viewonline'])) ? 0 : 1;

			// If authentication is successful we redirect user to previous page
			$result = $auth->login($username, $password, $autologin, $viewonline, false);

			// The result parameter is always an array, holding the relevant information...
			if ($result['status'] == LOGIN_SUCCESS)
			{
				$redirect = redirect(append_sid("{$phpbb_root_path}mainpage.$phpEx"));
			}

			// Something failed, determine what...
			if ($result['status'] == LOGIN_BREAK)
			{
				trigger_error($result['error_msg']);
			}

			// Special cases... determine
			switch ($result['status'])
			{
				case LOGIN_ERROR_ATTEMPTS:
					$captcha = phpbb_captcha_factory::get_instance($this->config['captcha_plugin']);
					$captcha->init(CONFIRM_LOGIN);

					$template->assign_vars(array(
						'CAPTCHA_TEMPLATE' => $captcha->get_template(), ));

					$err = $user->lang[$result['error_msg']];
					break;

				case LOGIN_ERROR_PASSWORD_CONVERT:
					$err = sprintf($user->lang[$result['error_msg']], ($this->config['email_enable']) ? '<a href="' . append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=sendpassword') . '">' : '', ($this->config['email_enable']) ? '</a>' : '', ($this->config['board_contact']) ? '<a href="mailto:' . htmlspecialchars($this->config['board_contact']) . '">' : '', ($this->config['board_contact']) ? '</a>' : '');
					break;

				default:
					// Username, password, etc...
					$err = $user->lang[$result['error_msg']];

					// Assign admin contact to some error messages
					if ($result['error_msg'] == 'LOGIN_ERROR_USERNAME' || $result['error_msg'] == 'LOGIN_ERROR_PASSWORD')
					{
						$err = (!$this->config['board_contact']) ? sprintf($user->lang[$result['error_msg']], '', '') : sprintf($user->lang[$result['error_msg']], '<a href="mailto:' . htmlspecialchars($this->config['board_contact']) . '">', '</a>');
					}
					break;
			}
		}

		$s_hidden_fields = array(
			'sid'		 => $user->session_id,
			'redirect'	 => append_sid("{$phpbb_root_path}mainpage.{$phpEx}"),
		);

		$template->assign_vars(array(
			'LOGIN_ERROR'			 => $err,
			'U_SEND_PASSWORD'		 => ($this->config['email_enable']) ? append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=sendpassword') : '',
			'U_RESEND_ACTIVATION'	 => ($this->config['require_activation'] == USER_ACTIVATION_SELF && $this->config['email_enable']) ? append_sid("{$phpbb_root_path}ucp.$phpEx", 'mode=resend_act') : '',
			'S_HIDDEN_FIELDS'		 => build_hidden_fields($s_hidden_fields),

			'S_DISPLAY_WELCOME'		 => $this->config['mp_display_welcome'],
		));

		return true;
	}

	/**
	 * Load my profile Block
	 *
	 * @access public
	 * @since 0.6.1
	 * @return void
	 */
	function myprofile()
	{
		global $phpbb_root_path, $phpEx;
		global $user, $template;

		if (!$this->config['sn_block_myprofile'])
		{
			return;
		}

		$template_vars = array(
			'S_MY_USERNAME'		 => $this->get_username_string($this->config['mp_colour_username'], 'full', $user->data['user_id'], $user->data['username'], $user->data['user_colour']),
			'S_MY_USER_AVATAR'	 => $this->get_user_avatar_resized($user->data['user_avatar'], $user->data['user_avatar_type'], $user->data['user_avatar_width'], $user->data['user_avatar_height'], 50),
			'U_EDIT_MY_PROFILE'	 => append_sid("{$phpbb_root_path}ucp.$phpEx", 'i=profile'),
			'USER_ID'			 => $user->data['user_id'],
		);

		$template->assign_vars($template_vars);

		return true;
	}

	/**
	 * Load Menu Block
	 *
	 * @access public
	 * @since 0.6.1
	 * @return void
	 */
	function menu()
	{
		global $phpbb_root_path, $phpEx;
		global $user, $template;

		if (!$this->config['sn_block_menu'])
		{
			return;
		}

		$template->set_filenames(array('sn_user_menu' => 'socialnet/block_menu_item.html'));
		$block_menu_html = $this->_gen_menu();

		$template->assign_var('SN_BLOCK_MENU_HTML', $block_menu_html);

		return true;
	}

	/**
	 * Load board Statistics Block
	 *
	 * @access public
	 * @since 0.6.1
	 * @return void
	 */
	function statistics()
	{
		global $user, $template;

		if (!$this->config['sn_block_statistics'])
		{
			return;
		}

		$time = (time() - $this->config['board_startdate']) / 86400;

		$total_posts = $this->config['num_posts'];
		$total_topics = $this->config['num_topics'];
		$total_users = $this->config['num_users'];
		$topics_per_day = ($total_topics) ? round($total_topics / $time, 0) : 0;
		$posts_per_day = ($total_posts) ? round($total_posts / $time, 0) : 0;
		$users_per_day = round($total_users / $time, 0);

		if ($topics_per_day > $total_topics)
		{
			$topics_per_day = $total_topics;
		}
		if ($posts_per_day > $total_posts)
		{
			$posts_per_day = $total_posts;
		}
		if ($users_per_day > $total_users)
		{
			$users_per_day = $total_users;
		}

		$l_total_user_s = 'SN_MP_TOTAL_USERS';
		$l_total_post_s = 'SN_MP_TOTAL_POSTS';
		$l_total_topic_s = 'SN_MP_TOTAL_TOPICS';
		$l_topics_per_day_s = 'SN_MP_TOPICS_PER_DAY';
		$l_posts_per_day_s = 'SN_MP_POSTS_PER_DAY';
		$l_users_per_day_s = 'SN_MP_USERS_PER_DAY';

		$template->assign_vars(array(
			'TOTAL_POSTS'	 => sprintf($user->lang[$l_total_post_s], $total_posts),
			'TOTAL_TOPICS'	 => sprintf($user->lang[$l_total_topic_s], $total_topics),
			'TOTAL_USERS'	 => sprintf($user->lang[$l_total_user_s], $total_users),
			'TOPICS_PER_DAY' => sprintf($user->lang[$l_topics_per_day_s], $topics_per_day),
			'POSTS_PER_DAY'	 => sprintf($user->lang[$l_posts_per_day_s], $posts_per_day),
			'USERS_PER_DAY'	 => sprintf($user->lang[$l_users_per_day_s], $users_per_day),
		));

	}

	/**
	 * Thanks to Silli for this function
	 *
	 * @access public
	 * @since 0.6.0
	 * @return void
	 */
	function birthday()
	{
		global $db, $template, $phpbb_root_path, $phpEx, $user, $cache;

		if (!$this->config['sn_block_birthday'])
		{
			return;
		}

		$cache_friends_birthday = '_snMpFriendsBirthday' . $user->data['user_id'];
		$friends_birthday = $cache->get($cache_friends_birthday);

		$now = getdate(time() + $user->timezone + $user->dst - date('Z'));
		//$now = getdate(time());

		if (empty($friends_birthday))
		{
			$my_friends = $this->friends['user_id'];

			$cache_days = 7;
			$sql_days = array();

			while ($cache_days >= 0)
			{
				$day = getdate(time() + 86400 * $cache_days + $user->timezone + $user->dst - date('Z'));
				//$day = getdate(time() + 86400 * $cache_days);
				$sql_days[] = "u.user_birthday LIKE '" . $db->sql_escape(sprintf('%2d-%2d-', $day['mday'], $day['mon'])) . "%'";
				$cache_days--;
			}

			switch ($db->sql_layer)
			{
				case 'mssql':
				case 'mssql_odbc':
					$order_by = 'u.user_birthday ASC';
					break;

				default:
					$order_by = 'SUBSTRING(u.user_birthday FROM 4 FOR 2) ASC, SUBSTRING(u.user_birthday FROM 1 FOR 2) ASC, u.username_clean ASC';
					break;
			}
			$sql = 'SELECT u.user_id, u.username, u.user_birthday, u.user_colour
							FROM ' . USERS_TABLE . ' u
							LEFT JOIN ' . BANLIST_TABLE . " b ON (u.user_id = b.ban_userid)
							WHERE (b.ban_id IS NULL	OR b.ban_exclude = 1)
								AND " . $db->sql_in_set('u.user_id', $my_friends, false, true) . "
								AND (" . implode(" OR ", $sql_days) . ")
								AND u.user_type IN (" . USER_NORMAL . ', ' . USER_FOUNDER . ')
							ORDER BY ' . $order_by;
			$rs = $db->sql_query($sql);

			$friends_birthday = $db->sql_fetchrowset($rs);
			$db->sql_freeresult($rs);
			// Cache up to midnight
			$cache->put($cache_friends_birthday, $friends_birthday, mktime(23, 59, 59) - time());
		}

		for ($i = 0; isset($friends_birthday[$i]); $i++)
		{
			$row = $friends_birthday[$i];

			$arr_birthday = explode('-', $row['user_birthday']);

			$birth_time = mktime(0, 0, 1, $arr_birthday[1], $arr_birthday[0], $now['year']);
			$diff_days = round(abs($birth_time - strtotime(date('Y-m-d'))) / 3600 / 24);

			$row['username'] = sprintf($user->lang['SN_MP_BIRTHDAY_USERNAME'], $row['username']);
			$template->assign_block_vars('friends_birthday', array(
				'USERNAME'			 => $this->get_username_string($this->config['mp_colour_username'], 'full_add', $row['user_id'], $row['username'], $row['user_colour']),
				'SN_MP_BIRTHDAY_ON'	 => sprintf($user->lang['SN_MP_BIRTHDAY_' . ($diff_days < 2 ? '1' : '2')], $user->format_date($birth_time, '|j. n.|', false)),
				'U_FRIEND_LINK'		 => append_sid("{$phpbb_root_path}memberlist.{$phpEx}", "mode=viewprofile&amp;u=" . $row['user_id']),
			));
		}

	}

	/**
	 * Load Search Block
	 *
	 * @access public
	 * @since 0.6.1
	 * @return void
	 */
	function search()
	{
		// NOTHING TO FILL	
		if (!$this->config['sn_block_search'])
		{
			return;
		}

	}

	/**
	 * Load Friends Suggestions Block
	 *
	 * @param integer $limit How much Friend Sugessions should be displayed
	 * @access public
	 * @since 0.6.1
	 * @return void
	 */
	function friends_suggestions($limit = 4)
	{
		global $db, $template, $phpbb_root_path, $phpEx, $user, $cache, $socialnet;

		if (!$this->config['sn_block_friends_suggestions'])
		{
			return;
		}

		$cache_people_to_know = $this->friendsCacheNameMutual . $user->data['user_id'];
		$people_to_know = $cache->get($cache_people_to_know);

		if (empty($people_to_know))
		{
			// My friends
			$sql = "SELECT zebra_id
					FROM " . ZEBRA_TABLE . "
					WHERE user_id = {$user->data['user_id']} AND (friend = 1 OR approval = 1)";
			$rs = $db->sql_query($sql);

			$mp_my_friends_ary = $mp_my_friends_me_ary = array();

			while ($row = $db->sql_fetchrow($rs))
			{
				$mp_my_friends_ary[] = $row['zebra_id'];
			}

			if (empty($mp_my_friends_ary))
			{
				return;
			}

			$mp_my_friends_me_ary = $mp_my_friends_ary;
			$mp_my_friends_me_ary[] = $user->data['user_id'];

			$sql = "SELECT DISTINCT u.user_id, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
					FROM " . ZEBRA_TABLE . " AS z, " . USERS_TABLE . " AS u
					WHERE " . $db->sql_in_set('z.user_id', $mp_my_friends_ary, false, true) . " AND " . $db->sql_in_set('z.zebra_id', $mp_my_friends_me_ary, true, true) . " AND z.friend = 1 AND z.zebra_id = u.user_id";
			$rs = $db->sql_query($sql);

			$people_to_know = $db->sql_fetchrowset($rs);
			$db->sql_freeresult($rs);
			// Cache 1 day
			$cache->put($cache_people_to_know, $people_to_know, 86400);
		}

		$this->fms_users(array(
			'mode'				 => 'suggestion',
			'user_id'			 => $user->data['user_id'],
			'fmsf'				 => 0,
			'limit'				 => $limit,
			'slider'			 => false,
			'avatar_size'		 => 50,
			'add_friend_link'	 => true,
			'user_id_field'		 => 'user_id',
			'rowset'			 => $people_to_know,
			'random'			 => true
		));
	}

	/**
	 * Load Friend Requests
	 *
	 * @access public
	 * @since 0.5.2
	 * @return void
	 */
	function friend_requests()
	{
		global $db, $template, $user, $phpbb_root_path, $phpEx;

		if (!$this->config['sn_block_friend_requests'])
		{
			return;
		}

		$sql = "SELECT u.user_id, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
					FROM " . ZEBRA_TABLE . " AS z, " . USERS_TABLE . " AS u
					WHERE z.approval = 1 AND z.zebra_id = {$user->data['user_id']} AND z.user_id = u.user_id";

		$limit_requests = 5;
		$i_avatar_maxHeight = 36;

		$rs = $db->sql_query($sql);

		$rows = $db->sql_fetchrowset($rs);
		$db->sql_freeresult($rs);

		$template->assign_var('S_NUM_FRIEND_REQUESTS', (string) count($rows));

		for ($i = 0; $i < $limit_requests && isset($rows[$i]); $i++)
		{
			$row = $rows[$i];

			$img_avatar = $this->get_user_avatar_resized($row['user_avatar'], $row['user_avatar_type'], $row['user_avatar_width'], $row['user_avatar_height'], $i_avatar_maxHeight);

			$template->assign_block_vars('friend_requests', array(
				'AVATAR'			 => $img_avatar,
				'USERNAME'			 => $this->get_username_string($this->config['mp_colour_username'], 'full', $row['user_id'], $row['username'], $row['user_colour']),
				'USERNAME_PROFILE'	 => $this->get_username_string($this->config['mp_colour_username'], 'profile', $row['user_id'], $row['username'], $row['user_colour']),
				'USERNAME_CLEAN'	 => $row['username'],
				'USER_ID'			 => $row['user_id'],
			));

		}

	}

	/**
	 * Load Last Recent Discussions Block
	 *
	 * @access public
	 * @since 0.6.1
	 * @return void
	 */
	function recent_discussions()
	{
		global $db, $auth, $template, $user;
		global $phpbb_root_path, $phpEx;

		if (!isset($this->config['mp_num_last_posts']) || $this->config['mp_num_last_posts'] == 0 || !$this->config['sn_block_recent_discussions'])
		{
			return;
		}

		$a_f_auth_read = $auth->acl_getf('f_read');
		$a_f_read = array();
		if (!empty($a_f_auth_read))
		{
			foreach ($a_f_auth_read as $i_f_id => $a_auth)
			{
				if ($a_auth['f_read'] == 1)
				{
					$a_f_read[] = $i_f_id;
				}
			}
		}

		$last_posts = 'SELECT p.post_id, p.topic_id, p.post_time, p.forum_id, t.topic_title, f.forum_name
						FROM ' . POSTS_TABLE . ' p
							LEFT JOIN ' . TOPICS_TABLE . ' t ON p.topic_id = t.topic_id
							LEFT JOIN ' . FORUMS_TABLE . ' f ON p.forum_id = f.forum_id
						WHERE p.post_approved = 1
							AND ' . $db->sql_in_set('p.forum_id', $a_f_read, false, true) . '
						ORDER BY p.post_id DESC';
		$last_posts_result = $db->sql_query_limit($last_posts, $this->config['mp_num_last_posts']);
		$last_posts_rowset = $db->sql_fetchrowset($last_posts_result);
		$db->sql_freeresult($last_posts_result);

		for ($i = 0; isset($last_posts_rowset[$i]); $i++)
		{
			$last_posts_row = $last_posts_rowset[$i];
			$template->assign_block_vars('last_posts', array(
				'TOPIC_TITLE'		 => $last_posts_row['topic_title'],
				'POST_LINK'			 => append_sid("{$phpbb_root_path}viewtopic.$phpEx", "t=" . $last_posts_row['topic_id'] . "&amp;p=" . $last_posts_row['post_id'] . "#p" . $last_posts_row['post_id']),
				'TOPIC_FORUM'		 => $last_posts_row['forum_name'],
				'TOPIC_FORUM_LINK'	 => append_sid("{$phpbb_root_path}viewforum.$phpEx", "f=" . $last_posts_row['forum_id']),
				'POST_TIME'			 => $this->time_ago($last_posts_row['post_time']),
			));
		}
	}

	/**
	 * Post new status from PHP code
	 *
	 * @since 0.5.2
	 * @author Culprit
	 * @access public
	 * @param string $new_status
	 * @return void
	 */
	function post_status($new_status, $wall_id = 0)
	{
		global $socialnet;
		if (!isset($socialnet) || !is_object($socialnet))
		{
			return false;
		}

		if (!isset($socialnet->modules_obj['userstatus']) || !is_object($socialnet->modules_obj['userstatus']))
		{
			return false;
		}

		$_REQUEST['status'] = $new_status;
		if ($wall_id != 0)
		{
			$_REQUEST['wall_id'] = $wall_id;
		}

		$socialnet->modules_obj['userstatus']->_status_share();
	}

	function _gen_menu($parent_id = 0)
	{
		global $template, $db, $user;

		$sql = "SELECT *
		            FROM " . SN_MENU_TABLE . "
		              WHERE button_display = 1
		                AND parent_id = {$parent_id}
		              ORDER BY left_id";
		$result = $db->sql_query($sql);

		$menu = array();

		while ($row = $db->sql_fetchrow($result))
		{
			if (($row['button_only_registered'] && $user->data['user_id'] == ANONYMOUS) || ($row['button_only_guest'] && $user->data['user_id'] != ANONYMOUS))
			{
				continue;
			}

			$menu[] = array(
				'ID'		 => $row['button_id'],
				'PARENT'	 => $parent_id,
				'URL'		 => $row['button_url'],
				'NAME'		 => $row['button_name'],
				'EXTERNAL'	 => $row['button_external'],
				'SUBMENU'	 => $this->_gen_menu($row['button_id']),
			);

		}

		$db->sql_freeresult($result);

		$template->destroy_block_vars('sn_user_menu');
		for ($i = 0; isset($menu[$i]); $i++)
		{
			$template->assign_block_vars('sn_user_menu', $menu[$i]);
		}

		return $this->get_page('sn_user_menu');
	}

	function relationship_status($status_id, $approved = false)
	{
		global $user;

		switch ($status_id)
		{
			case '1':
				$status = $user->lang['SN_UP_SINGLE'];
				break;
			case '2':
				$status = $user->lang['SN_UP_IN_RELATIONSHIP'] . (($approved) ? ' ' . $user->lang['SN_UP_WITH'] : '');
				break;
			case '3':
				$status = $user->lang['SN_UP_ENGAGED'] . (($approved) ? ' ' . $user->lang['SN_UP_TO'] : '');
				break;
			case '4':
				$status = $user->lang['SN_UP_MARRIED'] . (($approved) ? ' ' . $user->lang['SN_UP_TO'] : '');
				break;
			case '5':
				$status = $user->lang['SN_UP_ITS_COMPLICATED'] . (($approved) ? ' ' . $user->lang['SN_UP_WITH'] : '');
				break;
			case '6':
				$status = $user->lang['SN_UP_OPEN_RELATIONSHIP'] . (($approved) ? ' ' . $user->lang['SN_UP_WITH'] : '');
				break;
			case '7':
				$status = $user->lang['SN_UP_WIDOWED'];
				break;
			case '8':
				$status = $user->lang['SN_UP_SEPARATED'];
				break;
			case '9':
				$status = $user->lang['SN_UP_DIVORCED'];
				break;
			default:
				$status = '';
		}

		return $status;
	}

	function family_status($status_id)
	{
		global $user;

		switch ($status_id)
		{
			case '20':
				$status = $user->lang['SN_UP_SISTER'];
				break;
			case '21':
				$status = $user->lang['SN_UP_BROTHER'];
				break;
			case '22':
				$status = $user->lang['SN_UP_DAUGHTER'];
				break;
			case '23':
				$status = $user->lang['SN_UP_SON'];
				break;
			case '24':
				$status = $user->lang['SN_UP_MOTHER'];
				break;
			case '25':
				$status = $user->lang['SN_UP_FATHER'];
				break;
			case '26':
				$status = $user->lang['SN_UP_AUNT'];
				break;
			case '27':
				$status = $user->lang['SN_UP_UNCLE'];
				break;
			case '28':
				$status = $user->lang['SN_UP_NIECE'];
				break;
			case '29':
				$status = $user->lang['SN_UP_NEPHEW'];
				break;
			case '30':
				$status = $user->lang['SN_UP_COUSIN_FEMALE'];
				break;
			case '31':
				$status = $user->lang['SN_UP_COUSIN_MALE'];
				break;
			case '32':
				$status = $user->lang['SN_UP_GRANDDAUGHTER'];
				break;
			case '33':
				$status = $user->lang['SN_UP_GRANDSON'];
				break;
			case '34':
				$status = $user->lang['SN_UP_GRANDMOTHER'];
				break;
			case '35':
				$status = $user->lang['SN_UP_GRANDFATHER'];
				break;
			case '36':
				$status = $user->lang['SN_UP_SISTER_IN_LAW'];
				break;
			case '37':
				$status = $user->lang['SN_UP_BROTHER_IN_LAW'];
				break;
			case '38':
				$status = $user->lang['SN_UP_MOTHER_IN_LAW'];
				break;
			case '39':
				$status = $user->lang['SN_UP_FATHER_IN_LAW'];
				break;
			case '40':
				$status = $user->lang['SN_UP_DAUGHTER_IN_LAW'];
				break;
			case '41':
				$status = $user->lang['SN_UP_SON_IN_LAW'];
				break;
			default:
				$status = '';
		}

		return $status;
	}

	function return_family($status_id, $sex)
	{
		global $user, $socialnet;

		$status_arr = array();
		$status_options = '';

		if ($status_id == '20' || $status_id == '21')
		{
			$status_arr = array(20, 21);
		}
		elseif ($status_id == '22' || $status_id == '23')
		{
			$status_arr = array(24, 25);
		}
		elseif ($status_id == '24' || $status_id == '25')
		{
			$status_arr = array(22, 23);
		}
		elseif ($status_id == '26' || $status_id == '27')
		{
			$status_arr = array(28, 29);
		}
		elseif ($status_id == '28' || $status_id == '29')
		{
			$status_arr = array(26, 27);
		}
		elseif ($status_id == '30' || $status_id == '31')
		{
			$status_arr = array(30, 31);
		}
		elseif ($status_id == '32' || $status_id == '33')
		{
			$status_arr = array(34, 35);
		}
		elseif ($status_id == '34' || $status_id == '35')
		{
			$status_arr = array(32, 33);
		}
		elseif ($status_id == '36' || $status_id == '37')
		{
			$status_arr = array(36, 37);
		}
		elseif ($status_id == '38' || $status_id == '39')
		{
			$status_arr = array(40, 41);
		}
		elseif ($status_id == '40' || $status_id == '41')
		{
			$status_arr = array(38, 39);
		}

		$male_option = '<option value="' . $status_arr[1] . '">' . $socialnet->family_status($status_arr[1]) . '</option>';
		$female_option = '<option value="' . $status_arr[0] . '">' . $socialnet->family_status($status_arr[0]) . '</option>';

		if ($sex == 1)
		{
			$status_options = $male_option;
		}
		elseif ($sex == 2)
		{
			$status_options = $female_option;
		}
		else
		{
			$status_options = $male_option . $female_option;
		}

		return $status_options;
	}

	/**
	 * Socialnet::_calc_bbcodeFlags
	 * Calculate bbcode flags for socialnet
	 */
	function _calc_bbcodeFlags()
	{
		global $config;

		$this->allow_bbcode = isset($config['sn_allow_bbcode']) ? $config['sn_allow_bbcode'] : $this->allow_bbcode;
		$this->allow_urls = isset($config['sn_allow_urls']) ? $config['sn_allow_urls'] : $this->allow_urls;
		$this->allow_smilies = isset($config['sn_allow_smilies']) ? $config['sn_allow_smilies'] : $this->allow_smilies;

		$this->bbCodeFlags = (($this->allow_bbcode) ? 1 : 0) + (($this->allow_smilies) ? 2 : 0) + (($this->allow_urls) ? 4 : 0);
	}

	/**
	 * Permission Exists
	 *
	 * Check if a permission (auth) setting exists
	 *
	 * @param string $auth_option The name of the permission (auth) option
	 * @param boolean $global True for checking a global permission setting, False for a local permission setting
	 *
	 * @return boolean true if it exists, false if not
	 */
	function _permission_exists($auth_option, $global = true)
	{
		global $db;

		if ($global)
		{
			$type_sql = ' AND is_global = 1';
		}
		else
		{
			$type_sql = ' AND is_local = 1';
		}

		$sql = 'SELECT auth_option_id
  					FROM ' . ACL_OPTIONS_TABLE . "
  					WHERE auth_option = '" . $db->sql_escape($auth_option) . "'" . $type_sql;
		$result = $db->sql_query($sql);

		$row = $db->sql_fetchrow($result);
		$db->sql_freeresult($result);

		if ($row)
		{
			return true;
		}

		return false;
	}

	function _version_checker($file)
	{
		global $config, $template, $user;

		$update_xml = get_remote_file($file['host'], $file['directory'], $file['filename'], $errstr, $errno);

		$update_xml = 'Destination host forbidden';
		if (!$update_xml || empty($update_xml) || $update_xml == 'Destination host forbidden')
		{
			$template->assign_var('SN_VERSION_AVAILABLE', true);
			$template->assign_var('B_COULD_NOT_GET_UPDATE', true);
			return;
		}

		if (version_compare(PHP_VERSION, '5.0.0', '<'))
		{
			$xml_data = $this->_setup_array($update_xml);
			$rows = $xml_data['modules'];
		}
		else
		{
			$xml_data = simplexml_load_string($update_xml);
			$rows = $this->_simpleXMLToArray($xml_data);
		}

		foreach ($rows['module'] as $idx => $module)
		{
			if ($idx == 0)
			{
				continue;
			}

			$code_name = $module["code"];
			$name = $module["name"];
			$msg = $module["msg"];
			$version = $module["version"];
			$download = $module["download"];
			$pay = $module['pay'] ? true : false;

			if ($code_name == 'socialNet')
			{
				$not_up_to_date = version_compare(@$version, @$config['version_socialNet']);

				$template->assign_vars(array(
					'SN_VERSION_CHECK_NOT_UP_TO_DATE'	 => ($not_up_to_date == 1) ? true : false,
					'SN_VERSION_CHECK_DOWNLOAD'			 => $download,
					'SN_VERSION_CHECK_NAME'				 => isset($user->lang[$name]) ? $user->lang[$name] : $name,
					'L_ACP_SN_VERSION_UP_TO_DATE'		 => sprintf($user->lang['ACP_SN_VERSION_UP_TO_DATE'], $name),
					'L_ACP_SN_VERSION_NOT_UP_TO_DATE'	 => sprintf($user->lang['ACP_SN_VERSION_NOT_UP_TO_DATE'], $name, $download),
					'SN_VERSION_INSTALLED'				 => $config['version_socialNet'],
					'SN_VERSION_AVAILABLE'				 => $version,
				));

				continue;
			}

			$status = isset($config[$code_name]) ? 1 : 0;
			if ($status == 1)
			{
				$status += version_compare(@$version, @$config['version_' . $code_name]);
			}

			$template->assign_block_vars('avail_module', array(
				'CODE_NAME'			 => $code_name,
				'NAME'				 => isset($user->lang[$name]) ? $user->lang[$name] : $name,
				'VERSION'			 => $version,
				'MSG'				 => ($msg != '') ? $msg : '',
				'STATUS'			 => $status,
				'INSTALLED_VERSION'	 => @$config['version_' . $code_name],
				'DOWNLOAD'			 => $download,
				'B_PAY'				 => $pay,
			));
		}

	}

	/**
	 * XML parser
	 *
	 * Rozparsování xml souboru do pole
	 * Převzato z {@link http://startrekguide.com/community/viewtopic.php?f=87&t=3584 [MODDB]MOD Version Check 1.0.2}
	 * Pro PHP < 5.0.0
	 *
	 * @param string $xml XML data
	 * @param boolean $get_attributes Vrátit atributy xml tagů
	 * @param string $priority
	 */
	function _setup_array($xml, $get_attributes = 1, $priority = 'tag')
	{
		$parser = xml_parser_create('');
		if (!$parser)
			return false;

		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($xml), $xml_values);
		xml_parser_free($parser);
		if (!$xml_values)
			return array();

		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();
		$current =& $xml_array;
		$repeated_tag_index = array();
		foreach ($xml_values as $data)
		{
			unset($attributes, $value);
			extract($data);
			$result = array();
			$attributes_data = array();
			if (isset($value))
			{
				if ($priority == 'tag')
					$result = $value;
				else
					$result['value'] = $value;
			}
			if (isset($attributes) and $get_attributes)
			{
				foreach ($attributes as $attr => $val)
				{
					if ($priority == 'tag')
						$attributes_data[$attr] = $val;
					else
						$result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
					}
			}
			if ($type == "open")
			{
				$parent[$level - 1] =& $current;
				if (!is_array($current) or (!in_array($tag, array_keys($current))))
				{
					$current[$tag] = $result;
					if ($attributes_data)
						$current[$tag . '_attr'] = $attributes_data;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					$current =& $current[$tag];
				}
				else
				{
					if (isset($current[$tag][0]))
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						$repeated_tag_index[$tag . '_' . $level]++;
					}
					else
					{
						$current[$tag] = array(
							$current[$tag],
							$result
						);
						$repeated_tag_index[$tag . '_' . $level] = 2;
						if (isset($current[$tag . '_attr']))
						{
							$current[$tag]['0_attr'] = $current[$tag . '_attr'];
							unset($current[$tag . '_attr']);
						}
					}
					$last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
					$current =& $current[$tag][$last_item_index];
				}
			}
			elseif ($type == "complete")
			{
				if (!isset($current[$tag]))
				{
					$current[$tag] = $result;
					$repeated_tag_index[$tag . '_' . $level] = 1;
					if ($priority == 'tag' and $attributes_data)
						$current[$tag . '_attr'] = $attributes_data;
				}
				else
				{
					if (isset($current[$tag][0]) and is_array($current[$tag]))
					{
						$current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
						if ($priority == 'tag' and $get_attributes and $attributes_data)
						{
							$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag . '_' . $level]++;
					}
					else
					{
						$current[$tag] = array(
							$current[$tag],
							$result
						);
						$repeated_tag_index[$tag . '_' . $level] = 1;
						if ($priority == 'tag' and $get_attributes)
						{
							if (isset($current[$tag . '_attr']))
							{
								$current[$tag]['0_attr'] = $current[$tag . '_attr'];
								unset($current[$tag . '_attr']);
							}
							if ($attributes_data)
							{
								$current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
						}
				}
			}
			elseif ($type == 'close')
			{
				$current =& $parent[$level - 1];
			}
		}
		return ($xml_array);
	}

	/**
	 * Converts a simpleXML element into an array. Preserves attributes and everything.
	 * You can choose to get your elements either flattened, or stored in a custom index that
	 * you define.
	 * For example, for a given element
	 * <field name="someName" type="someType"/>
	 * if you choose to flatten attributes, you would get:
	 * $array['field']['name'] = 'someName';
	 * $array['field']['type'] = 'someType';
	 * If you choose not to flatten, you get:
	 * $array['field']['@attributes']['name'] = 'someName';
	 * _____________________________________
	 * Repeating fields are stored in indexed arrays. so for a markup such as:
	 * <parent>
	 * <child>a</child>
	 * <child>b</child>
	 * <child>c</child>
	 * </parent>
	 * you array would be:
	 * $array['parent']['child'][0] = 'a';
	 * $array['parent']['child'][1] = 'b';
	 * ...And so on.
	 * _____________________________________
	 * @param simpleXMLElement $xml the XML to convert
	 * @param boolean $flattenValues    Choose wether to flatten values
	 *                                    or to set them under a particular index.
	 *                                    defaults to true;
	 * @param boolean $flattenAttributes Choose wether to flatten attributes
	 *                                    or to set them under a particular index.
	 *                                    Defaults to true;
	 * @param boolean $flattenChildren    Choose wether to flatten children
	 *                                    or to set them under a particular index.
	 *                                    Defaults to true;
	 * @param string $valueKey            index for values, in case $flattenValues was set to
	 *                            false. Defaults to "@value"
	 * @param string $attributesKey        index for attributes, in case $flattenAttributes was set to
	 *                            false. Defaults to "@attributes"
	 * @param string $childrenKey        index for children, in case $flattenChildren was set to
	 *                            false. Defaults to "@children"
	 * @return array the resulting array.
	 */
	function _simpleXMLToArray($xml, $flattenValues = true, $flattenAttributes = true, $flattenChildren = true, $valueKey = '@value', $attributesKey = '@attributes', $childrenKey = '@children')
	{

		$return = array();
		if (!is_a($xml, 'SimpleXMLElement'))
		{
			return $return;
		}
		$name = $xml->getName();
		$_value = trim((string) $xml);
		if (strlen($_value) == 0)
		{
			$_value = null;
		}
		;

		if ($_value !== null)
		{
			if (!$flattenValues)
			{
				$return[$valueKey] = $_value;
			}
			else
			{
				$return = $_value;
			}
		}

		$children = array();
		$first = true;
		foreach ($xml->children() as $elementName => $child)
		{
			$value = $this->_simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
			if (isset($children[$elementName]))
			{
				if ($first)
				{
					$temp = $children[$elementName];
					unset($children[$elementName]);
					$children[$elementName][] = $temp;
					$first = false;
				}
				$children[$elementName][] = $value;
			}
			else
			{
				$children[$elementName] = $value;
			}
		}
		if (count($children) > 0)
		{
			if (!$flattenChildren)
			{
				$return[$childrenKey] = $children;
			}
			else
			{
				$return = array_merge($return, $children);
			}
		}

		$attributes = array();
		foreach ($xml->attributes() as $name => $value)
		{
			$attributes[$name] = trim($value);
		}
		if (count($attributes) > 0)
		{
			if (!$flattenAttributes)
			{
				$return[$attributesKey] = $attributes;
			}
			else
			{
				$return = array_merge($return, $attributes);
			}
		}

		return $return;
	}

}

function snFunctions_absolutePath($matches)
{
	global $phpbb_root_path, $config;

	// WINDOWS SERVER FIX
	$_phpbb_root_path = str_replace('\\', '/', $phpbb_root_path);
	$_script_path = str_replace('//', '/', str_replace('\\', '/', $config['script_path']) . '/');
	$path = preg_replace('#^' . preg_quote($_phpbb_root_path) . '#si', $_script_path, $matches[3]);

	return "{$matches[1]}" . (!empty($matches[1]) ? '=' : '') . "{$matches[2]}{$path}{$matches[4]}";
}

if (!function_exists('json_encode'))
{
	/**
	 * json_encode function for PHP lower than 5.2.0
	 * boukeversteegh at gmail dot com 10-Nov-2010 02:02
	 * For users of php 5.1.6 or lower, a native json_encode function. This version handles objects, and makes proper distinction between [lists] and {associative arrays}, mixed arrays work as well. It can handle newlines and quotes in both keys and data.
	 *
	 * This function will convert non-ascii symbols to "\uXXXX" format as does json_encode.
	 *
	 * Besides that, it outputs exactly the same string as json_encode. Including UTF-8 encoded 2-, 3- and 4-byte characters. It is a bit faster than PEAR/JSON::encode, but still slow compared to php 5.3's json_encode. It encodes any variable type exactly as the original.
	 *
	 * Relative speeds:
	 * PHP json_encode: 1x
	 * json_encode: 31x
	 * PEAR/JSON: 46x
	 *
	 * NOTE: I assume the input will be valid UTF-8. I don't know what happens if your data contains illegal Unicode sequences. I tried to make the code fast and compact.
	 *
	 * @author boukeversteegh
	 */
	function json_encode($data)
	{
		if (is_array($data) || is_object($data))
		{
			$islist = is_array($data) && (empty($data) || array_keys($data) === range(0, count($data) - 1));

			if ($islist)
			{
				$json = '[' . implode(',', array_map('json_encode', $data)) . ']';
			}
			else
			{
				$items = Array();
				foreach ($data as $key => $value)
				{
					$items[] = json_encode("$key") . ':' . json_encode($value);
				}
				$json = '{' . implode(',', $items) . '}';
			}
		}
		elseif (is_string($data))
		{
			# Escape non-printable or Non-ASCII characters.
			# I also put the \\ character first, as suggested in comments on the 'addclashes' page.
			$string = '"' . addcslashes($data, "\\\"\n\r\t/" . chr(8) . chr(12)) . '"';
			$json = '';
			$len = strlen($string);
			# Convert UTF-8 to Hexadecimal Codepoints.
			for ($i = 0; $i < $len; $i++)
			{

				$char = $string[$i];
				$c1 = ord($char);

				# Single byte;
				if ($c1 < 128)
				{
					$json .= ($c1 > 31) ? $char : sprintf("\\u%04x", $c1);
					continue;
				}

				# Double byte
				$c2 = ord($string[++$i]);
				if (($c1 & 32) === 0)
				{
					$json .= sprintf("\\u%04x", ($c1 - 192) * 64 + $c2 - 128);
					continue;
				}

				# Triple
				$c3 = ord($string[++$i]);
				if (($c1 & 16) === 0)
				{
					$json .= sprintf("\\u%04x", (($c1 - 224) << 12) + (($c2 - 128) << 6) + ($c3 - 128));
					continue;
				}

				# Quadruple
				$c4 = ord($string[++$i]);
				if (($c1 & 8) === 0)
				{
					$u = (($c1 & 15) << 2) + (($c2 >> 4) & 3) - 1;

					$w1 = (54 << 10) + ($u << 6) + (($c2 & 15) << 2) + (($c3 >> 4) & 3);
					$w2 = (55 << 10) + (($c3 & 15) << 6) + ($c4 - 128);
					$json .= sprintf("\\u%04x\\u%04x", $w1, $w2);
				}
			}
		}
		else
		{
			# int, floats, bools, null
			$json = strtolower(var_export($data, true));
		}
		return $json;
	}
}

if (!function_exists('json_decode'))
{
	/**
	 * json_encode function for PHP lower than 5.2.0
	 * @author walidator.info 2009
	 * */
	function json_decode($json)
	{
		//
		$comment = false;
		$out = '$x=';

		for ($i = 0; $i < strlen($json); $i++)
		{
			if (!$comment)
			{
				if ($json[$i] == '{')
					$out .= ' array(';
				else if ($json[$i] == '}')
					$out .= ')';
				else if ($json[$i] == ':')
					$out .= '=>';
				else
					$out .= $json[$i];
			}
			else
				$out .= $json[$i];
			if ($json[$i] == '"')
				$comment = !$comment;
		}
		eval($out . ';');
		return $x;
	}
}

if (!function_exists('array_walk_recursive'))
{
	/**
	 * array_walk_recursive for PHP lower than 5.0.0
	 * @author omera13a
	 * @since 22.12.2005
	 */
	function array_walk_recursive(&$input, $funcname, $userdata = "")
	{
		if (!is_callable($funcname))
		{
			return false;
		}

		if (!is_array($input))
		{
			return false;
		}

		foreach ($input AS $key => $value)
		{
			if (is_array($input[$key]))
			{
				array_walk_recursive($input[$key], $funcname, $userdata);
			}
			else
			{
				$saved_value = $value;
				if (!empty($userdata))
				{
					$funcname($value, $key, $userdata);
				}
				else
				{
					$funcname($value, $key);
				}

				if ($value != $saved_value)
				{
					$input[$key] = $value;
				}
			}
		}
		return true;
	}
}

?>