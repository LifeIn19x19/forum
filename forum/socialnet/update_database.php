<?php
/**
 *
 * @package phpBB3 Social Network
 * @version 0.6.2
 * @copyright (c) 2011 Kamahl & Culprit http://socialnetwork.phpbb3hacks.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

/**
 * @ignore
 */
define('UMIL_AUTO', true);
/**
 * @ignore
 */
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
/**
 * Default phpBB common file
 */
include_once($phpbb_root_path . 'common.' . $phpEx);
$user->session_begin();
$auth->acl($user->data);
$user->setup();

if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}

// The name of the mod to be displayed during installation.
$mod_name = 'Social Network';

/**
 * The name of the config variable which will hold the currently installed version
 * You do not need to set this yourself, UMIL will handle setting and updating the version itself.
 */
$version_config_name = 'version_socialNet';

/**
 * The language file which will be included when installing
 * Language entries that should exist in the language file for UMIL (replace $mod_name with the mod's name you set to $mod_name above)
 * $mod_name
 * 'INSTALL_' . $mod_name
 * 'INSTALL_' . $mod_name . '_CONFIRM'
 * 'UPDATE_' . $mod_name
 * 'UPDATE_' . $mod_name . '_CONFIRM'
 * 'UNINSTALL_' . $mod_name
 * 'UNINSTALL_' . $mod_name . '_CONFIRM'
 */
$language_file = 'mods/socialnet_acp';

/**
 * Load default constants for extend phpBB constants
 */
include_once($phpbb_root_path . 'socialnet/includes/constants.' . $phpEx);

/*
 * Options to display to the user (this is purely optional, if you do not need the options you do not have to set up this variable at all)
 * Uses the acp_board style of outputting information, with some extras (such as the 'default' and 'select_user' options)
 */

/*
 * Optionally we may specify our own logo image to show in the upper corner instead of the default logo.
 * $phpbb_root_path will get prepended to the path specified
 * Image height should be 50px to prevent cut-off or stretching.
 */
//$logo_img = 'styles/prosilver/imageset/site_logo.gif';

/*
 * The array of versions and actions within each.
 * You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
 *
 * You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
 * The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
 */
