<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2009 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

require_once ('include/config.php');
require_once ('include/functions_agents.php');

if (is_ajax ()) {
	$search_agents = (bool) get_parameter ('search_agents');
	
	if ($search_agents) {
		require_once ('include/functions_ui_renders.php');
		
		$filter = str_replace  ("\\\"", "\"", $_POST['filter']);
		$filter = json_decode ($filter, true);
		$fields = '';
		if (isset ($_POST['fields']))
			$fields = json_decode (str_replace  ("\\\"", "\"", $_POST['fields']));
		
		$table_renders = str_replace  ("\\\"", "\"", $_POST['table_renders']);
		$table_renders = json_decode ($table_renders, true);
		print_r ($fields);
		$access = (string) get_parameter ('access', 'AR');
		
		foreach ($_POST as $field => $value) {
			$value = safe_input ($value);
			switch ($field) {
			case 'page':
			case 'search_agents':
			case 'search':
			case 'table_renders':
			case 'fields':
			case 'filter':
			case 'access':
				continue;
			case 'search':
				array_push ($filter, '(nombre LIKE "%%'.$value.'%%" OR descripcion LIKE "%%'.$value.'%%")');
				break;
			case 'id_group':
				if ($value == 1)
					$filter['id_grupo'] = array_keys (get_user_groups (false, $value));
				else
					$filter['id_grupo'] = $value;
				break;
			default:
				$filter[$field] = $value;
			}
		}
		
		$agents = get_agents ($filter, $fields, $access);
		$all_data = array ();
		if ($agents !== false) {
			foreach ($agents as $agent) {
				$data = array ();
				foreach ($table_renders as $name => $values) {
					if (! is_numeric ($name)) {
						array_push ($data, render_agent_field (&$agent, $name, $values, true));
					} else {
						array_push ($data, render_agent_field (&$agent, $values, false, true));
					}
				}
				array_push ($all_data, $data);
			}
		}
		
		echo json_encode ($all_data);
		return;
	}
	return;
}

require_once ('include/functions_ui_renders.php');

check_login ();

if ($show_filter_form) {
	$table->width = '90%';
	$table->id = 'search_agent_table';
	$table->data = array ();
	$table->size = array ();
	$table->style = array ();
	$table->style[0] = 'font-weight: bold';
	$table->style[2] = 'font-weight: bold';

	$odd = true;
	if ($group_filter) {
		if ($odd) 
			$data = array ();
		$data = array ();
		$data[] = __('Group');
		$data[] = print_select (get_user_groups (false, $access),
			'id_group', '', '', '', '', true);
		if (! $odd)
			array_push ($table->data, $data);
		$odd = !$odd;
	}

	if ($text_filter) {
		if ($odd) 
			$data = array ();
		$data[] = __('Search');
		$data[] = print_input_text ('search', '', '', 15, 255, true);
		if (! $odd)
			array_push ($table->data, $data);
		$odd = !$odd;
	}
	
	echo '<form id="agent_search" method="post">';
	print_table ($table);
	
	echo '<div class="action-buttons" style="width: '.$table->width.'">';
	print_submit_button (__('Search'), 'search', false, 'class="sub search"');
	print_input_hidden ('search_agents', 1);
	echo '</div>';
	echo '</form>';
	
	require_jquery_file ('form');
}

$table->width = '90%';
$table->id = 'agents_table';
$table->head = $table_heads;
$table->align = $table_align;
$table->size = $table_size;
$table->data = array ();

$agents = get_agents ($filter, $fields, $access);

echo '<div id="agents_loading" class="loading invisible">';
echo '<img src="images/spinner.gif" />';
echo __('Loading').'&hellip;';
echo '</div>';

echo '<div id="agents_list"'.($agents === false ? ' class="invisible"' : '').'">';
echo '<div id="no_agents"'.($agents === false ? '' : ' class="invisible"').'>';
print_error_message (__('No agents found'));
echo '</div>';
if ($agents !== false) {
	foreach ($agents as $agent) {
		$data = array ();
		foreach ($table_renders as $name => $values) {
			if (! is_numeric ($name)) {
				array_push ($data, render_agent_field (&$agent, $name, $values, true));
			} else {
				array_push ($data, render_agent_field (&$agent, $values, false, true));
			}
		}
		array_push ($table->data, $data);
	}
}

print_table ($table);
echo '</div>';
?>
<script type="text/javascript">
/* <![CDATA[ */
var table_renders = '<?php echo json_encode ($table_renders) ?>';
var fields = '<?php echo json_encode ($fields) ?>';
var filter = '<?php echo json_encode ($filter) ?>';

$(document).ready (function () {
<?php if ($show_filter_form): ?>
	$("form#agent_search").submit (function () {
		$("#agents_loading").show ();
		$("#no_agents, #agents_list, table#agents_table").hide ();
		$("table#agents_table tbody tr").remove ();
		values = $(this).formToArray ();
		values.push ({name: "page", value: "general/ui/agents_list"});
		values.push ({name: "table_renders", value: table_renders});
		values.push ({name: "filter", value: filter});
		
		if (fields != "")
			values.push ({name: "fields", value: fields});
		jQuery.post ("ajax.php",
			values,
			function (data, status) {
				if (! data || data.length == 0) {
					$("#agents_loading").hide ();
					$("#agents_list, #no_agents").show ();
					return;
				}
				
				jQuery.each (data, function () {
					tr = $("<tr></tr>");
					len = this.length;
					for (i = 0; i < len; i++) {
						td = $("<td></td>").html (this[i]);
						tr.append (td);
					}
					$("table#agents_table tbody").append (tr);
				});
				$("#agents_loading").hide ();
				$("#agents_list, table#agents_table").show ();
			},
			"json"
		);
		return false;
	});
<?php endif; ?>
});
/* ]]> */
</script>
