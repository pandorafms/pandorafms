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

require_once ($config['homedir'] . '/include/functions_visual_map.php');
enterprise_include_once('/include/functions_visual_map.php');

// ACL for the general permission
$vconsoles_read = check_acl ($config['id_user'], 0, "VR");
$vconsoles_write = check_acl ($config['id_user'], 0, "VW");
$vconsoles_manage = check_acl ($config['id_user'], 0, "VM");

if (!$vconsoles_read && !$vconsoles_write && !$vconsoles_manage) {
	db_pandora_audit("ACL Violation",
		"Trying to access map builder");
	require ("general/noaccess.php");
	exit;
}

$pure = (int)get_parameter('pure', 0);
$hack_metaconsole = '';
if (defined('METACONSOLE'))
	$hack_metaconsole = '../../';

$buttons['visual_console'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/map_builder">' .
                html_print_image ("images/visual_console.png", true, array ("title" => __('Visual Console List'))) .'</a>'
);

$buttons['visual_console_favorite'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_favorite">' .
                html_print_image ("images/list.png", true, array ("title" => __('Visual Favourite Console'))) .'</a>'
);

$buttons['visual_console_template'] = array(
    'active' => false,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template">' .
                html_print_image ("images/templates.png", true, array ("title" => __('Visual Console Template'))) .'</a>'
);

$buttons['visual_console_template_wizard'] = array(
    'active' => true,
    'text' => '<a href="index.php?sec=network&sec2=godmode/reporting/visual_console_template_wizard">' .
                html_print_image ("images/wand.png", true, array ("title" => __('Visual Console Template Wizard'))) .'</a>'
);

if (!defined('METACONSOLE')) {
	ui_print_page_header(
		__('Visual Console') .' &raquo; ' . __('Wizard'),
		"images/op_reporting.png",
		false,
		"map_builder",
		false,
		$buttons
	);
}

$action = get_parameter ('action', '');

if ($action == 'apply') {
	$agents_selected = (array) get_parameter('id_agents2');
	$id_layout_template = get_parameter('templates');
	$name  = get_parameter('template_report_name', '');
	$group = get_parameter('template_report_group');

	if (empty($agents_selected) || empty($id_layout_template))
		$result = false;
	else {
        if($agents_selected && is_array($agents_selected)){
            foreach ($agents_selected as $key => $value) {
                $result = visual_map_instanciate_template(
                    $id_layout_template,
                    $name,
                    $value
                );
            }
        }
	}

	if ($result){
		ui_print_success_message(__('Sucessfully applied'));
    }
    else{
        ui_print_error_message(__('Could not be applied'));
    }
}

$templates = visual_map_get_user_layout_templates($config['id_user'], true);

if (is_metaconsole()) {
    $keys_field = 'nombre';
}
else {
    $keys_field = 'id_grupo';
}

$attr_available = array('id' => 'image-select_all_available', 'title' => __('Select all'), 'style' => 'cursor: pointer;');
$attr_apply     = array('id' => 'image-select_all_apply', 'title' => __('Select all'), 'style' => 'cursor: pointer;');

