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


if (is_ajax ()) {
	$get_reconscript_description = get_parameter('get_reconscript_description');
	$id_reconscript = get_parameter('id_reconscript');
	
	$description = db_get_value_filter('description', 'trecon_script',
		array('id_recon_script' => $id_reconscript));
	
	echo htmlentities (io_safe_output($description), ENT_QUOTES, "UTF-8", true);
	return;
}

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access recon script Management");
	require ("general/noaccess.php");
	return;
}

/*
 * Disabled at the moment.
if (!check_referer()) {
	require ("general/noaccess.php");
	
	return;
}
*/

$view = get_parameter ("view", "");
$create = get_parameter ("create", "");

if ($view != "") {
	$form_id = $view;
	$reconscript = db_get_row ("trecon_script", "id_recon_script", $form_id);
	$form_name = $reconscript["name"];
	$form_description = $reconscript["description"];
	$form_script = $reconscript ["script"];
	$macros = $reconscript ["macros"];
} 
if ($create != "") {
	$form_name = "";
	$form_description = "";
	$form_script = "";
	$macros = "";
}

// SHOW THE FORM
// =================================================================

if (($create != "") OR ($view != "")) {
	
	if ($create != "")
		ui_print_page_header (__('Recon script creation'), "images/gm_servers.png", false, "reconscript_definition", true);
	else {
		ui_print_page_header (__('Recon script update'), "images/gm_servers.png", false, "reconscript_definition", true);
		$id_recon_script = get_parameter ("view","");
	}
	
	
	if ($create == "") 
		echo "<form name=reconscript method='post' action='index.php?sec=gservers&sec2=godmode/servers/recon_script&update_reconscript=$id_recon_script'>";
	else
		echo "<form name=reconscript method='post' action='index.php?sec=gservers&sec2=godmode/servers/recon_script&create_reconscript=1'>";
	
	$table = new stdClass();
	$table->width = '100%';
	$table->id = 'table-form';
	$table->class = 'databox filters';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->style[2] = 'font-weight: bold';
	$table->data = array ();
	
	$data = array();
	$data[0] = __('Name');
	$data[1] = '<input type="text" name="form_name" size=30 value="'.$form_name.'">';
	$table->data['recon_name'] = $data;
	$table->colspan['recon_name'][1] = 3;
	
	$data = array();
	$data[0] = __('Script fullpath');
	$data[1] = '<input type="text" name="form_script" size=70 value="'.$form_script.'">';
	$table->data['recon_fullpath'] = $data;
	$table->colspan['recon_fullpath'][1] = 3;

	$data = array();
	$data[0] = __('Description');
	$data[1] = '<textarea name="form_description" cols="50" rows="4">';
	$data[1] .= $form_description;
	$data[1] .= '</textarea>';
	$table->data['recon_description'] = $data;
	$table->colspan['recon_description'][1] = 3;

	$macros = json_decode($macros,true);
	
	// This code is ready to add locked feature as plugins
	$locked = false;
	
	// The next row number is recon_3
	$next_name_number = 3;
	$i = 1;
	while (1) {
		// Always print at least one macro
		if((!isset($macros[$i]) || $macros[$i]['desc'] == '') && $i > 1) {
			break;
		}
		$macro_desc_name = 'field'.$i.'_desc';
		$macro_desc_value = '';
		$macro_help_name = 'field'.$i.'_help';
		$macro_help_value = '';
		$macro_value_name = 'field'.$i.'_value';
		$macro_value_value = '';
		$macro_name_name = 'field'.$i.'_macro';
		$macro_name = '_field'.$i.'_';
		$macro_hide_value_name = 'field'.$i.'_hide';
		$macro_hide_value_value = 0;
		
		if(isset($macros[$i]['desc'])) {
			$macro_desc_value = $macros[$i]['desc'];
		}
		
		if(isset($macros[$i]['help'])) {
			$macro_help_value = $macros[$i]['help'];
		}
		
		if(isset($macros[$i]['value'])) {
			$macro_value_value = $macros[$i]['value'];
		}
		if(isset($macros[$i]['hide'])) {
			$macro_hide_value_value = $macros[$i]['hide'];
		}
		
		$datam = array ();
		$datam[0] = __('Description')."<span style='font-weight: normal'> ($macro_name)</span>";
		$datam[0] .= html_print_input_hidden($macro_name_name, $macro_name, true);
		$datam[1] = html_print_input_text_extended ($macro_desc_name, $macro_desc_value, 'text-'.$macro_desc_name, '', 30, 255, $locked, '', "class='command_advanced_conf'", true);
		if($locked) {
			$datam[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf'));
		}
		
		$datam[2] = __('Default value')."<span style='font-weight: normal'> ($macro_name)</span>";
		$datam[3] = html_print_input_text_extended ($macro_value_name, $macro_value_value, 'text-'.$macro_value_name, '', 30, 255, $locked, '', "class='command_component command_advanced_conf'", true);
		if($locked) {
			$datam[3] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf'));
		}
		
		$table->data['recon_'.$next_name_number] = $datam;
		
		$next_name_number++;
		
		$table->colspan['recon_'.$next_name_number][1] = 3;
		
		$datam = array ();
		$datam[0] = __('Hide value') . ui_print_help_tip(__('This field will show up as dots like a password'), true);
		$datam[1] = html_print_checkbox_extended ($macro_hide_value_name, 1, $macro_hide_value_value, 0, '', array('class' => 'command_advanced_conf'), true, 'checkbox-'.$macro_hide_value_name);

		$table->data['recon_'.$next_name_number] = $datam;
		$next_name_number++;
		
		$table->colspan['recon_'.$next_name_number][1] = 3;

		$datam = array ();
		$datam[0] = __('Help')."<span style='font-weight: normal'> ($macro_name)</span><br><br><br>";
		$tadisabled = $locked === true ? ' disabled' : '';
		$datam[1] = html_print_textarea ($macro_help_name, 6, 100, $macro_help_value, 'class="command_advanced_conf" style="width: 97%;"' . $tadisabled, true);
		
		if($locked) {
			$datam[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf'));
		}
		$datam[1] .= "<br><br><br>";
		
		$table->data['recon_'.$next_name_number] = $datam;
		$next_name_number++;
		$i++;
	}
	
	if (!$locked) {
		$datam = array ();
		$datam[0] = '<span style="font-weight: bold">'.__('Add macro').'</span> <a href="javascript:new_macro(\'table-form-recon_\');update_preview();">'.html_print_image('images/add.png',true).'</a>';
		$datam[0] .= '<div id="next_macro" style="display:none">'.$i.'</div>';
		$datam[0] .= '<div id="next_row" style="display:none">'.$next_name_number.'</div>';
		$delete_macro_style = '';
		if($i <= 2) {
			$delete_macro_style = 'display:none;';
		}
		$datam[2] = '<div id="delete_macro_button" style="'.$delete_macro_style.'">'.__('Delete macro').' <a href="javascript:delete_macro_form(\'table-form-recon_\');update_preview();">'.html_print_image('images/delete.png',true).'</a></div>';
		
		$table->colspan['recon_action'][0] = 2;
		$table->rowstyle['recon_action'] = 'text-align:center';
		$table->colspan['recon_action'][2] = 2;
		$table->data['recon_action'] = $datam;
	}
	
	html_print_table($table);
	
	echo '<table width=100%>';
	echo '<tr><td align="right">';
	
	if ($create != "") {
		echo "<input name='crtbutton' type='submit' class='sub wand' value='".__('Create')."'>";
	}
	else {
		echo "<input name='uptbutton' type='submit' class='sub upd' value='".__('Update')."'>";
	}
	echo '</form></table>';
}
else {
	ui_print_page_header (__('Recon scripts registered on %s', get_product_name()), "images/gm_servers.png", false, "", true);
	
	// Update reconscript
	if (isset($_GET["update_reconscript"])) { // if modified any parameter
		$id_recon_script = get_parameter ("update_reconscript", 0);
		$reconscript_name = get_parameter ("form_name", "");
		$reconscript_description = get_parameter ("form_description", "");
		$reconscript_script = get_parameter ("form_script", "");
		
		// Get macros
		$i = 1;
		$macros = array();
		while (1) {
			$macro = (string)get_parameter ('field'.$i.'_macro');
			if($macro == '') {
				break;
			}
			
			$desc = (string)get_parameter ('field'.$i.'_desc');
			$help = (string)get_parameter ('field'.$i.'_help');
			$value = (string)get_parameter ('field'.$i.'_value');
			$hide = get_parameter ('field'.$i.'_hide');
			
			$macros[$i]['macro'] = $macro;
			$macros[$i]['desc'] = $desc;
			$macros[$i]['help'] = $help;
			$macros[$i]['value'] = $value;
			$macros[$i]['hide'] = $hide;
			$i++;
		}
		
		$macros = io_json_mb_encode($macros);
		
		$sql_update ="UPDATE trecon_script SET 
		name = '$reconscript_name',  
		description = '$reconscript_description', 
		script = '$reconscript_script', 
		macros = '$macros' 
		WHERE id_recon_script = $id_recon_script";
		$result = false;
		if ($reconscript_name != '' && $reconscript_script != '')
			$result = db_process_sql ($sql_update);
		if (! $result) {
			ui_print_error_message(__('Problem updating'));
		}
		else {
			ui_print_success_message(__('Updated successfully'));
		}
	}
	
	// Create reconscript
	if (isset($_GET["create_reconscript"])) {
		$reconscript_name = get_parameter ("form_name", "");
		$reconscript_description = get_parameter ("form_description", "");
		$reconscript_script = get_parameter ("form_script", "");
		
		// Get macros
		$i = 1;
		$macros = array();
		while (1) {
			$macro = (string)get_parameter ('field'.$i.'_macro');
			if($macro == '') {
				break;
			}
			
			$desc = (string)get_parameter ('field'.$i.'_desc');
			$help = (string)get_parameter ('field'.$i.'_help');
			$value = (string)get_parameter ('field'.$i.'_value');
			$hide = get_parameter ('field'.$i.'_hide');
			
			$macros[$i]['macro'] = $macro;
			$macros[$i]['desc'] = $desc;
			$macros[$i]['help'] = $help;
			$macros[$i]['value'] = $value;
			$macros[$i]['hide'] = $hide;
			$i++;
		}
		
		$macros = io_json_mb_encode($macros);
		
		$values = array(
			'name' => $reconscript_name,
			'description' => $reconscript_description,
			'script' => $reconscript_script,
			'macros' => $macros);
		$result = false;
		if ($values['name'] != '' && $values['script'] != '')
			$result = db_process_sql_insert('trecon_script', $values);
		if (! $result) {
			ui_print_error_message(__('Problem creating'));
		}
		else {
			ui_print_success_message(__('Created successfully'));
		}
	}
	
	if (isset($_GET["kill_reconscript"])) { // if delete alert
		$reconscript_id = get_parameter ("kill_reconscript", 0);
		
		$result = db_process_sql_delete('trecon_script',
			array('id_recon_script' => $reconscript_id));
		
		if (! $result) {
			ui_print_error_message(__('Problem deleting reconscript'));
		}
		else {
			ui_print_success_message(__('reconscript deleted successfully'));
		}
		if ($reconscript_id != 0) {
			$result = db_process_sql_delete('trecon_task',
				array('id_recon_script' => $reconscript_id));
		}
	}
	
	// If not edition or insert, then list available reconscripts
	
	$rows = db_get_all_rows_in_table('trecon_script');
	
	if ($rows !== false) {
		echo '<table width="100%" cellspacing="4" cellpadding="4" class="databox data">';
		echo "<th>" . __('Name') . "</th>";
		echo "<th>" . __('Description') . "</th>";
		echo "<th>" . __('Delete') . "</th>";
		$color = 0;
		foreach ($rows as $row) {
			if ($color == 1) {
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			echo "<td class='$tdcolor' style='min-width: 100px;'>";
			echo "<b><a href='index.php?sec=gservers&sec2=godmode/servers/recon_script&view=".$row["id_recon_script"]."'>";
			echo $row["name"];
			echo "</a></b></td>";
			echo "</td><td class='$tdcolor'>";
			$desc = io_safe_output($row["description"]);
			$desc = str_replace("\n", "<br>", $desc);
			echo $desc . '<br><br>';
			echo '<b>' . __('Command') . ': </b><i>' . $row["script"] . '</i>';
			echo "</td><td align='center' class='$tdcolor'>";
			echo "<a href='index.php?sec=gservers&sec2=godmode/servers/recon_script&kill_reconscript=".$row["id_recon_script"]."'>" . html_print_image("images/cross.png", true, array("border" => '0')) . "</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}
	else {
		ui_print_info_message ( array('no_close'=>true, 'message'=>  __('There are no recon scripts in the system') ) );
	}
	echo "<table width=100%>";
	echo "<tr><td align=right>";
	echo "<form name=reconscript method='post' action='index.php?sec=gservers&sec2=godmode/servers/recon_script&create=1'>";
	echo "<input name='crtbutton' type='submit' class='sub next' value='".__('Add')."'>";
	echo "</td></tr></table>";
}

ui_require_javascript_file('pandora_modules');

?>

