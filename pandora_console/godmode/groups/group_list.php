<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.


// Load global vars
global $config;

check_login();

require_once("include/functions_groups.php");
require_once($config['homedir'] . "/include/functions_agents.php");
require_once($config['homedir'] . '/include/functions_users.php');

if (is_ajax ()) {
	if (! check_acl($config['id_user'], 0, "AR")) {
		db_pandora_audit("ACL Violation", "Trying to access Group Management");
		require ("general/noaccess.php");
		return;
	}

	$get_group_json = (bool) get_parameter ('get_group_json');
	$get_group_agents = (bool) get_parameter ('get_group_agents');
	
	if ($get_group_json) {
		$id_group = (int) get_parameter ('id_group');
		
		if ($id_group == 0) {
			$group = array('id_grupo' => 0,
				'nombre' => 'All', 
				'icon' => 'world',
				'parent' => 0,
				'disabled' => 0,
				'custom_id' => null);
			
			echo json_encode ($group);
			return;
		}
		
		if (! check_acl ($config['id_user'], $id_group, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		$group = db_get_row ('tgrupo', 'id_grupo', $id_group);
		
		echo json_encode ($group);
		return;
	}
	
	if ($get_group_agents) {
		$id_group = (int) get_parameter ('id_group');
		$disabled = (int) get_parameter ('disabled', 0);
		$search = (string) get_parameter ('search', '');
		$recursion = (int) get_parameter ('recursion', 0);
		// Ids of agents to be include in the SQL clause as id_agent IN ()
		$filter_agents_json = (string) get_parameter ('filter_agents_json', '');
				
		if (! check_acl ($config['id_user'], $id_group, "AR")) {
			db_pandora_audit("ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}

		if($filter_agents_json != '') {
			$filter['id_agente'] = json_decode(io_safe_output($filter_agents_json), true);
		}

		$filter['disabled'] = $disabled;
		
		if($search != '') {
			$filter['string'] = $search;
		}
			
		$agents = agents_get_group_agents ($id_group, $filter, "none", false, $recursion);
		echo json_encode ($agents);
		return;
	}

	return;
}

if (! check_acl($config['id_user'], 0, "PM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

// Header
ui_print_page_header (__("Groups defined in Pandora"), "images/god1.png", false, "", true, "");

$create_group = (bool) get_parameter ('create_group');
$update_group = (bool) get_parameter ('update_group');
$delete_group = (bool) get_parameter ('delete_group');

/* Create group */
if ($create_group) {
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$alerts_disabled = (bool) get_parameter ('alerts_disabled');
	$custom_id = (string) get_parameter ('custom_id');
	$skin = (string) get_parameter ('skin');
$check = db_get_value('nombre', 'tgrupo', 'nombre', $name);

	
	/*Check if name field is empty*/
	if ($name != "") {
		if (!$check){
			$values = array(
				'nombre' => $name,
				'icon' => substr ($icon, 0, -4),
				'parent' => $id_parent,
				'disabled' => $alerts_disabled,
				'custom_id' => $custom_id,
				'id_skin' => $skin
			);
		
			$result = db_process_sql_insert('tgrupo', $values);
			if ($result) {
				echo "<h3 class='suc'>".__('Group successfully created')."</h3>"; 
			} else {
				echo "<h3 class='error'>".__('There was a problem creating group')."</h3>";
			}
		} else {
			echo "<h3 class='error'>".__('Each group must have a different name')."</h3>";
		}
	} else {
		//$result = false;
		echo "<h3 class='error'>".__('Group must have a name')."</h3>";
	}

}

/* Update group */
if ($update_group) {
	$id_group = (int) get_parameter ('id_group');
	$name = (string) get_parameter ('name');
	$icon = (string) get_parameter ('icon');
	$id_parent = (int) get_parameter ('id_parent');
	$alerts_enabled = (bool) get_parameter ('alerts_enabled');
	$custom_id = (string) get_parameter ('custom_id');
	$propagate = (bool) get_parameter('propagate');
	$skin = (string) get_parameter ('skin');

	/*Check if name field is empty*/
	if( $name != "") {	
		switch ($config["dbtype"]) {
			case "mysql":
				$sql = sprintf ('UPDATE tgrupo  SET nombre = "%s",
						icon = "%s", disabled = %d, parent = %d, custom_id = "%s", propagate = %d, id_skin = %d
						WHERE id_grupo = %d',
						$name, substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $custom_id, $propagate, $skin, $id_group);
				break;
			case "postgresql":
			case "oracle":
				$sql = sprintf ('UPDATE tgrupo  SET nombre = \'%s\',
						icon = \'%s\', disabled = %d, parent = %d, custom_id = \'%s\', propagate = %d, id_skin = %d
						WHERE id_grupo = %d',
						$name, substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $custom_id, $propagate, $skin, $id_group);
				break;
		}		
		$result = db_process_sql ($sql);
	} else {
		$result = false;
	}
	
	if ($result !== false) {
		echo "<h3 class='suc'>".__('Group successfully updated')."</h3>";
	} else {
		echo "<h3 class='error'>".__('There was a problem modifying group')."</h3>";
	}
}

/* Delete group */
if ($delete_group) {
	$id_group = (int) get_parameter ('id_group');

	$usedGroup = groups_check_used($id_group);
	
	if (!$usedGroup['return']) {
		
		$group = db_get_row_filter('tgrupo', array('id_grupo' => $id_group));
		
		db_process_sql_update('tgrupo', array('parent' => $group['parent']), array('parent' => $id_group));
		
		$result = db_process_sql_delete('tgroup_stat', array('id_group' => $id_group));
		
		$result = db_process_sql_delete('tgrupo', array('id_grupo' => $id_group));
	}
	else {
		echo "<h3 class='error'>" .
			sprintf(__('The group is not empty. It is use in %s.'), implode(', ', $usedGroup['tables'])) . "</h3>";
	}
	
	if ($result && (!$usedGroup['return'])) {
		echo "<h3 class='suc'>".__('Group successfully deleted')."</h3>";
	} 
	else {
		echo "<h3 class='error'>".__('There was a problem deleting group')."</h3>";
	}
		 
}
db_clean_cache();
$groups = users_get_groups_tree ($config['id_user'], "AR", true); 
$table->width = '98%';

if(!empty($groups)) {
	$table->head = array ();
	$table->head[0] = __('Name');
	$table->head[1] = __('ID');
	$table->head[2] = __('Icon');
	$table->head[3] = __('Alerts');
	$table->head[4] = __('Actions');
	$table->align = array ();
	$table->align[2] = 'center';
	$table->align[4] = 'center';
	$table->data = array ();

	$iterator = 0;

	foreach ($groups as $id_group => $group) {
		if ($group['deep'] == 0) {
			$table->rowstyle[$iterator] = '';
		}
		else {
			if ($group['parent'] != 0) {
				$table->rowstyle[$iterator] = 'display: none;';
			}
		}
		
		$symbolBranchs = '';
		if ($group['id_grupo'] != 0) {
			
			//Make a list of parents this group
			$end = false;
			$unloop = true;
			$parents = null;
			$parents[] = $group['parent'];
			while (!$end) {
				$lastParent = end($parents);
				if ($lastParent == 0) {
					$end = true;
				}
				else {
					$unloop = true;
					foreach ($groups as $id => $node) {
						if ($node['id_grupo'] == 0) {
							continue;
						}
						if ($node['id_grupo'] == $lastParent) {
							array_push($parents, $node['parent']);
							$unloop = false;
						}
					}
					
					//For exit of infinite loop
					if ($unloop) {
						break;
					}
				}
			}
			
			$table->rowclass[$iterator] = 'parent_' . $group['parent'];
			
			//Print the branch classes (for close a branch with child branch in the
			//javascript) of this parent as example:
			//
			// the tree (0(1,2(4,5),3))
			// for the group 4 have the style "parent_4 branch_0 branch_2"
			if (!empty($parents)) {
				foreach ($parents as $idParent) {
					$table->rowclass[$iterator] .= ' branch_' . $idParent;
					$symbolBranchs .= ' symbol_branch_' . $idParent;
				}
			}
		}
		
		$tabulation = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $group['deep']);
		
		if ($group['id_grupo'] == 0) {
			$symbol = '-';
		}
		else {
			$symbol = '+';
		}
		
		if ($group['hash_branch']) {
			$data[0] = '<strong>'.$tabulation . ' ' . 
				'<a href="javascript: showBranch(' . $group['id_grupo'] .
				', ' . $group['parent'] . ');" title="' . __('Show branch children') .
				'"><span class="symbol_' . $group['id_grupo'] . ' ' . $symbolBranchs . '">' .
				$symbol . '</span> '. ui_print_truncate_text($group['nombre']) . '</a></strong>';
		}
		else {
			$data[0] = '<strong>'.$tabulation . ' ' . ui_print_truncate_text($group['nombre']) . '</strong>';
		}
		$data[1] = $group['id_grupo'];
		$data[2] = ui_print_group_icon($group['id_grupo'], true);
		$data[3] = $group['disabled'] ? __('Disabled') : __('Enabled');
		if ($group['id_grupo'] == 0) {
			$data[4] = '';
		}
		else {
			$data[4] = '<a href="index.php?sec=gagente&sec2=godmode/groups/configure_group&id_group='.$group['id_grupo'].'">' . html_print_image("images/config.png", true, array("alt" => __('Edit'), "title" => __('Edit'), "border" => '0'));
			//Check if there is only a group to unable delete it
			if ((count($groups) > 3) || (count($groups) <= 3 && $group['parent'] != 0)) {
				$data[4] .= '&nbsp;&nbsp;<a href="index.php?sec=gagente&sec2=godmode/groups/group_list&id_group='.$id_group.'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;">' . html_print_image("images/cross.png", true, array("alt" => __('Delete'), "border" => '0'));
			}
		}
		
		array_push ($table->data, $data);
		$iterator++;
	}
	
	html_print_table ($table);
}
else {
	echo "<div class='nf'>".__('There are no defined groups')."</div>";
}

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Create group'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>

<script type="text/javascript">
function showBranch(parent) {
	display = $('.parent_' + parent).css('display');
	
	if (display != 'none') {
		$('.symbol_' + parent).html('+');
		$('.parent_' + parent).css('display', 'none');
		
		//Close the child branch too
		$('.branch_' + parent).css('display', 'none');
		$('.symbol_branch_' + parent).html('+');
	}
	else {
		$('.symbol_' + parent).html('-');
		$('.parent_' + parent).css('display', '');
	}
}
</script>
