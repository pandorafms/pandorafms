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
        // Javascript.
        ui_require_jquery_file('pandora');
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        global $config;
        // Datatables list.
        try {
            $columns = [
                'name',
                'address',
                'actions',
            ];

            $column_names = [
                __('Agent Name'),
                __('IP Adrress'),
                __('Actions'),
            ];

            $this->tableId = 'satellite_agents';

            if (is_metaconsole() === true) {
                // Only in case of Metaconsole, format the frame.
                open_meta_frame();
            }

            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => $this->tableId,
                    'class'               => 'info_table',
                    'style'               => 'width: 100%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => [
                        'method'        => 'draw',
                        'server_remote' => $this->satellite_server,
                    ],
                    'ajax_postprocces'    => 'process_datatables_item(item)',
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'date',
                        'direction' => 'asc',
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

                        ],
                    ],
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if (is_metaconsole() === true) {
            // Close the frame.
            close_meta_frame();
        }

        // Auxiliar div.
        $modal = '<div id="modal" class="invisible"></div>';
        $msg = '<div id="msg"     class="invisible"></div>';
        $aux = '<div id="aux"     class="invisible"></div>';

          echo $modal.$msg.$aux;

        // Create button.
        echo '<div class="w100p flex-content-right">';
        html_print_submit_button(
            __('Add host'),
            'create',
            false,
            'class="sub next"'
        );

        echo '</div>';
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
        // Initialice filter.
        $filter = '1=1';
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

            foreach ($this->satellite_config as $line) {
                $re = '/^#*add_host \b(\S+) (\S*)$/m';

                if (preg_match($re, $line, $matches, PREG_OFFSET_CAPTURE, 0) > 0) {
                    $agent['address'] = $matches[1][0];
                    if (isset($matches[2][0]) === false || empty($matches[2][0]) === true) {
                        $agent['name'] = '';
                    } else {
                        $agent['name'] = $matches[2][0];
                    }

                    array_push($data, $agent);
                }
            }

            if (empty($data) === false) {
                $data = array_reduce(
                    $data,
                    function ($carry, $item) {
                        global $config;
                        // Transforms array of arrays $data into an array
                        // of objects, making a post-process of certain fields.
                        $tmp = (object) $item;

                        $tmp->actions .= html_print_image(
                            'images/cross.png',
                            true,
                            [
                                'border'  => '0',
                                'class'   => 'action_button_img invert_filter',
                                'onclick' => 'delete_agent(\''.$tmp->address.'\',\''.$tmp->name.'\')',
                            ]
                        );

                        $carry[] = $tmp;
                        return $carry;
                    }
                );
            }

            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $count,
                    'recordsFiltered' => $count,
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

        $return_all_group = false;

        if (users_can_manage_group_all('AR') === true) {
            $return_all_group = true;
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

        if ($this->parseSatelliteConf('delete', $values) === false) {
            $this->ajaxMsg('error', __('Error saving agent'));
        } else {
            $this->ajaxMsg('result', _('Host '.$values['addres'].' added.'));
        }

        exit;
    }


    /**
     * Parse satellite configuration .
     *
     * @param  string $action  Action to perform (save, delete).
     * @param  array  $values.
     * @return void
     */
    private function parseSatelliteConf(string $action, array $values)
    {
        switch ($action) {
            case 'save':
                if (isset($values['address']) === true && empty($values['address']) === false) {
                    $string_hosts = 'add_host '.$values['address'].' '.$values['name']."\n";

                    // Add host to conf
                    array_push($this->satellite_config, $string_hosts);

                    // Check config.
                    if (empty($this->satellite_config)) {
                        return false;
                    }

                    $conf = implode('', $this->satellite_config);
                } else {
                    return false;
                }
            break;

            case 'delete':
                $conf = implode('', $this->satellite_config);
                // Find agent to mark for deletion.
                $pattern = io_safe_expreg($values['address'].' '.$values['name']);
                $re = "/add_host ($pattern)/m";
                $subst = 'delete_host $1';
                $conf = preg_replace($re, $subst, $conf);
            break;

            default:
                $this->ajaxMsg('error', __('Error'));
            exit;
        }

        return $this->saveAgent($conf);
    }


    /**
     * Saves agent to satellite cofiguration file.
     *
     * @param  array $values
     * @return void
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

            // Save configuration
            $result = file_put_contents($files['conf'], $new_conf);

        if ($result === false) {
            return false;
        }

            // Save configuration md5
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
      * @param string  $type   Type: result || error.
      * @param string  $msg    Message.
      * @param boolean $delete Deletion messages.
      *
      * @return void
      */
    private function ajaxMsg($type, $msg, $delete=false)
    {
        $msg_err = 'Failed while saving: %s';
        $msg_ok = 'Successfully saved agent ';

        if ($delete) {
            $msg_err = 'Failed while removing: %s';
            $msg_ok = 'Successfully deleted ';
        }

        if ($type == 'error') {
            echo json_encode(
                [
                    $type => ui_print_error_message(
                        __(
                            $msg_err,
                            $msg
                        ),
                        '',
                        true
                    ),
                ]
            );
        } else {
            echo json_encode(
                [
                    $type => ui_print_success_message(
                        __(
                            $msg_ok,
                            $msg
                        ),
                        '',
                        true
                    ),
                ]
            );
        }

        exit;
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
        function delete_agent(address, name) {
            $('#aux').empty();
            $('#aux').text('<?php echo __('Are you sure?'); ?>');
            $('#aux').dialog({
                title: '<?php echo __('Delete'); ?> ' + address,
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
                        text: 'Delete',
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

            $("#submit-create").on('click', function(){
                show_form();
            });
        });

             
        </script>
        <?php
        // EOF Javascript content.
        return ob_get_clean();
    }


}
