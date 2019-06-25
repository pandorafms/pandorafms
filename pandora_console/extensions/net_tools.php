<?php
/**
 * Net tools utils.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage NetTools
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

// Requires.
require_once $config['homedir'].'/include/functions.php';

// This extension is usefull only if the agent has associated IP.
$id_agente = get_parameter('id_agente');
$address = agents_get_address($id_agente);

if (!empty($address) || empty($id_agente)) {
    extensions_add_opemode_tab_agent(
        'network_tools',
        'Network Tools',
        'extensions/net_tools/nettool.png',
        'main_net_tools',
        'v1r1',
        'AW'
    );
}


/**
 * Searchs for command.
 *
 * @param string $command Command.
 *
 * @return string Path.
 */
function whereis_the_command($command)
{
    global $config;

    if (isset($config['network_tools_config'])) {
        $network_tools_config = json_decode($config['network_tools_config'], true);
        $traceroute_path = $network_tools_config['traceroute_path'];
        $ping_path = $network_tools_config['ping_path'];
        $nmap_path = $network_tools_config['nmap_path'];
        $dig_path = $network_tools_config['dig_path'];
        $snmpget_path = $network_tools_config['snmpget_path'];

        switch ($command) {
            case 'traceroute':
                if (!empty($traceroute_path)) {
                    return $traceroute_path;
                }
            break;

            case 'ping':
                if (!empty($ping_path)) {
                    return $ping_path;
                }
            break;

            case 'nmap':
                if (!empty($nmap_path)) {
                    return $nmap_path;
                }
            break;

            case 'dig':
                if (!empty($dig_path)) {
                    return $dig_path;
                }
            break;

            case 'snmpget':
                if (!empty($snmpget_path)) {
                    return $snmpget_path;
                }
            break;

            default:
            return null;
        }
    }

    ob_start();
    system('whereis '.$command);
    $output = ob_get_clean();
    $result = explode(':', $output);
    $result = trim($result[1]);

    if (empty($result)) {
        return null;
    }

    $result = explode(' ', $result);
    $fullpath = trim($result[0]);

    if (! file_exists($fullpath)) {
        return null;
    }

    return $fullpath;
}


/**
 * Execute net tools action.
 *
 * @param integer $operation    Operation.
 * @param string  $ip           Ip.
 * @param string  $community    Community.
 * @param string  $snmp_version SNMP version.
 *
 * @return void
 */
function net_tools_execute($operation, $ip, $community, $snmp_version)
{
    if (!validate_address($ip)) {
            ui_print_error_message(__('The ip or dns name entered cannot be resolved'));
    } else {
        switch ($operation) {
            case 1:
                $traceroute = whereis_the_command('traceroute');
                if (empty($traceroute)) {
                    ui_print_error_message(__('Traceroute executable does not exist.'));
                } else {
                    echo '<h3>'.__('Traceroute to ').$ip.'</h3>';
                    echo '<pre>';
                    echo system($traceroute.' '.$ip);
                    echo '</pre>';
                }
            break;

            case 2:
                $ping = whereis_the_command('ping');
                if (empty($ping)) {
                    ui_print_error_message(__('Ping executable does not exist.'));
                } else {
                    echo '<h3>'.__('Ping to %s', $ip).'</h3>';
                    echo '<pre>';
                    echo system($ping.' -c 5 '.$ip);
                    echo '</pre>';
                }
            break;

            case 4:
                $nmap = whereis_the_command('nmap');
                if (empty($nmap)) {
                    ui_print_error_message(__('Nmap executable does not exist.'));
                } else {
                    echo '<h3>'.__('Basic TCP Scan on ').$ip.'</h3>';
                    echo '<pre>';
                    echo system($nmap.' -F '.$ip);
                    echo '</pre>';
                }
            break;

            case 5:
                echo '<h3>'.__('Domain and IP information for ').$ip.'</h3>';

                $dig = whereis_the_command('dig');
                if (empty($dig)) {
                    ui_print_error_message(__('Dig executable does not exist.'));
                } else {
                    echo '<pre>';
                    echo system('dig '.$ip);
                    echo '</pre>';
                }

                $whois = whereis_the_command('whois');
                if (empty($whois)) {
                    ui_print_error_message(__('Whois executable does not exist.'));
                } else {
                    echo '<pre>';
                    echo system('whois '.$ip);
                    echo '</pre>';
                }
            break;

            case 3:
                $snmp_obj = [
                    'ip_target'      => $ip,
                    'snmp_version'   => $snmp_version,
                    'snmp_community' => $community,
                    'format'         => '-Oqn',
                ];

                $snmp_obj['base_oid'] = '.1.3.6.1.2.1.1.3.0';
                $result = get_h_snmpwalk($snmp_obj);
                echo '<h3>'.__('SNMP information for ').$ip.'</h3>';
                echo '<h4>'.__('Uptime').'</h4>';
                echo '<pre>';
                if (empty($result)) {
                    ui_print_error_message(__('Target unreachable.'));
                    break;
                } else {
                    echo array_pop($result);
                }

                echo '</pre>';
                echo '<h4>'.__('Device info').'</h4>';
                echo '<pre>';
                $snmp_obj['base_oid'] = '.1.3.6.1.2.1.1.1.0';
                $result = get_h_snmpwalk($snmp_obj);
                if (empty($result)) {
                    ui_print_error_message(__('Target unreachable.'));
                    break;
                } else {
                    echo array_pop($result);
                }

                echo '</pre>';

                echo '<h4>Interface Information</h4>';

                $table = new StdClass();
                $table->class = 'databox';
                $table->head = [];
                $table->head[] = __('Interface');
                $table->head[] = __('Status');

                $i = 0;

                $base_oid = '.1.3.6.1.2.1.2.2.1';
                $idx_oids = '.1';
                $names_oids = '.2';
                $status_oids = '.8';

                $snmp_obj['base_oid'] = $base_oid.$idx_oids;
                $idx = get_h_snmpwalk($snmp_obj);

                $snmp_obj['base_oid'] = $base_oid.$names_oids;
                $names = get_h_snmpwalk($snmp_obj);

                $snmp_obj['base_oid'] = $base_oid.$status_oids;
                $statuses = get_h_snmpwalk($snmp_obj);

                foreach ($idx as $k => $v) {
                    $index = str_replace($base_oid.$idx_oids, '', $k);
                    $name = $names[$base_oid.$names_oids.$index];

                    $status = $statuses[$base_oid.$status_oids.$index];

                    $table->data[$i][0] = $name;
                    $table->data[$i++][1] = $status;
                }

                html_print_table($table);
            break;

            default:
                // Ignore.
            break;
        }
    }

}