$versions = array(
	'0.4.9.9'	 => array(
		'custom' => 'phpBB_SN_rename_table',
	),

	'0.5.0'		 => array(
		'table_add'			 => array(
			// GLOBAL TABLES
			array(SN_CONFIG_TABLE, array(
				'COLUMNS'		 => array(
					'config_name'	 => array('VCHAR', ''),
					'config_value'	 => array('VCHAR', ''),
					'is_dynamic'	 => array('BOOL', 0),
				),

				'PRIMARY_KEY'	 => array('config_name'),

				'KEYS'			 => array(
					'a'	 => array('INDEX', array('is_dynamic')),
				),
			)),

			array(SN_USERS_TABLE, array(
				'COLUMNS'		 => array(
					'user_id'					 => array('UINT', 0),
					'user_status'				 => array('TEXT', ''),
					'user_im_online'			 => array('BOOL', 1),
					'user_zebra_alert_friend'	 => array('BOOL', 1),
					'user_note'					 => array('TEXT', ''),
				),
				'PRIMARY_KEY'	 => array('user_id'),
			)),

			// INSTANT MESSENGER TABLES
			array(SN_IM_TABLE, array(
				'COLUMNS'	 => array(
					'uid_from'			 => array('UINT', 0),
					'uid_to'			 => array('UINT', 0),
					'message'			 => array('TEXT', ''),
					'sent'				 => array('UINT:11', 0),
					'recd'				 => array('BOOL', 0),
					'bbcode_bitfield'	 => array('VCHAR:255', ''),
					'bbcode_uid'		 => array('VCHAR:8', ''),
				),
				'KEYS'		 => array(
					'a'	 => array('INDEX', array('sent')),
				),
			)),
			array(SN_IM_CHATBOXES_TABLE, array(
				'COLUMNS'	 => array(
					'uid_from'		 => array('UINT', 0),
					'uid_to'		 => array('UINT', 0),
					'username_to'	 => array('VCHAR:255', ''),
					'starttime'		 => array('UINT:11', 0),
				),
				'KEYS'		 => array(
					'a'	 => array('UNIQUE', array('uid_from', 'uid_to')),
				),
			)),

			// USER STATUS TABLES
			array(SN_STATUS_TABLE, array(
				'COLUMNS'		 => array(
					'status_id'			 => array('UINT', NULL, 'auto_increment'),
					'poster_id'			 => array('UINT', 0),
					'status_time'		 => array('UINT:11', 0),
					'status_text'		 => array('TEXT', ''),
					'bbcode_bitfield'	 => array('VCHAR:255', ''),
					'bbcode_uid'		 => array('VCHAR:8', ''),
				),
				'PRIMARY_KEY'	 => array('status_id'),
			)),

			array(SN_STATUS_COMMENTS_TABLE, array(
				'COLUMNS'		 => array(
					'comment_id'		 => array('UINT', NULL, 'auto_increment'),
					'status_id'			 => array('UINT', 0),
					'poster_id'			 => array('UINT', 0),
					'comment_time'		 => array('UINT:11', 0),
					'comment_text'		 => array('TEXT', ''),
					'bbcode_bitfield'	 => array('VCHAR:255', ''),
					'bbcode_uid'		 => array('VCHAR:8', ''),
				),
				'PRIMARY_KEY'	 => array('comment_id'),
			)),

			array(SN_ENTRIES_TABLE, array(
				'COLUMNS'		 => array(
					'entry_id'		 => array('UINT', NULL, 'auto_increment'),
					'user_id'		 => array('UINT', 0),
					'entry_target'	 => array('UINT', 0),
					'entry_type'	 => array('UINT:11', 0),
					'entry_time'	 => array('UINT:11', 0),
				),
				'PRIMARY_KEY'	 => array('entry_id'),
			)),

		),

		'table_column_add'	 => array(
			array(ZEBRA_TABLE, 'approval', array('BOOL', 0)),
		),

		'table_row_insert'	 => array(
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_global_enable', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'module_im', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'module_userstatus', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'module_approval', 'config_value' => '0')),
			array(SN_CONFIG_TABLE, array('config_name' => 'module_mainpage', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_only_friends', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_allow_sound', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_url_new_window', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'fas_alert_friend_pm', 'config_value' => '0')),

			array(SN_USERS_TABLE, array('user_id' => ANONYMOUS, 'user_status' => '', 'user_im_online' => 1, 'user_zebra_alert_friend' => 0, 'user_note' => '')),
			// USER STATUS
			array(SN_CONFIG_TABLE, array('config_name' => 'userstatus_comments_load_last', 'config_value' => '1', 'is_dynamic' => 0)),
			// CONFIRM BOX
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_cb_enable', 'config_value' => '1', 'is_dynamic' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_cb_resize', 'config_value' => '0', 'is_dynamic' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_cb_draggable', 'config_value' => '0', 'is_dynamic' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_cb_modal', 'config_value' => '1', 'is_dynamic' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_cb_width', 'config_value' => '400', 'is_dynamic' => 0)),
		),

		'permission_add'	 => array(
			array('a_sn_settings', true),
			//	array( 'u_sn_im', true),
			array('u_sn_userstatus', true),
		),

		'module_add'		 => array(
			// TAB
			array('acp', 0, 'ACP_CAT_SOCIALNET'),
			// MAIN INTRO
			array('acp', 'ACP_CAT_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_MAIN',
				'module_mode'		 => 'main',
				'module_auth'		 => 'acl_a_sn_settings',
			)),

			// CATEGORY: SOCIALNET Settings
			array('acp', 'ACP_CAT_SOCIALNET', 'ACP_SN_CONFIGURATION'),
			// MODULE: SocialNet Global Settings
			array('acp', 'ACP_SN_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_AVAILABLE_MODULES',
				'module_mode'		 => 'sett_modules',
				'module_auth'		 => 'acl_a_sn_settings'
			)),

			array('acp', 'ACP_SN_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_CONFIRMBOX_SETTINGS',
				'module_mode'		 => 'sett_confirmBox',
				'module_auth'		 => 'acl_a_sn_settings'
			)),

			// CATEGORY: MODULES Settings
			array('acp', 'ACP_CAT_SOCIALNET', 'ACP_SN_MODULES_CONFIGURATION'),
			// MODULE: Core Modules
			array('acp', 'ACP_SN_MODULES_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_IM_SETTINGS',
				'module_mode'		 => 'module_im',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
			array('acp', 'ACP_SN_MODULES_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_USERSTATUS_SETTINGS',
				'module_mode'		 => 'module_userstatus',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
			array('acp', 'ACP_SN_MODULES_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_APPROVAL_SETTINGS',
				'module_mode'		 => 'module_approval',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
			array('acp', 'ACP_SN_MODULES_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_MAINPAGE_SETTINGS',
				'module_mode'		 => 'module_mainpage',
				'module_auth'		 => 'acl_a_sn_settings'
			)),

			array('ucp', 0, 'UCP_SOCIALNET'),

			array('ucp', 'UCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_ZEBRA_FRIENDS',
				'module_mode'		 => 'module_approval_friends',
				'module_auth'		 => ''
			)),

			array('ucp', 'UCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_IM',
				'module_mode'		 => 'module_im',
				'module_auth'		 => '',
			)),
		),

		'custom'			 => 'phpbb_SN_umil_auto',
	),
	
	'0.5.0.9'	 => array(
		'custom' => 'phpBB_SN_rename_table',
	),

	'0.5.1'		 => array(

		'table_column_add'	 => array(
			array(SN_USERS_TABLE, 'user_im_sound', array('TINT:1', '1')),
		),

		'table_row_insert'	 => array(
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_show_new_friendships', 'config_value' => '1')),
		),

		'custom'			 => 'phpbb_SN_umil_auto',
	),

	'0.5.1.9'	 => array(
		'custom' => 'phpBB_SN_rename_table',
	),
	
	'0.5.2'		 => array(
		'table_row_insert'	 => array(
			array(SN_CONFIG_TABLE, array('config_name' => 'im_msg_purged_time', 'config_value' => '0')),
			array(SN_CONFIG_TABLE, array('config_name' => 'fas_friendlist_limit', 'config_value' => '20')),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_num_last_topics', 'config_value' => '10')),
		),

		'module_add'		 => array(
			array('ucp', 'UCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_IM_HISTORY',
				'module_mode'		 => 'module_im_history',
				'module_auth'		 => '',
			)),
		),

		'table_column_add'	 => array(
			array(SN_USERS_TABLE, 'user_im_soundname', array('VCHAR:255', 'IM_New-message-1.mp3')),
		),

		'custom'			 => 'phpbb_SN_umil_auto',
	),

	'0.5.9.9'	 => array(
		'custom' => 'phpBB_SN_rename_table',
	),

	'0.6.0'		 => array(
		'table_index_add'	 => array(
			array(SN_IM_TABLE, 'b', array('uid_to', 'recd')),
			array(ZEBRA_TABLE, 'c', array('user_id', 'zebra_id', 'approval')),
			array(SN_IM_CHATBOXES_TABLE, 'b', array('uid_from', 'uid_to', 'starttime')),
			array(SN_ENTRIES_TABLE, 'a', array('user_id', 'entry_target', 'entry_type', 'entry_time')),
			array(SN_STATUS_TABLE, 'b', array('poster_id', 'status_time')),
			array(SN_STATUS_COMMENTS_TABLE, 'a', array('status_id', 'poster_id', 'comment_time')),
		),

		'table_add'			 => array(
			array(SN_NOTIFY_TABLE, array(
				'COLUMNS'		 => array(
					'ntf_id'	 => array('UINT:11', NULL, 'auto_increment'),
					'ntf_time'	 => array('UINT:11', 0),
					'ntf_type'	 => array('USINT', 0),
					'ntf_user'	 => array('UINT', 0),
					'ntf_poster' => array('UINT', 0),
					'ntf_read'	 => array('USINT', 0),
					'ntf_change' => array('UINT:11', 0),
					'ntf_data'	 => array('TEXT', ''),
				),
				'PRIMARY_KEY'	 => array('ntf_id'),
				'KEYS'			 => array(
					'a'	 => array('INDEX', array('ntf_read', 'ntf_user')),
					'b'	 => array('INDEX', array('ntf_read', 'ntf_time')),
					'c'	 => array('INDEX', array('ntf_read', 'ntf_change')),
				),
			)),
		),

		'table_column_add'	 => array(
			array(SN_STATUS_TABLE, 'page_data', array('TEXT', NULL)),
			array(SN_STATUS_TABLE, 'wall_id', array('UINT:8', 0)),
		),

		'module_add'		 => array(
			array('acp', 'ACP_SN_MODULES_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_NOTIFY_SETTINGS',
				'module_mode'		 => 'module_notify',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
		),

		'table_row_insert'	 => array(
			array(SN_CONFIG_TABLE, array('config_name' => 'module_notify', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'ntf_theme', 'config_value' => 'default')),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_display_welcome', 'config_value' => '0', 'is_dynamic' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_msg_purged_automatic_time', 'config_value' => '0')),
		),

	),

	'0.6.0.9'	 => array(
		'custom' => 'phpBB_SN_rename_table',
	),

	'0.6.1'		 => array(

		'table_add'	 => array(
			array(SN_REPORTS_TABLE, array(
				'COLUMNS'		 => array(
					'report_id'		 => array('UINT', NULL, 'auto_increment'),
					'reason_id'		 => array('USINT', 0),
					'report_text'	 => array('TEXT', ''),
					'user_id'		 => array('UINT', 0),
					'reporter'		 => array('UINT', 0),
					'report_closed'	 => array('TINT:1', '0'),
				),
				'PRIMARY_KEY'	 => array('report_id'),
			)),
			array(SN_REPORTS_REASONS_TABLE, array(
				'COLUMNS'		 => array(
					'reason_id'		 => array('USINT', NULL, 'auto_increment'),
					'reason_text'	 => array('TEXT', ''),
				),
				'PRIMARY_KEY'	 => array('reason_id'),
			)),
			array(SN_MENU_TABLE, array(
				'COLUMNS'		 => array(
					'button_id'				 => array('UINT', NULL, 'auto_increment'),
					'button_url'			 => array('TEXT', ''),
					'button_name'			 => array('VCHAR', ''),
					'button_external'		 => array('BOOL', 0),
					'button_display'		 => array('BOOL', 1),
					'button_only_registered' => array('BOOL', 0),
					'button_only_guest'		 => array('BOOL', 0),
					'left_id'				 => array('UINT', 0),
					'right_id'				 => array('UINT', 0),
					'parent_id'				 => array('UINT', 0),
				),
				'PRIMARY_KEY'	 => array('button_id'),
				'KEY'			 => array(
					'a'	 => array('left_id'),
					'b'	 => array('right_id'),
					'c'	 => array('parent_id'),
					'd'	 => array('parent_id', 'left_id'),
				),
			)),
			array(SN_FAMILY_TABLE, array(
				'COLUMNS'		 => array(
					'id'				 => array('UINT', NULL, 'auto_increment'),
					'user_id'			 => array('UINT', '0'),
					'relative_user_id'	 => array('UINT', '0'),
					'status_id'			 => array('UINT', '0'),
					'approved'			 => array('TINT:1', '0'),
					'anniversary'		 => array('VCHAR:10', ''),
					'family'			 => array('TINT:1', '0'),
					'name'				 => array('VCHAR:255', ''),
				),
				'PRIMARY_KEY'	 => array('id'),
				'KEY'			 => array(
					'a'	 => array('user_id'),
					'b'	 => array('relative_user_id'),
					'c'	 => array('status_id'),
					'd'	 => array('approved'),
				),
			)),
			array(SN_PROFILE_VISITORS_TABLE, array(
				'COLUMNS'	 => array(
					'profile_uid'	 => array('UINT', '0'),
					'visitor_uid'	 => array('UINT', '0'),
					'visit_time'	 => array('UINT:11', '0'),
				),
				'KEY'		 => array(
					'a'	 => array('profile_uid'),
					'b'	 => array('visitor_uid'),
					'c'	 => array('visit_time')
				),
			)),
			array(SN_FMS_GROUPS_TABLE, array(
				'COLUMNS'		 => array(
					'fms_gid'	 => array('UINT', NULL, 'auto_increment'),
					'user_id'	 => array('UINT', '0'),
					'fms_name'	 => array('VCHAR:255', ''),
					'fms_clean'	 => array('VCHAR:255', ''),
				),
				'PRIMARY_KEY'	 => array('user_id', 'fms_clean'),
				'KEYS'			 => array(
					'a'	 => array('UNIQUE', array('user_id', 'fms_name')),
					'b'	 => array('INDEX', array('fms_gid', 'user_id')),
					'c'	 => array('INDEX', array('user_id')),
				),

			)),
			array(SN_FMS_USERS_GROUP_TABLE, array(
				'COLUMNS'		 => array(
					'fms_gid'	 => array('UINT', '0'),
					'user_id'	 => array('UINT', '0'),
				),
				'PRIMARY_KEY'	 => array('fms_gid', 'user_id'),
				'KEYS'			 => array(
					'a'	 => array('INDEX', array('user_id')),
					'b'	 => array('INDEX', array('fms_gid')),
				),
			)),
			/*array(SN_SUBSCRIPTIONS_TABLE, array(
			 'COLUMNS'		 => array(
			 'id'				 => array('UINT', NULL, 'auto_increment'),
			 'user_id'			 => array('UINT', '0'),
			 'subscribed_uid'	 => array('UINT', '0'),
			 'approved'	 => array('TINT:1', '0'),
			 ),
			 'PRIMARY_KEY'	 => array('id'),
			 'KEY'			 => array(
			 'a'	 => array('user_id'),
			 'b'	 => array('subscribed_uid'),
			 ),
			 )),  */
		),

		'table_column_add' => array(
			array(SN_USERS_TABLE, 'hometown', array('VCHAR:255', '')),
			array(SN_USERS_TABLE, 'sex', array('TINT:1', 0)),
			array(SN_USERS_TABLE, 'interested_in', array('TINT:1', 0)),
			array(SN_USERS_TABLE, 'languages', array('TEXT', '')),
			array(SN_USERS_TABLE, 'about_me', array('TEXT', '')),
			array(SN_USERS_TABLE, 'employer', array('TEXT', '')),
			array(SN_USERS_TABLE, 'university', array('TEXT', '')),
			array(SN_USERS_TABLE, 'high_school', array('TEXT', '')),
			array(SN_USERS_TABLE, 'religion', array('TEXT', '')),
			array(SN_USERS_TABLE, 'political_views', array('TEXT', '')),
			array(SN_USERS_TABLE, 'quotations', array('TEXT', '')),
			array(SN_USERS_TABLE, 'music', array('TEXT', '')),
			array(SN_USERS_TABLE, 'books', array('TEXT', '')),
			array(SN_USERS_TABLE, 'movies', array('TEXT', '')),
			array(SN_USERS_TABLE, 'games', array('TEXT', '')),
			array(SN_USERS_TABLE, 'foods', array('TEXT', '')),
			array(SN_USERS_TABLE, 'sports', array('TEXT', '')),
			array(SN_USERS_TABLE, 'sport_teams', array('TEXT', '')),
			array(SN_USERS_TABLE, 'activities', array('TEXT', '')),
			array(SN_USERS_TABLE, 'skype', array('VCHAR:32', '')),
			array(SN_USERS_TABLE, 'facebook', array('VCHAR:255', '')),
			array(SN_USERS_TABLE, 'twitter', array('VCHAR:255', '')),
			array(SN_USERS_TABLE, 'youtube', array('VCHAR:255', '')),
			array(SN_USERS_TABLE, 'profile_views', array('UINT:11', 0)),
			array(SN_USERS_TABLE, 'profile_last_change', array('UINT:11', 0)),
			array(SN_ENTRIES_TABLE, 'entry_additionals', array('TEXT', '')),
		),

		'table_row_insert' => array(
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_hide_for_guest', 'config_value' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_colour_username', 'config_value' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'us_colour_username', 'config_value' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'fas_colour_username', 'config_value' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_colour_username', 'config_value' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'ntf_colour_username', 'config_value' => 0)),
			array(SN_CONFIG_TABLE, array('config_name' => 'report_user_enable', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_myprofile', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_menu', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_search', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_friends_suggestions', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_friend_requests', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_birthday', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_recent_discussions', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_statistics', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'sn_block_online_users', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'block_uo_all_users', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'block_uo_check_every', 'config_value' => '10')),
			array(SN_CONFIG_TABLE, array('config_name' => 'module_profile', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'up_enable_report', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_show_profile_updated', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_show_new_family', 'config_value' => '1')),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_show_new_relationship', 'config_value' => '1')),
			array(SN_REPORTS_REASONS_TABLE, array('reason_text' => 'This person is annoying me')),
			array(SN_REPORTS_REASONS_TABLE, array('reason_text' => 'This profile is pretending to be someone or is fake')),
			array(SN_REPORTS_REASONS_TABLE, array('reason_text' => 'Inappropriate profile photo')),
			array(SN_REPORTS_REASONS_TABLE, array('reason_text' => 'This person is bullying or harassing me')),
			array(SN_REPORTS_REASONS_TABLE, array('reason_text' => 'Inappropriate Wall post')),
			array(SN_REPORTS_REASONS_TABLE, array('reason_text' => 'Other')),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_checkTime_min', 'config_value' => 1)),
			array(SN_CONFIG_TABLE, array('config_name' => 'im_checkTime_max', 'config_value' => 60)),
			//array(SN_CONFIG_TABLE, array('config_name' => 'mp_num_last_posts', 'config_value' => 10)),
			array(SN_CONFIG_TABLE, array('config_name' => 'mp_max_profile_value', 'config_value' => 60)),
			array(SN_CONFIG_TABLE, array('config_name' => 'up_enable_subscriptions', 'config_value' => 1)),
			array(SN_CONFIG_TABLE, array('config_name' => 'up_alert_relation_pm', 'config_value' => '0')),
		),

		'permission_add' => array(
			array('m_sn_close_reports', true),
		),

		'module_add' => array(
			array('acp', 'ACP_SN_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_BLOCKS_ENABLE',
				'module_mode'		 => 'blocks_enable',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
			array('acp', 'ACP_SN_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_BLOCKS_CONFIGURATION',
				'module_mode'		 => 'blocks_config',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
			array('ucp', 'UCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_APPROVAL_UFG',
				'module_mode'		 => 'module_approval_ufg',
				'module_auth'		 => ''
			)),
			array('ucp', 'UCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_PROFILE',
				'module_mode'		 => 'module_profile',
				'module_auth'		 => ''
			)),
			array('ucp', 'UCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_PROFILE_RELATIONS',
				'module_mode'		 => 'module_profile_relations',
				'module_auth'		 => '',
			)),
			array('ucp', 'UCP_PROFILE', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_PROFILE',
				'module_mode'		 => 'module_profile',
				'module_auth'		 => ''
			)),
			array('ucp', 'UCP_PROFILE', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'UCP_SN_PROFILE_RELATIONS',
				'module_mode'		 => 'module_profile_relations',
				'module_auth'		 => '',
			)),
			array('acp', 'ACP_SN_MODULES_CONFIGURATION', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'ACP_SN_PROFILE_SETTINGS',
				'module_mode'		 => 'module_profile',
				'module_auth'		 => 'acl_a_sn_settings'
			)),
			array('mcp', 0, 'MCP_SOCIALNET'),
			array('mcp', 'MCP_SOCIALNET', array(
				'module_basename'	 => 'socialnet',
				'module_langname'	 => 'MCP_SN_REPORTUSER',
				'module_mode'		 => 'module_reportuser',
				'module_auth'		 => 'acl_m_sn_close_reports'
			)),
		),

		'custom'	 => 'phpbb_SN_umil_auto',
	),
	
	'0.6.2'	 => array(
	
    'cache_purge' => array(
			'imageset',
			'template',
			'theme',
			'cache',
		),
		
	),

);

