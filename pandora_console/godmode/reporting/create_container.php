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
require_once ($config['homedir'] . '/include/functions_custom_graphs.php');

$id_container = get_parameter('id',0);
$offset = (int) get_parameter ('offset',0);

if (is_ajax()){
    $add_single = (bool) get_parameter('add_single',0);
    $add_custom = (bool) get_parameter('add_custom',0);
	$add_dynamic = (bool) get_parameter('add_dynamic',0);
    $id_container2 = get_parameter('id_container',0);
    
    if($add_single){
        $id_agent = get_parameter('id_agent');
        $id_agent_module = get_parameter('id_agent_module');
        $time_lapse = get_parameter('time_lapse');
        $only_avg = (int) get_parameter('only_avg');
        
        $values = array(
            'id_container' => $id_container2,
            'type' => "simple_graph",
            'id_agent' => $id_agent,
            'id_agent_module' => $id_agent_module,
            'time_lapse' => $time_lapse,
            'only_average' => $only_avg);

        $id_item = db_process_sql_insert('tcontainer_item', $values);
        return;
    }
    
    if($add_custom){
        $time_lapse = get_parameter('time_lapse');
        $id_custom = get_parameter('id_custom');
        
        $values = array(
            'id_container' => $id_container2,
            'type' => "custom_graph",
            'time_lapse' => $time_lapse,
            'id_graph' => $id_custom);

        $id_item = db_process_sql_insert('tcontainer_item', $values);
		return;
    }
	
	if($add_dynamic) {
        $time_lapse = get_parameter('time_lapse');
		$group = get_parameter('group',0);
		$module_group= get_parameter('module_group',0);
        $agent_alias = get_parameter('agent_alias','');
		$module_name = get_parameter('module_name','');
		$tag = get_parameter('tag',0);

		$values = array(
    		'id_container' => $id_container2,
        	'type' => "dynamic_graph",
			'time_lapse' => $time_lapse,
        	'id_group' => $group,
        	'id_module_group' => $module_group,
			'agent' => $agent_alias,
			'module' => $module_name,
        	'id_tag' => $tag);
		
		$id_item = db_process_sql_insert('tcontainer_item', $values);
		return;
	}
}

$add_container = (bool) get_parameter ('add_container',0);
$edit_container = (bool) get_parameter ('edit_container',0);
$update_container = (bool) get_parameter ('update_container',0);
$delete_item = (bool) get_parameter ('delete_item',0);

if ($edit_container) {
    $name = io_safe_input(get_parameter ('name',''));
    if (!empty($name)){
        $id_parent = get_parameter ('id_parent',0);
        $description = io_safe_input(get_parameter ('description',''));
        $id_group = get_parameter ('container_id_group',0);
    }else{
        $tcontainer = db_get_row_sql("SELECT * FROM tcontainer WHERE id_container = " . $id_container);
        $name = $tcontainer['name'];
        $id_parent = $tcontainer['parent'];
        $description = $tcontainer['description'];
        $id_group = $tcontainer['id_group'];
    }
	
}

if($add_container){
    $values = array(
        'name' => $name,
        'description' => $description,
        'parent' => $id_parent,
        'id_group' => $id_group);
    $id_container = db_process_sql_insert('tcontainer', $values);
}

if($update_container){
    if($id_container === $id_parent){
        $success = false;
    } else {
        $values = array(
            'name' => $name,
            'description' => $description,
            'parent' => $id_parent,
            'id_group' => $id_group);
        $success = db_process_sql_update('tcontainer', $values,array('id_container' => $id_container));
    }
}


if($delete_item){
    $id_item = get_parameter('id_item',0);
    $success = db_process_sql_delete('tcontainer_item', array('id_ci' => $id_item));
}

$buttons['graph_container'] = array('active' => false,
	'text' => '<a href="index.php?sec=reporting&sec2=godmode/reporting/graph_container">' .
		html_print_image("images/graph-container.png", true, array ("title" => __('Graph container'))) . '</a>');

// Header
ui_print_page_header (__('Create container'), "", false, "", false, $buttons);

if($add_container){
    ui_print_result_message($id_container, __('Container stored successfully'), __('There was a problem storing container'));
}

if($update_container){
    ui_print_result_message($success, __("Update the container"), __("Bad update the container"));
}

