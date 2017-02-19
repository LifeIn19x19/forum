<?php
/**
 * 
 * push reply
 * @param int $post_id  the current post_id
 * @param array $current_topic_info
 */
function tapatalk_push_reply($data)
{
	global $db, $user, $config,$table_prefix,$phpbb_root_path,$phpEx;
	$is_only_alert = false;
	if(!function_exists('push_table_exists'))
	{
		define('IN_MOBIQUO', 1);
		require_once $phpbb_root_path . $config['tapatalkdir'] . '/xmlrpcresp.' . $phpEx;
	}
	if(!push_table_exists())
		return false;
	if(!(function_exists('curl_init') || ini_get('allow_url_fopen')))
	{
		$is_only_alert = true;
	}
	$return_status = false;
    if (!empty($data))// mobi_table_exists('tapatalk_users')
    {
    	$sql = "SELECT t.userid FROM " . $table_prefix . "tapatalk_users AS t  LEFT JOIN " .TOPICS_WATCH_TABLE . " AS w 
    	ON t.userid = w.user_id
    	WHERE w.topic_id = '".$data['topic_id']."' AND t.subscribe=1";
    	$result = $db->sql_query($sql);
    	while($row = $db->sql_fetchrow($result))
    	{
    		if ($row['userid'] == $user->data['user_id']) continue;
    		define("TAPATALK_PUSH".$row['userid'], 1);
            $return_status = tt_send_push_data($row['userid'], 'sub', $data['topic_id'], $data['post_id'], $data['topic_title'], $user->data['username'],$is_only_alert);
    	}
    }
    return $return_status;
}
/**
 * 
 * push watch forum
 * @param array $current_topic_info
 */
function tapatalk_push_newtopic($data)
{
	global $db, $user, $config,$table_prefix,$phpbb_root_path,$phpEx;
	$return_status = false;
	$is_only_alert = false;
	if(!function_exists('push_table_exists'))
	{
		define('IN_MOBIQUO', 1);
		require_once $phpbb_root_path . $config['tapatalkdir'] . '/xmlrpcresp.' . $phpEx;
	}
	if(!push_table_exists())
		return false;
	if(!(function_exists('curl_init') || ini_get('allow_url_fopen')))
	{
		$is_only_alert = true;
	}	
    if (!empty($data))// mobi_table_exists('tapatalk_users')
    {
    	$sql = "SELECT t.userid FROM " . $table_prefix . "tapatalk_users AS t  LEFT JOIN " .FORUMS_WATCH_TABLE . " AS w 
    	ON t.userid = w.user_id
    	WHERE w.forum_id = '".$data['forum_id']."' AND t.newtopic = 1";
    	$result = $db->sql_query($sql);
    	while($row = $db->sql_fetchrow($result))
    	{
    		if ($row['userid'] == $user->data['user_id']) continue;
    		define("TAPATALK_PUSH".$row['userid'], 1);
            $return_status = tt_send_push_data($row['userid'], 'newtopic', $data['topic_id'], $data['post_id'], $data['topic_title'], $user->data['username'],$is_only_alert);
    	}
    }
    return $return_status;
}
/**
 * 
 * push the private message
 * @param int $userid
 * @param int $pm_id
 * @param string $subject
 */
function tapatalk_push_pm($userid,$pm_id,$subject)
{
    global $db, $user, $config,$table_prefix,$boardurl,$phpbb_root_path,$phpEx;
    $is_only_alert = false;
	if(!function_exists('push_table_exists'))
	{
		define('IN_MOBIQUO', 1);
		require_once $phpbb_root_path . $config['tapatalkdir'] . '/xmlrpcresp.' . $phpEx;
	}
    if(!push_table_exists())
		return false;
	if(!(function_exists('curl_init') || ini_get('allow_url_fopen')))
	{
		$is_only_alert = true;
	}
	$return_status = false;
    if ($userid)
    {         
         $sql = "SELECT userid FROM " . $table_prefix . "tapatalk_users WHERE userid = '".$userid."' and pm =1";
         $result = $db->sql_query($sql);
         $row = $db->sql_fetchrow($result);
         if ($row['userid'] == $user->data['user_id']) return false;
         $db->sql_freeresult($result);
         if(!empty($row))
         {
        	 $return_status = tt_send_push_data($row['userid'], 'pm', $pm_id, '', $subject, $user->data['username'],$is_only_alert);
         }
    }
    return $return_status;     
}
function tapatalk_push_quote($data,$user_name_arr,$type="quote")
{
	global $db, $user, $config,$table_prefix,$phpbb_root_path,$phpEx;
	$return_status = false;
	$is_only_alert = false;
	if(!function_exists('push_table_exists'))
	{
		define('IN_MOBIQUO', 1);
		require_once $phpbb_root_path . $config['tapatalkdir'] . '/xmlrpcresp.' . $phpEx;
	}
	if(!push_table_exists())
		return false;
	if(!(function_exists('curl_init') || ini_get('allow_url_fopen')))
	{
		$is_only_alert = true;
	}
	if(!empty($user_name_arr) && !empty($data))
	{
		foreach ($user_name_arr as $username)
		{			
			$user_id = tt_get_user_id($username);
			if ($user_id == $user->data['user_id']) continue;
			if (empty($user_id)) continue;
			$sql = "SELECT userid FROM " . $table_prefix . "tapatalk_users WHERE userid = '".$user_id."' AND " . $type . " = 1" ;
	        $result = $db->sql_query($sql);
	        $row = $db->sql_fetchrow($result);
	        $db->sql_freeresult($result);
	        if(!empty($row))
	        {
	            $id = empty($data['topic_id']) ? $data['forum_id'] : $data['topic_id'];
	            if(defined("TAPATALK_PUSH".$row['userid']))
	            {
	            	continue;
	            }
	            $return_status = tt_send_push_data($row['userid'], $type, $id, $data['post_id'], $data['topic_title'], $user->data['username'],$is_only_alert);
	            define("TAPATALK_PUSH".$row['userid'], 1);
	        }
			
		}
	}
	return $return_status;
}

