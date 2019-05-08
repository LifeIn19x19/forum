<?php
/**
 *
 * @package phpBB3 Social Network
 * @version 0.6.1
 * @copyright (c) 2011 Kamahl & Culprit http://socialnetwork.phpbb3hacks.com
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 *
 */

class ucp_im
{
	var $p_master = null;

	function ucp_im(&$p_master)
	{
		$this->p_master =& $p_master;
	}

	function main($id, $module)
	{
		global $template, $config, $user, $db, $socialnet;
		$display_vars = array();

		switch ($module)
		{
			case 'default':
				$display_vars = array(
					'title'	 => 'ACP_IM_SETTINGS',
					'vars'	 => array(
						'legend1'			 => 'UCP_SN_IM_SETTINGS',
						'user_im_online'	 => array('lang' => 'IM_ONLINE', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
						'user_im_sound'		 => array('lang' => 'IM_ALLOW_SOUND', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),

						'user_im_soundname'	 => array('lang' => 'IM_SOUND_SELECT_NAME', 'validate' => 'string', 'type' => 'custom', 'function' => array($this, '_soundSelect'), 'explain' => true),

						/*
						 'user_friends_only'	 => array('lang' => 'IM_ONLY_FRIENDS', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
																																																																																																						
						 //'im_override_cfg'	 => array('lang' => 'IM_OVERRIDE_USER_SETTINGS', 'validate' => 'bool', 'type' => 'radio:yes_no', 'explain' => true),
						 */
					)
				);

				$this->p_master->_settings($id, 'sn_im', $display_vars);

				$template->assign_vars(array(
					'S_SN_IM_USER_SOUNDNAME' => $this->p_master->new_config['user_im_soundname'],
					'SN_IM_ONLINE'			 => $this->p_master->new_config['user_im_online'],
				));

				break;

			case 'history':
				$this->p_master->tpl_name = 'socialnet/ucp_im_history';
				$error = array();

				$history_limit = 20;
				$history_username = '';
				$history_user = request_var('u', 0);
				$history_start = request_var('start', 0);
				$history_limit = request_var('limit', (int) $history_limit);

				$sql = "SELECT DISTINCT u.username, u.user_id, u.user_colour
						      FROM " . SN_IM_TABLE . " AS im,
							         " . USERS_TABLE . " AS u
						        WHERE (im.uid_from = u.user_id OR im.uid_to = u.user_id )
                      AND (im.uid_from = {$user->data['user_id']} OR im.uid_to = {$user->data['user_id']} )
									    AND u.user_id <> {$user->data['user_id']}
						        ORDER BY u.username";
				$rs = $db->sql_query($sql);
				while ($row = $db->sql_fetchrow($rs))
				{
					if ($history_user == $row['user_id'])
					{
						$history_username = $row['username'];
						$user_colour = $row['user_colour'];
					}

					$template->assign_block_vars('users', array(
						'USER_ID'	 => $row['user_id'],
						'USERNAME'	 => $row['username'],
						'SELECTED'	 => $row['user_id'] == $history_user,
					));
				}
				$db->sql_freeresult($rs);
				
				if ($config['sn_cb_enable'])
				{
					$template->assign_var('S_UCP_ACTION', $this->p_master->u_action . '&amp;confirmBox=0');
				}
				
				if ($history_user)
				{
					$pagination_url = append_sid($this->p_master->u_action, 'u=' . $history_user);

					$sql = "SELECT COUNT(*) AS count
                    FROM " . SN_IM_TABLE . "
  						        WHERE uid_from IN ({$user->data['user_id']}, {$history_user})
                        AND uid_to IN ({$user->data['user_id']}, {$history_user})";
					$rs = $db->sql_query($sql);
					$row = $db->sql_fetchrow($rs);

					$history_total = $row['count'];

					$sql = "SELECT *
                    FROM " . SN_IM_TABLE . "
  						        WHERE uid_from IN ({$user->data['user_id']}, {$history_user})
                        AND uid_to IN ({$user->data['user_id']}, {$history_user})
  						        ORDER BY sent DESC";
					$rs = $db->sql_query_limit($sql, $history_limit, $history_start);

					while ($row = $db->sql_fetchrow($rs))
					{
						$message = generate_text_for_display($row['message'], $row['bbcode_uid'], $row['bbcode_bitfield'], $socialnet->bbCodeFlags);

						$template->assign_block_vars('history', array(
							'USERNAME'	 => $socialnet->get_username_string($config['im_colour_username'], 'full', $row['uid_from'], $row['uid_from'] == $user->data['user_id'] ? $user->data['username'] : $history_username, $row['uid_from'] == $user->data['user_id'] ? $user->data['user_colour'] : $user_colour),
							'TIME'		 => $user->format_date($row['sent']),
							'MESSAGE'	 => $message,
							'B_INCOMING' => $row['uid_to'] == $user->data['user_id'],
						));
					}
					$db->sql_freeresult($rs);

					if ($config['im_msg_purged_time'] != 0)
					{
						$error[] = sprintf($user->lang['IM_HISTORY_PURGED_AT'], $user->format_date($config['im_msg_purged_time']));
					}

					$template->assign_vars(array(
						'ERROR'			 => implode('<br />', $error),
						'PAGINATION'	 => generate_pagination($pagination_url, $history_total, $history_limit, $history_start),
						'PAGE_NUMBER'	 => on_page($history_total, $history_limit, $history_start),
						'MSG_TOTAL'		 => $history_total == 1 ? $user->lang['IM_MSG_TOTAL'] : sprintf($user->lang['IM_MSGS_TOTAL'], $history_total),
						'U_EXPORT_IM'		 => append_sid($this->p_master->u_action, 'u=' . $history_user),
					));

					// Export history to .txt file
					$export = (isset($_POST['export'])) ? true : false;

					if ($export)
					{
						$sql = "SELECT *
                      FROM " . SN_IM_TABLE . "
						            WHERE uid_from IN ({$user->data['user_id']}, {$history_user})
                          AND uid_to IN ({$user->data['user_id']}, {$history_user})
						            ORDER BY sent ASC";
						$result = $db->sql_query($sql);
						$output = '';

						while ($row = $db->sql_fetchrow($result))
						{
							$date = $user->format_date($row['sent']);
							$from_to = ($row['uid_to'] == $user->data['user_id']) ? $history_username . ' » ' . $user->data['username'] : $user->data['username'] . ' » ' . $history_username;
							strip_bbcode($row['message'], $row['bbcode_uid']);
							$message = str_replace("<br />", "\n", $row['message']);

							$line = $from_to . "\n" . $date . "\n" . $message . "\n";

							$output .= $line;
						}
						$db->sql_freeresult($result);

						$output = pack('CCC', 239, 187, 191) . $output;
						Header('Pragma: no-cache');
						Header('Cache-control: no-cache');
						Header('Expires: ' . gmdate("D, d m Y H:i:s") . ' GMT');
						header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');

						header('Content-Description: File Transfer');
						header('Content-Type: text/txt; charset="UTF-8"');
						header("Content-length: " . strlen($output));
						header('Content-Disposition: attachment; filename="' . $history_username . '_im_history.txt"');

						exit($output);
					}
				}

				break;
		}

	}

	function _soundSelect($value, $key)
	{
		global $phpbb_root_path, $cache;

		$soundSelect_ary = $cache->get('_snImSoundSelect');

		if (empty($soundSelect_ary))
		{
			foreach (glob("{$phpbb_root_path}socialnet/styles/sound/*.mp3") as $filename)
			{
				$sound = basename($filename);
				$soundSelect_ary[$sound] = str_replace('-', ' ', str_replace('_', '::', substr($sound, 0, -4)));
			}

			ksort($soundSelect_ary);
			$cache->put('_snImSoundSelect', $soundSelect_ary);
		}

		$soundSelect = '<select name="config[' . $key . ']" id="' . $key . '">';
		if (!empty($soundSelect_ary))
		{
			foreach ($soundSelect_ary as $idx => $soundName)
			{
				$soundSelect .= '<option value="' . $idx . '"' . ($value == $idx ? ' selected="selected"' : '') . '>' . $soundName . '</option>';
			}
		}
		$soundSelect .= '</select>';

		$soundSelect .= '<br /><br />';

		$soundSelect .= '<div id="snImSoundTest"><object id="snTest" type="application/x-shockwave-flash" data="' . $phpbb_root_path . 'socialnet/styles/sound/player_mp3_maxi.swf" width="200" height="20">
     <param name="movie" value="' . $phpbb_root_path . 'socialnet/styles/sound/player_mp3_maxi.swf" />
     <param name="autoload" value="1" />
     <param name="FlashVars" value="mp3=' . $phpbb_root_path . 'socialnet/styles/sound/' . $value . '" />
</object></div><script type="text/javascript">
jQuery(document).ready(function($){
	$("#' . $key . '").change(function(){
		var mp3 = "mp3=' . $phpbb_root_path . 'socialnet/styles/sound/"+$(this).children("option:selected").val();
		
		if ($.browser.msie) {
			$(\'#snImSoundTest\').html(\'<object height="20" width="200" type="application/x-shockwave-flash" data="' . $phpbb_root_path . 'socialnet/styles/sound/player_mp3_maxi.swf"><param name="movie" value="' . $phpbb_root_path . 'socialnet/styles/sound/player_mp3_maxi.swf"><param name="FlashVars" value="\'+mp3+\'"></object>\');
		} else {
			$(\'#snImSoundTest\').html(\'<embed src="' . $phpbb_root_path . 'socialnet/styles/sound/player_mp3_maxi.swf" width="200" height="20" pluginspage="http://www.macromedia.com/go/getflashplayer" type="application/x-shockwave-flash" FlashVars="\'+mp3+\'"></embed>\');
		}
		
	});
});
</script>';

		return $soundSelect;
	}
}

?>