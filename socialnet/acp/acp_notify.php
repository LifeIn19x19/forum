<?php

if (!defined('SOCIALNET_INSTALLED') && !defined('IN_PHPBB'))
{
	return;
}

/**
 * Admin class fro module Approval for Social Network
 * @package Approval
 */
class acp_notify extends socialnet
{
	var $p_master = null;

	function acp_notify(&$p_master)
	{
		$this->p_master =& $p_master;
	}

	function main($id)
	{
		$display_vars = array(
			'title'	 => 'ACP_MP_SETTINGS',
			'vars'	 => array(
				'legend1'	 => 'ACP_SN_NOTIFY_SETTINGS',
				//	'fas_allow_use'		 => array('lang' => 'SN_FAS_CHANGE_MODULES', 'validate' => 'bool', 'type' => 'radio:yes:no', 'explain' => true),
				'ntf_theme'	 => array('lang' => 'SN_NTF_THEME', 'validate' => 'string', 'type' => 'select', 'function' => 'ntf_theme_select', 'params' => array('{CONFIG_VALUE}', 1), 'explain' => true),
				// 0.6.0.1
				'ntf_colour_username'		 => array('lang' => 'SN_COLOUR_NAME', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
		)
		);

		$this->p_master->_settings($id, 'sn_ntf', $display_vars);

	}
}

function ntf_theme_select($selected_theme, $key = '')
{
	global $user;

	if ($selected_theme == '')
	{
		$selected_theme = 'default';
	}

	$jGrowl_theme = array(
		'default'	 => isset($user->lang['BLACK']) ? $user->lang['BLACK'] : 'Black',
		'blue'		 => isset($user->lang['BLUE']) ? $user->lang['BLUE'] : 'Blue',
		'red'		 => isset($user->lang['RED']) ? $user->lang['RED'] : 'Red',
		'orange'	 => isset($user->lang['ORANGE']) ? $user->lang['ORANGE'] : 'Orange',
		'green'		 => isset($user->lang['GREEN']) ? $user->lang['GREEN'] : 'Green'
	);
	$ret_str = "";

	foreach ($jGrowl_theme as $idx => $color)
	{
		$ret_str .= '<option value="'.$idx.'"' . ($idx == $selected_theme ? ' selected="selected"' : '') . '>' . $color . '</option>';
	}

	return $ret_str;
}

?>