<?php
defined('IN_MOBIQUO') or exit;

require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
require('include/mobi_ucp_profile.php');
$user->session_begin();
$auth->acl($user->data);
$user->setup('ucp');
$profile = new mobi_ucp_profile();
$profile->main();
$result = $profile->result;
$result_text = $profile->result_text;