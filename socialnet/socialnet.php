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
if (!defined('IN_PHPBB') || !defined('SOCIALNET_INSTALLED'))
{
	exit;
}

/**
 * Main class of Social Network for phpBB
 *
 * @package Socialnet
 */
class socialnet extends snFunctions
{

	/**
	 * @var array
	 */
	var $periods = array("SECOND", "MINUTE", "HOUR", "DAY", "WEEK", "MONTH", "YEAR", "DECADE");
	/**
	 * @var array
	 */
	var $lengths = array("60", "60", "24", "7", "4.35", "12", "10");
	/**
	 * @var array $config pointer to $config
	 */
	var $config = array();
	var $socialnet_root_path = '';
	var $modules = array();
	var $existing = array();
	var $modules_obj = array();
	var $bbCodeFlags = null;
	var $allow_bbcode = 1;
	var $allow_urls = 1;
	var $allow_smilies = 1;
	var $script_name = '';

	var $friendsCacheName = '_sn_Friends_';
	var $friendsCacheNameMutual = '_sn_MutualFriends_';
	var $groupsCacheName = '_sn_Groups_';

	var $friends = array('user_id' => array(), 'username' => array(), 'friends' => array(), 'colourNames' => array());
	var $groups = array();

	var $memory_usage = array();

