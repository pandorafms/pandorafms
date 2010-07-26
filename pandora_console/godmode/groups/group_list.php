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

if (! give_acl($config['id_user'], 0, "PM")) {
	audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
		"Trying to access Group Management");
	require ("general/noaccess.php");
	return;
}

if (is_ajax ()) {
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
		
		if (! give_acl ($config['id_user'], $id_group, "AR")) {
			audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		$group = get_db_row ('tgrupo', 'id_grupo', $id_group);
		
		echo json_encode ($group);
		return;
	}
	
	if ($get_group_agents) {
		$id_group = (int) get_parameter ('id_group');
		
		if (! give_acl ($config['id_user'], $id_group, "AR")) {
			audit_db ($config['id_user'], $_SERVER['REMOTE_ADDR'], "ACL Violation",
				"Trying to access Alert Management");
			echo json_encode (false);
			return;
		}
		
		echo json_encode (get_group_agents ($id_group, false, "none"));
		return;
	}

	return;
}

// Header
print_page_header (__("Groups defined in Pandora"), "images/god1.png", false, "", true, "");

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
	
	/*Check if name field is empty*/
	if ($name != "") {
		$sql = sprintf ('INSERT INTO tgrupo (nombre, icon, parent, disabled, custom_id) 
				VALUES ("%s", "%s", %d, %d, "%s")',
				$name, substr ($icon, 0, -4), $id_parent, $alerts_disabled, $custom_id);
		$result = mysql_query ($sql);
	} else {
		$result = false;
	}
	
	if ($result) {
		echo "<h3 class='suc'>".__('Group successfully created')."</h3>"; 
	} else {
		echo "<h3 class='error'>".__('There was a problem creating group')."</h3>";	}
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

	/*Check if name field is empty*/
	if( $name != "") {	
		$sql = sprintf ('UPDATE tgrupo  SET nombre = "%s",
				icon = "%s", disabled = %d, parent = %d, custom_id = "%s", propagate = %d
				WHERE id_grupo = %d',
				$name, substr ($icon, 0, -4), !$alerts_enabled, $id_parent, $custom_id, $propagate, $id_group);
		$result = process_sql ($sql);
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
	
	$sql = sprintf ('SELECT * FROM tagente WHERE id_grupo = %d', $id_group);
	$agent = process_sql ($sql);
			
	if(!$agent){
		
		$group = get_db_row_filter('tgrupo', array('id_grupo' => $id_group));
		
		process_sql_update('tgrupo', array('parent' => $group['parent']), array('parent' => $id_group));

		$sql = sprintf ('DELETE FROM tgroup_stat WHERE id_group = %d', $id_group);
		$result = process_sql ($sql);
		
		$sql = sprintf ('DELETE FROM tgrupo WHERE id_grupo = %d', $id_group);
		$result = process_sql ($sql);
	}
	else
		echo "<h3 class='error'>".__('The group is not empty.')."</h3>";

	
	if (!$result || $agent )
		echo "<h3 class='error'>".__('There was a problem deleting group')."</h3>"; 
	else
		echo "<h3 class='suc'>".__('Group successfully deleted')."</h3>";
		 
}


$table->width = '65%';
$table->head = array ();
$table->head[0] = __('Name');
$table->head[1] = __('Icon');
$table->head[2] = __('Alerts');
$table->head[3] = __('Actions');
$table->align = array ();
$table->align[1] = 'center';
$table->align[3] = 'center';
$table->data = array ();

$groups = get_user_groups_tree ($config['id_user'], "AR", true);
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
			'<a href="javascript: showBranch(' . $group['id_grupo'] . ', ' . $group['parent'] . ');" title="' . __('Show branch children') . '"><span class="symbol_' . $group['id_grupo'] . ' ' . $symbolBranchs . '">' . $symbol . '</span> '. $group['nombre'].'</a></strong>';
	}
	else {
		$data[0] = '<strong>'.$tabulation . ' '. $group['nombre'].'</strong>';
	}
	$data[1] = print_group_icon($group['id_grupo'], true);
	$data[2] = $group['disabled'] ? __('Disabled') : __('Enabled');
	if ($group['id_grupo'] == 0) {
		$data[3] = '';
	}
	else {
		$data[3] = '<a href="index.php?sec=gagente&sec2=godmode/groups/configure_group&id_group='.$group['id_grupo'].'"><img border="0" src="images/config.png" alt="' . __('Edit') . '" title="' . __('Edit') . '" /></a>';
		$data[3] .= '<a href="index.php?sec=gagente&sec2=godmode/groups/group_list&id_group='.$id_group.'&delete_group=1" onClick="if (!confirm(\' '.__('Are you sure?').'\')) return false;"><img alt="' . __('Delete') . '" alt="' . __('Delete') . '" border="0" src="images/cross.png"></a>';
	}
	
	array_push ($table->data, $data);
	$iterator++;
}

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
<?php

print_table ($table);

echo '<form method="post" action="index.php?sec=gagente&sec2=godmode/groups/configure_group">';
echo '<div class="action-buttons" style="width: '.$table->width.'">';
print_submit_button (__('Create group'), 'crt', false, 'class="sub next"');
echo '</div>';
echo '</form>';

?>
