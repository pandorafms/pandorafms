<?php
/**
 * Group tactic view.
 *
 * @category   Group Tactic View
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Tactical View Group'
    );
    include 'general/noaccess.php';
    return;
}



$id_group = get_parameter('id_group', '');
if (empty($id_group) === true) {
    return;
}

$user_groups_acl = users_get_groups(false, 'AR');
if (in_array(groups_get_name($id_group), $user_groups_acl) === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Tactical View Group'
    );
    include 'general/noaccess.php';
    return;
}

if (is_metaconsole() === false) {
    // Header.
    ui_print_standard_header(
        __(groups_get_name($id_group)),
        'images/group.png',
        false,
        '',
        false,
        [],
        [
            [
                'link'  => '',
                'label' => __('Monitoring'),
            ],
            [
                'link'  => '',
                'label' => __('Tactical group view'),
            ],
        ],
        [
            'id_element' => $id_group,
            'url'        => 'gagent&sec2=godmode/groups/tactical&id_group='.$id_group,
            'label'      => groups_get_name($id_group),
            'section'    => 'Groups',
        ]
    );
}

ui_require_css_file('tactical_groups');
ui_require_javascript_file('tactical_groups');
$groups = groups_get_children($id_group);
$id_groups = [];
if (count($groups) > 0) {
    foreach ($groups as $key => $value) {
        $id_groups[] = $value['id_grupo'];
    }
} else {
    $id_groups[] = $id_group;
}


echo '<div id="tactic_view">';
echo '<div class="tactical_group_left_columns">';
echo '<div class="tactical_group_left_column">';
$table_col1 = new stdClass();
$table_col1->class = 'no-class';
$table_col1->data = [];
$table_col1->rowclass[] = '';
$table_col1->headstyle[0] = 'text-align:center;';
$table_col1->width = '100%';
$table_col1->data[0][0] = groups_get_heat_map_agents($id_groups, 330, 100);
$table_col1->data[1][0] = tactical_groups_get_agents_and_monitoring($id_groups);

$distribution_by_so = '<table cellpadding=0 cellspacing=0 class="databox pies graph-distribution-so" width=100%><tr><td style="width:50%;">';
$distribution_by_so .= '<fieldset class="padding-0 databox tactical_set" id="distribution_by_so_graph">';
$distribution_by_so .= '<legend>'.__('Distribution by os').'</legend>';
$distribution_by_so .= html_print_image('images/spinner.gif', true, ['id' => 'spinner_distribution_by_so_graph']);
$distribution_by_so .= '</fieldset>';
$distribution_by_so .= '</td></tr></table>';


$table_col1->data[2][0] = $distribution_by_so;


ui_toggle(
    html_print_table($table_col1, true),
    __('Monitoring'),
    '',
    '',
    false,
    false
);

echo '</div>';
echo '<div class="tactical_group_left_column">';
$table_col2 = new stdClass();
$table_col2->class = 'no-class';
$table_col2->data = [];
$table_col2->rowclass[] = '';
$table_col2->headstyle[0] = 'text-align:center;';
$table_col2->width = '100%';
$table_col2->data[0][0] = tactical_groups_get_stats_alerts($id_groups);
$table_col2->data[1][0] = groups_get_stats_modules_status($id_groups);

$events_by_agents_group = '<table cellpadding=0 cellspacing=0 class="databox pies mrgn_top_15px" width=100%><tr><td style="width:50%;">';
$events_by_agents_group .= '<fieldset class="padding-0 databox tactical_set" id="events_by_agents_group_graph">';
$events_by_agents_group .= '<legend>'.__('Events by agent').'</legend>';
$events_by_agents_group .= html_print_image('images/spinner.gif', true, ['id' => 'spinner_events_by_agents_group_graph']);
$events_by_agents_group .= '</fieldset>';
$events_by_agents_group .= '</td></tr></table>';


$table_col2->data[2][0] = $events_by_agents_group;
ui_toggle(
    html_print_table($table_col2, true),
    __('Alerts and events'),
    '',
    '',
    false,
    false
);
echo '</div>';
echo '</div>';
echo '<div class="tactical_group_right_column">';
$table_col3 = new stdClass();
$table_col3->class = 'no-class';
$table_col3->data = [];
$table_col3->rowclass[] = '';
$table_col3->headstyle[0] = 'text-align:center;';
$table_col3->width = '100%';

try {
    $columns = [
        'alias',
        'status',
        'alerts',
        'ultimo_contacto_remoto',
    ];

    $columnNames = [
        __('Alias'),
        __('Status'),
        __('Alerts'),
        __('Last remote contact'),
    ];

    // Load datatables user interface.
    $table_col3->data[3][0] = ui_print_datatable(
        [
            'id'                  => 'list_agents_tactical',
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $columnNames,
            'return'              => true,
            'ajax_url'            => 'include/ajax/group',
            'ajax_data'           => [
                'method'   => 'getAgentsByGroup',
                'id_group' => $id_group,
            ],
            'dom_elements'        => 'lpfti',
            'no_sortable_columns' => [-1],
            'order'               => [
                'field'     => 'alias',
                'direction' => 'asc',
            ],
        ]
    );
} catch (Exception $e) {
    echo $e->getMessage();
}

ui_toggle(
    html_print_table($table_col3, true),
    __('Agents'),
    '',
    '',
    false,
    false
);
echo '</div>';
echo '</div>';
echo '<div id="modal-info-agent"></div>'

?>
<script type="text/javascript">
    $(document).ready(function () {
        var parameters = {};
        parameters["page"] = "include/ajax/group";
        parameters["method"] = 'distributionBySoGraph';
        parameters["id_group"] = <?php echo $id_group; ?>;

        $.ajax({type: "GET",url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",data: parameters,
            success: function(data) {
                $("#spinner_distribution_by_so_graph").hide();
                $("#distribution_by_so_graph").append(data);
            }
        });

        parameters["method"] = 'groupEventsByAgent';
        $.ajax({type: "GET",url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",data: parameters,
            success: function(data) {
                $("#spinner_events_by_agents_group_graph").hide();
                $("#events_by_agents_group_graph").append(data);
                const canvas = $('#events_by_agents_group_graph canvas')[0];
                canvas.addEventListener('click', function(event) {
                var middle_canvas = $('#events_by_agents_group_graph canvas').width() / 2;
                if(event.layerX < middle_canvas){
                    window.location.replace("index.php?sec=eventos&sec2=operation/events/events&filter[id_group_filter]=<?php echo $id_group; ?>")
                }
                });
            }
        });

    });
</script>