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
            $this->operation    = get_parameter('operation', 0);
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

            // Capture custom commands.
            $this->pathCustomComm = [];
            foreach ($_REQUEST as $customKey => $customValue) {
                if ((bool) preg_match('/command_custom_/', $customKey) === true) {
                    $temporaryCustomCommandId = explode('_', $customKey);
                    $customCommandId = $temporaryCustomCommandId[2];
                    // Define array for host the command/parameters pair data.
                    $this->pathCustomComm[$customValue] = [];
                    // Ensure the information.
                    $this->pathCustomComm[$customValue]['command_custom'] = (string) get_parameter('command_custom_'.$customCommandId);
                    $this->pathCustomComm[$customValue]['params_custom'] = (string) get_parameter('params_custom_'.$customCommandId);
                }
            }
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

        $i = 0;
        $sounds = $this->get_sounds();

        if ($this->updatePaths === true) {
            $external_tools_config = [];
            $external_tools_config['traceroute_path'] = $this->pathTraceroute;
            $external_tools_config['ping_path']       = $this->pathPing;
            $external_tools_config['nmap_path']       = $this->pathNmap;
            $external_tools_config['dig_path']        = $this->pathDig;
            $external_tools_config['snmpget_path']    = $this->pathSnmpget;

            $otherParameters = [];
            $otherParameters['sound_alert']      = (string) get_parameter('sound_alert');
            $otherParameters['sound_critical']   = (string) get_parameter('sound_critical');
            $otherParameters['sound_warning']    = (string) get_parameter('sound_warning');
            $otherParameters['graphviz_bin_dir'] = (string) get_parameter('graphviz_bin_dir');

            if (empty($this->pathCustomComm) === false) {
                $external_tools_config['custom_commands'] = $this->pathCustomComm;
            }

            foreach ($otherParameters as $keyParam => $valueParam) {
                $result = config_update_value($keyParam, $valueParam);

                if ($result === false) {
                    break;
                }
            }

            if ($result === true) {
                $result = config_update_value(
                    'external_tools_config',
                    json_encode($external_tools_config)
                );
            }

            ui_print_result_message(
                ($result),
                __('Changes successfully saved.'),
                __('Changes not saved.')
            );
        } else {
            if (isset($config['external_tools_config']) === true) {
                $external_tools_config_output = io_safe_output($config['external_tools_config']);
                $external_tools_config = json_decode($external_tools_config_output, true);
                // Setting paths.
                $this->pathTraceroute = $external_tools_config['traceroute_path'];
                $this->pathPing       = $external_tools_config['ping_path'];
                $this->pathNmap       = $external_tools_config['nmap_path'];
                $this->pathDig        = $external_tools_config['dig_path'];
                $this->pathSnmpget    = $external_tools_config['snmpget_path'];
                $this->pathCustomComm = ($external_tools_config['custom_commands'] ?? ['a' => 'a']);
            }
        }

        // Make the table for show the form.
        $table = new stdClass();
        $table->width = '100%';
        $table->id = 'commandsTable';

        $table->data = [];

        $table->data[$i][0] = __('Sound for Alert fired');
        $table->data[$i][1] = html_print_select(
            $sounds,
            'sound_alert',
            $config['sound_alert'],
            'replaySound(\'alert\');',
            '',
            '',
            true
        );
        $table->data[$i][1] .= html_print_anchor(
            [
                'href'    => 'javascript:toggleButton(\'alert\')',
                'content' => html_print_image(
                    'images/control_play_col.png',
                    true,
                    [
                        'id'    => 'button_sound_warning',
                        'style' => 'vertical-align: middle;',
                        'width' => '16',
                        'title' => __('Play sound'),
                    ]
                ),
            ],
            true
        );
        $table->data[$i++][1] .= '<div id="layer_sound_alert"></div>';

        $table->data[$i][0] = __('Sound for Monitor critical');
        $table->data[$i][1] = html_print_select(
            $sounds,
            'sound_critical',
            $config['sound_critical'],
            'replaySound(\'critical\');',
            '',
            '',
            true
        );
        $table->data[$i][1] .= html_print_anchor(
            [
                'href'    => 'javascript:toggleButton(\'critical\')',
                'content' => html_print_image(
                    'images/control_play_col.png',
                    true,
                    [
                        'id'    => 'button_sound_warning',
                        'style' => 'vertical-align: middle;',
                        'width' => '16',
                        'title' => __('Play sound'),
                    ]
                ),
            ],
            true
        );
        $table->data[$i++][1] .= '<div id="layer_sound_critical"></div>';

        $table->data[$i][0] = __('Sound for Monitor warning');
        $table->data[$i][1] = html_print_select(
            $sounds,
            'sound_warning',
            $config['sound_warning'],
            'replaySound(\'warning\');',
            '',
            '',
            true
        );
        $table->data[$i][1] .= html_print_anchor(
            [
                'href'    => 'javascript:toggleButton(\'warning\')',
                'content' => html_print_image(
                    'images/control_play_col.png',
                    true,
                    [
                        'id'    => 'button_sound_warning',
                        'style' => 'vertical-align: middle;',
                        'width' => '16',
                        'title' => __('Play sound'),
                    ]
                ),
            ],
            true
        );
        $table->data[$i++][1] .= '<div id="layer_sound_warning"></div>';

        $table->data[$i][0] = __('Custom graphviz directory');
        $table->data[$i++][1] = html_print_input_text(
            'graphviz_bin_dir',
            $config['graphviz_bin_dir'],
            '',
            25,
            255,
            true
        );

        $table->data[$i][0] = __('Traceroute path');
        $table->data[$i++][1] = html_print_input_text('traceroute_path', $this->pathTraceroute, '', 40, 255, true);

        $table->data[$i][0] = __('Ping path');
        $table->data[$i++][1] = html_print_input_text('ping_path', $this->pathPing, '', 40, 255, true);

        $table->data[$i][0] = __('Nmap path');
        $table->data[$i++][1] = html_print_input_text('nmap_path', $this->pathNmap, '', 40, 255, true);

        $table->data[$i][0] = __('Dig path');
        $table->data[$i++][1] = html_print_input_text('dig_path', $this->pathDig, '', 40, 255, true);

        $table->data[$i][0] = __('Snmpget path');
        $table->data[$i++][1] = html_print_input_text('snmpget_path', $this->pathSnmpget, '', 40, 255, true);

        $table->data[$i][0] = html_print_div(
            [
                'class'   => 'title_custom_commands bolder float-left',
                'content' => __('Custom commands'),
            ],
            true
        );
        $table->data[$i++][0] .= html_print_div(
            [
                'id'      => 'add_button_custom_command',
                'content' => html_print_image(
                    'images/add.png',
                    true,
                    [
                        'title'   => __('Add new custom command'),
                        'onclick' => 'manageCommandLines(event)',
                        'id'      => 'img_add_button_custom_command',
                    ]
                ),
            ],
            true
        );

        $table->data[$i][0] = __('Command');
        $table->data[$i++][1] = __('Parameters').ui_print_help_tip(__('Adding `_address_` macro will use agent\'s IP when perform the execution'), true);

        $y = 1;
        $iRow = $i;

        if (empty($this->pathCustomComm) === true) {
            $table->rowid[$iRow] = 'custom_row_'.$y;

            $table->data[$iRow][0] = $this->customCommandPair('command', $y);
            $table->data[$iRow][1] = $this->customCommandPair('params', $y);
            $table->data[$iRow][2] = $this->customCommandPair('delete', $y);
        } else {
            foreach ($this->pathCustomComm as $command) {
                // Fill the fields.
                $customCommand = ($command['command_custom'] ?? '');
                $customParams  = ($command['params_custom'] ?? '');
                // Attach the fields.
                $table->rowid[$iRow] = 'custom_row_'.$y;
                $table->data[$iRow][0] = $this->customCommandPair('command', $y, $customCommand);
                $table->data[$iRow][1] = $this->customCommandPair('params', $y, $customParams);
                $table->data[$iRow][2] = $this->customCommandPair('delete', $y);
                // Add another command.
                $y++;
                $iRow++;
            }
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
     * Prints the custom command fields.
     *
     * @param string  $type  Type of field.
     * @param integer $index Control index.
     * @param string  $value Value of this field.
     *
     * @return string
     */
    private function customCommandPair($type, $index=0, $value='')
    {
        $output = '';

        switch ($type) {
            case 'command':
            case 'params':
                $output = html_print_input_text(
                    $type.'_custom_'.$index,
                    $value,
                    '',
                    40,
                    255,
                    true
                );
            break;

            case 'delete':
                $output = html_print_div(
                    [
                        'id'      => 'delete_button_custom_'.$index,
                        'class'   => '',
                        'content' => html_print_image(
                            'images/delete.png',
                            true,
                            [
                                'title'   => __('Delete this custom command'),
                                'onclick' => 'manageCommandLines(event)',
                                'id'      => 'img_delete_button_custom_'.$index,
                            ]
                        ),
                    ],
                    true
                );
            break;

            default:
                // Do none.
            break;
        }

        return $output;
    }


    /**
     * Print the form for use the external tools.
     *
     * @return void
     */
    private function agentExternalToolsForm()
    {
        global $config;

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

        // Must be an a IP at least for work.
        if (empty($ids) === true) {
            ui_print_message(__('The agent doesn`t have an IP yet'), 'error', true);
            return;
        }

        $ips = db_get_all_rows_sql(
            sprintf(
                'SELECT ip FROM taddress WHERE id_a IN (%s)',
                join(',', $ids)
            )
        );

        // Make the data for show in table.
        $ipsSelect = array_reduce(
            $ips,
            function ($carry, $item) {
                $carry[$item['ip']] = $item['ip'];
                return $carry;
            }
        );

        // Get the list of available commands.
        $commandList = [
            COMMAND_TRACEROUTE => __('Traceroute'),
            COMMAND_PING       => __('Ping host & Latency'),
            COMMAND_SNMP       => __('SNMP Interface status'),
            COMMAND_NMAP       => __('Basic TCP Port Scan'),
            COMMAND_DIGWHOIS   => __('DiG/Whois Lookup'),
        ];

        // Adding custom commands.
        $tempCustomCommandsList = json_decode(io_safe_output($config['external_tools_config']), true);
        $customCommandsList     = $tempCustomCommandsList['custom_commands'];

        foreach ($customCommandsList as $customCommandKey => $customCommandValue) {
            $commandList[$customCommandKey] = $customCommandKey;
        }

        // Form table.
        $table = new StdClass();
        $table->class = 'databox filters w100p';
        $table->id = 'externalToolTable';

        $table->data = [];

        $table->data[0][0] = __('Operation');

        $table->data[0][1] = html_print_select(
            $commandList,
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

        if (isset($config['external_tools_config']) === true) {
            $external_tools_config = json_decode(io_safe_output($config['external_tools_config']), true);
            $traceroute_path = $external_tools_config['traceroute_path'];
            $ping_path       = $external_tools_config['ping_path'];
            $nmap_path       = $external_tools_config['nmap_path'];
            $dig_path        = $external_tools_config['dig_path'];
            $snmpget_path    = $external_tools_config['snmpget_path'];

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
     * @param mixed  $operation    Operation.
     * @param string $ip           Ip.
     * @param string $community    Community.
     * @param string $snmp_version SNMP version.
     *
     * @return string String formed result of execution.
     */
    public function externalToolsExecution($operation, string $ip, string $community, string $snmp_version)
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
                        global $config;

                        $tempCustomCommandsList = json_decode(io_safe_output($config['external_tools_config']), true);
                        $customCommandsList     = $tempCustomCommandsList['custom_commands'];
                        // If the selected operation exists or not.
                        if (isset($customCommandsList[$operation]) === true) {
                            // Setting custom commands.
                            $customCommand  = $customCommandsList[$operation]['command_custom'];
                            $customParams   = $customCommandsList[$operation]['params_custom'];
                            // If '_address_' macro is setted, attach to execution.
                            if ((bool) preg_match('/_address_/', $customParams) === true) {
                                $customParams   = preg_replace('/_address_/', $ip, $customParams);
                                $stringCommand  = __('Performing %s execution on %s', $customCommand, $ip);
                            } else {
                                $stringCommand  = __('Performing %s execution', $customCommand);
                            }

                            $executeCommand = sprintf('%s %s', $customCommand, $customParams);
                        } else {
                            // Nothing to do.
                            $stringCommand = '';
                            $executeCommand = '';
                        }
                    break;
                }

                $this->performExecution($executeCommand, $stringCommand);
            }
        }

        return $output;

    }


    /**
     * Return sounds path.
     *
     * @return string Path.
     */
    private function get_sounds()
    {
        global $config;

        $return = [];

        $files = scandir($config['homedir'].'/include/sounds');

        foreach ($files as $file) {
            if (strstr($file, 'wav') !== false) {
                $return['include/sounds/'.$file] = $file;
            }
        }

        return $return;
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

                    mostrarColumns($('#operation :selected').val());
                });
            
                // Manage network component oid field generation.
                function manageCommandLines(event) {
                    let buttonId = event.target.id;
                    let parentId = event.target.parentElement.id;
                    let action = parentId.split('_');
                    action = action[0];

                    if (action === 'add') {
                        let fieldLines = $("tr[id*=custom_row_]").length;
                        let fieldLinesAdded = fieldLines + 1;

                        // Ensure the first erase button is clickable
                        $("#img_delete_button_custom_1")
                            .attr("style", "opacity: 1;")
                            .addClass("clickable");

                        $("#custom_row_" + fieldLines).after(
                            $("#custom_row_" + fieldLines)
                                .clone()
                                .attr("id", 'custom_row_'+fieldLinesAdded)
                        );
                        let rowCommand = Array.from($("#custom_row_"+fieldLinesAdded).children());

                        rowCommand.forEach(function(value, index){
                            let thisId = $(value).attr("id");
                            let separatedId = thisId.split("-");
                            let fieldLinesAddedForNewId = parseInt(separatedId[1]) + 1;
                            let thisNewId = separatedId[0] + "-" + fieldLinesAddedForNewId + "-" + separatedId[2];
                            // Assignation of new Id for this cell
                            $(value).attr("id", thisNewId);

                            // Children text fields.
                            if (parseInt(separatedId[2]) === 0) {
                                $("#text-command_custom_"+fieldLines, "#"+thisNewId)
                                    .attr("name", "command_custom_"+fieldLinesAdded)
                                    .attr("id", "text-command_custom_"+fieldLinesAdded);
                            } else if (parseInt(separatedId[2]) === 1) {
                                $("#text-params_custom_"+fieldLines, "#"+thisNewId)
                                    .attr("id", "text-params_custom_"+fieldLinesAdded)
                                    .attr("name", "params_custom_"+fieldLinesAdded);
                            } else if (parseInt(separatedId[2]) === 2) {
                                $("#img_delete_button_custom_"+fieldLines, "#"+thisNewId)
                                    .attr("id", "img_delete_button_custom_"+fieldLinesAdded);
                            }
                        });

                    } else if (action === 'delete') {
                        let buttonIdDivided = buttonId.split("_");
                        let lineNumber = buttonIdDivided[buttonIdDivided.length-1];
                        let lineCount = parseInt($("tr[id*=custom_row_]").length);

                        if (parseInt(lineNumber) >= 1 && lineCount > 1) {
                            $("#custom_row_" + lineNumber).remove();
                        }

                        if (lineCount === 1) {
                            $("[id*=img_delete_button_custom_]")
                                .attr("style", "opacity: 0.5;")
                                .removeClass("clickable");
                        }
                    }
                }

                function mostrarColumns(value) {
                    if (parseInt(value) === 3) {
                        $('.snmpcolumn').show();
                    }
                    else {
                        $('.snmpcolumn').hide();
                    }
                }

                function toggleButton(type) {
                    if ($("#button_sound_" + type).attr('src') == 'images/control_pause_col.png') {
                        $("#button_sound_" + type).attr('src', 'images/control_play_col.png');
                        $('#layer_sound_' + type).html("");
                    }
                    else {
                        $("#button_sound_" + type).attr('src', 'images/control_pause_col.png');
                        $('#layer_sound_' + type).html("<audio src='" + $("#sound_" + type).val() + "' autoplay='true' hidden='true' loop='true'>");
                    }
                }

                function replaySound(type) {
                    if ($("#button_sound_" + type).attr('src') == 'images/control_pause_col.png') {
                        $('#layer_sound_' + type).html("");
                        $('#layer_sound_' + type).html("<audio src='" + $("#sound_" + type).val() + "' autoplay='true' hidden='true' loop='true'>");
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
