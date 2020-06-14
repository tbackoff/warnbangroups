<?php
/**
 *
 * Warn Ban Groups extension for the phpBB Forum Software package
 *
 * @copyright (c) 2020, Tabitha Backoff
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tbackoff\warnbangroups\migrations\v10x;

class install_schema extends \phpbb\db\migration\migration
{
	public static function depends_on()
	{
		return ['\phpbb\db\migration\data\v330\v330'];
	}

	/**
	 * Update database schema
	 */
	public function update_schema()
	{
		return [
			'add_columns'	=> [
				$this->table_prefix . 'groups'	=> [
					'group_ban'		=> ['BOOL', 0],
					'group_warn'	=> ['BOOL', 0],
				],
			],
		];
	}

	/**
	 * Revert database schema changes
	 */
	public function revert_schema()
	{
		return [
			'drop_columns'	=> [
				$this->table_prefix . 'groups'	=> [
					'group_ban',
					'group_warn',
				],
			],
		];
	}
}
