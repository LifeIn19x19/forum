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

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// Social Network Permissions
$lang['permission_cat']['socialnet'] = 'Social Network';

// Adding the permissions
$lang = array_merge($lang, array(
	'acl_a_sn_settings'		 => array('lang' => 'Can alter Social Network settings', 'cat' => 'settings'),
	//'acl_u_sn_im'			 => array('lang' => 'Can use Instant Messenger', 'cat' => 'socialnet'),
	'acl_u_sn_userstatus'	 => array('lang' => 'Can use User Status', 'cat' => 'socialnet'),
	'acl_m_sn_close_reports'	=> array('lang' => 'Can close user reports', 'cat' => 'misc'),
));

?>