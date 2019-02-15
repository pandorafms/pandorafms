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

require_once 'include/functions_custom_graphs.php';

if (is_ajax()) {
    $search_agents = (bool) get_parameter('search_agents');

    if ($search_agents) {
        include_once 'include/functions_agents.php';

        $id_agent = (int) get_parameter('id_agent');
        $string = (string) get_parameter('q');
        // q is what autocomplete plugin gives
        $id_group = (int) get_parameter('id_group');

        $filter = [];
        $filter[] = '(nombre COLLATE utf8_general_ci LIKE "%'.$string.'%" OR direccion LIKE "%'.$string.'%" OR comentarios LIKE "%'.$string.'%")';
        $filter['id_grupo'] = $id_group;

        $agents = agents_get_agents($filter, ['nombre', 'direccion']);
        if ($agents === false) {
            return;
        }

        foreach ($agents as $agent) {
            echo $agent['nombre'].'|'.$agent['direccion']."\n";
        }

        return;
    }

    return;
}

check_login();

if (! check_acl($config['id_user'], 0, 'RW') && ! check_acl($config['id_user'], 0, 'RM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access graph builder'
    );
    include 'general/noaccess.php';
    exit;
}

if ($edit_graph) {
    $graphInTgraph = db_get_row_sql('SELECT * FROM tgraph WHERE id_graph = '.$id_graph);
    $stacked = $graphInTgraph['stacked'];
    $period = $graphInTgraph['period'];
    $id_group = $graphInTgraph['id_group'];
    $check = false;
    $percentil = $graphInTgraph['percentil'];
    $summatory_series = $graphInTgraph['summatory_series'];
    $average_series = $graphInTgraph['average_series'];
    $modules_series = $graphInTgraph['modules_series'];
    $fullscale = $graphInTgraph['fullscale'];

    if ($stacked == CUSTOM_GRAPH_BULLET_CHART_THRESHOLD) {
        $stacked = CUSTOM_GRAPH_BULLET_CHART;
        $check = true;
    }
} else {
    $id_agent = 0;
    $id_module = 0;
    $id_group = 0;
    $period = SECONDS_1DAY;
    $factor = 1;
    $stacked = 4;
    $check = false;
    $percentil = 0;
    $summatory_series = 0;
    $average_series = 0;
    $modules_series = 0;
    if ($config['full_scale_option'] == 1) {
        $fullscale = 1;
    } else {
        $fullscale = 0;
    }
}

// -----------------------
// CREATE/EDIT GRAPH FORM
// -----------------------
echo "<table width='100%' cellpadding=4 cellspacing=4 class='databox filters'>";

if ($edit_graph) {
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&update_graph=1&id=".$id_graph."'>";
} else {
    echo "<form method='post' action='index.php?sec=reporting&sec2=godmode/reporting/graph_builder&edit_graph=1&add_graph=1'>";
}

echo '<tr>';
echo "<td class='datos'><b>".__('Name').'</b></td>';
echo "<td class='datos'><input type='text' name='name' size='25' ";
if ($edit_graph) {
    echo "value='".$graphInTgraph['name']."' ";
}

echo '>';

$own_info = get_user_info($config['id_user']);

echo '<td><b>'.__('Group').'</b></td><td>';
if (check_acl($config['id_user'], 0, 'RW')) {
    echo html_print_select_groups($config['id_user'], 'RW', true, 'graph_id_group', $id_group, '', '', '', true);
} else if (check_acl($config['id_user'], 0, 'RM')) {
    echo html_print_select_groups($config['id_user'], 'RM', true, 'graph_id_group', $id_group, '', '', '', true);
}

echo '</td></tr>';
echo '<tr>';
echo "<td class='datos2'><b>".__('Description').'</b></td>';
echo "<td class='datos2' colspan=3><textarea name='description' style='height:45px;' cols=55 rows=2>";
if ($edit_graph) {
    echo $graphInTgraph['description'];
}

echo '</textarea>';
echo '</td></tr>';
if ($stacked == CUSTOM_GRAPH_GAUGE) {
    $hidden = ' style="display:none;" ';
} else {
    $hidden = '';
}

echo '<tr>';
echo "<td class='datos'>";
echo '<b>'.__('Period').'</b></td>';
echo "<td class='datos'>";
html_print_extended_select_for_time('period', $period, '', '', '0', 10);
echo "</td><td class='datos2'>";
echo '<b>'.__('Type of graph').'</b></td>';
echo "<td class='datos2'> <div style='float:left;display:inline-block'>";

require_once $config['homedir'].'/include/functions_graph.php';

