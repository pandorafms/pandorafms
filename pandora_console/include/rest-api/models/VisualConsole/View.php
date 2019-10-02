<?php
/**
 * Class to handle visual console interface (modals and any stuff).
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Visual Console - View
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
namespace Models\VisualConsole;
use Models\VisualConsole\Container as VisualConsole;

global $config;
require_once $config['homedir'].'/include/class/HTML.class.php';

/**
 * Global HTML generic class.
 */
class View extends \HTML
{


    /**
     * Generates a form for you <3
     *
     * @return string HTML code for Form.
     *
     * @throws Exception On error.
     */
    public function loadForm()
    {
        // Load desired form based on item type.
        $values = [];
        $item = null;
        $item_json = get_parameter('item', null);
        $item = json_decode(io_safe_output($item_json));

        $type = null;
        if (isset($item) === true) {
            $values = $item->itemProps;
            $type = $values->type;
        }

        $itemClass = VisualConsole::getItemClass($type);

        if (!isset($itemClass)) {
            throw new Exception(__('Item type not valid ['.$type.']'));
        }

        $form = [
            'action'   => '#',
            'id'       => 'modal_form',
            'onsubmit' => 'return false;',
            'class'    => 'discovery modal',
            'extra'    => 'autocomplete="new-password"',
        ];

        // Retrieve inputs.
        $inputs = $itemClass::getFormInputs($values);

        // Generate Form.
        return $this->printForm(
            [
                'form'   => $form,
                'inputs' => $inputs,
            ],
            true
        );

    }


    /**
     * Process a form.
     *
     * @return string JSON response.
     */
    public function processForm()
    {
        $item = json_decode($_REQUEST['item'])->itemProps;
        return json_encode(['error' => obhd($item)]);
    }


}
