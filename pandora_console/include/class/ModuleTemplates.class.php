<?php
/**
 * Module Template feature.
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
     * List of valid PENs
     *
     * @var string
     */
    private $validPen;

    /**
     * Complete list of PENes.
     *
     * @var array.
     */
    private $penRefs;

    /**
     * List of valid PENs
     *
     * @var string
     */
    private $action;


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
        if ($this->id_np > 0) {
            // Profile exists. Set the attributes with the info.
            $this->setNetworkProfile();
        } else {
            $this->name             = get_parameter('name', '');
            $this->description      = get_parameter('description', '');
            $this->pen              = get_parameter('pen', '');
        }

        $this->action           = get_parameter('action_button', '');
        $this->offset           = get_parameter('offset', 0);
        $this->ajaxController   = $ajax_controller;
        $this->ncGroup          = get_parameter('add-modules-group', '0');
        $this->ncFilter         = get_parameter('add-modules-filter', '');
        // Get all of PENs valid for autocomplete.
        $getPENs = db_get_all_rows_sql('SELECT pen,manufacturer FROM tpen');
        $outputPENs = [];

        $this->validPen = '';
        $this->penRefs = [];
        foreach ($getPENs as $pen) {
            $this->validPen .= ((int) $pen['pen']).',';
            $this->penRefs[] = [
                'label' => io_safe_output($pen['manufacturer']),
                'value' => $pen['pen'],
            ];
            // Reverse autocompletion.
            $this->penRefs[] = [
                'label' => $pen['pen'],
                'value' => $pen['pen'],
            ];
        }

        chop($this->validPen);

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
        ui_require_javascript_file('jquery.caret.min');

        // Breadcrums.
        $this->setBreadcrum([]);

        if ($this->id_np > 0) {
            // Add a breadcrumb with the current template.
            $urlToGo = $this->baseUrl.'&id_np='.$this->id_np;

            $this->prepareBreadcrum(
                [
                    ['label' => __('Configuration')],
                    ['label' => __('Templates')],
                    [
                        'link'     => $this->baseUrl,
                        'label'    => __('Module template management'),
                        'selected' => false,
                    ],
                    [
                        'link'     => $urlToGo,
                        'label'    => $this->name,
                        'selected' => true,
                    ],
                ],
                true
            );
        } else {
            $this->prepareBreadcrum(
                [
                    ['label' => __('Configuration')],
                    ['label' => __('Templates')],
                    [
                        'link'     => $this->baseUrl,
                        'label'    => __('Module template management'),
                        'selected' => true,
                    ],
                ],
                true
            );
        }

        // Prints the header.
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

        // Process the data if action is required
        if (!empty($this->action)) {
            $this->processData();
        }

        if ($this->id_np === -1) {
            // List all Module Blocks.
            $this->moduleTemplateList();
        } else {
            // Show form for create or update template.
            $this->moduleTemplateForm();
        }

        $this->loadJS();
    }


    /**
     * Minor function to dump json message as ajax response.
     *
     * @param string $type Type: result || error.
     * @param string $msg  Message.
     *
     * @return void
     */
    private function ajaxMsg($type, $msg)
    {
        if ($type == 'error') {
            echo json_encode(
                [
                    $type => ui_print_error_message(
                        __($msg),
                        '',
                        true
                    ),
                ]
            );
        } else {
            echo json_encode(
                [
                    $type => ui_print_success_message(
                        __($msg),
                        '',
                        true
                    ),
                ]
            );
        }

        exit;
    }


    /**
     * Save or Update the data received.
     *
     * @return void
     */
    public function processData()
    {
        // Only needed if process data.
        $modules_submit = get_parameter('add-modules-submit', '');
        // Success variable.
        $success     = false;
        // Evaluate the modules allowed.
        if (!empty($this->action)) {
            $numberComponent = [];
            foreach ($_POST as $k => $value) {
                if (strpos($k, 'module_check_') >= 0 && $value == 1) {
                    $tmpNumberComponent = explode('_', $k);
                    $numberComponent[] = $tmpNumberComponent[2];
                }
            }

            switch ($this->action) {
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

                    if ($success === true) {
                        $msg = __('Template %s successfully updated', $this->name);
                    } else {
                        $msg = __('Error updating template');
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

                    if ($success === true) {
                        $msg = __('Template %s successfully created', $this->name);
                    } else {
                        $msg = __('Error creating template');
                    }
                break;

                case 'Delete':
                    $success = db_process_sql_delete('tnetwork_profile', ['id_np' => $this->id_np]);

                    if ($success != false) {
                        $msg = __('Template %s successfully deleted', $this->name);
                    } else {
                        $msg = __('Error deleting %s template', $this->name);
                    }

                    // Reset id_np for show the templates list.
                    $this->id_np = -1;
                break;

                default:
                    // There is possible want do an action detailed.
                    $action_detailed = explode('_', $this->action);
                    // Action deletion.
                    if ($action_detailed[0] === 'del') {
                        // Block or Module is affected.
                        switch ($action_detailed[1]) {
                            case 'module':
                                $success = $this->deleteModule($action_detailed[2]);

                                if ($success != false) {
                                    $msg = __('Module successfully deleted');
                                } else {
                                    $msg = __('Error deleting module');
                                }
                            break;

                            case 'block':
                                $block = explode('-', $action_detailed[2]);
                                foreach ($block as $module) {
                                    $success = $this->deleteModule($module);
                                }

                                if ($success != false) {
                                    $msg = __('Block successfully deleted');
                                } else {
                                    $msg = __('Error deleting block');
                                }
                            break;

                            default:
                                // Do nothing.
                            break;
                        }
                    } else {
                        $msg = __('Something gone wrong. Please, try again');
                    }
                break;
            }

            if ($success === false) {
                ui_print_error_message($msg);
            } else {
                ui_print_success_message($msg);
            }
        } else if ($modules_submit != '') {
            $modulesToAdd = get_parameter('add-modules-components', '');
            $modulesToAddList = explode(',', $modulesToAdd);

            foreach ($modulesToAddList as $module) {
                db_process_sql_insert(
                    'tnetwork_profile_component',
                    [
                        'id_nc' => $module,
                        'id_np' => $this->id_np,
                    ]
                );
            }

            $this->ajaxMsg('result', __('Components added sucessfully'));
        }
    }


    /**
     * Delete of block the module desired
     *
     * @param integer $id_module Id of module that must delete.
     *
     * @return mixed Return false if something went wrong.
     */
    private function deleteModule($id_module)
    {
        $dbResult = db_process_sql_delete(
            'tnetwork_profile_component',
            [
                'id_np' => $this->id_np,
                'id_nc' => $id_module,
            ]
        );

        return $dbResult;
    }


    /**
     * Show the adding modules form
     *
     * @return void
     */
    public function addingModulesForm()
    {
        // Get the groups for select input.
        $result = db_get_all_rows_in_table('tnetwork_component_group', 'name');
        if ($result === false) {
            $result = [];
        }

        // 2 arrays. 1 with the groups, 1 with the groups by parent
        $groups = [];
        $groups_compound = [];
        // Default group filter.
        $groups_compound[0] = 'Group - All';
        foreach ($result as $row) {
            $groups[$row['id_sg']] = $row['name'];
        }

        foreach ($result as $row) {
            $groups_compound[$row['id_sg']] = '';
            if ($row['parent'] > 1) {
                $groups_compound[$row['id_sg']] = $groups[$row['parent']].' / ';
            }
        }

        // Get the components for show in a list for select.
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
                'value'  => $this->id_np,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'add-modules-components-values',
            'arguments' => [
                'name'   => 'add-modules-components-values',
                'type'   => 'hidden',
                'value'  => '',
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Filter'),
            'id'        => 'txt-add-modules-filter',
            'arguments' => [
                'name'        => 'add-modules-filter',
                'input_class' => 'flex-row',
                'type'        => 'text',
                'value'       => '',
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'label'      => __('Filter'),
                'name'       => 'add-modules-submit',
                'type'       => 'button',
                'script'     => 'this.form.submit()',
                'attributes' => 'class="sub search"',
                'return'     => true,
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
                'nothing'     => $groups_compound[$this->ncGroup],
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Components'),
            'id'        => 'slc-add-modules-components',
            'arguments' => [
                'name'        => 'add-modules-components',
                'input_class' => 'flex-row',
                'type'        => 'select',
                'multiple'    => true,
                'fields'      => $components,
                'return'      => true,
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
     * General setter
     *
     * @return void
     */
    private function setNetworkProfile()
    {
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
     * @return void
     */
    public function moduleTemplateList()
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
            $data[3] = '<a href="'.$this->baseUrl.'&action_button=Delete&id_np='.$row['id_np'].'" onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';
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
     * @return mixed
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
        }

        // Main form.
        $form = [
            'action' => $this->baseUrl,
            'id'     => 'module_template_form',
            'method' => 'POST',
            'class'  => 'databox filters',
            'extra'  => 'id="module_template_form"',
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
            'id'        => 'inp-valid-pen',
            'arguments' => [
                'name'   => 'valid-pen',
                'type'   => 'hidden',
                'value'  => $this->validPen,
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
                'name'       => 'action_button',
                'label'      => $formButtonLabel,
                'type'       => 'submit',
                'value'      => $formButtonValue,
                'attributes' => 'class="'.$formButtonClass.'"',
                'return'     => true,
            ],
        ];

        // Adding components button.
        $inputs[] = [
            'arguments' => [
                'name'       => 'add_components_button',
                'label'      => __('Add components'),
                'type'       => 'button',
                'attributes' => 'class="sub cog"',
                'script'     => 'showAddComponent();',
                'return'     => true,
            ],
        ];
        // Required for PEN field.
        ui_require_jquery_file('tag-editor');
        ui_require_css_file('jquery.tag-editor');

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

                if (count($blockTables) === 0) {
                    ui_print_info_message(__('No module blocks for this profile'));
                } else {
                    foreach ($blockTables as $id_group => $blockTable) {
                        // Data with all components.
                        $blockData = $blockTable['data'];
                        // Creation of list of all components.
                        $blockComponentList = '';
                        foreach ($blockData as $component) {
                            $blockComponentList .= $component['component_id'].'-';
                        }

                        $blockComponentList = chop($blockComponentList, '-');
                        // Title of Block.
                        $blockTitle = '<div style="padding-top: 8px;">';
                        $blockTitle .= $blockTable['name'];
                        $blockTitle .= '<div class="white_table_header_checkbox">';
                        $blockTitle .= html_print_input_image(
                            'del_block_'.$id_group.'_',
                            'images/cross.png',
                            1,
                            false,
                            true,
                            [
                                'title'   => __('Delete this block'),
                                'onclick' => 'if(confirm(\''.__('Do you want delete this block?').'\')){deleteModuleTemplate(\'block\',\''.$blockComponentList.'\')};return false;',
                            ]
                        );

                        $blockTitle .= '</div></div>';

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
                        $table->head[3] = '<span style="float:right;margin-right:1.2em;">'.__('Delete').'</span>';

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
                            $data[3] = html_print_input_image(
                                'del_module_'.$module['component_id'].'_',
                                'images/cross.png',
                                1,
                                '',
                                true,
                                [
                                    'title'   => __('Delete this module'),
                                    'onclick' => 'if(confirm(\''.__('Do you want delete this module?').'\')){deleteModuleTemplate(\'module\','.$module['component_id'].')};return false;',
                                ]
                            );

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
                true
            ]
        );

        if ($createNewBlock === false) {
            echo '<div style="display:none;" id="modal"></div>';
            echo '<div style="display:none;" id="msg"></div>';
        }

        $this->printGoBackButton($this->baseUrl);
    }


    /**
     * Loads JS and return code.
     *
     * @return string
     */
    public function loadJS()
    {
        $str = '';

        ob_start();
        ?>
        <script type="text/javascript">

        function deleteModuleTemplate(type, id){
                var input_hidden = '<input type="hidden" name="action_button" value="del_'+type+'_'+id+'"/>';
                $('#module_template_form').append(input_hidden);
                $('#module_template_form').submit();
        }

        function switchBlockControl(e) {
            var switchId = e.target.id.split("_");
            var blockNumber = switchId[2];
            var switchNumber = switchId[3];
            var totalCount = 0;
            var markedCount = 0;
        
            $("[id*=checkbox-module_check_" + blockNumber + "]").each(function() {
            if ($(this).prop("checked")) {
                markedCount++;
            }
            totalCount++;
            });
        
            if (totalCount == markedCount) {
            $("#checkbox-block_id_" + blockNumber).prop("checked", true);
            $("#checkbox-block_id_" + blockNumber)
                .parent()
                .removeClass("alpha50");
            } else if (markedCount == 0) {
            $("#checkbox-block_id_" + blockNumber).prop("checked", false);
            $("#checkbox-block_id_" + blockNumber)
                .parent()
                .removeClass("alpha50");
            } else {
            $("#checkbox-block_id_" + blockNumber).prop("checked", true);
            $("#checkbox-block_id_" + blockNumber)
                .parent()
                .addClass("alpha50");
            }
        }
        
        function showAddComponent() {
            var btn_ok_text = "<?php echo __('OK'); ?>";
            var btn_cancel_text = "<?php echo __('Cancel'); ?>";
            var title = "<?php echo __('Add components'); ?>";
        
            load_modal({
            target: $("#modal"),
            form: "add_module_form",
            url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
            ajax_callback: showMsg,
            modal: {
                title: title,
                ok: btn_ok_text,
                cancel: btn_cancel_text
            },
            extradata: [
                {
                name: "id_np",
                value: "<?php echo $this->id_np; ?>"
                }
            ],
            onshow: {
                page: "<?php echo $this->ajaxController; ?>",
                method: "addingModulesForm"
            },
            onsubmit: {
                page: "<?php echo $this->ajaxController; ?>",
                method: "processData"
            }
            });
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
                    if (!failed) {
                    $(".ui-dialog-content").dialog("close");
                    $(".info").hide();
                    location.reload();
                    } else {
                    $(this).dialog("close");
                    }
                }
                }
            ]
            });
        }
        
        $(document).ready(function() {
            var listValidPens = $("#hidden-valid-pen").val();
            try {
                listValidPens = listValidPens.split(',');
            } catch (e) {
                console.error(e);
                return;
            }

            //Adding tagEditor for PEN management.
            $("#text-pen").tagEditor({
            beforeTagSave: function(field, editor, tags, tag, val) {
                if (listValidPens.indexOf(val) == -1) {
                return false;
                }
            },
            autocomplete: {
                source: <?php echo json_encode($this->penRefs); ?>

            }
            });
            //Values for add.
            $("#add-modules-components").change(function() {
            var valores = $("#add-modules-components")
                .val()
                .join(",");
            $("#hidden-add-modules-components-values").val(valores);
            });
        });

    </script>

        <?php
        $str = ob_get_clean();
        echo $str;
        return $str;
    }


}