echo "<table width='100%' cellpadding=4 cellspacing=4 class='databox filters'>";
if($edit_container){
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&update_container=1&id=" . $id_container . "'>";
} else {
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&add_container=1'>";
}

echo "<tr>";
echo "<td class='datos' style='width: 12%;'><b>".__('Name')."</b></td>";
if($id_container === '1'){
    echo "<td class='datos' style='width: 27%;'><input type='text' name='name' size='30' disabled='1'";
} else {
    echo "<td class='datos' style='width: 27%;'><input type='text' name='name' size='30' ";
}

if ($edit_container) {
	echo "value='" . io_safe_output($name) . "'";
}
echo "></td>";
$own_info = get_user_info ($config['id_user']);
if ($own_info['is_admin'] || check_acl ($config['id_user'], 0, "PM"))
	$return_all_groups = true;
else	
	$return_all_groups = false;
	
echo "<td style='width: 12%;'><b>".__('Group')."</b></td><td>";
if($id_container === '1'){
    echo html_print_select_groups($config['id_user'], '', $return_all_groups, 'container_id_group', $id_group, '', '', '', true,false,true,'',true);
} else {
    echo html_print_select_groups($config['id_user'], '', $return_all_groups, 'container_id_group', $id_group, '', '', '', true,false,true,'',false);
}
    
echo "</td></tr>";

echo "<tr>";
echo "<td class='datos2'><b>".__('Description')."</b></td>";
if($id_container === '1'){
    echo "<td class='datos2' colspan=3><textarea name='description' style='height:45px;' cols=95 rows=2 disabled>";
} else {
    echo "<td class='datos2' colspan=3><textarea name='description' style='height:45px;' cols=95 rows=2>";
}

if ($edit_container) {
	echo io_safe_output($description);
}

echo "</textarea>";
echo "</td></tr>";
$container = folder_get_folders();
$tree = folder_get_folders_tree_recursive($container);
$containers_tree = folder_flatten_tree_folders($tree,0);
$containers_tree = folder_get_select($containers_tree);

unset($containers_tree[$id_container]);

echo "<tr>";
echo "<td class='datos2'><b>".__("Parent container")."</b></td>";
if($id_container === '1'){
    echo "<td class='datos2'>" . html_print_select ($containers_tree, "id_parent", $id_parent, 
    '', __('none'), 0, true,'',false,'w130',true,'width: 195px','');
} else {
    echo "<td class='datos2'>" . html_print_select ($containers_tree, "id_parent", $id_parent, 
    '', __('none'), 0, true,'',false,'w130','','width: 195px','');
}


echo "</td></tr>";


echo "</table>";

if ($edit_container) {
    if($id_container !== '1'){
        echo "<div style='width:100%'><input style='float:right;' type=submit name='store' disbaled class='sub upd' value='".__('Update')."'></div>";
    }
}
else {
	echo "<div style='width:100%'><input style='float:right;' type=submit name='store' class='sub next' value='".__('Create')."'></div>";
}

echo "</form>";

echo "</br>";
echo "</br>";
echo "</br>";

