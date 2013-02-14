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
	$get_plugin_description = get_parameter('get_plugin_description');
	$id_plugin = get_parameter('id_plugin');
	
	$description = db_get_value_filter('description', 'tplugin', array('id' => $id_plugin));
	$preload = io_safe_output($description);
	$preload = str_replace ("\n", "<br>", $preload);
	
	echo $preload;
	return;
}

// Load global vars
global $config;

check_login ();

if (! check_acl ($config['id_user'], 0, "LM")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Plugin Management");
	require ("general/noaccess.php");
	return;
}

enterprise_include_once ('meta/include/functions_components_meta.php');

$view = get_parameter ("view", "");
$create = get_parameter ("create", "");

if ($view != "") {
	$form_id = $view;
	$plugin = db_get_row ("tplugin", "id", $form_id);
	$form_name = $plugin["name"];
	$form_description = $plugin["description"];
	$form_max_timeout = $plugin ["max_timeout"];
	$form_max_retries = $plugin ["max_retries"];
	$form_execute = $plugin ["execute"];
	$form_plugin_type = $plugin ["plugin_type"];
	$macros = $plugin ["macros"];
	$parameters = $plugin ["parameters"];
} 
if ($create != "") {
	$form_name = "";
	$form_description = "";
	$form_max_timeout = 15;
	$form_max_retries = 1;
	$form_execute = "";
	$form_plugin_type = 0;
	$form_parameters = "";
	$macros = "";
	$parameters = "";
}

// SHOW THE FORM
// =================================================================

$sec = 'gservers';

