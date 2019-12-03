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
     * @param boolean $must_run        Must run or not.
     * @param string  $ajax_controller Controller.
     *
     * @return object
     * @throws Exception On error.
     */
    public function __construct(
        $ajax_controller='include/ajax/order_interpreter'
    ) {
        $this->ajaxController = $ajax_controller;
        $this->pages_name = [
            0 => __('Tactical View'),
            1 => __('Agent Management'),
            2 => __('List Alerts'),
            3 => __('Manage Policies'),
        ];
        $this->pages_url = [
            0 => ui_get_full_url(
                'index.php?sec=view&sec2=operation/agentes/tactical'
            ),
            1 => ui_get_full_url(
                'index.php?sec=gagente&sec2=godmode/agentes/modificar_agente'
            ),
            2 => ui_get_full_url(
                'index.php?sec=galertas&sec2=godmode/alerts/alert_list'
            ),
            3 => ui_get_full_url(
                'index.php?sec=gmodules&sec2=enterprise/godmode/policies/policies'
            ),
        ];

    }


    /**
     * Method to print order interpreted on header search input.
     *
     * @return array
     */
    public function getResult()
    {
        // Take value from input search.
        $text = get_parameter('text', '');
        $array_found = [];
        echo '<div id="result_order" class="show_result_interpreter">';
        echo '<ul>';
        foreach ($this->pages_name as $key => $value) {
            if (preg_match('/.*'.$text.'.*/', $value)) {
                echo '<li>';
                echo '<img src="http://localhost/pandora_console/images/arrow_right_green.png">';
                echo 'GO TO <a href="'.$this->pages_url[$key].'">'.$value.'</a><br>';
            }
        }

        echo '</ul></div';

    }


    /**
     * Load JS content.
     * function that enables the functions to the buttons when its action is
     *  completed.
     * Assign the url of each button.
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
