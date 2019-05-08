<?php
	$user->add_lang('mods/info_acp_mobiquo');
	if(file_exists($phpbb_root_path.$tapatalk_dir.'/hook/function_hook.php'))
	{
		require_once $phpbb_root_path.$tapatalk_dir.'/hook/function_hook.php';
		$tapatalk_location_url = get_tapatlk_location();
	}
	else 
	{
		$tapatalk_location_url = '';
	}
	
	if(file_exists($phpbb_root_path.$tapatalk_dir . '/smartbanner/head.inc.php'))
	{
		$api_key = isset($config['tapatalk_push_key']) ? $config['tapatalk_push_key'] : '';
    	$app_ads_enable = isset($config['tapatalk_app_ads_enable']) ? $config['tapatalk_app_ads_enable'] : 1;
    	
		$app_forum_name = $config['sitename'];
	    $tapatalk_dir_url = $board_url . $tapatalk_dir;
	    $is_mobile_skin = 0;
	    $app_location_url = $tapatalk_location_url;
	    
	    $app_banner_message = isset($config['tapatalk_app_banner_msg']) ? $config['tapatalk_app_banner_msg'] : '';
	    $app_ios_id = isset($config['tapatalk_app_ios_id']) ? $config['tapatalk_app_ios_id'] : '';
	    $app_android_id = isset($config['tapatalk_android_url']) ? $config['tapatalk_android_url'] : '';
	    $app_kindle_url = isset($config['tapatalk_kindle_url']) ? $config['tapatalk_kindle_url'] : '';
	    
		include $phpbb_root_path.$tapatalk_dir . '/smartbanner/head.inc.php';
	}
	else 
	{
		$app_head_include = '';
	}