if (($create != "") OR ($view != "")) {
	
	if(defined('METACONSOLE')) {
		components_meta_print_header();
		$sec = 'advanced';
	}
	else {
		if ($create != "")
			ui_print_page_header (__('Plugin creation') . ui_print_help_icon("plugin_definition", true), "", false, "", true);
		else {
			ui_print_page_header (__('Plugin update') . ui_print_help_icon("plugin_definition", true), "", false, "", true);
		}
	}
	
	if ($create == "") {
		$plugin_id = get_parameter ("view","");
		echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&update_plugin=$plugin_id&pure=" . $config['pure'] . "'>";
	}
	else {
		echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&create_plugin=1&pure=" . $config['pure'] . "'>";
	}
	
	$table->width = '98%';
	$table->id = 'table-form';
	$table->class = 'databox_color';
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->style[2] = 'font-weight: bold';
	$table->data = array ();
	
	$data = array();
	$data[0] = __('Name');
	$data[1] = '<input type="text" name="form_name" size=100 value="'.$form_name.'">';
	$table->colspan['plugin_name'][1] = 3;
	$table->data['plugin_name'] = $data;
	
	$data = array();
	$data[0] = __('Plugin type');
	$fields[0]= __("Standard");
	$fields[1]= __("Nagios");
	$data[1] = html_print_select ($fields, "form_plugin_type", $form_plugin_type, '', '', 0, true);
	$table->data['plugin_type'] = $data;

	$data[0] = __('Max. timeout').ui_print_help_tip (__('This value only will be applied if is minor than the server general configuration plugin timeout').'. <br><br>'.__('If you set a 0 seconds timeout, the server plugin timeout will be used'), true);
	$data[1] = html_print_extended_select_for_time ('form_max_timeout', $form_max_timeout, '', '', '0', false, true);
	$data[2] = __('Max. retries');
	$data[3] = '<input type="text" name="form_max_retries" size=5 value="'.$form_max_retries.'">';
	$table->data['plugin_timeout'] = $data;
	
	$data = array();
	$data[0] = __('Description');
	$data[1] = '<textarea name="form_description" cols="50" rows="4">'.$form_description.'</textarea>';
	$table->colspan['plugin_desc'][1] = 3;
	$table->data['plugin_desc'] = $data;
	
	echo '<br>';
	
	echo '<fieldset style="width:96%"><legend>'.__('General').'</legend>';
	html_print_table($table);
	echo '</fieldset>';
	
	$table->data = array();
	
	$plugin_id = get_parameter ("view", 0);
	
	$locked = true;
	
	// If we have plugin id (update mode) and this plugin used by any module or component
	// The command configuration will be locked
	if ($plugin_id > 0) {
		$modules_using_plugin = db_get_value_filter('count(*)','tagente_modulo', array('delete_pending' => 0, 'id_plugin' => $plugin_id));	
		$components_using_plugin = db_get_value_filter('count(*)','tnetwork_component', array('id_plugin' => $plugin_id));	
		if(($components_using_plugin + $modules_using_plugin) == 0) {
			$locked = false;
		}
	}
	else {
		$locked = false;
	}
	
	$disabled = '';
	if ($locked) {
		$disabled = 'readonly="readonly"';
	}
	
	$data = array();
	$data[0] = __('Plugin command');
	$data[1] = '<input type="text" name="form_execute" id="form_execute" class="command_component command_advanced_conf" size=100 value="'.$form_execute.'" '.$disabled.'>';
	if ($locked) {
		$data[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf'));
	}
	$table->data['plugin_command'] = $data;
	
	$data = array();
	$data[0] = __('Plug-in parameters').ui_print_help_icon ('plugin_parameters', true);
	$data[1] = '<input type="text" name="form_parameters" id="form_parameters" class="command_component command_advanced_conf" size=100 value="'.$parameters.'" '.$disabled.'>';
	if ($locked) {
		$data[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf'));
	}
	$table->data['plugin_parameters'] = $data;
	
	$data = array();
	$data[0] = __('Command preview');
	$data[1] = '<div id="command_preview" style="font-style:italic"></div>';
	$table->data['plugin_preview'] = $data;

	echo '<fieldset style="width:96%"><legend>'.__('Command').'</legend>';
	html_print_table($table);
	echo '</fieldset>';

	$data = array();
	
	$table->data = array ();
	
	$macros = json_decode($macros,true);
	
	// The next row number is plugin_9
	$next_name_number = 9;
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
		
		if(isset($macros[$i]['desc'])) {
			$macro_desc_value = $macros[$i]['desc'];
		}
		
		if(isset($macros[$i]['help'])) {
			$macro_help_value = $macros[$i]['help'];
		}
		
		if(isset($macros[$i]['value'])) {
			$macro_value_value = $macros[$i]['value'];
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
		
		$table->data['plugin_'.$next_name_number] = $datam;

		$next_name_number++;
		
		$table->colspan['plugin_'.$next_name_number][1] = 3;

		$datam = array ();
		$datam[0] = __('Help')."<span style='font-weight: normal'> ($macro_name)</span><br><br><br>";
		$datam[1] = html_print_input_text_extended ($macro_help_name, $macro_help_value, 'text-'.$macro_help_name, '', 100, 255, $locked, '', "class='command_advanced_conf'", true);
		if($locked) {
			$datam[1] .= html_print_image('images/lock.png', true, array('class' => 'command_advanced_conf'));
		}
		$datam[1] .= "<br><br><br>";
		
		$table->data['plugin_'.$next_name_number] = $datam;
		$next_name_number++;
		$i++;
	}
	
	if (!$locked) {
		$datam = array ();
		$datam[0] = '<span style="font-weight: bold">'.__('Add macro').'</span> <a href="javascript:new_macro(\'table-form-plugin_\');update_preview();">'.html_print_image('images/add.png',true).'</a>';
		$datam[0] .= '<div id="next_macro" style="display:none">'.$i.'</div>';
		$datam[0] .= '<div id="next_row" style="display:none">'.$next_name_number.'</div>';
		$delete_macro_style = '';
		if($i <= 2) {
			$delete_macro_style = 'display:none;';
		}
		$datam[2] = '<div id="delete_macro_button" style="'.$delete_macro_style.'">'.__('Delete macro').' <a href="javascript:delete_macro(\'table-form-plugin_\');update_preview();">'.html_print_image('images/cancel.png',true).'</a></div>';
		
		$table->colspan['plugin_action'][0] = 2;
		$table->rowstyle['plugin_action'] = 'text-align:center';
		$table->colspan['plugin_action'][2] = 2;
		$table->data['plugin_action'] = $datam;
	}
	
	echo '<fieldset style="width:96%"><legend>'.__('Parameters macros').ui_print_help_icon ('macros', true).'</legend>';
	html_print_table($table);
	echo '</fieldset>';
	
	echo '<table width="98%">';
	echo '<tr><td align="right">';
	
	if ($create != ""){
		echo "<input name='crtbutton' type='submit' class='sub wand' value='" .
			__('Create') . "'>";
	}
	else {
		echo "<input name='uptbutton' type='submit' class='sub upd' value='" .
			__('Update') . "'>";
	}
	echo '</form></table>';
}
else {
	if(defined('METACONSOLE')) {
		components_meta_print_header();
		$sec = 'advanced';
	}
	else {
		ui_print_page_header (__('Plugins registered in Pandora FMS'), "", false, "", true);
	}
	
	// Update plugin
	if (isset($_GET["update_plugin"])){ // if modified any parameter
		$plugin_id = get_parameter ("update_plugin", 0);
		$plugin_name = get_parameter ("form_name", "");
		$plugin_description = get_parameter ("form_description", "");
		$plugin_max_timeout = get_parameter ("form_max_timeout", "");
		$plugin_max_retries = get_parameter ("form_max_retries", "");
		$plugin_execute = get_parameter ("form_execute", "");
		$plugin_plugin_type = get_parameter ("form_plugin_type", "0");
		$parameters = get_parameter ("form_parameters", "");
		
		// Get macros
		$i = 1;
		$macros = array();
		while(1) {
			$macro = (string)get_parameter ('field'.$i.'_macro');
			if($macro == '') {
				break;
			}
			
			$desc = (string)get_parameter ('field'.$i.'_desc');
			$help = (string)get_parameter ('field'.$i.'_help');
			$value = (string)get_parameter ('field'.$i.'_value');
			
			$macros[$i]['macro'] = $macro;
			$macros[$i]['desc'] = $desc;
			$macros[$i]['help'] = $help;
			$macros[$i]['value'] = $value;
			$i++;
		}
		
		$macros = json_encode($macros);
		
		$values = array(
			'name' => $plugin_name,  
			'description' => $plugin_description, 
			'max_timeout' => $plugin_max_timeout, 
			'max_retries' => $plugin_max_retries, 
			'execute' => $plugin_execute, 
			'plugin_type' => $plugin_plugin_type,
			'parameters' => $parameters,
			'macros' => $macros); 
		
		$result = false;
		if ($values['name'] != '' && $values['execute'] != '')
			$result = db_process_sql_update('tplugin', $values, array('id' => $plugin_id));
		
		if (! $result) {
			echo "<h3 class='error'>".__('Problem updating plugin')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('Plugin updated successfully')."</h3>";
		}
	}
	
	// Create plugin
	if (isset($_GET["create_plugin"])){	 
		$plugin_name = get_parameter ("form_name", "");
		$plugin_description = get_parameter ("form_description", "");
		$plugin_max_timeout = get_parameter ("form_max_timeout", "");
		$plugin_max_retries = get_parameter ("form_max_retries", "");
		$plugin_execute = get_parameter ("form_execute", "");
		$plugin_plugin_type = get_parameter ("form_plugin_type", "0");
		$plugin_parameters = get_parameter ("form_parameters", "0");
		
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
			
			$macros[$i]['macro'] = $macro;
			$macros[$i]['desc'] = $desc;
			$macros[$i]['help'] = $help;
			$macros[$i]['value'] = $value;
			$i++;
		}
		
		$macros = json_encode($macros);
		
		$values = array(
			'name' => $plugin_name,
			'description' => $plugin_description,
			'max_timeout' => $plugin_max_timeout,
			'max_retries' => $plugin_max_retries,
			'execute' => $plugin_execute,
			'plugin_type' => $plugin_plugin_type,
			'parameters' => $plugin_parameters,
			'macros' => $macros);
		
		$result = false;
		if ($values['name'] != '' && $values['execute'] != '')
			$result = db_process_sql_insert('tplugin', $values);
		
		if (! $result) {
			echo "<h3 class='error'>" .
				__('Problem creating plugin') . "</h3>";
		}
		else {
			echo "<h3 class='suc'>" .
				__('Plugin created successfully') . "</h3>";
		}
	}
	
	if (isset($_GET["kill_plugin"])) { // if delete alert
		$plugin_id = get_parameter ("kill_plugin", 0);
		
		$result = db_process_sql_delete('tplugin', array('id' => $plugin_id));
			
		if (! $result) {
			echo "<h3 class='error'>".__('Problem deleting plugin')."</h3>";
		}
		else {
			echo "<h3 class='suc'>".__('Plugin deleted successfully')."</h3>";
		}
		if ($plugin_id != 0) {
			// Delete all the modules with this plugin
			$plugin_modules = db_get_all_rows_filter('tagente_modulo', array('id_plugin' => $plugin_id));
			
			foreach($plugin_modules as $pm) {
				modules_delete_agent_module ($pm['id_agente_modulo']);
			}
		}
	}
	
	// If not edition or insert, then list available plugins
	$rows = db_get_all_rows_sql('SELECT * FROM tplugin ORDER BY name');
	
	if ($rows !== false) {
		echo '<table width="98%" cellspacing="4" cellpadding="4" class="databox">';
		echo "<th>".__('Name')."</th>";
		echo "<th>".__('Type')."</th>";
		echo "<th>".__('Command')."</th>";
		echo "<th style='width:50px;'>" . '<span title="Operations">' . __('Op.') . '</span>' . "</th>";
		$color = 0;
		
		foreach ($rows as $row) {
			if ($color == 1){
				$tdcolor = "datos";
				$color = 0;
			}
			else {
				$tdcolor = "datos2";
				$color = 1;
			}
			echo "<tr>";
			echo "<td class=$tdcolor>";
			echo "<b><a href='index.php?sec=$sec&sec2=godmode/servers/plugin&view=".$row["id"]."&tab=plugins&pure=" . $config['pure'] . "'>";
			echo $row["name"];
			echo "</a></b></td>";
			echo "<td class=$tdcolor>";
			if ($row["plugin_type"] == 0)
				echo __('Standard');
			else
				echo __('Nagios');
			echo "</td><td class=$tdcolor>";
			echo $row["execute"];
			echo "</td><td class=$tdcolor>";
			echo "<a href='index.php?sec=$sec&sec2=godmode/servers/plugin&view=".$row["id"]."&tab=plugins&pure=" . $config['pure'] . "'>" . html_print_image('images/config.png', true, array("title" => __("Edit"))) . "</a>&nbsp;&nbsp;";
			echo "<a href='index.php?sec=$sec&sec2=godmode/servers/plugin&kill_plugin=".$row["id"]."&tab=plugins&pure=" . $config['pure'] . "' onclick='javascript: if (!confirm(\"" . __('All the modules that are using this plugin will be deleted') . '. ' . __('Are you sure?') . "\")) return false;'>" . html_print_image("images/cross.png", true, array("border" => '0')) . "</a>";
			echo "</td></tr>";
		}
		echo "</table>";
	}
	else {
		echo '<div class="nf">'. __('There are no plugins in the system') . '</div>';
		echo "<br>";
	}
	echo "<table width='98%'>";
	echo "<tr><td align=right>";
	echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&create=1&pure=" . $config['pure'] . "'>";
	echo "<input name='crtbutton' type='submit' class='sub next' value='".__('Add')."'>";
	echo "</td></tr></table>";
}

ui_require_javascript_file('pandora_modules');

?>

<script type="text/javascript">
	
$(document).ready(function() {
	function update_preview() {
		var command = $('#form_execute').val();
		var parameters = $('#form_parameters').val();
		
		var i = 1;
		
		while(1) {
			if($('#text-field'+i+'_value').val() == undefined) {
				break;
			}
			
			if($('#text-field'+i+'_value').val() != '') {
				parameters = parameters.replace('_field'+i+'_',$('#text-field'+i+'_value').val());
			}
			
			i++;
		}
		
		$('#command_preview').html(command+' '+parameters);
	}

	update_preview();
	
	$('.command_component').live( 'keyup', function() {
		update_preview();
	});

});

<?php 
	if($locked) {
?>
		$('.command_advanced_conf').click(function() {
			alert('<?php echo __("The plugin command cannot be updated because some modules or components are using the plugin."); ?>');
		});
<?php
	}
?>

</script>