$table = '<form method="post" action="" enctype="multipart/form-data">';
$table .= "<table border=0 cellpadding=4 cellspacing=4 class='databox filters' width=100%>";
	$table .= "<tr>";
		$table .= "<td align='left'>";
		    $table .= "<b>" . __('Templates') . ":</b>";
		$table .= "</td>";
		$table .= "<td align='left'>";
		    $table .= html_print_select($templates, 'templates', $id_layout_template, '', __('None'), '0', true, false, true, '', false, 'width:180px;');
        $table .=  "</td>";
        $table .= "<td align='left'>";
		    $table .= "<b>" . __('Report name') . " " .
                ui_print_help_tip(__('Left in blank if you want to use default name: Template name - agents (num agents) - Date'), true) . ":</b>";
		$table .= "</td>";
		$table .= "<td align='left'>";
		    $table .= html_print_input_text ('template_report_name', '', '', 80, 150, true);
        $table .= "</td>";
    $table .= "</tr>";

    $table .= "<tr>";
        $table .= "<td align='left'>";
            $table .= '<b>' . __('Filter group') . ':</b>';
        $table .= "</td>";
        $table .= "<td align='left'>";
            $table .= html_print_select_groups(
                false, "RR", users_can_manage_group_all("RR"),
                'group', '', '', '', 0, true, false, false,
                '', false, false, false, false, $keys_field
            );
        $table .= "</td>";
        $table .= "<td align='left'>";
            $table .= "<b>" . __('Target group') . ":</b>";
        $table .= "</td>";
        $table .= "<td align='left'>";
            $table .= html_print_select_groups(
                false, "RR", users_can_manage_group_all("RR"),
                'template_report_group', '', '', '', 0, true,
                false, false, '', false, false, false, false,
                $keys_field
            );
        $table .= "</td>";
	$table .= "</tr>";

    $table .= "<tr>";
		$table .= "<td align='left'>";
		$table .= '<b>' . __('Filter agent') . ':</b>';
		$table .= "</td>";
        $table .= "<td align='left'>";
        $table .= html_print_input_text ('agent_filter', $agent_filter, '', 20, 150, true);
        $table .= "</td>";
        $table .= "<td align='left'>";
        $table .= '';
        $table .= "</td>";
        $table .= "<td align='left'>";
        $table .= '';
        $table .= "</td>";
    $table .=  "</tr>";
    $table .= "<tr>";
        $table .= "<td align='left' colspan=2>";
        $table .= "<b>" . __('Agents available')."</b>&nbsp;&nbsp;" .
                html_print_image ('images/tick.png', true, $attr_available, false, true);
        $table .= "</td>";
        $table .= "<td align='left' colspan=2>";
        $table .= "<b>" . __('Agents to apply')."</b>&nbsp;&nbsp;" .
                html_print_image ('images/tick.png', true, $attr_apply, false, true);
        $table .= "</td>";
    $table .=  "</tr>";

    $table .= "<tr>";
        $table .= "<td align='left' colspan=2>";
		    $option_style = array();
            $template_agents_in = array();
            $template_agents_all = array();
            $template_agents_out = array();
            $template_agents_out = array_diff_key($template_agents_all, $template_agents_in);
            $template_agents_in_keys = array_keys($template_agents_in);
            $template_agents_out_keys = array_keys($template_agents_out);

            $table .= html_print_select ($template_agents_out, 'id_agents[]', 0, false, '', '', true, true, true, '', false, 'width: 100%;', $option_style);
		$table .= "</td>";
        $table .= "<td align='left'>";
            $table .= html_print_image ('images/darrowright.png', true, array ('id' => 'right', 'title' => __('Add agents to template')));
            $table .= html_print_image ('images/darrowleft.png', true, array ('id' => 'left', 'title' => __('Undo agents to template')));
        $table .= "</td>";
        $table .= "<td align='left'>";
        $table .= $option_style = array();
        //Agents applied to the template
        $table .= html_print_select ($template_agents_in, 'id_agents2[]', 0, false, '', '', true, true, true, '', false, 'width: 100%;', $option_style);
        $table .= "</td>";
    $table .=  "</tr>";
$table .=  "</table>";

html_print_input_hidden('separator', $separator);
html_print_input_hidden('agents_in', implode($separator, $template_agents_in));
html_print_input_hidden('agents_in_keys', implode($separator, $template_agents_in_keys));
html_print_input_hidden('agents_out', implode($separator, $template_agents_out));
html_print_input_hidden('agents_out_keys', implode($separator, $template_agents_out_keys));

if (check_acl ($config['id_user'], 0, "RW")) {
	$table .= '<div class="action-buttons" style="width: 100%;">';
	$table .= html_print_input_hidden('action', 'apply', true);
	$table .= html_print_submit_button (__('Apply template'), 'apply', false, 'class="sub next"', true);
	$table .= '</div>';
}
$table .=  '</form>';

echo $table;

?>
<script language="javascript" type="text/javascript">

var metaconsole_enabled = 0;
if (<?php echo (int) is_metaconsole(); ?>) {
	metaconsole_enabled = 1;
}

var agents_out;
var agents_out_keys;
var agents_in;
var pending_delete_ids;
var agents_in_keys;
var separator;

