<?php
/**
 * Netflow Filter Editor.
 *
 * @category   Netflow
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_netflow.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/functions_groups.php';

check_login();

// Fix: Netflow have to check RW ACL
if (! check_acl($config['id_user'], 0, 'RW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access event viewer'
    );
    include $config['homedir'].'/general/noaccess.php';
    return;
}

$id = (int) get_parameter('id');
$name = db_get_value('id_name', 'tnetflow_filter', 'id_sg', $id);
$update = (string) get_parameter('update', 0);
$create = (string) get_parameter('create', 0);

$pure = get_parameter('pure', 0);

if ($id) {
    $permission = netflow_check_filter_group($id);
    if (!$permission) {
        // no tiene permisos para acceder a un filtro
        include $config['homedir'].'/general/noaccess.php';
        return;
    }
}

// Header Buttons.
$buttons = [];
$buttons[] = ['text' => '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit">'.html_print_image('images/logs@svg.svg', true, ['title' => __('Filter list')]).'</a>'];
$buttons[] = ['text' => '<a href="index.php?sec=netf&sec2=godmode/netflow/nf_edit_form">'.html_print_image('images/plus@svg.svg', true, ['title' => __('Add filter')]).'</a>'];
// Header Caption.
$headerTitle = ($id) ? __('Update filter') : __('Create filter');

// Header.
ui_print_standard_header(
    $headerTitle,
    'images/gm_netflow.png',
    false,
    '',
    true,
    $buttons,
    [
        [
            'link'  => '',
            'label' => __('Netflow'),
        ],
    ],
);

if ($id) {
    $filter = netflow_filter_get_filter($id);
    $assign_group = $filter['id_group'];
    $name = $filter['id_name'];
    $ip_dst = $filter['ip_dst'];
    $ip_src = $filter['ip_src'];
    $dst_port = $filter['dst_port'];
    $src_port = $filter['src_port'];
    $aggregate = $filter['aggregate'];
    $advanced_filter = $filter['advanced_filter'];
} else {
    $name = '';
    $assign_group = '';
    $ip_dst = '';
    $ip_src = '';
    $dst_port = '';
    $src_port = '';
    $aggregate = 'dstip';
    $advanced_filter = '';
}

if ($update) {
    $name = (string) get_parameter('name');
    $assign_group = (int) get_parameter('assign_group');
    $aggregate = get_parameter('aggregate', '');
    $ip_dst = get_parameter('ip_dst', '');
    $ip_src = get_parameter('ip_src', '');
    $dst_port = get_parameter('dst_port', '');
    $src_port = get_parameter('src_port', '');
    $advanced_filter = get_parameter('advanced_filter', '');

    if ($name == '') {
        ui_print_error_message(__('Not updated. Blank name'));
    } else {
        $values = [
            'id_sg'           => $id,
            'id_name'         => $name,
            'id_group'        => $assign_group,
            'aggregate'       => $aggregate,
            'ip_dst'          => $ip_dst,
            'ip_src'          => $ip_src,
            'dst_port'        => $dst_port,
            'src_port'        => $src_port,
            'advanced_filter' => $advanced_filter,
        ];

        // Save filter args.
        $values['filter_args'] = netflow_get_filter_arguments($values, true);

        $result = db_process_sql_update('tnetflow_filter', $values, ['id_sg' => $id]);

        ui_print_result_message(
            $result,
            __('Successfully updated'),
            __('Not updated. Error updating data')
        );
    }
}

if ($create) {
    $name = (string) get_parameter('name');
    $assign_group = (int) get_parameter('assign_group');
    $aggregate = get_parameter('aggregate', 'dstip');
    $ip_dst = get_parameter('ip_dst', '');
    $ip_src = get_parameter('ip_src', '');
    $dst_port = get_parameter('dst_port', '');
    $src_port = get_parameter('src_port', '');
    $advanced_filter = (string) get_parameter('advanced_filter', '');

    $values = [
        'id_name'         => $name,
        'id_group'        => $assign_group,
        'ip_dst'          => $ip_dst,
        'ip_src'          => $ip_src,
        'dst_port'        => $dst_port,
        'src_port'        => $src_port,
        'aggregate'       => $aggregate,
        'advanced_filter' => $advanced_filter,
    ];

    // Save filter args
    $values['filter_args'] = netflow_get_filter_arguments($values, true);

    $id = db_process_sql_insert('tnetflow_filter', $values);
    if ($id === false) {
        ui_print_error_message('Error creating filter');
    } else {
        ui_print_success_message('Filter created successfully');
    }
}

$own_info = get_user_info($config['id_user']);
$filter_type = (empty($advanced_filter) === false) ? 1 : 0;
$aggregate_list = [
    'srcip'   => __('Src Ip Address'),
    'dstip'   => __('Dst Ip Address'),
    'srcport' => __('Src Port'),
    'dstport' => __('Dst Port'),
];


$table = new stdClass();
$table->id = 'table1';
$table->width = '100%';
$table->class = 'databox filter-table-adv';
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';

$table->data = [];

$table->data['first_line'][] = html_print_label_input_block(
    __('Name'),
    html_print_input_text(
        'name',
        $name,
        false,
        20,
        80,
        true
    )
);

$table->data['first_line'][] = html_print_label_input_block(
    __('Group'),
    html_print_select_groups(
        $config['id_user'],
        'RW',
        $own_info['is_admin'],
        'assign_group',
        $assign_group,
        '',
        '',
        -1,
        true,
        false,
        false,
        '',
        false,
        false,
        false,
        false,
        'id_grupo',
        false,
        false,
        false,
        '250px'
    )
);

$table->data['filter_line'][] = html_print_label_input_block(
    __('Filter'),
    html_print_div(
        [
            'class'   => 'flex',
            'content' => html_print_div(
                [
                    'class'   => 'flex-row-end',
                    'content' => __('Normal').' '.html_print_radio_button_extended('filter_type', 0, '', $filter_type, false, 'displayNormalFilter();', 'class="mrgn_right_40px"', true),
                ],
                true
            ).html_print_div(
                [
                    'class'   => 'flex-row-end',
                    'content' => __('Advanced').' '.html_print_radio_button_extended('filter_type', 1, '', $filter_type, false, 'displayAdvancedFilter();', 'class="mrgn_right_40px"', true),
                ],
                true
            ),
        ],
        true
    ),
);

$table->data['filter_line'][] = html_print_label_input_block(
    __('Aggregate by'),
    html_print_select(
        $aggregate_list,
        'aggregate',
        $aggregate,
        '',
        '',
        0,
        true,
        false,
        true,
        '',
        false
    )
);

$table->data['ip_line'][] = html_print_label_input_block(
    __('Dst Ip'),
    html_print_input_text(
        'ip_dst',
        $ip_dst,
        false,
        40,
        80,
        true
    ).ui_print_input_placeholder(__('Destination IP. A comma separated list of destination ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249'), true)
);

$table->data['ip_line'][] = html_print_label_input_block(
    __('Src Ip'),
    html_print_input_text(
        'ip_src',
        $ip_src,
        false,
        40,
        80,
        true
    ).ui_print_input_placeholder(__('Source IP. A comma separated list of source ip. If we leave the field blank, will show all ip. Example filter by ip:<br>25.46.157.214,160.253.135.249'), true)
);

$table->data['ports_line'][] = html_print_label_input_block(
    __('Dst Port'),
    html_print_input_text(
        'dst_port',
        $dst_port,
        false,
        40,
        80,
        true
    ).ui_print_input_placeholder(__('Destination port. A comma separated list of destination ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22'), true)
);

$table->data['ports_line'][] = html_print_label_input_block(
    __('Src Port'),
    html_print_input_text(
        'src_port',
        $src_port,
        false,
        40,
        80,
        true
    ).ui_print_input_placeholder(__('Source port. A comma separated list of source ports. If we leave the field blank, will show all ports. Example filter by ports 80 and 22:<br>80,22'), true)
);

$table->colspan['advanced_filters'][] = 2;
$table->data['advanced_filters'][] = html_print_label_input_block(
    __('Advanced filters'),
    html_print_textarea('advanced_filter', 4, 40, $advanced_filter, '', true, 'w50p')
);


$hiddens = '';
if ($id) {
    $buttonTitle = __('Update');
    $hiddens .= html_print_input_hidden('update', 1, true);
    $hiddens .= html_print_input_hidden('id', $id, true);
} else {
    $buttonTitle = __('Create');
    $hiddens .= html_print_input_hidden('create', 1, true);
}

echo '<form class="max_floating_element_size" id="nf_edit_form" method="post" action="'.$config['homeurl'].'index.php?sec=netf&sec2=godmode/netflow/nf_edit_form&pure='.$pure.'">';
echo $hiddens;
html_print_table($table);
echo '</form>';

html_print_action_buttons(
    html_print_submit_button(
        $buttonTitle,
        'crt',
        false,
        [
            'icon' => 'upd',
            'form' => 'nf_edit_form',
        ],
        true
    )
);

?>

<script type="text/javascript">
    $(document).ready(function(){
        var filter_type = <?php echo $filter_type; ?>;
        if (filter_type == 0) {
            displayNormalFilter ();
        }
        else {
            displayAdvancedFilter ();
        }
    });

    function displayAdvancedFilter () {
        console.log('papapa advanced filter');
        // Erase the normal filter
        document.getElementById("text-ip_dst").value = '';
        document.getElementById("text-ip_src").value = '';
        document.getElementById("text-dst_port").value = '';
        document.getElementById("text-src_port").value = '';
        
        // Hide the normal filter
        //document.getElementById("table1-3").style.display = 'none';
        //document.getElementById("table1-4").style.display = 'none';
        //document.getElementById("table1-5").style.display = 'none';
        //document.getElementById("table1-6").style.display = 'none';
        $("#table1-ip_line").css("display", "none");
        $("#table1-ports_line").css("display", "none");
        // Show the advanced filter
        $("#table1-advanced_filters").css("display", "table-row");
        //document.getElementById("table1-7").style.display = '';
    };
    
    function displayNormalFilter () {
        console.log('papapa normal filter');
        // Erase the advanced filter
        document.getElementById("textarea_advanced_filter").value = '';
        // Hide the advanced filter
        //document.getElementById("table1-7").style.display = 'none';
        $("#table1-advanced_filters").css("display", "none");
        // Show the normal filter
        $("#table1-ip_line").css("display", "table-row");
        $("#table1-ports_line").css("display", "table-row");
        /*
        document.getElementById("table1-3").style.display = '';
        document.getElementById("table1-4").style.display = '';
        document.getElementById("table1-5").style.display = '';
        document.getElementById("table1-6").style.display = '';
        */
    };
</script>
