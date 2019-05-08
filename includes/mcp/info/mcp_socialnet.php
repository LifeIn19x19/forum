<?php
/**
*
* @package phpBB3 Social Network    
* @version 0.6.1
* @copyright (c) 2011 Kamahl & Culprit http://socialnetwork.phpbb3hacks.com
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

class mcp_socialnet_info
{
	function module()
	{
		global $config;

		if (!isset($config['version_socialNet']))
		{
			return array();
		}

		return array(
			'filename'	 => 'mcp_socialnet',
			'title'		 => 'MCP_SOCIALNET',
			'version'	 => $config['version_socialNet'],
			'modes'		 => array(
				'module_reportuser'					 => array('title' => 'MCP_SN_REPORTUSER', 'auth' => 'acl_m_sn_close_reports', 'cat' => array('MCP_SOCIALNET')),
			),
		);
	}

	function install()
	{
	}

	function uninstall()
	{
	}
}
?>