	/**
	 * Construction function
	 * Prepare active modules
	 * @return void
	 */
	function socialnet()
	{
		global $user, $auth, $config, $db, $template, $phpbb_root_path, $socialnet_root_path, $phpEx;

		$this->socialnet_root_path = $socialnet_root_path;
		$this->script_name = str_replace('.' . $phpEx, '', $user->page['page_name']);
		$this->config =& $config;

		// Extend Config data;
		/**
		 $sql = "SELECT config_value FROM " . SN_CONFIG_TABLE . " WHERE config_name = 'sn_global_enable'";
		 $result = $db->sql_query($sql);
		 $row = $db->sql_fetchrow($result);
		 $config['sn_global_enable'] = $row['config_value'];
		 */
		$sql = "SELECT config_name, config_value FROM " . SN_CONFIG_TABLE . " WHERE config_name <> 'sn_global_enable'";

		$result = $db->sql_query($sql);
		$rowset = $db->sql_fetchrowset($result);
		$db->sql_freeresult($result);
		$block_settings = $enable_modules = $confirmBox_settings = array();

		foreach ($rowset as $idx => $row)
		{
			$config[$row['config_name']] = $row['config_value'];
			if (preg_match('/^module_(.+)$/si', $row['config_name'], $module_match))
			{
				$moduleName = 'SN_' . strtoupper($row['config_name']) . '_ENABLED';
				$enable_modules[$moduleName] = ($row['config_value'] == 1 ? true : false) && ($config['sn_global_enable'] == 1);

				/* DOCASNE ODEBRANO - OPRAVNENI POUZIT MODUL */
				$permission_allow = $user->data['is_registered'] == 1 || $row['config_name'] == 'module_mainpage';

				if ($this->_permission_exists('u_sn_' . $module_match[1]))
				{
					$permission_allow = $auth->acl_get('u_sn_' . $module_match[1]);
				}

				/*
				 if (isset($user->data['user_sn_module_' . $module_match[1]]) && $user->data['user_sn_module_' . $module_match[1]] == 0)
				 {
				 $permission_allow = 0;
				 }
				 */
				$this->existing[] = $module_match[1];

				if (!defined('ADMIN_START') && $enable_modules[$moduleName] == 1 && $permission_allow == 1)
				{
					$module_filename = $socialnet_root_path . $module_match[1] . '.' . $phpEx;
					if (file_exists($module_filename))
					{
						if (!class_exists('socialnet_' . $module_match[1]))
						{
							include($module_filename);
						}
						if (class_exists('socialnet_' . $module_match[1]))
						{
							$this->modules[] = $module_match[1];
						}
						else
						{
							$enable_modules[$moduleName] = false;
						}
					}
					else
					{
						$enable_modules[$moduleName] = false;
					}
				}
				else
				{
					$enable_modules[$moduleName] = false;
				}

			}

			// SN BLOCKS

			if (preg_match('/^sn_block_(.*)$/si', $row['config_name'], $block_match))
			{
				$block_settings['B_' . strtoupper($row['config_name']) . '_ENABLED'] = $row['config_value'];
			}

			// CONFIRM BOX
			if (preg_match('/^sn_cb(.+)$/', $row['config_name']))
			{
				$confirmBox_settings['S_' . strtoupper($row['config_name'])] = $row['config_value'];
			}
		}
		$this->load_friends();
		$this->load_groups();

		$board_url = generate_board_url() . '/';
		$socialnet_web_path = ((defined('PHPBB_USE_BOARD_URL_PATH') && PHPBB_USE_BOARD_URL_PATH) ? $board_url : $phpbb_root_path) . 'socialnet/';
		
		$template->assign_vars(array_merge(array(
			'B_SOCIALNET_ENABLED'		 => true,
			'SOCIALNET_ROOT_PATH'		 => $socialnet_root_path,
			'SOCIALNET_JS_PATH'			 => $socialnet_web_path . 'js/',
			'T_SOCIALNET_JS_PATH'		 => $socialnet_web_path . 'js',
			'T_SOCIALNET_STYLE_PATH'	 => $socialnet_web_path . 'styles',
			'T_SOCIALNET_IMAGES_PATH'	 => $socialnet_web_path . 'styles/images',
			'T_SOCIALNET_CSS_PATH'		 => $socialnet_web_path . 'styles/css',
			'S_SN_IM_USER_SOUND'		 => $user->data['user_im_sound'],
			'COOKIE_NAME'				 => $config['cookie_name'],
			'COOKIE_PATH'				 => $config['cookie_path'],
			'COOKIE_DOMAIN'				 => $config['cookie_domain'],
			'COOKIE_SECURE'				 => $config['cookie_secure'],
			'S_SN_IM_USER_SOUNDNAME'	 => $user->data['user_im_soundname'],
			'U_SN_MAINPAGE'				 => append_sid("mainpage.$phpEx" ),
			'S_ON_MAINPAGE'				 => $this->script_name == 'mainpage',
			'I_SN_BLOCK_ONLINE_USERS_CHECK_TIME' => $this->config['block_uo_check_every'] * 1000,
			'B_AJAX_LOAD_ALLOW'			 => $config['board_disable'] == 0 ? 'true' : 'false',
			'I_POST_MIN_CHARS'			 => $config['min_post_chars'],
			'U_SN_MY_PROFILE'			 => append_sid("{$phpbb_root_path}memberlist.{$phpEx}", "mode=viewprofile&amp;u={$user->data['user_id']}")
		), $enable_modules, $confirmBox_settings, $block_settings));

		$this->_calc_bbcodeFlags();
	}

	/**
	 * Socialnet
	 */
	function load_friends($user_id = 0, $type = 'user_id')
	{
		global $db, $user, $cache;

		$user_id = ($user_id == 0) ? $user->data['user_id'] : $user_id;

		$cache_my_friends = $this->friendsCacheName . $user_id;
		$friends = $cache->get($cache_my_friends);

		if (!isset($friends) || empty($friends) || !isset($friends['user_id']) || empty($friends['user_id']) || in_array(ANONYMOUS, $friends['user_id']))
		{
			$sql = "SELECT u.user_id, u.username
				    FROM " . ZEBRA_TABLE . " AS z, " . USERS_TABLE . " AS u
    				WHERE z.user_id = {$user_id} AND z.zebra_id = u.user_id AND z.friend = 1";

			$rs = $db->sql_query($sql);
			$rowset = $db->sql_fetchrowset($rs);
			$db->sql_freeresult($rs);

			$friends = array(
				'user_id'		 => array(),
				'usernames'		 => array(),
				'friends'		 => array(),
				'colourNames'	 => array(),
			);

			for ($i = 0; isset($rowset[$i]); $i++)
			{
				$friend = $rowset[$i];
				$friends['user_id'][] = $friend['user_id'];
				$friends['usernames'][] = $friend['username'];
				$friends['friends'][$friend['user_id']] = $friend['username'];
			}

			// Cache 1 hour
			$cache->put($cache_my_friends, $friends, 3600);
		}

		if ($user_id == $user->data['user_id'])
		{
			$this->friends = $friends;
		}

		return $this->friends[$type];
	}

