<?php
/**
 * Welcome to Pandora FMS feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Order Interpreter
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

// Begin.
global $config;

require_once $config['homedir'].'/godmode/wizards/Wizard.main.php';
ui_require_css_file('order_interpreter');

/**
 * Class OrderInterpreter.
 */
class OrderInterpreter extends Wizard
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = ['getResult'];

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;


    /**
     * Generates a JSON error.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function error($msg)
    {
        echo json_encode(
            ['error' => $msg]
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
        global $config;

        // Check access.
        check_login();

        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Constructor.
     *
     * @param string $ajax_controller Controller.
     *
     * @return object
     * @throws Exception On error.
     */
    public function __construct(
        $ajax_controller='include/ajax/order_interpreter'
    ) {
        global $config;
        $this->ajaxController = $ajax_controller;

        $this->pages_menu = [
            [
                'name' => __('Tactical View'),
                'icon' => ui_get_full_url(
                    'images/menu/monitoring.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=view&sec2=operation/agentes/tactical'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'AR'
                ) || check_acl(
                    $config['id_user'],
                    0,
                    'AW'
                ),
            ],
            [
                'name' => __('Agent Management'),
                'icon' => ui_get_full_url(
                    'images/menu/resources.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'AW'
                ) && check_acl(
                    $config['id_user'],
                    0,
                    'AD'
                ),
            ],
            [
                'name' => __('General Setup'),
                'icon' => ui_get_full_url(
                    'images/menu/settings.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=general&sec2=godmode/setup/setup&section=general'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'PM'
                ) || is_user_admin(
                    $config['id_user']
                ),
            ],
            [
                'name' => __('Manage Policies'),
                'icon' => ui_get_full_url(
                    'images/menu/configuration.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gmodules&sec2=enterprise/godmode/policies/policies'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'AW'
                ),
            ],
            [
                'name' => __('List Alerts'),
                'icon' => ui_get_full_url(
                    'images/menu/alerts.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=galertas&sec2=godmode/alerts/alert_list'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'LW'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'AD'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'LM'
                ),
            ],
            [
                'name' => __('View Events'),
                'icon' => ui_get_full_url(
                    'images/menu/events.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=eventos&sec2=operation/events/events'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'ER'
                ) ||
                check_acl(
                    $config['id_user'],
                    0,
                    'EW'
                ) ||
                check_acl(
                    $config['id_user'],
                    0,
                    'EM'
                ),
            ],
            [
                'name' => __('Dashboard'),
                'icon' => ui_get_full_url(
                    'images/menu/reporting.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=reporting&sec2=operation/dashboard/dashboard'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'RR'
                ),
            ],
            [
                'name' => __('Visual Console'),
                'icon' => ui_get_full_url(
                    'images/menu/network.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=network&sec2=godmode/reporting/map_builder'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'VR'
                ),
            ],
            [
                'name' => __('Manage Servers'),
                'icon' => ui_get_full_url(
                    'images/menu/servers.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gservers&sec2=godmode/servers/modificar_server'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'AW'
                ),
            ],
            [
                'name' => __('Edit User'),
                'icon' => ui_get_full_url(
                    'images/menu/users.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=workspace&sec2=operation/users/user_edit'
                ),
                'acl'  => true,
            ],
            [
                'name' => __('Tree View'),
                'icon' => ui_get_full_url(
                    'images/menu/monitoring.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=view&sec2=operation/tree'
                ),
                'acl'  => true,
            ],
            [
                'name' => __('Network Component'),
                'icon' => ui_get_full_url(
                    'images/menu/configuration.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gmodules&sec2=godmode/modules/manage_network_components'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'PM'
                ),
            ],
            [
                'name' => __('Task List'),
                'icon' => ui_get_full_url(
                    'images/menu/discovery.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=discovery&sec2=godmode/servers/discovery&wiz=tasklist'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'AR'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'AW'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'AM'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'RR'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'RW'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'RM'
                )
                || check_acl(
                    $config['id_user'],
                    0,
                    'PM'
                ),
            ],
            [
                'name' => __('Warp Update'),
                'icon' => ui_get_full_url(
                    'images/menu/warp_update.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=setup'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'PM'
                ) && is_user_admin($config['id_user']),
            ],
            [
                'name' => __('Manage Agent Groups'),
                'icon' => ui_get_full_url(
                    'images/menu/users.svg'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gagente&sec2=godmode/groups/group_list&tab=groups'
                ),
                'acl'  => check_acl(
                    $config['id_user'],
                    0,
                    'PM'
                ),
            ],

        ];

    }


    /**
     * Method to print order interpreted on header search input.
     *
     * @return void
     */
    public function getResult()
    {
        global $config;

        // Take value from input search.
        $text = get_parameter('text', '');
        $enterprise = (bool) get_parameter('enterprise', false);
        $iterator = 0;
        $more_results = 0;

        if ($text !== '') {
            echo '<div class="show_result_interpreter">';
            echo '<ul id="result_items">';

            foreach ($this->pages_menu as $key => $value) {
                if (preg_match(
                    '/.*'.io_safe_output($text).'.*/i',
                    __('GO TO '.$value['name'])
                ) && $value['acl']
                ) {
                    if ($iterator <= 9 && $this->canShowItem($enterprise, $this->pages_menu[$key]['url'])) {
                        echo '<li class="list_found" name="'.$iterator.'" id="'.$iterator.'">';
                        echo '
                        <span class="invert_filter"> Go to </span> &nbsp;
                        <img src="'.$this->pages_menu[$key]['icon'].'">';
                        echo '&nbsp;
                        <a href="'.$this->pages_menu[$key]['url'].'">
                        '.$value['name'].'</a><br>';
                    }

                    $iterator++;

                    if ($iterator > 10) {
                        $more_results++;
                    }
                }
            }

            if ($iterator > 9) {
                echo '</li>';
            }

            echo $this->loadJS();
            echo '</ul>';
            if ($iterator > 10) {
                echo '<div class="more_results"><span class="invert_filter">
                  + '.$more_results.' '.__('results found').'</span></div>';
            }

            if ($iterator === 0) {
                echo '<span class="invert_filter">'.__('Press enter to search').'</span>';
            }

            echo '</div>';
        }
    }


    /**
     * Determines if the element must be shown or not.
     *
     * @param boolean $isEnterprise Define if the console is Enterprise.
     * @param string  $url          Url of the element for select.
     *
     * @return boolean
     */
    private function canShowItem(bool $isEnterprise, string $url)
    {
        $canShow = false;

        $hasEnterpriseLocation = strpos($url, '&sec2=enterprise') !== false;

        if (($isEnterprise === false && $hasEnterpriseLocation === false) || $isEnterprise === true) {
            $canShow = true;
        }

        return $canShow;
    }


    /**
     * Load JS content.
     * function to create JS actions.
     *
     * @return string HTML code for javascript functionality.
     */
    public function loadJS()
    {
        ob_start();
        ?>
    <script type="text/javascript">
    
    </script>   
        <?php
        return ob_get_clean();
    }


}
