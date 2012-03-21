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

global $config;

// Login check
check_login ();

if (! check_acl ($config['id_user'], 0, "LW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Alert Management");
	require ("general/noaccess.php");
	exit;
}

include_once($config['homedir'] . "/include/functions_agents.php");
include_once($config['homedir'] . '/include/functions_users.php');

$table->id = 'add_alert_table';
$table->class = 'databox';
$table->width = '98%';
$table->head = array ();
$table->data = array ();
$table->size = array ();
$table->size = array ();
$table->size[0] = '20%';
$table->size[1] = '80%';
$table->style[0] = 'font-weight: bold; vertical-align: top;';
$table->align[0] = 'left';
$table->align[1] = 'left';


/* Add an agent selector */
if (! $id_agente) {
	
	$table->data['agent'][0] = __('Agent');
	$src_code = html_print_image('images/lightning.png', true, false, true);
	$table->data['agent'][1] = html_print_input_text_extended ('id_agent', '', 'text_id_agent', '', 30, 100, false, '',
	array('style' => 'background: url(' . $src_code . ') no-repeat right;'), true)
	. ui_print_help_tip(__('Type at least two characters to search'), true);
}

$table->data[0][0] = __('Module');
$modules = array ();
if ($id_agente)
	$modules = agents_get_modules ($id_agente, false, array("delete_pending" => 0));

$table->data[0][1] = html_print_select ($modules, 'id_agent_module', 0, true,
	__('Select'), 0, true, false, true, '', ($id_agente == 0));
$table->data[0][1] .= ' <span id="latest_value" class="invisible">'.__('Latest value').': ';
$table->data[0][1] .= '<span id="value">&nbsp;</span></span>';
$table->data[0][1] .= ' <span id="module_loading" class="invisible">';
$table->data[0][1] .= html_print_image('images/spinner.png', true) . '</span>';

$table->data[1][0] = __('Template');

$table->data[1][0] = __('Template');
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$templates = alerts_get_alert_templates (false, array ('id', 'name'));
else{
	$usr_groups = users_get_groups($config['id_user'], 'LW', true);
	$filter_groups = '';
	$filter_groups = implode(',', array_keys($usr_groups));
	$templates = alerts_get_alert_templates (array ('id_group IN (' . $filter_groups . ')'), array ('id', 'name'));
}	

$table->data[1][1] = html_print_select (index_array ($templates, 'id', 'name'),
	'template', '', '', __('Select'), 0, true);
$table->data[1][1] .= ' <a class="template_details invisible" href="#">' .
	html_print_image("images/zoom.png", true, array("class" => 'img_help')) . '</a>';
if (check_acl ($config['id_user'], 0, "LM")) {
	$table->data[1][1] .= html_print_image ('images/add.png', true);
	$table->data[1][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_template">';
	$table->data[1][1] .= __('Create Template');
	$table->data[1][1] .= '</a>';
}

$table->data[2][0] = __('Actions');

$actions = array ('0' => __('None'));

$table->data[2][1] = '<div class="actions_container">';
$table->data[2][1] = html_print_select($actions,'action_select','','','','',true);
$table->data[2][1] .= ' <span id="action_loading" class="invisible">';
$table->data[2][1] .= html_print_image('images/spinner.png', true) . '</span>';
$table->data[2][1] .= ' <span id="advanced_action" class="advanced_actions invisible">';
$table->data[2][1] .= __('Number of alerts match from').' ';
$table->data[2][1] .= html_print_input_text ('fires_min', '', '', 4, 10, true);
$table->data[2][1] .= ' '.__('to').' ';
$table->data[2][1] .= html_print_input_text ('fires_max', '', '', 4, 10, true);
$table->data[2][1] .= ui_print_help_icon ("alert-matches", true);
$table->data[2][1] .= '</span>';
$table->data[2][1] .= '</div>';
if (check_acl ($config['id_user'], 0, "LM")) {
	$table->data[2][1] .= html_print_image ('images/add.png', true);
	$table->data[2][1] .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/configure_alert_action">';
	$table->data[2][1] .= __('Create Action');
	$table->data[2][1] .= '</a>';
}
$table->data[3][0] = __('Threshold');
$table->data[3][1] = html_print_extended_select_for_time ('module_action_threshold', 0, '', 0,
	__('None'), false, true) . ui_print_help_icon ('action_threshold', true);

echo '<form class="add_alert_form" method="post">';

html_print_table ($table);

echo '<div class="action-buttons" style="width: '.$table->width.'">';
html_print_submit_button (__('Add alert'), 'add', false, 'class="sub wand"');
html_print_input_hidden ('create_alert', 1);
echo '</div></form>';

ui_require_css_file ('cluetip');
ui_require_jquery_file ('cluetip');
ui_require_jquery_file ('pandora.controls');
ui_require_jquery_file ('bgiframe');
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


	$("#text_id_agent").result (
			function () {
				selectAgent = true;
				var agent_name = this.value;
				$('#id_agent_module').fadeOut ('normal', function () {
					$('#id_agent_module').empty ();
					var inputs = [];
					inputs.push ("agent_name=" + agent_name);
					inputs.push ('filter=delete_pending = 0 AND id_agente_modulo NOT IN (SELECT id_agente_modulo FROM tagente_estado WHERE utimestamp = 0)');
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
						}
					});
				});
		
				
			}
		);

