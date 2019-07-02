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

check_login();

$report_w = check_acl($config['id_user'], 0, 'RW');
$report_m = check_acl($config['id_user'], 0, 'RM');

if (!$report_w && !$report_m) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access graph builder'
    );
    include 'general/noaccess.php';
    exit;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_groups.php';

$editGraph = (bool) get_parameter('edit_graph', 0);
$action = get_parameter('action', '');

if (isset($_GET['get_agent'])) {
    $id_agent = $_POST['id_agent'];
    if (isset($_POST['chunk'])) {
        $chunkdata = $_POST['chunk'];
    }
}

switch ($action) {
    case 'sort_items':
                $resultOperationDB = null;
                $position_to_sort = (int) get_parameter('position_to_sort', 1);
                $ids_serialize = (string) get_parameter('ids_items_to_sort', '');
                $move_to = (string) get_parameter('move_to', 'after');

                $countItems = db_get_sql(
                    '
					SELECT COUNT(id_gs)
					FROM tgraph_source
					WHERE id_graph = '.$id_graph
                );

        if (($countItems < $position_to_sort) || ($position_to_sort < 1)) {
            $resultOperationDB = false;
        } else if (!empty($ids_serialize)) {
            $ids = explode('|', $ids_serialize);

            switch ($config['dbtype']) {
                case 'mysql':
                    $items = db_get_all_rows_sql(
                        '
								SELECT id_gs, `field_order`
								FROM tgraph_source
								WHERE id_graph = '.$id_graph.'
								ORDER BY `field_order`'
                    );
                break;
            }

            if ($items === false) {
                $items = [];
            }


            // Clean the repeated order values
            $order_temp = 1;
            foreach ($items as $item) {
                switch ($config['dbtype']) {
                    case 'mysql':
                        db_process_sql_update(
                            'tgraph_source',
                            ['`field_order`' => $order_temp],
                            ['id_gs' => $item['id_rc']]
                        );
                    break;
                }

                $order_temp++;
            }


            switch ($config['dbtype']) {
                case 'mysql':
                    $items = db_get_all_rows_sql(
                        '
								SELECT id_gs, `field_order`
								FROM tgraph_source
								WHERE id_graph = '.$id_graph.'
								ORDER BY `field_order`'
                    );
                break;
            }

            if ($items === false) {
                $items = [];
            }



            $temp = [];

            $temp = [];
            foreach ($items as $item) {
                // Remove the contents from the block to sort
                if (array_search($item['id_gs'], $ids) === false) {
                    $temp[$item['field_order']] = $item['id_gs'];
                }
            }

            $items = $temp;



            $sorted_items = [];
            foreach ($items as $pos => $id_unsort) {
                if ($pos == $position_to_sort) {
                    if ($move_to == 'after') {
                        $sorted_items[] = $id_unsort;
                    }

                    foreach ($ids as $id) {
                        $sorted_items[] = $id;
                    }

                    if ($move_to != 'after') {
                        $sorted_items[] = $id_unsort;
                    }
                } else {
                    $sorted_items[] = $id_unsort;
                }
            }

            $items = $sorted_items;



            foreach ($items as $order => $id) {
                switch ($config['dbtype']) {
                    case 'mysql':

                        db_process_sql_update(
                            'tgraph_source',
                            ['`field_order`' => ($order + 1)],
                            ['id_gs' => $id]
                        );
                    break;
                }
            }

            $resultOperationDB = true;
        } else {
            $resultOperationDB = false;
        }
    break;
}

if ($editGraph) {
    $graphRows = db_get_all_rows_sql(
        'SELECT t1.*,
		(SELECT t3.alias 
			FROM tagente t3 
			WHERE t3.id_agente = 
				(SELECT t2.id_agente 
					FROM tagente_modulo t2
					WHERE t2.id_agente_modulo = t1.id_agent_module)) 
		AS agent_name
		FROM tgraph_source t1
		WHERE t1.id_graph = '.$id_graph.' order by `field_order`'
    );
    $position_array = [];
    $module_array = [];
    $weight_array = [];
    $agent_array = [];
    $label_array = [];

    if ($graphRows === false) {
            $graphRows = [];
    }

    foreach ($graphRows as $graphRow) {
        $idgs_array[] = $graphRow['id_gs'];
        $module_array[] = $graphRow['id_agent_module'];
        $weight_array[] = $graphRow['weight'];
        $label_array[] = $graphRow['label'];
        $agent_array[] = $graphRow['agent_name'];
        $position_array[] = $graphRow['field_order'];
    }

    $graphInTgraph = db_get_row_sql('SELECT * FROM tgraph WHERE id_graph = '.$id_graph);
    $stacked = $graphInTgraph['stacked'];
    $period = $graphInTgraph['period'];
    $width = $graphInTgraph['width'];
    $height = $graphInTgraph['height'];

    $modules = implode(',', $module_array);
    $weights = implode(',', $weight_array);
}

