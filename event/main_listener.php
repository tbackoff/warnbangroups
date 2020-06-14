<?php
/**
 *
 * Warn Ban Groups extension for the phpBB Forum Software package
 *
 * @copyright (c) 2020, Tabitha Backoff
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 */

namespace tbackoff\warnbangroups\event;

/**
 * @ignore
 */
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Warn Ban Groups event listener
 */
class main_listener implements EventSubscriberInterface
{
	/** @var \phpbb\auth\auth */
	protected $auth;

	/** @var \phpbb\db\driver\driver_interface */
	protected $db;

	/** @var \phpbb\language\language */
	protected $language;

	/** @var \phpbb\request\request */
	protected $request;

	/** @var \phpbb\template\template */
	protected $template;

	/** @var \phpbb\user */
	protected $user;

	/** @var */
	private $user_id = 0;

	/**
	 * Constructor
	 *
	 * @param \phpbb\auth\auth                   $auth
	 * @param \phpbb\db\driver\driver_interface  $db
	 * @param \phpbb\language\language           $language
	 * @param \phpbb\request\request             $request
	 * @param \phpbb\template\template           $template
	 * @param \phpbb\user                        $user
	 */
	public function __construct(\phpbb\auth\auth $auth, \phpbb\db\driver\driver_interface $db, \phpbb\language\language $language, \phpbb\request\request $request, \phpbb\template\template $template, $user)
	{
		$this->auth = $auth;
		$this->db = $db;
		$this->language = $language;//
		$this->request = $request;
		$this->template = $template;
		$this->user = $user;
	}

	public static function getSubscribedEvents()
	{
		return [
			'core.acp_ban_before'					=> 'user_ban_before',
			'core.acp_manage_group_request_data'	=> 'acp_manage_group_request_data',
			'core.acp_manage_group_initialise_data'	=> 'acp_manage_group_initialise_data',
			'core.acp_manage_group_display_form'	=> 'acp_manage_group_display_form',

			'core.mcp_ban_before'								=> 'user_ban_before',
			'core.mcp_warn_post_before'							=> 'mcp_warn_before',
			'core.mcp_warn_user_before'							=> 'mcp_warn_before',
			'core.memberlist_modify_view_profile_template_vars'	=> 'memberlist_modify_view_profile_template_vars',
			'core.memberlist_view_profile'						=> 'memberlist_view_profile',

			'core.user_setup'	=> 'load_language_on_setup',

			'core.viewtopic_modify_post_row'	=> 'viewtopic_modify_post_row',
		];
	}

	public function acp_manage_group_request_data($event)
	{
		$submit_ary = $event['submit_ary'];
		$submit_ary['ban'] = $this->request->variable('group_ban', 0);
		$submit_ary['warn'] = $this->request->variable('group_warn', 0);
		$event['submit_ary'] = $submit_ary;
	}

	public function acp_manage_group_initialise_data($event)
	{
		$test_variables = $event['test_variables'];
		$test_variables['ban'] = 'int';
		$test_variables['warn'] = 'int';
		$event['test_variables'] = $test_variables;
	}

	public function acp_manage_group_display_form($event)
	{
		$group_row = $event['group_row'];

		$this->template->assign_vars([
			'GROUP_BAN'		=> (isset($group_row['group_ban']) && $group_row['group_ban']) ? ' checked="checked"' : '',
			'GROUP_WARN'	=> (isset($group_row['group_warn']) && $group_row['group_warn']) ? ' checked="checked"' : '',
		]);
	}

	public function load_language_on_setup($event)
	{
		$lang_set_ext = $event['lang_set_ext'];
		$lang_set_ext[] = [
			'ext_name' => 'tbackoff/warnbangroups',
			'lang_set' => 'common',
		];
		$event['lang_set_ext'] = $lang_set_ext;
	}

