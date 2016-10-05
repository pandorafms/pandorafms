<?php
// ______                 __                     _______ _______ _______
//|   __ \.---.-.-----.--|  |.-----.----.---.-. |    ___|   |   |     __|
//|    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
//|___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
//
// ============================================================================
// Copyright (c) 2007-2010 Artica Soluciones Tecnologicas, http://www.artica.es
// This code is NOT free software. This code is NOT licenced under GPL2 licence
// You cannnot redistribute it without written permission of copyright holder.
// ============================================================================

// Load global variables
global $config;

// Check user credentials
check_login();

$id = (int) get_parameter('id_networkmap', 0);

$new_networkmap = (bool) get_parameter('new_networkmap', false);
$edit_networkmap = (bool) get_parameter('edit_networkmap', false);

$not_found = false;

if (empty($id)) {
	$new_networkmap = true;
	$edit_networkmap = false;
}

if ($new_networkmap) {
	$name = '';
	$id_group = 0;
	$node_radius = 40;
	$description = "";
	$method = 'twopi';
	$recon_task_id = 0;
	$source = 'group';
	$ip_mask = '';
	$dont_show_subgroups = false;
}

$disabled_generation_method_select = false;
$disabled_source = false;
if ($edit_networkmap) {
	$disabled_generation_method_select = true;
	$disabled_source = true;
	
	$values = db_get_row('tmap', 'id', $id);
	
	$not_found = false;
	if ($values === false) {
		$not_found = true;
	}
	else {
		$id_group = $values['id_group'];
		
		// ACL for the network map
		// $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
		$networkmap_write = check_acl ($config['id_user'], $id_group, "MW");
		$networkmap_manage = check_acl ($config['id_user'], $id_group, "MM");
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap");
			require ("general/noaccess.php");
			return;
		}
		
		$name = io_safe_output($values['name']);
		
		$description = $values['description'];
		
		$filter = json_decode($values['filter'], true);
		
		$node_radius = $filter['node_radius'];
		
		$source = $values['source'];
		switch ($source) {
			case 0:
				$source = 'group';
				break;
			case 1:
				$source = 'recon_task';
				break;
			case 2:
				$source = 'ip_mask';
				break;
		}
		$source_data = $values['source_data'];
		switch ($values['generation_method']) {
			case 0:
				$method = "circo";
				break;
			case 1:
				$method = "dot";
				break;
			case 2:
				$method = "twopi";
				break;
			case 3:
				$method = "neato";
				break;
			case 4:
				$method = "neato";
				break;
			case 5:
				$method = "fdp";
			case 6:
				$method = "radial_dinamic";
				break;
		}
		$recon_task_id = 0;
		if ($values['source'] == 1) {
			$recon_task_id = $values['source_data'];
		}
		else {
			$ip_mask = '';
			if (isset($filter['ip_mask'])) {
				$ip_mask = $filter['ip_mask'];
			}
		}
		
		$dont_show_subgroups = false;
		if (isset($filter['dont_show_subgroups']))
			$dont_show_subgroups = $filter['dont_show_subgroups'];
	}
}

ui_print_page_header(__('Networkmap'), "images/bricks.png",
	false, "network_map_enterprise", false);

$id_snmp_l2_recon = db_get_value('id_recon_script', 'trecon_script',
	'name', io_safe_input('SNMP L2 Recon'));