function tt_do_post_request($data,$is_test = false)
{
	global $config , $phpbb_root_path ,$cache;
	
	$push_url = 'http://push.tapatalk.com/push.php';
	
	if(!isset($config['tapatalk_push_slug']))
	{
		set_config('tapatalk_push_slug', 0);
	}
	
	//Get push_slug from db
    $push_slug = !empty($config['tapatalk_push_slug'])? $config['tapatalk_push_slug'] : 0;
    $slug = $push_slug;
    $slug = push_slug($slug, 'CHECK');
    $check_res = unserialize($slug);
  
    //If it is valide(result = true) and it is not sticked, we try to send push
    if($check_res[2] && !$check_res[5])
    {
        //Slug is initialed or just be cleared
        if($check_res[8])
        {
            set_config('tapatalk_push_slug',  $slug);
        }
		if(!function_exists("getContentFromRemoteServer"))
		{
			if(!defined("IN_MOBIQUO"))
			{
				define('IN_MOBIQUO', true);
			}			
			if(!isset($config['tapatalkdir']))
			{
				$config['tapatalkdir'] = 'mobiquo';
			}
			require_once $phpbb_root_path.$config['tapatalkdir'].'/mobiquo_common.php';
		}
		if(isset($data['ip']) || isset($data['test']))
		{
			$hold_time = 10;
		}
		else 
		{
			$hold_time = 0;
		}
        //Send push
		$error_msg = '';
        $push_resp = getContentFromRemoteServer($push_url, $hold_time, $error_msg, 'POST', $data);
        if((trim($push_resp) === 'Invalid push notification key') && !$is_test)
        {
        	$push_resp = 1;
        }
        
        if(!is_numeric($push_resp) && !$is_test)
        {
            //Sending push failed, try to update push_slug to db
            $slug = push_slug($slug, 'UPDATE');
            $update_res = unserialize($slug);
            if($update_res[2] && $update_res[8])
            {
                set_config('tapatalk_push_slug', $slug);
            }
        }
        
        return $push_resp;
    }
    return 1;
}

function push_slug($push_v, $method = 'NEW')
{
    if(empty($push_v))
        $push_v = serialize(array());
    $push_v_data = unserialize($push_v);
    $current_time = time();
    if(!is_array($push_v_data))
        return false;
    if($method != 'CHECK' && $method != 'UPDATE' && $method != 'NEW')
        return false;

    if($method != 'NEW' && !empty($push_v_data))
    {
        $push_v_data[8] = $method == 'UPDATE';
        if($push_v_data[5] == 1)
        {
            if($push_v_data[6] + $push_v_data[7] > $current_time)
                return $push_v;
            else
                $method = 'NEW';
        }
    }

    if($method == 'NEW' || empty($push_v_data))
    {
    	/*
    	 * 0=> max_times
    	 * 1=> max_times_in_period
    	 * 2=> result
    	 * 3=> result_text
    	 * 4=> stick_time_queue
    	 * 5=> stick
    	 * 6=> stick_timestamp
    	 * 7=> stick_time
    	 * 8=> save
    	 */ 
        $push_v_data = array();                       //Slug
        $push_v_data[] = 3;                //max push failed attempt times in period
        $push_v_data[] = 300;      //the limitation period
        $push_v_data[] = 1;                   //indicate if the output is valid of not
        $push_v_data[] = '';             //invalid reason
        $push_v_data[] = array();   //failed attempt timestamps
        $push_v_data[] = 0;                    //indicate if push attempt is allowed
        $push_v_data[] = 0;          //when did push be sticked
        $push_v_data[] = 600;             //how long will it be sticked
        $push_v_data[] = 1;                     //indicate if you need to save the slug into db
        return serialize($push_v_data);
    }

    if($method == 'UPDATE')
    {
        $push_v_data[4][] = $current_time;
    }
    $sizeof_queue = count($push_v_data[4]);
    $period_queue = $sizeof_queue > 1 ? ($push_v_data[4][$sizeof_queue - 1] - $push_v_data[4][0]) : 0;
    $times_overflow = $sizeof_queue > $push_v_data[0];
    $period_overflow = $period_queue > $push_v_data[1];

    if($period_overflow)
    {
        if(!array_shift($push_v_data[4]))
            $push_v_data[4] = array();
    }
    
    if($times_overflow && !$period_overflow)
    {
        $push_v_data[5] = 1;
        $push_v_data[6] = $current_time;
    }

    return serialize($push_v_data);
}

