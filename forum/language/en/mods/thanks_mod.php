<?php
/**
*
* thanks_mod[English]
*
* @package language
* @version $Id: thanks.php,v 125 2009-12-01 10:02:51Палыч $
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

/**
* DO NOT CHANGE
*/
if (!defined('IN_PHPBB'))
{
   exit;
}

if (empty($lang) || !is_array($lang))
{
	$lang = array();
}

// DEVELOPERS PLEASE NOTE
//
// All language files should use UTF-8 as their encoding and the files must not contain a BOM.
//
// Placeholders can now contain order information, e.g. instead of
// 'Page %s of %s' you can (and should) write 'Page %1$s of %2$s', this allows
// translators to re-order the output of data while ensuring it remains correct
//
// You do not need this where single placeholders are used, e.g. 'Message %d' is fine
// equally where a string contains only two placeholders which are used to wrap text
// in a url you again do not need to specify an order e.g., 'Click %sHERE%s' is fine
//
// Some characters you may want to copy&paste:
// ’ » “ ” …
//

$lang = array_merge($lang, array(
	'REMOVE_THANKS'				=> 'To unlike: ',
	'THANK_POST'				=> 'Like this post by ',
	'THANK_FROM'				=> 'from',
	'THANK_TEXT_1'				=> 'This post by ',
	'THANK_TEXT_2'				=> ' was liked by',
	'THANK_TEXT_2pl'			=> ' was liked by %d people',
	'RECEIVED'					=> 'Was liked',
	'THANKS'					=> '',
	'GIVEN'						=> 'Liked others',
	'GRATITUDES'				=> 'Gratitudes',
	'FOR_MESSAGE'				=> ' for post',
	'THANKS_LIST'				=> 'View/Close list',
	'THANKS_PM_SUBJECT_GIVE'	=> 'Thanks for the message',
	'THANKS_PM_SUBJECT_GIVE_EXPLAIN' => '<strong>CAUTION!<br />It is strong recommended to run this installation only after following the instructions on changes to the code files conference (or perform the installation using AutoMod)!<br />Also strongly recommended select Yes to Display Full Results (below)!</strong>',
	'THANKS_PM_SUBJECT_REMOVE'	=> 'Unlike',
	'THANKS_PM_MES_GIVE'		=> 'Thanks for the message',
	'THANKS_PM_MES_REMOVE'		=> 'Unlike',
	'THANKS_INFO_GIVE'			=> 'You have just liked this post.',
	'THANKS_INFO_REMOVE'		=> 'You have just unliked this post.',
	'RETURN_POST'				=> 'Return',
	'THANKS_USER'				=> 'List of likes',
	'THANKS_BACK'				=> 'Return',
	'JUMP_TO_FORUM'				=> 'Jump to forum',
	'JUMP_TO_TOPIC'				=> 'Jump to topic',
	'INCORRECT_THANKS'			=> 'Invalid like',
	'REMOVE_THANKS_CONFIRM'		=> 'Are you sure you want to remove your like?',
	'NO_VIEW_USERS_THANKS'		=> 'You are not authorized to view the likes list.',
// Install block
	'THANKS_INSTALLED'			=> 'You have successfully <strong>installed</strong> Thanks MOD.',
	'NO_FILES_MODIFIED'			=> 'You have not modified files in according to Thanks MOD installation instruction.',
	'NOT_INSTALLED'				=> 'You have not Thanks MOD installed.',
));
?>