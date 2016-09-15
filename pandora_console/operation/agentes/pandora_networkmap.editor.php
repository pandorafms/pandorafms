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

if (is_ajax ()) {
	
	$resize_networkmap_enterprise = (bool)get_parameter(
		'resize_networkmap_enterprise', false);
	
	if ($resize_networkmap_enterprise) {
		$id = (int) get_parameter('id', 0);
		
		$return = array();
		$return['correct'] = false;
		
		$id_group = db_get_value('id_group', 'tmap', 'id', $id);
		// ACL for the network map
		// $networkmap_read = check_acl ($config['id_user'], $id_group, "MR");
		$networkmap_write = check_acl ($config['id_user'], $id_group, "MW");
		$networkmap_manage = check_acl ($config['id_user'], $id_group, "MM");
		
		if (!$networkmap_write && !$networkmap_manage) {
			db_pandora_audit("ACL Violation",
				"Trying to access networkmap");
			echo json_encode($return);
			return;
		}
		
		$min_x = db_get_sql('SELECT MIN(x)
			FROM titem
			WHERE id_map = ' . $id . ';');
		
		$min_x_options = db_get_sql('SELECT style
			FROM titem
			WHERE id_map = ' . $id . '
			ORDER BY x ASC LIMIT 1;');
		
		$min_x_options = json_decode($min_x_options, true);
		$min_x = $min_x - $min_x_options['width'] - 10; //For prevent the exit icons of networkmap, -10 for the text
		
		
		$min_y = db_get_sql('SELECT MIN(y)
			FROM titem
			WHERE id_map = ' . $id . ';');
		
		$min_y_options = db_get_sql('SELECT style
			FROM titem
			WHERE id_map = ' . $id . '
			ORDER BY y ASC LIMIT 1;');
		
		$min_y_options = json_decode($min_y_options, true);
		$min_y = $min_y - $min_y_options['height'] - 10; //For prevent the exit icons of networkmap, -10 for the text
		
		$result = db_process_sql('UPDATE titem
			SET x = x - ' . $min_x . ', y = y - ' . $min_y . '
			WHERE id_map = ' . $id . ';');
		
		if ($result !== false) {
			$max_x = db_get_sql('SELECT MAX(x)
				FROM titem
				WHERE id_map = ' . $id . ';');
			
			$max_x_options = db_get_sql('SELECT style
				FROM titem
				WHERE id_map = ' . $id . '
				ORDER BY x DESC LIMIT 1;');
			
			$max_x_options = json_decode($max_x_options, true);
			$max_x = $max_x + $max_x_options['width'] + 10; //For prevent the exit icons of networkmap, +10 for the text
			
			
			$max_y = db_get_sql('SELECT MAX(y)
				FROM titem
				WHERE tmap = ' . $id . ';');
			
			$max_y_options = db_get_sql('SELECT style
				FROM titem
				WHERE tmap = ' . $id . '
				ORDER BY y DESC LIMIT 1;');
			
			$max_y_options = json_decode($max_y_options, true);
			$max_y = $max_y + $max_y_options['height'] + 10; //For prevent the exit icons of networkmap, +10 for the text
			
			
			$options_w = db_get_value_filter('width',
				'tmap', array('id' => $id));
			$options_h = db_get_value_filter('height',
				'tmap', array('id' => $id));
			
			$options_w = $max_x; 
			$options_h = $max_y;
			
			$result = db_process_sql_update('tmap',
				array('width' => $options_w, 'height' => $options_h),
				array('id' => $id));
			
			if ($result) {
				$return['correct'] = true;
				$return['width'] = $max_x;
				$return['height'] = $max_y;
			}
		}
		
		echo json_encode($return);
		
		return;
	}
}

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
	$width = 3000;
	$height = 3000;
	$method = 'twopi';
	$refresh_value = 60 * 5;
	$l2_network_interfaces = true;
	// --------- DEPRECATED ----------------------------------------
	$old_mode = false;
	// --------- END DEPRECATED ------------------------------------
	$recon_task_id = 0;
	$source_data = 'group';
	$ip_mask = '';
	$dont_show_subgroups = false;
}

$disabled_generation_method_select = false;
if ($edit_networkmap) {
	$disabled_generation_method_select = true;
	
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

		$filter = json_decode($values['filter'], true);
		$source_data = $values['source_data'];
		$width = $values['width'];
		$height = $values['height'];
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
				break;
		}
		$refresh_value = $values['source_period'];
		$l2_network_interfaces = false;
		/* NO CONTEMPLADO
		if (isset($options['l2_network_interfaces']))
			$l2_network_interfaces = $options['l2_network_interfaces'];
		*/
		// --------- DEPRECATED ----------------------------------------
		$old_mode = false;
		/* NO CONTEMPLADO
		if (isset($options['old_mode']))
			$old_mode = (bool)$options['old_mode'];
		*/
		// --------- END DEPRECATED ------------------------------------
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
		/* NO CONTEMPLADO
		if (isset($options['dont_show_subgroups']))
			$dont_show_subgroups = $options['dont_show_subgroups'];
		*/
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
	
	$table->data['source_data'][0] = __('Source data');
	$table->data['source_data'][1] =
		html_print_radio_button('source_data', 'group', __('Group'), $source_data, true) .
		html_print_radio_button('source_data', 'recon_task', __('Recon task'), $source_data, true) .
		html_print_radio_button('source_data', 'ip_mask', __('CIDR IP mask'), $source_data, true);
	
	// --------- DEPRECATED --------------------------------------------
	$table->data['old_mode'][0] =
		__('Generate networkmap with parents relationships') .
		ui_print_help_tip(
			__('This feature is deprecated, be careful because in the next releases it will be disappear.'), true);
	
	$table->data['old_mode'][1] =
		html_print_checkbox('old_mode', '1', $old_mode, true);
	// --------- END DEPRECATED ----------------------------------------
	
	$table->data['source_data'][1] .=
		html_print_input_hidden('l2_network_interfaces', 1, true);
	
	
	$table->data['source_data_recon_task'][0] = __('Source from recon task');
	$table->data['source_data_recon_task'][0] .= ui_print_help_tip(
		__('It is setted any recon task, the nodes get from the recontask IP mask instead from the group.'), true);
	$table->data['source_data_recon_task'][1] = html_print_select(
		$list_recon_tasks, 'recon_task_id',  $recon_task_id, '', __('None'), 0, true);
	$table->data['source_data_recon_task'][1] .= ui_print_help_tip(
		__('Show only the task with the recon script "SNMP L2 Recon".'), true);
	
	
	$table->data['source_data_ip_mask'][0] = __('Source from CIDR IP mask');
	$table->data['source_data_ip_mask'][1] =
		html_print_input_text('ip_mask', $ip_mask, '', 20, 255, true);
	
	$table->data['source_data_dont_show_subgroups'][0] = __('Don\'t show subgroups:');
	$table->data['source_data_dont_show_subgroups'][1] =
		html_print_checkbox ('dont_show_subgroups', '1', $dont_show_subgroups, true);
	
	$table->data['source_data_empty'][0] = __('Start empty networkmap');
	if (((bool)$id)) {
		$table->data['source_data_empty'][1] =
			__('The networkmap has been generated already.');
	}
	else {
		$table->data['source_data_empty'][1] =
			html_print_checkbox_extended(
				'generation_process',
				'empty',
				false,
				false,
				'', '', true);
	}
	
	
	$table->data[3][0] = __('Size of networkmap (Width x Height)');
	$table->data[3][1] = html_print_input_text ('width', $width, '', 4,
		10,true) . __("x");
	$table->data[3][1] .= html_print_input_text ('height', $height, '',
		4, 10,true);
	$methods = array(
		'twopi' => 'radial',
		'dot' => 'flat',
		'circo' => 'circular',
		'neato' => 'spring1',
		'fdp' => 'spring2'
		);
	
	$table->data[4][0] = __('Method generation networkmap');
	$table->data[4][1] = html_print_select($methods, 'method', $method,
		'', '', 'twopi', true, false, true, '',
		$disabled_generation_method_select);
	$table->data[5][0] = __('Refresh network map state');
	$table->data[5][1] = html_print_extended_select_for_time(
		'refresh_state', $refresh_value, '', '', 0, 7, true);
	
	$table->data[6][0] = __('Resize the networkmap');
	$table->data[6][0] .= ui_print_help_tip(
		__('This operation can\'t be undone, because it is on DB.'), true);
	$table->data[6][1] = '<div id="spinner_process"></div><div id="process_button">' . 
		html_print_button(__('Process'), 
		'process', !((bool)$id), 'resize_networkmap_enterprise(' . $id . ');', 'class="sub"', true) .
		'</div>';
	
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
function resize_networkmap_enterprise(id) {
	var params1 = [];
	params1.push("get_image_path=1");
	params1.push("img_src=" + "images/spinner.gif");
	params1.push("page=include/ajax/skins.ajax");
	jQuery.ajax ({
		data: params1.join ("&"),
		type: 'POST',
		url: action="ajax.php",
		async: false,
		timeout: 10000,
		success: function (data) {
			$("#spinner_process").html(data);
			$("#process_button").hide();
		}
	});
	$("#submit-crt").hide();
	
	
	var params = [];
	params.push("resize_networkmap_enterprise=1");
	params.push("id=" + id);
	params.push("page=operation/agentes/pandora_networkmap.editor");
	jQuery.ajax ({
		data: params.join ("&"),
		dataType: 'json',
		type: 'POST',
		url: action="ajax.php",
		success: function (data) {
			if (data['correct']) {
				//$("#spinner_process").hide();
				$("#spinner_process").html('<?php echo __('Networkmap resized.');?>');
				
				
				$("#text-width").val(data['width']);
				$("#text-height").val(data['height']);
			}
			else {
				$("#spinner_process").html('<?php echo __('Error process map');?>');
			}
		}
	});
}

$(document).ready(function() {
	$("input[name='source_data']").on('change', function() {
		var source_data = $("input[name='source_data']:checked").val();
		
		if (source_data == 'recon_task') {
			$("#form_editor-source_data_ip_mask")
				.css('display', 'none');
			$("#form_editor-source_data_dont_show_subgroups")
				.css('display', 'none');
			$("#form_editor-source_data_recon_task")
				.css('display', '');
		}
		else if (source_data == 'ip_mask') {
			$("#form_editor-source_data_ip_mask")
				.css('display', '');
			$("#form_editor-source_data_recon_task")
				.css('display', 'none');
			$("#form_editor-source_data_dont_show_subgroups")
				.css('display', 'none');
		}
		else if (source_data == 'group') {
			$("#form_editor-source_data_ip_mask")
				.css('display', 'none');
			$("#form_editor-source_data_recon_task")
				.css('display', 'none');
			$("#form_editor-source_data_dont_show_subgroups")
				.css('display', '');
		}
	});
	
	$("input[name='source_data']").trigger("change");
});
</script>
