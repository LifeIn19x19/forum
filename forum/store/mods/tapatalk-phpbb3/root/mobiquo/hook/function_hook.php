<?php
function get_tapatlk_location()
{
	global $user,$phpbb_root_path;
	$location = $user->extract_current_page($phpbb_root_path);
	$param_arr = array();
	switch ($location['page_name'])
    {
        case "viewforum.php":
        	if(!empty($_GET['f']))
        	{
        		$param_arr['fid'] = $_GET['f'];
        	} 
            $param_arr['location'] = 'forum';
            break;
        case "index.php":
        case '':
            $param_arr['location'] = 'index';
            break;
        case "ucp.php":
            if(!empty($_GET['i']) && ($_GET['i'] == "pm"))
            {
                $param_arr['location'] = 'message';
                if(!empty($_GET['p']))
                $param_arr['mid'] = $_GET['p'];
            }
            if(!empty($_GET['mode']) && ($_GET['mode'] == 'login'))
            {
            	$param_arr['location'] = 'login';
            }
            break;
        case "search.php":
            $param_arr['location'] = "search";
            break;
        case "viewtopic.php":
            if(!empty($_GET['t']))
            {               
                //$param_arr['fid'] = $parameters['fid'];
                $param_arr['location'] = 'topic';
                $param_arr['tid'] = $_GET['t'];
            }
            break;
        case "memberlist.php":
           	if(!empty($_GET['mode']) && $_GET['mode'] == "viewprofile" && !empty($_GET['u']))
            {
                $param_arr['location'] = 'profile';
                $param_arr['uid'] = $_GET['u'];
            }

            break;
        case "viewonline.php":
            $param_arr['location'] = 'online';
            break;
        default:
            $param_arr['location'] = 'index';
            break;
    }
    $queryString = http_build_query($param_arr);
    $url = generate_board_url() . '/?' .$queryString;
    $url = preg_replace('/^(http|https)/isU', 'tapatalk', $url);
    return $url;
}

function tapatalk_online_display($tapatalk_method)
{
	global $on_page,$row,$tapatalk_params,$db;
	switch ($tapatalk_method)
	{
		case 'get_config':
		case 'get_forum':
		case 'get_participated_forum':
		case 'login_forum':
		case 'get_forum_status':
		case 'get_topic':
			$on_page[1] = 'viewforum';
			if(!empty($tapatalk_params[0]))
			{
				$row['session_forum_id'] = $tapatalk_params[0];
			}	                
			break;
		case 'get_user_info':
			$on_page[1] = 'memberlist';
			$row['session_page'] = 'mode=viewprofile';
			break;
		case 'register':
			$on_page[1] = 'ucp';
			$row['session_page'] = 'mode=register';
			break;
		case 'get_online_users':
			$on_page[1] = 'viewonline';
			break;
		case 'get_user_topic':
		case 'get_user_reply_post':
			$on_page[1] = 'viewtopic';
			break;
		case 'new_topic':
			$on_page[1] = 'posting';
			break;
		case 'search':
		case 'search_topic':
		case 'search_post':
		case 'get_unread_topic':
		case 'get_participated_topic':
		case 'get_latest_topic':
			$on_page[1] = 'search';
			break;
		case 'get_quote_post':
			$on_page[1] = 'posting';
			$row['session_page'] = 'mode=quote';
			if(!empty($tapatalk_params[0]))
			{
				$tapatalk_params[0] = intval($tapatalk_params[0]);
				$sql = 'SELECT post_id, topic_id, forum_id
				FROM ' . POSTS_TABLE . '
				WHERE post_id = ' . $tapatalk_params[0];
				$result = $db->sql_query($sql);
				$post_row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if(!empty($post_row['forum_id']))
				$row['session_forum_id'] = $post_row['forum_id'];
			}
			break;
		case 'reply_post':
			if(!empty($tapatalk_params[0]))
			{
				$row['session_forum_id'] = $tapatalk_params[0];
			}
			$on_page[1] = 'posting';
			$row['session_page'] = 'mode=reply';
			break;
		case 'get_thread':
			$on_page[1] = 'viewtopic';
			if(!empty($tapatalk_params[0]))
			{
				$tapatalk_params[0] = intval($tapatalk_params[0]);
				$sql = 'SELECT forum_id
					FROM ' . TOPICS_TABLE . '
					WHERE topic_id = ' . $tapatalk_params[0];
				$result = $db->sql_query($sql);
				$topic_row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if(!empty($topic_row['forum_id']))
				$row['session_forum_id'] = $topic_row['forum_id'];
			}
			break;
		case 'get_thread_by_post':
			$on_page[1] = 'viewtopic';
			if(!empty($tapatalk_params[0]))
			{
				$tapatalk_params[0] = intval($tapatalk_params[0]);
				$sql = 'SELECT post_id, topic_id, forum_id
				FROM ' . POSTS_TABLE . '
				WHERE post_id = ' . $tapatalk_params[0];
				$result = $db->sql_query($sql);
				$post_row = $db->sql_fetchrow($result);
				$db->sql_freeresult($result);
				if(!empty($post_row['forum_id']))
				$row['session_forum_id'] = $post_row['forum_id'];
			}
			break;
		case 'create_message':
		case 'get_box_info':
		case 'get_box':
		case 'get_quote_pm':
		case 'delete_message':
		case 'mark_pm_unread':
		case 'get_message':
			$on_page[1] = 'ucp';
			$row['session_page'] = 'i=pm&';
			break;
		default:
			if(strpos($tapatalk_method, 'm_') === 0)
			{
				$on_page[1] = 'mcp';
			}
			else 
			{
				$on_page[1] = 'index';
			}
			break;	            
	}    		
}