// Modules table
if (count($module_array) > 0) {
    echo "<table width='100%' cellpadding=4 cellpadding=4 class='databox filters'>";
    echo '<tr>
	<th>'.__('P.').'</th>
	<th>'.__('Agent').'</th>
	<th>'.__('Module').'</th>
	<th>'.__('Label').'</th>
	<th>'.__('Weight').'</th>
	<th>'.__('Delete').'</th>
	<th>'.__('Sort').'</th>';
    $color = 0;
    for ($a = 0; $a < count($module_array); $a++) {
        // Calculate table line color
        if ($color == 1) {
            $tdcolor = 'datos';
            $color = 0;
        } else {
            $tdcolor = 'datos2';
            $color = 1;
        }

        echo "<tr><td class='$tdcolor'>$position_array[$a]</td>";
        echo "<td class='$tdcolor'>".$agent_array[$a].'</td>';
        echo "<td class='$tdcolor'>";
        echo modules_get_agentmodule_name($module_array[$a]).'</td>';

        echo "<td class='$tdcolor' align=''>";
        echo '<table><tr>';

        echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&change_label=1&id=".$id_graph.'&graph='.$idgs_array[$a]."'>";
        html_print_input_text('label', $label_array[$a], '', 20, 30, false, false);
        html_print_submit_button('Ok', 'btn', false, '', false);
        echo '</form>';

        echo '</tr></table>';
        echo '</td>';

        echo "<td class='$tdcolor' align=''>";
        echo '<table><tr>';

        echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&change_weight=1&id=".$id_graph.'&graph='.$idgs_array[$a]."'>";
        html_print_input_text('weight', $weight_array[$a], '', 20, 10, false, false);
        html_print_submit_button('Ok', 'btn', false, '', false);
        echo '</form>';

        echo '</tr></table>';
        echo '</td>';
        echo "<td class='$tdcolor' align=''>";
        echo "<a href='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&delete_module=1&id=".$id_graph.'&delete='.$idgs_array[$a]."'>".html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';

        echo '</td>';

        echo '<td>';

        echo html_print_checkbox_extended('sorted_items[]', $idgs_array[$a], false, false, '', 'class="selected_check"', true);

        echo '</td>';


        echo '</tr>';
    }

    echo '</table>';
}


$table = new stdClass();
$table->width = '100%';
$table->colspan[0][0] = 3;
$table->size = [];
$table->size[0] = '25%';
$table->size[1] = '25%';
$table->size[2] = '25%';
$table->size[3] = '25%';
if (defined('METACONSOLE')) {
    $table->class = 'databox data';
    $table->head[0] = __('Sort items');
    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
} else {
    $table->data[0][0] = '<b>'.__('Sort items').'</b>';
}

$table->data[1][0] = __('Sort selected items');
$table->data[1][1] = html_print_select_style(
    [
        'before' => __('before to'),
        'after'  => __('after to'),
    ],
    'move_to',
    '',
    '',
    '',
    '',
    0,
    true
);
$table->data[1][2] = html_print_input_text_extended(
    'position_to_sort',
    1,
    'text-position_to_sort',
    '',
    3,
    10,
    false,
    "only_numbers('position_to_sort');",
    '',
    true
);
$table->data[1][2] .= html_print_input_hidden('ids_items_to_sort', '', true);
$table->data[1][3] = html_print_submit_button(__('Sort'), 'sort_submit', false, 'class="sub upd"', true);
$table->data[1][4] = html_print_input_hidden('action', 'sort_items', true);

