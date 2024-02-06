<?php
/**
 * Manage ExtensionsDiscovery wizard for Pandora FMS Discovery
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage ExtensionsDiscovery
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

use PandoraFMS\Tools\Files;

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
/**
 * Implements extensionsDiscovery in wizard.
 */
class ExtensionsDiscovery extends Wizard
{

    /**
     * Id from extension
     *
     * @var integer
     */
    public $id;

    /**
     * Name from extension
     *
     * @var string
     */
    public $name;

    /**
     * Description from extension
     *
     * @var string
     */
    public $description;

    /**
     * Path from extensions Discovery
     *
     * @var string
     */
    public $path = '/attachment/discovery';

    /**
     * Icon from extensions Discovery
     *
     * @var string
     */
    public $icon = 'logo.png';

    /**
     * Section Discovery
     *
     * @var string
     */
    public $section = 'custom';

    /**
     * Extension to load
     *
     * @var string
     */
    public $mode = null;

    /**
     * Last page for steps.
     *
     * @var integer
     */
    public $lastPage;

    /**
     * Url page
     *
     * @var string
     */
    public $url;

    /**
     * All information from ini file extension.
     *
     * @var array
     */
    public $iniFile;

    /**
     * Current page
     *
     * @var integer
     */
    public $currentPage;

    /**
     * Values of macros in database.
     *
     * @var array
     */
    public $macrosValues = false;

    /**
     * Id from task
     *
     * @var integer
     */
    public $idTask;

    /**
     * Name of fields for send.
     *
     * @var array
     */
    public $nameFields;

    /**
     * List of select data custom of pandora
     *
     * @var array
     */
    public $pandoraSelectData = [
        'agent_groups',
        'agents',
        'module_groups',
        'modules',
        'module_types',
        'status',
        'alert_templates',
        'alert_actions',
        'interval',
        'tags',
        'credentials.custom',
        'credentials.aws',
        'credentials.azure',
        'credentials.sap',
        'credentials.snmp',
        'credentials.gcp',
        'credentials.wmi',
        'os',
    ];

    /**
     * Default logo for extension.
     *
     * @var string
     */
    public $defaultLogo = '/images/wizard/app_generico.svg';


    /**
     * Constructor.
     *
     * @param string $_section In discovery.
     * @param string $_mode    Extension to load.
     *
     * @return mixed
     */
    public function __construct($_section='custom', $_mode=null)
    {
        $this->section = $_section;
        $this->mode = $_mode;
        $this->url = 'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz='.$_section;
        $this->loadConfig();
    }


    /**
     * Load config from extension.
     *
     * @return void
     */
    private function loadConfig()
    {
        $row = db_get_row('tdiscovery_apps', 'short_name', $this->mode);
        $this->id = $row['id_app'];
        $this->name = $row['name'];
        $this->description = $row['description'];
    }


    /**
     * Return array extensions filtered by section
     *
     * @param boolean $not_defined_only Get only those extensions that are defined in the metadata CSV and not in db.
     *
     * @return array Extensions
     */
    public function loadExtensions($not_defined_only=false)
    {
        global $config;
        // Check access.
        check_login();
        $extensions = [];
        $rows = $this->getExtensionsApps();

        define('NOT_FOUND_MSG', 1);
        define('ENTERPRISE_MSG', 2);
        define('URL_MSG', 3);

        $appsMetadata = self::loadDiscoveryAppsMetadata();
        $sectionMetadata = $appsMetadata[$this->section];

        $anchor = html_print_anchor(
            [
                'href'    => 'index.php?sec=gagente&amp;sec2=godmode/agentes/configurar_agente&amp;tab=alert',
                'content' => __('here'),
            ],
            true
        );

        // Print JS required for message management.
        echo '<script>
        function showExtensionMsg(msgs, url, title) {
            var msgs_json = JSON.parse(msgs);

            var url_str = "";
            if (url != false) {
                url_str = `<a target="_blank" class="link-important" href="${url}">'.__('here').'</a>`;
            }

            var markup = "<ul class=\'\'>";

            if (msgs_json.includes('.NOT_FOUND_MSG.')) {
                markup += "<li>&nbsp;&nbsp;&nbsp;'.__('The required files for the application were not found.').'</li>";
            }

            if (msgs_json.includes('.ENTERPRISE_MSG.')) {
                markup += "<li>&nbsp;&nbsp;&nbsp;'.__('This discovery application is for Enterprise customers only.').'</li>";
            }

            if (msgs_json.includes('.URL_MSG.')) {
                markup += \'<li>&nbsp;&nbsp;&nbsp;'.__('You can download this application from').' \'+url_str+\'.</li>\';
            }

            markup += "</ul>";

            confirmDialog({
                title: title,
                message: markup,
                hideOkButton: true,
                ok: "'.__('OK').'",
                cancel: "'.__('Cancel').'",
                size: 550,
                maxHeight: 500
            });
        } 
        </script>';

        if ($not_defined_only === true) {
            // Case: search for those extensions defined in metadata CSV which are not in database.
            $short_names_list = array_column($rows, 'short_name');

            // Traverse apps in CSV metadata file and set properly those that do not exist in database.
            foreach ($sectionMetadata as $short_name => $val) {
                if (in_array($short_name, $short_names_list) === false) {
                    $logo = $this->path.'/'.$short_name.'/'.$val['image'];
                    if (file_exists($config['homedir'].$logo) === false) {
                        $logo = $this->defaultLogo;
                    }

                    $error_msgs = [];

                    if (isset($val['image']) === true
                        && file_exists($config['homedir'].'/images/discovery/'.$val['image']) === true
                        && file_exists($config['homedir'].$this->path.'/'.$short_name.'/'.$val['image']) === false
                    ) {
                        $logo = '/images/discovery/'.$val['image'];
                    }

                    $url = ui_get_full_url(
                        'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz='.$this->section.'&mode='.$extension['short_name']
                    );

                    if (enterprise_installed() === false && ((bool) $val['enterprise'] === true)) {
                        // Display enterprise message if console is open and extension is enterprise.
                        $error_msgs[] = ENTERPRISE_MSG;
                    }

                    $url_href = false;
                    if (isset($val['url']) === true
                        && $val['url'] !== ''
                    ) {
                        $url_href = $val['url'];
                        // Display URL message if an URL is defined in the metadata.
                        $error_msgs[] = URL_MSG;
                    }

                    if (empty($error_msgs) === false) {
                        $json_errors = json_encode($error_msgs);
                        // Display messages dialog if there are some.
                        $url = 'javascript: showExtensionMsg(\''.$json_errors.'\', \''.$url_href.'\', \''.io_safe_input($val['name']).'\');';
                    }

                    $extensions[] = [
                        'icon'               => $logo,
                        'label'              => io_safe_input($val['name']),
                        'url'                => $url,
                        'ghost_mode'         => true,
                        'mark_as_enterprise' => (bool) $val['enterprise'],
                        'defined'            => false,
                    ];
                }
            }
        } else {
            foreach ($rows as $key => $extension) {
                $error_msgs = [];

                $logo = $this->path.'/'.$extension['short_name'].'/'.$this->icon;
                if (file_exists($config['homedir'].$logo) === false) {
                    $logo = $this->defaultLogo;
                }

                $mark_as_enterprise = false;
                $ghostMode = false;
                $url = ui_get_full_url(
                    'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz='.$this->section.'&mode='.$extension['short_name']
                );
                $url_href = false;

                $iniFileExists = self::iniFileExists($extension['short_name']);

                // Access metadata for current extension.
                if (isset($sectionMetadata[$extension['short_name']]) === true) {
                    $itemData = $sectionMetadata[$extension['short_name']];

                    if (isset($itemData) === true) {
                        if (isset($itemData['image']) === true
                            && file_exists($config['homedir'].'/images/discovery/'.$itemData['image']) === true
                            && file_exists($config['homedir'].$this->path.'/'.$extension['short_name'].'/'.$this->icon) === false
                        ) {
                            $logo = '/images/discovery/'.$itemData['image'];
                        }

                        $mark_as_enterprise = (bool) $itemData['enterprise'];

                        if ($iniFileExists === false
                            && isset($itemData['url']) === true
                            && $itemData['url'] !== ''
                        ) {
                            $url_href = $itemData['url'];
                            // Display URL message if an URL is defined in the metadata.
                            $error_msgs[] = URL_MSG;
                        }

                        if (enterprise_installed() === false
                            && (bool) $itemData['enterprise'] === true
                        ) {
                            // Set ghost mode and display enterprise message if console is open and extension is enterprise.
                            $error_msgs[] = ENTERPRISE_MSG;
                            $ghostMode = true;
                        }

                        $itemName = $itemData['name'];
                    }
                }

                if ($iniFileExists === false) {
                    // Set ghost mode and display not found message if ini file does not exist for extension.
                    $error_msgs[] = NOT_FOUND_MSG;
                    $ghostMode = true;
                }

                if (empty($error_msgs) === false) {
                    $json_errors = json_encode($error_msgs);
                    // Display messages dialog if there are some.
                    $url = 'javascript: showExtensionMsg(\''.$json_errors.'\', \''.$url_href.'\', \''.io_safe_input($itemName).'\');';
                }

                $extensions[] = [
                    'icon'               => $logo,
                    'label'              => $extension['name'],
                    'url'                => $url,
                    'ghost_mode'         => $ghostMode,
                    'mark_as_enterprise' => $mark_as_enterprise,
                    'defined'            => true,
                ];
            }
        }

        return $extensions;
    }


