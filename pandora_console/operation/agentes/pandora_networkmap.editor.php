<?php
/**
 * Empty Network editor.
 *
 * @category   View
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

// Begin.
global $config;

// Check user credentials.
check_login();

$id = (int) get_parameter('id_networkmap', 0);

$new_networkmap = (bool) get_parameter('new_networkmap', false);
$edit_networkmap = (bool) get_parameter('edit_networkmap', false);

$not_found = false;

if (empty($id)) {
    $new_networkmap = true;
    $edit_networkmap = false;
}

if ($new_networkmap) {
    $name = '';
    $id_group = 0;
    $node_radius = 40;
    $description = '';
    $method = 'neato';
    $recon_task_id = 0;
    $source = 'group';
    $ip_mask = '';
    $dont_show_subgroups = 0;
    $offset_x = '';
    $offset_y = '';
    $scale_z = 0.5;
    $node_sep = 0.25;
    $rank_sep = 0.5;
    $mindist = 1.0;
    $kval = 0.3;
    $refresh_time = 300;
}

$disabled_generation_method_select = false;
$disabled_source = false;
if ($edit_networkmap) {
    $disabled_generation_method_select = true;
    $disabled_source = true;

    $values = db_get_row('tmap', 'id', $id);

    $not_found = false;
    if ($values === false) {
        $not_found = true;
    } else {
        $id_group = $values['id_group'];

        $id_group_acl_check = $id_group_map;

        if ($id_group_map === null) {
            $id_group_acl_check = $values['id_group_map'];
        }

        // ACL for the network map.
        $networkmap_write = check_acl_restricted_all($config['id_user'], $id_group_acl_check, 'MW');
        $networkmap_manage = check_acl_restricted_all($config['id_user'], $id_group_acl_check, 'MM');

        if (!$networkmap_write && !$networkmap_manage) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return;
        }

        $name = io_safe_output_html($values['name']);

        // Id group of the map itself, not data source.
        $id_group_map = $values['id_group_map'];

        $description = $values['description'];

        $filter = json_decode($values['filter'], true);

        $offset_x = $filter['x_offs'];
        $offset_y = $filter['y_offs'];
        $scale_z = $filter['z_dash'];

        if (isset($filter['node_sep'])) {
            $node_sep = $filter['node_sep'];
        } else {
            $node_sep = 0.25;
        }

        if (isset($filter['rank_sep'])) {
            $rank_sep = $filter['rank_sep'];
        } else {
            if ($values['generation_method'] == 'twopi') {
                $rank_sep = 1.0;
            } else {
                $rank_sep = 0.5;
            }
        }

        if (isset($filter['mindist'])) {
            $mindist = $filter['mindist'];
        } else {
            $mindist = 1.0;
        }

        if (isset($filter['kval'])) {
            $kval = $filter['kval'];
        } else {
            $kval = 0.3;
        }

        $refresh_time = $values['refresh_time'];

        $node_radius = $filter['node_radius'];

        $source = $values['source'];
        switch ($source) {
            case 0:
                $source = 'group';
            break;

            case 1:
                $source = 'recon_task';
            break;

            case 2:
                $source = 'ip_mask';
            break;
        }

        $source_data = $values['source_data'];
        switch ($values['generation_method']) {
            case 0:
                $method = 'circo';
            break;

            case 1:
                $method = 'dot';
            break;

            case 2:
                $method = 'twopi';
            break;

            case 3:
                $method = 'neato';
            break;

            case 4:
                $method = 'neato';
            break;

            case 5:
                $method = 'fdp';
            case 6:
                $method = 'radial_dinamic';
            break;
        }

        $recon_task_id = 0;
        if ($values['source'] == 1) {
            $recon_task_id = $values['source_data'];
        } else {
            $ip_mask = '';
            if (isset($filter['ip_mask'])) {
                $ip_mask = $filter['ip_mask'];
            }
        }

        $dont_show_subgroups = false;
        if (isset($filter['dont_show_subgroups'])) {
            $dont_show_subgroups = $filter['dont_show_subgroups'];
        }
    }
}

$button = [];
if ($edit_networkmap === true) {
    $button['map'] = [
        'active' => false,
        'text'   => '<a href="index.php?sec=network&sec2=operation/agentes/pandora_networkmap&tab=view&id_networkmap='.$id.'">'.html_print_image(
            'images/network@svg.svg',
            true,
            [
                'title' => __('View map'),
                'class' => 'main_menu_icon invert_filter',
            ]
        ).'</a>',
    ];
}

// Header.
ui_print_standard_header(
    __('Network maps editor'),
    'images/bricks.png',
    false,
    'network_map_enterprise_edit',
    false,
    $button,
    [
        [
            'link'  => '',
            'label' => __('Topology maps'),
        ],
        [
            'link'  => '',
            'label' => __('Networkmap'),
        ],
    ]
);

$id_snmp_l2_recon = db_get_value(
    'id_recon_script',
    'trecon_script',
    'name',
    io_safe_input('SNMP L2 Recon')
);

if (! check_acl($config['id_user'], 0, 'PM')) {
    $sql = sprintf(
        'SELECT *
		FROM trecon_task RT, tusuario_perfil UP
		WHERE UP.id_usuario = "%s" AND UP.id_grupo = RT.id_group',
        $config['id_user']
    );


    $result = db_get_all_rows_sql($sql);
} else {
    $sql = sprintf(
        'SELECT *
		FROM trecon_task'
    );
    $result = db_get_all_rows_sql($sql);
}

$list_recon_tasks = [];
if (!empty($result)) {
    foreach ($result as $item) {
        $list_recon_tasks[$item['id_rt']] = io_safe_output($item['name']);
    }
}

if ($not_found) {
    ui_print_error_message(__('Not found networkmap.'));
} else {
    if ($disabled_source === false) {
        echo '<div id="map_loading" style="width: 98%;height: 1000px; background-color: rgba(245, 245, 245, .3);position: absolute;display: flex;justify-content: center;align-items: center;flex-direction: column-reverse;">';
        echo html_print_image('images/spinner.gif', true, ['width' => '50px', 'height' => '50px']);
        echo '<div>'.__('Creating map...').'</div>';
        echo '</div>';
        $info1 = __('To create a network map that visually recreates link-level (L2) relationships, you must first discover these relationships with Discovery Server.  Network maps only reflect relationships that have already been discovered.');
        $separator = '<br>';
        $info2 = __('Discovery Server discovers relationships between interfaces (L2) through SNMP and relationships between hosts (L3) through route discovery.');
        $info3 = __('You can also create these relationships manually by editing nodes or re-passing a discovery task after adding new information (for example by adding new SNMP communities).');
        $info4 = __('See our documentation for more information.');
        ui_print_info_message(
            [
                'no_close' => false,
                'message'  => $info1.$separator.$info2.$separator.$info3.$separator.$info4,
            ],
            'style="width: 98%;"'
        );
    }

    $methods = [
        'twopi'          => 'radial',
        'dot'            => 'flat',
        'circo'          => 'circular',
        'neato'          => 'spring1',
        'fdp'            => 'spring2',
        'radial_dinamic' => 'radial dynamic',
    ];

    $itemClass = '';
    if ($disabled_source === true) {
        $itemClass = 'disabled';
    }

    $return_all_group = false;

    if (users_can_manage_group_all('AR') === true) {
        $return_all_group = true;
    }

    if (empty($scale_z) === true) {
        $scale_z = 0.5;
    }

    $table = new stdClass();
    $table->id = 'form_editor';
    $table->width = '100%';
    $table->class = 'databox filter-table-adv max_floating_element_size';
    $table->head = [];
    $table->size = [];
    $table->style = [];
    $table->style[0] = 'width: 50%';
    $table->style[1] = 'width: 50%';
    $table->colspan[1][0] = 2;
    $table->data = [];

    $table->data[0][] = html_print_label_input_block(
        __('Name'),
        html_print_input_text(
            'name',
            $name,
            '',
            30,
            100,
            true
        )
    );

    $table->data[0][] = html_print_label_input_block(
        __('Group'),
        html_print_select_groups(
            // Id_user.
            $config['id_user'],
            // Privilege.
            'AR',
            // ReturnAllGroup.
            $return_all_group,
            // Name.
            'id_group_map',
            // Selected.
            $id_group_map,
            // Script.
            '',
            // Nothing.
            '',
            // Nothing_value.
            '',
            // Return.
            true
        )
    );

    $table->data[1][] = html_print_label_input_block(
        __('Description'),
        html_print_input_text(
            'description',
            $description,
            '',
            100,
            100,
            true
        )
    );

    $divLittleFields = [];
    $divLittleFields[] = html_print_label_input_block(
        __('Position X'),
        html_print_input_text('pos_x', $offset_x, '', 10, 10, true, false, false, '', 'w50p'),
        [ 'div_class' => 'div-4-col' ]
    );

    $divLittleFields[] = html_print_label_input_block(
        __('Position Y'),
        html_print_input_text('pos_y', $offset_y, '', 10, 10, true, false, false, '', 'w50p'),
        [ 'div_class' => 'div-4-col' ]
    );

    $divLittleFields[] = html_print_label_input_block(
        __('Zoom scale'),
        html_print_input_text('scale_z', $scale_z, '', 10, 10, true, false, false, '', 'w50p').ui_print_input_placeholder(__('Introduce zoom level. 1 = Highest resolution. Figures may include decimals'), true),
        [ 'div_class' => 'div-4-col' ]
    );

    $divLittleFields[] = html_print_label_input_block(
        __('Node radius'),
        html_print_input_text(
            'node_radius',
            $node_radius,
            '',
            10,
            10,
            true,
            false,
            false,
            '',
            'w50p'
        ),
        [ 'div_class' => 'div-4-col' ]
    );

    $table->colspan[2][0] = 2;
    $table->data[2][0] = html_print_div(
        [
            'style'   => 'flex-direction: row;',
            'content' => implode('', $divLittleFields),
        ],
        true
    );

    $table->data['source'][] = html_print_label_input_block(
        __('Source'),
        html_print_select(
            [
                'group'      => __('Group'),
                'recon_task' => __('Discovery task'),
                'ip_mask'    => __('CIDR IP mask'),
            ],
            'source',
            $source,
            '',
            '',
            0,
            true,
            false,
            false,
            '',
            $disabled_source
        )
    );

    $table->data['source_data_group'][] = html_print_label_input_block(
        __('Source group'),
        html_print_select_groups(
            $config['id_user'],
            'AR',
            true,
            'id_group[]',
            explode(',', $id_group),
            '',
            '',
            '',
            true,
            true
        ).ui_print_input_placeholder(
            __('Source id group changed. All elements in networkmap will be lost.'),
            true,
            [
                'class' => 'input_sub_placeholder input_sub_placeholder_warning',
                'style' => 'display: none',
                'id'    => 'group_change_warning',
            ]
        )
    );

    $table->data['source_data_group'][] = html_print_label_input_block(
        __('Don\'t show subgroups:'),
        html_print_checkbox(
            'dont_show_subgroups',
            '1',
            $dont_show_subgroups,
            true,
            $disabled_source
        )
    );


    $table->data['source_data_recon_task'][] = html_print_label_input_block(
        __('Source from recon task'),
        html_print_select(
            $list_recon_tasks,
            'recon_task_id',
            $recon_task_id,
            '',
            __('None'),
            0,
            true,
            false,
            true,
            '',
            $disabled_source
        ).ui_print_input_placeholder(
            __('It is setted any recon task, the nodes get from the recontask IP mask instead from the group.'),
            true
        )
    );

    $table->data['source_data_ip_mask'][] = html_print_label_input_block(
        __('Source from CIDR IP mask'),
        html_print_textarea(
            'ip_mask',
            3,
            5,
            $ip_mask,
            'style="width: 100%"',
            true,
            '',
            $disabled_source
        )
    );

    $table->data[7][] = html_print_label_input_block(
        __('Method generation networkmap'),
        html_print_select(
            $methods,
            'method',
            $method,
            '',
            '',
            'neato',
            true,
            false,
            true,
            '',
            $disabled_generation_method_select
        )
    );

    $table->data['nodesep'][] = html_print_label_input_block(
        __('Node separation'),
        html_print_input_text('node_sep', $node_sep, '', 5, 10, true, $disabled_source, false, $itemClass).ui_print_input_placeholder(__('Separation between nodes. By default 0.25'), true)
    );

    $table->data['ranksep'][] = html_print_label_input_block(
        __('Rank separation'),
        html_print_input_text('rank_sep', $rank_sep, '', 5, 10, true, $disabled_source, false, $itemClass).ui_print_input_placeholder(__('Only flat and radial. Separation between arrows. By default 0.5 in flat and 1.0 in radial'), true)
    );

    $table->data['mindist'][] = html_print_label_input_block(
        __('Min nodes dist'),
        html_print_input_text('mindist', $mindist, '', 5, 10, true, $disabled_source, false, $itemClass).ui_print_input_placeholder(__('Only circular. Minimum separation between all nodes. By default 1.0'), true)
    );

    $table->data['kval'][] = html_print_label_input_block(
        __('Default ideal node separation'),
        html_print_input_text('kval', $kval, '', 5, 10, true, $disabled_source, false, $itemClass).ui_print_input_placeholder(__('Only fdp. Default ideal node separation in the layout. By default 0.3'), true)
    );

    $table->data['refresh'][] = html_print_label_input_block(
        __('Refresh'),
        html_print_extended_select_for_time(
            'refresh_time',
            $refresh_time,
            '',
            '',
            '0',
            false,
            true,
            false,
            false
        )
    );

    echo '<form id="networkmap_options_form" method="post" action="index.php?sec=network&amp;sec2=operation/agentes/pandora_networkmap">';

    html_print_table($table);

    $actionButtons = [];

    if ($new_networkmap === true) {
        html_print_input_hidden('save_networkmap', 1);
        $actionButtons[] = html_print_submit_button(
            __('Save networkmap'),
            'crt',
            false,
            [
                'onClick' => 'if (typeof(sent) == \'undefined\') {sent = 1; return true;} else {return false;}',
                'icon'    => 'next',
            ],
            true
        );
    }

    if ($edit_networkmap === true) {
        html_print_input_hidden('id_networkmap', $id);
        html_print_input_hidden('update_networkmap', 1);
        $actionButtons[] = html_print_submit_button(
            __('Update networkmap'),
            'crt',
            false,
            [ 'icon' => 'update'],
            true
        );
    }

    $actionButtons[] = html_print_go_back_button(
        'index.php?sec=networkmapconsole&sec2=operation/agentes/pandora_networkmap',
        ['button_class' => ''],
        true
    );

    html_print_action_buttons(
        $actionButtons
    );

    echo '</form>';
}
?>
<script type="text/javascript">

$(document).ready(function() {
    $("#map_loading").hide();
    $("#source").change(function() {
        const source = $(this).val();

        if (source == 'recon_task') {
            $("#form_editor-source_data_ip_mask")
                .css('display', 'none');
            $("#form_editor-source_data_dont_show_subgroups")
                .css('display', 'none');
            $("#form_editor-source_data_group")
                .css('display', 'none');
            $("#form_editor-source_data_recon_task")
                .css('display', '');
        }
        else if (source == 'ip_mask') {
            $("#form_editor-source_data_ip_mask")
                .css('display', '');
            $("#form_editor-source_data_recon_task")
                .css('display', 'none');
            $("#form_editor-source_data_dont_show_subgroups")
                .css('display', 'none');
            $("#form_editor-source_data_group")
                .css('display', 'none');
        }
        else if (source == 'group') {
            $("#form_editor-source_data_ip_mask")
                .css('display', 'none');
            $("#form_editor-source_data_recon_task")
                .css('display', 'none');
            $("#form_editor-source_data_dont_show_subgroups")
                .css('display', '');
                $("#form_editor-source_data_group")
                .css('display', '');
        }
    });

    $("#method").on('change', function () {
        var method = $("#method").val();

        if (method == 'circo') {
            $("#form_editor-ranksep")
                .css('display', 'none');
            $("#form_editor-mindist")
                .css('display', '');
            $("#form_editor-kval")
                .css('display', 'none');
            $("#form_editor-nodesep")
                .css('display', '');
        }
        else if (method == 'dot') {
            $("#form_editor-ranksep")
                .css('display', '');
            $("#form_editor-mindist")
                .css('display', 'none');
            $("#form_editor-kval")
                .css('display', 'none');
            $("#form_editor-nodesep")
                .css('display', '');
        }
        else if (method == 'twopi') {
            $("#form_editor-ranksep")
                .css('display', '');
            $("#form_editor-mindist")
                .css('display', 'none');
            $("#form_editor-kval")
                .css('display', 'none');
            $("#form_editor-nodesep")
                .css('display', 'none');
        }
        else if (method == 'neato') {
            $("#form_editor-ranksep")
                .css('display', 'none');
            $("#form_editor-mindist")
                .css('display', 'none');
            $("#form_editor-kval")
                .css('display', 'none');
            $("#form_editor-nodesep")
                .css('display', '');
        }
        else if (method == 'radial_dinamic') {
            $("#form_editor-ranksep")
                .css('display', 'none');
            $("#form_editor-mindist")
                .css('display', 'none');
            $("#form_editor-kval")
                .css('display', 'none');
            $("#form_editor-nodesep")
                .css('display', 'none');
        }
        else if (method == 'fdp') {
            $("#form_editor-ranksep")
                .css('display', 'none');
            $("#form_editor-mindist")
                .css('display', 'none');
            $("#form_editor-kval")
                .css('display', '');
            $("#form_editor-nodesep")
                .css('display', '');
        }
    });

    $("#source").trigger("change");
    $("#method").trigger("change");

    // Control if id_group has changed.
    var id_group_old = $("#id_group").val();
    var id_group_changed = false;

    $("#id_group").on('change',{id_group_old: id_group_old}, function () {
        var id_group_new = $("#id_group").val();
        if((id_group_old != id_group_new) && (update_networkmap == 1 )) {
            id_group_changed = true;
            $("#group_change_warning").show();

        } else {
            id_group_changed = false;
            $("#group_change_warning").hide();
        }
    });

    var update_networkmap = 0;
    // Show advice if id_group has changed.
    update_networkmap = $("input[name='update_networkmap']").val();

    $( "#submit-crt" ).click(function( event ) {
        $("#map_loading").show();
        if(update_networkmap == 1 && id_group_changed === true) {
            confirmDialog({
                        title: '<?php echo __('Are you sure?'); ?>',
                        message: '<?php echo __('Source id group changed. All elements in Networkmap will be lost'); ?>',
                        ok: '<?php echo __('OK'); ?>',
                        cancel: '<?php echo __('Cancel'); ?>',
                        onDeny: function() {
                            // Continue execution.
                            return false;
                        },
                        onAccept: function () {
                            // Submit form
                            $("#networkmap_options_form").submit();
                        }
                    })
            event.preventDefault();
        }
    });

    $("#refresh_time_units").trigger("change");
});


</script>
