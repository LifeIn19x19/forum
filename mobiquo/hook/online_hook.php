<?php 
		if(isset($on_page[1]) && ($on_page[1] == 'mobiquo/mobiquo'))
    	{
    		$config['tapatalkdir'] = !empty($config['tapatalkdir']) ? $config['tapatalkdir'] : 'mobiquo';
    		$icon_url = $phpbb_root_path.$config['tapatalkdir'].'/images/tapatalk-online.png?new';
    		$icon_byo_url = $phpbb_root_path.$config['tapatalkdir'].'/images/byo-online.png';
    		if(strpos($row['session_browser'],"BYO"))
    		{
    			$username_full = $username_full.'&nbsp;<img src="'.$icon_byo_url.'" title="Own app of this forum" onclick="window.open(\'http://www.tapatalk.com\')"/ style="vertical-align: middle;cursor:pointer;">';
    			$row['is_byo'] = true;
    		}
    		else 
    		{
    			$row['is_tapatalk'] = true;
    			$username_full = $username_full.'&nbsp;<img src="'.$icon_url.'" title="On Tapatalk" onclick="window.open(\'http://www.tapatalk.com\')"/ style="vertical-align: middle;cursor:pointer;">';
    		}	
    		$query_str = parse_url($row['session_page']);
    		$param_arr = explode('&', $query_str['query']);
    		$param_method = explode("=", $param_arr[0]);
    		$param_param = explode('=', $param_arr[1]);
    		$tapatalk_method = $param_method[1];
    		$tapatalk_params = explode('-',$param_param[1]);
    		if(!function_exists("tapatalk_online_display"))
    		{
    			require_once $phpbb_root_path.$config['tapatalkdir'].'/hook/function_hook.php';
    		}
    		tapatalk_online_display($tapatalk_method);
    	}
    	
    	