	function purge_friends($user_id = 0)
	{
		global $user, $cache;

		$user_id = $user_id == 0 ? $user->data['user_id'] : $user_id;
		$cache->purge($this->friendsCacheName . $user_id);
		$cache->purge($this->friendsCacheNameMutual . $user_id);
	}

	function reload_friends($user_id = 0)
	{
		global $user;
		$user_id = $user_id == 0 ? $user->data['user_id'] : $user_id;
		$this->purge_friends($user_id);
		$this->load_friends($user_id);
	}

	function load_groups($user_id = 0)
	{
		global $db, $user, $cache;

		$user_id = ($user_id == 0) ? $user->data['user_id'] : $user_id;

		$cache_my_groups_name = $this->groupsCacheName . $user_id;
		$groups = $cache->get($cache_my_groups_name);

		if (empty($groups))
		{
			$sql = "SELECT DISTINCT g.fms_gid, g.fms_name, ug.user_id
				FROM " . SN_FMS_GROUPS_TABLE . " AS g LEFT OUTER JOIN " . SN_FMS_USERS_GROUP_TABLE . " AS ug ON g.fms_gid = ug.fms_gid
				WHERE g.user_id = {$user_id}
				ORDER BY g.fms_name";
			$result = $db->sql_query($sql);
			$rowset = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);

			$groups = array();
			for ($i = 0; isset($rowset[$i]) && $gu = $rowset[$i]; $i++)
			{
				if (!isset($groups[$gu['fms_gid']]))
				{
					$groups[$gu['fms_gid']] = array('name' => $gu['fms_name'], 'users' => array());
				}

				if ($gu['user_id'] != 0)
				{
					$groups[$gu['fms_gid']]['users'][] = $gu['user_id'];
				}
			}

			$cache->put($cache_my_groups_name, $groups);
		}

