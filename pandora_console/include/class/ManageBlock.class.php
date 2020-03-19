<?php
/**
 * Module Block feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage SNMP
 * @version    0.0.1
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

global $config;

require_once $config['homedir'].'/include/class/HTML.class.php';
/**
 * Class ManageBlock
 */
class ManageBlock extends HTML
{

    /**
     * Var that contain very cool stuff
     *
     * @var string
     */
    private $ajaxController;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $offset;

    /**
     * Base URL for internal purposes
     *
     * @var string
     */
    private $baseUrl;

    /**
     * Id of the Thing ???
     *
     * @var integer
     */
    private $id_np;


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

        $this->id_np = get_parameter('id_np', -1);

        $this->offset = get_parameter('offset', 0);

        $this->ajaxController = $ajax_controller;

        // Set baseUrl for use it in several locations in this class
        $this->baseUrl = ui_get_full_url('index.php?sec=gmodules&sec2=godmode/modules/manage_block_templates');

        return $this;
    }


    /**
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // Require specific CSS and JS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        // Header section.
        // Breadcrums.
        $this->setBreadcrum([]);

        $this->prepareBreadcrum(
            [
                [
                    'link'     => '',
                    'label'    => __('Configuration'),
                    'selected' => false,
                ],
                [
                    'link'     => $this->baseUrl,
                    'label'    => __('Module Blocks'),
                    'selected' => true,
                ],
            ],
            true
        );

        ui_print_page_header(
            __('Manage module blocks'),
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


    /**
     * Get the value of this current thing ???
     *
     * @return integer Id of this thing ???
     */
    public function getIdNp()
    {
        return $this->id_np;
    }


    /**
     * Create the table with the list of Blocks Templates
     *
     * @return html Formed table
     */
    public function moduleBlockList()
    {
        global $config;
        // Get the count of Blocks.
        $countModuleBlocks = db_get_value(
            'count(*)',
            'tnetwork_profile'
        );

        // Get all the data.
        $resultModuleBlocksTable = db_get_all_rows_filter(
            'tnetwork_profile',
            [
                'order'  => 'name',
                'limit'  => $config['block_size'],
                'offset' => $this->offset,
            ]
        );

        ui_pagination($countModuleBlocks, false, $this->offset);
        // Create the table with Module Block list.
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
        $table->head[0] = html_print_checkbox('all_delete', 0, false, true, false);

        $table->head[1] = __('Name');
        $table->head[2] = __('Description');
        $table->head[3] = '<span style="margin-right:7%;">'.__('Action').'</span>';
        $table->size = [];
        $table->size[0] = '20px';
        $table->size[2] = '65%';
        $table->size[3] = '15%';

        $table->align = [];
        $table->align[3] = 'left';

        $table->data = [];

        foreach ($resultModuleBlocksTable as $row) {
            $data = [];
            $data[0] = html_print_checkbox_extended('delete_multiple[]', $row['id_np'], false, false, '', 'class="check_delete"', true);
            $data[1] = '<a href="'.$this->baseUrl.'&amp;id_np='.$row['id_np'].'">'.io_safe_output($row['name']).'</a>';
            $data[2] = ui_print_truncate_text(io_safe_output($row['description']), 'description', true, true, true, '[&hellip;]');
            $table->cellclass[][3] = 'action_buttons';
            $data[3] = html_print_input_image(
                'delete_profile',
                'images/cross.png',
                $row['id_np'],
                '',
                true,
                ['onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;']
            );
            $data[3] .= html_print_input_image(
                'export_profile',
                'images/csv.png',
                $row['id_np'],
                '',
                true,
                ['title' => 'Export to CSV']
            );
            $data[3] = '<a href="'.$this->baseUrl.'&delete_profile=1&delete_profile='.$row['id_np'].'" '.'onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';
            $data[3] .= '<a href="'.$this->baseUrl.'&export_profile='.$row['id_np'].'">'.html_print_image('images/csv.png', true, ['title' => __('Export to CSV')]).'</a>';

            array_push($table->data, $data);
        }

        html_print_table($table);

        $output = '<div style="float:right;" class="">';

        $form = [
            'method' => 'POST',
            'action' => $this->baseUrl,
        ];

        $inputs[] = [
            'arguments' => [
                'name'   => 'id_np',
                'type'   => 'hidden',
                'value'  => 0,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'label'      => __('Create'),
                'name'       => 'crt',
                'type'       => 'submit',
                'attributes' => 'class="sub wand"',
                'return'     => true,
            ],
        ];

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
     * Prints Form for template management
     *
     * @param integer $id_np
     */
    public function moduleTemplateForm(int $id_np=0)
    {
        $output = [];
        $createNewBlock = ($id_np === 0) ? true : false;

        if ($createNewBlock) {
            // Assignation for submit button.
            $formButtonClass = 'sub wand';
            $formButtonName = 'crtbutton';
            $formButtonLabel = __('Create');
            // Set of empty values.
            $description = '';
            $name = '';
            $pen = '';
        } else {
            // Assignation for submit button.
            $formButtonClass = 'sub upd';
            $formButtonName = 'updbutton';
            $formButtonLabel = __('Update');
            // Profile exists.
            $row = db_get_row('tnetwork_profile', 'id_np', $id_np);
            // Fill the inputs with the obtained data.
            $description = $row['description'];
            $name = $row['name'];
            $pen = '';
        }

        // Main form.
        $form = [
            'action'   => $this->baseUrl,
            'id'       => 'module_block_form',
            'onsubmit' => 'return false;',
            'method'   => 'POST',
            'class'    => 'databox filters',
            'extra'    => '',
        ];

        // Inputs.
        $inputs = [];

        $inputs[] = [
            'label'     => __('Name'),
            'id'        => 'inp-name',
            'arguments' => [
                'name'        => 'name',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'value'       => $name,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Description'),
            'id'        => 'inp-description',
            'arguments' => [
                'name'        => 'description',
                'input_class' => 'flex-row',
                'type'        => 'textarea',
                'value'       => $description,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('PEN'),
            'id'        => 'inp-pen',
            'arguments' => [
                'name'        => 'pen',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'value'       => $pen,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'name'       => $formButtonName,
                'label'      => $formButtonLabel,
                'type'       => 'button',
                'attributes' => 'class="'.$formButtonClass.'"',
                'return'     => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'name'       => 'buttonGoBack',
                'label'      => __('Go back'),
                'type'       => 'button',
                'attributes' => 'class="sub cancel"',
                'return'     => true,
            ],
        ];

        $this->printFormAsList(
            [
                'form'   => $form,
                'inputs' => $inputs,
                true
            ]
        );

        if ($createNewBlock === false) {
            // Get the data.
            $sql = sprintf(
                'SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS `group`, ncg.name AS `group_name`
				FROM tnetwork_profile_component AS npc, tnetwork_component AS nc 
				INNER JOIN tnetwork_component_group AS ncg ON ncg.id_sg = nc.id_group
                WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d',
                $id_np
            );
            $moduleBlocks = db_get_all_rows_sql($sql);

            $blockTables = [];
            // Build the information of the blocks
            foreach ($moduleBlocks as $block) {
                if (key_exists($block['group'], $blockTables) === false) {
                    $blockTables[$block['group']] = [
                        'name' => $block['group_name'],
                        'data' => [],
                    ];
                } else {
                    $blockTables[$block['group']]['data'][] = [
                        'component_id' => $block['component_id'],
                        'name'         => $block['name'],
                        'type'         => $block['type'],
                        'description'  => $block['description'],
                    ];
                }
            }

            if (count($blockTables) === 0) {
                ui_print_info_message(__('No module blocks for this profile'));
            } else {
                foreach ($blockTables as $id_group => $blockTable) {
                    $blockData = $blockTable['data'];
                    $blockTitle = $blockTable['name'];
                    $blockTitle .= '<div class="white_table_header_checkbox">'.html_print_checkbox_switch_extended('block_id_'.$id_group, 1, 0, false, '', '', true).'</div>';

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
                    $table->head[0] = __('Module Name');
                    $table->head[1] = __('Type');
                    $table->head[2] = __('Description');
                    $table->head[3] = '<span style="float:right;margin-right:2em;">'.__('Add').'</span>';

                    $table->size = [];
                    $table->size[0] = '20%';
                    $table->size[2] = '65%';
                    $table->size[3] = '15%';

                    $table->align = [];
                    $table->align[3] = 'right';

                    $table->style = [];
                    $table->style[3] = 'padding-right:2em';

                    $table->data = [];

                    foreach ($blockData as $module) {
                        $data[0] = $module['name'];
                        $data[1] = ui_print_moduletype_icon($module['type'], true);
                        $data[2] = mb_strimwidth(io_safe_output($module['description']), 0, 150, '...');
                        $data[3] = html_print_checkbox_switch_extended('active_'.$module['component_id'], 1, 0, false, '', '', true);

                        array_push($table->data, $data);
                    }

                    $content = html_print_table($table, true);

                    $output[] = ui_toggle($content, $blockTitle, '', '', false);
                }
            }
        }

    }


}

/*
    ui_require_jquery_file('tag-editor');
    ui_require_css_file('jquery.tag-editor');
    $(\'#text-community\').tagEditor();
*/