if($edit_container){
	$period = SECONDS_15DAYS;
	$periods = array ();
	$periods[-1] = __('custom');
	$periods[SECONDS_1HOUR] = __('1 hour');
	$periods[SECONDS_2HOUR] = sprintf(__('%s hours'), '2 ');
	$periods[SECONDS_6HOURS] = sprintf(__('%s hours'), '6 ');
	$periods[SECONDS_12HOURS] = sprintf(__('%s hours'), '12 ');
	$periods[SECONDS_1DAY] = __('1 day');
	$periods[SECONDS_2DAY] = sprintf(__('%s days'), '2 ');
	$periods[SECONDS_5DAY] = sprintf(__('%s days'), '5 ');
	$periods[SECONDS_1WEEK] = __('1 week');
	$periods[SECONDS_15DAYS] = __('15 days');
	$periods[SECONDS_1MONTH] = __('1 month');
	
    $single_table = "<table width='100%' cellpadding=4 cellspacing=4>";
        $single_table .= "<tr id='row_time_lapse' style='' class='datos'>";
            $single_table .= "<td style='font-weight:bold;width: 12%;'>";
                $single_table .= __('Time lapse');
                $single_table .= ui_print_help_tip(__('This is the interval or period of time with which the graph data will be obtained. For example, a week means data from a week ago from now. '),true);
            $single_table .= "</td>";
            $single_table .= "<td>";
                $single_table .= html_print_extended_select_for_time('period_single', $period,
                    '', '', '0', 10, true,false,true,'',false,$periods);
            $single_table .= "</td>";
        $single_table .= "</tr>";
        
        $single_table .= "<tr id='row_agent' style='' class='datos'>";
            $single_table .= "<td style='font-weight:bold;width: 12%;'>";
                $single_table .= __('Agent');
            $single_table .= "</td>";
            $single_table .= "<td>";
                $params = array();
                
                $params['show_helptip'] = false;
                $params['input_name'] = 'agent';
                $params['value'] = '';
                $params['return'] = true;
                
                $params['javascript_is_function_select'] = true;
                $params['selectbox_id'] = 'id_agent_module';
                $params['add_none_module'] = true;
                $params['use_hidden_input_idagent'] = true;
                $params['hidden_input_idagent_id'] = 'hidden-id_agent';
                
                
                $single_table .= ui_print_agent_autocomplete_input($params);
            $single_table .= "</td>";
        $single_table .= "</tr>";
        
        $single_table .= "<tr id='row_module' style='' class='datos'>";
            $single_table .= "<td style='font-weight:bold;width: 12%;'>";
                $single_table .= __('Module');
            $single_table .= "</td>";
            $single_table .= "<td>";
                if ($idAgent) {
                    $single_table .= html_print_select_from_sql($sql_modules, 'id_agent_module', $idAgentModule, '', '', '0',true);
                } else {
                    $single_table .= "<select style='max-width: 180px' id='id_agent_module' name='id_agent_module' disabled='disabled'>";
                        $single_table .= "<option value='0'>";
                            $single_table .= __('Select an Agent first');
                        $single_table .= "</option>";
                    $single_table .= "</select>";
                }
            $single_table .= "</td>";
        $single_table .= "</tr>";
        
        $single_table .= "<tr id='row_only_avg' style='' class='datos'>";
            $single_table .= "<td style='font-weight:bold;'>";
                $single_table .= __('Only average');
            $single_table .= "</td>";
            $single_table .= "<td>";
                $single_table .= html_print_checkbox('only_avg', 1, true,true);
            $single_table .= "</td>";
        $single_table .= "</tr>";
        $single_table .= "<tr>";
            $single_table .= "<td >";
            $single_table .= "</td>";
            $single_table .= "<td style='float:right;'>";
                $single_table .= "<input style='float:right;' type=submit name='add_single' class='sub add' value='".__('Add item')."'>";
            $single_table .= "</td>";
        $single_table .= "</tr>";
    $single_table .= "</table>";

    echo "<table width='100%' cellpadding=4 cellspacing=4 class='databox filters'>";
        echo "<tr>";
            echo "<td>";
                echo ui_toggle($single_table,'Simple module graph', '', true, true);
            echo "</td>";
        echo "</tr>";
    echo "</table>";
        
    $table = new stdClass();
    $table->id = 'custom_graph_table';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'dat';

    $table->styleTable = 'font-weight: bold;';
    $table->style[0] = 'width: 12%';
    $table->data = array();

    $data = array();
    $data[0] = __('Time lapse');
    $data[0] .= ui_print_help_tip(__('This is the interval or period of time with which the graph data will be obtained. For example, a week means data from a week ago from now. '),true);
    $data[1] = html_print_extended_select_for_time('period_custom', $period,'', '', '0', 10, true,false,true,'',false,$periods);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] = __('Custom graph');

    $list_custom_graphs = custom_graphs_get_user ($config['id_user'], false, true, "RR");

    $graphs = array();
    foreach ($list_custom_graphs as $custom_graph) {
        $graphs[$custom_graph['id_graph']] = $custom_graph['name'];
    }

    $data[1] = html_print_select($graphs, 'id_custom_graph',$idCustomGraph, '', __('None'), 0,true);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] = "";
    $data[1] = "<input style='float:right;' type=submit name='add_custom' class='sub add' value='".__('Add item')."'>";
    $table->data[] = $data;
    $table->rowclass[] = '';

    echo "<table width='100%' cellpadding=4 cellspacing=4 class='databox filters'>";
        echo "<tr>";
            echo "<td>";
                echo ui_toggle(html_print_table($table, true),'Custom graph', '', true, true);
            echo "</td>";
        echo "</tr>";
    echo "</table>";

    unset($table);

    $table = new stdClass();
    $table->id = 'dynamic_rules_table';
    $table->width = '100%';
    $table->cellspacing = 4;
    $table->cellpadding = 4;
    $table->class = 'dat';

    $table->styleTable = 'font-weight: bold;';
    $table->style[0] = 'width: 12%';
    $table->data = array();

    $data = array();
    $data[0] = __('Time lapse');
    $data[0] .= ui_print_help_tip(__('This is the interval or period of time with which the graph data will be obtained. For example, a week means data from a week ago from now. '),true);
    $data[1] = html_print_extended_select_for_time('period_dynamic', $period,'', '', '0', 10, true,false,true,'',false,$periods);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] = __('Group');
    $data[1] = html_print_select_groups($config['id_user'], 'RW', $return_all_groups, 'container_id_group', $id_group, '', '', '', true);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] = __('Module group');
    $data[1] = html_print_select_from_sql(
        "SELECT * FROM tmodule_group ORDER BY name",
        'combo_modulegroup', $modulegroup, '',__('All'),false,true);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] =  __('Agent');
    $data[1] = html_print_input_text ('text_agent', $textAgent, '', 30, 100, true);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] =  __('Module');
    $data[1] = html_print_input_text ('text_agent_module', $textModule, '', 30, 100, true);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] =  __('Tag');
    $select_tags = tags_search_tag (false, false, true);
    $data[1] = html_print_select ($select_tags, 'tag',
    	$tag, '', __('Any'), 0, true, false, false);
    $table->data[] = $data;
    $table->rowclass[] = '';

    $data = array();
    $data[0] = "";
    $data[1] = "<input style='float:right;' type=submit name='add_dynamic' class='sub add' value='".__('Add item')."'>";
    $table->data[] = $data;
    $table->rowclass[] = '';

    echo "<table width='100%' cellpadding=4 cellspacing=4 class='databox filters'>";
        echo "<tr>";
            echo "<td>";
                echo ui_toggle(html_print_table($table, true),'Dynamic rules for simple module graph', '', true, true);
            echo "</td>";
        echo "</tr>";
    echo "</table>";
    
    $total_item = db_get_all_rows_sql("SELECT count(*) FROM tcontainer_item WHERE id_container = " . $id_container);
    $result_item =  db_get_all_rows_sql("SELECT * FROM tcontainer_item WHERE id_container = " . $id_container . " LIMIT 10 OFFSET ". $offset);
    
    if(!$result_item){
        echo "<div class='nf'>".__('There are no defined item container')."</div>";
    } else {
        ui_pagination ($total_item[0]['count(*)'],false,$offset,10);
        $table = new stdClass();
    	$table->width = '100%';
    	$table->class = 'databox data';
        $table->id = 'item_table';
    	$table->align = array ();
    	$table->head = array ();
    	$table->head[0] = __('Agent/Module');
    	$table->head[1] = __('Custom graph');
    	$table->head[2] = __('Group');
    	$table->head[3] = __('M.Group');
        $table->head[4] = __('Agent');
        $table->head[5] = __('Module');
        $table->head[6] = __('Tag');
    	$table->head[7] = __('Delete');
    	
    	$table->data = array ();
        
        
    	foreach ($result_item as $item) {
            $data = array ();
			switch ($item['type']) {
				case 'simple_graph':
					$agent_alias =  ui_print_truncate_text(agents_get_alias($item['id_agent'],20,false));
					$module_name = ui_print_truncate_text(modules_get_agentmodule_name($item['id_agent_module']),20,false);
					$module_name = 
					$data[0] = $agent_alias . " / " .$module_name;
					$data[1] = '';
					$data[2] = '';
		            $data[3] = '';
		            $data[4] = '';
		            $data[5] = '';
		            $data[6] = '';
					break;
				
				case 'custom_graph':
					$data[0] = '';
					$name =  db_get_value_filter('name','tgraph',array('id_graph' => $item['id_graph']));
	                $data[1] = ui_print_truncate_text(io_safe_output($name),35,false);
					$data[2] = '';
		            $data[3] = '';
		            $data[4] = '';
		            $data[5] = '';
		            $data[6] = '';
					break;
				
				case 'dynamic_graph':
					$data[0] = '';
					$data[1] = '';
					
					$data[2] = ui_print_group_icon($item['id_group'],true);
					if ($item['id_module_group'] === '0') {
						$data[3] = 'All';
					} else {
						$data[3] = io_safe_output(db_get_value_filter('name','tmodule_group',array('id_mg' => $item['id_module_group'])));
						
					}
					$data[4] = io_safe_output($item['agent']);
					$data[5] = io_safe_output($item['module']);
					if ($item['id_tag'] === '0') {
						$data[6] = 'Any';
					} else {
						$data[6] = io_safe_output(db_get_value_filter('name','ttag',array('id_tag' => $item['id_tag'])));
					}
					break;
				
			}
            $data[7] = '<a href="index.php?sec=reporting&sec2=godmode/reporting/create_container&edit_container=1&delete_item=1&id_item='
                .$item['id_ci'].'&id='.$id_container.'" onClick="if (!confirm(\''.__('Are you sure?').'\'))
                return false;">' . html_print_image("images/cross.png", true, array('alt' => __('Delete'), 'title' => __('Delete'))) . '</a>';
            
            array_push ($table->data, $data);
        }
        html_print_table ($table);
    }

    
}

