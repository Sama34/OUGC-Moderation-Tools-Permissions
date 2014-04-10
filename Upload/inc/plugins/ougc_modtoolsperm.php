<?php

/***************************************************************************
 *
 *	OUGC Moderation Tools Permissions plugin (/inc/plugins/ougc_modtoolsperm.php)
 *	Author: Omar Gonzalez
 *	Copyright: © 2012-2014 Omar Gonzalez
 *
 *	Website: http://omarg.me
 *
 *	Allows you to select which groups can use each custom moderator tool.
 *
 ***************************************************************************

****************************************************************************
	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/
 
// Die if IN_MYBB is not defined, for security reasons.
defined('IN_MYBB') or die('This file cannot be accessed directly.');

// Run the ACP hooks.
if(defined('IN_ADMINCP'))
{
	$plugins->add_hook('admin_formcontainer_end', 'ougc_modtoolsperm_container');
	$plugins->add_hook('admin_config_mod_tools_edit_thread_tool_commit', 'ougc_modtoolsperm_modtools_commit');
	$plugins->add_hook('admin_config_mod_tools_add_thread_tool_commit', 'ougc_modtoolsperm_modtools_commit');
	$plugins->add_hook('admin_config_mod_tools_edit_post_tool_commit', 'ougc_modtoolsperm_modtools_commit');
	$plugins->add_hook('admin_config_mod_tools_add_post_tool_commit', 'ougc_modtoolsperm_modtools_commit');
}
else
{
	$plugins->add_hook('moderation_start', 'ougc_modtoolsperm_moderation', -999);
	$plugins->add_hook('forumdisplay_start', 'ougc_modtoolsperm_hide');
	$plugins->add_hook('showthread_start', 'ougc_modtoolsperm_hide');
}

// Plugin API
function ougc_modtoolsperm_info()
{
	global $lang;
	isset($lang->ougc_modtoolsperm) or $lang->load('ougc_modtoolsperm');

	return array(
		'name'			=> 'OUGC Moderation Tools Permissions',
		'description'	=> $lang->ougc_modtoolsperm_d,
		'website'		=> 'http://mods.mybb.com/view/ougc-moderation-tools-permissions',
		'author'		=> 'Omar Gonzalez',
		'authorsite'	=> 'http://omarg.me',
		'version'		=> '1.0',
		'versioncode'	=> 1000,
		'compatibility'	=> '16*',
		'guid'			=> '6e80880bd41907f9513b5545e0c7451d'
	);
}

// _activate() routine
function ougc_modtoolsperm_activate()
{
	global $cache;

	// Insert/update version into cache
	$plugins = $cache->read('ougc_plugins');
	if(!$plugins)
	{
		$plugins = array();
	}

	$info = ougc_modtoolsperm_info();

	if(!isset($plugins['modtoolsperm']))
	{
		$plugins['modtoolsperm'] = $info['versioncode'];
	}

	/*~*~* RUN UPDATES START *~*~*/

	/*~*~* RUN UPDATES END *~*~*/

	$plugins['modtoolsperm'] = $info['versioncode'];
	$cache->update('ougc_plugins', $plugins);
}

// _install() routine
function ougc_modtoolsperm_install()
{
	global $db;

	if(!$db->field_exists('groups', 'modtools'))
	{
		$db->add_column('modtools', 'groups', 'text NOT NULL');
	}
}

// _is_installed() routine
function ougc_modtoolsperm_is_installed()
{
	global $db;

	return (bool)$db->field_exists('groups', 'modtools');
}

// _uninstall() routine
function ougc_modtoolsperm_uninstall()
{
	global $db, $cache;

	if($db->field_exists('groups', 'modtools'))
	{
		$db->drop_column('modtools', 'groups');
	}

	// Delete version from cache
	$plugins = (array)$cache->read('ougc_plugins');

	if(isset($plugins['modtoolsperm']))
	{
		unset($plugins['modtoolsperm']);
	}

	if(!empty($plugins))
	{
		$cache->update('ougc_plugins', $plugins);
	}
	else
	{
		global $db;

		$db->delete_query('datacache', 'title=\'ougc_plugins\'');
		!is_object($cache->handler) or $cache->handler->delete('ougc_plugins');
	}
}

