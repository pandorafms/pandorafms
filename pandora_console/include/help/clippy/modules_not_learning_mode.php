<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Clippy
 * @package    Pandora FMS
 * @subpackage Community
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


function clippy_modules_not_learning_mode()
{
    $return_tours = [];
    $return_tours['first_step_by_default'] = true;
    $return_tours['help_context'] = true;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour about the monitoring with a ping (step 3)
    // ------------------------------------------------------------------
    $return_tours['tours']['modules_not_learning_mode'] = [];
    $return_tours['tours']['modules_not_learning_mode']['steps'] = [];
    $return_tours['tours']['modules_not_learning_mode']['steps'][] = [
        'init_step_context' => true,
        'position'          => 'left',
        'intro'             => '<table><tr><td class="context_help_body">'.__('Please note that you have your agent setup to do not add new modules coming from the data XML.').'<br />'.__('That means if you have a local plugin or add manually new modules to the configuration file, you won\'t have it in your agent, unless you first create manually in the interface (with the exact name and type as coming in the XML file).').'<br />'.__('You should use the "normal" mode (non learn) only when you don\'t intend to add more modules to the agent.').'</td></tr></table>',
    ];
    $return_tours['tours']['modules_not_learning_mode']['conf'] = [];
    $return_tours['tours']['modules_not_learning_mode']['conf']['autostart'] = false;
    $return_tours['tours']['modules_not_learning_mode']['conf']['show_bullets'] = 0;
    $return_tours['tours']['modules_not_learning_mode']['conf']['show_step_numbers'] = 0;
    // ==================================================================
    return $return_tours;
}
