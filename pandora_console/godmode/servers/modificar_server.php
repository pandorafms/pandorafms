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
// Load global vars
global $config;

require_once 'include/functions_servers.php';
require_once 'include/functions_graph.php';

check_login();

if (! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access Server Management'
    );
    include 'general/noaccess.php';
    exit;
}

if (isset($_GET['server'])) {
    $id_server = get_parameter_get('server');
    // Headers
    ui_print_page_header(__('Update Server'), 'images/gm_servers.png', false, 'servers', true);
    $sql = sprintf('SELECT name, ip_address, description, server_type, exec_proxy FROM tserver WHERE id_server = %d', $id_server);
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

    $table->cellpadding = 4;
    $table->cellspacing = 4;
    $table->width = '100%';
    $table->class = 'databox filters';

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

    if (enterprise_installed()) {
        $table->data[] = [
            __('Type'),
            $server_type,
        ];
        if ($row['server_type'] == 13 || $row['server_type'] == 1) {
            $table->data[] = [
                __('Exec Server'),
                html_print_checkbox('exec_proxy', 1, $row['exec_proxy'], true),
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

    echo '<div class="action-buttons" style="width: 100%">';
    echo '<input type="submit" class="sub upd" value="'.__('Update').'">';
    echo '</div>';
} else if (isset($_GET['server_remote'])) {
    // Headers
    $id_server = get_parameter_get('server_remote');
    $ext = get_parameter('ext', '');
    ui_print_page_header(__('Remote Configuration'), 'images/gm_servers.png', false, 'servers', true);
    enterprise_include('godmode/servers/server_disk_conf_editor.php');
} else {
    // Header
    ui_print_page_header(__('%s servers', get_product_name()), 'images/gm_servers.png', false, 'servers', true);

    // Move SNMP modules back to the enterprise server
    if (isset($_GET['server_reset_snmp_enterprise'])) {
        $result = db_process_sql('UPDATE tagente_estado SET last_error=0');

        if ($result === false) {
            ui_print_error_message(__('Unsuccessfull action'));
        } else {
            ui_print_success_message(__('Successfully action'));
        }
    }

    // Reset module count
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
        $address = get_parameter_post('address');
        $description = get_parameter_post('description');
        $id_server = get_parameter_post('server');
        $exec_proxy = get_parameter_post('exec_proxy');

        $values = [
            'ip_address'  => $address,
            'description' => $description,
            'exec_proxy'  => $exec_proxy,
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
            // Server remote configuration editor
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
    $("#check_exec_server img").on("click", function () {
        $("#check_exec_server img").attr("src", "images/spinner.gif");

        check_process("<?php echo $id_server; ?>");
    });
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
