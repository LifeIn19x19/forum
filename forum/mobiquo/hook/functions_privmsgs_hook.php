<?php
	if(!isset($tapatalk_push_run)) $tapatalk_push_run = true;
	if(!empty($data['msg_id']) && ($mode != 'edit') && $tapatalk_push_run)
	{
		$config['tapatalkdir'] = !empty($config['tapatalkdir']) ? $config['tapatalkdir'] : 'mobiquo';
		require_once($phpbb_root_path . $config['tapatalkdir'].'/push_hook.' . $phpEx);
    	tapatalk_push_pm($user_id, $data['msg_id'], $subject);
	}
	$tapatalk_push_run = false;