// Moderator Tools page.
function ougc_modtoolsperm_container()
{
	global $run_module, $form_container, $lang;

	if($run_module == 'config' && !empty($form_container->_title) && !empty($lang->general_options) && $form_container->_title == $lang->general_options)
	{
		global $form, $mybb;
		isset($lang->ougc_modtoolsperm) or $lang->load('ougc_modtoolsperm');

		if(!in_array($mybb->input['action'], array('add_thread_tool', 'add_post_tool')))
		{
			global $db;

			$query = $db->simple_select('modtools', 'groups', 'tid=\''.(int)$mybb->input['tid'].'\'');
			$thistool = $db->fetch_array($query);
			if($thistool['groups'])
			{
				$mybb->input['group_1_groups'] = explode(',', $thistool['groups']);
			}
		}

		isset($mybb->input['group_1_groups']) or ($mybb->input['group_1_groups'] = array());
		$group_checked = array('all' => 'checked="checked"', 'select' => '');
		if(!empty($mybb->input['group_1_groups']))
		{
			$group_checked = array('all' => '', 'select' => ' checked="checked"');
		}

		$actions = '<dl style="margin-top: 0; margin-bottom: 0; width: 100%;">
		<dt><label style="display: block;"><input type="radio" name="group_type" value="1" class="groups_check" onclick="checkAction(\'group\');" style="vertical-align: middle;"'.$group_checked['all'].' /> <strong>'.$lang->ougc_modtoolsperm_all.'</strong></label></dt>
			<dt><label style="display: block;"><input type="radio" name="group_type" value="2" class="groups_check" onclick="checkAction(\'group\');" style="vertical-align: middle;"'.$group_checked['select'].' /> <strong>'.$lang->ougc_modtoolsperm_selected.'</strong></label></dt>
			<dd style="margin-top: 4px;" id="group_2" class="groups">
				<table cellpadding="4">
					<tr>
						<td valign="top"><small>'.$lang->ougc_modtoolsperm_groups.'</small></td>
						<td>'.$form->generate_group_select('group_1_groups[]', $mybb->input['group_1_groups'], array('multiple' => true, 'size' => 5)).'</td>
					</tr>
				</table>
			</dd>
		</dl>
		<script type="text/javascript">
		checkAction(\'group\');
		</script>';
		$form_container->output_row($lang->ougc_modtoolsperm_container.' <em>*</em>', '', $actions);
	}
}

// Save our groups
function ougc_modtoolsperm_modtools_commit()
{
	global $mybb;

	if($mybb->request_method != 'post')
	{
		return;
	}

	global $db, $tid;

	$cleangroups = '';
	if((int)$mybb->input['group_type'] != 1 && $groups = array_filter(array_unique(array_map('intval', (array)$mybb->input['group_1_groups']))))
	{
		$cleangroups = implode(',', $groups);
	}

	$db->update_query('modtools', array('groups' => $db->escape_string($cleangroups)), 'tid=\''.(int)(in_array($mybb->input['action'], array('add_thread_tool', 'add_post_tool')) ? $tid : $mybb->input['tid']).'\'');
}

// Moderation hook to check permissions (custom moderator permissions too).
function ougc_modtoolsperm_moderation()
{
	global $mybb;

	if(in_array($mybb->input['action'], array('reports', 'allreports', 'getip', 'cancel_delayedmoderation', 'delayedmoderation', 'do_delayedmoderation', 'openclosethread', 'stick', 'removeredirects', 'deletethread', 'do_deletethread', 'deletepoll', 'do_deletepoll', 'approvethread', 'unapprovethread', 'deleteposts', 'do_deleteposts', 'mergeposts', 'do_mergeposts', 'move', 'do_move', 'threadnotes', 'do_threadnotes', 'merge', 'do_merge', 'split', 'do_split', 'removesubscriptions', 'multideletethreads', 'do_multideletethreads', 'multiopenthreads', 'multiclosethreads', 'multiapprovethreads', 'multiunapprovethreads', 'multistickthreads', 'multiunstickthreads', 'multimovethreads', 'do_multimovethreads', 'multideleteposts', 'do_multideleteposts', 'multimergeposts', 'do_multimergeposts', 'multisplitposts', 'do_multisplitposts', 'multiapproveposts', 'multiunapproveposts')) || ($tid = (int)$mybb->input['action']) < 1)
	{
		return;
	}

	global $db;

	require_once MYBB_ROOT.'inc/class_custommoderation.php';
	$custommod = new CustomModeration;
	$tool = $custommod->tool_info($tid);

	if($tool !== false && (bool)$tool['groups'] && !ougc_modtoolsperm_check_groups($tool['groups']))
	{
		error_no_permission();
	}
}

// Attempt to hide those moderation tools current user can use
function ougc_modtoolsperm_hide()
{
	global $mybb;

	// Current user is not a moderator or cannot use moderation tools
	switch(THIS_SCRIPT)
	{
		case 'forumdisplay.php':
			$fid = (int)$mybb->input['fid'];
			break;
		default:
			$fid = (int)$GLOBALS['thread']['fid'];
			break;
	}

	if(!is_moderator($fid, 'canusecustomtools'))
	{
		return;
	}

	global $db;

	// Lets figure out the what code we are going to insert
	$gids = explode(',', $mybb->user['additionalgroups']);
	$gids[] = $mybb->user['usergroup'];
	$gids = array_filter(array_unique($gids));

	switch($db->type)
	{
		case 'pgsql':
		case 'sqlite':
			$mysql = false;
			break;
		default:
			$mysql = true;
			break;
	}

	$replace = "(groups=\'\'";
	foreach($gids as $gid)
	{
		$gid = (int)$gid;
		if($mysql)
		{
			$replace .= " OR CONCAT(\',\',groups,\',\') LIKE \'%,{$gid},%\'";
		}
		else
		{
			$replace .= " OR \',\'||groups||\',\' LIKE \'%,{$gid},%\'";
		}
	}
	$replace .= ')';

	// Get the code depending in the script
	if(THIS_SCRIPT == 'forumdisplay.php')
	{
		$search = 'AND type';
		$code = '\'AND type\' => \'AND '.$replace.' AND type\'';
	}
	else
	{
		$search = 'modtools';
		$code = '\'WHERE CONCAT\' => \'WHERE (CONCAT\',
		\'forums=\\\'\\\'\' => \'forums=\\\'\\\') AND '.$replace.'\'';
	}

	// Do it!
	control_object($db, '
		function query($string, $hide_errors=0, $write_query=0)
		{
			if(!$write_query && strpos($string, \''.$search.'\') && !strpos($string, \''.$replace.'\'))
			{
				$string = strtr($string, array(
					'.$code.'
				));
			}
			return parent::query($string, $hide_errors, $write_query);
		}
	');
}

// Check if user meets user group memberships
function ougc_modtoolsperm_check_groups($groups)
{
	if(empty($groups))
	{
		return true;
	}

	global $mybb;
	$usergroups = explode(',', $mybb->user['additionalgroups']);
	$usergroups[] = $mybb->user['usergroup'];

	return (bool)array_intersect(array_map('intval', explode(',', $groups)), array_map('intval', $usergroups));
}

// control_object by Zinga Burga from MyBBHacks ( mybbhacks.zingaburga.com ), 1.62
if(!function_exists('control_object'))
{
	function control_object(&$obj, $code)
	{
		static $cnt = 0;
		$newname = '_objcont_'.(++$cnt);
		$objserial = serialize($obj);
		$classname = get_class($obj);
		$checkstr = 'O:'.strlen($classname).':"'.$classname.'":';
		$checkstr_len = strlen($checkstr);
		if(substr($objserial, 0, $checkstr_len) == $checkstr)
		{
			$vars = array();
			// grab resources/object etc, stripping scope info from keys
			foreach((array)$obj as $k => $v)
			{
				if($p = strrpos($k, "\0"))
				{
					$k = substr($k, $p+1);
				}
				$vars[$k] = $v;
			}
			if(!empty($vars))
			{
				$code .= '
					function ___setvars(&$a) {
						foreach($a as $k => &$v)
							$this->$k = $v;
					}
				';
			}
			eval('class '.$newname.' extends '.$classname.' {'.$code.'}');
			$obj = unserialize('O:'.strlen($newname).':"'.$newname.'":'.substr($objserial, $checkstr_len));
			if(!empty($vars))
			{
				$obj->___setvars($vars);
			}
		}
		// else not a valid object or PHP serialize has changed
	}
}