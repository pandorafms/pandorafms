<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Controller for collections
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

// Begin.
global $config;

// Necessary classes for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
require_once $config['homedir'].'/include/functions_servers.php';
enterprise_include_once('include/functions_satellite.php');
enterprise_include_once('include/functions_collection.php');

/**
 * Class SatelliteCollection
 */
class SatelliteCollection extends HTML
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'draw',
        'addCollection',
        'deleteCollection',
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

        if ((int) $config['license_nms'] === 1) {
            db_pandora_audit(
                AUDIT_LOG_NMS_VIOLATION,
                'Trying to access satellite collections'
            );
            include $config['homedir'].'/general/noaccess.php';
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
        // Javascript.
        ui_require_jquery_file('pandora');
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        $this->createBlock();

        // Datatables list.
        try {
            $columns = [
                'name',
                'dir',
                'description',
                'actions',
            ];

            $column_names = [
                __('Name'),
                __('Dir'),
                __('Description'),
                __('Actions'),
            ];

            $this->tableId = 'satellite_collections';

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
                                'label' => __('Search'),
                                'type'  => 'text',
                                'name'  => 'filter_search',
                                'class' => 'w400px',
                                'size'  => 12,
                            ],
                        ],
                    ],
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        echo '<div id="aux" class="invisible"></div>';
        echo '<div id="msg" class="invisible"></div>';
        html_print_action_buttons('');

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
        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order = get_datatable_order(true);
        $filters = get_parameter('filter', []);

        try {
            ob_start();

            // Gets all collections (database).
            $collections = collection_get_collections(null, $filters['filter_search']);
            if (empty($collections) === false) {
                $data = $collections;
            }

            // All satellite conf collections.
            foreach ($this->satellite_config as $line) {
                $regex = '/^file_collection\s(\S+)/m';

                if (preg_match($regex, $line, $matches, PREG_OFFSET_CAPTURE, 0) > 0) {
                    $key = array_search($matches[1][0], array_column($data, 'short_name'));
                    if ($key !== false) {
                        $data[$key]['delete'] = true;
                    }
                }
            }

            if (empty($data) === false) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $delete = (int) isset($tmp->delete);

                        $tmp->dir = $tmp->short_name;

                        $tmp->actions = '';
                        $tmp->actions .= html_print_image(
                            ($delete === 0) ? 'images/add.png' : 'images/delete.svg',
                            true,
                            [
                                'border'  => '0',
                                'class'   => 'action_button_img mrgn_lft_05em invert_filter',
                                'onclick' => ($delete === 0)
                                    ? 'add_collection(\''.$tmp->short_name.'\')'
                                    : 'delete_collection(\''.$tmp->short_name.'\')',
                            ]
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
        if (json_last_error() === JSON_ERROR_NONE) {
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
     * Add collection to satellite conf.
     *
     * @return void
     */
    public function addCollection()
    {
        $short_name = get_parameter('short_name');

        if ($this->parseSatelliteConf('add', $short_name) === false) {
            $this->ajaxMsg('error', __('Error adding collection'));
        } else {
            $this->ajaxMsg('result', _('Collection '.$short_name.' added.'));
        }

        exit;
    }


    /**
     * Delete collection to satellite conf.
     *
     * @return void
     */
    public function deleteCollection()
    {
        $short_name = get_parameter('short_name');

        if ($this->parseSatelliteConf('delete', $short_name) === false) {
            $this->ajaxMsg('error', __('Error deleting collection'));
        } else {
            $this->ajaxMsg('result', _('Collection '.$short_name.' deleted.'));
        }

        exit;
    }


    /**
     * Parse satellite configuration .
     *
     * @param string $action     Action to perform (add, delete).
     * @param string $short_name Short name.
     *
     * @return boolean
     */
    private function parseSatelliteConf(string $action, string $short_name)
    {
        switch ($action) {
            case 'delete':
                $pos = preg_grep('/^file_collection '.$short_name.'/', $this->satellite_config);
                if (empty($pos) === false) {
                    $key_pos = 0;
                    foreach ($pos as $key => $value) {
                        $key_pos = $key;
                        break;
                    }

                    unset($this->satellite_config[$key_pos]);
                }

                $conf = implode('', $this->satellite_config);
            break;

            default:
            case 'add':
                $pos = preg_grep('/^file_collection/', $this->satellite_config);
                if (empty($pos) === false) {
                    $string_collection = 'file_collection '.$short_name."\n";

                    $key_pos = array_keys($pos)[(count($pos) - 1)];
                    $array1 = array_slice($this->satellite_config, 0, ($key_pos + 1));
                    $array2 = array_slice($this->satellite_config, ($key_pos + 1));
                    $array_merge = array_merge($array1, [$string_collection], $array2);
                    $this->satellite_config = $array_merge;

                    // Check config.
                    if (empty($this->satellite_config) === true) {
                        return false;
                    }
                } else {
                    $pos = preg_grep('/^\#\sFile\scollections/', $this->satellite_config);
                    $string_collection = 'file_collection '.$short_name."\n";

                    $key_pos = 0;
                    foreach ($pos as $key => $value) {
                        $key_pos = $key;
                        break;
                    }

                    $key_pos++;

                    $array1 = array_slice($this->satellite_config, 0, $key_pos);
                    $array2 = array_slice($this->satellite_config, $key_pos);
                    // Add collection to conf.
                    $array_merge = array_merge($array1, [$string_collection], $array2);
                    $this->satellite_config = $array_merge;

                    // Check config.
                    if (empty($this->satellite_config) === true) {
                        return false;
                    }
                }

                $conf = implode('', $this->satellite_config);
            break;
        }

        return $this->saveAgent($conf);
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

        if (empty($new_conf) === true) {
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
     * @param string $type Type: result || error.
     * @param string $msg  Message.
     *
     * @return void
     */
    private function ajaxMsg(string $type, string $msg)
    {
        echo json_encode(
            [
                $type => __($msg),
            ]
        );

        exit;
    }


    /**
     * Create file_collections blocks
     *
     * @return void
     */
    public function createBlock()
    {
        $init = preg_grep('/^\#\sFile\scollections/', $this->satellite_config);

        if (empty($init) === true) {
            $collection = "# File collections\n";

            array_push($this->satellite_config, "\n");
            array_push($this->satellite_config, $collection);
            array_push($this->satellite_config, "\n");

            $conf = implode('', $this->satellite_config);
            $this->saveAgent($conf);
        }
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
             * Add collection.
             */
            function add_collection(short_name) {
                $('#aux').empty();
                $('#aux').text('<?php echo __('Are you sure?'); ?>');
                $('#aux').dialog({
                    title: '<?php echo __('Add collection'); ?>',
                    buttons: [{
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                            text: '<?php echo __('Cancel'); ?>',
                            click: function(e) {
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: 'Add',
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                            click: function(e) {
                                $.ajax({
                                    method: 'post',
                                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                    data: {
                                        page: 'enterprise/godmode/servers/collections_satellite',
                                        method: 'addCollection',
                                        short_name: short_name,
                                        server_remote: <?php echo $this->satellite_server; ?>,
                                    },
                                    datatype: "json",
                                    success: function(data) {
                                        console.log(data);
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
             * Delete collection.
             */
            function delete_collection(short_name) {
                $('#aux').empty();
                $('#aux').text('<?php echo __('Are you sure?'); ?>');
                $('#aux').dialog({
                    title: '<?php echo __('Delete collection'); ?>',
                    buttons: [{
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel',
                            text: '<?php echo __('Cancel'); ?>',
                            click: function(e) {
                                $(this).dialog('close');
                            }
                        },
                        {
                            text: 'Delete',
                            class: 'ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next',
                            click: function(e) {
                                $.ajax({
                                    method: 'post',
                                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                                    data: {
                                        page: 'enterprise/godmode/servers/collections_satellite',
                                        method: 'deleteCollection',
                                        short_name: short_name,
                                        server_remote: <?php echo $this->satellite_server; ?>,
                                    },
                                    datatype: "json",
                                    success: function(data) {
                                        console.log(data);
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
            * Process ajax responses and shows a dialog with results.
            */
            function showMsg(data) {
                var title = "<?php echo __('Success'); ?>";
                var dt_satellite_agents = $("#satellite_collections").DataTable();
                dt_<?php echo $this->tableId; ?>.draw(false);

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
                                } else {
                                    $(this).dialog('close');
                                }
                            }
                        }
                    ]
                });
            }
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