if (! check_acl ($config['id_user'], 0, "PM")) {
	$sql = sprintf('SELECT *
		FROM trecon_task RT, tusuario_perfil UP
		WHERE 
			id_recon_script = ' . $id_snmp_l2_recon . ' AND
			UP.id_usuario = "%s" AND UP.id_grupo = RT.id_group', 
		$config['id_user']);
	
	
	$result = db_get_all_rows_sql ($sql);
}
else {
	$sql = sprintf('SELECT *
		FROM trecon_task
		WHERE id_recon_script = ' . $id_snmp_l2_recon);
	$result = db_get_all_rows_sql ($sql);
}

$list_recon_tasks = array();
if (!empty($result)) {
	foreach ($result as $item) {
		$list_recon_tasks[$item['id_rt']] = io_safe_output($item['name']);
	}
}

if ($not_found) {
	ui_print_error_message(__('Not found networkmap.'));
}
else {
	$table = null;
	$table->id = 'form_editor';
	
	$table->width = '98%';
	$table->class = "databox_color";
	
	$table->head = array ();
	
	$table->size = array();
	$table->size[0] = '30%';
	
	$table->style = array ();
	$table->style[0] = 'font-weight: bold; width: 150px;';
	$table->data = array ();
	
	$table->data[0][0] = __('Name');
	$table->data[0][1] = html_print_input_text ('name', $name, '', 30,
		100,true);
	$table->data[1][0] = __('Group');
	$table->data[1][1] = html_print_select_groups(false, "AR", true,
		'id_group', $id_group, '', '', 0, true);
	
	$table->data[2][0] = __('Node radius');
	$table->data[2][1] = html_print_input_text ('node_radius', $node_radius, '', 2,
		10,true);
		
	$table->data[3][0] = __('Description');
	$table->data[3][1] = html_print_textarea ('description', 7, 25, $description, '', true);
	
	$table->data['source'][0] = __('Source');
	$table->data['source'][1] =
		html_print_radio_button('source', 'group', __('Group'), $source, true, $disabled_source) .
		html_print_radio_button('source', 'recon_task', __('Recon task'), $source, true, $disabled_source) .
		html_print_radio_button('source', 'ip_mask', __('CIDR IP mask'), $source, true, $disabled_source);
	
	$table->data['source_data_recon_task'][0] = __('Source from recon task');
	$table->data['source_data_recon_task'][0] .= ui_print_help_tip(
		__('It is setted any recon task, the nodes get from the recontask IP mask instead from the group.'), true);
	$table->data['source_data_recon_task'][1] = html_print_select(
		$list_recon_tasks, 'recon_task_id',  $recon_task_id, '', __('None'), 0, true, false, true, '', $disabled_source);
	$table->data['source_data_recon_task'][1] .= ui_print_help_tip(
		__('Show only the task with the recon script "SNMP L2 Recon".'), true);
	
	$table->data['source_data_ip_mask'][0] = __('Source from CIDR IP mask');
	$table->data['source_data_ip_mask'][1] =
		html_print_input_text('ip_mask', $ip_mask, '', 20, 255, true, $disabled_source);
	
	$table->data['source_data_dont_show_subgroups'][0] = __('Don\'t show subgroups:');
	$table->data['source_data_dont_show_subgroups'][1] =
		html_print_checkbox ('dont_show_subgroups', '1', $dont_show_subgroups, true, $disabled_source);
	
	$methods = array(
		'twopi' => 'radial',
		'dot' => 'flat',
		'circo' => 'circular',
		'neato' => 'spring1',
		'fdp' => 'spring2',
		'radial_dinamic' => 'radial dinamic'
		);
	
	$table->data[4][0] = __('Method generation networkmap');
	$table->data[4][1] = html_print_select($methods, 'method', $method,
		'', '', 'twopi', true, false, true, '',
		$disabled_generation_method_select);
	
	echo '<form method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';
	
	html_print_table($table);
	
	echo "<div style='width: " . $table->width . "; text-align: right;'>";
	if ($new_networkmap) {
		html_print_input_hidden ('save_networkmap', 1);
		html_print_submit_button (__('Save networkmap'), 'crt', false,
			'class="sub"');
	}
	if ($edit_networkmap) {
		html_print_input_hidden ('id_networkmap', $id);
		html_print_input_hidden ('update_networkmap', 1);
		html_print_submit_button (__('Update networkmap'), 'crt', false,
			'class="sub"');
	}
	echo "</form>";
	echo "</div>";
}
?>
<script type="text/javascript">

$(document).ready(function() {
	$("input[name='source']").on('change', function() {
		var source = $("input[name='source']:checked").val();
		
		if (source == 'recon_task') {
			$("#form_editor-source_data_ip_mask")
				.css('display', 'none');
			$("#form_editor-source_data_dont_show_subgroups")
				.css('display', 'none');
			$("#form_editor-source_data_recon_task")
				.css('display', '');
		}
		else if (source == 'ip_mask') {
			$("#form_editor-source_data_ip_mask")
				.css('display', '');
			$("#form_editor-source_data_recon_task")
				.css('display', 'none');
			$("#form_editor-source_data_dont_show_subgroups")
				.css('display', 'none');
		}
		else if (source == 'group') {
			$("#form_editor-source_data_ip_mask")
				.css('display', 'none');
			$("#form_editor-source_data_recon_task")
				.css('display', 'none');
			$("#form_editor-source_data_dont_show_subgroups")
				.css('display', '');
		}
	});
	
	$("input[name='source']").trigger("change");
});
</script>
