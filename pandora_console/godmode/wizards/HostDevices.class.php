<?php

require_once __DIR__.'/Wizard.main.php';
require_once $config['homedir'].'/include/functions_users.php';

/**
 * Undocumented class
 */
class HostDevices extends Wizard
{
    // CSV constants.
    const HDW_CSV_NOT_DATA = 0;
    const HDW_CSV_DUPLICATED = 0;
    const HDW_CSV_GROUP_EXISTS = 0;

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $values = [];

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $result;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $id;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $msg;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $icon;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $label;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $url;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $page;


    /**
     * Undocumented function.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Mensajito.
     * @param string  $icon  Mensajito.
     * @param string  $label Mensajito.
     *
     * @return class HostDevices
     */
    public function __construct(
        int $page=0,
        string $msg='hola',
        string $icon='hostDevices.png',
        string $label='Host & Devices'
    ) {
        $this->setBreadcrum([]);

        $this->id = null;
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = $label;
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd'
        );

        return $this;
    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function run()
    {
        global $config;

        // Load styles.
        parent::run();

        $mode = get_parameter('mode', null);

        if ($mode === null) {
            $this->setBreadcrum(['<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>']);
            $this->printHeader();

            echo '<a href="'.$this->url.'&mode=importcsv" alt="importcsv">Importar csv</a>';
            echo '<a href="'.$this->url.'&mode=netscan" alt="netscan">Escanear red</a>';

            return;
        }

        if ($mode == 'importcsv') {
            $this->setBreadcrum(
                [
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>',
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=importcsv">Import CSV</a>',
                ]
            );
            $this->printHeader();
            return $this->runCSV();
        }

        if ($mode == 'netscan') {
            $this->setBreadcrum(
                [
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>',
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=netscan">Net scan</a>',
                ]
            );
            $this->printHeader();
            return $this->runNetScan();
        }

        return null;
    }