		$this->groups = $groups;

	}

	function purge_groups($user_id = 0)
	{
		global $cache, $user;

		$user_id = $user_id == 0 ? $user->data['user_id'] : $user_id;
		$cache->purge($this->groupsCacheName . $user_id);
	}

	function reload_groups($user_id = 0)
	{
		global $user;
		$user_id = $user_id == 0 ? $user->data['user_id'] : $user_id;
		$this->purge_groups($user_id);
		$this->load_groups($user_id);
	}

	/**
	 * Run start script for any active module of Social Network
	 * Mainpage module must be loaded as latest module if enabled
	 * @ example class: socialnet_im function socialnet_im
	 * @return void
	 */
	function start_modules()
	{
		if (sizeOf($this->modules ) == 0)
		{
			return;
		}

		if (in_array('mainpage', $this->modules))
		{
			$this->modules = array_diff($this->modules, array('mainpage'));
			$this->modules[] = 'mainpage';
		}

		foreach ($this->modules as $idx => $module)
		{

			$module_class = 'socialnet_' . $module;
			$this->modules_obj[$module] = new $module_class($this);
		}

	}

	/**
	 * Get user avatar rezised
	 *
	 * @param string $avatar Users assigned avatar name
	 * @param integer $avatar_type Type of avatar
	 * @param string $avatar_width Width of users avatar
	 * @param string $avatar_height Height of users avatar
	 * @param integer $max_height Maximální výška avataru
	 * @param boolean $stretch Roztazeni avataru na maximalni velikost?
	 * @param string $alt Optional language string for alt tag within image, can be a language key or text
	 * @param bool $ignore_config Ignores the config-setting, to be still able to view the avatar in the UCP
	 *
	 * @return string Avatar image
	 */
	function get_user_avatar_resized($avatar = '', $avatar_type = 0, $avatar_width = 0, $avatar_height = 0, $max_height = 0, $stretch = true, $alt = 'USER_AVATAR', $ignore_config = false)
	{
		global $config, $socialnet_root_path, $phpbb_root_path, $user, $snFunctions_avatars, $template;

		if ( $max_height == 0)
		{
			$max_height = $avatar_height;
		}
		
		if (in_array($avatar . $max_height, array_keys($snFunctions_avatars)))
		{
			return $snFunctions_avatars[$avatar . $max_height];
		}
		$add_style = '';
		$add_class = 'snUserAvatar';
		$padding = 0;

		if (empty($avatar) || !$avatar_type || (!$config['allow_avatar'] && !$ignore_config) || !method_exists($this, 'get_user_avatar'))
		{
			if ($max_height < 25)
			{
				$img_sized = 'no_avatar';
				$size = 22;
			}
			else if (abs(150 - $max_height) < abs(50 - $max_height))
			{
				$img_sized = 'no_avatar_150';
				$size = 150;
			}
			else if (abs(30 - $max_height) < abs(50 - $max_height))
			{
				$img_sized = 'no_avatar_30';
				$size = 30;
			}
			else
			{
				$img_sized = 'no_avatar_50';
				$size = 50;
			}

			if ($stretch)
			{
				$avatar_height = $avatar_width = $max_height;
			}
			else
			{
				$avatar_height = $avatar_width = $size;

				$padding = ceil(($max_height - $size) / 2);
				$add_class .= '-1';
				$add_style = 'padding:' . $padding . 'px 0;';
			}
			$user_avatar = '<img src="' . $phpbb_root_path . 'styles/' . $user->theme['imageset_path'] . '/imageset/socialnet/' . $img_sized . '.png" height="' . $avatar_height . '" width="' . $avatar_height . '" alt="' . ((!empty($user->lang[$alt])) ? $user->lang[$alt] : $alt) . '" />';
		}
		else if ($avatar_height != 0)
		{
			$origin_width = $avatar_width;
			$origin_height = $avatar_height;

			if ($stretch)
			{
				if ($avatar_width > $avatar_height)
				{
					$avatar_height = (int) ceil($max_height * ($avatar_height / $avatar_width));
					$avatar_width = $max_height;
				}
				else
				{
					$avatar_width = (int) ceil($avatar_width * ($max_height / $avatar_height));
					$avatar_height = $max_height;

				}
			}
			else
			{
				if ($avatar_height > $max_height)
				{
					$avatar_width = $avatar_width * $max_height / $avatar_height;
					$avatar_height = $max_height;
				}
				if ($avatar_width > $max_height)
				{
					$avatar_height = $avatar_height * $max_height / $avatar_width;
					$avatar_width = $max_height;
				}

				$padding = ceil(($max_height - $avatar_height) / 2);
				$add_class .= '-1';
				$add_style = 'padding:' . $padding . 'px 0;';
			}

			$user_avatar = $this->get_user_avatar($avatar, $avatar_type, $avatar_width, $avatar_height, $alt, $ignore_config);

			if (!$stretch)
			{
				$user_avatar = str_replace(' />', ' style="vertical-align:middle" align="middle" />', $user_avatar);
			}
		}

		$avatar_width = $max_height;
		$avatar_height = $max_height;

		if( $padding!= 0 && !$stretch)
		{
			$avatar_height-=2*$padding;
		}
		
		$snFunctions_avatars[$avatar . $max_height] = '<span class="'.$add_class.'" style="width:' . $avatar_width . 'px;height:'.$avatar_height.'px;'.$add_style.'">' . $user_avatar . '</span>';
		return $snFunctions_avatars[$avatar . $max_height];
	}

	/**
	 * Get user avatar ORIGINAL FROM phpBB
	 *
	 * @param string $avatar Users assigned avatar name
	 * @param int $avatar_type Type of avatar
	 * @param string $avatar_width Width of users avatar
	 * @param string $avatar_height Height of users avatar
	 * @param string $alt Optional language string for alt tag within image, can be a language key or text
	 * @param bool $ignore_config Ignores the config-setting, to be still able to view the avatar in the UCP
	 *
	 * @return string Avatar image
	 */
	function get_user_avatar($avatar, $avatar_type, $avatar_width, $avatar_height, $alt, $ignore_config = false)
	{
		global $user, $config, $phpbb_root_path, $phpEx;

		if (empty($avatar) || !$avatar_type || (!$config['allow_avatar'] && !$ignore_config))
		{
			return '';
		}

		$avatar_img = '';

		switch ($avatar_type)
		{
			case AVATAR_UPLOAD:
				if (!$config['allow_avatar_upload'] && !$ignore_config)
				{
					return '';
				}
				$avatar_img = $phpbb_root_path . "download/file.$phpEx?avatar=";
				break;

			case AVATAR_GALLERY:
				if (!$config['allow_avatar_local'] && !$ignore_config)
				{
					return '';
				}
				$avatar_img = $phpbb_root_path . $config['avatar_gallery_path'] . '/';
				break;

			case AVATAR_REMOTE:
				if (!$config['allow_avatar_remote'] && !$ignore_config)
				{
					return '';
				}
				break;
		}

		$avatar_img .= $avatar;

		return '<img src="' . (str_replace(' ', '%20', $avatar_img)) . '" width="' . $avatar_width . '" height="' . $avatar_height . '" alt="' . ((!empty($user->lang[$alt])) ? $user->lang[$alt] : $alt) . '" />';
	}

	function get_friend($mode, $user_id, $cfg_module, $cache_friends = true)
	{
		global $cache, $user, $db;
		
		$sql = "SELECT u.username, u.username_clean, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
				FROM " . USERS_TABLE . " AS u
				WHERE u.user_id = {$user_id}";
		$rs = $db->sql_query($sql);
		$row = $db->sql_fetchrow($rs);
		$db->sql_freeresult($rs);
		$this->friends['friends'][$user_id] = $row['username'];
		$this->get_username_string($cfg_module, $mode, $user_id, $row['username'], $row['user_colour'], $cache_friends);

	}

	/**
	 * socialnet::get_username_string
	 * Function generate user coloured nick dependent on module config
	 */
	function get_username_string($cfg_module, $mode, $user_id, $username, $username_colour = '', $guest_username = false, $custom_profile_url = false, $cache_friends = true)
	{
		global $cache, $user, $phpbb_root_path;
		
		
		if (isset($this->friends['colourNames'][$user_id][$mode]) && $username != $user->lang['GUEST'] && strpos( $this->friends['colourNames'][$user_id][$mode], $user->lang['GUEST']) === false)
		{
			return $this->friends['colourNames'][$user_id][$mode];
		}

		if (!$cfg_module)
		{
			$username_colour = '';
		}

		$modeFull = $mode;
		
		if (preg_match('/^full.*?$/si', $mode, $match))
		{
			$mode = 'full';
		}

		$this->friends['colourNames'][$user_id][$modeFull] = $this->absolutePath(get_username_string($mode, $user_id, $username, $username_colour, $guest_username, $custom_profile_url));
		
		if ( $modeFull == 'profile')
		{
			$this->friends['colourNames'][$user_id][$modeFull] = snFunctions_absolutePath(array('','',$this->friends['colourNames'][$user_id][$modeFull],'', ''));
		}
		
		if ($cache_friends)
		{
			$cache->put($this->friendsCacheName . $user->data['user_id'], $this->friends, 3600);
		}

		return $this->friends['colourNames'][$user_id][$modeFull];
	}

	function absolutePath($content)
	{
		return preg_replace_callback('/(src|href|action)=([\'"])([^\'"]+)([\'"])/si', 'snFunctions_absolutePath', $content);
	}
