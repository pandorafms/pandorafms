<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Controller for Audit Logs
 *
 * @category   Controller
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
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

use Mpdf\Tag\Address;
use PandoraFMS\Agent as PandoraFMSAgent;
use PandoraFMS\Enterprise\Agent;

use function Composer\Autoload\includeFile;

// Begin.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
require_once $config['homedir'].'/include/functions_servers.php';
enterprise_include_once('include/functions_satellite.php');

/**
 * Class SatelliteAgent
 */
class SatelliteAgent extends HTML
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'draw',
        'addAgent',
        'deleteAgent',
        'disableAgent',
        'loadModal',
    ];

    /**
     * Ajax page.
     *
     * @var string
     */
    private $ajaxController;


    /**
     * Class constructor
     *
     * @param string $ajaxController Ajax controller.
     */
    public function __construct(string $ajaxController)
    {
        global $config;

        check_login();

        if (check_acl($config['id_user'], 0, 'PM') === false
            && is_user_admin($config['id_user']) === true
        ) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Satellite agents'
            );
            include 'general/noaccess.php';
            return;
        }

        // Set the ajax controller.
        $this->ajaxController = $ajaxController;
        // Capture all parameters before start.
        $this->satellite_server = (int) get_parameter('server_remote');
        if ($this->satellite_server !== 0) {
            $this->satellite_name = servers_get_name($this->satellite_server);
            $this->satellite_config = (array) config_satellite_get_config_file($this->satellite_name);
        }
    }


    /**
     * Run view
     *
     * @return void
     */
    public function run()
    {
        global $config;
        // Javascript.
        ui_require_jquery_file('pandora');
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        $this->createBlock();

        // Datatables list.
        try {
            $checkbox_all = html_print_checkbox(
                'all_validate_box',
                1,
                false,
                true
            );

            $columns = [
                [
                    'text'  => 'm',
                    'extra' => $checkbox_all,
                    'class' => 'mw60px',
                ],
                'name',
                'address',
                'actions',
            ];

            $column_names = [
                [
                    'text'  => 'm',
                    'extra' => $checkbox_all,
                    'class' => 'w20px no-text-imp',
                ],
                __('Agent Name'),
                __('IP Adrress'),
                __('Actions'),
            ];

            $show_agents = [
                0 => __('Everyone'),
                1 => __('Only disabled'),
                2 => __('Only deleted'),
                3 => __('Only added'),
            ];

            $this->tableId = 'satellite_agents';

            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => $this->tableId,
                    'class'               => 'info_table',
                    'style'               => 'width: 99%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => [
                        'method'        => 'draw',
                        'server_remote' => $this->satellite_server,
                    ],
                    'ajax_postprocces'    => 'process_datatables_item(item)',
                    'no_sortable_columns' => [
                        0,
                        1,
                        2,
                        3,
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'form'                => [
                        'inputs' => [
                            [
                                'label' => __('Search').ui_print_help_tip(
                                    __('Search filter by alias, name, description, IP address or custom fields content'),
                                    true
                                ),
                                'type'  => 'text',
                                'name'  => 'filter_search',
                                'size'  => 12,
                            ],
                            [
                                'label'    => __('Show agents'),
                                'type'     => 'select',
                                'id'       => 'filter_agents',
                                'name'     => 'filter_agents',
                                'fields'   => $show_agents,
                                'return'   => true,
                                'selected' => 0,
                            ],
                        ],
                    ],
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        // Auxiliar div.
        $modal = '<div id="modal" class="invisible"></div>';
        $msg = '<div id="msg"     class="invisible"></div>';
        $aux = '<div id="aux"     class="invisible"></div>';

        echo $modal.$msg.$aux;

        $select = html_print_select(
            [
                '0' => 'Disable / Enable selected agents',
                '1' => 'Delete / Create selected agents',
            ],
            'satellite_action',
            '',
            '',
            '',
            0,
            true,
            false,
            false
        );

        $execute = html_print_submit_button(
            __('Execute action'),
            'submit_satellite_action',
            false,
            [
                'icon'  => 'cog',
                'class' => 'secondary',
            ],
            true
        );

        // Create button add host.
        $add = html_print_submit_button(
            __('Add host'),
            'create',
            false,
            ['icon' => 'next'],
            true
        );

        html_print_action_buttons($add.$execute.$select);

        // Load own javascript file.
        echo $this->loadJS();
    }


    /**
     * Get the data for draw the table.
     *
     * @return void.
     */
    public function draw()
    {
        global $config;

        // Init data.
        $data = [];
        // Count of total records.
        $count = 0;
        // Catch post parameters.
        $start              = get_parameter('start', 0);
        $length             = get_parameter('length', $config['block_size']);
        $order              = get_datatable_order(true);
        $filters            = get_parameter('filter', []);

        try {
            ob_start();
            $data = [];

            $agents_db = db_get_all_rows_sql(
                sprintf(
                    'SELECT id_agente, alias AS name, direccion AS address,
                    IF(disabled = 0, INSERT("add_host", 0 , 0, ""),
                    IF(modo = 1, INSERT("ignore_host", 0 , 0, ""), INSERT("delete_host", 0, 0, "")))  AS type
                    FROM tagente WHERE `satellite_server` = %d',
                    $this->satellite_server
                )
            );

            if (empty($agents_db) === false) {
                $data = $agents_db;
            }

            foreach ($this->satellite_config as $line) {
                $re = '/^#*add_host \b(\S+) (\S*)/m';
                $re_disable = '/^ignore_host \b(\S+)/m';
                $re_delete = '/^delete_host \b(\S+)/m';

                if (preg_match($re, $line, $matches, PREG_OFFSET_CAPTURE, 0) > 0) {
                    $agent['address'] = $matches[1][0];
                    if (isset($matches[2][0]) === false || empty($matches[2][0]) === true) {
                        $agent['name'] = '';
                    } else {
                        $agent['name'] = $matches[2][0];
                    }

                    $agent['type'] = 'add_host';

                    array_push($data, $agent);
                }

                if (preg_match($re_disable, $line, $matches, PREG_OFFSET_CAPTURE, 0) > 0) {
                    $agent['name'] = $matches[1][0];

                    $agent['type'] = 'ignore_host';

                    array_push($data, $agent);
                }

                if (preg_match($re_delete, $line, $matches, PREG_OFFSET_CAPTURE, 0) > 0) {
                    $agent['name'] = $matches[1][0];

                    $agent['type'] = 'delete_host';

                    array_push($data, $agent);
                }
            }

            if (empty($data) === false) {
                $data = $this->uniqueMultidimArray($data, ['name', 'address']);
                if (empty($filters['filter_agents']) === false || empty($filters['filter_search']) === false) {
                    foreach ($data as $key => $value) {
                        switch ($filters['filter_agents']) {
                            case 1:
                                if ($value['type'] !== 'ignore_host') {
                                    unset($data[$key]);
                                }
                            break;

                            case 2:
                                if ($value['type'] !== 'delete_host') {
                                    unset($data[$key]);
                                }
                            break;

                            case 3:
                                if ($value['type'] !== 'add_host') {
                                    unset($data[$key]);
                                }
                            break;

                            default:
                                // Everyone.
                            break;
                        }

                        if (empty($filters['filter_search']) === false) {
                            if (empty(preg_grep('/'.$filters['filter_search'].'?/mi', array_values($value))) === true) {
                                unset($data[$key]);
                            }
                        }
                    }
                }

                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        global $config;
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $disable = ($tmp->type === 'ignore_host');
                        $delete = ($tmp->type === 'delete_host');

                        if ($disable === true) {
                            $tmp->name = '<i class="italic_a">'.$tmp->name.'</i>';
                        }

                        if ($delete === true) {
                            $tmp->name = '<del>'.$tmp->name.'</del>';
                        }

                        $id_agente = (isset($tmp->id_agente) === true) ? $tmp->id_agente : 0;

                        $tmp->actions = '';

                        if ($delete === false) {
                            $tmp->actions .= html_print_image(
                                ($disable === true) ? 'images/lightbulb_off.png' : 'images/lightbulb.png',
                                true,
                                [
                                    'border'  => '0',
                                    'class'   => 'main_menu_icon mrgn_lft_05em invert_filter',
                                    'onclick' => 'disable_agent(\''.$tmp->address.'\',\''.strip_tags($tmp->name).'\',\''.(int) $disable.'\',\''.$id_agente.'\')',
                                ]
                            );
                        }

                        if ($disable === false) {
                            $tmp->actions .= html_print_image(
                                ($delete === true) ? 'images/add.png' : 'images/delete.svg',
                                true,
                                [
                                    'border'  => '0',
                                    'class'   => 'main_menu_icon mrgn_lft_05em invert_filter',
                                    'onclick' => 'delete_agent(\''.$tmp->address.'\',\''.strip_tags($tmp->name).'\',\''.(int) $delete.'\',\''.$id_agente.'\')',
                                ]
                            );
                        }

                        $tmp->m = html_print_checkbox(
                            'check_'.strip_tags($tmp->name),
                            $tmp->address.','.strip_tags($tmp->name).','.(int) $delete.','.(int) $disable.','.$id_agente,
                            false,
                            true
                        );

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            if (empty($data) === true) {
                $total = 0;
                $data = [];
            } else {
                $total = count($data);
                $data = array_slice($data, $start, $length, false);
            }

            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        // If not valid, show error with issue.
        json_decode($response);
        if (json_last_error() == JSON_ERROR_NONE) {
            // If valid dump.
            echo $response;
        } else {
            echo json_encode(
                ['error' => $response]
            );
        }

        exit;
    }


    /**
     * Prints inputs for modal "Add agent".
     *
     * @return void
     */
    public function loadModal()
    {
        $values['address'] = get_parameter('address', null);
        $values['name'] = get_parameter('name', null);

        echo $this->printInputs($values);
    }


     /**
      * Generates inputs for new/update agents.
      *
      * @param array $values Values or null.
      *
      * @return string Inputs.
      */
    public function printInputs($values=null)
    {
        if (!is_array($values)) {
            $values = [];
        }

        $form = [
            'action'   => '#',
            'id'       => 'modal_form',
            'onsubmit' => 'return false;',
            'class'    => 'modal',
        ];

        $inputs = [];

        $inputs[] = [
            'label'     => __('Agent address'),
            'id'        => 'div-identifier',
            'arguments' => [
                'name'   => 'address',
                'type'   => 'text',
                'class'  => 'w100p',
                'value'  => $values['address'],
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Agent name'),
            'id'        => 'div-identifier',
            'arguments' => [
                'name'   => 'name',
                'type'   => 'text',
                'class'  => 'w100p',
                'value'  => $values['name'],
                'return' => true,
            ],
        ];

        return $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );
    }


    /**
     * Add agent to satellite conf.
     *
     * @return void
     */
    public function addAgent()
    {
        global $config;

        $values['address'] = get_parameter('address');
        $values['name'] = get_parameter('name');

        if ($this->checkAddressExists($values['address']) === true) {
            $this->ajaxMsg('error', __('Error saving agent. The address already exists'));
            exit;
        }

        if ($this->checkNameExists($values['name']) === true) {
            $this->ajaxMsg('error', __('Error saving agent. The Name already exists'));
            exit;
        }

        if ($this->parseSatelliteConf('save', $values) === false) {
            $this->ajaxMsg('error', __('Error saving agent'));
        } else {
            $this->ajaxMsg('result', _('Host '.$values['addres'].' added.'));
        }

        exit;

    }


     /**
      * Delete agent from satellite conf.
      *
      * @return void
      */
    public function deleteAgent()
    {
        $values['address'] = get_parameter('address', '');
        $values['name'] = get_parameter('name', '');
        $values['delete'] = get_parameter('delete', '');
        $values['id'] = get_parameter('id', 0);

        $no_msg = (bool) get_parameter('no_msg', 0);

        if ((bool) $values['id'] === true) {
            db_process_sql_update(
                'tagente',
                [
                    'disabled' => ($values['delete'] === '0') ? 1 : 0,
                    'modo'     => ($values['delete'] === '0') ? 2 : 1,
                ],
                ['id_agente' => (int) $values['id']]
            );
        }

        if ($this->parseSatelliteConf('delete', $values) === false) {
            if ($no_msg === false) {
                $this->ajaxMsg('error', ($values['delete'] === '0') ? __('Error delete agent') : __('Error add agent'));
            }
        } else {
            if ($no_msg === false) {
                $this->ajaxMsg(
                    'result',
                    ($values['delete'] === '0')
                        ? _('Host '.$values['address'].' deleted.')
                        : _('Host '.$values['address'].' added.'),
                    true
                );
            }
        }

        exit;
    }


    /**
     * Disable agent from satellite conf.
     *
     * @return void
     */
    public function disableAgent()
    {
        $values['address'] = get_parameter('address', '');
        $values['name'] = get_parameter('name', '');
        $values['disable'] = get_parameter('disable', '');
        $values['id'] = get_parameter('id', 0);

        $no_msg = (bool) get_parameter('no_msg', 0);

        if ((bool) $values['id'] === true) {
            db_process_sql_update(
                'tagente',
                ['disabled' => ($values['disable'] === '0') ? 1 : 0],
                ['id_agente' => (int) $values['id']]
            );
        }

        if ($this->parseSatelliteConf('disable', $values) === false) {
            if ($no_msg === false) {
                $this->ajaxMsg(
                    'error',
                    ($values['disable'] === '0') ? __('Error disable agent') : __('Error enable agent')
                );
            }
        } else {
            if ($no_msg === false) {
                $this->ajaxMsg(
                    'result',
                    ($values['disable'] === '0')
                        ? _('Host '.$values['address'].' disabled.')
                        : _('Host '.$values['address'].' enabled.'),
                    false,
                    true
                );
            }
        }

        exit;
    }


    /**
     * Parse satellite configuration .
     *
     * @param string $action Action to perform (save, delete).
     * @param array  $values Values.
     *
     * @return boolean
     */
    private function parseSatelliteConf(string $action, array $values)
    {
        switch ($action) {
            case 'save':
                if (isset($values['address']) === true && empty($values['address']) === false) {
                    $pos = preg_grep('/^\#INIT ignore_host/', $this->satellite_config);
                    if (empty($pos) === false) {
                        $string_hosts = 'add_host '.$values['address'].' '.$values['name']."\n";

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        $array1 = array_slice($this->satellite_config, 0, $key_pos);
                        $array2 = array_slice($this->satellite_config, $key_pos);
                        // Add host to conf.
                        $array_merge = array_merge($array1, [$string_hosts], $array2);
                        $this->satellite_config = $array_merge;

                        // Check config.
                        if (empty($this->satellite_config)) {
                            return false;
                        }

                        $conf = implode('', $this->satellite_config);
                    }
                } else {
                    return false;
                }
            break;

            case 'disable':
                if ((bool) $values['disable'] === true) {
                    $pos = preg_grep('/^\#INIT ignore_host/', $this->satellite_config);
                    if (empty($pos) === false) {
                        $string_hosts = 'add_host '.$values['address'].' '.$values['name']."\n";

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        $array1 = array_slice($this->satellite_config, 0, $key_pos);
                        $array2 = array_slice($this->satellite_config, $key_pos);
                        // Add host to conf.
                        $array_merge = array_merge($array1, [$string_hosts], $array2);
                        $this->satellite_config = $array_merge;

                        // Remove ignore_host.
                        $pattern = io_safe_expreg('ignore_host '.$values['name']);
                        $pos = preg_grep('/'.$pattern.'/', $this->satellite_config);

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        if (empty($pos) === false) {
                            unset($this->satellite_config[$key_pos]);
                        }

                        $conf = implode('', $this->satellite_config);
                    }
                } else {
                    $pos = preg_grep('/^\#INIT delete_host/', $this->satellite_config);
                    if (empty($pos) === false) {
                        $string_hosts = 'ignore_host '.$values['name']."\n";

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        $array1 = array_slice($this->satellite_config, 0, $key_pos);
                        $array2 = array_slice($this->satellite_config, $key_pos);
                        // Add host to conf.
                        $array_merge = array_merge($array1, [$string_hosts], $array2);
                        $this->satellite_config = $array_merge;

                        // Remove add_host.
                        $pattern = io_safe_expreg('add_host '.$values['address'].' '.$values['name']);
                        $pos = preg_grep('/'.$pattern.'/', $this->satellite_config);

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        if (empty($pos) === false) {
                            unset($this->satellite_config[$key_pos]);
                        }

                        $conf = implode('', $this->satellite_config);
                    }
                }
            break;

            case 'delete':
                if ((bool) $values['delete'] === true) {
                    $pos = preg_grep('/^\#INIT ignore_host/', $this->satellite_config);
                    if (empty($pos) === false) {
                        $string_hosts = 'add_host '.$values['address'].' '.$values['name']."\n";

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        $array1 = array_slice($this->satellite_config, 0, $key_pos);
                        $array2 = array_slice($this->satellite_config, $key_pos);
                        // Add host to conf.
                        $array_merge = array_merge($array1, [$string_hosts], $array2);
                        $this->satellite_config = $array_merge;

                        // Remove delete_host.
                        $pattern = io_safe_expreg('delete_host '.$values['name']);
                        $pos = preg_grep('/'.$pattern.'/', $this->satellite_config);

                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        unset($this->satellite_config[$key_pos]);

                        $conf = implode('', $this->satellite_config);
                    }
                } else {
                    // Find agent to mark for deletion.
                    $pattern = io_safe_expreg('add_host '.$values['address'].' '.$values['name']);
                    $pos = preg_grep('/'.$pattern.'/', $this->satellite_config);

                    if (empty($pos) === false) {
                        $key_pos = 0;
                        foreach ($pos as $key => $value) {
                            $key_pos = $key;
                            break;
                        }

                        unset($this->satellite_config[$key_pos]);
                    }

                    $string_hosts = 'delete_host '.$values['name']."\n";
                    $pos = preg_grep('/delete_host/', $this->satellite_config);
                    if (empty($pos) === false) {
                        $key_pos = array_keys($pos)[(count($pos) - 1)];
                        $array1 = array_slice($this->satellite_config, 0, ($key_pos + 1));
                        $array2 = array_slice($this->satellite_config, ($key_pos + 1));
                        $array_merge = array_merge($array1, [$string_hosts], $array2);
                        $this->satellite_config = $array_merge;
                    }

                    $conf = implode('', $this->satellite_config);
                }
            break;

            default:
                $this->ajaxMsg('error', __('Error'));
            exit;
        }

        return $this->saveAgent($conf);
    }


    public function checkAddressExists($address)
    {
        $pos_address = preg_grep('/.*_host\s('.$address.')\s.*/', $this->satellite_config);

        if (empty($pos_address) === false) {
            return true;
        }

        return false;

    }


    public function checkNameExists($name)
    {
        $pos_name = preg_grep('/.*_host.*('.$name.')$/', $this->satellite_config);

        if (empty($pos_name) === false) {
            return true;
        }

        return false;
    }


    /**
     * Saves agent to satellite cofiguration file.
     *
     * @param string $new_conf Config file.
     *
     * @return boolean|void
     */
    private function saveAgent(string $new_conf)
    {
        global $config;

        if (empty($new_conf)) {
            return false;
        }

        db_pandora_audit(
            AUDIT_LOG_SYSTEM,
            'Update remote config for server '.$this->satellite_name
        );

        // Convert to config file encoding.
        $encoding = config_satellite_get_encoding($new_conf);
        if ($encoding !== false) {
            $converted_server_config = mb_convert_encoding($new_conf, $encoding, 'UTF-8');
            if ($converted_server_config !== false) {
                $new_conf = $converted_server_config;
            }
        }

        // Get filenames.
        if ($this->satellite_server !== false) {
            $files = config_satellite_get_satellite_config_filenames($this->satellite_name);
        } else {
            $files = [];
            $files['conf'] = $config['remote_config'].'/conf/'.md5($this->satellite_name).'.srv.conf';
            $files['md5'] = $config['remote_config'].'/md5/'.md5($this->satellite_name).'.srv.md5';
        }

        // Save configuration.
        $result = file_put_contents($files['conf'], $new_conf);

        if ($result === false) {
            return false;
        }

        // Save configuration md5.
        $result = file_put_contents($files['md5'], md5($new_conf));
    }


    /**
     * Creates add_host, ignore_host and delete_host blocks
     *
     * @return void
     */
    public function createBlock()
    {
        $init = preg_grep('/^\#INIT/', $this->satellite_config);

        if (empty($init) === true) {
            $add_host = "#INIT add_host\n";
            $ignore_host = "#INIT ignore_host\n";
            $delete_host = "#INIT delete_host\n";

            array_push($this->satellite_config, "\n");
            array_push($this->satellite_config, $add_host);
            array_push($this->satellite_config, $ignore_host);
            array_push($this->satellite_config, $delete_host);

            $conf = implode('', $this->satellite_config);
            $this->saveAgent($conf);
        }
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod(string $method)
    {
        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Minor function to dump json message as ajax response.
     *
     * @param string  $type    Type: result || error.
     * @param string  $msg     Message.
     * @param boolean $delete  Deletion messages.
     * @param boolean $disable Disable messages.
     *
     * @return void
     */
    private function ajaxMsg($type, $msg, $delete=false, $disable=false)
    {
        if ($type === 'error') {
            if ($delete === true) {
                $msg_title = 'Failed while removing';
            } else if ($disable === true) {
                $msg_title = 'Failed while disabling';
            } else {
                $msg_title = 'Failed while saving';
            }
        } else {
            if ($delete === true) {
                $msg_title = 'Successfully deleted';
            } else if ($disable === true) {
                $msg_title = 'Successfully disabled';
            } else {
                $msg_title = 'Successfully saved agent';
            }
        }

        echo json_encode(
            [ $type => __($msg_title).':<br>'.$msg ]
        );

        exit;
    }


    /**
     * Removes duplicate values from a multidimensional array
     *
     * @param array $array Input array.
     * @param array $key   Keys.
     *
     * @return array
     */
    public function uniqueMultidimArray($array, $key)
    {
        $temp_array = [];
        $i = 0;
        $key_array_name = [];
        $key_array_address = [];

        foreach ($array as $val) {
            if (!in_array($val[$key[0]], $key_array_name) && !in_array($val[$key[1]], $key_array_address)) {
                $key_array_name[$i] = $val[$key[0]];
                $key_array_address[$i] = $val[$key[1]];
                $temp_array[$i] = $val;
            }

            $i++;
        }

        return $temp_array;
    }


    /**
     * Load Javascript code.
     *
     * @return string.
     */
    public function loadJS()
    {
        // Nothing for this moment.
        ob_start();

        // Javascript content.
        ?>
        <script type="text/javascript">

             /**
         * Cleanup current dom entries.
         */
        function cleanupDOM() {
            $('#div-address').empty();
            $('#div-name').empty();
        }


        /**
        * Process ajax responses and shows a dialog with results.
        */
        function showMsg(data) {
            var title = "<?php echo __('Success'); ?>";
            var dt_satellite_agents = $("#satellite_agents").DataTable();

            var text = '';
            var failed = 0;
            try {
                data = JSON.parse(data);
                text = data['result'];
            } catch (err) {
                title =  "<?php echo __('Failed'); ?>";
                text = err.message;
                failed = 1;
            }
            if (!failed && data['error'] != undefined) {
                title =  "<?php echo __('Failed'); ?>";
                text = data['error'];
                failed = 1;
            }
            if (data['report'] != undefined) {
                data['report'].forEach(function (item){
                    text += '<br>'+item;
                });
            }

            $('#msg').empty();
            $('#msg').html(text);
            $('#msg').dialog({
                width: 450,
                position: {
                    my: 'center',
                    at: 'center',
                    of: window,
                    collision: 'fit'
                },
                title: title,
                buttons: [
                    {
                        class: "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
                        text: 'OK',
                        click: function(e) {
                            if (!failed) {
                                $(".ui-dialog-content").dialog("close");
                                $('.info').hide();
                                cleanupDOM();
                                dt_<?php echo $this->tableId; ?>.draw(false);
                            } else {
                                $(this).dialog('close');
                            }
                        }
                    }
                ]
            });
        }
            
        /**
         * Loads modal from AJAX to add a new agent.
         */

        function show_form(address) {
            var btn_ok_text = '<?php echo __('OK'); ?>';
            var btn_cancel_text = '<?php echo __('Cancel'); ?>';
            var title = '<?php echo __('Add agent to satellite'); ?>';
            var method = 'addAgent';
           

            load_modal({
                target: $('#modal'),
                form: 'modal_form',
                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                ajax_callback: showMsg,
                cleanup: cleanupDOM,
                modal: {
                    title: title,
                    ok: btn_ok_text,
                    cancel: btn_cancel_text,
                },
                extradata: [
                    {   
                        name: 'server_remote',
                        value:  <?php echo $this->satellite_server; ?>,
                    }
                ],
                onshow: {
                    page: '<?php echo $this->ajaxController; ?>',
                    method: 'loadModal'
                },
                onsubmit: {
                    page: '<?php echo $this->ajaxController; ?>',
                    method: method
                }
            });

        }

        /**
         * Delete selected agent
         */
        function delete_agent(address, name, deleted, id_agente) {
            $('#aux').empty();
            $('#aux').text('<?php echo __('Are you sure?'); ?>');
            $('#aux').dialog({
                title: (deleted == 0) ? '<?php echo __('Delete'); ?> '+address : '<?php echo __('Add'); ?>'+address,
                buttons: [
                    {
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                        text: '<?php echo __('Cancel'); ?>',
                        click: function(e) {
                            $(this).dialog('close');
                            cleanupDOM();

                        }
                    },
                    {
                        text: (deleted == 0) ? 'Delete' : 'Add',
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                        click: function(e) {
                            $.ajax({
                                method: 'post',
                                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                data: {
                                    page: 'enterprise/godmode/servers/agents_satellite',
                                    method: 'deleteAgent',
                                    address: address,
                                    name: name,
                                    id: id_agente,
                                    delete: deleted,
                                    server_remote:  <?php echo $this->satellite_server; ?>,
                                },
                                datatype: "json",
                                success: function (data) {
                                    showMsg(data);
                                },
                                error: function(e) {
                                    showMsg(e);
                                }
                            });
                        }
                    }
                ]
            });
        }

        /**
         * Disable selected agent
         */
        function disable_agent(address, name, disabled, id_agente) {
            $('#aux').empty();
            $('#aux').text('<?php echo __('Are you sure?'); ?>');
            $('#aux').dialog({
                title: (disabled == 0) ? '<?php echo __('Disable'); ?>'+address : '<?php echo __('Enable'); ?>'+address,
                buttons: [
                    {
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                        text: '<?php echo __('Cancel'); ?>',
                        click: function(e) {
                            $(this).dialog('close');
                            cleanupDOM();

                        }
                    },
                    {
                        text: (disabled == 0) ? 'Disable' : 'Enable',
                        class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                        click: function(e) {
                            $.ajax({
                                method: 'post',
                                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                data: {
                                    page: 'enterprise/godmode/servers/agents_satellite',
                                    method: 'disableAgent',
                                    address: address,
                                    disable: disabled,
                                    id: id_agente,
                                    name: name,
                                    server_remote:  <?php echo $this->satellite_server; ?>,
                                },
                                datatype: "json",
                                success: function (data) {
                                    showMsg(data);
                                },
                                error: function(e) {
                                    showMsg(e);
                                }
                            });
                        }
                    }
                ]
            });
        }

        $(document).ready(function() {

            $('body').append('<div id="dialog"></div>');

            $("#button-create").on('click', function() {
                show_form();
            });

            $("#checkbox-all_validate_box").click(function() {
                const check = $("#checkbox-all_validate_box").is(":checked");
                $('input[name*=check_]').prop('checked', check);
            });

            $('#button-submit_satellite_action').click(function() {
                const checks = $('input[name*=check_]:checked');
                const action = $('#satellite_action').val();
                let agent_delete_error = [];
                let agent_disable_error = [];
                $('#aux').empty();
                $('#aux').text('<?php echo __('Are you sure?'); ?>');
                $('#aux').dialog({
                    title: (action === '0') ? '<?php echo __('Disable / Enable Agents'); ?>' : '<?php echo __('Delete / create Agents'); ?>',
                    buttons: [
                        {
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                            text: '<?php echo __('Cancel'); ?>',
                            click: function(e) {
                                $(this).dialog('close');
                                cleanupDOM();

                            }
                        },
                        {
                            text: '<?php echo __('Ok'); ?>',
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                            click: function(e) {
                                $(this).dialog('close');
                                $.each(checks, function(i, val) {
                                    const params = val.value.split(",");
                                    if (action === '0') {
                                        if (params[2] === '0') {
                                            $.ajax({
                                                method: 'post',
                                                async: false,
                                                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                                data: {
                                                    page: 'enterprise/godmode/servers/agents_satellite',
                                                    method: 'disableAgent',
                                                    address: params[0],
                                                    disable: params[3],
                                                    id: params[4],
                                                    name: params[1],
                                                    no_msg: 1,
                                                    server_remote:  <?php echo $this->satellite_server; ?>,
                                                },
                                                datatype: "json",
                                                success: function (data) {
                                                },
                                                error: function(e) {
                                                    console.error(e);
                                                }
                                            });
                                        } else {
                                            agent_disable_error.push(params[0]);
                                        }
                                    } else {
                                        if (params[3] === '0') {
                                            $.ajax({
                                                method: 'post',
                                                async: false,
                                                url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                                data: {
                                                    page: 'enterprise/godmode/servers/agents_satellite',
                                                    method: 'deleteAgent',
                                                    address: params[0],
                                                    name: params[1],
                                                    id: params[4],
                                                    delete: params[2],
                                                    no_msg: 1,
                                                    server_remote:  <?php echo $this->satellite_server; ?>,
                                                },
                                                datatype: "json",
                                                success: function (data) {
                                                },
                                                error: function(e) {
                                                    console.error(e);
                                                }
                                            });
                                        } else {
                                            agent_delete_error.push(params[0]);
                                        }
                                    }
                                });

                                if (agent_delete_error.length > 0) {
                                     $("#dialog").dialog({
                                        resizable: true,
                                        draggable: true,
                                        modal: true,
                                        height: 240,
                                        width: 600,
                                        title: '<?php echo __('Warning'); ?>',
                                        open: function(){
                                            let text = '<?php echo __('These agents could not be deleted. They must first be enabled'); ?>';
                                            text += ` (${agent_delete_error.join()})`;
                                            $('#dialog').html(`<br><table><tr><td><img src="images/icono-warning-triangulo.png" class="float-left mrgn_lft_25px"></td><td><p id="p_configurar_agente" >${text}</p></td></tr></table>`);
                                        },
                                        buttons: [
                                            {
                                                text: "Ok",
                                                click: function() {
                                                    $( this ).dialog( "close" );
                                                    return false;
                                                }
                                            }
                                        ]
                                    });
                                }

                                if (agent_disable_error.length > 0) {
                                     $("#dialog").dialog({
                                        resizable: true,
                                        draggable: true,
                                        modal: true,
                                        height: 240,
                                        width: 600,
                                        title: '<?php echo __('Warning'); ?>',
                                        open: function(){
                                            let text = '<?php echo __('These agents could not be disabled. They must first be created'); ?>';
                                            text += ` (${agent_disable_error.join()})`;
                                            $('#dialog').html(`<br><table><tr><td><img src="images/icono-warning-triangulo.png" class="float-left mrgn_lft_25px"></td><td><p id="p_configurar_agente" >${text}</p></td></tr></table>`);
                                        },
                                        buttons: [
                                            {
                                                text: "Ok",
                                                click: function() {
                                                    $( this ).dialog( "close" );
                                                    return false;
                                                }
                                            }
                                        ]
                                    });
                                }

                                var dt_satellite_agents = $("#satellite_agents").DataTable();
                                dt_satellite_agents.draw();
                            }
                        }
                    ]
                });
            });
        });


        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
