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
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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
     * List of valid PENs
     *
     * @var string
     */
    private $validPen;

    /**
     * Complete list of PENs.
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
                AUDIT_LOG_ACL_VIOLATION,
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
        $this->action           = get_parameter('action', '');
        // Profile exists. Set the attributes with the info.
        if ($this->id_np > 0 || empty($this->action)) {
            $this->setNetworkProfile();
        }

        $this->offset           = (int) get_parameter('offset', 0);
        $this->ajaxController   = $ajax_controller;
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

        // Process the data if action is required.
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
        echo json_encode(
            [
                $type => __($msg),
            ]
        );

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
        $modulesToAdd = get_parameter('add-modules-components', '');
        // Evaluate the modules allowed.
        if (!empty($this->action)) {
            // Success variable.
            $success     = false;
            $this->name             = io_safe_input(strip_tags(io_safe_output((string) get_parameter('name'))));
            $this->description      = io_safe_input(strip_tags(io_safe_output((string) get_parameter('description'))));
            $this->pen              = get_parameter('pen', '');

            switch ($this->action) {
                case 'update':
                    if (empty($this->name)) {
                        $dbResult_tnp = false;
                    } else {
                        $dbResult_tnp = db_process_sql_update(
                            'tnetwork_profile',
                            [
                                'name'        => $this->name,
                                'description' => $this->description,
                            ],
                            ['id_np' => $this->id_np]
                        );
                    }

                    if ($dbResult_tnp === false) {
                        $success = false;
                    } else {
                        db_process_sql_delete('tnetwork_profile_pen', ['id_np' => $this->id_np]);
                        if (empty($this->pen)) {
                            $success = true;
                        } else {
                            $pensList = explode(',', $this->pen);
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
                        }
                    }

                    if ($success === true) {
                        $msg = __('Template %s successfully updated', $this->name);
                    } else {
                        $msg = __('Error updating template');
                    }
                break;

                case 'create':
                    if (empty($this->name)) {
                        $dbResult_tnp = false;
                    } else {
                        $dbResult_tnp = db_process_sql_insert(
                            'tnetwork_profile',
                            [
                                'name'        => $this->name,
                                'description' => $this->description,
                            ]
                        );
                    }

                    // The insert gone fine!
                    if ($dbResult_tnp != false) {
                        // Set the new id_np.
                        $this->id_np = $dbResult_tnp;
                        if (empty($this->pen)) {
                            $success = true;
                        } else {
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
                    }

                    if ($success === true) {
                        $msg = __('Template %s successfully created', $this->name);
                    } else {
                        $msg = __('Error creating template');
                    }
                break;

                case 'delete':
                    $success = db_process_sql_delete('tnetwork_profile', ['id_np' => $this->id_np]);

                    if ($success != false) {
                        $msg = __('Template %s successfully deleted', $this->name);
                    } else {
                        $msg = __('Error deleting %s template', $this->name);
                    }

                    // Reset id_np for show the templates list.
                    $this->id_np = -1;
                break;

                case 'export':
                    global $config;

                    enterprise_include_once('include/functions_reporting_csv.php');

                    $id_network_profile = safe_int($this->id_np);
                    if (empty($id_network_profile)) {
                        return false;
                    }

                    $filter['id_np'] = $id_network_profile;

                    $profile_info = @db_get_row_filter('tnetwork_profile', $filter, false);

                    if (empty($profile_info)) {
                        $success = false;
                        // ui_print_error_message(__('This template does not exist'));
                        return;
                    }

                    $sql = sprintf(
                        '
                        SELECT components.name, components.description, components.type, components.max, components.min, components.module_interval, 
                            components.tcp_port, components.tcp_send, components.tcp_rcv, components.snmp_community, components.snmp_oid, 
                            components.id_module_group, components.id_modulo, components.plugin_user, components.plugin_pass, components.plugin_parameter,
                            components.max_timeout, components.max_retries, components.history_data, components.min_warning, components.max_warning, components.str_warning, components.min_critical, 
                            components.max_critical, components.str_critical, components.min_ff_event, components.dynamic_interval, components.dynamic_max, components.dynamic_min, components.dynamic_two_tailed, comp_group.name AS group_name, components.critical_instructions, components.warning_instructions, components.unknown_instructions
                        FROM `tnetwork_component` AS components, tnetwork_profile_component AS tpc, tnetwork_component_group AS comp_group
                        WHERE tpc.id_nc = components.id_nc
                            AND components.id_group = comp_group.id_sg
                            AND tpc.id_np = %d',
                        $this->id_np
                    );

                    $components = db_get_all_rows_sql($sql);

                    $row_names = [];
                    $inv_names = [];
                    // Find the names of the rows that we are getting and throw away the duplicate numeric keys.
                    foreach ($components[0] as $row_name => $detail) {
                        if (is_numeric($row_name) === true) {
                            $inv_names[] = $row_name;
                        } else {
                            $row_names[] = $row_name;
                        }
                    }

                    $fileName = io_safe_output($profile_info['name']);
                    // Send headers to tell the browser we're sending a file.
                    header('Content-type: application/octet-stream');
                    header('Content-Disposition: attachment; filename='.preg_replace('/\s/', '_', $fileName).'.csv');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    setDownloadCookieToken();

                    // Clean up output buffering.
                    while (@ob_end_clean()) {
                    }

                    // Then print the first line (row names).
                    echo '"'.implode('"'.$config['csv_divider'].'"', $row_names).'"';
                    echo "\n";

                    // Then print the rest of the data. Encapsulate in quotes in case we have comma's in any of the descriptions
                    foreach ($components as $row) {
                        foreach ($inv_names as $bad_key) {
                            unset($row[$bad_key]);
                        }

                        if ($config['csv_decimal_separator'] !== '.') {
                            foreach ($row as $name => $data) {
                                if (is_numeric($data)) {
                                    // Get the number of decimals, if > 0, format dec comma.
                                    $decimals = strlen(substr(strrchr($data, '.'), 1));
                                    if ($decimals !== 0) {
                                        $row[$name] = csv_format_numeric((float) $data, $decimals, true);
                                    }
                                }
                            }
                        }

                        echo '"'.implode('"'.$config['csv_divider'].'"', $row).'"';
                        echo "\n";
                    }

                    // We're done here. The original page will still be there
                exit;

                break;

                default:
                    // There is possible want do an action detailed.
                    $action_detailed = explode('_', $this->action);
                    // Action deletion.
                    if ($action_detailed[0] === 'del') {
                        // Block or Module is affected.
                        switch ($action_detailed[1]) {
                            case 'module':
                                $success = db_process_sql_delete(
                                    'tnetwork_profile_component',
                                    'id_nc='.$action_detailed[2].' AND id_np='.$this->id_np
                                );

                                if ($success != false) {
                                    $msg = __('Module successfully deleted');
                                } else {
                                    $msg = __('Error deleting module');
                                }
                            break;

                            case 'block':
                                $success = db_process_sql_delete(
                                    'tnetwork_profile_component',
                                    'id_nc in ('.$action_detailed[2].') AND id_np='.$this->id_np
                                );

                                if ($success != false) {
                                    $msg = __('Block successfully deleted');
                                } else {
                                    $msg = __('Error deleting block');
                                }
                            break;

                            case 'template':
                                if ($action_detailed[2] === 'all') {
                                    $success = db_process_sql_delete(
                                        'tnetwork_profile',
                                        ['1' => 1]
                                    );

                                    if ($success != false) {
                                        $msg = __('All templates deleted');
                                    } else {
                                        $msg = __('Error deleting all templates');
                                    }
                                } else {
                                    $success = db_process_sql_delete(
                                        'tnetwork_profile',
                                        'id_np in ('.$action_detailed[2].')'
                                    );

                                    if ($success != false) {
                                        $msg = __('Selected templates deleted');
                                    } else {
                                        $msg = __('Error deleting selected templates');
                                    }
                                }

                                $this->id_np = -1;
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
        } else if ($modulesToAdd != '') {
            $modulesToAddList = explode(',', $modulesToAdd);

            $modulesAddedList = db_get_all_rows_in_table('tnetwork_profile_component');

            $modulesToAdd = [];

            foreach ($modulesToAddList as $module) {
                $is_added = false;
                foreach ($modulesAddedList as $item) {
                    if ($item['id_nc'] === $module
                        && $item['id_np'] === $this->id_np
                    ) {
                            $is_added = true;
                    }
                }

                if ($is_added === false) {
                    $name = io_safe_output(
                        db_get_row_filter(
                            'tnetwork_component',
                            ['id_nc' => $module],
                            'name'
                        )
                    );
                    $modulesToAdd[] = $name;
                    db_process_sql_insert(
                        'tnetwork_profile_component',
                        [
                            'id_nc' => $module,
                            'id_np' => $this->id_np,
                        ]
                    );
                } else {
                    $message = 'Some modules already exists<br>';
                }
            }

            if (empty($modulesToAdd)) {
                $this->ajaxMsg(
                    'error',
                    __('The modules is already added')
                );
                return false;
            }

            if ($message === '') {
                $message = 'Following modules will be added:';
            } else {
                $message .= 'Following modules will be added:';
            }

            $message .= '<ul>';
            foreach ($modulesToAdd as $key => $value) {
                $message .= '<li>'.$value['name'].'</li>';
            }

            $message .= '</ul>';

            $this->ajaxMsg(
                'result',
                __($message)
            );
        }
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

        foreach ($result as $row) {
            $groups[$row['id_sg']] = $row['name'];
        }

        foreach ($result as $row) {
            if ($row['parent'] > 1) {
                $groups_compound[$row['id_sg']] = $groups[$row['parent']].' / '.$row['name'];
            } else {
                $groups_compound[$row['id_sg']] = $row['name'];
            }
        }

        $result = db_get_all_rows_sql(
            'SELECT id_nc, name, id_group
            FROM tnetwork_component
            ORDER BY name'
        );

        $entireComponentsList = [];
        $components = [];
        if ($result === false) {
            $result = [];
        }

        foreach ($result as $row) {
            $strIdGroup = (string) $row['id_group'];
            if (!isset($entireComponentsList[$strIdGroup])) {
                $entireComponentsList[$strIdGroup] = $row['id_nc'];
            } else {
                $entireComponentsList[$strIdGroup] .= ','.$row['id_nc'];
            }

            $components[$row['id_nc']] = $row['name'];
        }

        $entireComponentsList = json_encode($entireComponentsList);

        // Main form.
        $form = [
            'action' => $this->baseUrl,
            'id'     => 'add_module_form',
            'method' => 'POST',
            'class'  => 'modal filter-list-adv',
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
            'id'        => 'txt-add-modules-filter',
            'arguments' => [
                'input_class' => 'flex-row',
                'name'        => 'filter',
                'type'        => 'text',
                'size'        => '40',
                'class'       => 'float-right',
                'onKeyDown'   => 'filterTextComponents(event);',
                'value'       => '',
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Group'),
            'id'        => 'add-modules-group',
            'arguments' => [
                'input_class' => 'flex-row',
                'type'        => 'select',
                'script'      => 'filterGroupComponents(event);',
                'class'       => 'float-right',
                'fields'      => $groups_compound,
                'nothing'     => 'Group - All',
                'return'      => true,
            ],
        ];

        $inputs[] = [
            'id'        => 'group-components',
            'arguments' => [
                'name'   => 'group-components',
                'type'   => 'hidden',
                'value'  => $entireComponentsList,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'label'     => __('Components'),
            'id'        => 'slc-add-modules-components',
            'arguments' => [
                'name'        => 'add-modules-components',
                'input_class' => 'flex-row',
                'style'       => 'width:100%;margin-top: 1em;',
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
        // Handle if list of PEN does not exist or is empty.
        if ($penInfo !== false) {
            foreach ($penInfo as $pen) {
                $penList[] = $pen['pen'];
            }
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
        $countModuleTemplates = db_get_value(
            'count(*)',
            'tnetwork_profile'
        );

        // Get all the data.
        $resultModuleTemplatesTable = db_get_all_rows_filter(
            'tnetwork_profile',
            [
                'order'  => 'name',
                'limit'  => $config['block_size'],
                'offset' => $this->offset,
            ]
        );

        $tablePagination = ui_pagination(
            $countModuleTemplates,
            $this->baseUrl,
            $this->offset,
            0,
            true,
            'offset',
            false
        );
        // Create the table with Module Block list.
        $table = new stdClass();
        $table->class = 'databox data ';
        $table->width = '75%';
        $table->styleTable = 'border: 1px solid #ddd;';
        $table->rowid = [];
        $table->data = [];

        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->width = '100%';
        $table->class = 'info_table border_bt';

        $table->head = [];
        $table->head[0] = html_print_checkbox('all_delete', 0, false, true, false);

        $table->head[1] = __('Name');
        $table->head[2] = __('Description');
        $table->head[3] = '<span class="mrgn_right_7p">'.__('Action').'</span>';
        $table->size = [];
        $table->size[0] = '20px';
        $table->size[2] = '65%';
        $table->size[3] = '15%';

        $table->align = [];
        $table->align[3] = 'left';

        $table->data = [];

        foreach ($resultModuleTemplatesTable as $row) {
            $data = [];
            $data[0] = html_print_checkbox_extended('delete_multiple[]', $row['id_np'], false, false, '', 'class="check_delete"', true);
            $data[1] = '<a href="'.$this->baseUrl.'&amp;id_np='.$row['id_np'].'">'.io_safe_output($row['name']).'</a>';
            $data[2] = ui_print_truncate_text(io_safe_output($row['description']), 'description', true, true, true, '[&hellip;]');
            $table->cellclass[][3] = 'table_action_buttons';
            $data[3] = html_print_input_image(
                'delete_profile',
                'images/delete.svg',
                $row['id_np'],
                '',
                true,
                [
                    'onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                    'class'   => 'invert_filter main_menu_icon',
                ]
            );
            $data[3] .= html_print_input_image(
                'export_profile',
                'images/file-csv.svg',
                $row['id_np'],
                '',
                true,
                [
                    'title' => 'Export tdaso CSV',
                    'class' => 'invert_filter main_menu_icon',
                ]
            );
            $data[3] = '<a href="'.$this->baseUrl.'&action=delete&id_np='.$row['id_np'].'" onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;">';
            $data[3] .= html_print_image(
                'images/delete.svg',
                true,
                [
                    'title' => __('Delete'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            );
            $data[3] .= '</a>';
            $data[3] .= '<a href="'.$this->baseUrl.'&action=export&id_np='.$row['id_np'].'" onclick="blockResubmit($(this))">';
            $data[3] .= html_print_image(
                'images/file-csv.svg',
                true,
                [
                    'title' => __('Export to CSV'),
                    'class' => 'invert_filter main_menu_icon',
                ]
            );
            $data[3] .= '</a>';

            array_push($table->data, $data);
        }

        html_print_table($table);

        $form = [
            'method' => 'POST',
            'action' => $this->baseUrl,
            'id'     => 'main_management_form',
            'class'  => 'flex_center',
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
                'attributes' => ['icon' => 'wand'],
                'return'     => true,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'label'      => __('Delete selected'),
                'name'       => 'erase',
                'type'       => 'button',
                'attributes' => [
                    'icon' => 'delete',
                    'mode' => 'secondary',
                ],
                'return'     => true,
            ],
        ];

        html_print_action_buttons(
            $this->printForm(
                [
                    'form'   => $form,
                    'inputs' => $inputs,
                ],
                true
            ),
            [
                'type'          => 'data_table',
                'class'         => 'fixed_action_buttons',
                'right_content' => $tablePagination,
            ]
        );

    }


    /**
     * Prints Form for template management
     *
     * @return mixed
     */
    public function moduleTemplateForm()
    {
        global $config;

        $createNewTemplate = ((int) $this->id_np === 0);

        if ($createNewTemplate === true) {
            // Assignation for submit button.
            $formButtonClass = 'wand';
            $formAction = 'create';
            $formButtonLabel = __('Create');
        } else {
            // Assignation for submit button.
            $formButtonClass = 'update';
            $formAction = 'update';
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
            'id'        => 'inp-action',
            'arguments' => [
                'name'   => 'action',
                'type'   => 'hidden',
                'value'  => $formAction,
                'return' => true,
            ],
        ];

        // Required for PEN field.
        ui_require_jquery_file('tag-editor');
        ui_require_css_file('jquery.tag-editor');

        $buttons = $this->printInput(
            [
                'name'       => 'action_button',
                'label'      => $formButtonLabel,
                'type'       => 'submit',
                'attributes' => [
                    'icon' => $formButtonClass,
                    'form' => 'module_template_form',
                ],
                'return'     => true,
                'width'      => 'initial',
            ]
        );

        if ($createNewTemplate === false) {
            $buttons .= $this->printInput(
                [
                    'name'       => 'add_components_button',
                    'label'      => __('Add components'),
                    'type'       => 'button',
                    'attributes' => [ 'icon' => 'cog' ],
                    'script'     => 'showAddComponent();',
                    'return'     => true,
                    'width'      => 'initial',
                ]
            );
        }

        if ($createNewTemplate === false) {
            // Get the data.
            $sql = sprintf(
                'SELECT npc.id_nc AS component_id, nc.name, nc.type, nc.description, nc.id_group AS `group`, nc.id_modulo AS `id_format`, ncg.name AS `group_name`
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
                        'id_format'    => $block['id_format'],
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
                            $blockComponentList .= $component['component_id'].',';
                        }

                        $blockComponentList = chop($blockComponentList, ',');
                        // Title of Block.
                        $blockTitle = '<div class="subsection_header_title">';
                        $blockTitle .= $blockTable['name'];
                        $blockTitle .= '<div class="white_table_header_checkbox">';
                        $blockTitle .= html_print_input_image(
                            'del_block_'.$id_group.'_',
                            'images/delete.svg',
                            1,
                            '',
                            true,
                            [
                                'title'   => __('Delete this block'),
                                'class'   => 'invert_filter main_menu_icon',
                                'onclick' => 'if(confirm(\''.__('Do you want delete this block?').'\')){deleteModuleTemplate(\'block\',\''.$blockComponentList.'\')};return false;',
                            ]
                        );

                        $blockTitle .= '</div></div>';

                        $table = new StdClasS();
                        $table->class = 'databox data border_bt';
                        $table->width = '75%';
                        $table->styleTable = 'margin: 0; border: 1px solid #ddd;';
                        $table->rowid = [];
                        $table->data = [];

                        $table->cellpadding = 0;
                        $table->cellspacing = 0;
                        $table->width = '100%';
                        $table->class = 'info_table border_bt';

                        $table->head = [];
                        $table->head[0] = __('Module Name');
                        $table->head[1] = '<span class="mrgn_lft_0px">'.__('Format').'</span>';
                        $table->head[2] = '<span class="center">'.__('Type').'</span>';
                        $table->head[3] = __('Description');
                        $table->head[4] = '<span class="float-right mrgn_right_1.2em">'.__('Delete').'</span>';

                        $table->size = [];
                        $table->size[0] = '15%';
                        $table->size[3] = '65%';
                        $table->size[4] = '15%';

                        $table->align = [];
                        $table->align[4] = 'right';

                        $table->style = [];
                        $table->style[4] = 'padding-right:2em';

                        $table->data = [];

                        foreach ($blockData as $module) {
                            $data[0] = '<a href="'.ui_get_full_url('index.php?sec=gmodules&sec2=godmode/modules/manage_network_components&id='.$module['component_id']).'">'.$module['name'].'</a>';
                            switch ($module['id_format']) {
                                case MODULE_NETWORK:
                                    $formatInfo = html_print_image(
                                        'images/network-server@os.svg',
                                        true,
                                        [
                                            'title' => __('Network module'),
                                            'class' => 'invert_filter main_menu_icon',
                                        ]
                                    );
                                break;

                                case MODULE_WMI:
                                    $formatInfo = html_print_image(
                                        'images/WMI@svg.svg',
                                        true,
                                        [
                                            'title' => __('WMI module'),
                                            'class' => 'invert_filter main_menu_icon',
                                        ]
                                    );
                                break;

                                case MODULE_PLUGIN:
                                    $formatInfo = html_print_image(
                                        'images/plugins@svg.svg',
                                        true,
                                        [
                                            'title' => __('Plug-in module'),
                                            'class' => 'invert_filter main_menu_icon',
                                        ]
                                    );
                                break;

                                default:
                                    $formatInfo = $module['id_format'];
                                break;
                            }

                            $data[1] = html_print_div(
                                [
                                    'style'   => 'margin: 0 auto;width: 50%;',
                                    'content' => $formatInfo,
                                ],
                                true
                            );
                            $data[2] = ui_print_moduletype_icon($module['type'], true);
                            $data[3] = mb_strimwidth(io_safe_output($module['description']), 0, 150, '...');
                            $data[4] = html_print_input_image(
                                'del_module_'.$module['component_id'].'_',
                                'images/delete.svg',
                                1,
                                '',
                                true,
                                [
                                    'title'   => __('Delete this module'),
                                    'class'   => 'invert_filter main_menu_icon',
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

        if ($createNewTemplate === false) {
            echo '<div class="invisible" id="modal"></div>';
            echo '<div class="invisible" id="msg"></div>';
        }

        $buttons .= $this->printGoBackButton($this->baseUrl, true);

        html_print_action_buttons($buttons);
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

        /**
        * Function for action of delete modules or blocks in template form
        */
        function deleteModuleTemplate(type, id){
            var input_hidden = '<input type="hidden" name="action" value="del_'+type+'_'+id+'"/>';
            $('#module_template_form').append(input_hidden);
            $('#module_template_form').submit();
        }
       
       /**
        * Filter with text the components form
        */
        function filterTextComponents(e){
            var text_search = e.target.value;
            text_search = text_search.toLowerCase();
            $('#add-modules-components').children().each(function(){
                var name = $(this).text().toLowerCase();
                if (name.indexOf(text_search) === -1) {
                    $(this).css('display','none');
                } else {
                    $(this).css('display','block');
                }
            });
        }

        /**
         * Filter with group the components form
         */
        function filterGroupComponents(e){
            var selectedGroup = e.target.value;
            var entireList = JSON.parse($('#hidden-group-components').val());
            if(typeof entireList[selectedGroup] !== 'undefined'){
                var componentsToShow = entireList[selectedGroup].split(",");
            } else {
                var componentsToShow = [];
            }
            $('#add-modules-components').children().each(function(){
                var id = $(this).val();
                if (typeof componentsToShow === 'undefined' && selectedGroup != '0') {
                    $(this).css('display','none');
                } else if (selectedGroup == '0' || componentsToShow.includes(id)) {
                    $(this).css('display','block');
                } else {
                    $(this).css('display','none');
                }
            });
        }

        /**
        * Show the modal with list of entire components
        */
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
                    var id_np = <?php echo $this->id_np; ?>;
                    window.location = window.location.href+'&id_np='+id_np;
                    } else {
                    $(this).dialog("close");
                    }
                }
                }
            ]
            });
        }
        
        $(document).ready(function() {
            //Main module template form deleting selected items
            $('#button-erase').click(function(){
                var message = '';
                var templatesList = '';
                if($('#checkbox-all_delete').prop('checked') == true){
                    message = "<?php echo __('Do you want delete all templates?'); ?>";
                    templatesList = 'all';
                } else {
                    message = "<?php echo __('Do you want delete the selected templates?'); ?>";
                    $("[id*=checkbox-delete_multiple]").each(function(){
                        if ($(this).prop('checked') == true) {
                            templatesList = templatesList + $(this).val() + ',';
                        }
                    });
                    // Clean the last comma
                    templatesList = templatesList.slice(0, -1);
                }

                if (confirm(message)) {
                    let hidden = '<input type="hidden" name="action" value="del_template_'+templatesList+'">';
                    $('#main_management_form').append(hidden);
                    $('#main_management_form').submit();
                }
            });

            var listValidPens = $("#hidden-valid-pen").val();
            try {
                if(listValidPens != undefined) {
                    listValidPens = listValidPens.split(',');
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
                }
            } catch (e) {
                console.error(e);
                return;
            }

            //Values for add.
            $("#add-modules-components").change(function() {
            var valores = $("#add-modules-components")
                .val()
                .join(",");
            });
        });

    </script>

        <?php
        $str = ob_get_clean();
        echo $str;
        return $str;
    }


}
