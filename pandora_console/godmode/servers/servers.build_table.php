<?php
/**
 * Table builder for Servers View.
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Monitoring.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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
require_once 'include/functions_clippy.php';
require_once 'pending_alerts_list.php';

global $config;

check_login();

if ((bool) check_acl($config['id_user'], 0, 'AR') === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Server Management'
    );
    include 'general/noaccess.php';
    exit;
}

global $tiny;
global $hidden_toggle;
$date = time();

$servers = servers_get_info();
if ($servers === false) {
    $server_clippy = clippy_context_help('servers_down');
    echo "<div class='nf'>".__('There are no servers configured into the database').$server_clippy.'</div>';
    return;
}

$table = new StdClass();
$table->class = 'info_table';
$table->cellpadding = 0;
$table->cellspacing = 0;
$table->size = [];

$table->style = [];
// $table->style[0] = 'font-weight: bold';
$table->align = [];
$table->align[1] = 'center';
$table->align[4] = 'center';
$table->align[9] = 'right';

$table->headstyle[1] = 'text-align:center';
$table->headstyle[4] = 'text-align:center';
$table->headstyle[9] = 'text-align:right;width: 120px;';

$table->titleclass = 'tabletitle';
$table->titlestyle = 'text-transform:uppercase;';

$table->style[7] = 'display: flex;align-items: center;';

$table->head = [];
$table->head[0] = __('Name');
$table->head[1] = __('Status');
$table->head[2] = __('Type');
$table->head[3] = __('Master');
$table->head[4] = __('Version');
$table->head[5] = __('Modules');
$table->head[6] = __('Lag').ui_print_help_tip(__('Avg. Delay(sec)/Modules delayed'), true);
$table->head[7] = __('T/Q').ui_print_help_tip(__('Threads / Queued modules currently'), true);
// This will have a column of data such as "6 hours".
$table->head[8] = __('Updated');

// Only Pandora Administrator can delete servers.
if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
    $table->head[9] = '<span title="Operations">'.__('Op.').'</span>';
}

$table->data = [];
$names_servers = [];
$master = 1;
// The server with the highest number in master, will be the real master.
foreach ($servers as $server) {
    if ($server['master'] > $master) {
        $master = $server['master'];
    }
}

$ext = '';

// Check for any data-type server present in servers list. If none, enable server access for first server.
if (array_search('data', array_column($servers, 'type')) === false) {
    $ext = '_server';
}

foreach ($servers as $server) {
    $data = [];

    $table->cellclass[] = [
        3 => 'progress_bar',
        9 => 'table_action_buttons',
    ];
    $data[0] = '<span title="'.$server['version'].'">'.strip_tags($server['name']).'</span>';

    $server_keepalive = time_w_fixed_tz($server['keepalive']);

    if ($server['server_keepalive_utimestamp'] > 0) {
        $server_keepalive = $server['server_keepalive_utimestamp'];
    }

    // Status.
    $data[1] = ui_print_status_image(STATUS_SERVER_OK, '', true);
    if ($server['status'] == -1) {
        $data[1] = ui_print_status_image(
            STATUS_SERVER_CRASH,
            __('Server has crashed.'),
            true
        );
    
    } else if ((int) ($server['disabled'] == 1)){
        $data[1] = ui_print_status_image(
            STATUS_SERVER_STANDBY,
            __('Server was manually disabled.'),
            true
        );

    } else if ((int) ($server['status'] === 0)
        || (($date - $server_keepalive) > ($server['server_keepalive']) * 2)
    ) {
        $data[1] = ui_print_status_image(
            STATUS_SERVER_DOWN,
            __('Server is stopped.'),
            true
        );
    }

    // Type.
    $data[2] = '<span class="nowrap">'.$server['img'].'&nbsp;&nbsp;&nbsp;&nbsp;'.$server['server_name'];
    if ($server['master'] == $master) {
        $data[3] = __('Yes', true);
    } else {
        $data[3] = __('-');
    }

    if ((int) $server['exec_proxy'] === 1) {
        $data[2] .= html_print_image('images/star.png', true, ['title' => __('Exec server enabled')]);
    }

    switch ($server['type']) {
        case 'snmp':
        case 'event':
        case 'autoprovision':
        case 'migration':
            $data[4] = $server['version'];
            $data[5] = __('N/A');
            $data[6] = __('N/A');
        break;

        case 'export':
            $data[4] = $server['version'];
            $data[5] = $server['modules'].' '.__('of').' '.$server['modules_total'];
            $data[6] = __('N/A');
        break;

        default:
            $data[4] = $server['version'];
            $data[5] = $server['modules'].' '.__('of').' '.$server['modules_total'];
            $data[6] = '<span class="nowrap">'.$server['lag_txt'].'</span>';
        break;
    }

    $data[7] = '';
    if ($server['queued_modules']  >= $config['number_modules_queue']) {
        $data[7] .= '<div class="inline"><a onclick="show_dialog();" >'.html_print_image(
            'images/info-warning.svg',
            true,
            [
                'width' => 16,
                'heght' => 16,
                'class' => 'pulsate clickable',
                'style' => 'margin-left: -25px;',
            ]
        ).'</a></div>&nbsp;&nbsp;';
    }

    $data[7] .= $server['threads'].' : '.$server['queued_modules'];

    $data[8] = ui_print_timestamp($server['keepalive'], true);

    if ($server['type'] === 'data') {
        $ext = '_server';
    }

    $safe_server_name = servers_get_name($server['id_server']);
    if (($ext === '_server' || $server['type'] == 'enterprise satellite')) {
        if (servers_check_remote_config($safe_server_name.$ext) && enterprise_installed()) {
            $names_servers[$safe_server_name] = true;
        } else {
            $names_servers[$safe_server_name] = false;
        }
    }

    // Only Pandora Administrator can delete servers.
    if ((bool) check_acl($config['id_user'], 0, 'PM') === true) {
         $data[9] = '';

        if ($server['type'] === 'recon') {
            $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=tasklist').'">';
            $data[9] .= html_print_image(
                'images/snmp-trap@svg.svg',
                true,
                [
                    'title' => __('Manage Discovery tasks'),
                    'class' => 'main_menu_icon invert_filter',

                ]
            );
            $data[9] .= '</a>';
        }

        if ($server['type'] === 'data') {
            $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=0&server_reset_counts='.$server['id_server']).'">';
            $data[9] .= html_print_image(
                'images/force@svg.svg',
                true,
                [
                    'title' => __('Reset module status and fired alert counts'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[9] .= '</a>';
        } else if ($server['type'] === 'enterprise snmp') {
            $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=0&server_reset_snmp_enterprise='.$server['id_server']).'">';
            $data[9] .= html_print_image(
                'images/force@svg.svg',
                true,
                [
                    'title' => __('Claim back SNMP modules'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[9] .= '</a>';
        }

        if ($server['type'] === 'event' && (bool) check_acl($config['id_user'], 0, 'LM') === true) {
            $data[9] .= '<a class="open-alerts-list-modal" href="">';
            $data[9] .= html_print_image(
                'images/alert@svg.svg',
                true,
                [
                    'title' => __('Pending alerts list'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[9] .= '</a>';
        }

        $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&server='.$server['id_server']).'">';
        $data[9] .= html_print_image(
            'images/edit.svg',
            true,
            [
                'title' => __('Edit'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $data[9] .= '</a>';

        if (($names_servers[$safe_server_name] === true) && ($ext === '_server' || $server['type'] === 'enterprise satellite')) {
            $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$server['id_server'].'&ext='.$ext.'&tab=advanced_editor').'">';
            $data[9] .= html_print_image(
                'images/agents@svg.svg',
                true,
                [
                    'title' => __('Manage server conf'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[9] .= '</a>';

            $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_remote='.$server['id_server'].'&ext='.$ext).'">';
            $data[9] .= html_print_image(
                'images/remote-configuration@svg.svg',
                true,
                [
                    'title' => __('Remote configuration'),
                    'class' => 'main_menu_icon invert_filter',
                ]
            );
            $data[9] .= '</a>';
            $names_servers[$safe_server_name] = false;
        }

        $data[9] .= '<a href="'.ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&server_del='.$server['id_server'].'&amp;delete=1').'">';
        $data[9] .= html_print_image(
            'images/delete.svg',
            true,
            [
                'title'   => __('Delete'),
                'onclick' => "if (! confirm ('".__('Modules run by this server will stop working. Do you want to continue?')."')) return false",
                'class'   => 'main_menu_icon invert_filter',
            ]
        );
        $data[9] .= '</a>';
    }

    if ($tiny) {
        unset($data[5]);
        unset($data[7]);
        unset($data[8]);
        unset($data[9]);
    }

    $ext = '';

    array_push($table->data, $data);
}

if ($tiny) {
    unset($table->head[5]);
    unset($table->head[7]);
    unset($table->head[8]);
    unset($table->head[9]);
}

if ($tiny) {
    ui_toggle(
        html_print_table($table, true),
        __('Tactical server information'),
        '',
        '',
        $hidden_toggle
    );
} else {
    html_print_table($table);
}

?>
<script type="text/javascript">
    function show_dialog() {
        confirmDialog({
            title: "<?php echo __('Excesive Queued.'); ?>",
            message: "<?php echo __('You have too many items in the processing queue. This can happen if your server is overloaded and/or improperly configured. This could be something temporary, or a bottleneck. If it is associated with a delay in monitoring, with modules going to unknown, try increasing the number of threads.'); ?>",
            strOKButton: "<?php echo __('Close'); ?>",
            hideCancelButton: true,
            size: 675,
        });
    }

    function runIt() {
        $('.pulsate').animate({
            opacity: '1'
        }, 1000);
        $('.pulsate').animate({
            opacity: '0.6'
        }, 1000, runIt);
    }
    runIt();
</script>
