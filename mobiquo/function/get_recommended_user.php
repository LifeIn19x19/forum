<?php
/**
*
* @copyright (c) 2009, 2010, 2011 Quoord Systems Limited
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License (GPLv2)
*
*/

defined('IN_MOBIQUO') or exit;

function get_recommended_user_func()
{
	global $db, $auth, $user, $config, $phpbb_home,$table_prefix,$tapatalk_users;
	$tapatalk_users = array();
	$users = array();
	
	//get tapatalk users
	$sql = 	"SELECT userid AS uid FROM " . $table_prefix . "tapatalk_users";
	$query = $db->sql_query($sql);
	while ($row =  $db->sql_fetchrow($query))
	{
		$tapatalk_users[] = $row['uid'];
	}
	
	//get pm users
	$sql = "SELECT user_id AS uid FROM " . PRIVMSGS_TO_TABLE . " WHERE author_id = '" . $user->data['user_id'] . "'";
	$query = $db->sql_query($sql);
	while ($row =  $db->sql_fetchrow($query))
	{
		if(isset($users[$row['uid']]))
			$users[$row['uid']] = $users[$row['uid']] + 10;
		else 
			$users[$row['uid']] = 10;
	}
	
	//get pm to me users 
    $sql = "SELECT author_id AS uid FROM " . PRIVMSGS_TO_TABLE . " WHERE user_id = '" . $user->data['user_id'] . "'";
	$query = $db->sql_query($sql);
	while ($row =  $db->sql_fetchrow($query))
	{
		if(isset($users[$row['uid']]))
			$users[$row['uid']] = $users[$row['uid']] + 10;
		else 
			$users[$row['uid']] = 10;
	}
	
	//get sub users 
	$sql = "SELECT tw.user_id AS uid FROM " . TOPICS_WATCH_TABLE . " AS tw 
	LEFT JOIN " . TOPICS_TABLE . " AS t ON tw.topic_id=t.topic_id 
	WHERE t.topic_poster = '" . $user->data['user_id'] . "'";
	$query = $db->sql_query($sql);
	while ($row =  $db->sql_fetchrow($query))
	{
		if(isset($users[$row['uid']]))
			$users[$row['uid']] = $users[$row['uid']] + 5;
		else 
			$users[$row['uid']] = 5;
	}
	
	//get me sub users 
	$sql = "SELECT t.topic_poster AS uid FROM " . TOPICS_WATCH_TABLE . " AS tw 
	RIGHT JOIN " . TOPICS_TABLE . " AS t ON tw.topic_id=t.topic_id 
	WHERE tw.user_id = '" . $user->data['user_id'] . "'";
	$query = $db->sql_query($sql);
	while ($row =  $db->sql_fetchrow($query))
	{
		if(isset($users[$row['uid']]))
			$users[$row['uid']] = $users[$row['uid']] + 5;
		else 
			$users[$row['uid']] = 5;
	}
	
	arsort($users);	

	foreach ($users as $key =>$row)
	{
		//non tapatalk users
		if(isset($_POST['mode']) && $_POST['mode'] == 2 && in_array($key, $tapatalk_users))
		{
			unset($users[$key]);
		}
		if($key == $user->data['user_id'])	
		{
			unset($users[$key]);
		}
		if($key == 1)
		{
			unset($users[$key]);
		}
	}

	$page =  intval($_POST['page']);
    $perpage = intval($_POST['perpage']);
    $start = ($page-1) * $perpage;
	$total = count($users);
	$users_keys = array_keys($users);
	$users_slice = array_slice($users_keys, $start,$perpage);
	$id_str = implode(',', $users_slice);
	$return_user_lists = array();
	if(!empty($id_str))
	{
		$sql = "SELECT * FROM " . USERS_TABLE . " WHERE user_id in (" . $id_str . ")";
		$query = $db->sql_query($sql);
		while ($row =  $db->sql_fetchrow($query))
		{		
	        $return_user_lists[] = new xmlrpcval(array(
	            'username'      => new xmlrpcval(basic_clean($row['username']), 'base64'),
	            'user_id'       => new xmlrpcval($row['user_id'], 'string'),
	            'icon_url'      => new xmlrpcval(get_user_avatar_url($row['user_avatar'], $row['user_avatar_type']),'string'),
	            'type'          => new xmlrpcval('', 'string'),
	            'enc_email'     => new xmlrpcval(base64_encode(encrypt($row['user_email'], loadAPIKey())), 'string'),
	        ), 'struct');
		}
	}
	$suggested_users = new xmlrpcval(array(
        'total' => new xmlrpcval($total, 'int'),
        'list'         => new xmlrpcval($return_user_lists, 'array'),
    ), 'struct');

    return new xmlrpcresp($suggested_users);
}

function keyED($txt,$encrypt_key)
{
    $encrypt_key = md5($encrypt_key);
    $ctr=0;
    $tmp = "";
    for ($i=0;$i<strlen($txt);$i++)
    {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1);
        $ctr++;
    }
    return $tmp;
}
 
function encrypt($txt,$key)
{
    srand((double)microtime()*1000000);
    $encrypt_key = md5(rand(0,32000));
    $ctr=0;
    $tmp = "";
    for ($i=0;$i<strlen($txt);$i++)
    {
        if ($ctr==strlen($encrypt_key)) $ctr=0;
        $tmp.= substr($encrypt_key,$ctr,1) .
        (substr($txt,$i,1) ^ substr($encrypt_key,$ctr,1));
        $ctr++;
    }
    return keyED($tmp,$key);
}

function loadAPIKey()
{
    global $config;
    $mobi_api_key = isset($config['tapatalk_push_key']) ? $config['tapatalk_push_key'] : '';
    if(empty($mobi_api_key))
    {   
        $boardurl = $mybb->settings['bburl'];
        $boardurl = urlencode($boardurl);
        $response = getContentFromRemoteServer("http://directory.tapatalk.com/au_reg_verify.php?url=$boardurl", 10, $error);
        if($response)
        {
            $result = json_decode($response, true);
            if(isset($result) && isset($result['result']))
            {
                $mobi_api_key = $result['api_key'];
                return $mobi_api_key;
            }
        } 
        return false;    
    }
    return $mobi_api_key;
}
