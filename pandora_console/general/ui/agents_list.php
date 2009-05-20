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
	$show_filter_form = (bool) get_parameter ('show_filter_form', false);
	$access = (string) get_parameter ('access', 'AR');
	
	$filter = str_replace  ("\\\"", "\"", $_POST['filter']);
	$filter = json_decode ($filter, true);
	$id_group = (int) get_parameter ('id_group');
	if ($id_group > 1 && give_acl ($config['id_user'], $id_group, $access))
		$filter['id_grupo'] = $id_group;
	else
		$filter['id_grupo'] = array_keys (get_user_groups (false, $access));
	
	$fields = '';
	if (isset ($_POST['fields']))
		$fields = json_decode (str_replace  ("\\\"", "\"", $_POST['fields']));
	
	$table_heads = array ();
	if (isset ($_POST['table_heads']))
		$table_heads = json_decode (str_replace  ("\\\"", "\"", $_POST['table_heads']));
	$table_size = array ();
	if (isset ($_POST['table_size']))
		$table_size = json_decode (str_replace  ("\\\"", "\"", $_POST['table_size']));
	$table_size = array ();
	if (isset ($_POST['table_align']))
		$table_align = json_decode (str_replace  ("\\\"", "\"", $_POST['table_align']));
	$table_renders = str_replace  ("\\\"", "\"", $_POST['table_renders']);
	$table_renders = json_decode ($table_renders, true);
	
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
		$data[] = print_input_text ('search_string', '', '', 15, 255, true);
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

if (! isset ($filter) || ! is_array ($filter))
	$filter = array ();

$search_string = (string) get_parameter ('search_string');
if ($search_string != '') {
	$filter[] = '(nombre LIKE "%'.$search_string.'%" OR comentarios LIKE "%'.$search_string.'%" OR direccion LIKE "%'.$search_string.'%")';
}

$total_agents = get_agents ($filter, array ('COUNT(*) AS total'), $access);
if ($total_agents !== false)
	$total_agents = $total_agents[0]['total'];
else
	$total_agents = 0;
$filter['limit'] = $config['block_size'];
$filter['offset'] = (int) get_parameter ('offset');
$agents = get_agents ($filter, $fields, $access);
unset ($filter['limit']);
unset ($filter['offset']);

if (! is_ajax ()) {
	echo '<div id="agents_loading" class="loading invisible">';
	echo '<img src="images/spinner.gif" />';
	echo __('Loading').'&hellip;';
	echo '</div>';
}

echo '<div id="agents_list"'.($agents === false ? ' class="invisible"' : '').'">';
echo '<div id="no_agents"'.($agents === false ? '' : ' class="invisible"').'>';
print_error_message (__('No agents found'));
echo '</div>';

$table->width = '90%';
$table->id = 'agents_table';
$table->head = $table_heads;
$table->align = $table_align;
$table->size = $table_size;
$table->style = array ();
$table->data = array ();
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
echo '<div id="agents"'.($agents === false ? ' class="invisible"' : '').'>';
pagination ($total_agents, '#');
print_table ($table);
echo '</div>';
echo '</div>';

if (is_ajax ())
	return;
?>
<script type="text/javascript">
/* <![CDATA[ */

function send_search_form (offset) {
	table_renders = '<?php echo json_encode ($table_renders) ?>';
	fields = '<?php echo json_encode ($fields) ?>';
	filter = '<?php echo json_encode ($filter) ?>';
	table_heads = '<?php echo json_encode ($table_heads) ?>';
	table_align = '<?php echo json_encode ($table_align) ?>';
	table_size = '<?php echo json_encode ($table_size) ?>';
	
	$("#agents_loading").show ();
	$("#no_agents, #agents_list, table#agents_table").hide ();
	$("#agents_list").remove ();
	values = $("form#agent_search").formToArray ();
	values.push ({name: "page", value: "general/ui/agents_list"});
	values.push ({name: "table_renders", value: table_renders});
	values.push ({name: "table_size", value: table_size});
	values.push ({name: "table_align", value: table_align});
	values.push ({name: "table_heads", value: table_heads});
	values.push ({name: "filter", value: filter});
	values.push ({name: "offset", value: offset});
	
	if (fields != "")
		values.push ({name: "fields", value: fields});
	jQuery.post ("ajax.php",
		values,
		function (data, status) {
			$("#agents_loading").hide ().after (data);
			$("#agents_list, table#agents_table").show ();
			$("a.pagination").click (function () {
				offset = this.href.split ("=").pop ();
				send_search_form (offset);
				return false;
			});
		},
		"html"
	);
}

$(document).ready (function () {
<?php if ($show_filter_form): ?>
	$("form#agent_search").submit (function () {
		send_search_form (0);
		return false;
	});
	
	$("a.pagination").click (function () {
		offset = this.href.split ("=").pop ();
		send_search_form (offset);
		return false;
	});
<?php endif; ?>
});
/* ]]> */
</script>
