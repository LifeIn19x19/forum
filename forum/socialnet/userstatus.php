<?php
/**
 *
 * @package phpBB3 Social Network
 * @version 0.6.1
 * @copyright (c) 2011 Kamahl & Culprit http://socialnetwork.phpbb3hacks.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

if (!defined('SOCIALNET_INSTALLED'))
{
	/**
	 * @ignore
	 */
	define('IN_PHPBB', true);
	/**
	 * @ignore
	 */
	define('SN_LOADER', 'userstatus');
	define('SN_USERSTATUS', true);
	$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	/**
	 * @ignore
	 */
	include_once($phpbb_root_path . 'common.' . $phpEx);
	/**
	 * @ignore
	 */
	include_once($phpbb_root_path . 'includes/functions_display.' . $phpEx);

	// Start session management
	$user->session_begin(false);
	$auth->acl($user->data);
	$user->setup();

}

if (!class_exists('socialnet_userstatus'))
{
	/**
	 * Socialnet UserStatus
	 *
	 * @package UserStatus
	 * @access public
	 */

	class socialnet_userstatus
	{

		var $p_master = null;

		/**
		 * @var string $script_name Název stránky na které se nacházíme v rámci celého phpBB
		 */
		var $script_name = '';
		/**
		 * @var array $on_header seznam stránek na kterých má vý zobrazen status hned pod hlavičkou.<br>
		 * Pole může obsahovat další pole, které specifikuje část stránky definované pomocí GET parametru 'mode'
		 */
		//var $on_header = array('faq', 'index', array('memberlist', ''), 'posting',	'search', 'viewforum', 'viewonline', 'viewtopic', 'ucp');
		var $on_header = array();

		/**
		 * Konstruktor
		 *
		 * V rámci konstruktoru jsou načítány všechny potřebné věci pro danou stránku fóra.<br>
		 * Tato funkce je volána na každé stránce pomocí modulu Socialnet, který je vytvořen v hook pro rozšíření uživatelových informací.
		 */

		function socialnet_userstatus(&$p_master)
		{
			global $db, $template, $user, $config, $auth, $phpEx, $phpbb_root_path, $phpEx;

			$this->p_master =& $p_master;

			$this->script_name = $this->p_master->script_name;
			$mode = request_var('mode', '', true);

			$template_assign_vars = array(
				'B_SN_US_ON_HEADER'					 => in_array(array($this->script_name, $mode), $this->on_header) || in_array($this->script_name, $this->on_header) || in_array('all', $this->on_header),
				'B_LOAD_FIRST_USERSTATUS_COMMENTS'	 => isset($config['userstatus_comments_load_last']) ? $config['userstatus_comments_load_last'] : 1,
			);

			if (!isset($template->_tpldata['.'][0]['T_IMAGESET_PATH']))
			{
				$t_imaset_path = "{$phpbb_root_path}styles/" . $user->theme['imageset_path'] . '/imageset';
				$_phpbb_root_path = str_replace('\\', '/', $phpbb_root_path);
				$_script_path = str_replace('//', '/', str_replace('\\', '/', $config['script_path']) . '/');
				$t_imaset_path = preg_replace('#^' . preg_quote($_phpbb_root_path) . '#si', $_script_path, $t_imaset_path);
				$template_assign_vars = array_merge($template_assign_vars, array(
					'T_IMAGESET_PATH'	 => $t_imaset_path,
				));
			}

			if (isset($template->_tpldata['.'][0]['T_IMAGESET_PATH']))
			{
				$template->assign_var('T_IMAGESET_PATH', $t_imageset_path);
			}

			switch ($this->script_name)
			{
				case 'memberlist':
				case 'profile':
					$user_id = $this->_wall_id();

					if ($user_id != ANONYMOUS)
					{
						$status_id = request_var('status_id', 0);

						$my_friends = $this->p_master->friends['user_id'];
						$my_friends[] = $user->data['user_id'];

						if ($status_id == 0)
						{
							$more_statuses = $this->_get_statuses($user_id);

							$template_assign_vars = array_merge($template_assign_vars, array(
								'SN_MODULE_USERSTATUS_VIEWPROFILE_ENABLE'	 => true,
								'SN_MODULE_USERSTATUS_CAN_POST_STATUS'		 => in_array($user_id, $my_friends) ? '1' : '0',
								'SN_US_DISPLAY_LOAD_MORE_STATUS'			 => $more_statuses,
								'SN_US_USER_ID'								 => $user_id,
							));

							if (!isset($template->_tpldata['.'][0]['USERNAME']))
							{
								$sql = 'SELECT username FROM ' . USERS_TABLE . ' WHERE user_id = ' . $user_id;
								$rs = $db->sql_query($sql);
								$username = $db->sql_fetchfield('username');
								$db->sql_freeresult($rs);
								$template->assign_var('USERNAME', $username);
							}
						}
						else
						{
							$this->_get_statuses($user_id, $status_id, 1, 15, true);
							$more_statuses = false;

							$template_assign_vars = array_merge($template_assign_vars, array(
								'SN_MODULE_USERSTATUS_VIEWPROFILE_ENABLE'	 => true,
								'SN_MODULE_USERSTATUS_CAN_POST_STATUS'		 => in_array($user_id, $my_friends),
								'SN_US_DISPLAY_LOAD_MORE_STATUS'			 => false,
								'SN_US_USER_ID'								 => $user_id,
							));
						}
					}

					break;
			}

			$template->assign_vars($template_assign_vars);
		}

		/**
		 * Zakladní funkce pro AJAX
		 *
		 *  Tato funkce je volána v případě, že je volána pomocí AJAX přímo ze stránky<br>
		 *  Funkce by měla vracet data v JSON, nebo přímo HTML.
		 */
		function load($mode = '')
		{
			global $user, $db, $template, $phpbb_root_path;

			switch ($mode)
			{
				case 'status_share':
					$this->_status_share();
					break;
				case 'status_share_wall':
					$this->_status_share(true);
					break;
				case 'status_more':
					$this->_status_more();
					break;
				case 'status_delete':
					$this->_status_delete();
					break;
				case 'comment_share':
					$this->_comment_share();
					break;
				case 'comment_delete':
					$this->_comment_delete();
					break;
				case 'comment_more':
					$this->_comment_more();
					break;
				case 'get_status':
					$this->_get_status();
					break;
			}
		}

		/**
		 * Zápis noveho statusu
		 *
		 * Funkce zapisuje nový status uživatele
		 * @access private
		 */
		function _status_share($on_the_wall = false)
		{
			global $db, $user, $template, $phpbb_root_path, $config;

			$new_status = request_var('status', '', true); // text_to_submit
			$wall_id = request_var('wall', 0);
			$wall_id = $wall_id == 0 ? $user->data['user_id'] : $wall_id;

			if ($new_status != '')
			{
				$now = time();

				$isPage = request_var('isPage', 0);

				$uid = $bitfield = $flags = '';
				$allow_bbcode = $this->p_master->allow_bbcode;
				$allow_urls = $this->p_master->allow_urls;
				$allow_smilies = $this->p_master->allow_smilies;

				if ($isPage)
				{
					$page = request_var('page', array('' => ''), true);
					$page['title'] = htmlspecialchars_decode($page['title'], ENT_QUOTES);
					$page['desc'] = htmlspecialchars_decode($page['desc'], ENT_QUOTES);
					$page['uid'] = $page['bitfield'] = $page['flags'] = '';
					generate_text_for_storage($page['desc'], $page['uid'], $page['bitfield'], $page['flags'], $allow_bbcode, $allow_urls, $allow_smilies);
					$pageData = $db->sql_escape(serialize($page));

				}
				else
				{
					$pageData = '';
				}
				generate_text_for_storage($new_status, $uid, $bitfield, $flags, $allow_bbcode, $allow_urls, $allow_smilies);

				$new_status = $db->sql_escape($new_status);

				$sql = "INSERT INTO " . SN_STATUS_TABLE . " (poster_id, status_time, status_text, bbcode_bitfield, bbcode_uid, page_data, wall_id)
								VALUES (" . $user->data['user_id'] . ", " . $now . ", '{$new_status}', '{$bitfield}', '{$uid}', '{$pageData}', $wall_id)";
				$db->sql_query($sql);

				$sql = "SELECT status_id FROM " . SN_STATUS_TABLE . "
						WHERE poster_id = {$user->data['user_id']} AND wall_id = {$wall_id} AND status_time = {$now}";
				$rs = $db->sql_query($sql);
				$row = $db->sql_fetchrow($rs);
				$db->sql_freeresult($rs);

				// Record about new status
				$this->p_master->record_entry($wall_id, $row['status_id'], SN_TYPE_NEW_STATUS);
			}

			if ($on_the_wall)
			{
				if (isset($this->p_master->modules_obj['notify']) && is_object($this->p_master->modules_obj['notify']))
				{
					$this->p_master->modules_obj['notify']->ntf_check_FRIEND_WALL();
				}
				$template->set_filenames(array('body' => 'socialnet/userstatus_status.html'));

				$data = $this->_get_last_status($wall_id);
				$data['B_SN_US_CAN_COMMENT'] = true;
				$data['DELETE_STATUS'] = true;
				$template->assign_block_vars('us_status', $data);

				$this->p_master->page_header();
				$content = $this->p_master->page_footer();
				header('Content-type: text/html; charset=UTF-8');
				die($content);
			}

		}

		/**
		 * Delete User Status
		 */
		function _status_delete()
		{
			global $db, $auth, $user;

			$status_id = request_var('s_id', 0);

			$sql = "SELECT poster_id
      				FROM " . SN_STATUS_TABLE . "
      				WHERE status_id = " . $status_id;
			$res = $db->sql_query($sql);
			$userstatus = $db->sql_fetchrow($res);

			if ($auth->acl_get('a_') || ($userstatus['poster_id'] == $user->data['user_id'] /*  && $auth->acl_get('u_sn_us_delete_own_status') */))
			{
				$sql = "DELETE FROM " . SN_STATUS_TABLE . "
								WHERE status_id = " . $status_id;
				$db->sql_query($sql);

				$sql = "DELETE FROM " . SN_STATUS_COMMENTS_TABLE . "
								WHERE status_id = " . $status_id;
				$db->sql_query($sql);

				// Delete record of new status
				$this->p_master->delete_entry($status_id, SN_TYPE_NEW_STATUS);
			}

		}

		/**
		 * Load more comments
		 *
		 * Načtení dalších statusů pro zadaný uživatele
		 * Funkce zároveň ukončí běh PHP po výpisu
		 */
		function _status_more()
		{
			global $template, $phpbb_root_path;

			$user_id = request_var('u', ANONYMOUS);
			$last_status_id = request_var('lStatusID', 0);

			$template->set_filenames(array('body' => 'socialnet/userstatus_status.html'));

			$return = array();
			$return['moreStatuses'] = $this->_get_statuses($user_id, $last_status_id);

			$this->p_master->page_header();
			$content = $this->p_master->page_footer();

			$return['statuses'] = $content;

			header('Content-type: application/json');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			die(json_encode($return));
		}

		/**
		 * Add new comment
		 *
		 * Funkce vkládá komentář k příslušnému statusu
		 */
		function _comment_share()
		{
			global $db, $user, $template, $phpbb_root_path, $phpEx;

			$text_to_submit = request_var('comment', '', true); // text_to_submit
			$status_id = request_var('s_id', 0);

			if ($text_to_submit != '')
			{
				$now = time();

				$uid = $bitfield = $flags = '';
				$allow_bbcode = $this->p_master->allow_bbcode;
				$allow_urls = $this->p_master->allow_urls;
				$allow_smilies = $this->p_master->allow_smilies;

				generate_text_for_storage($text_to_submit, $uid, $bitfield, $flags, $allow_bbcode, $allow_urls, $allow_smilies);

				$sql = "INSERT INTO " . SN_STATUS_COMMENTS_TABLE . " (status_id, poster_id, comment_time, comment_text, bbcode_bitfield, bbcode_uid)
								VALUES (" . $status_id . ", " . $user->data['user_id'] . ", " . $now . ", '" . $db->sql_escape($text_to_submit) . "', '" . $bitfield . "', '" . $uid . "')";
				$db->sql_query($sql);

				$comment_sql_ary = array(
					'SELECT'	 => 'sc.*, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour',
					'FROM'		 => array(SN_STATUS_COMMENTS_TABLE => 'sc',
						USERS_TABLE => 'u', ),
					'WHERE'		 => "u.user_id = sc.poster_id AND sc.status_id = '{$status_id}' AND sc.comment_time = {$now} AND sc.poster_id = {$user->data['user_id']}",
					'ORDER_BY'	 => 'sc.comment_id ASC', );
				$comment_sql = $db->sql_build_query('SELECT', $comment_sql_ary);
				$comment_res = $db->sql_query_limit($comment_sql, 1);
				$comment_row = $db->sql_fetchrow($comment_res);

				$template->set_filenames(array('body' => 'socialnet/userstatus_status.html'));

				$template->assign_block_vars('us_status', array(
					'STATUS_ID'					 => $status_id,
					'B_SN_US_NOT_ONLY_COMMENT'	 => true,
				));

				$avatar_img = $this->p_master->get_user_avatar_resized($comment_row['user_avatar'], $comment_row['user_avatar_type'], $comment_row['user_avatar_width'], $comment_row['user_avatar_height'], 30);

				$comment_text_format = generate_text_for_display($comment_row['comment_text'], $comment_row['bbcode_uid'], $comment_row['bbcode_bitfield'], $this->p_master->bbCodeFlags);

				$template->assign_block_vars('us_status.us_comment', array('COMMENT_ID'		 => $comment_row['comment_id'],
					'POSTER_USERNAME'	 => $this->p_master->get_username_string($this->p_master->config['us_colour_username'], 'no_profile', $comment_row['poster_id'], $comment_row['username'], $comment_row['user_colour']),
					'U_POSTER_PROFILE'	 => $this->p_master->get_username_string($this->p_master->config['us_colour_username'], 'profile', $comment_row['poster_id'], $comment_row['username'], $comment_row['user_colour']),
					'POSTER_AVATAR'		 => $avatar_img,
					'TIME'				 => $user->format_date($comment_row['comment_time']),
					'TEXT'				 => $comment_text_format,
					'DELETE_COMMENT'	 => true));

				$this->p_master->page_header();
				$content = $this->p_master->page_footer();
				$this->p_master->record_entry($user->data['user_id'], $status_id, SN_TYPE_NEW_STATUS_COMMENT);
				header('Content-type: text/html; charset=UTF-8');
				die($content);
				// Record about new status comment
				}
		}

		/**
		 * Delete comment
		 */
		function _comment_delete()
		{
			global $db, $auth, $user;

			$comment_id = request_var('c_id', 0);

			$sql = "SELECT poster_id
      				FROM " . SN_STATUS_COMMENTS_TABLE . "
      				WHERE comment_id = " . $comment_id;
			$res = $db->sql_query($sql);
			$comment = $db->sql_fetchrow($res);

			if ($auth->acl_get('a_') || ($comment['poster_id'] == $user->data['user_id'] /*  && $auth->acl_get('u_sn_us_delete_own_comment') */))
			{
				$sql = "DELETE FROM " . SN_STATUS_COMMENTS_TABLE . "
								WHERE comment_id = " . $comment_id;
				$db->sql_query($sql);

				// Delete record of new comment
				$this->p_master->delete_entry($comment_id, SN_TYPE_NEW_STATUS_COMMENT);
			}
		}

		/**
		 * Load more comments
		 *
		 * Načtení dalších kometářů pro zadaný status
		 * Funkce zároveň ukončí běh PHP po výpisu
		 */
		function _comment_more()
		{
			global $template, $phpbb_root_path;

			$user_id = request_var('u', ANONYMOUS);
			$status_id = request_var('s_id', 0);
			$last_comment_id = request_var('lCommentID', 0);

			$template->set_filenames(array('body' => 'socialnet/userstatus_status.html'));

			$template->assign_block_vars('us_status', array('B_SN_US_NOT_ONLY_COMMENT' => true));
			$return = array();
			$return['moreComments'] = $this->_get_comments($status_id, $last_comment_id, 10);

			$this->p_master->page_header();
			$content = $this->p_master->page_footer();
			$return['comments'] = $content;

			header('Content-type: application/json');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			die(json_encode($return));
		}

		function _get_status()
		{
			global $template;
			$status_id = request_var('status', 0);
			$wall_id = request_var('wall', 0);

			$template->set_filenames(array('body' => 'socialnet/userstatus_status.html'));
			$data = $this->_get_last_status($wall_id, $status_id);
			$data['B_SN_US_CAN_COMMENT'] = false;
			$data['DELETE_STATUS'] = false;

			$template->assign_block_vars('us_status', $data);

			$return['content'] = str_replace(array('<a ', '</a>'), array('<span ', '</span>'), $this->p_master->get_page());
			$return['content'] = preg_replace('/<img[^>]*>/si', '', $return['content']);

			header('Content-type: application/json');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			die(json_encode($return));

		}

		/**
		 * Načti poslední status pro "on header"
		 *
		 * Funkce načítá poslední uživatelův status pro stránku
		 * @access private
		 * @return array Pole dat s uživatelovým statusem pro zobrazení
		 */
		function _get_last_userstatus()
		{
			$wall_id = $this->_wall_id();
			$return = $this->_get_last_status($wall_id);

			return array(
				'SN_US_MY_STATUS'		 => $return['SN_US_STATUS'],
				'SN_US_STATUS_POSTED'	 => $return['SN_US_STATUS_POSTED'],
			);
		}

		/**
		 * Nacti posledni
		 */
		function _get_last_status($user_id, $user_status = 0)
		{
			global $db, $user, $auth, $phpEx, $phpbb_root_path;

			$my_friends = $this->p_master->friends['user_id'];
			$my_friends[] = $user->data['user_id'];

			$sql_ary = array(
				'SELECT'	 => 's.status_id, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour',
				'FROM'		 => array(SN_STATUS_TABLE => 's', USERS_TABLE => 'u', ),
				'WHERE'		 => 'u.user_id = s.poster_id AND s.wall_id = ' . $user_id . ($user_status != 0 ? " AND status_id = {$user_status}"
				: ''), 'ORDER_BY'	 => 's.status_id DESC', );

			$sql = "SELECT s.*, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
					FROM " . SN_STATUS_TABLE . " AS s LEFT OUTER JOIN " . USERS_TABLE . " AS u
						ON u.user_id = s.poster_id
					WHERE s.wall_id = '{$user_id}'" . ($user_status != 0 ? " AND status_id = {$user_status}" : "") . "
					ORDER BY s.status_id DESC";

			//$sql = $db->sql_build_query('SELECT', $sql_ary);
			$res = $db->sql_query_limit($sql, 1);
			$status_row = $db->sql_fetchrow($res);
			$db->sql_freeresult($res);

			return $this->_get_status_array($status_row, $my_friends);
		}

		/**
		 * Load statuses
		 *
		 * Funkce načítá statusy zadaného uživatele.
		 * Podle zadaných parametrů vrátí příslušný počet statusů s komentáři
		 * @access private
		 * @param integer $user_id Identifikátor uživatele, kterému mají být načteny statusy s komentáři
		 * @param integer $last_status_id Identifikátor posledního již načteného statusu, od něho budou načteny další
		 * @param integer $status_limit Limitní počet kolik statusů má být načteno
		 * @param integer $comment_limit Limitní počet komentářů kolik jich má být načteno ke každému statusu
		 * @return boolean Vrací informaci, jestli existují další statusy daného uživatele
		 */
		function _get_statuses($user_id, $last_status_id = 0, $status_limit = 10, $comment_limit = 3, $only_one = false)
		{
			global $db, $template, $user, $auth, $phpEx, $phpbb_root_path;

			$my_friends = $this->p_master->friends['user_id'];
			$my_friends[] = $user->data['user_id'];

			$sql_ary = array(
				'SELECT'	 => 's.*, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour',
				'FROM'		 => array(SN_STATUS_TABLE => 's', USERS_TABLE => 'u', ),
				'WHERE'		 => 'u.user_id = s.poster_id AND s.wall_id = ' . $user_id . (($last_status_id != 0) ? (($only_one) ? ' AND s.status_id = ' . $last_status_id : ' AND s.status_id < ' . $last_status_id) : ''),
				'ORDER_BY'	 => 's.status_id DESC', );
			$sql = $db->sql_build_query('SELECT', $sql_ary);
			$result = $db->sql_query($sql, $status_limit + 1);
			$status_rows = $db->sql_fetchrowset($result);
			$db->sql_freeresult($result);

			for ($j = 0; $j < $status_limit && isset($status_rows[$j]); $j++)
			{
				$status_row = $status_rows[$j];

				$template_block_data = $this->_get_status_array($status_row, $my_friends);

				$template->assign_block_vars('us_status', $template_block_data);

				$template->_tpldata['us_status'][$j]['SN_US_MORE_COMMENTS'] = $this->_get_comments($status_row['status_id'], 0, $comment_limit);
			}

			return (count($status_rows) > $status_limit) ? true : false;
		}

		/**
		 * Prepare status array
		 * Funkce vytvori nezbytne pole pro vyplneni sablony se statusem.
		 * Potreba pro jednotne plneni v libovolne casti SN
		 *
		 * @since 0.6.0
		 * @author Culprit <jankalach@gmail.com>
		 * @param array $status_row Fetched array row from DB of current status
		 * @param array $my_friends Array of friends
		 * @return array Array with template data for status
		 */
		function _get_status_array($status_row, $my_friends = array())
		{
			global $phpbb_root_path, $phpEx, $auth, $user, $db;

			$avatar_img = $this->p_master->get_user_avatar_resized($status_row['user_avatar'], $status_row['user_avatar_type'], $status_row['user_avatar_width'], $status_row['user_avatar_height'], 50);
			$status_text_format = generate_text_for_display($status_row['status_text'], $status_row['bbcode_uid'], $status_row['bbcode_bitfield'], $this->p_master->bbCodeFlags);

			$pageData = array();
			$template_block_data = array();
			if ($status_row['page_data'] != '')
			{
				$pageData = unserialize($status_row['page_data']);
				$pageData['desc_text'] = generate_text_for_display($pageData['desc'], @$pageData['uid'], @$pageData['bitfield'], $this->p_master->bbCodeFlags);

				$pageData['video'] = html_entity_decode($pageData['video']);
				$pageData['video'] = preg_replace('/width="?([0-9]*)"?/si', 'width="150" owidth="\1"', $pageData['video']);
				$pageData['video'] = preg_replace('/height="?([0-9]*)"?/si', 'height="150" oheight="\1"', $pageData['video']);
				$pageData['video'] = preg_replace('/<(param[^>]*)>/si', '<\1 />', $pageData['video']);
				foreach ($pageData as $key => $value)
				{
					$template_block_data['PAGE_' . strtoupper($key)] = $value;
				}
			}

			$wall_id = $this->_wall_id();

			$another_wall = $status_row['poster_id'] != $status_row['wall_id'] && $this->script_name != 'profile'; // && $status_row['wall_id'] != $wall_id;

			$wall_row = array('username' => '', 'user_colour' => '');
			if ($another_wall)
			{
				$sql = 'SELECT username, user_colour
      			     FROM ' . USERS_TABLE . '
      			       WHERE user_id = ' . $status_row['wall_id'];
				$result = $db->sql_query($sql);
				$wall_row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
			}

			$st_time = $this->p_master->time_ago($status_row['status_time']);

			return array_merge(array(
				'SN_US_STATUS'			 => $status_row['status_text'],
				'SN_US_STATUS_POSTED'	 => $st_time,
				'STATUS_ID'				 => $status_row['status_id'],
				'U_POSTER_PROFILE'		 => $this->p_master->get_username_string($this->p_master->config['us_colour_username'], 'full', $status_row['poster_id'], $status_row['username'], $status_row['user_colour']),
				'U_PROFILE'				 => append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $status_row['poster_id']),
				'POSTER_AVATAR'			 => $avatar_img,
				'TIME'					 => $st_time,
				'TEXT'					 => $status_text_format,
				'DELETE_STATUS'			 => ($auth->acl_get('a_') || ($status_row['poster_id'] == $user->data['user_id'])) ? true : false,
				'B_SN_US_CAN_COMMENT'	 => (in_array($status_row['poster_id'], $my_friends) && in_array($status_row['wall_id'], $my_friends)) || $status_row['poster_id'] == $user->data['user_id'],
				'B_ISPAGE'				 => !empty($pageData) && !empty($pageData['title']),
				'WALL_ID'				 => $status_row['wall_id'],
				'U_WALL'				 => append_sid("{$phpbb_root_path}memberlist.$phpEx", 'mode=viewprofile&amp;u=' . $status_row['wall_id']),
				'ANOTHER_WALL'			 => $another_wall,
				'U_WALL_PROFILE'		 => $this->p_master->get_username_string($this->p_master->config['us_colour_username'], 'full', $status_row['wall_id'], $wall_row['username'], $wall_row['user_colour']),
			), $template_block_data);

		}

		/**
		 * Load comments
		 *
		 * Funkce načte k zadanému statusu.
		 * Komentáře jsou načítány podle příslušných parametrů
		 * @access private
		 * @param integer $status_id Identifikátor statusu, kterého mají být komentáře načteny
		 * @param integer $last_comment_id Identifikátor posledního již zobrazeného komentáře zadaného statusu
		 * @param integer $comment_limit Limitní počet komentářů, které mají být načteny k statusu
		 * @param string $template_parent_block Pokud chceme zapsat komentare do jineho nez defaultniho sablonoveho bloku
		 * @return boolean Vrací infromaci, jestli existují další komentáře daného stasutu uživatele
		 */

		function _get_comments($status_id, $last_comment_id = 0, $comment_limit = 3, $template_parent_block = 'us_status')
		{
			global $user, $template, $db, $auth, $config, $phpEx, $phpbb_root_path;

			$comment_order = 1; // LAST 3 // NUTNO jeste UPRAVIT JS a HTML, jinak by to melo byt spravne
			//$comment_order = 0; // FIRST 3
			$comment_order = $config['userstatus_comments_load_last'];

			$comment_sql_ary = array(
				'SELECT'	 => 'sc.*, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour',
				'FROM'		 => array(SN_STATUS_COMMENTS_TABLE => 'sc', USERS_TABLE => 'u', ),
				'WHERE'		 => 'u.user_id = sc.poster_id AND sc.status_id = ' . $status_id . ($last_comment_id != 0 ? ' AND sc.comment_id ' . ($comment_order == 0 ? ' > ' : ' < ') . $last_comment_id : ''),
				'ORDER_BY'	 => 'sc.comment_id ' . ($comment_order == 0 ? 'ASC' : 'DESC'), );

			$comment_sql = $db->sql_build_query('SELECT', $comment_sql_ary);

			$comment_sql = "SELECT sc.*, u.username, u.user_avatar, u.user_avatar_type, u.user_avatar_width, u.user_avatar_height, u.user_colour
							FROM " . SN_STATUS_COMMENTS_TABLE . " AS sc LEFT OUTER JOIN " . USERS_TABLE . " AS u
								ON u.user_id = sc.poster_id
							WHERE sc.status_id = {$status_id}" . ($last_comment_id != 0 ? ' AND sc.comment_id ' . ($comment_order == 0 ? ' > ' : ' < ') . $last_comment_id : '') . "
							ORDER BY sc.comment_id " . ($comment_order == 0 ? 'ASC' : 'DESC');

			$comment_res = $db->sql_query($comment_sql, $comment_limit + 1);
			$comment_rows = $db->sql_fetchrowset($comment_res);
			$db->sql_freeresult($comment_res);
			$comment_count = count($comment_rows);

			// $comment_more = ($comment_count > $comment_limit) ? true : false;
			$comment_more = $comment_count - $comment_limit;
			if ($comment_more < 0)
			{
				$comment_more = 0;
			}

			if ($comment_order == 0)
			{
				$comment_idx_start = 0;
			}
			else
			{
				$comment_rows = array_reverse($comment_rows);
				$comment_idx_start = $comment_count - $comment_limit;
				if ($comment_idx_start < 0)
				{
					$comment_limit = $comment_limit + $comment_idx_start;
					$comment_idx_start = 0;
				}
				else
				{
					$comment_limit = $comment_count;
				}
			}

			for ($comment_i = $comment_idx_start; $comment_i < $comment_limit && isset($comment_rows[$comment_i]); $comment_i++) //while ($comment_row = $db->sql_fetchrow($comment_res))

			{
				$comment_row = $comment_rows[$comment_i];
				$avatar_img = $this->p_master->get_user_avatar_resized($comment_row['user_avatar'], $comment_row['user_avatar_type'], $comment_row['user_avatar_width'], $comment_row['user_avatar_height'], 30);

				$comment_text_format = generate_text_for_display($comment_row['comment_text'], $comment_row['bbcode_uid'], $comment_row['bbcode_bitfield'], $this->p_master->bbCodeFlags);

				$template->assign_block_vars($template_parent_block . '.us_comment', array(
					'COMMENT_ID'				 => $comment_row['comment_id'],
					'POSTER_USERNAME'			 => $this->p_master->get_username_string($this->p_master->config['us_colour_username'], 'no_profile', $comment_row['poster_id'], $comment_row['username'], $comment_row['user_colour']),
					'POSTER_USERNAME_NO_COLOR'	 => $comment_row['username'],
					'U_POSTER_PROFILE'			 => $this->p_master->get_username_string($this->p_master->config['us_colour_username'], 'profile', $comment_row['poster_id'], $comment_row['username'], $comment_row['user_colour']),
					'POSTER_AVATAR'				 => $avatar_img,
					'TIME'						 => $this->p_master->time_ago($comment_row['comment_time']),
					'TEXT'						 => $comment_text_format,
					'DELETE_COMMENT'			 => ($auth->acl_get('a_') || ($comment_row['poster_id'] == $user->data['user_id'])) ? true : false,
				));
			}

			return $comment_more;
		}

		/**
		 * Select wall
		 * Postuji li na jinou wall nez svoji, potrebuji znat ID uzivatele, komu wall nalezi
		 *
		 * @author Culprit <jankalach@gmail.com>
		 * @since 0.6.0
		 * @access private
		 * @return integer ID of user, which belongs current wall.
		 */
		function _wall_id()
		{
			global $db, $user;
			$user_id = request_var('u', $user->data['user_id']);
			$username = request_var('un', '');
			// Get user...
			$sql = 'SELECT user_id
					FROM ' . USERS_TABLE . '
					WHERE ' . (($username) ? "username_clean = '" . $db->sql_escape(utf8_clean_string($username)) . "'" : "user_id = $user_id");
			$result = $db->sql_query($sql, 600);
			$member = $db->sql_fetchrow($result);
			$db->sql_freeresult($result);

			if (!$member)
			{
				return $user->data['user_id'];
			}
			return $member['user_id'];

		}

	}
}

if (isset($socialnet) && defined('SN_USERSTATUS'))
{
	if ($user->data['user_type'] == USER_IGNORE || $config['board_disable'] == 1)
	{

		header('Content-type: text/html');
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		die('');
		return;
	}

	$status_mode = request_var('smode', '');

	$socialnet->modules_obj['userstatus']->load($status_mode);
}
?>