// Include the UMIF Auto file and everything else will be handled automatically.
/**
 * @ignore
 */
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

/**
 * Here is our custom function that will be called.
 *
 * @access public
 * @param string $action The action (install|update|uninstall) will be sent through this.
 * @param string $version The version this is being run for will be sent through this.
 */
function phpbb_SN_umil_auto($action, $version)
{
	global $db, $umil;

	if ($action == 'uninstall')
	{
		// Run this when uninstalling
		}
	else if ($action == 'install')
	{
		// Run this when installing
		$umil->permission_set('REGISTERED', 'u_sn_userstatus', 'group');
	}
	else
	{
		// Run this when updating
		if ($version == '0.6.0')
		{
			$db->sql_query("UPDATE " . SN_STATUS_TABLE . " SET wall_id = poster_id");
		}

	}
}

function phpbb_SN_umil_0_6_1($action, $version)
{
	global $umil;
	phpBB_SN_rename_table($action, $version);

	$umil->permission_set('ROLE_MOD_STANDARD', 'm_sn_close_reports', 'role', $action != 'uninstall');
	$umil->permission_set('ROLE_MOD_FULL', 'm_sn_close_reports', 'role', $action != 'uninstall');
	$umil->permission_set('ROLE_MOD_QUEUE', 'm_sn_close_reports', 'role', false);
	$umil->permission_set('ROLE_MOD_SIMPLE', 'm_sn_close_reports', 'role', false);
}

