<?php

require_once __DIR__.'/Wizard.main.php';

/**
 * Undocumented class
 */
class HostDevices extends Wizard
{

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
        $mode = get_parameter('mode', null);

        if ($mode === null) {
            $this->setBreadcrum(['<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>']);
            $this->printHeader();
            if (extensions_is_enabled_extension('csv_import')) {
                echo '<a href="'.$this->url.'&mode=importcsv" alt="importcsv">Importar csv</a>';
            }

            echo '<a href="'.$this->url.'&mode=netscan" alt="netscan">Escanear red</a>';
            return;
        }

        if ($mode == 'importcsv') {
            $this->setBreadcrum(
                [
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd">Host&devices</a>',
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=hd&mode=csv">Import CSV</a>',
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

        if (!check_acl($config['id_user'], 0, 'AW')
        ) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access db status'
            );
            include 'general/noaccess.php';
            return;
        }

        if (!extensions_is_enabled_extension('csv_import')) {
            ui_print_error_message(
                [
                    'message'  => __('Extension CSV Import is not enabled.'),
                    'no_close' => true,
                ]
            );
            return;
        }

        include_once $config['homedir'].'/enterprise/extensions/csv_import/main.php';
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

        include_once $config['homedir'].'/include/functions_users.php';

        $user_groups = users_get_groups(false, 'AW', true, false, null, 'id_grupo');
        $user_groups = array_keys($user_groups);

        if (isset($this->page) === false
            || $this->page == 0
        ) {
            $form = [];

            // Input task name.
            // Input Discovery Server.
            // Input Network.
            // Input interval.
            // Input group.
            $form['inputs'] = [
                [
                    'label'     => __('Task name'),
                    'arguments' => [
                        'name'  => 'name',
                        'value' => '',
                        'type'  => 'text',
                        'size'  => 25,
                    ],
                ],
            ];

            $this->printForm($form);
        }

        if ($this->page == 100) {
            return [
                'result' => $this->result,
                'id'     => $this->id,
                'msg'    => $this->msg,
            ];
        }
    }


}
