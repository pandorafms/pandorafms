<?php
/**
 * External Tools view Class.
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
 * External Tools class
 */
class ExternalTools extends HTML
{


    /**
     * Constructor.
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
            $this->id_agente    = (int) get_parameter('id_agente', 0);
            $this->operation    = (int) get_parameter('operation', 0);
            $this->community    = (string) get_parameter('community', 'public');
            $this->ip           = (string) get_parameter('select_ips');
            $this->snmp_version = (string) get_parameter('select_version');
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
     * Run action.
     *
     * @return void
     */
    public function run()
    {
        if ($this->origin === 'agent') {
            // Print tool form.
            $this->agentExternalToolsForm();
        } else if ($this->origin === 'setup') {
            // Print setup form.
            $this->setupExternalToolsForm();
        }

        // Anyway, load JS.
        $this->loadJS();
    }


    /**
     * Print the form for setup the external tools.
     *
     * @return void
     */
    private function setupExternalToolsForm()
    {
        global $config;

        if ($this->updatePaths === true) {
            $network_tools_config = [];
            $network_tools_config['traceroute_path'] = $this->pathTraceroute;
            $network_tools_config['ping_path']       = $this->pathPing;
            $network_tools_config['nmap_path']       = $this->pathNmap;
            $network_tools_config['dig_path']        = $this->pathDig;
            $network_tools_config['snmpget_path']    = $this->pathSnmpget;

            $result = config_update_value(
                'network_tools_config',
                json_encode($network_tools_config)
            );

            ui_print_result_message(
                $result,
                __('Set the paths.'),
                __('Set the paths.')
            );
        } else {
            if (isset($config['network_tools_config']) === true) {
                $network_tools_config_output = io_safe_output($config['network_tools_config']);
                $network_tools_config = json_decode($network_tools_config_output, true);
                // Setting paths.
                $this->pathTraceroute = $network_tools_config['traceroute_path'];
                $this->pathPing       = $network_tools_config['ping_path'];
                $this->pathNmap       = $network_tools_config['nmap_path'];
                $this->pathDig        = $network_tools_config['dig_path'];
                $this->pathSnmpget    = $network_tools_config['snmpget_path'];
                $this->pathCustomComm = ($network_tools_config['custom_command'] ?? ['a' => 'a']);
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

        $table->data[5][0] = html_print_div(
            [
                'class'   => 'title_custom_commands bolder float-left',
                'content' => __('Custom commands'),
            ],
            true
        );

        $table->data[5][0] .= html_print_div(
            [
                'id'      => 'add_button_custom_command',
                'content' => html_print_image(
                    'images/add.png',
                    true,
                    [ 'title' => __('Add new custom command') ]
                ),
            ],
            true
        );

        $table->data[6][0] = __('Command');
        $table->data[6][1] = __('Parameters');

        $i = 0;
        $iRow = 6;
        foreach ($this->pathCustomComm as $command) {
            $i++;
            $iRow++;

            // Fill the fields.
            $customCommand = ($command['custom_command'] ?? '');
            $customParams  = ($command['custom_params'] ?? '');
            // Attach the fields.
            $table->rowid[$iRow] = 'custom_row_'.$i;
            $table->data[$iRow][0] = html_print_input_text(
                'command_custom_'.$i,
                $customCommand,
                '',
                40,
                255,
                true
            );
            $table->data[$iRow][1] = html_print_input_text(
                'params_custom_'.$i,
                $customParams,
                '',
                40,
                255,
                true
            );
            $table->data[$iRow][2] = html_print_div(
                [
                    'id'      => 'delete_custom_'.$i,
                    'class'   => '',
                    'content' => html_print_image(
                        'images/delete.png',
                        true,
                        ['title' => __('Delete this custom command')]
                    ),
                ],
                true
            );
        }

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
     * Print the form for use the external tools.
     *
     * @return void
     */
    private function agentExternalToolsForm()
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
        $table->id = 'externalToolTable';

        $table->data = [];

        $table->data[0][0] = __('Operation');

        $table->data[0][1] = html_print_select(
            [
                COMMAND_TRACEROUTE => __('Traceroute'),
                COMMAND_PING       => __('Ping host & Latency'),
                COMMAND_SNMP       => __('SNMP Interface status'),
                COMMAND_NMAP       => __('Basic TCP Port Scan'),
                COMMAND_DIGWHOIS   => __('DiG/Whois Lookup'),
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
        $output .= '<form name="actionbox" method="post">';
        $output .= html_print_table($table, true);
        $output .= '</form>';

        html_print_div(
            [
                'class'   => '',
                'style'   => 'width: 100%',
                'content' => $output,
            ]
        );

        if ($this->operation !== 0) {
            // Execute form.
            echo $this->externalToolsExecution($this->operation, $this->ip, $this->community, $this->snmp_version);
        }
    }


    /**
     * Searchs for command.
     *
     * @param string $command Command.
     *
     * @return string Path.
     */
    private function whereIsTheCommand(string $command)
    {
        global $config;

        if (isset($config['network_tools_config']) === true) {
            $network_tools_config = json_decode(io_safe_output($config['network_tools_config']), true);
            $traceroute_path = $network_tools_config['traceroute_path'];
            $ping_path       = $network_tools_config['ping_path'];
            $nmap_path       = $network_tools_config['nmap_path'];
            $dig_path        = $network_tools_config['dig_path'];
            $snmpget_path    = $network_tools_config['snmpget_path'];

            switch ($command) {
                case 'traceroute':
                    if (empty($traceroute_path) === false) {
                        return $traceroute_path;
                    }
                break;

                case 'ping':
                    if (empty($ping_path) === false) {
                        return $ping_path;
                    }
                break;

                case 'nmap':
                    if (empty($nmap_path) === false) {
                        return $nmap_path;
                    }
                break;

                case 'dig':
                    if (empty($dig_path) === false) {
                        return $dig_path;
                    }
                break;

                case 'snmpget':
                    if (empty($snmpget_path) === false) {
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

        if (empty($result) === true) {
            return null;
        }

        $result = explode(' ', $result);
        $fullpath = trim($result[0]);

        if (file_exists($fullpath) === false) {
            return null;
        }

        return $fullpath;
    }


    /**
     * Create the output for show.
     *
     * @param string $command Command for execute.
     * @param string $caption Description of the execution.
     *
     * @return void
     */
    private function performExecution(string $command='', string $caption='')
    {
        $output = '';

        try {
            // If caption is not added, don't show anything.
            if (empty($caption) === false) {
                $output .= sprintf('<h3>%s</h3>', $caption);
            }

            $output .= '<pre>';

            // Only perform an execution if command is passed. Avoid errors.
            if (empty($command) === false) {
                ob_start();
                system($command);
                $output .= ob_get_clean();
            } else {
                $output .= __('No command for perform');
            }

            $output .= '</pre>';
        } catch (\Throwable $th) {
            $output = __('Something went wrong while perform the execution. Please check the configuration.');
        }

        echo $output;
    }


    /**
     * Execute external tools action.
     *
     * @param integer $operation    Operation.
     * @param string  $ip           Ip.
     * @param string  $community    Community.
     * @param string  $snmp_version SNMP version.
     *
     * @return string String formed result of execution.
     */
    public function externalToolsExecution(int $operation, string $ip, string $community, string $snmp_version)
    {
        $output = '';

        if (validate_address($ip) === false) {
            $output .= ui_print_error_message(
                __('The ip or dns name entered cannot be resolved'),
                '',
                true
            );
        } else {
            if ($operation === COMMAND_SNMP) {
                $snmp_obj = [
                    'ip_target'      => $ip,
                    'snmp_version'   => $snmp_version,
                    'snmp_community' => $community,
                    'format'         => '-Oqn',
                ];

                echo '<h3>'.__('SNMP information for ').$ip.'</h3>';

                $snmp_obj['base_oid'] = '.1.3.6.1.2.1.1.3.0';
                $result = get_h_snmpwalk($snmp_obj);
                if (empty($result) === true) {
                    ui_print_error_message(__('Target unreachable.'));
                    return null;
                } else {
                    echo '<h4>'.__('Uptime').'</h4>';
                    echo '<pre>';
                    echo array_pop($result);
                    echo '</pre>';
                }

                $snmp_obj['base_oid'] = '.1.3.6.1.2.1.1.1.0';
                $result = get_h_snmpwalk($snmp_obj);
                if (empty($result) === true) {
                    ui_print_error_message(__('Target unreachable.'));
                    return null;
                } else {
                    echo '<h4>'.__('Device info').'</h4>';
                    echo '<pre>';
                    echo array_pop($result);
                    echo '</pre>';
                }

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
            } else if ($operation === COMMAND_DIGWHOIS) {
                echo '<h3>'.__('Domain and IP information for ').$ip.'</h3>';

                // Dig execution.
                $dig = $this->whereIsTheCommand('dig');
                if (empty($dig) === true) {
                    ui_print_error_message(__('Dig executable does not exist.'));
                } else {
                    $this->performExecution($dig);
                }

                // Whois execution.
                $whois = $this->whereIsTheCommand('whois');
                if (empty($whois) === true) {
                    ui_print_error_message(__('Whois executable does not exist.'));
                } else {
                    $this->performExecution($whois);
                }

                return;
            } else {
                switch ($operation) {
                    case COMMAND_TRACEROUTE:
                        $command = $this->whereIsTheCommand('traceroute');
                        if (empty($command) === true) {
                            ui_print_error_message(__('Traceroute executable does not exist.'));
                            return;
                        } else {
                            $stringCommand = __('Traceroute to %s', $ip);
                            $executeCommand = sprintf('%s %s', $command, $ip);
                        }
                    break;

                    case COMMAND_PING:
                        $command = $this->whereIsTheCommand('ping');
                        if (empty($command) === true) {
                            ui_print_error_message(__('Ping executable does not exist.'));
                            return;
                        } else {
                            $stringCommand = __('Ping to %s', $ip);
                            $executeCommand = sprintf('%s -c 5 %s', $command, $ip);
                        }
                    break;

                    case COMMAND_NMAP:
                        $command = $this->whereIsTheCommand('nmap');
                        if (empty($command) === true) {
                            ui_print_error_message(__('Nmap executable does not exist.'));
                            return;
                        } else {
                            $stringCommand = __('Basic TCP Scan on %s', $ip);
                            $executeCommand = sprintf('%s -F %s', $command, $ip);
                        }
                    break;

                    default:
                        // Nothing to do.
                        $stringCommand = '';
                        $executeCommand = '';
                    break;
                }

                $this->performExecution($executeCommand, $stringCommand);
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
                    let custom_command = $('#add_button_custom_command');
                
                    custom_command.on('click', function(event){
                        console.log(event);
                    });


                    mostrarColumns($('#operation :selected').val());
                });
            
                // Manage network component oid field generation.
                function manageCommandLines(action, type) {
                    var fieldLines = $("tr[id*=network_component-" + type + "]").length;
                    var protocol = $("#module_protocol").val();
                    if (action === "add") {
                        let lineNumber = fieldLines + 1;
                        let textForAdd =
                        type === "oid-list-pluginRow-snmpRow"
                            ? "_oid_" + lineNumber + "_"
                            : lineNumber;

                        $("#network_component-manage-" + type).before(
                        $("#network_component-" + type + "-row-1")
                            .clone()
                            .attr("id", "network_component-" + type + "-row-" + lineNumber)
                        );

                        $("#network_component-" + type + "-row-" + lineNumber + " input")
                        .attr("name", "extra_field_" + protocol + "_" + lineNumber)
                        .attr("id", "extra_field_" + protocol + "_" + lineNumber);

                        $("#network_component-" + type + "-row-" + lineNumber + " td div").html(
                        textForAdd
                        );

                        $("#del_field_button")
                        .attr("style", "opacity: 1;")
                        .addClass("clickable");
                    } else if (action === "del") {
                        if (fieldLines >= 2) {
                        $("#network_component-" + type + "-row-" + fieldLines).remove();
                        }

                        if (fieldLines == 2) {
                        $("#del_field_button")
                            .attr("style", "opacity: 0.5;")
                            .removeClass("clickable");
                        }
                    }
                }

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
