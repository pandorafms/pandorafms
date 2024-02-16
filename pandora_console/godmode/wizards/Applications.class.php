<?php
/**
 * Applications wizard manager.
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Applications
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

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
require_once $config['homedir'].'/include/functions_users.php';
require_once $config['homedir'].'/include/class/ExtensionsDiscovery.class.php';

/**
 * Implements Wizard to provide generic Applications wizard.
 */
class Applications extends Wizard
{

    /**
     * Sub-wizard to be launch (vmware,oracle...).
     *
     * @var string
     */
    public $mode;

    /**
     * Task properties.
     *
     * @var array
     */
    public $task;

    /**
     * Class of styles.
     *
     * @var string
     */
    public $class;


    /**
     * Constructor.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Default message to show to users.
     * @param string  $icon  Target icon to be used.
     * @param string  $label Target label to be displayed.
     *
     * @return mixed
     */
    public function __construct(
        int $page=0,
        string $msg='Default message. Not set.',
        string $icon='images/wizard/applications.png',
        string $label='Applications'
    ) {
        $this->setBreadcrum([]);

        $this->access = 'AW';
        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->class = $class_style;
        $this->label = $label;
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=app'
        );

        // Print Warning Message.
        $this->printWarningMessage();

        return $this;
    }


    /**
     * Run wizard manager.
     *
     * @return mixed Returns null if wizard is ongoing. Result if done.
     */
    public function run()
    {
        global $config;

        // Load styles.
        parent::run();

        // Load current wiz. sub-styles.
        ui_require_css_file(
            'application',
            ENTERPRISE_DIR.'/include/styles/wizards/'
        );

        $mode = get_parameter('mode', null);

        // Load application wizards.
        $enterprise_classes = glob(
            $config['homedir'].'/'.ENTERPRISE_DIR.'/include/class/*.app.php'
        );
        $extensions = new ExtensionsDiscovery('app', $mode);

        foreach ($enterprise_classes as $classpath) {
            enterprise_include_once(
                'include/class/'.basename($classpath)
            );
        }

        switch ($mode) {
            case 'DB2':
                $classname_selected = 'DB2';
            break;

            case 'SAP':
                $classname_selected = 'SAP';
            break;

            case 'vmware':
                $classname_selected = 'VMware';
            break;

            case 'mysql':
                $classname_selected = 'MySQL';
            break;

            case 'oracle':
                $classname_selected = 'Oracle';
            break;

            case 'MicrosoftSQLServer':
                $classname_selected = 'MicrosoftSQLServer';
            break;

            default:
                $classname_selected = null;
            break;
        }

        // Else: class not found pseudo exception.
        if ($classname_selected !== null) {
            $wiz = new $classname_selected($this->page);
            // Check if app has been migrated.
            if (method_exists($wiz, 'isMigrated') === true) {
                if ($wiz->isMigrated() === true) {
                    ui_print_info_message(__('This legacy app has been migrated to new discovery 2.0 system'));
                    return false;
                }
            }

            $result = $wiz->run();
            if (is_array($result) === true) {
                return $result;
            }
        }

        if ($classname_selected === null) {
            if ($mode !== null) {
                // Load extension if exist.
                $extensions->run();
                return;
            }

            // Load classes and print selector.
            $wiz_data = [];
            foreach ($enterprise_classes as $classpath) {
                $classname = basename($classpath, '.app.php');
                $obj = new $classname();

                if (method_exists($obj, 'isMigrated') === true) {
                    if ($obj->isMigrated() === true) {
                        continue;
                    }
                }

                $wiz_data[] = $obj->load();
            }

            $wiz_data = array_merge($wiz_data, $extensions->loadExtensions());

            $this->prepareBreadcrum(
                [
                    [
                        'link'  => ui_get_full_url(
                            'index.php?sec=gservers&sec2=godmode/servers/discovery'
                        ),
                        'label' => __('Discovery'),
                    ],
                    [
                        'link'     => ui_get_full_url(
                            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=app'
                        ),
                        'label'    => __('Applications'),
                        'selected' => true,
                    ],
                ]
            );

            // Header.
            ui_print_page_header(
                __('Applications'),
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

            Wizard::printBigButtonsList($wiz_data);

            $not_defined_extensions = $extensions->loadExtensions(true);

            $output = html_print_div(
                [
                    'class'   => 'agent_details_line',
                    'content' => ui_toggle(
                        Wizard::printBigButtonsList($not_defined_extensions, true),
                        '<span class="subsection_header_title">'.__('Not installed').'</span>',
                        'not_defined_apps',
                        'not_defined_apps',
                        false,
                        true,
                        '',
                        '',
                        'box-flat white_table_graph w100p'
                    ),
                ],
            );

            echo $output;

            echo '<div class="app_mssg"><i>*'.__('All company names used here are for identification purposes only. Use of these names, logos, and brands does not imply endorsement.').'</i></div>';
        }

        return $result;
    }


    /**
     * Check if section have extensions.
     *
     * @return boolean Return true if section is empty.
     */
    public function isEmpty()
    {
        $extensions = new ExtensionsDiscovery('app');
        $listExtensions = $extensions->getExtensionsApps();
        if ($listExtensions > 0 || enterprise_installed() === true) {
            return false;
        } else {
            return true;
        }
    }


}
