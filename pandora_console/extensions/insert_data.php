<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

global $config;

include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . "/include/functions_modules.php");
include_once($config['homedir'] . "/include/functions.php");

function createXMLData($agent, $agentModule, $time, $data) {
	global $config;
	
	$xmlTemplate = "<?xml version='1.0' encoding='ISO-8859-1'?>
		<agent_data description='' group='' os_name='%s' " .
		" os_version='%s' interval='%d' version='%s' timestamp='%s' agent_name='%s' timezone_offset='%d'>
			<module>
				<name><![CDATA[%s]]></name>
				<description><![CDATA[%s]]></description>
				<type><![CDATA[%s]]></type>
				<data><![CDATA[%s]]></data>
			</module>
		</agent_data>";
	
	$xml = sprintf($xmlTemplate, io_safe_output(get_os_name($agent['id_os'])),
		io_safe_output($agent['os_version']), $agent['intervalo'],
		io_safe_output($agent['agent_version']), $time,
		io_safe_output($agent['nombre']), $agent['timezone_offset'],
		io_safe_output($agentModule['nombre']), io_safe_output($agentModule['descripcion']), modules_get_type_name($agentModule['id_tipo_modulo']), $data);
	
	if (false === @file_put_contents($config['remote_config'] . '/' . io_safe_output($agent['nombre']) . '.' . strtotime($time) . '.data', $xml)) {
		return false;
	}
	else {
		return true;
	}
}

