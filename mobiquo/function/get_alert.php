<?php
defined('IN_MOBIQUO') or exit;
$totalAlert = 0;
$alertData = getAlert();
function getAlert()
{
	global $db,$request_params,$user, $config ,$table_prefix,$totalAlert;
	$push_table = $table_prefix . "tapatalk_push_data";
	$lang = array(
		'reply_to_you' => "%s replied to \"%s\"",
		'quote_to_you' => '%s quoted your post in thread "%s"',
	    'tag_to_you' => '%s mentioned you in thread "%s"',
	    'post_new_topic' => '%s started a new thread "%s"',
	    'like_your_thread' => '%s liked your post in thread "%s"',
		'pm_to_you' => '%s sent you a message "%s"',
	);
	$alertData = array();
	if (!$user->data['is_registered']) trigger_error('No auth to get alert data');
	if(!push_data_table_exists()) trigger_error('Push data table not exist');
	$page = !empty($request_params[0]) ? $request_params[0] : 1;
	$per_page = !empty($request_params[1]) ? $request_params[1] :20;
	$nowtime = time();
    $monthtime = 30*24*60*60;
    $preMonthtime = $nowtime-$monthtime;
    $startNum = ($page-1) * $per_page; 
    $sql = 'DELETE FROM ' . $push_table . ' WHERE create_time < ' . $preMonthtime . ' and user_id = ' . $user->data['user_id'];
    $db->sql_query($sql);
    $sql_select = "SELECT p.* FROM ". $push_table . " p 
    WHERE p.user_id = " . $user->data['user_id'] . "
    ORDER BY create_time DESC LIMIT $startNum,$per_page ";
    $query = $db->sql_query($sql_select);
    
    $total_sql = "SELECT count(*) as total FROM ". $push_table . " p 
    WHERE p.user_id = " . $user->data['user_id'];
    $query_total = $db->sql_query($total_sql);
    $total_data = $db->sql_fetchrow($query_total);
    $totalAlert = $total_data['total'];
    
    $author_arr = array();
    while($data = $db->sql_fetchrow($query))
    {  	
    	if(!isset($author_arr[$data['user_id']]))
    	{
    		$author_clean = $db->sql_escape(utf8_clean_string($data['author']));   		
    		$sql_author = "SELECT u.user_id as author_id, u.user_avatar, u.user_avatar_type FROM " . USERS_TABLE ." AS u WHERE username_clean = '$author_clean'";
    		$query_author = $db->sql_query($sql_author);
    		$author_data = $db->sql_fetchrow($query_author);
    		if(!empty($author_data))
    		{
    			$author_arr[$data['user_id']] = $author_data;
    		}
    		else 
    		{
    			$totalAlert = $totalAlert-1;
    			continue;
    		}
    	}
    	$data = array_merge($author_arr[$data['user_id']],$data);
    	switch ($data['data_type'])
		{
			case 'sub':
				$data['message'] = sprintf($lang['reply_to_you'],$data['author'],$data['title']);
				break;
			case 'tag':
				$data['message'] = sprintf($lang['tag_to_you'],$data['author'],$data['title']);
				break;
			case 'newtopic':
				$data['message'] = sprintf($lang['post_new_topic'],$data['author'],$data['title']);
				break;
			case 'quote':
				$data['message'] = sprintf($lang['quote_to_you'],$data['author'],$data['title']);
				break;
			case 'pm':
			case 'conv':
				$data['message'] = sprintf($lang['pm_to_you'],$data['author'],$data['title']);
				break;
		}
		$data['icon_url'] = get_user_avatar_url($data['user_avatar'], $data['user_avatar_type']);
    	$alertData[] = $data; 
    }
    return $alertData;
}