echo html_print_input_hidden('id_agent', 0);
?>

<script type="text/javascript">
    $(document).ready (function () {
        $("input[name=add_single]").click (function () {
            var id_agent_module = $("#id_agent_module").val();
			if(id_agent_module !== '0'){
				var id_agent = $("#hidden-id_agent").attr('value');
				var time_lapse = $("#hidden-period_single").attr('value');
	            var only_avg = $("#checkbox-only_avg").prop("checked");
	            var id_container = <?php echo $id_container; ?>;
				jQuery.post ("ajax.php",
	    			{"page" : "godmode/reporting/create_container",
	    			"add_single" : 1,
	                "id_agent" : id_agent,
	                "id_agent_module" : id_agent_module,
	                "time_lapse" : time_lapse,
	                "only_avg" : only_avg,
	                "id_container" : id_container,
	    			},
	                function (data, status) {
	                    var url = location.href.replace('&update_container=1', "");
	                    url = url.replace('&delete_item=1', "");
	                    location.href = url.replace('&add_container=1', "&id="+id_container);
	                }
	            );
			}
        });
        
        
        $("input[name=add_custom]").click (function () {
            var id_custom = $("#id_custom_graph").val();
			if (id_custom !== '0'){
				var time_lapse = $("#hidden-period_custom").attr('value');
            	var id_container = <?php echo $id_container; ?>;
            	jQuery.post ("ajax.php",
    				{"page" : "godmode/reporting/create_container",
    				"add_custom" : 1,
                	"time_lapse" : time_lapse,
                	"id_custom" : id_custom,
                	"id_container" : id_container,
    				},
                	function (data, status) {
                    	var url = location.href.replace('&update_container=1', "");
                    	url = url.replace('&delete_item=1', "");
                    	location.href = url.replace('&add_container=1', "&id="+id_container);
                	}
            	);
			}
        });
		
		$("input[name=add_dynamic]").click (function () {
			var agent_alias = $("#text-text_agent").val();
			var module_name = $("#text-text_agent_module").val();
			var time_lapse = $("#hidden-period_dynamic").attr('value');
			var group = $("#container_id_group1").val();
			var module_group = $("#combo_modulegroup").val();
			var tag = $("#tag").val();
	        var id_container = <?php echo $id_container; ?>;
            jQuery.post ("ajax.php",
    			{"page" : "godmode/reporting/create_container",
    			"add_dynamic" : 1,
            	"time_lapse" : time_lapse,
            	"group" : group,
				"module_group" : module_group,
				"agent_alias" : agent_alias,
				"module_name" : module_name,
				"tag" : tag,
            	"id_container" : id_container,
    			},
            	function (data, status) {
                	var url = location.href.replace('&update_container=1', "");
                	url = url.replace('&delete_item=1', "");
                	location.href = url.replace('&add_container=1', "&id="+id_container);
            	}
            );

        });
    });

</script>