/**
 * Main function.
 *
 * @return void
 */
function main_net_tools()
{
    $operation = get_parameter('operation', 0);
    $community = get_parameter('community', 'public');
    $ip = get_parameter('select_ips');
    $snmp_version = get_parameter('select_version');

    // Show form.
    $id_agente = get_parameter('id_agente', 0);
    $principal_ip = db_get_sql(
        sprintf(
            'SELECT direccion FROM tagente WHERE id_agente = %d',
            $id_agente
        )
    );

    $list_address = db_get_all_rows_sql(
        sprintf(
            'SELECT id_a FROM taddress_agent WHERE id_agent = %d',
            $id_agente
        )
    );
    foreach ($list_address as $address) {
        $ids[] = join(',', $address);
    }

    $ips = db_get_all_rows_sql(
        sprintf(
            'SELECT ip FROM taddress WHERE id_a IN (%s)',
            join($ids)
        )
    );

    if ($ips == '') {
        echo "<div class='error' style='margin-top:5px'>".__('The agent hasn\'t got IP').'</div>';
        return;
    }

    // Javascript.
    ?>
<script type='text/javascript'>
    $(document).ready(function(){
        mostrarColumns($('#operation :selected').val());
    });

    function mostrarColumns(value) {
        if (value == 3) {
            $('.snmpcolumn').show();
        }
        else {
            $('.snmpcolumn').hide();
        }
    }
</script>
    <?php
    echo '<div>';
    echo "<form name='actionbox' method='post'>";
    echo "<table class='databox filters' width=100% id=netToolTable>";
    echo '<tr><td>';
    echo __('Operation');
    ui_print_help_tip(
        __('You can set the command path in the menu Administration -&gt; Extensions -&gt; Config Network Tools')
    );
    echo '</td><td>';

    html_print_select(
        [
            1 => __('Traceroute'),
            2 => __('Ping host & Latency'),
            3 => __('SNMP Interface status'),
            4 => __('Basic TCP Port Scan'),
            5 => __('DiG/Whois Lookup'),
        ],
        'operation',
        $operation,
        'mostrarColumns(this.value)',
        __('Please select')
    );

    echo '</td>';
    echo '<td>';
    echo __('IP address');
    echo '</td><td>';

    $ips_for_select = array_reduce(
        $ips,
        function ($carry, $item) {
            $carry[$item['ip']] = $item['ip'];
            return $carry;
        }
    );

    html_print_select(
        $ips_for_select,
        'select_ips',
        $principal_ip
    );
    echo '</td>';
    echo "<td class='snmpcolumn'>";
    echo __('SNMP Version');
    html_print_select(
        [
            '1'  => 'v1',
            '2c' => 'v2c',
        ],
        'select_version',
        $snmp_version
    );
    echo '</td><td class="snmpcolumn">';
    echo __('SNMP Community').'&nbsp;';
    html_print_input_text('community', $community);
    echo '</td><td>';
    echo "<input style='margin:0px;' name=submit type=submit class='sub next' value='".__('Execute')."'>";
    echo '</td>';
    echo '</tr></table>';
    echo '</form>';

    if ($operation) {
        // Execute form.
        net_tools_execute($operation, $ip, $community, $snmp_version);
    }

    echo '</div>';
}