echo "<form action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&edit_graph=1&id=".$id_graph."' method='post' onsubmit='return added_ids_sorted_items_to_hidden_input();'>";
html_print_table($table);
echo '</form>';

echo '<br>';


// Configuration form
echo '<span id ="none_text" style="display: none;">'.__('None').'</span>';
echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&add_module=1&edit_graph=1&id=".$id_graph."'>";

echo "<table width='100%' cellpadding='4' cellpadding='4' class='databox filters'>";
echo '<tr>';
echo "<td colspan='3'>".__('Filter group').'</td>';
echo '</tr><tr>';
echo "<td colspan='3'>".html_print_select_groups(
    $config['id_user'],
    ($report_w == true) ? 'RW' : (($report_m == true) ? 'RM' : 'RW'),
    true,
    'group',
    '',
    'filterByGroup($(\'#group\').val());',
    '',
    0,
    true
).'</td>';
echo '</tr><tr>';
echo "<td style='vertical-align: top;'>".__('Agents').'</td>';
echo '<td></td>';
echo "<td style='vertical-align: top;'>".__('Modules').'</td>';
echo '</tr><tr>';
echo '<td style="width: 50%;">'.html_print_select(
    agents_get_group_agents(),
    'id_agents[]',
    0,
    false,
    '',
    '',
    true,
    true,
    true,
    'w100p',
    false,
    ''
).'</td>';
echo "<td style='width: 3em;vertical-align: center; text-align: center;'>".html_print_image('images/darrowright.png', true).'</td>';
echo '<td style="width: 50%;">'.html_print_select(
    [],
    'module[]',
    0,
    false,
    '',
    0,
    true,
    true,
    true,
    'w100p',
    false,
    ''
).'</td>';
echo '</tr><tr>';
echo "<td colspan='3'>";
echo "<table cellpadding='4'><tr>";
echo '<td>'.__('Weight').'</td>';
echo "<td><input type='text' name='weight' value='1' size=3></td>";
echo '</tr></table>';
echo '</td>';
echo '</tr><tr>';
echo "<td colspan='3' align='right'></td>";
echo '</tr></table>';
echo "<div style='width:100%'><input style='float:right;' id='submit-add' type=submit name='store' class='sub add' value='".__('Add')."'></div></form>";

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
ui_require_jquery_file('autocomplete');

?>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
    $(document).data('text_for_module', $("#none_text").html());
    
    $("#id_agents").change(agent_changed_by_multiple_agents);
    
    
    $("#submit-add").click(function() {
        $('#module').map(function(){
            if ($(this).val() != "0" )
                $(this).prop('selected', true);
        });
        
        if($('#module')[0].childElementCount == 1 && ($('#module')[0].value == "" || $('#module')[0].value == "0")) {
            alert("<?php echo __('Please, select a module'); ?>");
            return false;
        }
    });
});

function filterByGroup(idGroup) {
    $('#id_agents').empty ();
    $('#module').empty();
    $("#module").append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None'); ?>'));
    
    jQuery.post ("ajax.php",
            {"page" : "godmode/groups/group_list",
            "get_group_agents" : 1,
            "id_group" : idGroup,
            // Add a key prefix to avoid auto sorting in js object conversion
            "keys_prefix" : "_"
            },
            function (data, status) {
                i = 0
                jQuery.each (data, function (id, value) {
                    // Remove keys_prefix from the index
                    id = id.substring(1);
                    
                    i++;
                    $("#id_agents").append ($("<option></option>").attr ("value", id).html (value));
                });
                
                if (i == 0) {
                    $("#id_agents").append ($("<option></option>").attr ("value", 0).html ('<?php echo __('None'); ?>'));
                }
            },
            "json"
        );
}

function added_ids_sorted_items_to_hidden_input() {
    var ids = '';
    var first = true;
    
    $("input.selected_check:checked").each(function(i, val) {
        if (!first)
            ids = ids + '|';
        first = false;
        
        ids = ids + $(val).val();
    });
    
    $("input[name='ids_items_to_sort']").val(ids);
    
    if (ids == '') {
        alert("<?php echo __('Please select any item to order'); ?>");
        
        return false;
    }
    else {
        return true;
    }
}
</script>