function mainInsertData() {
	global $config;
	
	ui_print_page_header (__("Insert data"), "images/extensions.png", false, "", true, "");
	
	if (! check_acl ($config['id_user'], 0, "AW") && ! is_user_admin ($config['id_user'])) {
		db_pandora_audit("ACL Violation", "Trying to access Setup Management");
		require ("general/noaccess.php");
		return;
	}
	
	$save = (bool)get_parameter('save', false);
	$id_agent = (string)get_parameter('id_agent', '');
	$id_agent_module = (int)get_parameter('id_agent_module', '');
	$data = (string)get_parameter('data');
	$date = (string) get_parameter ('date', date ('Y-m-d'));
	$time = (string) get_parameter ('time', date ('h:00A'));
	if (isset($_FILES['csv'])) {
		if ($_FILES['csv']['error'] != 4) {
			$csv = $_FILES['csv'];
		}
		else {
			$csv = false;
		}
	}
	else {
		$csv = false;
	}
	
	
	if ($save) {
		if (!check_acl($config['id_user'], agents_get_agent_group(agents_get_agent_id($id_agent)), "AW")) {
			ui_print_error_message(__('You haven\'t privileges for insert data in the agent.'));
		}
		else {
			$agent = db_get_row_filter('tagente', array('nombre' => $id_agent));
			$agentModule = db_get_row_filter('tagente_modulo', array('id_agente_modulo' => $id_agent_module));
			
			$date2 = str_replace('-', '/', $date);
			$time2 = DATE("H:i", strtotime($time));
			
			$date_xml = $date2 . ' ' . $time2 . ':00';
			
			$done = 0;
			$errors = 0;
			if ($csv !== false) {
				$file = file($csv['tmp_name']);
				foreach ($file as $line) {
					$tokens = explode(';', $line);
					
					$result = createXMLData($agent, $agentModule, trim($tokens[0]), trim($tokens[1]));
					
					if ($result) {
						$done++;
					}
					else {
						$errors++;
					}
				}
			}
			else {
				$result = createXMLData($agent, $agentModule, $date_xml, $data);
				
				if ($result) {
					$done++;
				}
				else {
					$errors++;
				}
			}
		}
		
		if ($errors > 0) {
			$msg = sprintf(__('Can\'t save agent (%s), module (%s) data xml.'), $agent['nombre'], $agentModule['nombre']);
			if ($errors > 1) {
				$msg .= " ($errors)";
			}
			ui_print_error_message($msg);
		}
		if ($done > 0) {
			$msg = sprintf(__('Save agent (%s), module (%s) data xml.'), $agent['nombre'], $agentModule['nombre']);
			if ($done > 1) {
				$msg .= " ($done)";
			}
			ui_print_success_message($msg);
		}
	}
	
	echo '<div class="notify">';
	echo sprintf(__("Please check that the directory \"%s\" is writeable by the apache user. <br /><br />The CSV file format is date;value&lt;newline&gt;date;value&lt;newline&gt;... The date in CSV is in format Y/m/d H:i:s."),
		$config['remote_config']);
	echo '</div>';
	
	$table = null;
	$table->width = '98%';
	$table->style = array();
	$table->style[0] = 'font-weight: bolder;';
	
	$table->data = array();
	$table->data[0][0] = __('Agent');
	$src_code = html_print_image('images/lightning.png', true, false, true);
	$table->data[0][1] = html_print_input_text_extended ('id_agent', $id_agent, 'text_id_agent', '', 30, 100, false, '',
		array('style' => 'background: url(' . $src_code . ') no-repeat right;'), true)
		. ui_print_help_tip(__('Type at least two characters to search'), true);
	$table->data[1][0] = __('Module');
	$modules = array ();
	if ($id_agent)
		$modules = agents_get_modules ($id_agent, false, array("delete_pending" => 0));
	$table->data[1][1] = html_print_select ($modules, 'id_agent_module', $id_agent_module, true,
		__('Select'), 0, true, false, true, '', ($id_agent === ''));
	$table->data[2][0] = __('Data');
	$table->data[2][1] = html_print_input_text('data', $data, __('Data'), 40, 60, true);
	$table->data[3][0] = __('Date');
	$table->data[3][1] = html_print_input_text ('date', $date, '', 11, 11, true).' ';
	$table->data[3][1] .= html_print_input_text ('time', $time, '', 7, 7, true);
	$table->data[4][0] = __('CSV');
	$table->data[4][1] = html_print_input_file('csv', true);
	
	echo "<form method='post' enctype='multipart/form-data'>";
	
	html_print_table($table);
	
	echo "<div style='text-align: right; width: " . $table->width . "'>";
	html_print_input_hidden('save', 1);
	html_print_submit_button(__('Save'), 'submit', ($id_agent === ''), 'class="sub next"');
	echo "</div>";
	
	echo "</form>";
	
	ui_require_css_file ('datepicker');
	ui_require_jquery_file ('ui.core');
	ui_require_jquery_file ('ui.datepicker');
	ui_require_jquery_file ('timeentry');
	ui_require_jquery_file ('autocomplete');
?>
<script type="text/javascript">
	/* <![CDATA[ */
	$(document).ready (function () {
		
		$("#text_id_agent").autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "operation/agentes/exportdata",
					search_agents: 1,
					id_group: function() { return $("#id_group").val(); }
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$("#text_id_agent").css ('background-color', '#cc0000');
					else
						$("#text_id_agent").css ('background-color', '');
					if (data == "")
						return false;
					
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[1]+'</span>';
				},
				delay: 200
			}
		);

		$("#text-time").timeEntry ({
			spinnerImage: 'images/time-entry.png',
			spinnerSize: [20, 20, 0]
		});
		
		$("#text-date").datepicker ();
		
	});
	
	$("#text_id_agent").result (
		function () {
			selectAgent = true;
			var agent_name = this.value;
			$('#id_agent_module').fadeOut ('normal', function () {
				$('#id_agent_module').empty ();
				var inputs = [];
				inputs.push ("agent_name=" + agent_name);
				inputs.push ('filter=delete_pending = 0');
				inputs.push ("get_agent_modules_json=1");
				inputs.push ("page=operation/agentes/ver_agente");
				jQuery.ajax ({
					data: inputs.join ("&"),
					type: 'GET',
					url: action="ajax.php",
					timeout: 10000,
					dataType: 'json',
					success: function (data) {
						$('#id_agent_module').append ($('<option></option>').attr ('value', 0).text ("--"));
						jQuery.each (data, function (i, val) {
							s = js_html_entity_decode (val['nombre']);
							$('#id_agent_module').append ($('<option></option>').attr ('value', val['id_agente_modulo']).text (s));
						});
						$('#id_agent_module').enable();
						$('#id_agent_module').fadeIn ('normal');

						$('#submit-submit').enable();
						$('#submit-submit').fadeIn ('normal');
					}
				});
			});
	
			
		}
	);
	/* ]]> */
</script>
<?php
}

extensions_add_godmode_function('mainInsertData');
extensions_add_godmode_menu_option(__('Insert Data'), 'AW', 'gagente', null, "v1r1");
?>
