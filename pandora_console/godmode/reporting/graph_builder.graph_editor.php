<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
        AUDIT_LOG_ACL_VIOLATION,
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
                $items = db_get_all_rows_sql(
                    'SELECT id_gs, `field_order`
					 FROM tgraph_source
					 WHERE id_graph = '.$id_graph.'
					 ORDER BY `field_order`'
                );

            if ($items === false) {
                $items = [];
            }


            // Clean the repeated order values.
            $order_temp = 1;
            foreach ($items as $item) {
                db_process_sql_update(
                    'tgraph_source',
                    ['`field_order`' => $order_temp],
                    ['id_gs' => $item['id_rc']]
                );

                $order_temp++;
            }

                $items = db_get_all_rows_sql(
                    'SELECT id_gs, `field_order`
					 FROM tgraph_source
					 WHERE id_graph = '.$id_graph.'
					 ORDER BY `field_order`'
                );

            if ($items === false) {
                $items = [];
            }



            $temp = [];

            $temp = [];
            foreach ($items as $item) {
                // Remove the contents from the block to sort.
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
                db_process_sql_update(
                    'tgraph_source',
                    ['`field_order`' => ($order + 1)],
                    ['id_gs' => $id]
                );
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



$count_module_array = count($module_array);
if ($count_module_array > $config['items_combined_charts']) {
    ui_print_warning_message(
        __(
            'The maximum number of items in a chart is %d. You have %d elements, only first %d will be displayed.',
            $config['items_combined_charts'],
            $count_module_array,
            $config['items_combined_charts']
        )
    );
}

$table = new stdClass();
$table->width = '100%';
$table->colspan[0][0] = 3;
$table->size = [];

if (defined('METACONSOLE')) {
    $table->class = 'databox data';
    $table->head[0] = __('Sort items');
    $table->head_colspan[0] = 4;
    $table->headstyle[0] = 'text-align: center';
    $table->size[0] = '25%';
    $table->size[1] = '25%';
    $table->size[2] = '25%';
    $table->size[3] = '25%';
} else {
    $table->class = 'filter-table-adv';
    $table->size[0] = '50%';
    $table->size[1] = '50%';
}

$table->data[0][0] = html_print_label_input_block(
    __('Sort selected items'),
    html_print_select_style(
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
    )
);
$table->data[0][1] = html_print_label_input_block(
    __('Position'),
    html_print_input_text_extended(
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
    ).html_print_input_hidden('ids_items_to_sort', '', true)
);


$SortItems = "<form action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&edit_graph=1&id=".$id_graph."' method='post' onsubmit='return added_ids_sorted_items_to_hidden_input();'>";
$SortItems .= html_print_table($table, true);
$SortItems .= html_print_input_hidden('action', 'sort_items', true);
$SortItems .= html_print_div(
    [
        'class'   => 'action-buttons',
        'content' => html_print_submit_button(
            __('Sort'),
            'srcbutton',
            false,
            [
                'class' => 'mini',
                'icon'  => 'search',
                'mode'  => 'secondary',
            ],
            true
        ),
    ],
    true
);
$SortItems .= '</form>';

ui_toggle(
    $SortItems,
    '<span class="subsection_header_title">'.__('Sort items').'</span>',
    '',
    '',
    false,
    false,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph max_floating_element_size'
);


// Configuration form.
echo '<span id ="none_text" class="invisible">'.__('None').'</span>';
echo "<form  id='agentmodules' method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&add_module=1&edit_graph=1&id=".$id_graph."'>";

echo "<table width='100%' cellpadding='4' cellpadding='4' class='databox filters max_floating_element_size'>";
echo '<tr>';
echo '<td class="w50p pdd_50px" id="select_multiple_modules_filtered">'.html_print_input(
    [
        'type'      => 'select_multiple_modules_filtered',
        'uniqId'    => 'modules',
        'class'     => 'flex flex-row',
        'searchBar' => true,
    ]
).'</td>';
echo '</tr><tr>';
echo "<td colspan='3'>";
echo "<table cellpadding='4' class='filter-table-adv'><tr>";
echo '<td>';
echo html_print_label_input_block(
    __('Weight'),
    '<input type="text" name="weight" value="1" size=3>'
);
echo '</td>';
echo '</tr></table>';
echo '</td>';
echo '</tr><tr>';
echo "<td colspan='3' align='right'></td>";
echo '</tr></table>';
$ActionButtons[] = html_print_submit_button(
    __('Add'),
    'submit-add',
    false,
    [
        'class' => 'sub ok',
        'icon'  => 'next',
    ],
    true
);
html_print_action_buttons(
    implode('', $ActionButtons),
    ['type' => 'form_action']
);

echo '</form>';

// Modules table.
if ($count_module_array > 0) {
    echo "<table width='100%' cellpadding=4 cellpadding=4 class='databox filters info_table'>";
    echo '<tr>
	<th>'.__('P.').'</th>
	<th>'.__('Agent').'</th>
	<th>'.__('Module').'</th>
	<th>'.__('Label').'</th>
	<th>'.__('Weight').'</th>
	<th>'.__('Delete').'</th>
	<th>'.__('Sort').'</th>';
    $color = 0;
    for ($a = 0; $a < $count_module_array; $a++) {
        // Calculate table line color.
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
        echo '<div class="flex">';
        html_print_input_text('label', $label_array[$a], '', 30, 80, false, false);
        html_print_submit_button(
            __('Ok'),
            'btn',
            false,
            [
                'mode'  => 'mini',
                'class' => 'inputbuton',
            ]
        );
        echo '</div>';
        echo '</form>';

        echo '</tr></table>';
        echo '</td>';

        echo "<td class='$tdcolor' align=''>";
        echo '<table><tr>';

        echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&change_weight=1&id=".$id_graph.'&graph='.$idgs_array[$a]."'>";
        echo '<div class="flex">';
        html_print_input_text('weight', $weight_array[$a], '', 20, 10, false, false);
        html_print_submit_button(
            __('Ok'),
            'btn',
            false,
            [
                'mode'  => 'mini',
                'class' => 'inputbuton',
            ]
        );
        echo '</div>';
        echo '</form>';

        echo '</tr></table>';
        echo '</td>';
        echo "<td class='$tdcolor' align=''>";
        echo "<a href='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&tab=graph_editor&delete_module=1&id=".$id_graph.'&delete='.$idgs_array[$a]."'>".html_print_image('images/delete.svg', true, ['title' => __('Delete'), 'class' => 'invert_filter main_menu_icon']).'</a>';

        echo '</td>';

        echo '<td>';

        echo html_print_checkbox_extended('sorted_items[]', $idgs_array[$a], false, false, '', 'class="selected_check"', true);

        echo '</td>';


        echo '</tr>';
    }

    echo '</table>';
}

ui_require_jquery_file('pandora.controls');
ui_require_jquery_file('ajaxqueue');
ui_require_jquery_file('bgiframe');
ui_require_jquery_file('autocomplete');

?>
<script language="javascript" type="text/javascript">
$(document).ready (function () {
    $(document).data('text_for_module', $("#none_text").html());

    $("#button-submit-add").click(function() {
        if($('#filtered-module-modules-modules')[0].value == "" || $('#filtered-module-modules-modules')[0].value == "0") {
            alert("<?php echo __('Please, select a module'); ?>");
            return false;
        }

        var modules_selected = $(
            "#filtered-module-modules-modules"
        ).val();
        var agents_selected = $(
            "#filtered-module-agents-modules"
        ).val();

        $("#agentmodules").submit( function(eventObj) {
        $("<input />").attr("type", "hidden")
            .attr("value", agents_selected)
            .attr("name", "id_agents")
            .appendTo("#agentmodules");
        $("<input />").attr("type", "hidden")
            .attr("value", modules_selected)
            .attr("name", "id_modules")
            .appendTo("#agentmodules");
        return true;
    });
    });
});

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
