<?php
defined('IN_MOBIQUO') or exit;
require($phpbb_root_path . 'includes/functions_user.' . $phpEx);
require('include/mobi_ucp_remind.php');
$user->session_begin();
$auth->acl($user->data);
$user->setup('ucp');
$remind = new mobi_ucp_remind();
$remind->main();
$result = $remind->result;
$result_text = $remind->result_text;
$verified = $remind->verify;