    /**
     * Return all extensions from apps section
     *
     * @return array extensions.
     */
    public function getExtensionsApps()
    {
        return db_get_all_rows_filter('tdiscovery_apps', ['section' => $this->section]);

    }


    /**
     * Load the extension information from discovery_definition.ini.
     *
     * @return array Information ini file.
     */
    public function loadIni()
    {
        global $config;
        $iniFile = parse_ini_file($config['homedir'].$this->path.'/'.$this->mode.'/discovery_definition.ini', true, INI_SCANNER_TYPED);

        return $iniFile;
    }


    /**
     * Return next page from config_steps.
     *
     * @return integer Return the number of next page.
     */
    public function nextPage()
    {
        $pages = array_keys($this->iniFile['config_steps']['name']);
        if ($this->currentPage === 0 || empty($this->currentPage) === true) {
            return $pages[0];
        }

        foreach ($pages as $k => $page) {
            if ($page === $this->currentPage) {
                if (end($pages) === $this->currentPage) {
                    return $this->currentPage;
                } else {
                    return $pages[($k + 1)];
                }
            }
        }
    }


    /**
     * Draw the extension forms.
     *
     * @return boolean Return boolean if exist error.
     */
    public function run()
    {
        ui_require_javascript_file('select2.min');
        ui_require_javascript_file('extensions_discovery');
        $_iniFile = $this->loadIni();
        if ($_iniFile === false) {
             // No file .disco.
            ui_print_info_message(['no_close' => true, 'message' => __('No .disco file found') ]);
            return false;
        }

        $this->iniFile = $_iniFile;
        if (empty($this->iniFile['config_steps']) === false) {
            $this->lastPage = end(array_keys($this->iniFile['config_steps']['name']));
        } else {
            $this->lastPage = 0;
        }

        $this->currentPage = (int) get_parameter('page', '0');
        $this->idTask = get_parameter('id_task', '');
        $action = get_parameter('action', '');
        $isTheEnd = get_parameter('complete_button', '');

        // Control parameters and errors.
        $error = false;

        if ($action === 'task_definition_form') {
            $error = $this->processTaskDefinition();
        }

        if ($action === 'process_macro') {
            $error = $this->processCustomMacro();
        }

        $task = $this->getTask();

        if ($task === false && $this->currentPage > 0) {
            $error = __('Task not defined');
        }

        // Build breadcrum.
        $breadcrum = [
            [
                'link'  => 'index.php?sec=gservers&sec2=godmode/servers/discovery',
                'label' => 'Discovery',
            ],
        ];

        switch ($this->section) {
            case 'app':
                $breadcrum[] = [
                    'link'  => $this->url,
                    'label' => __('Application'),
                ];
            break;

            case 'cloud':
                $breadcrum[] = [
                    'link'  => $this->url,
                    'label' => __('Cloud'),
                ];
            break;

            case 'custom':
                $breadcrum[] = [
                    'link'  => $this->url,
                    'label' => __('Custom'),
                ];
            break;

            default:
                $breadcrum[] = [
                    'link'  => $this->url,
                    'label' => __('Custom'),
                ];
            break;
        }

        $parameters = '';
        if (empty($this->idTask) === false) {
            $parameters .= '&id_task='.$this->idTask;
        }

        $breadcrum[] = [
            'link'     => $this->url.'&mode='.$this->mode.$parameters,
            'label'    => 'Task definition',
            'selected' => ((0 === (int) $this->currentPage) ? 1 : 0),
        ];

        foreach ($this->iniFile['config_steps']['name'] as $key => $step) {
            $parameters = '&mode='.$this->mode.'&page='.$key;
            if (empty($this->idTask) === false) {
                $parameters .= '&id_task='.$this->idTask;
            }

            $breadcrum[] = [
                'link'     => $this->url.$parameters,
                'label'    => $step,
                'selected' => (($key === (int) $this->currentPage) ? 1 : 0),
            ];
        }

        // Avoid to print header out of wizard.
        $this->prepareBreadcrum($breadcrum);

        // Header.
        ui_print_page_header(
            $this->iniFile['discovery_extension_definition']['name'],
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

        if ($error !== false) {
            ui_print_error_message(
                $error
            );
            return;
        } else if ($action !== '') {
            ui_print_success_message(__('Operation realized'));

            if (empty($isTheEnd) === false) {
                header('Location:'.$config['homeurl'].'index.php?sec=discovery&sec2=godmode/servers/discovery&wiz=tasklist');
            }
        }

        $_url = ui_get_full_url(
            sprintf(
                $this->url.'&mode=%s&page=%s%s',
                $this->mode,
                $this->nextPage(),
                (empty($this->idTask) === false) ? '&id_task='.$this->idTask : '',
            )
        );

        $table = new StdClass();
        $table->id = 'form_editor';
        $table->width = '100%';
        $table->class = 'databox filter-table-adv max_floating_element_size';

        $table->style = [];
        $table->style[0] = 'width: 50%';
        $table->style[1] = 'width: 50%';
        $table->data = [];
        if ($this->currentPage === 0) {
            // If page is 0 then create form for task definition.
            $table->data = $this->viewTaskDefinition();
        } else {
            // If page is bigger than 0 then render form .ini.
            $table->data = $this->viewMacroForm();
        }

        echo '<form type="submit" action="'.$_url.'" method="post">';
        html_print_table($table);

        $actionButtons = '';

        if ($this->currentPage !== $this->nextPage()) {
            $actionButtons = html_print_submit_button(
                __('Next'),
                'next_button',
                false,
                [
                    'class' => 'sub',
                    'icon'  => 'plus',
                ],
                true
            );
        }

        $actionButtons .= html_print_submit_button(
            __('Complete setup'),
            'complete_button',
            false,
            [
                'class' => 'sub',
                'icon'  => 'update',
                'value' => '1',
            ],
            true
        );

        html_print_action_buttons($actionButtons);
        echo '</form>';

    }


    /**
     * Draw a select with the pandora data
     *
     * @param string  $selectData   Type of select.
     * @param string  $name         Name of select.
     * @param string  $defaultValue Default value.
     * @param boolean $multiple     Define if the select is multiple.
     * @param boolean $required     Define if field is required.
     *
     * @return string Return the html select.
     */
    private function drawSelectPandora($selectData, $name, $defaultValue, $multiple=false, $required=false)
    {
        if ($multiple === true && $selectData !== 'interval') {
            $name .= '[]';
            $defaultValue = json_decode($defaultValue);
        } else {
            $defaultValue = io_safe_input($defaultValue);
        }

        switch ($selectData) {
            case 'agent_groups':
                $input = html_print_select_groups(
                    false,
                    'AR',
                    true,
                    $name,
                    $defaultValue,
                    '',
                    '',
                    '',
                    true,
                    $multiple,
                    false,
                    '',
                    false,
                    false,
                    false,
                    false,
                    'id_grupo',
                    true,
                    false,
                    false,
                    false,
                    false,
                    $required
                );
            break;

            case 'agents':
                $input = html_print_select_from_sql(
                    'SELECT nombre, alias as n FROM tagente',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%;',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'module_groups':
                $input = html_print_select_from_sql(
                    'SELECT id_mg, name
                    FROM tmodule_group ORDER BY name',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'modules':
                $input = html_print_select_from_sql(
                    'select nombre, nombre as n from tagente_modulo',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'module_types':
                $input = html_print_select_from_sql(
                    'select nombre, descripcion from ttipo_modulo',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'status':
                $module_status_arr = [];
                // Default.
                $module_status_arr[AGENT_MODULE_STATUS_NORMAL] = __('Normal');
                $module_status_arr[AGENT_MODULE_STATUS_WARNING] = __('Warning');
                $module_status_arr[AGENT_MODULE_STATUS_CRITICAL_BAD] = __('Critical');
                $module_status_arr[AGENT_MODULE_STATUS_UNKNOWN] = __('Unknown');
                $module_status_arr[AGENT_MODULE_STATUS_NOT_INIT] = __('Not init');
                $input = html_print_select(
                    $module_status_arr,
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    '',
                    false,
                    'width:100%',
                    false,
                    false,
                    false,
                    '',
                    false,
                    false,
                    $required,
                    false,
                    true,
                    true
                );
            break;

            case 'alert_templates':
                $input = html_print_select_from_sql(
                    'select id, name from talert_templates',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'alert_actions':
                $input = html_print_select_from_sql(
                    'select id, name from talert_actions',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'interval':
                $input = html_print_extended_select_for_time(
                    $name,
                    (string) $defaultValue,
                    '',
                    '',
                    '0',
                    false,
                    true
                );
            break;

            case 'tags':
                $input = html_print_select_from_sql(
                    'select id_tag, name from ttag',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.custom':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "custom"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.aws':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "AWS"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.azure':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "AZURE"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.sap':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "SAP"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.snmp':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "SNMP"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.gcp':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "GOOGLE"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'credentials.wmi':
                $input = html_print_select_from_sql(
                    'select identifier, identifier as i  from tcredential_store WHERE product = "WMI"',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            case 'os':
                $input = html_print_select_from_sql(
                    'SELECT id_os, name FROM tconfig_os ORDER BY name',
                    $name,
                    $defaultValue,
                    '',
                    ($multiple === false) ? __('None selected') : '',
                    '',
                    true,
                    $multiple,
                    true,
                    false,
                    'width: 100%',
                    false,
                    GENERIC_SIZE_TEXT,
                    '',
                    $required
                );
            break;

            default:
                $input = html_print_select(
                    [],
                    $name,
                    $defaultValue,
                    '',
                    '',
                    0,
                    true
                );
            break;
        }

        return $input;
    }


    /**
     * Draw input from parameters of .ini.
     *
     * @param array   $parameters Configuration of input.
     * @param boolean $implicit   Indicates if all the configuration is indicated in the array.
     *
     * @return string Html from input.
     */
    public function drawInput($parameters, $implicit=false)
    {
        $input = '';
        $defaultValue = $this->macrosValues[$parameters['macro']];
        switch ($parameters['type']) {
            case 'string':
                $input = html_print_input_text(
                    $parameters['macro'],
                    $defaultValue,
                    '',
                    50,
                    255,
                    true,
                    false,
                    ($parameters['mandatory_field'] === false) ? false : true
                );
            break;

            case 'number':
                $config = [
                    'type'   => 'number',
                    'name'   => $parameters['macro'],
                    'value'  => $defaultValue,
                    'return' => true,
                ];
                if ($parameters['mandatory_field'] !== false) {
                    $config['required'] = true;
                }

                $input = html_print_input($config);
            break;

            case 'password':
                $isEncrypted = (bool) $this->macrosValues[$parameters['encrypt_on_true']];
                if ($isEncrypted === true) {
                    $defaultValueEncrypted = $this->encryptPassword($defaultValue, true);
                    if (empty($defaultValueEncrypted) === false) {
                        $defaultValue = $defaultValueEncrypted;
                    }
                }

                $input = html_print_input_password(
                    $parameters['macro'],
                    $defaultValue,
                    '',
                    50,
                    255,
                    true,
                    false,
                    ($parameters['mandatory_field'] === false) ? false : true,
                    '',
                    'on'
                );
                if (empty($parameters['encrypt_on_true']) === false) {
                    $input .= html_print_input_hidden(
                        $parameters['macro'].'_encrypt',
                        $parameters['encrypt_on_true'],
                        true
                    );
                }
            break;

            case 'checkbox':
                $input = html_print_checkbox_switch(
                    $parameters['macro'],
                    1,
                    (bool) $defaultValue,
                    true
                );
            break;

            case 'textarea':
                $input = html_print_textarea(
                    $parameters['macro'],
                    5,
                    20,
                    $defaultValue,
                    ($parameters['mandatory_field'] === false) ? '' : 'required="required"',
                    true
                );
            break;

            case 'select':
                if (in_array($parameters['select_data'], $this->pandoraSelectData) === true) {
                    $input = $this->drawSelectPandora(
                        $parameters['select_data'],
                        $parameters['macro'],
                        $defaultValue,
                        false,
                        ($parameters['mandatory_field'] === false) ? false : true,
                    );
                    $parameters['type'] = $parameters['select_data'];
                } else {
                    if ($implicit === false) {
                        $options = $this->iniFile[$parameters['select_data']]['option'];
                    } else {
                        $options = $parameters['select_data'];
                    }

                    $input = html_print_select(
                        $options,
                        $parameters['macro'],
                        $defaultValue,
                        '',
                        __('None selected'),
                        '',
                        true,
                        false,
                        true,
                        '',
                        false,
                        'width: 100%;',
                        false,
                        false,
                        false,
                        '',
                        false,
                        false,
                        ($parameters['mandatory_field'] === false) ? false : true,
                    );
                }
            break;

            case 'multiselect':
                if (in_array($parameters['select_data'], $this->pandoraSelectData) === true) {
                    $input = $this->drawSelectPandora(
                        $parameters['select_data'],
                        $parameters['macro'],
                        $defaultValue,
                        true,
                    );
                    $parameters['type'] = $parameters['select_data'];
                } else {
                    if ($implicit === false) {
                        $options = $this->iniFile[$parameters['select_data']]['option'];
                    } else {
                        $options = $parameters['select_data'];
                    }

                    $input = html_print_select(
                        $options,
                        $parameters['macro'].'[]',
                        json_decode($defaultValue, true),
                        '',
                        '',
                        0,
                        true,
                        true,
                        true,
                        '',
                        false,
                        'width: 100%',
                        false,
                        false,
                        false,
                        '',
                        false,
                        false,
                        false,
                        false,
                        true,
                        true
                    );
                }
            break;

            case 'tree':
                // Show bucket tree explorer.
                ui_require_javascript_file('pandora_snmp_browser');
                if ($implicit === false) {
                    $treeData = $this->iniFile[$parameters['tree_data']];
                    $treeInfo = $this->getTreeStructure($parameters, $treeData);
                } else {
                    $treeData = $parameters['tree_data'];
                    $treeInfo = $this->getTreeStructureByScript($parameters, $treeData);
                }

                $input = ui_print_tree(
                    $treeInfo,
                    // Id.
                    0,
                    // Depth.
                    0,
                    // Last.
                    0,
                    // Last_array.
                    [],
                    // Sufix.
                    true,
                    // Return.
                    true,
                    // Descriptive ids.
                    false
                );
            break;

            default:
                $input = html_print_input_text(
                    $parameters['macro'],
                    $defaultValue,
                    '',
                    50,
                    255,
                    true,
                    false,
                    ($parameters['mandatory_field'] === false) ? false : true
                );
            break;
        }

        $input .= html_print_input_hidden(
            $parameters['macro'].'type',
            $parameters['type'],
            true
        );
        $class = '';
        if ($parameters['show_on_true'] !== null) {
            $class = $parameters['macro'].'_hide';
            $input .= $this->showOnTrue($parameters['show_on_true'], $class);
        }

        $name = $parameters['name'];
        if (empty($parameters['tip']) === false) {
            $name .= ui_print_help_tip($parameters['tip'], true);
        }

        return html_print_label_input_block(
            $name,
            $input,
            ['div_class' => $class]
        );
    }


    /**
     * Return the task app from database.
     *
     * @return array $task Task of database.
     */
    private function getTask()
    {
        return db_get_row_filter(
            'trecon_task',
            [
                'id_app' => $this->id,
                'id_rt'  => $this->idTask,
                'type'   => 15,
            ],
        );
    }


    /**
     * Returns the value of the macro.
     *
     * @param string $macro Name of macro for filter.
     *
     * @return mixed Value of the macro.
     */
    private function getValueMacro($macro)
    {
        return db_get_value_filter(
            'value',
            'tdiscovery_apps_tasks_macros',
            [
                'id_task' => $this->idTask,
                'macro'   => $macro,
            ]
        );
    }


    /**
     * Return form for macro form.
     *
     * @return array $form Form macro.
     */
    private function viewMacroForm()
    {
        $data = [];

        $macros = db_get_all_rows_filter(
            'tdiscovery_apps_tasks_macros',
            ['id_task' => $this->idTask],
            ['*']
        );
        if ($macros !== false) {
            foreach ($macros as $key => $macro) {
                $this->macrosValues[$macro['macro']] = io_safe_output($macro['value']);
            }
        }

        // Process ini or script.
        $customFields = $this->iniFile['config_steps']['custom_fields'][$this->currentPage];
        $customFieldsByScript = $this->getStructureFormByScript($this->iniFile['config_steps']['script_data_fields'][$this->currentPage]);

        if ($customFields === null && $customFieldsByScript === null) {
            $data[0][0] = html_print_image(
                'images/no_data_toshow.png',
                true,
                ['class' => 'w200px']
            );
            $data[1][0] = html_print_input_hidden(
                'action',
                'process_macro',
                true
            );
            return $data;
        }

        $columns = 2;
        if ($this->iniFile['config_steps']['fields_columns'][$this->currentPage] !== null
            && $this->iniFile['config_steps']['fields_columns'][$this->currentPage] === 1
        ) {
            $columns = 1;
        }

        $row = 0;
        $col = 0;
        foreach ($customFieldsByScript as $key => $value) {
            $this->nameFields[] = $value['macro'];
            $data[$row][$col] = $this->drawInput($value, true);
            $col++;
            if ($col == $columns) {
                $row++;
                $col = 0;
            }
        }

        foreach ($this->iniFile[$customFields]['macro'] as $key => $id) {
            $parameters = [
                'macro'           => $id,
                'name'            => $this->iniFile[$customFields]['name'][$key],
                'tip'             => $this->iniFile[$customFields]['tip'][$key],
                'type'            => $this->iniFile[$customFields]['type'][$key],
                'placeholder'     => $this->iniFile[$customFields]['placeholder'][$key],
                'mandatory_field' => $this->iniFile[$customFields]['mandatory_field'][$key],
                'show_on_true'    => $this->iniFile[$customFields]['show_on_true'][$key],
                'encrypt_on_true' => $this->iniFile[$customFields]['encrypt_on_true'][$key],
                'select_data'     => $this->iniFile[$customFields]['select_data'][$key],
                'tree_data'       => $this->iniFile[$customFields]['tree_data'][$key],
            ];
            $this->nameFields[] = $id;
            $data[$row][$col] = $this->drawInput($parameters);
            $col++;
            if ($col == $columns) {
                $row++;
                $col = 0;
            }
        }

        $data[($row + 1)][1] = html_print_input_hidden(
            'action',
            'process_macro',
            true
        );
        $data[($row + 1)][1] .= html_print_input_hidden(
            'name_fields',
            implode(',', $this->nameFields),
            true
        );

        return $data;
    }


    /**
     * Return form for task definition.
     *
     * @return array $form Form for task definition.
     */
    private function viewTaskDefinition()
    {
        $task = $this->getTask();

        $data = [];
        $data[0][0] = html_print_label_input_block(
            __('Task name'),
            html_print_input_text(
                'task_name',
                $task['name'],
                '',
                50,
                255,
                true,
                false,
                true
            )
        );

        $data[1][0] = html_print_label_input_block(
            __('Description'),
            html_print_textarea(
                'description',
                5,
                20,
                $task['description'],
                '',
                true
            )
        );

        $data[2][0] = html_print_label_input_block(
            __('Discovery server'),
            html_print_select_from_sql(
                sprintf(
                    'SELECT id_server, name
                                    FROM tserver
                                    WHERE server_type = %d
                                    ORDER BY name',
                    SERVER_TYPE_DISCOVERY
                ),
                'discovery_server',
                $task['id_recon_server'],
                '',
                '',
                '0',
                true,
                false,
                true,
                false,
                false,
                false,
                GENERIC_SIZE_TEXT,
                '',
                false
            )
        );

        $data[3][0] = html_print_label_input_block(
            __('Group'),
            html_print_select_groups(
                false,
                'AR',
                false,
                'group',
                $task['id_group'],
                '',
                '',
                0,
                true,
                false,
                false,
                '',
                false,
                false,
                false,
                false,
                'id_grupo',
                false,
                false,
                false,
                false,
                false,
                true
            )
        );

        $inputs_interval = html_print_select(
            [
                'defined' => 'Defined',
                'manual'  => 'Manual',
            ],
            'mode_interval',
            ($task['interval_sweep'] === '0') ? 'manual' : 'defined',
            'changeModeInterval(this)',
            '',
            '0',
            true,
            false,
            true,
            '',
            false
        ).html_print_extended_select_for_time(
            'interval',
            (empty($task['interval_sweep']) === true) ? '300' : $task['interval_sweep'],
            '',
            '',
            '0',
            false,
            true,
            false,
            true,
        );
        $js_variables = '<script>const interval = "'.$task['interval_sweep'].'";</script>';
        $data[4][0] = html_print_label_input_block(
            __('Interval'),
            html_print_div(
                [
                    'style'   => 'display: flex;max-width: 345px; justify-content: space-between;',
                    'content' => $inputs_interval.$js_variables,
                ],
                true
            )
        );

        $data[5][0] = html_print_label_input_block(
            __('Timeout').ui_print_help_tip('This timeout will be applied for each task execution', true),
            html_print_extended_select_for_time(
                'tiemout',
                (empty($task['executions_timeout']) === true) ? '60' : $task['executions_timeout'],
                '',
                '',
                '0',
                false,
                true
            ),
        );

        $data[6][0] = html_print_input_hidden(
            'action',
            'task_definition_form',
            true
        );

        $data[7][0] = html_print_input_hidden(
            'id_task',
            $task['id_rt'],
            true
        );

        return $data;
    }


    /**
     * Sabe data from task definition form.
     *
     * @return string $error Error string if exist.
     */
    private function processTaskDefinition()
    {
        $taskName = get_parameter('task_name', '');
        $description = get_parameter('description', '');
        $discoveryServer = get_parameter('discovery_server', '');
        $group = get_parameter('group', 0);
        $mode_interval = get_parameter('mode_interval', 'defined');
        $interval = get_parameter('interval', '');
        $tiemout = get_parameter('tiemout', 60);
        $completeTask = get_parameter('complete_button', '');

        if ($mode_interval === 'manual') {
            $interval = '0';
        }

        $error = false;

        if ($taskName === ''
            || $discoveryServer === ''
            || $group === ''
            || $interval === ''
        ) {
            $error = __('Fields empties');
            return $error;
        }

        if ($this->idTask === '') {
            db_process_sql_begin();
            try {
                $_idTask = db_process_sql_insert(
                    'trecon_task',
                    [
                        'id_app'             => $this->id,
                        'name'               => $taskName,
                        'description'        => $description,
                        'id_group'           => $group,
                        'interval_sweep'     => $interval,
                        'id_recon_server'    => $discoveryServer,
                        'type'               => 15,
                        'setup_complete'     => (empty($completeTask) === false) ? 1 : 0,
                        'executions_timeout' => $tiemout,
                    ]
                );

                if ($_idTask === false) {
                    $error = __('Error creating the discovery task');
                } else {
                    $this->idTask = $_idTask;
                    $this->autoLoadConfigMacro();
                }
            } catch (Exception $e) {
                $error = __('Error creating the discovery task');
            }

            if ($error === false) {
                db_process_sql_commit();
            } else {
                db_process_sql_rollback();
            }
        } else {
            $result = db_process_sql_update(
                'trecon_task',
                [
                    'id_app'             => $this->id,
                    'name'               => $taskName,
                    'description'        => $description,
                    'id_group'           => $group,
                    'interval_sweep'     => $interval,
                    'id_recon_server'    => $discoveryServer,
                    'type'               => 15,
                    'setup_complete'     => (empty($completeTask) === false) ? 1 : 0,
                    'executions_timeout' => $tiemout,
                ],
                ['id_rt' => $this->idTask]
            );

            if ($result === false) {
                $error = __('Error updating the discovery task');
            }
        }

        return $error;
    }


    /**
     * Process the values of input from macro defined in .ini
     *
     * @return string $error Error string if exist.
     */
    private function processCustomMacro()
    {
        $error = false;

        $keyParameters = explode(',', get_parameter('name_fields', ''));

        foreach ($keyParameters as $v => $key) {
            $type = get_parameter($key.'type', '');
            switch ($type) {
                case 'checkbox':
                    $value = get_parameter_switch($key, 0);
                break;

                case 'multiselect':
                    $value = io_safe_input(json_encode(get_parameter($key, '')));
                break;

                case 'password':
                    $value = get_parameter($key, '');
                    $encryptKey = get_parameter($key.'_encrypt', '');
                    if ($encryptKey !== '') {
                        $encrypt = (bool) get_parameter_switch($encryptKey, 0);
                        if ($encrypt === true) {
                            $valueEncrypt = $this->encryptPassword($value);
                            if (empty($valueEncrypt) === false) {
                                $value = $valueEncrypt;
                            }
                        }
                    }
                break;

                default:
                    $value = get_parameter($key, '');
                break;
            }

            if (is_array($value) === true) {
                $value = io_safe_input(json_encode($value));
            }

            $exist = db_get_row_filter(
                'tdiscovery_apps_tasks_macros',
                [
                    'id_task' => $this->idTask,
                    'macro'   => $key,
                ]
            );

            if (in_array($type, $this->pandoraSelectData) === false) {
                $type = 'custom';
            }

            if ($exist === false) {
                $result = db_process_sql_insert(
                    'tdiscovery_apps_tasks_macros',
                    [
                        'id_task' => $this->idTask,
                        'macro'   => $key,
                        'value'   => $value,
                        'type'    => $type,
                    ]
                );
                if ($result === false) {
                    $error = __('Field %s not insert', $key);
                }
            } else {
                $result = db_process_sql_update(
                    'tdiscovery_apps_tasks_macros',
                    [
                        'value' => $value,
                        'type'  => $type,
                    ],
                    [
                        'id_task' => $this->idTask,
                        'macro'   => $key,
                    ]
                );
                if ($result === false) {
                    $error = __('Field %s not updated', $key);
                }
            }
        }

        $completeTask = get_parameter('complete_button', '');
        if (empty($completeTask) === false) {
            $result = db_process_sql_update(
                'trecon_task',
                ['setup_complete' => 1],
                ['id_rt' => $this->idTask]
            );
            if ($result === false) {
                $error = __('Task not updated');
            }
        }

        return $error;
    }


    /**
     * Check if name of input macro is correct.
     *
     * @param string $name Name of input.
     *
     * @return boolean value true if name is correct.
     */
    private function isCorrectNameInput($name)
    {
        if (substr($name, 0, 1) === '_' && substr($name, -1) === '_') {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Return logic for component show on true.
     *
     * @param string $checkbox      Name the checkbox for hide input.
     * @param string $elementToHide Name the element to hide HIS PARENT.
     *
     * @return string String Name the element
     */
    private function showOnTrue($checkbox, $elementToHide)
    {
        return '<script>
                $(document).ready(function(){
                    if($("[name='.$checkbox.']").length > 0){
                        if(!$("[name='.$checkbox.']")[0].checked) {
                            $(".'.$elementToHide.'").hide();
                        }
                        $("[name='.$checkbox.']").on("change",
                        function(e) {
                            if (e.currentTarget.checked) {
                                $(".'.$elementToHide.'").show();
                            } else {
                                $(".'.$elementToHide.'").hide();
                            }
                        });
                    }
                })
                </script>';
    }


    /**
     * Load the macros task in database
     *
     * @throws Exception Excepcion to control possible error for default value.
     *
     * @return void
     */
    private function autoLoadConfigMacro()
    {
        $defaultValues = $this->iniFile['discovery_extension_definition']['default_value'];

        foreach ($defaultValues as $key => $value) {
            if ($value === false) {
                $value = 0;
            }

            $result = db_process_sql_insert(
                'tdiscovery_apps_tasks_macros',
                [
                    'id_task' => $this->idTask,
                    'macro'   => $key,
                    'value'   => (string) io_safe_input($value),
                    'type'    => 'custom',
                ]
            );
            if ($result === false) {
                throw new Exception('Error creating task');
            }
        }

        $tempFiles = $this->iniFile['tempfile_confs']['file'];

        foreach ($tempFiles as $key => $value) {
            $result = db_process_sql_insert(
                'tdiscovery_apps_tasks_macros',
                [
                    'id_task'   => $this->idTask,
                    'macro'     => $key,
                    'value'     => (string) io_safe_input($value),
                    'type'      => 'custom',
                    'temp_conf' => 1,
                ]
            );
            if ($result === false) {
                throw new Exception('Error creating task');
            }
        }
    }


    /**
     * Return array structure for draw tree when array is by .ini.
     *
     * @param array $parent        Parent from the tree.
     * @param array $firstChildren First children from parent.
     *
     * @return array $treeInfo Return the array with format for render treee
     */
    private function getTreeStructure($parent, $firstChildren)
    {
        $treeInfo = [];
        foreach ($firstChildren['name'] as $key => $value) {
            $checked = false;
            $name = (empty($firstChildren['macro'][$key]) === false) ? $firstChildren['macro'][$key].'[]' : $parent['id'].'[]';
            $nameField = (empty($firstChildren['macro'][$key]) === false) ? $firstChildren['macro'][$key] : $parent['id'];
            if (in_array($nameField, $this->nameFields) === false) {
                $this->nameFields[] = $nameField;
            }

            $checkedValues = json_decode(io_safe_output($this->macrosValues[$nameField]), true);
            if (empty($checkedValues) === false) {
                if (in_array($firstChildren['value'][$key], $checkedValues)) {
                    $checked = true;
                }
            }

            $treeInfo['__LEAVES__'][$key] = [
                'label'      => $value,
                'selectable' => (bool) $firstChildren['selectable'][$key],
                'name'       => $name,
                'value'      => $firstChildren['value'][$key],
                'checked'    => $checked,
            ];

            if (empty($firstChildren['children'][$key]) === false) {
                $children = $this->iniFile[$firstChildren['children'][$key]];
                $treeInfo['__LEAVES__'][$key]['sublevel'] = $this->getTreeStructure($parent, $children);
            }
        }

        return $treeInfo;
    }


    /**
     * Return array structure for draw tree when array is by script.
     *
     * @param array $parent        Parent from the tree.
     * @param array $firstChildren First children from parent.
     *
     * @return array $treeInfo Return the array with format for render treee
     */
    private function getTreeStructureByScript($parent, $firstChildren)
    {
        $treeInfo = [];
        foreach ($firstChildren as $key => $value) {
            $checked = false;
            $name = (empty($value['macro']) === false) ? $value['macro'].'[]' : $parent['macro'].'[]';
            $nameField = (empty($value['macro']) === false) ? $value['macro'] : $parent['macro'];
            if (in_array($nameField, $this->nameFields) === false) {
                $this->nameFields[] = $nameField;
            }

            $checkedValues = json_decode(io_safe_output($this->macrosValues[$nameField]), true);
            if (empty($checkedValues) === false) {
                if (in_array($value['value'], $checkedValues, true) === true) {
                    $checked = true;
                }
            }

            $treeInfo['__LEAVES__'][$key] = [
                'label'      => $value['name'],
                'selectable' => (bool) $value['selectable'],
                'name'       => $name,
                'value'      => $value['value'],
                'checked'    => $checked,
            ];

            if (empty($value['children']) === false) {
                $children = $value['children'];
                $treeInfo['__LEAVES__'][$key]['sublevel'] = $this->getTreeStructureByScript($parent, $children);
            }
        }

        return $treeInfo;
    }


    /**
     * Return a json with the form structure for draw.
     *
     * @param mixed $command String.
     *
     * @return array Result of command.
     */
    private function getStructureFormByScript($command)
    {
        global $config;
        $executionFiles = $this->iniFile['discovery_extension_definition']['execution_file'];
        foreach ($executionFiles as $key => $file) {
            $file = $config['homedir'].$this->path.'/'.$this->mode.'/'.$file;
            $command = str_replace($key, $file, $command);
        }

        $values = $this->replaceValues($command);
        $command = $values['command'];
        $toDelete = $values['delete'];
        if (empty($command) === false) {
            $result = $this->executeCommand($command);
        }

        if (count($toDelete) > 0) {
            foreach ($toDelete as $key => $folder) {
                Files::rmrf($folder);
            }
        }

        return json_decode($result, true);
    }


    /**
     * Replace values in command
     *
     * @param string $command String command for replace macros.
     *
     * @return array $values Command and files to delete.
     */
    private function replaceValues($command)
    {
        preg_match_all('/\b_[a-zA-Z0-9]*_\b/', $command, $matches);
        $foldersToDelete = [];
        foreach ($matches[0] as $key => $macro) {
            $row = db_get_row_filter(
                'tdiscovery_apps_tasks_macros',
                [
                    'macro'   => $macro,
                    'id_task' => $this->idTask,
                ]
            );
            if ($row !== false) {
                if (in_array($row['type'], $this->pandoraSelectData) === true) {
                    $value = $this->getValuePandoraSelect($row['type'], $row['value']);
                    $command = str_replace($macro, $value, $command);
                } else if ((int) $row['temp_conf'] === 1) {
                    $nameFile = $row['id_task'].'_'.$row['id_task'].'_'.uniqid();
                    $value = $this->getValueTempFile($nameFile, $row['value']);
                    $command = str_replace($macro, $value, $command);
                    $foldersToDelete[] = str_replace($nameFile, '', $value);
                } else {
                    $command = str_replace($macro, io_safe_output($row['value']), $command);
                }
            }
        }

        return [
            'command' => $command,
            'delete'  => $foldersToDelete,
        ];
    }


    /**
     * Create a temp file for tempfile_confs macros.
     *
     * @param string $nameFile Name file only.
     * @param string $content  Content to save to file.
     *
     * @return string $pathNameFile Name file and with path for replace.
     */
    private function getValueTempFile($nameFile, $content)
    {
        global $config;
        $content = io_safe_output($content);
        $content = $this->replaceValues($content)['command'];
        $nameTempDir = $config['attachment_store'].'/temp_files/';
        if (file_exists($nameTempDir) === false) {
            mkdir($nameTempDir);
        }

        $tmpPath = Files::tempdirnam(
            $nameTempDir,
            'temp_files_'
        );
        $pathNameFile = $tmpPath.'/'.$nameFile;
        file_put_contents($pathNameFile, $content);

        return $pathNameFile;
    }


    /**
     * Return the correct value for pandora select
     *
     * @param string $type Type of input.
     * @param string $id   Value of the row macro.
     *
     * @return string $id New id with the values replaced
     */
    private function getValuePandoraSelect($type, $id)
    {
        $id = io_safe_output($id);
        $idsArray = json_decode($id);
        if (is_array($idsArray) === false) {
            $idsArray = [$id];
        }

        foreach ($idsArray as $key => $v) {
            $value = false;
            switch ($type) {
                case 'agent_groups':
                    $value = groups_get_name($v);
                break;

                case 'module_groups':
                    $value = modules_get_modulegroup_name($v);
                break;

                case 'tags':
                    $value = tags_get_name($v);
                break;

                case 'alert_templates':
                    $value = alerts_get_alert_template_name($v);
                break;

                case 'alert_actions':
                    $value = alerts_get_alert_action_name($v);
                break;

                case 'credentials.custom':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode(
                        json_encode(
                            [
                                'user'     => $credentials['username'],
                                'password' => $credentials['password'],
                            ]
                        )
                    );
                break;

                case 'credentials.aws':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode(
                        json_encode(
                            [
                                'access_key_id'     => $credentials['username'],
                                'secret_access_key' => $credentials['password'],
                            ]
                        )
                    );
                break;

                case 'credentials.azure':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode(
                        json_encode(
                            [
                                'client_id'          => $credentials['username'],
                                'application_secret' => $credentials['password'],
                                'tenant_domain'      => $credentials['extra_1'],
                                'subscription_id'    => $credentials['extra_2'],
                            ]
                        )
                    );
                break;

                case 'credentials.gcp':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode($credentials['extra_1']);
                break;

                case 'credentials.sap':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode(
                        json_encode(
                            [
                                'user'     => $credentials['username'],
                                'password' => $credentials['password'],
                            ]
                        )
                    );
                break;

                case 'credentials.snmp':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode($credentials['extra_1']);
                break;

                case 'credentials.wmi':
                    $credentials = CredentialStore::getKey($v);
                    $value = base64_encode(
                        json_encode(
                            [
                                'user'      => $credentials['username'],
                                'password'  => $credentials['password'],
                                'namespace' => $credentials['extra_1'],
                            ]
                        )
                    );
                break;

                case 'os':
                    $value = get_os_name($v);
                break;

                default:
                continue;
            }

            if ($value !== false) {
                $id = str_replace($v, io_safe_output($value), $id);
            }
        }

        return $id;
    }


    /**
     * Encrypt and decode password with the user script.
     *
     * @param string  $password Password to encrypt.
     * @param boolean $decode   True for decode password.
     *
     * @return string Password encrypted
     */
    private function encryptPassword($password, $decode=false)
    {
        global $config;
        if ($decode === false) {
            $command = $this->iniFile['discovery_extension_definition']['passencrypt_exec'];
            $nameFile = $this->iniFile['discovery_extension_definition']['passencrypt_script'];
            $file = $config['homedir'].$this->path.'/'.$this->mode.'/'.$nameFile;
            $command = str_replace('_passencrypt_script_', $file, $command);
        } else {
            $command = $this->iniFile['discovery_extension_definition']['passdecrypt_exec'];
            $nameFile = $this->iniFile['discovery_extension_definition']['passdecrypt_script'];
            $file = $config['homedir'].$this->path.'/'.$this->mode.'/'.$nameFile;
            $command = str_replace('_passdecrypt_script_', $file, $command);
        }

        $command = str_replace('_password_', $password, $command);

        if (empty($command) === false) {
            return $this->executeCommand($command);
        } else {
            return false;
        }
    }


    /**
     * Valid the .ini
     *
     * @param array $iniForValidate IniFile to validate.
     *
     * @return mixed Return false if is ok and string for error.
     */
    public static function validateIni($iniForValidate)
    {
        $discoveryExtension = $iniForValidate['discovery_extension_definition'];

        if (!$discoveryExtension) {
            return __('The file does not contain the block \'discovery_extension_definition\'');
        }

        if (!array_key_exists('short_name', $discoveryExtension)) {
            return __('The \'discovery_extension_definition\' block must contain a \'short_name\' parameter');
        }

        $defaultValues = $discoveryExtension['default_value'];
        foreach ($defaultValues as $key => $value) {
            if (!preg_match('/^_[a-zA-Z0-9]+_$/', $key)) {
                return __(
                    'The \'discovery_extension_definition\' block \'default_value\' parameter has a key with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'',
                    $key
                );
            }
        }

        $shortName = $discoveryExtension['short_name'];

        if (!preg_match('/^[A-Za-z0-9._-]+$/', $shortName)) {
            return __('The \'discovery_extension_definition\' block \'short_name\' parameter contains illegal characters. Use only letters (A-Z and a-z), numbers (0-9), points (.), hyphens (-) and underscores (_)');
        }

        if (!array_key_exists('section', $discoveryExtension) || !array_key_exists('name', $discoveryExtension)) {
            return __('The \'discovery_extension_definition\' block must contain a \'section\' and a \'name\' parameters');
        }

        $section = $discoveryExtension['section'];
        $name = $discoveryExtension['name'];

        if (!in_array($section, ['app', 'cloud', 'custom'])) {
            return __('The \'discovery_extension_definition\' block \'section\' parameter must be \'app\', \'cloud\' or \'custom\'');
        }

        if (empty($name)) {
            return __('The \'discovery_extension_definition\' block \'name\' parameter can not be empty');
        }

        if (!array_key_exists('exec', $discoveryExtension)) {
            return __('The \'discovery_extension_definition\' block must contain an \'exec\' parameter');
        }

        $execs = $discoveryExtension['exec'];

        foreach ($execs as $exec) {
            if (empty($exec)) {
                return __('All the \'discovery_extension_definition\' block \'exec\' parameter definitions can not be empty');
            }
        }

        $checkEmptyFields = [
            'passencrypt_script',
            'passencrypt_exec',
            'passdecrypt_script',
            'passdecrypt_exec',
        ];

        foreach ($checkEmptyFields as $key) {
            if ($discoveryExtension[$key] !== null && empty($discoveryExtension[$key]) === true) {
                return __('The \'discovery_extension_definition\' block \'%s\' parameter can not be empty', $key);
            }
        }

        foreach ($discoveryExtension['execution_file'] as $key => $value) {
            if (!preg_match('/^_[a-zA-Z0-9]+_$/', $key)) {
                return __('The \'discovery_extension_definition\' block \'execution_file\' parameter has a key with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'', $key);
            }

            if (empty($value) === true) {
                return __('All the \'discovery_extension_definition\' block \'execution_file\' parameter definitions can not be empty: \'%s\'', $key);
            }
        }

        if ($iniForValidate['config_steps'] !== null && empty($iniForValidate['config_steps']) === true) {
            return __('The \'config_steps\' block must contain a \'name\' parameter that can not be empty.');
        }

        foreach ($iniForValidate['config_steps'] as $key => $value) {
            foreach ($value as $innerKey => $inner_value) {
                if (isset($inner_steps[$innerKey])) {
                    $inner_steps[$innerKey][$key] = $inner_value;
                } else {
                    $inner_steps[$innerKey] = [$key => $inner_value];
                }
            }
        }

        $customFields = [];
        foreach ($inner_steps as $key => $step) {
            if (is_numeric($key) === false || $key === 0) {
                return __('All the \'config_steps\' block parameters must use numbers greater than 0 as keys: \'%s\'.', $key);
            }

            if (empty($step['name']) === true) {
                return __('The \'config_steps\' block must contain a \'name\' parameter for all the configuration steps: \'%s\'', $key);
            }

            if (empty($step['custom_fields']) === true
                && empty($step['script_data_fields']) === true
            ) {
                return __('The \'config_steps\' block must contain a \'custom_fields\' or \'script_data_fields\' parameter that can not be empty');
            } else if (empty($step['custom_fields']) === false) {
                if (empty($iniForValidate[$step['custom_fields']]) === true) {
                    return __('The \'config_steps\' block \'custom_fields\' parameter has a key value reference that does not exist: \'%s\'', $step['custom_fields']);
                } else {
                    $customFields[] = $step['custom_fields'];
                }
            }

            $customFields[] = $step['name'];
        }

        $requiredKeys = [
            'macro',
            'name',
            'type',
        ];

        $validTypes = [
            'string',
            'number',
            'password',
            'textarea',
            'checkbox',
            'select',
            'multiselect',
            'tree',
        ];

        $validSelectData = [
            'agent_groups',
            'agents',
            'module_groups',
            'modules',
            'module_types',
            'status',
            'alert_templates',
            'alert_actions',
            'interval',
            'tags',
            'credentials.custom',
            'credentials.aws',
            'credentials.azure',
            'credentials.sap',
            'credentials.snmp',
            'credentials.gcp',
            'credentials.wmi',
            'os',
        ];

        $selectDataNames = [];
        $treeDataNames = [];

        foreach ($customFields as $key => $customField) {
            $innerFields = [];
            foreach ($iniForValidate[$customField] as $key => $value) {
                foreach ($value as $innerKey => $innerValue) {
                    if (isset($innerFields[$innerKey])) {
                        $innerFields[$innerKey][$key] = $innerValue;
                    } else {
                        $innerFields[$innerKey] = [$key => $innerValue];
                    }
                }
            }

            foreach ($innerFields as $key => $field) {
                if (is_numeric($key) === false || $key === 0) {
                    return __('All the \'%s\' block parameters must use numbers greater than 0 as keys: \'%s\'.', $customField, $key);
                }

                foreach ($requiredKeys as $k => $value) {
                    if (empty($field[$value]) === true) {
                            return __('The \'%s\' block \'%s\' parameter definitions can not be empty: \'%s\'.', $customField, $value, $key);
                    }
                }

                if (!preg_match('/^_[a-zA-Z0-9]+_$/', $field['macro'])) {
                    return __('The \'%s\' block \'macro\' parameter has a definition with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'', $customField, $field['macro']);
                }

                if (in_array($field['type'], $validTypes) === false) {
                    return __('The \'%s\' block \'type\' parameter has a definition with invalid value. Must be \'string\', \'number\', \'password\', \'textarea\', \'checkbox\', \'select\', \'multiselect\' or \'tree\': \'%s\'', $customField, $field['type']);
                }

                if ($field['type'] === 'select' || $field['type'] === 'multiselect') {
                    if (empty($field['select_data']) === true) {
                        return __('All the \'%s\' block \'select_data\' parameter definitions can not be empty: \'%s\'.', $customField, $key);
                    } else if ($iniForValidate[$field['select_data']] === null && in_array($field['select_data'], $validSelectData) === false) {
                        return __(
                            'The \'%s\' block \'select_data\' parameter has a definition with invalid select type. Must be \'agent_groups\', \'agents\', \'module_groups\', \'modules\', \'module_types\', \'tags\', \'status\', \'alert_templates\', \'alert_actions\', \'interval\', \'credentials.custom\', \'credentials.aws\', \'credentials.azure\', \'credentials.gcp\', \'credentials.sap\', \'credentials.snmp\', \'os\' or an existint reference: \'%s\'',
                            $customField,
                            $field['select_data']
                        );
                    } else if ($iniForValidate[$field['select_data']] !== null) {
                        $selectDataNames[] = $field['select_data'];
                    }
                }

                if ($field['type'] === 'tree') {
                    if (empty($field['tree_data']) === true) {
                        return __('All the \'%s\' block \'tree_data\' parameter definitions can not be empty: \'%s\'', $field['macro'], $key);
                    } else if ($iniForValidate[$field['tree_data']] === null) {
                        return __('The \'%s\' block \'tree_data\' parameter has a key value reference that does not exist: \'%s\'', $customField, $field['tree_data']);
                    } else {
                        $treeDataNames[] = $field['tree_data'];
                    }
                }

                if (empty($field['mandatory_field']) === false) {
                    $validValues = [
                        'true',
                        'false',
                        '1',
                        '0',
                        'yes',
                        'no',
                    ];

                    if (in_array($field['mandatory_field'], $validValues) === false) {
                        return __(
                            'The \'%s\' block \'mandatory_field\' parameter has a definition with invalid value. Must be \'true\' or \'false\', \'1\' or \'0\', \'yes\' or \'no\': \'%s\'',
                            $customField,
                            $field['mandatory_field']
                        );
                    }
                }

                if ($field['tip'] !== null && empty($field['tip']) === true) {
                    return __('All the \'%s\' block \'tip\' parameter definitions can not be empty: \'%s\'.', $customField, $key);
                }

                if ($field['placeholder'] !== null && empty($field['placeholder']) === true) {
                    return __('All the \'%s\' block \'placeholder\' parameter definitions can not be empty: \'%s\'.', $customField, $key);
                }

                if (empty($field['show_on_true']) === false) {
                    if (!preg_match('/^_[a-zA-Z0-9]+_$/', $field['show_on_true'])) {
                        return __(
                            'The \'%s\' block \'show_on_true\' parameter has a definition with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'',
                            $customField,
                            $field['show_on_true']
                        );
                    }
                }

                if (empty($field['encrypt_on_true']) === false) {
                    if (!preg_match('/^_[a-zA-Z0-9]+_$/', $field['encrypt_on_true'])) {
                        return __(
                            'The \'%s\' block \'encrypt_on_true\' parameter has a definition with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'',
                            $customField,
                            $field['encrypt_on_true']
                        );
                    }
                }
            }
        }

        foreach ($treeDataNames as $key => $name) {
            $error = self::validateTreeRecursive($name, $iniForValidate);
            if ($error !== false) {
                return $error;
            }
        }

        foreach ($selectDataNames as $key => $name) {
            if (empty($iniForValidate[$name]['option']) === true) {
                return __('The \'%s\' block must contain an \'option\' parameter', $name);
            }

            foreach ($iniForValidate[$name]['option'] as $key => $option) {
                if (empty($option) === true) {
                    return __('All the \'%s\' block \'option\' parameter definitions can not be empty: \'%s\'.', $name, $key);
                }
            }
        }

        if ($iniForValidate['tempfile_confs'] !== null && empty($iniForValidate['tempfile_confs']['file']) === true) {
            return __('The \'tempfile_confs\' block must contain a \'file\' parameter.');
        }

        foreach ($iniForValidate['tempfile_confs']['file'] as $key => $tempfile) {
            if (!preg_match('/^_[a-zA-Z0-9]+_$/', $key)) {
                return __(
                    'The \'tempfile_confs\' block \'file\' parameter has a key with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'',
                    $key
                );
            }

            if (empty($tempfile) === true) {
                return __('All the \'tempfile_confs\' block \'file\' parameter definitions can not be empty: \'%s\'.', $key);
            }
        }

        return false;
    }


    /**
     * Validate a tree recursively
     *
     * @param string $dataTree Name of parent data_tree.
     * @param array  $iniFile  Inifile for search children.
     * @param array  $parents  Array of parents for recursive action, DO NOT SET.
     *
     * @return boolean True if tree is correct.
     */
    public static function validateTreeRecursive($dataTree, $iniFile, $parents=[])
    {
        $innerData = [];
        $parents[] = $dataTree;
        foreach ($iniFile[$dataTree] as $key => $value) {
            foreach ($value as $innerKey => $innerValue) {
                if (isset($innerData[$innerKey])) {
                    $innerData[$innerKey][$key] = $innerValue;
                } else {
                    $innerData[$innerKey] = [$key => $innerValue];
                }
            }
        }

        if (count($innerData) === 0) {
            return __('The \'%s\' block must contain a \'name\' parameter that can not be empty.', $dataTree);
        }

        foreach ($innerData as $key => $prop) {
            if (is_numeric($key) === false || $key === 0) {
                return __('All the \'%s\' block parameters must use numbers greater than 0 as keys: \'%s\'.', $dataTree, $key);
            }

            if (empty($prop['name']) === true) {
                return __('The \'%s\' block must contain a \'name\' parameter for all the tree elements: \'%s\'.', $dataTree, $key);
            }

            if ($prop['selectable'] !== null && $prop['selectable'] === '') {
                return __('All the \'%s\' block \'selectable\' parameter definitions can not be empty: \'%s\'.', $dataTree, $key);
            } else {
                $validValues = [
                    'true',
                    'false',
                    '1',
                    '0',
                    'yes',
                    'no',
                ];

                if (in_array($prop['selectable'], $validValues) === false) {
                    return __(
                        'The \'%s\' block \'selectable\' parameter has a definition with invalid value. Must be \'true\' or \'false\', \'1\' or \'0\', \'yes\' or \'no\': \'%s\'',
                        $dataTree,
                        $prop['selectable']
                    );
                }
            }

            if ($prop['macro'] !== null && !preg_match('/^_[a-zA-Z0-9]+_$/', $prop['macro'])) {
                return __(
                    'The \'%s\' block \'macro\' parameter has a definition with invalid format. Use only letters (A-Z and a-z) and numbers (0-9) between opening and ending underscores (_): \'%s\'',
                    $dataTree,
                    $prop['macro']
                );
            }

            if ($prop['children'] !== null && empty($iniFile[$prop['children']]) === true) {
                return __('The \'%s\' block \'children\' parameter has a key value reference that does not exist: \'%s\'', $dataTree, $prop['children']);
            } else if (in_array($prop['children'], $parents) === true) {
                return __('The \'%s\' block \'children\' parameter has a key value reference to a parent tree element: \'%s\'', $dataTree, $prop['children']);
            } else if (empty($iniFile[$prop['children']]) === false) {
                $result = self::validateTreeRecursive($prop['children'], $iniFile, $parents);
                if ($result !== false) {
                    return $result;
                }
            }
        }

        return false;
    }


    /**
     * Excute command with the timeout of the task.
     *
     * @param string $command Command to execute.
     *
     * @return string Output of command
     */
    private function executeCommand($command)
    {
        $task = $this->getTask();
        $timeout = $task['executions_timeout'];

        $descriptors = [
            0 => [
                'pipe',
                'r',
            ],
            1 => [
                'pipe',
                'w',
            ],
            2 => [
                'pipe',
                'w',
            ],
        ];

        $process = proc_open($command, $descriptors, $pipes);

        if (!is_resource($process)) {
            return false;
        }

        stream_set_blocking($pipes[1], 0);

        stream_set_blocking($pipes[2], 0);

        if (!$timeout) {
            $timeout = 5;
        }

        $real_timeout = ($timeout * 1000000);

        $buffer = '';

        while ($real_timeout > 0) {
            $start = microtime(true);

            $read  = [$pipes[1]];
            $other = [];
            stream_select($read, $other, $other, 0, $real_timeout);

            $status = proc_get_status($process);

            $buffer .= stream_get_contents($pipes[1]);

            if ($status['running'] === false) {
                break;
            }

            $real_timeout -= ((microtime(true) - $start) * 1000000);
        }

        if ($real_timeout <= 0) {
            proc_terminate($process, 9);

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);

            return false;
        }

        $errors = stream_get_contents($pipes[2]);

        if (empty($errors) === false && empty($buffer)) {
            proc_terminate($process, 9);

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);

            proc_close($process);

            return false;
        }

        proc_terminate($process, 9);

        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        proc_close($process);

        return $buffer;
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


    /**
     * Check if ini file exists for extension.
     *
     * @param string $shortName Extension short name.
     *
     * @return boolean Whether or not ini file exists.
     */
    public static function iniFileExists($shortName)
    {
        global $config;

        $path = $config['homedir'].'/attachment/discovery/'.$shortName.'/discovery_definition.ini';
        return file_exists($path);
    }


}
