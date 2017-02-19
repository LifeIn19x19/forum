<?php

/**
*
* @author jeffrey jack15083@gmail.com 
* @package umil
* @version $Id mobiquo_install.php
* @copyright (c) 2012 www.tapatalk.com
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* @ignore
*/
define('UMIL_AUTO', true);
define('IN_PHPBB', true);
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
include($phpbb_root_path . 'common.' . $phpEx);
include_once $phpbb_root_path . 'includes/functions_convert.' . $phpEx;
$user->session_begin();
$auth->acl($user->data);
$user->setup();


if (!file_exists($phpbb_root_path . 'umil/umil_auto.' . $phpEx))
{
	trigger_error('Please download the latest UMIL (Unified MOD Install Library) from: <a href="http://www.phpbb.com/mods/umil/">phpBB.com/mods/umil</a>', E_USER_ERROR);
}


$language_file = 'mods/info_acp_mobiquo';


// The name of the mod to be displayed during installation.
$mod_name = 'ACP_MOBIQUO_TITLE';

/*
* The name of the config variable which will hold the currently installed version
* You do not need to set this yourself, UMIL will handle setting and updating the version itself.
*/
$version_config_name = 'mobiquo_version';

/*
* The array of versions and actions within each.
* You do not need to order it a specific way (it will be sorted automatically), however, you must enter every version, even if no actions are done for it.
*
* You must use correct version numbering.  Unless you know exactly what you can use, only use X.X.X (replacing X with an integer).
* The version numbering must otherwise be compatible with the version_compare function - http://php.net/manual/en/function.version-compare.php
*/
$versions = array(
	// Version 1.2.10
	'3.4.0'	=> array(
	
		// Lets add a config setting and set it to true
		'config_add' => array(
			array('mobiquo_push', 1),
			array('mobiquo_hide_forum_id',''),
			array('mobiquo_guest_okay',1),
			array('mobiquo_reg_url','ucp.php?mode=register'),
			array('tapatalkdir','mobiquo'),
			array('mobiquo_is_chrome',1)
		),
		
		// Now lets add some modules to the ACP
		'module_add' => array(
            array('acp', 'ACP_CAT_DOT_MODS', 'ACP_MOBIQUO'),
		    array('acp', 'ACP_MOBIQUO', array(
					'module_basename'	=> 'mobiquo',
					'module_langname'	=> 'ACP_MOBIQUO_SETTINGS',
					'module_mode'		=> 'mobiquo',
					'module_auth'		=> 'acl_a_mobiquo',
				),
			),		
		),
		// now install the mobiquo table
		// see if it exists from prior versions
		'custom'	=> 'mobiquo_table',
		
		// Now to add some tables
		// one will hold the chats, the other the config
		'table_add' => array(
			array($table_prefix.'tapatalk_users', array(
					'COLUMNS'		=> array(
						'userid'		=> array('INT:10', 0),
						'announcement'	=> array('INT:5', 1),
						'pm'			=> array('INT:5', 1),
						'subscribe'	=> array('INT:5', 1),
						'quote'         => array('INT:5', 1),
			            'newtopic'      => array('INT:5', 1),
						'tag'           => array('INT:5', 1),
						'updated'	    => array('TIMESTAMP', 0),
					),
					'PRIMARY_KEY'	=> 'userid',
				),
			),			
		),
	),
	'3.4.1' => array(
		// Now to add some permission settings
		'permission_add' => array(
			array('a_mobiquo',true),
		),

		// Admins can do anything with mobiquo
		'permission_set' => array(
			// Global Role permissions for admins
			array('ROLE_ADMIN_FULL', 'a_mobiquo'),
		),
	),
	'3.4.2' => array(
		'module_remove' => array(
            array('acp', 'ACP_MOBIQUO', 'ACP_MOBIQUO_SETTINGS'),
		),
		'module_add'  =>array(
			array('acp', 'ACP_MOBIQUO', array(
					'module_basename'	=> 'mobiquo',
					'module_langname'	=> 'ACP_MOBIQUO_SETTINGS',
					'module_mode'		=> 'mobiquo',
					'module_auth'		=> 'acl_a_mobiquo',
				),
			),
		),
	
	),
	'3.4.3' => array(
	),
	'3.5.0' => array(
		'config_add' => array(
			array('tapatalk_push_key', ''),
		),
		'table_add' => array(
			array($table_prefix.'tapatalk_push_data', array(
					'COLUMNS'		=> array(
						'push_id'		=> array('INT:10', NULL,'auto_increment'),
						'author'	    => array('VCHAR:100',''),
						'user_id'	    => array('INT:10',0),
						'data_type'	    => array('CHAR:20',''),
						'title'         => array('VCHAR:200',''),
			            'data_id'       => array('INT:10',0),
						'create_time'	=> array('TIMESTAMP',0),
					),
					'PRIMARY_KEY'	=> 'push_id',
					'KEYS'          => array('user_id' => array('INDEX','user_id'),'ct' => array('INDEX','create_time'),'author' => array('INDEX','author')),
				),
			)
		),
	),
	'3.6.0' => array(
		'config_add' => array(
			array('tapatalk_android_msg' , 'This forum has an app for Android. Click OK to learn more about Tapatalk.'),
			array('tapatalk_android_url','market://details?id=com.quoord.tapatalkpro.activity'),
			array('tapatalk_iphone_msg','This forum has an app for iPhone ! Click OK to learn more about Tapatalk.'),
			array('tapatalk_iphone_url','http://itunes.apple.com/us/app/tapatalk-forum-app/id307880732?mt=8'),
			array('tapatalk_ipad_msg','This forum has an app for iPad! Click OK to learn more about Tapatalk.'),
			array('tapatalk_ipad_url','http://itunes.apple.com/us/app/tapatalk-hd-for-ipad/id481579541?mt=8'),
			array('tapatalk_kindle_msg','This forum has an app for Kindle Fire! Click OK to learn more about Tapatalk.'),
			array('tapatalk_kindle_url','http://www.amazon.com/gp/mas/dl/android?p=com.quoord.tapatalkpro.activity'),
			array('tapatalk_forum_read_only',''),
		),
		'module_remove' => array(
            array('acp', 'ACP_MOBIQUO', 'ACP_MOBIQUO_SETTINGS'),
		),
		'module_add'  =>array(
			array('acp', 'ACP_MOBIQUO', array(
					'module_basename'	=> 'mobiquo',
					'module_langname'	=> 'ACP_MOBIQUO_SETTINGS',
					'module_mode'		=> 'mobiquo',
					'module_auth'		=> 'acl_a_board',
				),
			),
			array('acp', 'ACP_MOBIQUO', array(
					'module_basename'	=> 'mobiquo',
					'module_langname'	=> 'ACP_TAPATALK_REBRANDING',
					'module_mode'		=> 'rebranding',
					'module_auth'		=> 'acl_a_board',
				),
			),
			
		),
		'permission_remove' => array(
			array('a_mobiquo'),
		),
	),
	'3.6.1' => array(),
	'3.7.0' => array(
		'config_add' => array(
			array('tapatalk_allow_register','1'),
			array('tapatalk_iphone_app_id',''),
			array('tapatalk_kindle_hd_msg','This forum has an app for Kindle Fire HD! Click OK to learn more about Tapatalk.'),
			array('tapatalk_kindle_hd_url',''),
			array('tapatalk_android_hd_msg','This forum has an app for Android HD! Click OK to learn more about Tapatalk.'),
			array('tapatalk_android_hd_url',''),
			array('tapatalk_app_icon_url','mobiquo/smartbanner/tapatalk2.png'),
			array('tapatalk_custom_replace',""),
			array('tapatalk_app_desc',''),
			array('tapatalk_app_name',''),
		),
		'config_remove' => array(
			array('mobiquo_is_chrome'),
		),
		'custom'	=> 'push_table_update',
	),
	'3.7.1' => array(),
	'3.8.0' => array(
		'config_remove' => array(
			array('tapatalk_iphone_app_id'),
			array('tapatalk_kindle_hd_msg'),
			array('tapatalk_kindle_hd_url'),
			array('tapatalk_android_hd_msg'),
			array('tapatalk_android_hd_url'),
			array('tapatalk_app_icon_url'),
			array('tapatalk_android_msg'),
			array('tapatalk_android_url'),
			array('tapatalk_iphone_msg'),
			array('tapatalk_iphone_url'),
			array('tapatalk_ipad_msg'),
			array('tapatalk_ipad_url'),
			array('tapatalk_kindle_msg'),
			array('tapatalk_kindle_url'),
		),
		'config_add' => array(
			array('tapatalk_app_banner_msg',''),
			array('tapatalk_app_ios_id',''),
			array('tapatalk_android_url'),
			array('tapatalk_kindle_url'),
			array('tapatalk_push_slug','0'),
		),
		
	),
	'3.8.1' => array(),
	'3.9.0' => array(),
	'4.0.0' => array(
		'config_remove' => array(
			array('mobiquo_guest_okay'),
		),
	),
	'4.1.0' => array(
		'config_add' => array(
			array('tapatalk_app_ads_enable',1),			
		),
		'config_remove' => array(
			array('tapatalk_allow_register'),
		),
		'custom'	=> 'push_table_update',
	),
	'4.1.1' => array(),
	'4.2.0' => array(),
	'4.3.0' => array(
		'module_add'  =>array(
			array('acp', 'ACP_MOBIQUO', array(
					'module_basename'	=> 'mobiquo',
					'module_langname'	=> 'ACP_MOBIQUO_REGISTER_SETTINGS',
					'module_mode'		=> 'register',
					'module_auth'		=> 'acl_a_board',
				),
			),
		),
		'config_add' => array(
			array('tapatalk_register_status',2),
			array('tapatalk_register_group',get_group_id('REGISTERED'))			
		),
		'custom'	=> 'push_table_update',
	),
	'4.3.1' => array(),
);		

		