<?php if (! $id_agente) : ?>
	$("#id_group").pandoraSelectGroupAgent ({
		callbackBefore: function () {
			$select = $("#id_agent_module").disable ();
			$select.siblings ("span#latest_value").hide ();
			$("option[value!=0]", $select).remove ();
			return true;
		}
	});
<?php endif; ?>
	
	$("select#template").change (function () {
		id = this.value;
		$a = $(this).siblings ("a");
		if (id == 0) {
			$a.hide ();
			return;
		}
		$a.unbind ()
			.attr ("href", "ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template="+id)
			.show ()
			.cluetip ({
				arrows: true,
				attribute: 'href',
				cluetipClass: 'default'
			}).click (function () {
				return false;
			});

		$("#action_select").hide();
		$("#action_select").html('');
		$("#action_loading").show ();

		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/estado_agente",
			"get_actions_alert_template" : 1,
			"id_template" : this.value
			},
			function (data, status) {
				option = $("<option></option>")
					.attr ("value", '0')
					.append ('<?php echo __('None'); ?>');
				$("#action_select").append (option);
				
				if (data == false) {
					//There aren't any action
				}
				else {
					 if (data != '') {
						jQuery.each (data, function (i, val) {
							option = $("<option></option>")
								.attr ("value", val["id"])
								.append (val["name"]);

							if (val["sort_order"] == 1)
								option.attr ("selected", true);
							
							$("#action_select").append (option);
						});
					}	
					$('#advanced_action').show();
				}
				$("#action_loading").hide ();
				$("#action_select").show();
			},
			"json"
		);
	});

	$("#action_select").change(function () {
			if ($("#action_select").attr ("value") != '0') {
				$('#advanced_action').show();
			}
			else {
				$('#advanced_action').hide();
			} 	
		}
	);
	
	$("#id_agent_module").change (function () {
		var $value = $(this).siblings ("span#latest_value").hide ();
		var $loading = $(this).siblings ("span#module_loading").show ();
		$("#value", $value).empty ();
		jQuery.post ("ajax.php",
			{"page" : "operation/agentes/estado_agente",
			"get_agent_module_last_value" : 1,
			"id_agent_module" : this.value
			},
			function (data, status) {
				if (data === false) {
					$("#value", $value).append ("<em><?php echo __('Unknown') ?></em>");
				} else if (data == "") {
					$("#value", $value).append ("<em><?php echo __('Empty') ?></em>");
				} else {
					$("#value", $value).append (data);
				}
				$loading.hide ();
				$value.show ();
			},
			"json"
		);
	});
	
	$("form.add_alert_form :checkbox[name^=actions]").change (function () {
		$advanced = $(this).siblings ("span#advanced_"+this.value);
		$("input", $advanced).attr ("value", 0);
		$advanced.toggle ();
	});
});
/* ]]> */
</script>
