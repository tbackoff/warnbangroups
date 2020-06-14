<?php
/**
 *
 * Warn Ban Groups extension for the phpBB Forum Software package
 *
 * @copyright (c) 2020, Tabitha Backoff
 * @license GNU General Public License, version 2 (GPL-2.0)
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
	'GROUP_BAN'				=> 'Group can be banned',
	'GROUP_BAN_EXPLAIN'		=> 'Members in this group can be banned. Founders will be able to ban members in this group regardless of setting.',
	'GROUP_WARN'			=> 'Group can be warned',
	'GROUP_WARN_EXPLAIN'	=> 'Members in this group can be warned. Founders will be able to warn members in this group regardless of setting.',

	'NO_BAN'	=> 'One or more users in the ban list is in a group that cannot be banned. Please check the list and try again.',
));