/**
 * Add option.
 *
 * @return void
 */
function godmode_net_tools()
{
    global $config;

    check_login();

    if (! check_acl($config['id_user'], 0, 'PM')) {
        db_pandora_audit(
            'ACL Violation',
            'Trying to access Profile Management'
        );
        include 'general/noaccess.php';
        return;
    }

    ui_print_page_header(__('Config Network Tools'));

    $update_traceroute = (bool) get_parameter('update_traceroute', 0);

    $traceroute_path = (string) get_parameter('traceroute_path', '');
    $ping_path = (string) get_parameter('ping_path', '');
    $nmap_path = (string) get_parameter('nmap_path', '');
    $dig_path = (string) get_parameter('dig_path', '');
    $snmpget_path = (string) get_parameter('snmpget_path', '');

    if ($update_traceroute) {
        $network_tools_config = [];
        $network_tools_config['traceroute_path'] = $traceroute_path;
        $network_tools_config['ping_path'] = $ping_path;
        $network_tools_config['nmap_path'] = $nmap_path;
        $network_tools_config['dig_path'] = $dig_path;
        $network_tools_config['snmpget_path'] = $snmpget_path;

        $result = config_update_value('network_tools_config', json_encode($network_tools_config));

        ui_print_result_message(
            $result,
            __('Set the paths.'),
            __('Set the paths.')
        );
    } else {
        if (isset($config['network_tools_config'])) {
            $network_tools_config_output = io_safe_output($config['network_tools_config']);
            $network_tools_config = json_decode($network_tools_config_output, true);
            $traceroute_path = $network_tools_config['traceroute_path'];
            $ping_path = $network_tools_config['ping_path'];
            $nmap_path = $network_tools_config['nmap_path'];
            $dig_path = $network_tools_config['dig_path'];
            $snmpget_path = $network_tools_config['snmpget_path'];
        }
    }

    $table = null;
    $table->width = '100%';

    $table->data = [];

    $table->data[0][0] = __('Traceroute path');
    $table->data[0][0] .= ui_print_help_tip(__('If empty, %s will search the traceroute system.', get_product_name()), true);
    $table->data[0][1] = html_print_input_text('traceroute_path', $traceroute_path, '', 40, 255, true);

    $table->data[1][0] = __('Ping path');
    $table->data[1][0] .= ui_print_help_tip(__('If empty, %s will search the ping system.', get_product_name()), true);
    $table->data[1][1] = html_print_input_text('ping_path', $ping_path, '', 40, 255, true);

    $table->data[2][0] = __('Nmap path');
    $table->data[2][0] .= ui_print_help_tip(__('If empty, %s will search the nmap system.', get_product_name()), true);
    $table->data[2][1] = html_print_input_text('nmap_path', $nmap_path, '', 40, 255, true);

    $table->data[3][0] = __('Dig path');
    $table->data[3][0] .= ui_print_help_tip(__('If empty, %s will search the dig system', get_product_name()), true);
    $table->data[3][1] = html_print_input_text('dig_path', $dig_path, '', 40, 255, true);

    $table->data[4][0] = __('Snmpget path');
    $table->data[4][0] .= ui_print_help_tip(__('If empty, %s will search the snmpget system.', get_product_name()), true);
    $table->data[4][1] = html_print_input_text('snmpget_path', $snmpget_path, '', 40, 255, true);

    echo '<form id="form_setup" method="post" >';
    echo '<fieldset>';
    echo '<legend>'.__('Options').'</legend>';
    html_print_input_hidden('update_traceroute', 1);
    html_print_table($table);
    echo '</fieldset>';

    echo '<div class="action-buttons" style="width: '.$table->width.'">';
    html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
    echo '</div>';
    echo '</form>';
}


extensions_add_godmode_menu_option(__('Config Network Tools'), 'PM');
extensions_add_godmode_function('godmode_net_tools');