// Include the UMIF Auto file and everything else will be handled automatically.
include($phpbb_root_path . 'umil/umil_auto.' . $phpEx);

/*
* Here is our custom function that will be called
*
* @param string $action The action (install|update|uninstall) will be sent through this.
* @param string $version The version this is being run for will be sent through this.
*/
function mobiquo_table($action, $version)
{
	global $db, $table_prefix, $umil;

	if ($action == 'install')
	{
		// Run this when installing

		if ($umil->table_exists($table_prefix.'tapatalk_users'))
		{
			//table from previous version exists...delete it.
			$sql = 'DROP TABLE ' . $table_prefix.'tapatalk_users';
			$db->sql_query($sql);
			return 'MOBIQUO_TABLE_DELETED';
		}			
		
		if ($umil->table_exists($table_prefix.'tapatalk_push_data'))
		{
			//table from previous version exists...delete it.
			$sql = 'DROP TABLE ' . $table_prefix.'tapatalk_push_data';
			$db->sql_query($sql);
			return 'MOBIQUO_TABLE_DELETED';
		}
		return 'MOBIQUO_NOTHING_TO_UPDATE';
	}
	
}

function push_table_update ($action, $version)
{
	global $db, $table_prefix, $umil,$config;
	if ($umil->table_exists($table_prefix.'tapatalk_push_data'))
	{
		if(!$umil->table_index_exists($table_prefix.'tapatalk_push_data','ct') && (!$umil->table_index_exists($table_prefix.'tapatalk_push_data','create_time')))
		{
			$cloumn = array('create_time');
			$umil->table_index_add($table_prefix.'tapatalk_push_data','ct',$cloumn);
		}
		
		if(!$umil->table_index_exists($table_prefix.'tapatalk_push_data','author'))
		{
			$cloumn = array('author');
			$umil->table_index_add($table_prefix.'tapatalk_push_data','author',$cloumn);
		}
		
		if(!$umil->table_column_exists($table_prefix.'tapatalk_push_data', 'topic_id'))
		{
			$umil->table_column_add($table_prefix.'tapatalk_push_data', 'topic_id',array('UINT', 0));
		}
	}
	
}
	
?>