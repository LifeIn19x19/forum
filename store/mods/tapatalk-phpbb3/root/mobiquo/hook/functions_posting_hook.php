<?php
    if(!isset($tapatalk_push_run)) $tapatalk_push_run = true;
	if ($url && $post_approval && $tapatalk_push_run)
    {
    	$config['tapatalkdir'] = !empty($config['tapatalkdir']) ? $config['tapatalkdir'] : 'mobiquo';
        require_once($phpbb_root_path . $config['tapatalkdir'].'/push_hook.' . $phpEx);
        $user_name_tag_arr = tt_get_tag_list($data['message']);
        switch ($mode)
        {
        	case 'reply':
        		tapatalk_push_reply($data);
        		tapatalk_push_quote($data,$user_name_tag_arr,'tag');
        		break;
        	case 'post':
        		tapatalk_push_newtopic($data);
        		tapatalk_push_quote($data,$user_name_tag_arr,'tag');
        		break;
        	case 'quote':
        		preg_match_all('/quote=&quot;(.*?)&quot;/is', $data['message'],$matches);
        		$user_name_arr = array_unique($matches[1]);
        		unset($matches);		        		
        		tapatalk_push_reply($data);
        		tapatalk_push_quote($data,$user_name_arr,'quote');
        		tapatalk_push_quote($data,$user_name_tag_arr,'tag');
        		break;
        }		   
    }
    $tapatalk_push_run = false;