function tt_push_clean($str)
{
	global $db;
    $str = strip_tags($str);
    return $str;
}

function tt_get_user_id($username)
{
    global $db;
    
    if (!$username)
    {
        return false;
    }
    
    $username_clean = $db->sql_escape(utf8_clean_string($username));
    
    $sql = 'SELECT user_id
            FROM ' . USERS_TABLE . "
            WHERE username_clean = '$username_clean'";
    $result = $db->sql_query($sql);
    $user_id = $db->sql_fetchfield('user_id');
    $db->sql_freeresult($result);
    
    return $user_id;
}

function tt_get_tag_list($str)
{
    if ( preg_match_all( '/(?<=^@|\s@)(#(.{1,50})#|\S{1,50}(?=[,\.;!\?]|\s|$))/U', $str, $tags ) )
    {
        foreach ($tags[2] as $index => $tag)
        {
            if ($tag) $tags[1][$index] = $tag;
        }
        
        return array_unique($tags[1]);
    }
    
    return array();
}

function tt_insert_push_data($data)
{
	global $table_prefix,$db;	
	if($data['type'] == 'pm')
	{
		$data['subid'] = $data['id'];
	}
	$data['title'] = $db->sql_escape($data['title']);    	
	$data['author'] = $db->sql_escape($data['author']);
	$sql_data[$table_prefix . "tapatalk_push_data"]['sql'] = array(
        'author' => $data['author'],
		'user_id' => $data['userid'],
		'data_type' => $data['type'],
		'title' => $data['title'],
		'data_id' => $data['subid'],
		'create_time' => $data['dateline']		
    );
	if($data['type'] != 'pm')
    {
    	$sql_data[$table_prefix . "tapatalk_push_data"]['sql']['topic_id'] = $data['id'];
    }
    $sql = 'INSERT INTO ' . $table_prefix . "tapatalk_push_data" . ' ' .
    $db->sql_build_array('INSERT', $sql_data[$table_prefix . "tapatalk_push_data"]['sql']);
	$db->sql_query($sql);	
}

function tt_send_push_data($user_id,$type,$id,$sub_id,$title,$author,$is_only_alert=false)
{
	global $config,$db,$user,$phpbb_root_path;
	
	if(!function_exists("tt_get_ignore_users"))
	{
		if(!defined("IN_MOBIQUO"))
		{
			define('IN_MOBIQUO', true);
		}			
		if(!isset($config['tapatalkdir']))
		{
			$config['tapatalkdir'] = 'mobiquo';
		}
		require_once $phpbb_root_path.$config['tapatalkdir'].'/mobiquo_common.php';
	}
	$ignore_users = tt_get_ignore_users($user_id);
	
	if(in_array($user->data['user_id'], $ignore_users))
	{
		return false;
	}
    $boardurl = generate_board_url();
	$ttp_data = array(
                'userid'    => $user_id,
                'type'      => $type,
                'id'        => $id,
                'subid'     => $sub_id,
                'title'     => tt_push_clean($title),
                'author'    => tt_push_clean($author),
                'dateline'  => time(),
    );
    if(push_data_table_exists())
    {
    	tt_insert_push_data($ttp_data);
    }
    if($is_only_alert)
    {
    	return ;
    }
    $ttp_post_data = array(
          'url'  => $boardurl,
          'data' => base64_encode(serialize(array($ttp_data))),
       );
    if(!empty($config['tapatalk_push_key']))
    {
    	$ttp_post_data['key'] = $config['tapatalk_push_key'];
    }
    $return_status = tt_do_post_request($ttp_post_data);
    return $return_status;
}

function tt_get_user_push_type($userid)
{
	global $table_prefix,$db,$phpbb_root_path,$config,$phpEx;
	if(!function_exists('push_table_exists'))
	{
		define('IN_MOBIQUO', 1);
		require_once $phpbb_root_path . $config['tapatalkdir'] . '/xmlrpcresp.' . $phpEx;
	}
	if(!push_table_exists())
	{
		return array();
	}
	$sql = "SELECT pm,subscribe as sub,quote,newtopic,tag FROM " . $table_prefix . "tapatalk_users WHERE userid = '".$userid."'";
    $result = $db->sql_query($sql);
    $row = $db->sql_fetchrow($result);
    return $row;
}
?>