/*
	function absolutePathLink($link)
	{
		global $phpbb_root_path, $config;

		// WINDOWS SERVER FIX
		$_phpbb_root_path = str_replace('\\', '/', $phpbb_root_path);
		$_script_path = str_replace('//', '/', str_replace('\\', '/', $config['script_path']) . '/');
		$link = preg_replace('#^' . preg_quote($_phpbb_root_path) . '#si', $_script_path, $link);
		print $link;
		die(__FILE__ . ' ' . __LINE__);
		return $link;

	}
*/
	/**
	 * snFunctions::page_header
	 *
	 * Funkce vypisuje casti stranky bez toho aniz by vypsala na standardni vystup
	 * Zacatek stranky
	 *
	 * @access public
	 * @return void
	 */
	function page_header($page_title = '', $display_online_list = true, $item_id = 0, $item = 'forum')
	{
		if ($page_title != '')
		{
			page_header($page_title, $display_online_list, $item_id, $item);
		}
	}

	/**
	 * snFunctions::page_footer
	 *
	 * Funkce vypisuje casti stranky bez toho aniz by vypsala na standardni vystup
	 * Konec stranky
	 *
	 * @access public
	 * @param string $block Zakladni blok sablony
	 * @param boolean $print Ma se vypsat primo na stranku nebo vratit, v pripade true ukonci prubeh skriptu
	 * @return string Stranka vygenerovana ze sablon $block
	 */
	function page_footer($block = 'body', $print = false)
	{
		global $template;
		$content = $template->assign_display($block);
		if (!$print)
		{
			return $this->absolutePath(trim($content));
		}
		else
		{
			print $this->absolutePath(trim($content));
			die();
		}
	}

	/**
	 * snFunctions::get_page
	 *
	 * Funkce vypisuje stranku bez toho aniz by vypsala na standardni vystup
	 * Konec stranky
	 *
	 * @access public
	 * @param string $block Zakladni blok sablony
	 * @param boolean $print Ma se vypsat primo na stranku nebo vratit, v pripade true ukonci prubeh skriptu
	 * @return string Stranka vygenerovana ze sablon $block
	 */
	function get_page($block = 'body', $print = false)
	{
		$this->page_header();
		return $this->page_footer($block, $print);
	}

	/**
	 * snFunctions::time_ago
	 *
	 * @access public
	 * @link http://www.w3cgallery.com/w3c-blog/php-mysql-ajax-hacks-trick/how-to-create-time-difference-like-1-day-ago-in-php
	 * @since 0.4.5
	 * @param  integer $from UNIX timestamp
	 * @param integer $to UNIX timestamp
	 * @return string Time string
	 */
	function time_ago($from, $to = 0)
	{
		global $user;

		if ($to == 0)
		{
			$to = time();
		}

		if ($to > $from)
		{
			$difference = $to - $from;
			$tense = 'SN_TIME_AGO';
		}
		else
		{
			$difference = $from - $to;
			$tense = 'SN_TIME_FROM_NOW';
		}

		$lengths = $this->lengths;

		for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; $j++)
		{
			$difference /= $lengths[$j];
		}

		$difference = round($difference);

		$period = $this->periods[$j];

		if ($difference != 1)
		{
			$period .= "S";
		}

		return sprintf($user->lang[$tense], $difference, $user->lang['SN_TIME_PERIODS'][$period]);
	}

	/**
	 * snFunctions::record_entry
	 *
	 * Funkcia vlozi zaznam o cinnosti na SN
	 *
	 * @access public
	 * @param integer $user_id Identifikátor uživatele
	 * @param integer $target ...
	 * @param integer $type ...
	 * @return void
	 */
	function record_entry($user_id, $target, $type, $additionals = array())
	{
		global $db;

		$now = time();

		$sql_arr = array(
			'user_id'			 => $user_id,
			'entry_target'		 => $target,
			'entry_type'		 => $type,
			'entry_time'		 => $now,
			'entry_additionals'	 => serialize($additionals),
		);

		$sql = "INSERT INTO " . SN_ENTRIES_TABLE . $db->sql_build_array('INSERT', $sql_arr);
		$db->sql_query($sql);
	}

	/**
	 * snFunctions::record_entry
	 *
	 * Funkcia zmaze zaznam o cinnosti na SN
	 *
	 * @access public
	 * @param integer $target
	 * @param integer $type
	 * @return void
	 */
	function delete_entry($target, $type)
	{
		global $db;

		$sql = "DELETE FROM " . SN_ENTRIES_TABLE . "
	WHERE entry_target = " . $target . "
	AND entry_type = " . $type;
		$db->sql_query($sql);
	}

	function is_enabled($module_name)
	{
		return in_array($module_name, $this->existing);
	}

	/**
	 * hook_template
	 *
	 * Pomocí této funkce zavoláme pro jednotlivý modul příslušný skript pro změnu šablon<br>
	 * @return void
	 */
	function hook_template()
	{
		global $template, $user, $config, $phpbb_root_path, $phpEx;

		$copy_string = 'Powered by <a href="http://socialnetwork.phpbb3hacks.com/" title="phpBB Social Network">phpBB Social Network</a> ' . $config['version_socialNet'] . ' Kamahl &amp; Culprit &copy; 2010, 2011';
		if (!isset($template->_tpldata['.'][0]['TRANSLATION_INFO']))
		{
			$template->_tpldata['.'][0]['TRANSLATION_INFO'] = '';
		}

		if (isset($template->_tpldata['.'][0]['TRANSLATION_INFO']) && strpos($template->_tpldata['.'][0]['TRANSLATION_INFO'], $copy_string) === false)
		{
			$translation_info =& $template->_tpldata['.'][0]['TRANSLATION_INFO'];
			$translation_info = $copy_string . ((!empty($translation_info)) ? '<br />' . $translation_info : '');
		}

		if (defined('IN_ADMIN'))
		{
			global $module;
			if ($module->p_class == 'acp' && $module->p_name == 'update' && $module->p_mode == 'version_check')
			{
				$template->files['body'] = preg_replace('/(acp_)([^.]*\.html)/si', '\1socialnet_\2', $template->files['body']);
				$template->filename['body'] = preg_replace('/(acp_)([^.]*\.html)/si', '\1socialnet_\2', $template->filename['body']);
				$this->_version_checker(array('host' => 'socialnetwork.phpbb3hacks.com', 'directory' => '', 'filename' => 'sn_modules.xml'));
			}

			if ($module->p_class == 'acp' && $module->p_name == 'main' && $module->p_mode == 'main')
			{
				/*print '<pre>';
				 unset($template->_tpldata);
				 unset($template->_rootref);
				 print_r( $template);
				 die( __FILE__ . ' ' . __LINE__);*/
			}
			return;
		}

		foreach ($this->existing as $idx => $module)
		{
			$s = "socialnet_{$module}";
			if (!class_exists($s))
			{
				include("{$phpbb_root_path}socialnet/{$module}.{$phpEx}");
			}

			if (method_exists($s, 'hook_template_every'))
			{
				eval($s . '::hook_template_every();');
			}
		}

		if (sizeOf($this->modules ) == 0)
		{
			return;
		}

		foreach ($this->modules as $idx => $module)
		{
			if (isset($this->modules_obj[$module]) && is_object($this->modules_obj[$module]) && method_exists($this->modules_obj[$module], 'hook_template'))
			{
				$this->modules_obj[$module]->hook_template();
			}
		}

	}

}

?>