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

// Load global variables
global $config;

// Check user credentials
check_login ();

if (! check_acl ($config['id_user'], 0, "RW")) {
	db_pandora_audit("ACL Violation",
		"Trying to access Inventory Module Management");
	require ("general/noaccess.php");
	return;
}
require_once ('include/functions_container.php');

$delete_container = get_parameter('delete_container',0);

if ($delete_container){
	$id_container = get_parameter('id',0);
	$child = folder_get_all_child_container($id_container);
	
	if($child){
		foreach ($child as $key => $value) {
			$parent = array(
				'parent' => 1);
			db_process_sql_update('tcontainer', $parent, array('id_container' => $value['id_container']));
		}
	}
	db_process_sql_delete('tcontainer', array('id_container' => $id_container));
	
}

$max_graph = $config['max_graph_container'];

$buttons['graph_list'] = array('active' => false,
	'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graphs">' .
	html_print_image("images/list.png", true, array ("title" => __('Graph list'))) .'</a>');

$enterpriseEnable = false;
if (enterprise_include_once('include/functions_reporting.php') !== ENTERPRISE_NOT_HOOK) {
	$enterpriseEnable = true;
}

if ($enterpriseEnable) {
	$buttons = reporting_enterprise_add_template_graph_tabs($buttons);
}

$subsection = reporting_enterprise_add_graph_template_subsection('', $buttons);
reporting_enterprise_select_graph_template_tab();

$buttons['graph_container'] = array('active' => true,
	'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_container">' .
		html_print_image("images/graph-container.png", true, array ("title" => __('Graph container'))) . '</a>');
// Header
ui_print_page_header (__('Graph container'), "", false, "",false,$buttons);

$container = folder_get_folders();

$tree = folder_get_folders_tree_recursive($container);
echo folder_togge_tree_folders($tree);

echo "<div style='float: right;'>";
		echo '<form method="post" style="float:right;" action="index.php?sec=reporting&sec2=godmode/reporting/create_container">';
			html_print_submit_button (__('Create container'), 'create', false, 'class="sub next" style="margin-right:5px;margin-top: 15px;"');
		echo "</form>";
echo "</div>";

?>

<script type="text/javascript">
	function get_graphs_container (id_container,hash,time){
		$.ajax({
			async:false,
			type: "POST",
			url: "ajax.php",
			data: {"page" : "include/ajax/graph.ajax",
				"get_graphs_container" : 1,
				"id_container" : id_container,
				"hash" : hash,
				"time" : time,
				},
			success: function(data) {
				$("#div_"+hash).remove(); 
				$("#tgl_div_"+hash).prepend("<div id='div_"+hash+"' style='width: 100%;padding-left: 63px; padding-top: 7px;'>"+data+"</div>");
				
				if($('div[class *= graph]').length == 0  && $('div[class *= bullet]').length == 0 && $('div[id *= gauge_]').length == 0){
					$("#div_"+hash).remove();
				}
				
				$('div[class *= bullet]').css('margin-left','0');
				$('div[class *= graph]').css('margin-left','0');
				$('div[id *= gauge_]').css('width','100%');

				$('select[id *= period_container_'+hash+']').change(function() {
					var id = $(this).attr("id");
					if(!/unit/.test(id)){
						var time = $('select[id *= period_container_'+hash+']').val();
						get_graphs_container(id_container,hash,time);
					} 
				});
				
				$('input[id *= period_container_'+hash+']').keypress(function(e) {
					if(e.which == 13) {
						var time = $('input[id *= hidden-period_container_'+hash+']').val();
						get_graphs_container(id_container,hash,time);
       				}
				});
			}
		});
	}
	
	
    $(document).ready (function () {
		$('a[id *= tgl]').click(function(e) {
			var id = e.currentTarget.id;
			hash = id.replace("tgl_ctrl_","");
			var down = document.getElementById("image_"+hash).src;
			if (down.search("down") !== -1){
				var max_graph = "<?php echo $max_graph;?>";
				var id_container = $("#hidden-"+hash).val();
				get_graphs_container(id_container,hash,'0');
			} else {
				$("#div_"+hash).remove(); 
			}
		});
	});
</script>