<?php
/**
 * Net Tools view Class.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Setup
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

// Get global data.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';

/**
 * NetTools class
 */
class NetTools extends HTML
{


    /**
     * Undocumented function
     *
     * @param string $origin Origin of the request.
     */
    public function __construct(string $origin)
    {
        global $config;

        // Check if the user can access here.
        check_login();
        // Setting the origin.
        $this->origin = $origin;

        if ($this->origin === 'agent') {
            if (check_acl($config['id_user'], 0, 'AR') === false) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Agent Management'
                );
                include 'general/noaccess.php';
                return;
            }

            // Capture needed parameter for agent form.
            $this->operation = get_parameter('operation', 0);
            $this->community = get_parameter('community', 'public');
            $this->ip = get_parameter('select_ips');
            $this->snmp_version = get_parameter('select_version');

            // Show form.
            $this->id_agente = get_parameter('id_agente', 0);

            // Capture needed parameters for agent executions.
        } else if ($this->origin === 'setup') {
            if (check_acl($config['id_user'], 0, 'PM') === false) {
                db_pandora_audit(
                    'ACL Violation',
                    'Trying to access Profile Management'
                );
                include 'general/noaccess.php';
                return;
            }

            // Capture needed parameters for setup form.
            $this->updatePaths = (bool) get_parameter('update_paths', 0);
            // Capture paths.
            $this->pathTraceroute = (string) get_parameter('traceroute_path');
            $this->pathPing       = (string) get_parameter('ping_path');
            $this->pathNmap       = (string) get_parameter('nmap_path');
            $this->pathDig        = (string) get_parameter('dig_path');
            $this->pathSnmpget    = (string) get_parameter('snmpget_path');
        }

        return $this;

    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function run()
    {
        if ($this->origin === 'agent') {
            // Print tool form.
            $this->agentNetToolsForm();
        } else if ($this->origin === 'setup') {
            // Print setup form.
            $this->setupNetToolsForm();
        }

        // Anyway, load JS.
        $this->loadJS();
    }


    /**
     * Print the form for setup the network tools.
     *
     * @return void
     */
    private function setupNetToolsForm()
    {
        if ($this->updatePaths === true) {
            $network_tools_config = [];
            $network_tools_config['traceroute_path'] = $this->pathTraceroute;
            $network_tools_config['ping_path']       = $this->pathPing;
            $network_tools_config['nmap_path']       = $this->pathNmap;
            $network_tools_config['dig_path']        = $this->pathDig;
            $network_tools_config['snmpget_path']    = $this->pathSnmpget;

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
                // Setting paths.
                $this->pathTraceroute = $network_tools_config['traceroute_path'];
                $this->pathPing       = $network_tools_config['ping_path'];
                $this->pathNmap       = $network_tools_config['nmap_path'];
                $this->pathDig        = $network_tools_config['dig_path'];
                $this->pathSnmpget    = $network_tools_config['snmpget_path'];
            }
        }

        // Make the table for show the form.
        $table = new stdClass();
        $table->width = '100%';

        $table->data = [];

        $table->data[0][0] = __('Traceroute path');
        $table->data[0][1] = html_print_input_text('traceroute_path', $this->pathTraceroute, '', 40, 255, true);

        $table->data[1][0] = __('Ping path');
        $table->data[1][1] = html_print_input_text('ping_path', $this->pathPing, '', 40, 255, true);

        $table->data[2][0] = __('Nmap path');
        $table->data[2][1] = html_print_input_text('nmap_path', $this->pathNmap, '', 40, 255, true);

        $table->data[3][0] = __('Dig path');
        $table->data[3][1] = html_print_input_text('dig_path', $this->pathDig, '', 40, 255, true);

        $table->data[4][0] = __('Snmpget path');
        $table->data[4][1] = html_print_input_text('snmpget_path', $this->pathSnmpget, '', 40, 255, true);

        $form = '<form id="form_setup" method="post" >';
        $form .= '<fieldset>';
        $form .= '<legend>'.__('Options').'</legend>';
        $form .= html_print_input_hidden('update_paths', 1, true);
        $form .= html_print_table($table, true);
        $form .= '</fieldset>';
        $form .= html_print_div(
            [
                'id'      => '',
                'class'   => 'action-buttons',
                'style'   => 'width: 100%',
                'content' => html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"', true),
            ],
            true
        );

        $form .= '</form>';

        echo $form;
    }


    /**
     * Print the form for use the network tools.
     *
     * @return void
     */
    private function agentNetToolsForm()
    {
        $principal_ip = db_get_sql(
            sprintf(
                'SELECT direccion FROM tagente WHERE id_agente = %d',
                $this->id_agente
            )
        );

        $list_address = db_get_all_rows_sql(
            sprintf(
                'SELECT id_a FROM taddress_agent WHERE id_agent = %d',
                $this->id_agente
            )
        );
        foreach ($list_address as $address) {
            $ids[] = join(',', $address);
        }

        $ips = db_get_all_rows_sql(
            sprintf(
                'SELECT ip FROM taddress WHERE id_a IN (%s)',
                join(',', $ids)
            )
        );

        // Must be an a IP at least for work.
        if (empty($ips) === true) {
            html_print_div(
                [
                    'class'   => 'error',
                    'style'   => 'margin-top:5px',
                    'content' => __('The agent hasn\'t got IP'),
                ]
            );
            return;
        }

        // Make the data for show in table.
        $ipsSelect = array_reduce(
            $ips,
            function ($carry, $item) {
                $carry[$item['ip']] = $item['ip'];
                return $carry;
            }
        );

        // Form table.
        $table = new StdClass();
        $table->class = 'databox filters w100p';
        $table->id = 'netToolTable';

        $table->data = [];

        $table->data[0][0] = __('Operation');

        $table->data[0][1] = html_print_select(
            [
                1 => __('Traceroute'),
                2 => __('Ping host & Latency'),
                3 => __('SNMP Interface status'),
                4 => __('Basic TCP Port Scan'),
                5 => __('DiG/Whois Lookup'),
            ],
            'operation',
            $this->operation,
            'mostrarColumns(this.value)',
            __('Please select'),
            0,
            true
        );

        $table->data[0][2] = __('IP Adress');
        $table->data[0][3] = html_print_select(
            $ipsSelect,
            'select_ips',
            $principal_ip,
            '',
            '',
            0,
            true
        );

        $table->cellclass[0][4] = 'snmpcolumn';
        $table->data[0][4] = __('SNMP Version');
        $table->data[0][4] .= '&nbsp;';
        $table->data[0][4] .= html_print_select(
            [
                '1'  => 'v1',
                '2c' => 'v2c',
            ],
            'select_version',
            $this->snmp_version,
            '',
            '',
            0,
            true
        );

        $table->cellclass[0][5] = 'snmpcolumn';
        $table->data[0][5] = __('SNMP Community');
        $table->data[0][5] .= '&nbsp;';
        $table->data[0][5] .= html_print_input_text(
            'community',
            $this->community,
            '',
            50,
            255,
            true
        );

        $table->data[0][6] = "<input style='margin:0px;' name=submit type=submit class='sub next' value='".__('Execute')."'>";

        // Output string.
        $output = '';
        $output .= "<form name='actionbox' method='post'>";
        $output .= html_print_table($table, true);
        $output .= '</form>';

        html_print_div(
            [
                'class'   => '',
                'style'   => 'width: 100%',
                'content' => $output,
            ]
        );

        if ($this->operation === true) {
            // Execute form.
            $executionResult = $this->netToolsExecution($this->operation, $this->ip, $this->community, $this->snmp_version);
            echo $executionResult;
        }

        echo '</div>';

    }


    /**
     * Searchs for command.
     *
     * @param string $command Command.
     *
     * @return string Path.
     */
    private function whereIsTheCommand($command)
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
     * @return string String formed result of execution.
     */
    public function netToolsExecution(int $operation, string $ip, string $community, string $snmp_version)
    {
        $output = '';

        if (!validate_address($ip)) {
            $output .= ui_print_error_message(
                __('The ip or dns name entered cannot be resolved'),
                '',
                true
            );
        } else {
            switch ($operation) {
                case 1:
                    $traceroute = $this->whereIsTheCommand('traceroute');
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
                    $ping = $this->whereIsTheCommand('ping');
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
                    $nmap = $this->whereIsTheCommand('nmap');
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

                    $dig = $this->whereIsTheCommand('dig');
                    if (empty($dig)) {
                        ui_print_error_message(__('Dig executable does not exist.'));
                    } else {
                        echo '<pre>';
                        echo system('dig '.$ip);
                        echo '</pre>';
                    }

                    $whois = $this->whereIsTheCommand('whois');
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

        return $output;

    }


    /**
     * Load the JS and attach
     *
     * @return string Formed script string.
     */
    private function loadJS()
    {
        $str = '';
        ob_start();
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
        // Get the JS script.
        $str = ob_get_clean();
        // Return the loaded JS.
        echo $str;
        return $str;
    }


}
