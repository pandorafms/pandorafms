<?php

// Pandora FMS - https://pandorafms.com
// ==================================================
// Copyright (c) 2005-2023 Pandora FMS
// Please see https://pandorafms.com/community/ for full contribution list
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
ui_require_css_file('custom_graph');
$editGraph = (bool) get_parameter('edit_graph', 0);
$action = get_parameter('action', '');

if (isset($_GET['get_agent'])) {
    $id_agent = $_POST['id_agent'];
    if (isset($_POST['chunk'])) {
        $chunkdata = $_POST['chunk'];
    }
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


// Configuration form.
echo '<span id ="none_text" class="invisible">'.__('None').'</span>';
echo "<form  id='agentmodules' method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&tab=graph_editor&add_module=1&edit_graph=1&id=".$id_graph."'>";

echo "<table width='100%' cellpadding='4' cellpadding='4' class='databox filters max_floating_element_size'>";
echo '<tr>';
echo '<td class="w50p pdd_50px" id="select_multiple_modules_filtered">'.html_print_input(
    [
        'type'              => 'select_multiple_modules_filtered',
        'uniqId'            => 'modules',
        'class'             => 'flex flex-row',
        'searchBar'         => false,
        'placeholderAgents' => __('Search agent name'),
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
    echo '<thead>';
    echo '<tr>
	<th>'.__('P.').'</th>
	<th>'.__('Agent').'</th>
	<th>'.__('Module').'</th>
	<th>'.__('Label').'</th>
	<th>'.__('Weight').'</th>
	<th>'.__('Delete').'</th>
	<th>'.__('Sort').'</th>';
    echo '</thead>';
    echo '<tbody>';
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

        echo "<tr><td class='position $tdcolor'>$position_array[$a]</td>";
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

        echo '<td style="display: grid;">';

        echo html_print_input_image(
            'up',
            'images/arrow-up-white.png',
            'up',
            ($config['style'] !== 'pandora_black') ? 'filter: invert(100%)' : '',
            true,
            [
                'class'   => 'invert_filter main_menu_icon',
                'onclick' => 'reorder(\'up\', \''.$idgs_array[$a].'\', this)',
            ],
        );
        echo html_print_input_image(
            'down',
            'images/arrow-down-white.png',
            'down',
            ($config['style'] !== 'pandora_black') ? 'filter: invert(100%)' : '',
            true,
            [
                'class'   => 'invert_filter main_menu_icon',
                'onclick' => 'reorder(\'down\', \''.$idgs_array[$a].'\', this)',
            ]
        );

        echo '</td>';


        echo '</tr>';
    }

    echo '</tbody>';

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

    $("input.custom_checkbox_input:checked").each(function(i, val) {
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


function reorder(action, idElement, element) {
    var tr = $(element).parent().parent();
    switch (action) {
        case "up":
            changePosition(action, idElement)
            .then((data) => {
                if(data.success) {
                    $(tr).find('.position').html(parseInt($(tr).find('.position').html()) - 1);
                    $($(tr).prev()).find('.position').html(parseInt($($(tr).prev()).find('.position').html()) + 1);
                    $(tr).prev().insertAfter(tr);
                }
            })
            .catch((err) => {
                console.log(err);
            })
        break;

        case "down":
            changePosition(action, idElement)
            .then((data) => {
                if(data.success) {
                    $(tr).find('.position').html(parseInt($(tr).find('.position').html()) + 1);
                    $($(tr).next()).find('.position').html(parseInt(($(tr).next()).find('.position').html()) - 1);
                    $(tr).next().insertBefore(tr);
                }
            })
            .catch((err) => {
                console.log(err);
            })
        break;

        default:
        break;
    }
}

function changePosition(order, idElement) {
  return new Promise(function(resolve, reject) {
    $.ajax({
      method: "POST",
      url: "<?php echo ui_get_full_url('ajax.php'); ?>",
      dataType: "json",
      data: {
        page: "include/ajax/graph.ajax",
        sort_items: 1,
        order,
        id_graph: <?php echo $id_graph; ?>,
        id: idElement
      },
      success: function(data) {
        resolve(data);
      },
      error: function(error) {
        reject(error);
      }
    });
  });
}

</script>