var baseURL = "<?php echo ui_get_full_url(false, false, false, false); ?>";

$(document).ready (function () {
	if ($('#filter_by').length <= 0 || $('#filter_by').val() == 0) {
		$("#filter_tag_id").css('display', 'none');
		if (metaconsole_enabled) {
			filterByGroupMetaconsole($("#group").val(), '');
		}
		else {
			filterByGroup($("#group").val(), '');
		}
	}
	else {
		$("#filter_group_id").css('display', 'none');
		if (metaconsole_enabled) {
			filterByTagMetaconsole($("#tag_filter").val(), '');
		} else {
			filterByTag($("#tag_filter").val(), '');
		}
	}

	$('#filter_by').change (function () {
		if ($("#filter_by").val() == 0) {
			$("#filter_tag_id").css('display', 'none');
			$("#filter_group_id").css('display', '');
			if (metaconsole_enabled) {
				filterByGroupMetaconsole($("#group").val(), '');
			}
			else {
				filterByGroup($("#group").val(), '');
			}
		}
		else {
			$("#filter_tag_id").css('display', '');
			$("#filter_group_id").css('display', 'none');
			if (metaconsole_enabled) {
				filterByTagMetaconsole($("#tag_filter").val(), '');
			}
			else {
				filterByTag($("#tag_filter").val(), '');
			}
		}
	});

	// Get the agents in both sides from the hidden fields
	separator = $("#hidden-separator").attr('value');
	var aux;
	aux = $("#hidden-agents_in").attr('value');
	agents_in = aux.split(separator);
	aux = $("#hidden-agents_in_keys").attr('value');
	agents_in_keys = aux.split(separator);
	aux = $("#hidden-agents_out").attr('value');
	agents_out = aux.split(separator);
	aux = $("#hidden-agents_out_keys").attr('value');
	agents_out_keys = aux.split(separator);

	$("select[name='group']").change(function(){
		if (metaconsole_enabled) {
			filterByGroupMetaconsole($(this).val(), '');
		}
		else {
			filterByGroup($(this).val(), '');
		}
	});

	$("select[name='tag_filter']").change(function(){
		if (metaconsole_enabled) {
			filterByTagMetaconsole($(this).val(), '');
		}
		else {
			filterByTag($(this).val(), '');
		}
	});

	$("select[name='group2']").change(function(){
		filterByGroup($(this).val(), '2');
	});

	function filterByGroup(idGroup, idSelect) {
		$('#loading_group').show();

		$('#id_agents'+idSelect).empty ();
		search = $("#text-agent_filter"+idSelect).val();

		jQuery.post (baseURL + "/ajax.php",
			{"page" : "godmode/groups/group_list",
			"get_group_agents" : 1,
			"search" : search,
			"id_group" : idGroup,
			// Add a key prefix to avoid auto sorting in js object conversion
			"keys_prefix" : "_",
			// Juanma (22/05/2014) Fix: Dont show void agents in template wizard
			"show_void_agents" : 0
			},
			function (data, status) {

				var group_agents = new Array();
				var group_agents_keys = new Array();

				jQuery.each (data, function (id, value) {
					// Remove keys_prefix from the index
					id = id.substring(1);

					group_agents.push(value);
					group_agents_keys.push(id);
				});

				if(idSelect == '') {
					agents_out_keys = group_agents_keys;
					agents_out = group_agents;
				}
				else {
					agents_in_keys = group_agents_keys;
					agents_in = group_agents;
				}
				refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php echo (int) is_metaconsole(); ?>);
			},
			"json"
		);
	}

	function filterByGroupMetaconsole(groupName, idSelect) {
		$('#loading_group_filter_group').show();

		$('#id_agents'+idSelect).empty ();
		search = $("#text-agent_filter"+idSelect).val();

		jQuery.post (baseURL + "/ajax.php",
			{"page" : "enterprise/meta/include/ajax/wizard.ajax",
			"action" : "get_group_agents",
			"separator" : "|",
			"only_meta" : 0,
			"agent_search" : search,
			"no_filter_tag" : true,
			<?php
			if ($strict_user)
				echo '"id_group" : groupName';
			else
				echo '"group_name" : groupName';
			?>
			},
			function (data, status) {
				$('#loading_group_filter_group').hide();

				var group_agents = new Array();
				var group_agents_keys = new Array();

				jQuery.each (data, function (id, value) {
					group_agents.push(value);
					group_agents_keys.push(id);
				});

				if(idSelect == '') {
					agents_out_keys = group_agents_keys;
					agents_out = group_agents;
				}
				else {
					agents_in_keys = group_agents_keys;
					agents_in = group_agents;
				}
				refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php echo (int) is_metaconsole(); ?>);
			},
			"json"
		);
	}

	$("#group")
		.click (function () {
			$(this).css("width", "auto");
		})
		.blur (function () {
			$(this).css("width", "180px");
		});

	$("#group2").click (function () {
			$(this).css("width", "auto");
		})
		.blur (function () {
			$(this).css("width", "180px");
		});

	function refresh_agents(start_search, keys, values, select, metaconsole) {
		var n = 0;
		var i = 0;
		select.empty();

		// Fix: Remove agents inside the template from agent selector
		$('#id_agents2 option').each(function(){
			var out_agent = $(this).val();
			if (metaconsole) {
				var out_split = out_agent.split('|');

				if (out_split[0].length > 0)
					var out_agent = out_split[0] + '|' + out_split[1];
			}

			if (out_agent) {

				keys.forEach(function(it) {

					var it_data = it;
					if (metaconsole) {
						var it_split = it.split('|');
						var it_data = it_split[0] + '|' + it_split[1];
					}

					if (it_data == out_agent) {
						var index = keys.indexOf(it);

						// Remove from array!
						values.splice(index, 1);
						keys.splice(index, 1);
					}
				});
			}
		});

		values.forEach(function(item) {
			var re = new RegExp(start_search,"gi");

			match = item.match(re);

			if (match != null) {
				select.append ($("<option></option>").attr("value", keys[n]).html(values[n]));
				i++;
			}
			n++;
		});
		if (i == 0) {
			$(select).empty ();
			$(select).append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None');?>'));
		}

		$('.loading_div').hide();
	}

	$("#text-agent_filter").keyup (function () {
		$('#loading_filter').show();
		refresh_agents($(this).val(), agents_out_keys, agents_out, $("#id_agents"), <?php echo (int) is_metaconsole(); ?>);
	});

	$("#text-agent_filter2").keyup (function () {
		$('#loading_filter2').show();
		refresh_agents($(this).val(), agents_in_keys, agents_in, $("#id_agents2"), <?php echo (int) is_metaconsole(); ?>);
	});

	$("#right").click (function () {
		jQuery.each($("select[name='id_agents[]'] option:selected"), function (key, value) {
            agent_name = $(value).html();
            if (agent_name != <?php echo "'".__('None')."'"; ?>){
                id_agent = $(value).attr('value');
                //Remove the none value
                $("#id_agents2").find("option[value='']").remove();

                $("select[name='id_agents2[]']").append($("<option>").val(id_agent).html('<i>' + agent_name + '</i>'));
                $("#id_agents").find("option[value='" + id_agent + "']").remove();
            }
		});
	});

	$("#left").click(function() {
		jQuery.each($("select[name='id_agents2[]'] option:selected"), function (key, value) {
				agent_name = $(value).html();
				if (agent_name != <?php echo "'".__('None')."'"; ?>){
					id_agent = $(value).attr('value');
					$("select[name='id_agents[]']").append($("<option>").val(id_agent).html('<i>' + agent_name + '</i>'));
					$("#id_agents2").find("option[value='" + id_agent + "']").remove();
				}

				//If empty the selectbox
				if ($("#id_agents2 option").length == 0) {
					$("select[name='id_agents2[]']")
						.append($("<option>").val("")
						.html("<?php echo __('None'); ?>"));
				}
		});
	});

	$("#submit-apply").click(function () {
		$('#id_agents2>option').prop('selected', true);
		var id_agent2 = $('#id_agents2>option').val();
		//Prevent from applying template 'None' over agent
		if ($("#templates").val() == 0) {
			alert( <?php echo "'" . __('Please set template distinct than ') . '"' . __('None') . '"' . "'"; ?> );
			return false;
		}
		if(id_agent2 == ''){
			alert( <?php echo "'" . __('Please set agent distinct than ') . '"' . __('None') . '"' . "'"; ?> );
			return false;
		}
		if (!confirm ( <?php echo "'" . __('Are you sure?') . "'"; ?> ))
			return false;
	});

	$("#image-select_all_available").click(function (event) {
		event.preventDefault();

		$('#id_agents>option').prop('selected', true);
	})

	$("#image-select_all_apply").click(function (event) {
		event.preventDefault();

		$('#id_agents2>option').prop('selected', true);
	});

	$("#cleanup_template").click(function () {
		// Prevent user of current action
		if (! confirm ( <?php echo "'" . __('This will be delete all reports created in previous template applications. Do you want to continue?') . "'"; ?> )) 
			return false;

		// Prevent from applying template 'None' over agent
		if ($("#templates").val() == 0) {
			alert( <?php echo "'" . __('Please set template distinct than ') . '"' . __('None') . '"' . "'"; ?> );
			return false;
		}

		// Cleanup applied template
		var params = [];
		var result;
		params.push("cleanup_template=1");
		params.push("id_template_cleanup=" + $("#templates").val());
		params.push("page=" + <?php echo '"' . ENTERPRISE_DIR . '"'; ?> + "/godmode/reporting/reporting_builder.template_wizard");
		jQuery.ajax ({
			data: params.join ("&"),
			type: 'POST',
			url: action=baseURL + "/ajax.php",
			success: function (data) {
				var result = data;

				if (result == 1) {
					$("#wrong_cleanup").hide();
					$("#sucess_cleanup").show();
				}
				else {
					$("#sucess_cleanup").hide();
					$("#wrong_cleanup").show();
				}
			}
		});

		return false;
	});

	function filterByTagMetaconsole(idTag, idSelect) {
		$('#loading_tag_filter_tag').show();

		$('#id_agents'+idSelect).empty ();
		search = $("#text-agent_filter"+idSelect).val();

		jQuery.post (baseURL + "/ajax.php",
			{"page" : "enterprise/meta/include/ajax/wizard.ajax",
			"action" : "get_tag_agents",
			"id_user" : "<?php echo $config['id_user']; ?>",
			"separator" : "|",
			"only_meta" : 0,
			"agent_search" : search,
			"id_tag" : idTag
			},
			function (data, status) {
				$('#loading_tag_filter_tag').hide();

				var group_agents = new Array();
				var group_agents_keys = new Array();

				jQuery.each (data, function (id, value) {
					group_agents.push(value);
					group_agents_keys.push(id);
				});

				if(idSelect == '') {
					agents_out_keys = group_agents_keys;
					agents_out = group_agents;
				}
				else {
					agents_in_keys = group_agents_keys;
					agents_in = group_agents;
				}
				refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php echo (int) is_metaconsole(); ?>);
			},
			"json"
		);
	}

	function filterByTag(idTag, idSelect) {
		$('#loading_tag_filter_tag').show();

		$('#id_agents'+idSelect).empty ();
		search = $("#text-agent_filter"+idSelect).val();
		jQuery.post (baseURL + "/ajax.php",
			{"page" : "include/ajax/template_wizard.ajax",
			"action" : "get_tag_agents",
			"agent_search" : search,
			"id_tag" : idTag,
			"keys_prefix" : "_",
			"show_void_agents" : 0
			},
			function (data, status) {
				var group_agents = new Array();
				var group_agents_keys = new Array();

				jQuery.each (data, function (id, value) {
					// Remove keys_prefix from the index
					id = id.substring(1);

					group_agents.push(value);
					group_agents_keys.push(id);
				});

				if(idSelect == '') {
					agents_out_keys = group_agents_keys;
					agents_out = group_agents;
				}
				else {
					agents_in_keys = group_agents_keys;
					agents_in = group_agents;
				}

				refresh_agents($("#text-agent_filter"+idSelect).attr('value'), agents_out_keys, agents_out, $("#id_agents"+idSelect), <?php echo (int) is_metaconsole(); ?>);
			},
			"json"
		);
	}
});
</script>