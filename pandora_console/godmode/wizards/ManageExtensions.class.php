<?php
/**
 * Manage Extensions wizard for Pandora FMS Discovery
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage ManageExtensions
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

use PandoraFMS\Tools\Files;
require_once $config['homedir'].'/include/class/ExtensionsDiscovery.class.php';

/**
 * Manage interface for upload new extensions in discovery.
 */
class ManageExtensions extends HTML
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'getExtensionsInstalled',
        'validateIniName',
        'loadMigrateModal',
        'migrateApp',
    ];

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Icon of section
     *
     * @var string
     */
    public $icon = '/images/wizard/Configurar_app@svg.svg';

    /**
     * Name of section
     *
     * @var string
     */
    public $label = 'Manage disco packages';

    /**
     * Url of section
     *
     * @var string
     */
    public $url;

    /**
     * Path of the installation extension.
     *
     * @var string
     */
    public $path = 'attachment/discovery';

    /**
     * Ini file from extension.
     *
     * @var array
     */
    public $iniFile;

    /**
     * Default logo
     *
     * @var string
     */
    public $defaultLogo = '/images/wizard/app_generico.svg';


    /**
     * Constructor
     */
    public function __construct()
    {
        global $config;
        $this->ajaxController = $config['homedir'].'/include/ajax/manage_extensions.ajax';
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=magextensions'
        );
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod($method)
    {
        // Check access.
        check_login();

        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Implements load method.
     *
     * @return mixed Skeleton for button.
     */
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => __($this->label),
            'url'   => $this->url,

        ];

    }


    /**
     * Generates a JSON error.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function errorAjax(string $msg)
    {
        echo json_encode(
            ['error' => $msg]
        );
    }


    /**
     * Implements run method.
     *
     * @return void
     */
    public function run()
    {
        global $config;
        // Load styles.
        parent::run();

        $uploadDisco = get_parameter('upload_disco', '');
        $action = get_parameter('action', '');
        $shortName = get_parameter('short_name', '');

        if (empty($uploadDisco) === false) {
            if ($_FILES['file']['error'] == 0) {
                $result = $this->uploadExtension($_FILES['file']);
                if ($result === true) {
                    ui_print_success_message(
                        __('Uploaded extension')
                    );
                } else {
                    if (is_string($result)) {
                        echo $this->error($result);
                    } else {
                        echo $this->error(__('Failed to upload extension'));
                    }
                }
            } else {
                echo $this->error(__('Failed to upload extension'));
            }
        }

        if (empty($action) === false && empty($shortName) === false) {
            switch ($action) {
                case 'delete':
                    $result = $this->uninstallExtension($shortName);
                    if ($result === true) {
                        ui_print_success_message(
                            __('Deleted extension')
                        );
                    } else {
                        echo $this->error(__('Fail delete extension'));
                    }

                case 'sync_server':
                    $syncAction = get_parameter('sync_action', '');
                    if ($syncAction === 'refresh') {
                        $installationFolder = $config['homedir'].'/'.$this->path.'/'.$shortName;
                        $result = $this->copyExtensionToServer($installationFolder, $shortName);
                        if ($result === true) {
                            ui_print_success_message(
                                __('Extension folder created successfully')
                            );
                        } else {
                            echo $this->error(__('Fail created extension folder'));
                        }
                    }
                break;

                default:
                    // Nothing.
                break;
            }
        }

        // Check config migrated apps and create it if neccesary.
        $migratedAppsJson = db_get_value('value', 'tconfig', 'token', 'migrated_discovery_apps');
        if ($migratedAppsJson === false || empty($migratedAppsJson) === true) {
              // If does't exists it means is not migrated yet, insert it.
            $migratedApps = [
                'pandorafms.vmware'    => 0,
                'pandorafms.mysql'     => 0,
                'pandorafms.mssql'     => 0,
                'pandorafms.oracle'    => 0,
                'pandorafms.db2'       => 0,
                'pandorafms.sap.deset' => 0,
                'pandorafms.gcp.ce'    => 0,
                'pandorafms.aws.ec2'   => 0,
                'pandorafms.aws.s3'    => 0,
                'pandorafms.aws.rds'   => 0,
                'pandorafms.azure.mc'  => 0,
            ];

            $migratedAppsJson = json_encode($migratedApps);

            if (json_last_error() === JSON_ERROR_NONE) {
                config_create_value(
                    'migrated_discovery_apps',
                    io_safe_input($migratedAppsJson)
                );
            }
        }

        $this->prepareBreadcrum(
            [
                [
                    'link'  => ui_get_full_url(
                        'index.php?sec=gservers&sec2=godmode/servers/discovery'
                    ),
                    'label' => __('Discovery'),
                ],
                [
                    'link'     => '',
                    'label'    => _('Manage disco packages'),
                    'selected' => 1,
                ],
            ]
        );

        // Header.
        ui_print_page_header(
            __('Manage disco packages'),
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

        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'databox filters';
        $table->size = [];
        $table->size[0] = '80%';
        $table->align[3] = 'right';
        $table->data = [];
        $table->data[0][0] = html_print_label_input_block(
            __('Load DISCO'),
            html_print_div(
                [
                    'id'      => 'upload_file',
                    'content' => html_print_input_file(
                        'file',
                        true,
                        ['style' => 'width:100%']
                    ),
                    'class'   => 'mrgn_top_15px',
                ],
                true
            )
        );
        $table->data[0][3] = html_print_submit_button(
            __('Upload DISCO'),
            'upload_button',
            false,
            [
                'class' => 'sub ok float-right',
                'icon'  => 'next',
            ],
            true
        );

        echo '<form id="uploadExtension" enctype="multipart/form-data" action="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=magextensions" method="POST">';
        html_print_input_hidden('upload_disco', 1);
        html_print_table($table);
         // Auxiliar div ant string for migrate modal.
        $modal = '<div id="migrate_modal" class="invisible"></div>';
        $modal .= '<div class="invisible" id="msg"></div>';

        echo $modal;

        echo '<div class="action-buttons w700px">';

        echo '</div>';
        echo '</form>';

        echo '<script type="text/javascript">
                var page = "'.$this->ajaxController.'";
                var textsToTranslate = {
                    "Warning": "'.__('Warning').'",
                    "Confirm": "'.__('Confirm').'",
                    "Cancel": "'.__('Cancel').'",
                    "Error": "'.__('Error').'",
                    "Ok": "'.__('Ok').'",
                    "Failed to upload extension": "'.__('Failed to upload extension').'",
                    "Migrate": "'.__('Migrate').'",
                    "migrationSuccess": "'.__('Migration Suceeded').'",
                };
                var url = "'.ui_get_full_url('ajax.php', false, false, false).'";
            </script>';
        ui_require_javascript_file('manage_extensions');
        try {
            $columns = [
                'name',
                'short_name',
                'section',
                'description',
                'version',
                [
                    'text'  => 'actions',
                    'class' => 'flex flex-items-center',
                ],
            ];

            $columnNames = [
                __('Name'),
                __('Short name'),
                __('Section'),
                __('Description'),
                __('Version'),
                __('Actions'),
            ];

            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => 'list_extensions',
                    'class'               => 'info_table',
                    'style'               => 'width: 99%',
                    'dom_elements'        => 'plfti',
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
                    'columns'             => $columns,
                    'column_names'        => $columnNames,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => ['method' => 'getExtensionsInstalled'],
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'name',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                ]
            );
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }


    /**
     * Upload extension to server.
     *
     * @param array $disco File disco tu upload.
     *
     * @return boolean $result Of operation, true if is ok.
     */
    private function uploadExtension($disco)
    {
        global $config;
        if (substr($disco['name'], -6) !== '.disco') {
            return false;
        }

        $nameFile = str_replace('.disco', '.zip', $disco['name']);
        $nameTempDir = $config['attachment_store'].'/downloads/';
        if (file_exists($nameTempDir) === false) {
            mkdir($nameTempDir);
        }

        $tmpPath = Files::tempdirnam(
            $nameTempDir,
            'extensions_uploaded_'
        );
        $result = move_uploaded_file($disco['tmp_name'], $tmpPath.'/'.$nameFile);
        if ($result === true) {
            $unzip = $this->unZip($tmpPath.'/'.$nameFile, $tmpPath);
            if ($unzip === true) {
                unlink($tmpPath.'/'.$nameFile);
                db_process_sql_begin();
                $this->iniFile = parse_ini_file($tmpPath.'/discovery_definition.ini', true, INI_SCANNER_TYPED);
                if ($this->iniFile === false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return __('Failed to upload extension: Error while parsing dicovery_definition.ini');
                }

                $error = ExtensionsDiscovery::validateIni($this->iniFile);
                if ($error !== false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return $error;
                }

                $id = $this->installExtension();
                if ($id === false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return false;
                }

                $result = $this->autoLoadConfigExec($id);
                if ($result === false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return false;
                }

                $result = $this->autoUpdateDefaultMacros($id);
                if ($result === false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return false;
                }

                $nameFolder = $this->iniFile['discovery_extension_definition']['short_name'];
                $installationFolder = $config['homedir'].'/'.$this->path.'/'.$nameFolder;
                if (file_exists($installationFolder) === false) {
                    mkdir($installationFolder, 0777, true);
                } else {
                    Files::rmrf($installationFolder, true);
                }

                $result = Files::move($tmpPath, $installationFolder, true);
                if ($result === false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return false;
                }

                $this->setPermissionfiles($installationFolder, $this->iniFile['discovery_extension_definition']['execution_file']);
                $this->setPermissionfiles(
                    $installationFolder,
                    [
                        $this->iniFile['discovery_extension_definition']['passencrypt_script'],
                        $this->iniFile['discovery_extension_definition']['passdecrypt_script'],
                    ]
                );

                $result = $this->copyExtensionToServer($installationFolder, $nameFolder);
                if ($result === false) {
                    db_process_sql_rollback();
                    Files::rmrf($tmpPath);
                    return false;
                }

                Files::rmrf($tmpPath);
                db_process_sql_commit();
                return true;
            }
        } else {
            Files::rmrf($tmpPath);
            return false;
        }
    }


    /**
     * Copy the extension folder into remote path server.
     *
     * @param string $path       Path extension folder.
     * @param string $nameFolder Name of extension folder.
     *
     * @return boolean Result of operation.
     */
    public function copyExtensionToServer($path, $nameFolder)
    {
        global $config;
        $filesToExclude = [
            'discovery_definition.ini',
            'logo.png',
        ];
        $serverPath = $config['remote_config'].'/discovery/'.$nameFolder;
        if (file_exists($serverPath) === false) {
            mkdir($serverPath, 0777, true);
        } else {
            Files::rmrf($serverPath, true);
        }

        $result = $this->copyFolder($path, $serverPath, $filesToExclude);
        $this->setPermissionfiles($serverPath, $this->iniFile['discovery_extension_definition']['execution_file']);

        return $result;
    }


    /**
     * Copy from $source path to $destination
     *
     * @param string $source      Initial folder path.
     * @param string $destination Destination folder path.
     * @param array  $exclude     Files to exlcude in copy.
     *
     * @return boolean Result of operation.
     */
    public function copyFolder($source, $destination, $exclude=[])
    {
        if (file_exists($destination) === false) {
            mkdir($destination, 0777, true);
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                if (is_dir($source.'/'.$file)) {
                    $result = $this->copyFolder($source.'/'.$file, $destination.'/'.$file);
                    if ($result === false) {
                        return false;
                    }
                } else {
                    if (in_array($file, $exclude) === false) {
                        $result = copy($source.'/'.$file, $destination.'/'.$file);
                        if ($result === false) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }


    /**
     * Delete extension from database and delete folder
     *
     * @param integer $shortName Short name app for delete.
     *
     * @return boolean Result of operation.
     */
    private function uninstallExtension($shortName)
    {
        global $config;

        $result = db_process_sql_delete(
            'tdiscovery_apps',
            ['short_name' => $shortName]
        );

        if ($result !== false) {
            Files::rmrf($config['homedir'].'/'.$this->path.'/'.$shortName);
            Files::rmrf($config['remote_config'].'/discovery/'.$shortName);
            return true;
        } else {
            return false;
        }
    }


    /**
     * Load the basic information of the app into database.
     *
     * @return boolean Result of query.
     */
    private function installExtension()
    {
        $exist = db_get_row_filter(
            'tdiscovery_apps',
            [
                'short_name' => $this->iniFile['discovery_extension_definition']['short_name'],
            ]
        );
        $version = $this->iniFile['discovery_extension_definition']['version'];
        if ($version === null) {
            $version = '';
        }

        $description = $this->iniFile['discovery_extension_definition']['description'];
        if ($description === null) {
            $description = '';
        }

        if ($exist === false) {
            return db_process_sql_insert(
                'tdiscovery_apps',
                [
                    'short_name'  => $this->iniFile['discovery_extension_definition']['short_name'],
                    'name'        => io_safe_input($this->iniFile['discovery_extension_definition']['name']),
                    'description' => io_safe_input($description),
                    'section'     => $this->iniFile['discovery_extension_definition']['section'],
                    'version'     => $version,
                ]
            );
        } else {
            $result = db_process_sql_update(
                'tdiscovery_apps',
                [
                    'name'        => io_safe_input($this->iniFile['discovery_extension_definition']['name']),
                    'description' => io_safe_input($description),
                    'section'     => $this->iniFile['discovery_extension_definition']['section'],
                    'version'     => $version,
                ],
                [
                    'short_name' => $this->iniFile['discovery_extension_definition']['short_name'],
                ]
            );

            if ($result !== false) {
                return $exist['id_app'];
            }
        }
    }


    /**
     * Return all extension installed by ajax.
     *
     * @return void
     */
    public function getExtensionsInstalled()
    {
        global $config;

        $data = [];
        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $orderDatatable = get_datatable_order(true);
        $pagination = '';
        $order = '';

        try {
            ob_start();

            if (isset($orderDatatable)) {
                $order = sprintf(
                    ' ORDER BY %s %s',
                    $orderDatatable['field'],
                    $orderDatatable['direction']
                );
            }

            if (isset($length) && $length > 0
                && isset($start) && $start >= 0
            ) {
                $pagination = sprintf(
                    ' LIMIT %d OFFSET %d ',
                    $length,
                    $start
                );
            }

            $sql = sprintf(
                'SELECT short_name, name, section, description, version
                FROM tdiscovery_apps
                %s %s',
                $order,
                $pagination
            );

            $data = db_get_all_rows_sql($sql);

            $sqlCount = sprintf(
                'SELECT short_name, name, section, description, version
                FROM tdiscovery_apps
                %s',
                $order,
            );

            $appsMetadata = self::loadDiscoveryAppsMetadata();
            $flattenMetadata = array_merge(...array_values($appsMetadata));

            $count = db_get_num_rows($sqlCount);

            foreach ($data as $key => $row) {
                $logo = $this->path.'/'.$row['short_name'].'/logo.png';
                if (file_exists($logo) === false) {
                    $logo = $this->defaultLogo;
                }

                $metadataImage = $flattenMetadata[$row['short_name']]['image'];

                if (isset($metadataImage) === true
                    && file_exists($config['homedir'].'/images/discovery/'.$metadataImage) === true
                    && file_exists($this->path.'/'.$row['short_name'].'/logo.png') === false
                ) {
                    $logo = '/images/discovery/'.$metadataImage;
                }

                $logo = html_print_image($logo, true, ['style' => 'max-width: 30px; margin-right: 15px;']);
                $data[$key]['name'] = $logo.io_safe_output($row['name']);
                $data[$key]['short_name'] = $row['short_name'];
                $data[$key]['description'] = io_safe_output($row['description']);
                $data[$key]['version'] = $row['version'];
                $data[$key]['actions'] = '<form name="grupo" method="post" class="rowPair table_action_buttons" action="'.$this->url.'&action=delete">';
                $data[$key]['actions'] .= html_print_input_image(
                    'button_delete',
                    'images/delete.svg',
                    '',
                    '',
                    true,
                    [
                        'onclick' => 'if (!confirm(\''.__('Deleting this application will also delete all the discovery tasks using it. Do you want to delete it?').'\')) return false;',
                        'class'   => 'main_menu_icon invert_filter action_button_hidden',
                        'title'   => 'Delete',
                    ]
                );
                $data[$key]['actions'] .= html_print_input_hidden('short_name', $row['short_name'], true);
                $data[$key]['actions'] .= '</form>';

                if ($this->checkFolderConsole($row['short_name']) === true) {
                    $data[$key]['actions'] .= '<form name="grupo" method="post" class="rowPair table_action_buttons" action="'.$this->url.'&action=sync_server">';
                    $data[$key]['actions'] .= html_print_input_image(
                        'button_refresh',
                        'images/refresh@svg.svg',
                        '',
                        '',
                        true,
                        [
                            'onclick' => 'if (!confirm(\''.__('Are you sure you want to reapply?').'\')) return false;',
                            'class'   => 'main_menu_icon invert_filter action_button_hidden',
                            'title'   => 'Refresh',
                        ]
                    );
                    $data[$key]['actions'] .= html_print_input_hidden('sync_action', 'refresh', true);
                    $data[$key]['actions'] .= html_print_input_hidden('short_name', $row['short_name'], true);
                    $data[$key]['actions'] .= '</form>';
                } else {
                    $data[$key]['actions'] .= html_print_image(
                        'images/error_red.png',
                        true,
                        [
                            'title' => __('The extension directory or .ini does not exist in console.'),
                            'alt'   => __('The extension directory or .ini does not exist in console.'),
                            'class' => 'main_menu_icon invert_filter',
                        ],
                    );
                }

                $migrationHash = $this->canMigrate($row['short_name']);
                if ($migrationHash !== false && empty($migrationHash) !== true) {
                    // Migrate button.
                    $data[$key]['actions'] .= html_print_input_image(
                        'button_migrate-'.$row['short_name'],
                        'images/reset.png',
                        '',
                        '',
                        true,
                        [
                            'onclick' => 'show_migration_form(\''.$row['short_name'].'\',\''.$migrationHash.'\')',
                            'title'   => __('Migrate old discovery tasks.'),
                            'alt'     => __('Migrate old discovery tasks.'),
                            'class'   => 'main_menu_icon invert_filter',
                        ]
                    );
                }
            }

            if (empty($data) === true) {
                $total = 0;
                $data = [];
            } else {
                $total = $count;
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

        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $response;
        } else {
            echo json_encode(
                [
                    'success' => false,
                    'error'   => $response,
                ]
            );
        }

        exit;
    }


    /**
     * Insert new the default values for extension.
     *
     * @param integer $id Id of extension.
     *
     * @return boolean Result of query.
     */
    private function autoUpdateDefaultMacros($id)
    {
        $defaultValues = $this->iniFile['discovery_extension_definition']['default_value'];

        foreach ($defaultValues as $macro => $value) {
            $sql = 'INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
                    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
                    SELECT `id_rt`, "'.$macro.'", "custom", "'.(string) io_safe_input($value).'", "0"
                    FROM `trecon_task`
                    WHERE `id_app` = "'.$id.'";';
            $result = db_process_sql($sql);
            if ($result === false) {
                return false;
            }
        }

        $tempFiles = $this->iniFile['tempfile_confs']['file'];
        foreach ($tempFiles as $macro => $value) {
            $sql = 'UPDATE `tdiscovery_apps_tasks_macros`
                    SET `value` = "'.(string) io_safe_input($value).'" WHERE `id_task`
                    IN (SELECT `id_rt` FROM `trecon_task` WHERE `id_app` = "'.$id.'") AND `macro` = "'.$macro.'"';
            $result = db_process_sql($sql);
            if ($result === false) {
                return false;
            }

            $sql = 'INSERT IGNORE INTO `tdiscovery_apps_tasks_macros`
                    (`id_task`, `macro`, `type`, `value`, `temp_conf`)
                    SELECT `id_rt`, "'.$macro.'", "custom", "'.(string) io_safe_input($value).'", "1"
                    FROM `trecon_task`
                    WHERE `id_app` = "'.$id.'";';
            $result = db_process_sql($sql);
            if ($result === false) {
                return false;
            }
        }

        return true;
    }


    /**
     * Load the exec files in database
     *
     * @param integer $id Id of extension.
     *
     * @return boolean Result of query.
     */
    private function autoLoadConfigExec($id)
    {
        $executionFiles = $this->iniFile['discovery_extension_definition']['execution_file'];

        foreach ($executionFiles as $key => $value) {
            $exist = db_get_row_filter(
                'tdiscovery_apps_scripts',
                [
                    'id_app' => $id,
                    'macro'  => $key,
                ]
            );
            if ($exist === false) {
                $result = db_process_sql_insert(
                    'tdiscovery_apps_scripts',
                    [
                        'id_app' => $id,
                        'macro'  => $key,
                        'value'  => io_safe_input($value),
                    ]
                );
                if ($result === false) {
                    return false;
                }
            } else {
                $result = db_process_sql_update(
                    'tdiscovery_apps_scripts',
                    ['value' => io_safe_input($value)],
                    [
                        'id_app' => $id,
                        'macro'  => $key,
                    ]
                );
                if ($result === false) {
                    return false;
                }
            }
        }

        $execCommands = $this->iniFile['discovery_extension_definition']['exec'];
        $result = db_process_sql_delete(
            'tdiscovery_apps_executions',
            ['id_app' => $id]
        );
        if ($result === false) {
            return false;
        }

        foreach ($execCommands as $key => $value) {
            $result = db_process_sql_insert(
                'tdiscovery_apps_executions',
                [
                    'id_app'    => $id,
                    'execution' => io_safe_input($value),
                ]
            );
            if ($result === false) {
                return false;
            }
        }

        return true;
    }


    /**
     * Check if exist folder extension in console.
     *
     * @param string $shortName Name of folder.
     *
     * @return boolean Return true if exist folder
     */
    private function checkFolderConsole($shortName)
    {
        global $config;

        $folderPath = $config['homedir'].'/'.$this->path.'/'.$shortName;
        $iniPath = $config['homedir'].'/'.$this->path.'/'.$shortName.'/discovery_definition.ini';
        if (file_exists($folderPath) === false || file_exists($iniPath) === false) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * Validate the ini name by ajax.
     *
     * @return void
     */
    public function validateIniName()
    {
        global $config;
        $uploadDisco = get_parameter('upload_disco', '');
        if (empty($uploadDisco) === false) {
            if ($_FILES['file']['error'] == 0) {
                $disco = $_FILES['file'];
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to upload extension']);
                return;
            }
        }

        if (substr($disco['name'], -6) !== '.disco') {
            echo json_encode(['success' => false, 'message' => 'Failed to upload extension']);
            return;
        }

        $nameFile = str_replace('.disco', '.zip', $disco['name']);
        $nameTempDir = $config['attachment_store'].'/downloads/';
        if (file_exists($nameTempDir) === false) {
            mkdir($nameTempDir);
        }

        $tmpPath = Files::tempdirnam(
            $nameTempDir,
            'extensions_uploaded_'
        );
        $result = move_uploaded_file($disco['tmp_name'], $tmpPath.'/'.$nameFile);
        if ($result === true) {
            $unzip = $this->unZip($tmpPath.'/'.$nameFile, $tmpPath, 'discovery_definition.ini');
            if ($unzip === true) {
                unlink($tmpPath.'/'.$nameFile);
                $this->iniFile = parse_ini_file($tmpPath.'/discovery_definition.ini', true, INI_SCANNER_TYPED);
                if ($this->iniFile === false) {
                    Files::rmrf($tmpPath);
                    echo json_encode(['success' => false, 'message' => __('Failed to upload extension: Error while parsing dicovery_definition.ini')]);
                    return;
                }

                $message = false;
                $shortName = $this->iniFile['discovery_extension_definition']['short_name'];
                if (strpos($shortName, 'pandorafms.') === 0) {
                    $message = __('The \'short_name\' starting with \'pandorafms.\' is reserved for Pandora FMS applications. If this is not an official Pandora FMS application, consider changing the \'short_name\'. Do you want to continue?');
                }

                $exist = db_get_row_filter(
                    'tdiscovery_apps',
                    ['short_name' => $shortName]
                );

                if ($exist !== false) {
                    $message = __('There is another application with the same \'short_name\': \'%s\'. Do you want to overwrite the application and all of its contents?', $shortName);
                }

                if ($message !== false) {
                    echo json_encode(
                        [
                            'success' => true,
                            'warning' => true,
                            'message' => $message,
                        ]
                    );
                } else {
                    echo json_encode(['success' => true]);
                }

                Files::rmrf($tmpPath);
                return;
            }
        } else {
            Files::rmrf($tmpPath);
            echo json_encode(['success' => false, 'message' => __('Failed to upload extension')]);
            return;
        }
    }


    /**
     * Return all extensions from section.
     *
     * @param string $section Section to filter.
     *
     * @return array List of sections.
     */
    static public function getExtensionBySection($section)
    {
        return db_get_all_rows_filter(
            'tdiscovery_apps',
            ['section' => $section]
        );
    }


    /**
     * Set execution permission in folder items and subfolders.
     *
     * @param string $path   Array of files to apply permissions.
     * @param array  $filter Array of files for apply permission only.
     *
     * @return void
     */
    private function setPermissionfiles($path, $filter=false)
    {
        global $config;

        if ($filter !== false && is_array($filter) === true) {
            foreach ($filter as $key => $file) {
                if (substr($file, 0, 1) !== '/') {
                    $file = $path.'/'.$file;
                }

                chmod($file, 0777);
            }
        } else {
            chmod($path, 0777);

            if (is_dir($path)) {
                $items = scandir($path);
                foreach ($items as $item) {
                    if ($item != '.' && $item != '..') {
                        $itemPath = $path.'/'.$item;
                        $this->setPermissionfiles($itemPath);
                    }
                }
            }
        }
    }


    /**
     * Unzip folder or only file.
     *
     * @param string $zipFile     File to unzip.
     * @param string $target_path Target path into unzip.
     * @param string $file        If only need unzip one file.
     *
     * @return boolean  $result True if the file has been successfully decompressed.
     */
    public function unZip($zipFile, $target_path, $file=null)
    {
        $zip = new \ZipArchive;

        if ($zip->open($zipFile) === true) {
            $zip->extractTo($target_path, $file);
            $zip->close();
            return true;
        } else {
            return false;
        }
    }


    /**
     * Checks if the discovery app can be migrated to .disco system.
     * If app is migrated or is not in .ini file, it cannot be migrated.
     *
     * @param string $shortName Short name of the discovery app.
     *
     * @return string App hash, false in case hash doesnt exist on ini file, or is already migraeted..
     */
    private function canMigrate(string $shortName='')
    {
        global $config;

        if (empty($shortName) === true) {
            return false;
        }

        // 1. Check if app is already migrated:
        // Get migrated Discovery Apps from config.
        $migratedAppsJson = db_get_value('value', 'tconfig', 'token', 'migrated_discovery_apps');

        if ($migratedAppsJson === false || empty($migratedAppsJson) === true) {
            return false;
        }

        // Decode JSON migrated apps.
        $migrateApps = json_decode(io_safe_output($migratedAppsJson), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        // Check app migrated.
        if (array_key_exists($shortName, $migrateApps)) {
            if (empty($migrateApps[$shortName]) === false && (bool) $migrateApps[$shortName] === true) {
                // Already migrated.
                return false;
            }
        }

        // 2. If app not migrated yet, check DiscoveryApplicationsMigrateCodes.ini
        // Path to the INI file
        $filePath = $config['homedir'].'/extras/discovery/DiscoveryApplicationsMigrateCodes.ini';

        // Parse the INI file.
        $migrationCodes = parse_ini_file($filePath, true);

        if ($migrationCodes === false) {
            return false;
        }

        // Check shortname in ini file.
        if (array_key_exists($shortName, $migrationCodes) === false) {
            return false;
        } else {
            return $migrationCodes[$shortName];
        }

        // All checks ok, discovery app can be migrated.
        return false;

    }


    /**
     * Prints html for migrate modal
     *
     * @return void
     */
    public function loadMigrateModal()
    {
        $shortname = get_parameter('shortname', null);
        $hash = get_parameter('hash', null);

        $form = [
            'action'   => '#',
            'id'       => 'modal_migrate_form',
            'onsubmit' => 'return false;',
            'class'    => 'modal',
            'name'     => 'migrate_form',
        ];

        $inputs = [];
        $migrateMessage = __(
            'All ‘legacy‘ tasks for this application will be migrated to the new ‘.disco’ package system. All configurations and executions will be managed with the new system. This process will not be reversible.</br></br>'
        );
        $migrateMessage .= _('Please check the migration code for the application before proceeding.');

        $inputs[] = [
            'wrapper'       => 'div',
            'block_id'      => 'div_migrate_message',
            'class'         => 'hole flex-row flex-items-center w98p',
            'direct'        => 1,
            'block_content' => [
                [
                    'label'     => $migrateMessage,
                    'arguments' => [
                        'class' => 'first_lbl w98p',
                        'name'  => 'lbl_migrate_message',
                        'id'    => 'lbl_migrate_message',
                    ],
                ],
            ],
        ];

        $inputs[] = [
            'label'     => __('Applicattion hash'),
            'id'        => 'div-hash',
            'arguments' => [
                'name'   => 'hash',
                'type'   => 'text',
                'value'  => $hash,
                'return' => true,
            ],
        ];

        $inputs[] = [
            'block_id'      => 'migrate_buttons',
            'class'         => 'flex-row flex-items-center w98p',
            'direct'        => 1,
            'block_content' => [
                [
                    'arguments' => [
                        'name'       => 'cancel',
                        'label'      => __('Cancel'),
                        'type'       => 'button',
                        'attributes' => [
                            'icon'  => 'left',
                            'mode'  => 'secondary',
                            'class' => 'sub cancel float-left',
                        ],
                    ],
                ],
                [
                    'arguments' => [
                        'name'       => 'migrate',
                        'label'      => __('Migrate'),
                        'type'       => 'submit',
                        'attributes' => [
                            'icon'  => 'wand',
                            'class' => 'sub wand float-right',
                        ],
                    ],
                ],
            ],
        ];

        $spinner = '<div id="migration-spinner" class="invisible spinner-fixed"></div>';

        $migration_form = $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true,
        );

        echo $migration_form.$spinner;
    }


    /**
     * Migrate app to new .disco system
     *
     * @return true if success, false in case of error.
     */
    public function migrateApp()
    {
        global $config;

        $hash = get_parameter('hash', false);
        $shortName = get_parameter('shortName', false);

        if ($hash === false || $shortName === false) {
            return false;
        }

        // 1. Gets md5
        try {
            $console_md5 = $this->calculateDirectoryMD5($shortName, false);
            $server_md5 = $this->calculateDirectoryMD5($shortName, true);
        } catch (Exception $e) {
            $return = [
                'error' => $e->getMessage(),
            ];
            echo json_encode($return);
            return;
        }

        if ($console_md5 === false || $server_md5 === false) {
            $return = [
                'error' => __('Error calculating app MD5'),
            ];
            echo json_encode($return);
            return;
        }

        // 2. Checks MD5
        if ($hash === $console_md5 && $hash === $server_md5) {
            // Init migration script.
            $return = $this->executeMigrationScript($shortName);
        } else {
            $return = [
                'error' => __('App hash does not match.'),
            ];
        }

        // Add shotrname to return for showing messages.
        $return['shortname'] = $shortName;

        echo \json_encode($return);
    }


    /**
     * Calculates directory MD% and saves it into array
     *
     * @param string  $shortName Shorname of app.
     * @param boolean $server    If true, perform checks into server folder.
     *
     * @return $md5 Array of md5 of filess.
     */
    private function calculateDirectoryMD5($shortName, $server)
    {
        global $config;

        $md5List = [];

        $serverPath = $config['remote_config'].'/discovery/'.$shortName;
        $consolePath = $config['homedir'].'/'.$this->path.'/'.$shortName;

        if ($server === true) {
            $directory = $serverPath;
        } else {
            $directory = $consolePath;
        }

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $md5List[] = md5_file($file->getPathname());
            }
        }

        if ($server === true) {
            $console_ini = $consolePath.'/discovery_definition.ini';
            $logo = $consolePath.'/logo.png';

            if (file_exists($console_ini)) {
                $md5List[] = md5_file($console_ini);
            }

            if (file_exists($logo)) {
                $md5List[] = md5_file($logo);
            }
        }

        sort($md5List);
        $concatenatedChecksums = implode('', $md5List);
        return md5($concatenatedChecksums);
    }


    /**
     * Executed migration script for app
     *
     * @param string $shortName Shortname of the app.
     *
     * @return true on success, false in case of error.
     */
    private function executeMigrationScript(string $shortName)
    {
        global $config;

        $dblock = db_get_lock('migrate-working');
              // Try to get a lock from DB.
        if ($dblock !== 1) {
            // Locked!
            return false;
        }

        $scriptName = preg_replace('/^pandorafms\.(\w+\.?\w*)$/m', 'migrate.$1.sql', $shortName);

        $script_path = $config['homedir'].'/extras/discovery/migration_scripts/'.$scriptName;
        if (file_exists($script_path) === false) {
            $return = [
                'error' => __('Migration script '.$scriptName.' could not be found'),
            ];
        } else {
            try {
                $res = db_process_file($script_path, false);
            } catch (\Exception $e) {
                $return = [
                    'error' => $e->getMessage(),
                ];
            } finally {
                db_release_lock('migrate_working');
            }

            if ($res === true) {
                $migrateAppsJson = io_safe_output(
                    db_get_value(
                        'value',
                        'tconfig',
                        'token',
                        'migrated_discovery_apps'
                    )
                );

                $migrateApps = json_decode(
                    $migrateAppsJson,
                    true
                );

                $migrateApps[$shortName] = 1;

                $migratedAppsJson = json_encode($migrateApps);

                if (json_last_error() === JSON_ERROR_NONE) {
                    config_update_value(
                        'migrated_discovery_apps',
                        $migratedAppsJson
                    );
                } else {
                    $return = [
                        'error' => __('Error decoding migrated apps json.'),
                    ];
                }

                    $return = [
                        'result'    => __('App migrated successfully'),
                        'shortName' => $shortName,
                    ];
            } else {
                $return = [
                    'error' => __('Error migrating app'),
                ];
            }
        }

        return $return;

    }


    /**
     * Check if legacy app has been migrated.
     *
     * @param string $shortName Shorn name of the app.
     *
     * @return boolean
     */
    static public function isMigrated($shortName)
    {
        global $config;

        $migrateAppsJson = io_safe_output(
            db_get_value(
                'value',
                'tconfig',
                'token',
                'migrated_discovery_apps'
            )
        );

        $migratedApps = json_decode($migrateAppsJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        if (array_key_exists($shortName, $migratedApps) === true && empty($migratedApps[$shortName] === false)) {
            return (bool) $migratedApps[$shortName];
        } else {
            return false;
        }

    }


    /**
     * Read metadata CSV from system and store data structure in memory.
     *
     * @return array Data structure.
     */
    private static function loadDiscoveryAppsMetadata()
    {
        global $config;

        // Open the CSV file for reading.
        $fileHandle = fopen($config['homedir'].'/extras/discovery/DiscoveryApps.csv', 'r');

        // Check if the file was opened successfully.
        if ($fileHandle !== false) {
            $csvData = [];

            // Loop through each line in the CSV file.
            while (($data = fgetcsv($fileHandle)) !== false) {
                $csvData[] = explode(';', $data[0]);
            }

            // Close the file handle.
            fclose($fileHandle);
        }

        $groupedArray = [];

        foreach ($csvData as $item) {
            $key = $item[2];
            if (isset($groupedArray[$key]) === false) {
                $groupedArray[$key] = [];
            }

            $itemShortName = $item[0];
            unset($item[0]);
            unset($item[2]);

            $itemIns = [
                'name'       => $item[1],
                'enterprise' => $item[3],
                'image'      => $item[4],
                'url'        => $item[5],
            ];

            $groupedArray[$key][$itemShortName] = $itemIns;
        }

        return $groupedArray;
    }


}
