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

if (!isset($table_prefix))
{
	/**
	 * @ignore
	 */
	include($phpbb_root_path . 'config.' . $phpEx);
	unset($dbpasswd);
}

$table_prefix_socialnet = $table_prefix . 'sn_';

define('SN_TYPE_NEW_STATUS', 1);
define('SN_TYPE_NEW_STATUS_COMMENT', 2);
define('SN_TYPE_NEW_FRIENDSHIP', 3);
define('SN_TYPE_PROFILE_UPDATED', 4);
define('SN_TYPE_NEW_FAMILY', 5);
define('SN_TYPE_NEW_RELATIONSHIP', 6);

define('SN_NTF_FRIENDSHIP', 0x60);
define('SN_NTF_COMMENT', 0x30);
define('SN_NTF_WALL', 0x90);
define('SN_NTF_FAMILY', 0xb0);
define('SN_NTF_REALTION', 0xb1);

define('SN_NTF_STATUS_NEW', 0x20);
define('SN_NTF_STATUS_DISPLAYED', 0x15);
define('SN_NTF_STATUS_UNREAD', 0x13);
define('SN_NTF_STATUS_READ', 0x10);

define('SN_IM_TABLE', $table_prefix_socialnet . 'im');
define('SN_CONFIG_TABLE', $table_prefix_socialnet . 'config');
define('SN_USERS_TABLE', $table_prefix_socialnet . 'users');
define('SN_IM_CHATBOXES_TABLE', $table_prefix_socialnet . 'im_chatboxes');
define('SN_STATUS_TABLE', $table_prefix_socialnet . 'status');
define('SN_STATUS_COMMENTS_TABLE', $table_prefix_socialnet . 'status_comments');
define('SN_ENTRIES_TABLE', $table_prefix_socialnet . 'entries');
define('SN_FMS_GROUPS_TABLE', $table_prefix_socialnet . 'fms_groups');
define('SN_FMS_USERS_GROUP_TABLE', $table_prefix_socialnet . 'fms_users_group');
define('SN_NOTIFY_TABLE', $table_prefix_socialnet . 'notify');
define('SN_MENU_TABLE', $table_prefix_socialnet . 'menu');
define('SN_REPORTS_TABLE', $table_prefix_socialnet . 'reports');
define('SN_REPORTS_REASONS_TABLE', $table_prefix_socialnet . 'reports_reasons');
define('SN_FAMILY_TABLE',				$table_prefix_socialnet . 'family');
define('SN_PROFILE_VISITORS_TABLE',		$table_prefix_socialnet . 'profile_visitors');
define('SN_SUBSCRIPTIONS_TABLE',		$table_prefix_socialnet . 'subscriptions');

?>