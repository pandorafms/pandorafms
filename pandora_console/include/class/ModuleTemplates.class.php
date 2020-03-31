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
 * Class ModuleTemplates
 */
class ModuleTemplates extends HTML
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
     * Name of Block Template
     *
     * @var [type]
     */
    private $name;

    /**
     * Description of Block Template
     *
     * @var [type]
     */
    private $description;

    /**
     * Private Enterprise Numbers of Block Templates
     *
     * @var array
     */
    private $pen;

    /**
     * Group for adding modules
     *
     * @var string
     */
    private $ncGroup;

    /**
     * Filter for adding modules
     *
     * @var string
     */
    private $ncFilter;


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
        $this->baseUrl          = ui_get_full_url('index.php?sec=gmodules&sec2=godmode/modules/manage_module_templates');
        // Capture all parameters before start.
        $this->id_np            = get_parameter('id_np', -1);
        $this->name             = get_parameter('name', '');
        $this->description      = get_parameter('description', '');
        $this->pen              = get_parameter('pen', '');
        // Separate all PENs received.
        $this->offset           = get_parameter('offset', 0);
        $this->ajaxController   = $ajax_controller;
        $this->ncGroup          = get_parameter('ncgroup', -1);
        $this->ncFilter         = get_parameter('ncfilter', '');

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
                    'link'     => '',
                    'label'    => __('Templates'),
                    'selected' => false,
                ],
                [
                    'link'     => $this->baseUrl,
                    'label'    => __('Module template management'),
                    'selected' => true,
                ],
            ],
            true
        );

        ui_print_page_header(
            __('Module template management'),
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
     * Save or Update the data received.
     *
     * @return void
     */
    public function processData()
    {
        // Get action if is needed.
        $action      = get_parameter('submit_button', '');
        // Success variable.
        $success     = false;
        // Evaluate the modules allowed.
        if (!empty($action)) {
            $numberComponent = [];
            foreach ($_POST as $k => $value) {
                if (strpos($k, 'module_check_') >= 0 && $value == 1) {
                    $tmpNumberComponent = explode('_', $k);
                    $numberComponent[] = $tmpNumberComponent[2];
                }
            }

            switch ($action) {
                case 'Update':
                    $dbResult_tnp = db_process_sql_update(
                        'tnetwork_profile',
                        [
                            'name'        => $this->name,
                            'description' => $this->description,
                        ],
                        ['id_np' => $this->id_np]
                    );
                    if ($dbResult_tnp === false) {
                        $success = false;
                    } else {
                        db_process_sql_delete('tnetwork_profile_pen', ['id_np' => $this->id_np]);
                        $pensList = explode(',', $this->pen);
                        if (count($pensList) > 0) {
                            // Set again the new PENs associated.
                            foreach ($pensList as $currentPen) {
                                $dbResult_pen = db_process_sql_insert(
                                    'tnetwork_profile_pen',
                                    [
                                        'pen'   => $currentPen,
                                        'id_np' => $this->id_np,
                                    ]
                                );
                                if ($dbResult_pen === false) {
                                    $success = false;
                                    break;
                                }

                                $success = true;
                            }
                        } else {
                            $success = true;
                        }
                    }
                break;

                case 'Create':
                    $dbResult_tnp = db_process_sql_insert(
                        'tnetwork_profile',
                        [
                            'name'        => $this->name,
                            'description' => $this->description,
                        ]
                    );
                    // The insert gone fine!
                    if ($dbResult_tnp != false) {
                        // Set the new id_np.
                        $this->id_np = $dbResult_tnp;
                        $pensList = explode(',', $this->pen);
                        // Insert all of new PENs associated with this id_np.
                        foreach ($pensList as $currentPen) {
                            $dbResult_pen = db_process_sql_insert(
                                'tnetwork_profile_pen',
                                [
                                    'pen'   => $currentPen,
                                    'id_np' => $this->id_np,
                                ]
                            );
                            // If something is wrong, is better stop.
                            if ($dbResult_pen === false) {
                                break;
                            }

                            $success = true;
                        }
                    }
                break;

                case 'Delete':
                    // Only in this case, catch delete_profile.
                    $deleteProfile = get_parameter('delete_profile', -1);
                    $dbResult = db_process_sql_delete('tnetwork_profile', ['id_np' => $deleteProfile]);

                    if ($dbResult != false) {
                        $success = true;
                    }
                break;

                default:
                    $success = false;
                break;
            }

            if ($success === false) {
                ui_print_error_message(__('Error saving data'));
            } else {
                ui_print_success_message(__('Changes saved sucessfully'));
            }
        }

    }


    /**
     * Show the adding modules form
     *
     * @return void
     */
    public function addingModulesForm()
    {
        // Get the groups for select input
        $result = db_get_all_rows_in_table('tnetwork_component_group', 'name');
        if ($result === false) {
            $result = [];
        }

        // 2 arrays. 1 with the groups, 1 with the groups by parent
        $groups = [];
        $groups_compound = [];
        $groups_compound[0] = 'Group - All';
        foreach ($result as $row) {
            $groups[$row['id_sg']] = $row['name'];
        }

        foreach ($result as $row) {
            $groups_compound[$row['id_sg']] = '';
            if ($row['parent'] > 1) {
                $groups_compound[$row['id_sg']] = $groups[$row['parent']].' / ';
            }

            $groups_compound[$row['id_sg']] .= $row['name'];
        }

        // Get the components for show in a list for select
        if ($this->ncGroup > 0) {
            $sql = sprintf(
                "
                SELECT id_nc, name, id_group
                FROM tnetwork_component
                WHERE id_group = %d AND name LIKE '%".$this->ncFilter."%'
                ORDER BY name",
                $this->ncGroup
            );
        } else {
            $sql = "
                SELECT id_nc, name, id_group
                FROM tnetwork_component
                WHERE name LIKE '%".$this->ncFilter."%'
                ORDER BY name";
        }

        $result = db_get_all_rows_sql($sql);
        $components = [];
        if ($result === false) {
            $result = [];
        }

        foreach ($result as $row) {
            $components[$row['id_nc']] = $row['name'];
        }

        // Main form.
        $form = [
            'action' => $this->baseUrl,
            'id'     => 'add_module_form',
            'method' => 'POST',
            'class'  => 'databox filters',
            'extra'  => '',
        ];

        // Inputs.
        $inputs = [];

        $inputs[] = [
            'id'        => 'inp-id_np',
            'arguments' => [
                'name'   => 'id_np',
                'type'   => 'hidden',
                'value'  => $this->id_np,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Filter'),
            'id'        => 'add-modules-filter',
            'arguments' => [
                'name'        => 'add-modules-filter',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'value'       => '',
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Group'),
            'id'        => 'add-modules-group',
            'arguments' => [
                'name'        => 'add-modules-group',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'script'      => 'this.form.submit()',
                'fields'      => $groups_compound,
                'nothing'     => $groups_compound[0],
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Components'),
            'id'        => 'add-modules-components',
            'arguments' => [
                'name'        => 'add-modules-components',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'multiple'    => true,
                'fields'      => $components,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'label'      => __('Add components'),
                'name'       => 'add-modules-submit',
                'type'       => 'submit',
                'attributes' => 'class="sub wand"',
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
        /*
            $table = new StdClasS();

            $table->head = [];
            $table->data = [];
            $table->align = [];
            $table->width = '100%';
            $table->cellpadding = 0;
            $table->cellspacing = 0;
            $table->class = 'databox filters';

            $table->style[0] = 'font-weight: bold';

            // The form to submit when adding a list of components
            $filter = '<form name="filter_component" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&ncgroup='.$ncgroup.'&id_np='.$id_np.'#filter">';
            $filter .= html_print_input_text('ncfilter', $ncfilter, '', 50, 255, true);
            $filter .= '&nbsp;&nbsp;'.html_print_submit_button(__('Filter'), 'ncgbutton', false, 'class="sub search"', true);
            $filter .= '</form>';

            $group_filter = '<form name="filter_group" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'#filter">';
            $group_filter .= '<div style="width:540px"><a name="filter"></a>';
            $result = db_get_all_rows_in_table('tnetwork_component_group', 'name');
            if ($result === false) {
            $result = [];
            }

            // 2 arrays. 1 with the groups, 1 with the groups by parent
            $groups = [];
            $groups_compound = [];
            foreach ($result as $row) {
            $groups[$row['id_sg']] = $row['name'];
            }

            foreach ($result as $row) {
            $groups_compound[$row['id_sg']] = '';
            if ($row['parent'] > 1) {
                $groups_compound[$row['id_sg']] = $groups[$row['parent']].' / ';
            }

            $groups_compound[$row['id_sg']] .= $row['name'];
            }

            $group_filter .= html_print_select($groups_compound, 'ncgroup', $ncgroup, 'javascript:this.form.submit();', __('Group').' - '.__('All'), -1, true, false, true, '" style="width:350px');

            $group_filter .= '</div></form>';

            if ($ncgroup > 0) {
            $sql = sprintf(
                "
                SELECT id_nc, name, id_group
                FROM tnetwork_component
                WHERE id_group = %d AND name LIKE '%".$ncfilter."%'
                ORDER BY name",
                $ncgroup
            );
            } else {
            $sql = "
                SELECT id_nc, name, id_group
                FROM tnetwork_component
                WHERE name LIKE '%".$ncfilter."%'
                ORDER BY name";
            }

            $result = db_get_all_rows_sql($sql);
            $components = [];
            if ($result === false) {
            $result = [];
            }

            foreach ($result as $row) {
            $components[$row['id_nc']] = $row['name'];
            }

            $components_select = '<form name="add_module" method="post" action="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates_form&id_np='.$id_np.'&add_module=1">';
            $components_select .= html_print_select($components, 'components[]', $id_nc, '', '', -1, true, true, false, '" style="width:350px');

            $table->data[0][0] = __('Filter');
            $table->data[0][1] = $filter;
            $table->data[1][0] = __('Group');
            $table->data[1][1] = $group_filter;
            $table->data[2][0] = __('Components');
            $table->data[2][1] = $components_select;

            html_print_table($table);

            echo '<div style="width:'.$table->width.'; text-align:right">';
            html_print_submit_button(__('Add'), 'crtbutton', false, 'class="sub wand"');
        echo '</div></form>';*/
    }


    /**
     * General setter
     *
     * @return void
     */
    private function setNetworkProfile()
    {
        // Get t
        $profileInfo = db_get_row('tnetwork_profile', 'id_np', $this->id_np);
        $this->name = $profileInfo['name'];
        $this->description = $profileInfo['description'];

        $penInfo = db_get_all_rows_filter('tnetwork_profile_pen', ['id_np' => $this->id_np]);
        $penList = [];
        foreach ($penInfo as $pen) {
            $penList[] = $pen['pen'];
        }

        $this->pen = implode(',', $penList);
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
            $data[3] = '<a href="'.$this->baseUrl.'&submit_button=Delete&delete_profile='.$row['id_np'].'" '.'onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';
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
     */
    public function moduleTemplateForm()
    {
        $createNewBlock = ($this->id_np == 0) ? true : false;

        if ($createNewBlock) {
            // Assignation for submit button.
            $formButtonClass = 'sub wand';
            $formButtonValue = 'create';
            $formButtonLabel = __('Create');
        } else {
            // Assignation for submit button.
            $formButtonClass = 'sub upd';
            $formButtonValue = 'update';
            $formButtonLabel = __('Update');
            // Profile exists. Set the attributes with the info.
            $this->setNetworkProfile();
        }

        // Main form.
        $form = [
            'action' => $this->baseUrl,
            'id'     => 'module_block_form',
            'method' => 'POST',
            'class'  => 'databox filters',
            'extra'  => '',
        ];

        // Inputs.
        $inputs = [];
        // Inputs.
        $rawInputs = '';

        $inputs[] = [
            'id'        => 'inp-id_np',
            'arguments' => [
                'name'   => 'id_np',
                'type'   => 'hidden',
                'value'  => $this->id_np,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Name'),
            'id'        => 'inp-name',
            'arguments' => [
                'name'        => 'name',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'value'       => $this->name,
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
                'value'       => $this->description,
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
                'value'       => $this->pen,
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'name'       => 'submit_button',
                'label'      => $formButtonLabel,
                'type'       => 'submit',
                'value'      => $formButtonValue,
                'attributes' => 'class="'.$formButtonClass.'"',
                'return'     => true,
            ],
        ];

        ui_require_jquery_file('tag-editor');
        ui_require_css_file('jquery.tag-editor');

        $js = '$(\'#text-pen\').tagEditor();';

        if ($createNewBlock === false) {
            // Get the data.
            $sql = sprintf(
                'SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS `group`, ncg.name AS `group_name`
				FROM tnetwork_profile_component AS npc, tnetwork_component AS nc 
				INNER JOIN tnetwork_component_group AS ncg ON ncg.id_sg = nc.id_group
                WHERE npc.id_nc = nc.id_nc AND npc.id_np = %d',
                $this->id_np
            );
            $moduleBlocks = db_get_all_rows_sql($sql);

            if ($moduleBlocks) {
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
                            $data[3] = html_print_checkbox_switch_extended('module_check_'.$id_group.'_'.$module['component_id'], 1, 0, false, 'switchBlockControl(event)', '', true);

                            array_push($table->data, $data);
                        }

                        $content = html_print_table($table, true);

                        $rawInputs .= ui_toggle($content, $blockTitle, '', '', false, true);
                    }
                }
            } else {
                ui_print_info_message(__('No module blocks for this profile'));
            }
        }

        $this->printFormAsList(
            [
                'form'      => $form,
                'inputs'    => $inputs,
                'rawInputs' => $rawInputs,
                'js'        => $js,
                true
            ]
        );

        $javascript = "
            <script>
            function switchBlockControl(e){
                var switchId = e.target.id.split('_');
                var blockNumber = switchId[1];
                var switchNumber = switchId[2];
            
                $('[id*=checkbox-switch_'+blockNumber+']').each(function(){
                    console.log($(this).val());
                })
            
                console.log(blockNumber);
                console.log(switchNumber);
                
            }
            </script>
        ";

        echo $javascript;

        if ($createNewBlock === false) {
            $this->addingModulesForm();
        }

        $this->printGoBackButton($this->baseUrl);
    }


}