    /**
     * Checks if environment is ready,
     * returns array
     *   icon: icon to be displayed
     *   label: label to be displayed
     *
     * @return array With data.
     **/
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,
        ];
    }


    // Extra methods.


    /**
     * Undocumented function
     *
     * @return void
     */
    public function runCSV()
    {
        global $config;

        if (!check_acl($config['id_user'], 0, 'AW')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access db status'
            );
            include 'general/noaccess.php';
            return;
        }

        if (!isset($this->page) || $this->page == 0) {
            $this->printForm(
                [
                    'form'   => [
                        'action'  => '#',
                        'method'  => 'POST',
                        'enctype' => 'multipart/form-data',
                    ],
                    'inputs' => [
                        [
                            'arguments' => [
                                'type'   => 'hidden',
                                'name'   => 'import_file',
                                'value'  => 1,
                                'return' => true,
                            ],
                        ],
                        [
                            'label'     => __('Upload file'),
                            'arguments' => [
                                'type'   => 'file',
                                'name'   => 'file',
                                'return' => true,
                            ],
                        ],
                        [
                            'label'     => __('Server'),
                            'arguments' => [
                                'type'   => 'select',
                                'fields' => servers_get_names(),
                                'name'   => 'server',
                                'return' => true,
                            ],
                        ],
                        [
                            'label'     => __('Separator'),
                            'arguments' => [
                                'type'   => 'select',
                                'fields' => [
                                    ',' => ',',
                                    ';' => ';',
                                    ':' => ':',
                                    '.' => '.',
                                    '#' => '#',
                                ],
                                'name'   => 'separator',
                                'return' => true,
                            ],
                        ],
                        [
                            'arguments' => [
                                'name'   => 'page',
                                'value'  => 1,
                                'type'   => 'hidden',
                                'return' => true,
                            ],
                        ],
                        [
                            'arguments' => [
                                'name'       => 'submit',
                                'label'      => __('Go'),
                                'type'       => 'submit',
                                'attributes' => 'class="sub next"',
                                'return'     => true,
                            ],
                        ],
                    ],
                ]
            );
        }

        if (isset($this->page) && $this->page == 1) {
            $server = get_parameter('server');
            $separator = get_parameter('separator');

            if (isset($_FILES['file'])) {
                $file_status_code = get_file_upload_status('file');
                $file_status = translate_file_upload_status($file_status_code);

                if ($file_status === true) {
                    $error_message = [];
                    $line = -1;
                    $file = fopen($_FILES['file']['tmp_name'], 'r');
                    if (! empty($file)) {
                        while (($data = fgetcsv($file, 1000, $separator)) !== false) {
                            $result = $this->processCsvData($data, $server);
                            $line++;
                            if ($result === HDW_CSV_NOT_DATA || $result === HDW_CSV_DUPLICATED || $result === HDW_CSV_GROUP_EXISTS) {
                                if ($result === HDW_CSV_NOT_DATA) {
                                    $error_message[] = __('No data or wrong separator in line ').$line.'</br>';
                                } else if ($result === HDW_CSV_DUPLICATED) {
                                    $error_message[] = __('Agent ').io_safe_input($data[0]).__(' duplicated').'</br>';
                                } else {
                                    $error_message[] = __("Id group %s in line %s doesn't exist in %s", $data[4], $line, get_product_name()).'</br>';
                                }

                                continue;
                            }

                            ui_print_result_message(
                                $result !== false,
                                __('Created agent %s', $result['agent_name']),
                                __('Could not create agent %s', $result['agent_name'])
                            );
                        }
                    }

                    fclose($file);

                    if (empty($error_message)) {
                        ui_print_success_message(__('File processed'));
                    } else {
                        foreach ($error_message as $msg) {
                            ui_print_error_message($msg);
                        }
                    }
                } else {
                    ui_print_error_message($file_status);
                }

                @unlink($_FILES['file']['tmp_name']);
            } else {
                ui_print_error_message(__('No input file detected'));
            }

            echo $this->breadcrum[0];
        }
    }


    /**
     * Retrieves and validates information given by user in NetScan wizard.
     *
     * @return boolean Data OK or not.
     */
    public function parseNetScan()
    {
        return false;

    }


    /**
     * Undocumented function
     *
     * @return void
     */
    public function runNetScan()
    {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Agent Management'
            );
            include 'general/noaccess.php';
            return;
        }

        $user_groups = users_get_groups(false, 'AW', true, false, null, 'id_grupo');
        $user_groups = array_keys($user_groups);

        if (isset($this->page) && $this->page == 1) {
            // Parse page 0 responses.
            $this->parseNetScan();
        }

        if (!isset($this->page) || $this->page == 0) {
            // Interval.
            $interv_manual = 0;
            if ((int) $interval == 0) {
                $interv_manual = 1;
            }

            if (isset($this->page) === false
                || $this->page == 0
            ) {
                $form = [];

                // Input task name.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Task name').'</b>',
                    'arguments' => [
                        'name'  => 'taskname',
                        'value' => '',
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ];

                // Input Discovery Server.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Discovery server').'</b>'.ui_print_help_tip(
                        __('You must select a Discovery Server to run the Task, otherwise the Recon Task will never run'),
                        true
                    ),
                    'arguments' => [
                        'type'     => 'select_from_sql',
                        'sql'      => sprintf(
                            'SELECT id_server, name
                                    FROM tserver
                                    WHERE server_type = %d
                                    ORDER BY name',
                            SERVER_TYPE_DISCOVERY
                        ),
                        'name'     => 'id_recon_server',
                        'selected' => 0,
                        'return'   => true,
                    ],
                ];

                // Input Network.
                $form['inputs'][] = [

                    'label'     => '<b>'.__('Network').'</b>'.ui_print_help_tip(
                        __('You can specify several networks, separated by commas, for example: 192.168.50.0/24,192.168.60.0/24'),
                        true
                    ),
                    'arguments' => [
                        'name'  => 'name',
                        'value' => '',
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ];

                // Input interval.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Interval').'</b>'.ui_print_help_tip(
                        __('Manual interval means that it will be executed only On-demand'),
                        true
                    ),
                    'arguments' => [
                        'type'     => 'select',
                        'selected' => $interv_manual,
                        'fields'   => [
                            0 => __('Defined'),
                            1 => __('Manual'),
                        ],
                        'name'     => 'interval_manual_defined',
                        'return'   => true,
                    ],
                    'extra'     => '<span id="interval_manual_container">'.html_print_extended_select_for_time(
                        'interval',
                        $interval,
                        '',
                        '',
                        '0',
                        false,
                        true,
                        false,
                        false
                    ).ui_print_help_tip(
                        __('The minimum recomended interval for Recon Task is 5 minutes'),
                        true
                    ).'</span>',
                ];

                // Input Group.
                $form['inputs'][] = [
                    'label'     => '<b>'.__('Group').'</b>',
                    'arguments' => [
                        'name'      => 'id_group',
                        'privilege' => 'PM',
                        'type'      => 'select_groups',
                        'return'    => true,
                    ],
                ];

                // Hidden, page.
                $form['inputs'][] = [
                    'arguments' => [
                        'name'   => 'page',
                        'value'  => ($this->page + 1),
                        'type'   => 'hidden',
                        'return' => true,
                    ],
                ];

                // Submit button.
                $form['inputs'][] = [
                    'arguments' => [
                        'name'       => 'submit',
                        'label'      => __('Next'),
                        'type'       => 'submit',
                        'attributes' => 'class="sub next"',
                        'return'     => true,
                    ],
                ];

                $form['form'] = [
                    'method' => 'POST',
                    'action' => '#',
                ];

                $form['js'] = '
    $("select#interval_manual_defined").change(function() {
        if ($("#interval_manual_defined").val() == 1) {
            $("#interval_manual_container").hide();
            $("#text-interval_text").val(0);
            $("#hidden-interval").val(0);
        }
        else {
            $("#interval_manual_container").show();
            $("#text-interval_text").val(10);
            $("#hidden-interval").val(600);
            $("#interval_units").val(60);
        }
    }).change();';

                // Print NetScan page 0.
                $this->printForm($form);
            }
        }

        if ($this->page == 1) {
            // Page 1.
            echo 'page 1!';
        }

        if ($this->page == 100) {
            return [
                'result' => $this->result,
                'id'     => $this->id,
                'msg'    => $this->msg,
            ];
        }
    }


    /**
     * Process the csv of agent.
     *
     * @param array  $data   Data of agent.
     * @param string $server Name of server.
     *
     * @return array with data porcessed.
     */
    private static function processCsvData($data, $server='')
    {
        if (empty($data) || count($data) < 5) {
            return HDW_CSV_NOT_DATA;
        }

        $data['network_components'] = array_slice($data, 6);
        $data['agent_name'] = io_safe_input($data[0]);
        $data['alias'] = io_safe_input($data[0]);
        $data['ip_address'] = $data[1];
        $data['id_os'] = $data[2];
        $data['interval'] = $data[3];
        $data['id_group'] = $data[4];
        $data['comentarios'] = io_safe_input($data[5]);

        $exists = (bool) agents_get_agent_id($data['agent_name']);
        if ($exists) {
            return HDW_CSV_DUPLICATED;
        }

        $group_exists_in_pandora = (bool) groups_get_group_by_id($data['id_group']);
        if (!$group_exists_in_pandora) {
            return HDW_CSV_GROUP_EXISTS;
        }

        $data['id_agent'] = agents_create_agent(
            $data['agent_name'],
            $data['id_group'],
            $data['interval'],
            $data['ip_address'],
            [
                'id_os'       => $data['id_os'],
                'server_name' => $server,
                'modo'        => 1,
                'alias'       => $data['alias'],
                'comentarios' => $data['comentarios'],
            ]
        );

        foreach ($data['network_components'] as $id_network_component) {
            network_components_create_module_from_network_component(
                (int) $id_network_component,
                $data['id_agent']
            );
        }

        return $data;
    }


}