$stackeds = [
    CUSTOM_GRAPH_AREA         => __('Area'),
    CUSTOM_GRAPH_STACKED_AREA => __('Stacked area'),
    CUSTOM_GRAPH_LINE         => __('Line'),
    CUSTOM_GRAPH_STACKED_LINE => __('Stacked line'),
    CUSTOM_GRAPH_BULLET_CHART => __('Bullet chart'),
    CUSTOM_GRAPH_GAUGE        => __('Gauge'),
    CUSTOM_GRAPH_HBARS        => __('Horizontal bars'),
    CUSTOM_GRAPH_VBARS        => __('Vertical bars'),
    CUSTOM_GRAPH_PIE          => __('Pie'),
];
html_print_select($stackeds, 'stacked', $stacked);

echo '</div></td></tr>';

echo "<tr><td class='datos2'><b>".__('Percentil').'</b></td>';
echo "<td class='datos2'>".html_print_checkbox('percentil', 1, $percentil, true).'</td>';
echo "<td class='datos2'><div id='thresholdDiv' name='thresholdDiv'><b>".__('Equalize maximum thresholds').'</b>'.ui_print_help_tip(__('If an option is selected, all graphs will have the highest value from all modules included in the graph as a maximum threshold'), true);
    html_print_checkbox('threshold', CUSTOM_GRAPH_BULLET_CHART_THRESHOLD, $check, false, false, '', false);
echo '</div></td></tr>';
echo "<tr><td class='datos2'><b>".__('Add summatory series').ui_print_help_tip(
    __(
        'Adds synthetic series to the graph, using all module 
	values to calculate the summation and/or average in each time interval. 
	This feature could be used instead of synthetic modules if you only want to see a graph.'
    ),
    true
).'</b></td>';
echo "<td class='datos2'>".html_print_checkbox('summatory_series', 1, $summatory_series, true)."</td>
<td class='datos2'><b>".__('Add average series').'</b></td>';
echo "<td class='datos2'>".html_print_checkbox('average_series', 1, $average_series, true).'</td></tr>';
echo "<tr><td class='datos2'><b>".__('Modules and series').'</b></td>';

echo "<td class='datos2'>".html_print_checkbox('modules_series', 1, $modules_series, true).'</td>';
echo "<td class='datos2'><b>".__('Show full scale graph (TIP)').ui_print_help_tip(__('This option may cause performance issues'), true).'</td>';
echo "<td class='datos2'>".html_print_checkbox('fullscale', 1, $fullscale, true).'</td>';
echo '</tr>';
echo '</table>';

if ($edit_graph) {
    echo "<div style='width:100%'><input style='float:right;' type=submit name='store' class='sub upd' value='".__('Update')."'></div>";
} else {
    echo "<div style='width:100%'><input style='float:right;' type=submit name='store' class='sub next' value='".__('Create')."'></div>";
}

echo '</form>';


echo '<script type="text/javascript">
	$(document).ready(function() {
		if ($("#stacked").val() == '.CUSTOM_GRAPH_BULLET_CHART.') {
			$("#thresholdDiv").show();
		}else{
			$("#thresholdDiv").hide();
		}
		
		if(!$("#checkbox-summatory_series").is(":checked") && !$("#checkbox-average_series").is(":checked")){
			$("#checkbox-modules_series").attr("disabled", true);
			$("#checkbox-modules_series").attr("checked", false);
		}
		
	});

	$("#stacked").change(function(){
		if ( $(this).val() == '.CUSTOM_GRAPH_GAUGE.') {
			$("[name=threshold]").prop("checked", false);
			$(".stacked").hide();
			$("input[name=\'width\']").hide();
			$("#thresholdDiv").hide();
		} else if ($(this).val() == '.CUSTOM_GRAPH_BULLET_CHART.') {
			$("#thresholdDiv").show();
			$(".stacked").show();
			$("input[name=\'width\']").show();
		} else {
			$("[name=threshold]").prop("checked", false);
			$(".stacked").show();
			$("input[name=\'width\']").show();
			$("#thresholdDiv").hide();
		}
	});
	
	$("#checkbox-summatory_series").change(function() {
		if($("#checkbox-summatory_series").is(":checked") && $("#checkbox-modules_series").is(":disabled")) {
			$("#checkbox-modules_series").removeAttr("disabled");
		} else if(!$("#checkbox-average_series").is(":checked")) {
			$("#checkbox-modules_series").attr("disabled", true);
			$("#checkbox-modules_series").attr("checked", false);
		}
	});
	
	$("#checkbox-average_series").change(function() {
		if($("#checkbox-average_series").is(":checked") && $("#checkbox-modules_series").is(":disabled")) {
			$("#checkbox-modules_series").removeAttr("disabled");
		} else if(!$("#checkbox-summatory_series").is(":checked")) {
			$("#checkbox-modules_series").attr("disabled", true);
			$("#checkbox-modules_series").attr("checked", false);
		}
	});

</script>';
