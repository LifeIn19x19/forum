<?php
/**
*
* @copyright (c) 2009, 2010, 2011 Quoord Systems Limited
* @license http://opensource.org/licenses/gpl-2.0.php GNU Public License (GPLv2)
*
*/

defined('IN_MOBIQUO') or exit;

function get_forum_func($xmlrpc_params)
{
    global $db, $auth, $user, $config, $mobiquo_config, $phpbb_home;
    
    $params = php_xmlrpc_decode($xmlrpc_params);
    
    $return_description = isset($params[0]) ? $params[0] : false;
    
    if (isset($params[1]))
    {
        $fid = intval($params[1]);
        $forum_filter = " WHERE f.parent_id = '$fid'";
        $root_forum_id = $fid;
    }
    else
    {
        $forum_filter = '';
        $root_forum_id = 0;
    }
    
    $user_watch_array = array();
    if($user->data['is_registered'])
    {
        $sql = "SELECT notify_status,forum_id FROM " . FORUMS_WATCH_TABLE . " WHERE user_id = '".$user->data['user_id']."'";
        $result_watch = $db->sql_query($sql);
        while($row_watch = $db->sql_fetchrow($result_watch))
        {
            if(isset($row_watch['notify_status']) && !is_null($row_watch['notify_status']) && $row_watch['notify_status'] !== '')
            {
                $user_watch_array[] = $row_watch['forum_id'];
            }
        }
    }
    
    $forum_rows = array();
    $forum_rows[$root_forum_id] = array('forum_id' => $root_forum_id, 'parent_id' => -1, 'child' => array());
    $forum_hide_forum_arr = !empty($mobiquo_config['hide_forum_id']) ? $mobiquo_config['hide_forum_id'] : array();
    $sql = 'SELECT f.*  FROM ' . FORUMS_TABLE . ' f ' . $forum_filter . '
            ORDER BY f.left_id ASC';
    $result = $db->sql_query($sql, 600);
    while ($row = $db->sql_fetchrow($result))
    {
        $forum_id = $row['forum_id'];
        if ($row['forum_type'] == FORUM_CAT && ($row['left_id'] + 1 == $row['right_id']))
        {
            // Non-postable forum with no subforums, don't display
            continue;
        }
        if(in_array($row['forum_id'], $forum_hide_forum_arr))
        {
            continue;
        }
        elseif(in_array($row['parent_id'], $forum_hide_forum_arr))
        {
            array_push($forum_hide_forum_arr, $row['forum_id']);
            continue;
        }
        // Skip branch
        if (isset($right_id))
        {
            if ($row['left_id'] < $right_id)
            {
                continue;
            }
            unset($right_id);
        }

        if (!$auth->acl_get('f_list', $forum_id) || (isset($mobiquo_config['hide_forum_id']) && in_array($forum_id, $mobiquo_config['hide_forum_id'])))
        {
            // if the user does not have permissions to list this forum, skip everything until next branch
            $right_id = $row['right_id'];
            continue;
        }
        
        $row['unread_count'] = 0;
        
        if ($user->data['is_registered'] && ($config['email_enable'] || $config['jab_enable']) && $config['allow_forum_notify'] && $row['forum_type'] == FORUM_POST && $auth->acl_get('f_subscribe', $forum_id))
        {
            $row['can_subscribe'] = true;
            $row['is_subscribed'] = in_array($row['forum_id'], $user_watch_array) ? true : false;
        } else {
            $row['can_subscribe'] = false;
            $row['is_subscribed'] = false;
        }
        
        $forum_rows[$forum_id] = $row;
    }
    $db->sql_freeresult($result);
    $fids = array(-1);
    foreach($forum_rows as $id => $value)
    {
        if (!in_array($value['parent_id'], $fids))
            unset($forum_rows[$id]);
        else if ($value['left_id'] > $value['right_id'])
            unset($forum_rows[$id]);
        else
            $fids[] = $id;
    }
    
    while(empty($forum_rows[$root_forum_id]['child']) && count($forum_rows) > 1)
    {   
        $current_parent_id = -1;
        $leaves_forum = array();
        foreach($forum_rows as $row)
        {
            $row_parent_id = $row['parent_id'];
            if ($row_parent_id != $current_parent_id)
            {
                if(isset($leaves_forum[$row_parent_id]))
                {
                    $leaves_forum[$row_parent_id] = array();
                }
                else
                {
                    if(isset($leaves_forum[$forum_rows[$row_parent_id]['parent_id']]))
                    {
                        $leaves_forum[$forum_rows[$row_parent_id]['parent_id']] = array();
                    }
                    $leaves_forum[$row_parent_id][] = $row['forum_id'];
                }
                $current_parent_id = $row_parent_id;
            }
            else if ($row_parent_id == $current_parent_id)
            {
                if(!empty($leaves_forum[$row_parent_id]))
                {
                    $leaves_forum[$row_parent_id][] = $row['forum_id'];
                }
            }
        }
        
        foreach($leaves_forum as $node_forum_id => $leaves)
        {
            foreach($leaves as $forum_id)
            {
                $forum =& $forum_rows[$forum_id];
                if (function_exists('get_unread_topics'))
                    $unread_count = count(get_unread_topics(false, "AND t.forum_id = $forum_id"));
                else
                    $unread_count = count(tt_get_unread_topics(false, "AND t.forum_id = $forum_id"));
                
                $forum['unread_count'] += $unread_count;
                if ($forum['unread_count'])
                {
                    $forum_rows[$forum['parent_id']]['unread_count'] += $forum['unread_count'];
                }
                
                $forum_type = $forum['forum_link'] ? 'link' : ($forum['forum_type'] != FORUM_POST ? 'category' : 'forum');
                
                if ($logo_icon_name = tp_get_forum_icon($forum_id, $forum_type, $forum['forum_status'], $forum['unread_count']))
                    $logo_url = $phpbb_home.$config['tapatalkdir'] .'/forum_icons/'.$logo_icon_name;
                else if ($forum['forum_image'])
                {
                    if (preg_match('#^https?://#i', $forum['forum_image']))
                        $logo_url = $forum['forum_image'];
                    else
                        $logo_url = $phpbb_home.$forum['forum_image'];
                }
                else
                    $logo_url = '';
                
                
                $xmlrpc_forum = array(
                    'forum_id'      => new xmlrpcval($forum_id),
                    'forum_name'    => new xmlrpcval(basic_clean($forum['forum_name']), 'base64'),
                    'parent_id'     => new xmlrpcval($node_forum_id),
                    'logo_url'      => new xmlrpcval($logo_url),
                    'url'           => new xmlrpcval($forum['forum_link']),
                );
                
                if ($forum['unread_count'])     $xmlrpc_forum['unread_count']       = new xmlrpcval($forum['unread_count'], 'int');
                if ($forum['unread_count'])     $xmlrpc_forum['new_post']           = new xmlrpcval(true, 'boolean');
                if ($forum['forum_password'])   $xmlrpc_forum['is_protected']       = new xmlrpcval(true, 'boolean');
                if (!empty($forum['can_subscribe']))    $xmlrpc_forum['can_subscribe']      = new xmlrpcval(true, 'boolean');
                else $xmlrpc_forum['can_subscribe']      = new xmlrpcval(false, 'boolean');
                if (!empty($forum['is_subscribed']))    $xmlrpc_forum['is_subscribed']      = new xmlrpcval(true, 'boolean');
                else $xmlrpc_forum['is_subscribed']      = new xmlrpcval(false, 'boolean');
                if ($forum['forum_type'] != FORUM_POST) $xmlrpc_forum['sub_only']   = new xmlrpcval(true, 'boolean');
                
                if ($return_description)
                {
                    $description = smiley_text($forum['forum_desc'], true);
                    $description = generate_text_for_display($description, $forum['forum_desc_uid'], $forum['forum_desc_bitfield'], $forum['forum_desc_options']);
                    $description = preg_replace('/<br *?\/?>/i', "\n", $description);
                    $xmlrpc_forum['description'] = new xmlrpcval(basic_clean($description), 'base64');
                }
                
                if (isset($forum['child'])) {
                    $xmlrpc_forum['child'] = new xmlrpcval($forum['child'], 'array');
                }
                
                $forum_rows[$node_forum_id]['child'][] = new xmlrpcval($xmlrpc_forum, 'struct');
                unset($forum_rows[$forum_id]);
            }
        }
    }
 
    $response = new xmlrpcval($forum_rows[$root_forum_id]['child'], 'array');
    
    return new xmlrpcresp($response);
} // End of get_forum_func