	public function user_ban_before($event)
	{
		$ban = $event['ban'];

		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			$ban_list = (!is_array($ban)) ? array_unique(explode("\n", $ban)) : $ban;
			$banlist_ary = [];

			if ($event['mode'] == 'user')
			{
				$sql_usernames = [];

				foreach ($ban_list as $username)
				{
					$username = trim($username);
					if ($username != '')
					{
						$clean_name = utf8_clean_string($username);
						$sql_usernames[] = $clean_name;
					}
				}

				// Make sure we have been given someone to ban
				if (!count($sql_usernames))
				{
					trigger_error('NO_USER_SPECIFIED');
				}

				$sql = 'SELECT user_id
					FROM ' . USERS_TABLE . '
					WHERE ' . $this->db->sql_in_set('username_clean', $sql_usernames);
				$result = $this->db->sql_query($sql);
				while ($row = $this->db->sql_fetchrow($result))
				{
					$banlist_ary[] = (int) $row['user_id'];
				}
				$this->db->sql_freeresult($result);

				$no_ban = $this->build_ban();

				foreach ($banlist_ary as $user_id)
				{
					if (in_array((int) $user_id, $no_ban))
					{
						trigger_error($this->language->lang('NO_BAN'));
					}
				}
			}
		}
	}

	public function mcp_warn_before($event)
	{
		$user_row = $event['user_row'];

		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			$warn = $this->build_warn();

			if (in_array((int) $user_row['user_id'], $warn))
			{
				$event['s_mcp_warn_post'] = false;

				trigger_error($this->language->lang('NO_WARN'));
			}
		}
	}

	public function memberlist_view_profile($event)
	{
		$warn_user_enabled = $event['warn_user_enabled'];
		$member = $event['member'];

		$this->user_id = (int) $member['user_id'];

		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			$warn = $this->build_warn();

			if (in_array((int) $member['user_id'], $warn))
			{
				$warn_user_enabled = false;
			}

			$event['warn_user_enabled'] = $warn_user_enabled;
		}
	}

	public function memberlist_modify_view_profile_template_vars($event)
	{
		$template_ary = $event['template_ary'];

		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			$ban = $this->build_ban();
			if (in_array((int) $this->user_id, $ban))
			{
				$template_ary['U_USER_BAN'] = '';
			}

			$event['template_ary'] = $template_ary;
		}
	}

	public function viewtopic_modify_post_row($event)
	{
		$row = $event['row'];
		$post_row = $event['post_row'];
		$topic_data = $event['topic_data'];

		if ($this->user->data['user_type'] != USER_FOUNDER)
		{
			$warn = $this->build_warn();
			$post_row['U_WARN'] = ($this->auth->acl_get('m_warn') && $event['poster_id'] != $this->user->data['user_id'] && $event['poster_id'] != ANONYMOUS && !in_array($event['poster_id'], $warn)) ? append_sid("{$phpbb_root_path}mcp.$phpEx", 'i=warn&amp;mode=warn_post&amp;f=' . $topic_data['forum_id'] . '&amp;p=' . $row['post_id'], true, $this->user->session_id) : '';

			$event['post_row'] = $post_row;
		}
	}

	private function build_ban()
	{
		$sql_ary = [
			'SELECT'	=> 'g.group_id, g.group_ban, ug.user_id, ug.group_id',

			'FROM'		=> [
				GROUPS_TABLE	=> 'g',
			],

			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [
						USER_GROUP_TABLE	=> 'ug'
					],
					'ON'	=> 'g.group_id = ug.group_id'
				],
			],

			'WHERE'		=> 'g.group_ban = 0'
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$ban = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$ban[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		return $ban;
	}

	private function build_warn()
	{
		$sql_ary = [
			'SELECT'	=> 'g.group_id, g.group_warn, ug.user_id, ug.group_id',

			'FROM'		=> [
				GROUPS_TABLE	=> 'g',
			],

			'LEFT_JOIN'	=> [
				[
					'FROM'	=> [
						USER_GROUP_TABLE	=> 'ug'
					],
					'ON'	=> 'g.group_id = ug.group_id'
				],
			],

			'WHERE'		=> 'g.group_warn = 0'
		];
		$sql = $this->db->sql_build_query('SELECT', $sql_ary);
		$result = $this->db->sql_query($sql);
		$warn = [];
		while ($row = $this->db->sql_fetchrow($result))
		{
			$warn[] = $row['user_id'];
		}
		$this->db->sql_freeresult($result);

		return $warn;
	}
}
