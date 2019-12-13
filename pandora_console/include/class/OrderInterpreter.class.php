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
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
        $this->ajaxController = $ajax_controller;

        $this->pages_menu = [
            0  => [
                'name' => __('Tactical View'),
                'icon' => ui_get_full_url(
                    'images/op_monitoring.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=view&sec2=operation/agentes/tactical'
                ),
            ],
            1  => [
                'name' => __('Agent Management'),
                'icon' => ui_get_full_url(
                    'images/gm_resources.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'
                ),
            ],
            2  => [
                'name' => __('General Setup'),
                'icon' => ui_get_full_url(
                    'images/gm_setup.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=general&sec2=godmode/setup/setup&section=general'
                ),
            ],
            3  => [
                'name' => __('Manage Policies'),
                'icon' => ui_get_full_url(
                    'images/gm_configuration.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gmodules&sec2=enterprise/godmode/policies/policies'
                ),
            ],
            4  => [
                'name' => __('List Alerts'),
                'icon' => ui_get_full_url(
                    'images/gm_alerts.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=galertas&sec2=godmode/alerts/alert_list'
                ),
            ],
            5  => [
                'name' => __('View Events'),
                'icon' => ui_get_full_url(
                    'images/op_events.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=eventos&sec2=operation/events/events'
                ),
            ],
            6  => [
                'name' => __('Dashboard'),
                'icon' => ui_get_full_url(
                    'images/op_reporting.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=reporting&sec2=enterprise/dashboard/dashboards'
                ),
            ],
            7  => [
                'name' => __('Visual Console'),
                'icon' => ui_get_full_url(
                    'images/op_network.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=network&sec2=godmode/reporting/map_builder'
                ),
            ],
            8  => [
                'name' => __('Manage Servers'),
                'icon' => ui_get_full_url(
                    'images/gm_servers.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gservers&sec2=godmode/servers/modificar_server'
                ),
            ],
            9  => [
                'name' => __('Edit User'),
                'icon' => ui_get_full_url(
                    'images/gm_users.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=workspace&sec2=operation/users/user_edit'
                ),
            ],
            10 => [
                'name' => __('Three View'),
                'icon' => ui_get_full_url(
                    'images/op_monitoring.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=view&sec2=operation/tree'
                ),
            ],
            11 => [
                'name' => __('Network Component'),
                'icon' => ui_get_full_url(
                    'images/gm_configuration.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=gmodules&sec2=godmode/modules/manage_network_components'
                ),
            ],
            12 => [
                'name' => __('Task List'),
                'icon' => ui_get_full_url(
                    'images/gm_discovery.menu.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=discovery&sec2=godmode/servers/discovery&wiz=tasklist'
                ),
            ],
            13 => [
                'name' => __('Update Manager'),
                'icon' => ui_get_full_url(
                    'images/um_messages.menu_gray.png'
                ),
                'url'  => ui_get_full_url(
                    'index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=setup'
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
        // Take value from input search.
        $text = get_parameter('text', '');
        $array_found = [];
        $iterator = 0;
        $more_results = 0;

        if ($text !== '') {
            echo '<div id="result_order" class="show_result_interpreter">';
            echo '<ul id="result_items">';

            foreach ($this->pages_menu as $key => $value) {
                if (preg_match(
                    '/.*'.io_safe_output($text).'.*/i',
                    __('GO TO '.$value['name'])
                )
                ) {
                    if ($iterator <= 9) {
                        echo '<li class="list_found" name="'.$iterator.'" id="'.$iterator.'">';
                        echo '
                        Go to &nbsp;
                        <img src="'.$this->pages_menu[$key]['icon'].'">';
                        echo '&nbsp;
                        <a href="'.$this->pages_menu[$key]['url'].'">
                        '.$value['name'].'</a><br>';
                    }

                    $iterator ++;

                    if ($iterator > 10) {
                        $more_results ++;
                    }
                }
            }

            if ($iterator > 9) {
                echo '</li>';
            }

            echo $this->loadJS();
            echo '</ul>';
            if ($iterator > 10) {
                echo '<div class="more_results">
                  + '.$more_results.' results found</div>';
            }

            echo '</div';
        }
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
