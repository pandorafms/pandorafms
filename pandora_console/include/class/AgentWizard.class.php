<?php
/**
 * Agent Wizard for SNMP and WMI
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Agent Configuration
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
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

// Necessary class for extends.
require_once $config['homedir'].'/include/class/HTML.class.php';
/**
 * AgentWizard class
 */
class AgentWizard extends HTML
{

    /**
     * Var that contain very cool stuff
     *
     * @var string
     */
    private $ajaxController;

    /**
     * Contains the URL of this
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Id of this current agent
     *
     * @var integer
     */
    private $idAgent;

    /**
     * Wizard Section for Explore
     *
     * @var string
     */
    private $wizardSection;

    /**
     * Label to show what action are performing
     *
     * @var string
     */
    private $actionLabel;

    /**
     * Type of action to do
     *
     * @param string
     */
    private $actionType;


    /**
     * Constructor
     *
     * @param string $ajax_controller Pues hace cosas to wapas.
     */
    public function __construct(string $ajax_controller)
    {
        global $config;

        // Check access.
        check_login();

        if (! check_acl($config['id_user'], 0, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access event viewer'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        // Set baseUrl for use it in several locations in this class.
        $this->baseUrl          = ui_get_full_url('index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=agent_wizard');
        // Capture all parameters before start.
        $this->ajaxController   = $ajax_controller;
        $this->wizardSection    = get_parameter('wizard_section', '');
        $this->idAgent          = get_parameter('id_agente', '');
        return $this;
    }


    /**
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // CSS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        // Javascript.
        // ui_require_javascript_file('jquery.caret.min');
        $this->loadMainForm();
        $this->performWizard();
        // Load integrated JS
        $this->loadJS();
    }


    /**
     * Common Main Wizard form
     *
     * @return void
     */
    private function loadMainForm()
    {
        // Define name of explorer button
        switch ($this->wizardSection) {
            case 'snmp_explorer':
            case 'snmp_interfaces_explorer':
                // Define labels.
                $this->actionType = 'snmp';
                $this->actionLabel = __('SNMP Walk');
                // Fill with servers to perform SNMP walk.
                $fieldsServers = [];
                $fieldsServers[0] = __('Local console');
                if (enterprise_installed()) {
                    enterprise_include_once('include/functions_satellite.php');
                    // Get the servers.
                    $rows = get_proxy_servers();
                    // Check if satellite server has remote configuration enabled.
                    $satellite_remote = config_agents_has_remote_configuration($this->idAgent);
                    // Generate a list with allowed servers.
                    foreach ($rows as $row) {
                        if ($row['server_type'] == 13) {
                            $id_satellite = $row['id_server'];
                            $serverType = ' (Satellite)';
                        } else {
                            $serverType = ' (Standard)';
                        }

                        $fieldsServers[$row['id_server']] = $row['name'].$serverType;
                    }
                }

                // Fill with SNMP versions allowed.
                $fieldsVersions = [
                    '1'  => '1',
                    '2'  => '2',
                    '2c' => '2c',
                    '3'  => '3',
                ];
            break;

            case 'wmi_explorer':
                $this->actionType = 'wmi';
                $this->actionLabel = __('WMI Explorer');
            break;

            default:
                $this->actionType = 'none';
                $this->actionLabel = __('Nothing');
            exit;
            break;
        }

        // Main form.
        $form = [
            'action' => '',
            // 'action' => $this->baseUrl,
            'id'     => 'main_wizard_form',
            'method' => 'POST',
        ];

        // Inputs.
        $inputs = [];

        $inputs[] = [
            'id'        => 'hdn-type-action',
            'arguments' => [
                'name'   => 'type-action',
                'type'   => 'hidden',
                'value'  => $this->actionType,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Target IP'),
            'id'        => 'txt-target-ip',
            'arguments' => [
                'name'        => 'target-ip',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'class'       => '',
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Port'),
            'id'        => 'txt-target-port',
            'arguments' => [
                'name'        => 'target-port',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'size'        => '20',
                'class'       => '',
                'return'      => true,
            ],
        ];

        if ($this->actionType === 'snmp') {
            $inputs[] = [
                'label'     => __('Use agent IP'),
                'id'        => 'txt-use-agent-ip',
                'arguments' => [
                    'name'        => 'use-agent-ip',
                    'input_class' => 'flex-row',
                    'type'        => 'checkbox',
                    'class'       => '',
                    'return'      => true,
                ],
            ];
        }

        if ($this->actionType === 'wmi') {
            $inputs[] = [
                'label'     => __('Namespace'),
                'id'        => 'txt-namespace',
                'arguments' => [
                    'name'        => 'namespace',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Username'),
                'id'        => 'txt-username',
                'arguments' => [
                    'name'        => 'username',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('Password'),
                'id'        => 'txt-password',
                'arguments' => [
                    'name'        => 'password',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'class'       => '',
                    'return'      => true,
                ],
            ];
        }

        $inputs[] = [
            'label'     => __('Server to execute command'),
            'id'        => 'txt-target-port',
            'arguments' => [
                'name'        => 'target-port',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'fields'      => $fieldsServers,
                'class'       => '',
                'return'      => true,
            ],
        ];

        if ($this->actionType === 'snmp') {
            $inputs[] = [
                'label'     => __('SNMP community'),
                'id'        => 'txt-snmp-community',
                'arguments' => [
                    'name'        => 'snmp-community',
                    'input_class' => 'flex-row',
                    'type'        => 'text',
                    'size'        => '20',
                    'class'       => '',
                    'return'      => true,
                ],
            ];

            $inputs[] = [
                'label'     => __('SNMP version'),
                'id'        => 'txt-snmnp-version',
                'arguments' => [
                    'name'        => 'snmnp-version',
                    'input_class' => 'flex-row',
                    'type'        => 'select',
                    'fields'      => $fieldsVersions,
                    'class'       => '',
                    'return'      => true,
                ],
            ];
        }

        $inputs[] = [
            'arguments' => [
                'label'      => $this->actionLabel,
                'name'       => 'action',
                'type'       => 'submit',
                'attributes' => 'class="sub next" onclick="performAction();return false;"',
                'return'     => true,
            ],
        ];

        $output = '<div class="white_box">';
        $output .= $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );
        $output .= '</div>';

        echo $output;
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function performWizard()
    {
        // echo json_encode(['error' => obhd($_REQUEST)]);
        // exit;
        $sql = sprintf(
            'SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS `group`, ncg.name AS `group_name`
            FROM tnetwork_profile_component AS npc, tnetwork_component AS nc 
            INNER JOIN tnetwork_component_group AS ncg ON ncg.id_sg = nc.id_group
            WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d',
            10
        );
        $moduleBlocks = db_get_all_rows_sql($sql);

        $blockTables = [];
        // Build the information of the blocks.
        foreach ($moduleBlocks as $block) {
            if (key_exists($block['group'], $blockTables) === false) {
                $blockTables[$block['group']] = [
                    'name' => $block['group_name'],
                    'data' => [],
                ];
            }

            $blockTables[$block['group']]['data'][] = [
                'component_id' => $block['component_id'],
                'name'         => $block['name'],
                'type'         => $block['type'],
                'description'  => $block['description'],
            ];
        }

        $output = '<div class="white_box" style="margin-top: 20px;">';
        foreach ($blockTables as $id_group => $blockTable) {
            // Data with all components.
            $blockData = $blockTable['data'];
            // Creation of list of all components.
            $blockComponentList = '';
            foreach ($blockData as $component) {
                $blockComponentList .= $component['component_id'].',';
            }

            $blockComponentList = chop($blockComponentList, ',');
            // Title of Block.
            $blockTitle = $blockTable['name'];
            $blockTitle .= '<div class="white_table_header_checkbox">';
            $blockTitle .= html_print_checkbox_switch_extended('sel_block_'.$id_group, 1, 0, false, 'switchBlockControl(event)', '', true);
            $blockTitle .= '</div>';

            $table = new StdClasS();
            $table->class = 'databox data';
            $table->width = '75%';
            $table->styleTable = 'margin: 2em auto 0;border: 1px solid #ddd;background: white;';
            $table->rowid = [];
            $table->data = [];

            $table->cellpadding = 0;
            $table->cellspacing = 0;
            $table->width = '100%';
            $table->class = 'info_table';

            $table->head = [];
            $table->head[0] = '<div style="font-weight:700;">'.__('Module Name').'</div>';
            $table->head[1] = '<div style="text-align:center;font-weight:700;">'.__('Type').'</div>';
            $table->head[2] = '<div style="font-weight:700;">'.__('Module info').'</div>';
            $table->head[3] = '<div style="text-align:center;font-weight:700;">'.__('Warning').'</div>';
            $table->head[4] = '<div style="text-align:center;font-weight:700;">'.__('Critical').'</div>';
            $table->head[5] = '<div style="margin-right:1.2em;font-weight:700;">'.__('Active').'</div>';

            $table->size = [];
            $table->size[0] = '15%';
            $table->size[1] = '3%';
            $table->size[3] = '210px';
            $table->size[4] = '210px';
            $table->size[5] = '3%';

            $table->align = [];
            $table->align[5] = 'right';

            $table->data = [];

            foreach ($blockData as $module) {
                // Module Name column.
                $data[0] = $module['name'];
                // Module Type column.
                $data[1] = ui_print_moduletype_icon($module['type'], true);
                // Module info column.
                $data[2] = mb_strimwidth(io_safe_output($module['description']), 0, 150, '...');
                // Warning column.
                $data[3] = '<div style="float: left;width: 33%;text-align: center;">Min: ';
                $data[3] .= html_print_input_text(
                    'txt_min_warn_'.$module['component_id'],
                    '0',
                    '',
                    3,
                    4,
                    true
                );
                $data[3] .= '</div>';
                $data[3] .= ' ';
                $data[3] .= '<div style="float: left;width: 33%;text-align: center;">Max: ';
                $data[3] .= html_print_input_text(
                    'txt_max_warn_'.$module['component_id'],
                    '0',
                    '',
                    3,
                    4,
                    true
                );
                $data[3] .= '</div>';
                $data[3] .= '<div style="float: left;width: 33%;margin-top: 0.3em;">Inv: ';
                $data[3] .= html_print_checkbox(
                    'chk_inv_warn_'.$module['component_id'],
                    0,
                    false,
                    true,
                    false
                );
                $data[3] .= '</div>';
                // Critical column.
                $data[4] = '<div style="float: left;width: 33%;text-align: center;">Min: ';
                $data[4] .= html_print_input_text(
                    'txt_min_crit_'.$module['component_id'],
                    '0',
                    '',
                    3,
                    4,
                    true
                );
                $data[4] .= '</div>';
                $data[4] .= ' ';
                $data[4] .= '<div style="float: left;width: 33%;text-align: center;">Max: ';
                $data[4] .= html_print_input_text(
                    'txt_max_crit_'.$module['component_id'],
                    '0',
                    '',
                    3,
                    4,
                    true
                );
                $data[4] .= '</div>';
                $data[4] .= ' ';
                $data[4] .= '<div style="float: left;width: 33%;margin-top: 0.3em;">Inv: ';
                $data[4] .= html_print_checkbox(
                    'chk_inv_crit_'.$module['component_id'],
                    0,
                    false,
                    true,
                    false
                );
                $data[4] .= '</div>';
                // Activavion column.
                $data[5] = html_print_checkbox_switch_extended('sel_module_'.$id_group.'_'.$module['component_id'], 1, 0, false, 'switchBlockControl(event)', '', true);

                array_push($table->data, $data);
            }

            $content = html_print_table($table, true);

            $output .= ui_toggle($content, $blockTitle, '', '', false, true);
        }

        $output .= '</div>';

        echo $output;
        // Main form.
        $form = [
            'action' => $this->baseUrl,
            'id'     => 'modal_form_action_response',
            'method' => 'POST',
            'class'  => 'modal',
            'extra'  => '',
        ];

        // Inputs.
        $inputs = [];

        $inputs[] = [
            'id'        => 'inp-id_np',
            'arguments' => [
                'name'   => 'id_np',
                'type'   => 'hidden',
                'value'  => '69',
                'return' => true,
            ],
        ];

        $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
                true
            ]
        );
    }


    /**
     * Perform SNMP
     *
     * @return void
     */
    private function performSNMP()
    {
        echo 'HOLA';
    }


    /**
     * Perform WMI
     *
     * @return void
     */
    private function performWMI()
    {

    }


    /**
     * Generate the JS needed for use inside
     *
     * @return void
     */
    private function loadJS()
    {
        $str = '';

        ob_start();
        ?>
        <script type="text/javascript">
            // The functions goes here!

            /**
             * Loads modal from AJAX to perform the required action.
             */
            function performAction() {
                var btn_ok_text = '<?php echo __('OK'); ?>';
                var btn_cancel_text = '<?php echo __('Cancel'); ?>';
                var title = '<?php echo __('Perform %s', $this->actionLabel); ?>';
                var action = '<?php echo $this->actionType; ?>'; 
                console.log(title);
                console.log(action);
                load_modal({
                    target: $('#modal'),
                    form: 'modal_form_action_response',
                    url: '<?php echo ui_get_full_url('ajax.php', false, false, false); ?>',
                    ajax_callback: showMsg,
                    modal: {
                        title: title,
                        ok: btn_ok_text,
                        cancel: btn_cancel_text,
                    },
/*                     extradata: [
                        {
                            name: 'action',
                            value: action,
                        }
                    ], */
                    onshow: {
                        page: '<?php echo $this->ajaxController; ?>',
                        method: 'performWizard'
                    }/* ,
                    onsubmit: {
                        page: '<?//php echo $this->ajaxController; ?>',
                        method: 'processData'
                    } */
                });
                console.log("he terminado");
            }

            /**
            * Process ajax responses and shows a dialog with results.
            */
            function showMsg(data) {
                var title = "<?php echo __('Success'); ?>";
                var text = "";
                var failed = 0;
                try {
                data = JSON.parse(data);
                text = data["result"];
                } catch (err) {
                title = "<?php echo __('Failed'); ?>";
                text = err.message;
                failed = 1;
                }
                if (!failed && data["error"] != undefined) {
                title = "<?php echo __('Failed'); ?>";
                text = data["error"];
                failed = 1;
                }
                if (data["report"] != undefined) {
                data["report"].forEach(function(item) {
                    text += "<br>" + item;
                });
                }
            
                $("#msg").empty();
                $("#msg").html(text);
                $("#msg").dialog({
                width: 450,
                position: {
                    my: "center",
                    at: "center",
                    of: window,
                    collision: "fit"
                },
                title: title,
                buttons: [
                    {
                    class:
                        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
                    text: "OK",
                    click: function(e) {
                        
                    }
                    }
                ]
                });

            }

            /**
             * Controls checkboxes for modules
             */
            function switchBlockControl(e) {
                var switchId = e.target.id.split("_");
                var type = switchId[1];
                var blockNumber = switchId[2];
                var selectedBlock = $("#checkbox-sel_block_" + blockNumber);
                var totalCount = 0;
                var markedCount = 0;

                if (type == 'block') {
                    if (selectedBlock.prop("checked")) {
                        $("[id*=checkbox-sel_module_" + blockNumber + "]").each(function(){
                            $(this).prop("checked", true);
                        });
                    } else {
                        $("[id*=checkbox-sel_module_" + blockNumber + "]").each(function(){
                            $(this).prop("checked", false);
                        });
                    }
                } else if (type == 'module') {
                    $("[id*=checkbox-sel_module_" + blockNumber + "]").each(function() {
                        if ($(this).prop("checked")) {
                            markedCount++;
                        }
                        totalCount++;
                    });

                    if (totalCount == markedCount) {
                        selectedBlock.prop("checked", true);
                        selectedBlock
                            .parent()
                            .removeClass("alpha50");
                    } else if (markedCount == 0) {
                        selectedBlock.prop("checked", false);
                        selectedBlock
                            .parent()
                            .removeClass("alpha50");
                    } else {
                        selectedBlock.prop("checked", true);
                        selectedBlock
                            .parent()
                            .addClass("alpha50");
                    }
                }


            }
        </script>
        <?php
        $str = ob_get_clean();
        echo $str;
        return $str;
    }
}