/**
 * Function for table rename by install/update
 */
function phpBB_SN_rename_table($action, $version)
{
	$constants = get_defined_constants();
	foreach ($constants as $key => $value)
	{
		if (preg_match('/^SN_.*_TABLE$/', $key))
		{
			sql_rename_table($value);
		}
	}

}

/**
 * Function for catch old table name
 */
function sql_old_table_name($new_name)
{
	global $table_prefix, $table_prefix_socialnet;
	return preg_replace('/^' . $table_prefix_socialnet . '/', $table_prefix . 'socialnet_', $new_name);
}

/**
 * Function for rename SQL table for any Layer
 */
function sql_rename_table($new_name)
{
	global $db, $umil, $dbms;

	$old_name = sql_old_table_name($new_name);
	if ($umil->table_exists($old_name) && !$umil->table_exists($new_name))
	{
		switch ($db->sql_layer)
		{
			case 'firebird':
			case 'postgres':
			case 'oracle':
			case 'sqlite':
			case 'mysql_40':
			case 'mysql_41':
			case 'mysqli':
			case 'mysql':
			case 'mysql4':
				$sql = "ALTER TABLE {$old_name} RENAME TO {$new_name}";
				break;

			case 'mssql':
			case 'mssqlnative':
				$sql = "EXEC sp_rename '{$old_name}', '{$new_name}'";
				break;
		}

		$db->sql_query($sql);
	}

}

?>