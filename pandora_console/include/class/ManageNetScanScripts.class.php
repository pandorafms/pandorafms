<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
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

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';

/**
 * ManageNetScanScripts. Host and devices child class.
 */
class ManageNetScanScripts extends Wizard
{

    /**
     * Number of pages to control breadcrum.
     *
     * @var integer
     */
    public $MAXPAGES = 2;

    /**
     * Labels for breadcrum.
     *
     * @var array
     */
    public $pageLabels = [
        'List NetScan scripts',
        'Operation NetScan scripts',
    ];


    /**
     * Constructor.
     *
     * @param integer $page      Page.
     * @param array   $breadcrum Breadcrum.
     *
     * @return void
     */
    public function __construct(int $page, array $breadcrum)
    {
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd'
        );

        $this->access = 'PM';
        $this->page = $page;
        $this->breadcrum = $breadcrum;
    }


    /**
     * Run function. It will be call into HostsDevices class.
     *      Page 0: Upload form.
     *      Page 1: Task resume.
     *
     * @return void
     */
    public function runManageNetScanScript()
    {
        global $config;

        if (check_acl($config['id_user'], 0, $this->access) === 0) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Net Scan Script.'
            );
            include 'general/noaccess.php';
            return;
        }

        $run_url = 'index.php?sec=gservers&sec2=godmode/servers/discovery';

        $breadcrum = [
            [
                'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery',
                'label' => 'Discovery',
            ],
            [
                'link'  => $run_url.'&wiz=hd',
                'label' => __('Host & Devices'),
            ],
        ];

        for ($i = 0; $i < $this->MAXPAGES; $i++) {
            $breadcrum[] = [
                'link'     => $run_url.'&wiz=hd&mode=managenetscanscripts&page='.$i,
                'label'    => __($this->pageLabels[$i]),
                'selected' => (($i == $this->page) ? 1 : 0),
            ];
        }

        if ($this->page < $this->MAXPAGES) {
            // Avoid to print header out of wizard.
            $this->prepareBreadcrum($breadcrum);

            // Header.
            ui_print_page_header(
                __('Net scan scripts'),
                '',
                false,
                '',
                true,
                '',
                false,
                '',
                GENERIC_SIZE_TEXT,
                '',
                $this->printHeader(true)
            );
        }

        $id_script = get_parameter('id_script', 0);

        // Initialize msg.
        $msg = [];

        // Operations.
        $operation_scp = get_parameter('operation_scp', '');
        if ($operation_scp !== '') {
            switch ($operation_scp) {
                case 'update_scp':
                    $msg = $this->updateScanScripts($id_script);
                break;

                case 'delete_scp':
                    $msg = $this->deleteScanScripts($id_script);
                break;

                case 'create_scp':
                    $msg = $this->createScanScripts($id_script);
                break;

                default:
                    // Nothing for doing. Never exist other operation.
                break;
            }
        }

        if (!isset($this->page) || $this->page === 0) {
            $this->printListNetScanScripts($msg);
        }

        if (!isset($this->page) || $this->page === 1) {
            $this->printFormScanScripts($id_script);
        }
    }


    /**
     * Create net scan script.
     *
     * @return array Check msg successfully or problem
     */
    private function createScanScripts()
    {
        $result = [];

        $reconscript_name = get_parameter('form_name', '');
        $reconscript_description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('form_description'))));
        $reconscript_script = get_parameter('form_script', '');

        // Get macros.
        $i = 1;
        $macros = [];
        while (1) {
            $macro = (string) get_parameter('field'.$i.'_macro');
            if ($macro == '') {
                break;
            }

            $desc = (string) get_parameter('field'.$i.'_desc');
            $help = (string) get_parameter('field'.$i.'_help');
            $value = (string) get_parameter('field'.$i.'_value');
            $hide = get_parameter('field'.$i.'_hide');

            $macros[$i]['macro'] = $macro;
            $macros[$i]['desc'] = $desc;
            $macros[$i]['help'] = $help;
            $macros[$i]['value'] = $value;
            $macros[$i]['hide'] = $hide;
            $i++;
        }

        $macros = io_json_mb_encode($macros);

        $values = [
            'name'        => $reconscript_name,
            'description' => $reconscript_description,
            'script'      => $reconscript_script,
            'macros'      => $macros,
        ];

        $result_crt = false;
        if ($values['name'] !== '' && $values['script'] !== '') {
            $result_crt = db_process_sql_insert('trecon_script', $values);
            if (!$result_crt) {
                $result = [
                    'error' => 1,
                    'msg'   => __('Problem creating'),
                ];
            } else {
                $result = [
                    'error' => 0,
                    'msg'   => __('Created successfully'),
                ];
            }
        } else {
            $result = [
                'error' => 1,
                'msg'   => __('Name or Script fullpath they can not be empty'),
            ];
        }

        return $result;
    }


    /**
     * Update net scan script.
     *
     * @param integer $id_script Id script.
     *
     * @return array Check msg successfully or problem
     */
    private function updateScanScripts(int $id_script)
    {
        $result = [];
        if (isset($id_script) === false || $id_script === 0) {
            $result = [
                'error' => 1,
                'msg'   => __('Problem deleting Net scan Scripts, Not selected script'),
            ];

            return $result;
        }

        // If modified any parameter.
        $reconscript_name = get_parameter('form_name', '');
        $reconscript_description = io_safe_input(strip_tags(io_safe_output((string) get_parameter('form_description'))));
        $reconscript_script = get_parameter('form_script', '');

        // Get macros.
        $i = 1;
        $macros = [];
        while (1) {
            $macro = (string) get_parameter('field'.$i.'_macro');
            if ($macro == '') {
                break;
            }

            $desc = (string) get_parameter('field'.$i.'_desc');
            $help = (string) get_parameter('field'.$i.'_help');
            $value = (string) get_parameter('field'.$i.'_value');
            $hide = get_parameter('field'.$i.'_hide');

            $macros[$i]['macro'] = $macro;
            $macros[$i]['desc'] = $desc;
            $macros[$i]['help'] = $help;
            $macros[$i]['value'] = $value;
            $macros[$i]['hide'] = $hide;
            $i++;
        }

        $macros = io_json_mb_encode($macros);

        $sql_update = sprintf(
            "UPDATE trecon_script SET
		        name = '%s',
		        description = '%s',
		        script = '%s',
		        macros = '%s'
            WHERE id_recon_script = %d",
            $reconscript_name,
            $reconscript_description,
            $reconscript_script,
            $macros,
            $id_script
        );

        $result_upd = false;
        if ($reconscript_name !== '' && $reconscript_script !== '') {
            $result_upd = db_process_sql($sql_update);
            if (!$result_upd) {
                $result = [
                    'error' => 1,
                    'msg'   => __('Problem updating'),
                ];
            } else {
                $result = [
                    'error' => 0,
                    'msg'   => __('Updated successfully'),
                ];
            }
        } else {
            $result = [
                'error' => 1,
                'msg'   => __('Name or Script fullpath they can not be empty'),
            ];
        }

        return $result;
    }


    /**
     * Delete net scan script.
     *
     * @param integer $id_script Id script.
     *
     * @return array Check msg successfully or problem
     */
    private function deleteScanScripts(int $id_script)
    {
        $result = [];
        if (isset($id_script) === false || $id_script === 0) {
            $result = [
                'error' => 1,
                'msg'   => __('Problem deleting Net scan Scripts, Not selected script'),
            ];

            return $result;
        }

        $result_dlt = db_process_sql_delete(
            'trecon_script',
            ['id_recon_script' => $id_script]
        );

        $result_dlt2 = db_process_sql_delete(
            'trecon_task',
            ['id_recon_script' => $id_script]
        );

        if (!$result_dlt) {
            $result = [
                'error' => 1,
                'msg'   => __('Problem deleting Net scan Scripts'),
            ];
        } else {
            $result = [
                'error' => 0,
                'msg'   => __('Deleted successfully'),
            ];
        }

        return $result;

    }


    /**
     * Print list Net scan scripts and messages operations.
     *
     * @param array $msg Print msg if necessary.
     *
     * @return void
     */
    private function printListNetScanScripts(array $msg)
    {
        global $config;

        if (count($msg) > 0) {
            if ($msg['error'] === 1) {
                ui_print_error_message($msg['msg']);
            } else {
                ui_print_success_message($msg['msg']);
            }
        }

        $url = 'index.php?sec=gservers&sec2=godmode/servers/discovery';
        $url .= '&wiz=hd&mode=managenetscanscripts';

        // List available Net scan scripts.
        $rows = db_get_all_rows_in_table('trecon_script');

        if ($rows !== false) {
            echo '<table width="100%" cellspacing="0" cellpadding="0" class="info_table">';
            echo '<thead>';
            echo '<th>'.__('Name').'</th>';
            echo '<th>'.__('Description').'</th>';
            echo '<th>'.__('Delete').'</th>';
            echo '</thead>';
            $color = 0;
            foreach ($rows as $row) {
                if ($color == 1) {
                    $tdcolor = 'datos';
                    $color = 0;
                } else {
                    $tdcolor = 'datos2';
                    $color = 1;
                }

                echo '<tr>';
                echo "<td class='".$tdcolor." mw100px''>";
                echo '<b><a href="'.$url.'&page=1&id_script='.$row['id_recon_script'].'">';
                echo $row['name'];
                echo '</a></b></td>';
                echo "</td><td class='".$tdcolor."'>";
                $desc = io_safe_output(
                    $row['description']
                );

                $desc = str_replace(
                    "\n",
                    '<br>',
                    $desc
                );

                echo $desc.'<br><br>';
                echo '<b>'.__('Command').': </b><i>'.$row['script'].'</i>';
                echo "</td><td align='center' class='".$tdcolor."'>";
                // Delete.
                echo '<form
                    method="post"
                    onsubmit="if (! confirm (\''.__('Are you sure delete script?').'\')) return false"
                    class="display_in">';
                echo html_print_input_hidden('page', 0, true);
                echo html_print_input_hidden(
                    'operation_scp',
                    'delete_scp',
                    true
                );
                echo html_print_input_hidden(
                    'id_script',
                    $row['id_recon_script'],
                    true
                );
                echo html_print_input_image(
                    'delete',
                    'images/delete.svg',
                    1,
                    '',
                    true,
                    [
                        'title' => __('Delete Script'),
                        'class' => 'invert_filter main_menu_icon',
                    ]
                );
                echo '</form>';
                echo '</td></tr>';
            }

            echo '</table>';

            echo "<form name=reconscript method='post' action='".$url."'>";
                echo html_print_input_hidden('page', 1, true);
                html_print_action_buttons(
                    html_print_submit_button(
                        __('Add'),
                        'crtbutton',
                        false,
                        [ 'icon' => 'next' ],
                        true
                    )
                );
            echo '</form>';
        } else {
            ui_print_info_message(
                [
                    'no_close' => true,
                    'message'  => __(
                        'There are no net scan scripts in the system'
                    ),
                ]
            );
        }
    }


    /**
     * Print form net scan scripts.
     *
     * @param integer $id_script Id script.
     *
     * @return void
     */
    private function printFormScanScripts(int $id_script)
    {
        // Initialize vars.
        if ($id_script !== 0) {
            $form_id = $id_script;
            $reconscript = db_get_row(
                'trecon_script',
                'id_recon_script',
                $form_id
            );
            $form_name = $reconscript['name'];
            $form_description = $reconscript['description'];
            $form_script = $reconscript['script'];
            $macros = $reconscript['macros'];
        } else {
            $form_name = '';
            $form_description = '';
            $form_script = '';
            $macros = '';
        }

        $url = 'index.php?sec=gservers&sec2=godmode/servers/discovery';
        $url .= '&wiz=hd&mode=managenetscanscripts';

        if ($id_script !== 0) {
            echo '<form name=reconscript class="max_floating_element_size" method="post" action="'.$url.'&id_script='.$id_script.'">';
            echo html_print_input_hidden('page', 0, true);
            echo html_print_input_hidden(
                'operation_scp',
                'update_scp',
                true
            );
        } else {
            echo '<form name=reconscript class="max_floating_element_size" method="post" action="'.$url.'">';
            echo html_print_input_hidden('page', 0, true);
            echo html_print_input_hidden(
                'operation_scp',
                'create_scp',
                true
            );
        }

        $table = new stdClass();
        $table->width = '100%';
        $table->id = 'table-form';
        $table->class = 'databox filter-table-adv';

        $data = [];
        $data[0] = __('Name');

        $data[1] = '<input type="text" name="form_name" size=30 value="'.$form_name.'">';
        $table->data['recon_name'] = $data;
        $table->colspan['recon_name'][1] = 3;

        $data = [];
        $data[0] = __('Script fullpath');
        $data[1] = '<input type="text" name="form_script" size=70 value="'.$form_script.'">';
        $table->data['recon_fullpath'] = $data;
        $table->colspan['recon_fullpath'][1] = 3;

        $data = [];
        $data[0] = __('Description');
        $data[1] = '<textarea name="form_description" cols="69" rows="5">';
        $data[1] .= $form_description;
        $data[1] .= '</textarea>';
        $table->data['recon_description'] = $data;
        $table->colspan['recon_description'][1] = 3;

        $macros = json_decode($macros, true);

        // This code is ready to add locked feature as plugins.
        $locked = false;

        // The next row number is recon_3.
        $next_name_number = 3;
        $i = 1;
        while (1) {
            // Always print at least one macro.
            if ((!isset($macros[$i]) || $macros[$i]['desc'] == '') && $i > 1) {
                break;
            }

            $macro_desc_name = 'field'.$i.'_desc';
            $macro_desc_value = '';
            $macro_help_name = 'field'.$i.'_help';
            $macro_help_value = '';
            $macro_value_name = 'field'.$i.'_value';
            $macro_value_value = '';
            $macro_name_name = 'field'.$i.'_macro';
            $macro_name = '_field'.$i.'_';
            $macro_hide_value_name = 'field'.$i.'_hide';
            $macro_hide_value_value = 0;

            if (isset($macros[$i]['desc'])) {
                $macro_desc_value = $macros[$i]['desc'];
            }

            if (isset($macros[$i]['help'])) {
                $macro_help_value = $macros[$i]['help'];
            }

            if (isset($macros[$i]['value'])) {
                $macro_value_value = $macros[$i]['value'];
            }

            if (isset($macros[$i]['hide'])) {
                $macro_hide_value_value = $macros[$i]['hide'];
            }

            $datam = [];
            $datam[0] = __('Description');
            $datam[0] .= "<span class='normal_weight'> ( ";
            $datam[0] .= $macro_name;
            $datam[0] .= ' )</span>';
            $datam[0] .= html_print_input_hidden(
                $macro_name_name,
                $macro_name,
                true
            );
            $datam[1] = html_print_input_text_extended(
                $macro_desc_name,
                $macro_desc_value,
                'text-'.$macro_desc_name,
                '',
                30,
                255,
                $locked,
                '',
                "class='command_advanced_conf'",
                true
            );
            if ($locked) {
                $datam[1] .= html_print_image(
                    'images/lock.png',
                    true,
                    ['class' => 'command_advanced_conf']
                );
            }

            $datam[2] = __('Default value');
            $datam[2] .= "<span class='normal_weight'> ( ";
            $datam[2] .= $macro_name;
            $datam[2] .= ' ) </span>';
            $datam[3] = html_print_input_text_extended(
                $macro_value_name,
                $macro_value_value,
                'text-'.$macro_value_name,
                '',
                30,
                255,
                $locked,
                '',
                "class='command_component command_advanced_conf'",
                true
            );
            if ($locked) {
                $datam[3] .= html_print_image(
                    'images/lock.png',
                    true,
                    ['class' => 'command_advanced_conf']
                );
            }

            $table->data['recon_'.$next_name_number] = $datam;

            $next_name_number++;

            $table->colspan['recon_'.$next_name_number][1] = 3;

            $datam = [];
            $datam[0] = __('Hide value');
            $datam[0] .= ui_print_help_tip(
                __('This field will show up as dots like a password'),
                true
            );

            $datam[1] = html_print_checkbox_extended(
                $macro_hide_value_name,
                1,
                $macro_hide_value_value,
                0,
                '',
                ['class' => 'command_advanced_conf'],
                true,
                'checkbox-'.$macro_hide_value_name
            );

            $table->data['recon_'.$next_name_number] = $datam;
            $next_name_number++;

            $table->colspan['recon_'.$next_name_number][1] = 3;

            $datam = [];
            $datam[0] = __('Help');
            $datam[0] .= "<span class='normal_weight'> ( ";
            $datam[0] .= $macro_name;
            $datam[0] .= ' )</span><br><br><br>';

            $tadisabled = ($locked === true) ? ' disabled' : '';

            $datam[1] = html_print_textarea(
                $macro_help_name,
                6,
                100,
                $macro_help_value,
                'class="command_advanced_conf w97p"'.$tadisabled,
                true
            );

            if ($locked) {
                $datam[1] .= html_print_image(
                    'images/lock.png',
                    true,
                    ['class' => 'command_advanced_conf']
                );
            }

            $datam[1] .= '<br><br><br>';

            $table->data['recon_'.$next_name_number] = $datam;
            $next_name_number++;
            $i++;
        }

        if (!$locked) {
            $datam = [];
            $datam[0] = '<span class="bolder">';
            $datam[0] .= __('Add macro').'</span>';
            $datam[0] .= '<a href="javascript:new_macro(\'table-form-recon_\');update_preview();">';
            $datam[0] .= html_print_image(
                'images/add.png',
                true
            );
            $datam[0] .= '</a>';
            $datam[0] .= '<div id="next_macro" class="invisible">';
            $datam[0] .= $i.'</div>';
            $datam[0] .= '<div id="next_row" class="invisible">';
            $datam[0] .= $next_name_number.'</div>';

            $delete_macro_style = '';
            if ($i <= 2) {
                $delete_macro_style = 'display:none;';
            }

            $datam[2] = '<div id="delete_macro_button" style="'.$delete_macro_style.'">';
            $datam[2] .= __('Delete macro');
            $datam[2] .= '<a href="javascript:delete_macro_form(\'table-form-recon_\');update_preview();">';
            $datam[2] .= html_print_image(
                'images/delete.png',
                true,
                ['class' => 'invert_filter']
            );
            $datam[2] .= '</a></div>';

            $table->colspan['recon_action'][0] = 2;
            $table->rowstyle['recon_action'] = 'text-align:center';
            $table->colspan['recon_action'][2] = 2;
            $table->data['recon_action'] = $datam;
        }

        html_print_table($table);

        if ($id_script === 0) {
            $buttonName = 'crtbutton';
            $buttonCaption = __('Create');
            $buttonIcon = 'wand';
        } else {
            $buttonName = 'updbutton';
            $buttonCaption = __('Update');
            $buttonIcon = 'update';
        }

        html_print_action_buttons(
            html_print_div(
                [
                    'class'   => 'action-buttons',
                    'content' => html_print_submit_button(
                        $buttonCaption,
                        $buttonName,
                        false,
                        [ 'icon' => $buttonIcon ],
                        true
                    ),
                ],
                true
            )
        );

        echo '</form>';

        ui_require_javascript_file('pandora_modules');
    }


}
