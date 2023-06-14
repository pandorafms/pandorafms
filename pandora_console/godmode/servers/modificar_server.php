<?php
/**
 * Server list view.
 *
 * @category   Server
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

// Load global vars.
global $config;

require_once $config['homedir'].'/include/functions_servers.php';
require_once $config['homedir'].'/include/functions_graph.php';

check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Server Management'
    );
    include 'general/noaccess.php';
    exit;
}

if (isset($_GET['server']) === true) {
    $id_server = get_parameter_get('server');
    // Headers.
    ui_print_standard_header(
        __('Update Server'),
        'images/gm_servers.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Servers'),
            ],
            [
                'link'  => 'index.php?sec=gservers&sec2=godmode/servers/modificar_server',
                'label' => __('%s servers', get_product_name()),
            ],
        ]
    );

    $sql = sprintf('SELECT name, ip_address, description, server_type, exec_proxy, port FROM tserver WHERE id_server = %d', $id_server);
    $row = db_get_row_sql($sql);
    echo '<form name="servers" method="POST" action="index.php?sec=gservers&sec2=godmode/servers/modificar_server&update=1">';
    html_print_input_hidden('server', $id_server);

    $server_type = __('Standard');
    if ($row['server_type'] == 13) {
        $server_type = __('Satellite');
    }

    $exec_server_enable = __('No');
    if ($row['exec_proxy'] == 1) {
        $exec_server_enable = __('Yes');
    }

    $table = new stdClass();

    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->class = 'databox';
    $table->id = 'server_update_form';

    $table->data[] = [
        __('Name'),
        $row['name'],
    ];
    $table->data[] = [
        __('IP Address'),
        html_print_input_text('address', $row['ip_address'], '', 50, 0, true),
    ];
    $table->data[] = [
        __('Description'),
        html_print_input_text('description', $row['description'], '', 50, 0, true),
    ];

    if (enterprise_installed() === true) {
        $table->data[] = [
            __('Type'),
            $server_type,
        ];
        if ($row['server_type'] == 13 || $row['server_type'] == 1) {
            $table->data[] = [
                __('Exec Server'),
                html_print_checkbox('exec_proxy', 1, $row['exec_proxy'], true),
            ];

            $port_number = empty($row['port']) ? '' : $row['port'];

            $table->data[] = [
                __('Port'),
                html_print_input_text('port', $port_number, '', 10, 0, true).ui_print_help_tip(__('Leave blank to use SSH default port (22)'), true),
            ];

            if ($row['exec_proxy']) {
                $table->data[] = [
                    __('Check Exec Server'),
                    '<a id="check_exec_server">'.html_print_image('images/dot_red.disabled.png', true).'</a>'.'<div id="check_error_message"></div>',
                ];
            }
        }
    }

    html_print_table($table);

    $actionButtons = [];
    $actionButtons[] = html_print_submit_button(
        __('Update'),
        '',
        false,
        [ 'icon' => 'update' ],
        true
    );

    $actionButtons[] = html_print_go_back_button(
        'index.php?sec=gservers&sec2=godmode/servers/modificar_server',
        ['button_class' => ''],
        true
    );

    html_print_action_buttons(
        implode('', $actionButtons),
        ['type' => 'form_action'],
    );

    echo '</form>';

    if ((int) $row['server_type'] === 13) {
        html_print_div(
            [
                'class'   => 'mrgn_top_20px',
                'content' => ui_toggle($content, __('Credential boxes'), '', 'toggle_credential', false, true),
            ],
        );
    }
} else if (isset($_GET['server_remote']) === true) {
    // Headers.
    $id_server = get_parameter_get('server_remote');
    $ext = get_parameter('ext', '');
    $tab = get_parameter('tab', 'standard_editor');
    $advanced_editor = true;

    $server_type = (int) db_get_value(
        'server_type',
        'tserver',
        'id_server',
        $id_server
    );

    $buttons = '';

    // Buttons.
    $buttons = [
        'standard_editor' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$id_server.'&ext='.$ext.'&tab=standard_editor&pure='.$pure.'">'.html_print_image('images/list.png', true, ['title' => __('Standard editor')]).'</a>',
        ],
        'advanced_editor' => [
            'active' => false,
            'text'   => '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$id_server.'&ext='.$ext.'&tab=advanced_editor&pure='.$pure.'">'.html_print_image('images/pen.png', true, ['title' => __('Advanced editor')]).'</a>',
        ],
    ];

    if ($server_type === SERVER_TYPE_ENTERPRISE_SATELLITE) {
        $buttons['agent_editor'] = [
            'active' => false,
            'text'   => '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$id_server.'&ext='.$ext.'&tab=agent_editor&pure='.$pure.'">'.html_print_image('images/agent.png', true, ['title' => __('Manage agents')]).'</a>',

        ];

        if ((int) $config['license_nms'] !== 1) {
            $buttons['collections'] = [
                'active' => false,
                'text'   => '<a href="index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$id_server.'&ext='.$ext.'&tab=collections&pure='.$pure.'">'.html_print_image('images/collection.png', true, ['title' => __('Collections')]).'</a>',

            ];
        }
    }

    $buttons[$tab]['active'] = true;

    if (is_metaconsole() === true) {
        ui_print_standard_header(
            __('Remote Configuration'),
            'images/gm_servers.png',
            false,
            'servers',
            true,
            [],
            [
                [
                    'link'  => '',
                    'label' => __('Servers'),
                ],
                [
                    'link'  => 'index.php?sec=advanced&sec2=advanced/servers',
                    'label' => __('%s servers', get_product_name()),
                ],
            ]
        );
    } else {
        ui_print_standard_header(
            __('Remote Configuration'),
            'images/gm_servers.png',
            false,
            'servers',
            true,
            $buttons,
            [
                [
                    'link'  => '',
                    'label' => __('Servers'),
                ],
                [
                    'link'  => 'index.php?sec=gservers&sec2=godmode/servers/modificar_server',
                    'label' => __('%s servers', get_product_name()),
                ],
            ]
        );
    }


    if ($tab === 'standard_editor') {
        $advanced_editor = false;

        if ($server_type === 13) {
            echo "<table cellpadding='4' cellspacing='4' class='databox filters margin-bottom-10 max_floating_element_size filter-table-adv'>
            <tr>";
            echo '<td class="w100p">';
            echo html_print_label_input_block(
                __('Dynamic search'),
                html_print_input_text(
                    'search_config_token',
                    $search,
                    '',
                    12,
                    255,
                    true,
                    false,
                    false,
                    '',
                    'w400px'
                )
            );
            echo '</td>';
            echo '</tr></table>';
        }
    } else if ($tab === 'agent_editor' && $server_type === SERVER_TYPE_ENTERPRISE_SATELLITE) {
        $advanced_editor = 'agent_editor';
    } else if ($tab === 'collections' && $server_type === SERVER_TYPE_ENTERPRISE_SATELLITE) {
        $advanced_editor = 'collections';
    }

    enterprise_include('godmode/servers/server_disk_conf_editor.php');
} else {
    // Header.
    ui_print_standard_header(
        __('%s servers', get_product_name()),
        'images/gm_servers.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Servers'),
            ],
            [
                'link'  => '',
                'label' => __('Manage Servers'),
            ],
        ]
    );

    // Move SNMP modules back to the enterprise server.
    if (isset($_GET['server_reset_snmp_enterprise']) === true) {
        $result = db_process_sql('UPDATE tagente_estado SET last_error=0');

        if ($result === false) {
            ui_print_error_message(__('Unsuccessfull action'));
        } else {
            ui_print_success_message(__('Successfully action'));
        }
    }

    // Reset module count.
    if (isset($_GET['server_reset_counts'])) {
        $reslt = db_process_sql('UPDATE tagente SET update_module_count=1, update_alert_count=1');

        if ($result === false) {
            ui_print_error_message(__('Unsuccessfull action'));
        } else {
            ui_print_success_message(__('Successfully action'));
        }
    }

    if (isset($_GET['delete'])) {
        $id_server = get_parameter_get('server_del');

        $result = db_process_sql_delete('tserver', ['id_server' => $id_server]);

        if ($result !== false) {
             ui_print_success_message(__('Server deleted successfully'));
        } else {
            ui_print_error_message(__('There was a problem deleting the server'));
        }
    } else if (isset($_GET['update'])) {
        $address = trim(io_safe_output(get_parameter_post('address')), ' ');
        $description = trim(get_parameter_post('description'), '&#x20;');
        $id_server = get_parameter_post('server');
        $exec_proxy = get_parameter_post('exec_proxy');
        $port = get_parameter_post('port');

        $port_number = empty($port) ? 0 : $port;

        $values = [
            'ip_address'  => $address,
            'description' => $description,
            'exec_proxy'  => $exec_proxy,
            'port'        => $port_number,
        ];
        $result = db_process_sql_update('tserver', $values, ['id_server' => $id_server]);
        if ($result !== false) {
            ui_print_success_message(__('Server updated successfully'));
        } else {
            ui_print_error_message(__('There was a problem updating the server'));
        }
    } else if (isset($_GET['delete_conf_file'])) {
        $correct = false;
        $id_server = get_parameter('id_server');
        $ext = get_parameter('ext', '');
        $server_md5 = md5(io_safe_output(servers_get_name($id_server, 'none').$ext), false);

        if (file_exists($config['remote_config'].'/md5/'.$server_md5.'.srv.md5')) {
            // Server remote configuration editor.
            $file_name = $config['remote_config'].'/conf/'.$server_md5.'.srv.conf';
            $correct = @unlink($file_name);

            $file_name = $config['remote_config'].'/md5/'.$server_md5.'.srv.md5';
            $correct = @unlink($file_name);
        }

        ui_print_result_message(
            $correct,
            __('Conf file deleted successfully'),
            __('Could not delete conf file')
        );
    }


    $tiny = false;
    include $config['homedir'].'/godmode/servers/servers.build_table.php';
}
?>

<script language="javascript" type="text/javascript">

$(document).ready (function () {
    var id_server = '<?php echo $id_server; ?>';
    var server_type = '<?php echo $row['server_type']; ?>';
    $("#check_exec_server img").on("click", function () {
        $("#check_exec_server img").attr("src", "images/spinner.gif");

        check_process(id_server);
    });

    if (server_type == 13) {
        load_credential_boxes();
    }

    function load_credential_boxes () {
        var parameters = {};
        parameters['page'] = 'enterprise/include/ajax/servers.ajax';
        parameters['load_credential_boxes'] = 1;
        parameters['id_server'] = id_server;
        parameters['server_name'] = "<?php echo $row['name']; ?>";

        jQuery.get(
            "ajax.php",
            parameters,
            function (data) {
                $(".white-box-content").html(data);

                $("#submit-add").click(function (e) {
                    add_credential_boxes();
                });

                $("[id^=delete-]").click(function (e) {
                    delete_credential_boxes(e.currentTarget.id);
                });

                $("[id^=update-]").click(function (e) {
                    load_update_credential_boxes(e.currentTarget.id);
                });
            },
            "html"
        );
    }

    function add_credential_boxes () {
        $(".white-box-content").html('');
        var parameters2 = {};
        parameters2['page'] = 'enterprise/include/ajax/servers.ajax';
        parameters2['add_credential_boxes'] = 1;

        jQuery.get(
            "ajax.php",
            parameters2,
            function (data2) {
                $(".white-box-content").html(data2);

                // Insert credential
                $("#submit-add").click(function (e) {
                    save_credential_boxes();
                })
            },
            "html"
        );
    }

    function save_credential_boxes () {
        var parameters3 = {};
        parameters3['page'] = 'enterprise/include/ajax/servers.ajax';
        parameters3['save_credential_boxes'] = 1;
        parameters3['subnet'] = $("#text-subnet").val();
        parameters3['name'] = $("#text-name").val();
        parameters3['pass'] = $("#password-pass").val();
        parameters3['server_name'] = "<?php echo $row['name']; ?>";


        jQuery.post(
            "ajax.php",
            parameters3,
            function (data3) {
                $(".white-box-content").html('');
                load_credential_boxes();
            },
            "html"
        );
    }

    function delete_credential_boxes (datas) {
        var parameters = {};
        parameters['page'] = 'enterprise/include/ajax/servers.ajax';
        parameters['delete_credential_boxes'] = 1;
        parameters['server_name'] = "<?php echo $row['name']; ?>";
        parameters['datas'] = datas;

        jQuery.post(
            "ajax.php",
            parameters,
            function (data) {
                $(".white-box-content").html('');
                load_credential_boxes();
            },
            "html"
        );
    }

    function load_update_credential_boxes (datas) {
        var parameters = {};
        parameters['page'] = 'enterprise/include/ajax/servers.ajax';
        parameters['load_update_credential_boxes'] = 1;
        parameters['datas'] = datas;

        jQuery.get(
            "ajax.php",
            parameters,
            function (data) {
                $(".white-box-content").html(data);

                $("#submit-update").click(function (e) {
                    update_credential_boxes(datas);
                });
            },
            "html"
        );
    }

    function update_credential_boxes(datas) {
        var parameters = {};
        parameters['page'] = 'enterprise/include/ajax/servers.ajax';
        parameters['update_credential_boxes'] = 1;
        parameters['subnet'] = $("#text-subnet").val();
        parameters['name'] = $("#text-name").val();
        parameters['pass'] = $("#password-pass").val();
        parameters['server_name'] = "<?php echo $row['name']; ?>";
        parameters['old_datas'] = datas;

        jQuery.post(
            "ajax.php",
            parameters,
            function (data) {
                $(".white-box-content").html('');
                load_credential_boxes();
            },
            "html"
        );
    }

});

function check_process (id_server) {
    var parameters = {};
    parameters['page'] = 'enterprise/include/ajax/servers.ajax';
    parameters['check_exec_server'] = 1;
    parameters['id_server'] = id_server;
    
    jQuery.post(
        "ajax.php",
        parameters,
        function (data) {
            if (data['correct']) {
                $("#check_exec_server img").attr("src", "images/dot_green.png");
            }
            else {
                $("#check_exec_server img").attr("src", "images/dot_red.png");
                $("#check_error_message").empty();
                $("#check_error_message").append("<span>" + data['message'] + "</span>");
            }
        },
        "json"
    );
}

</script>
