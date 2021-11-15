<?php
/**
 * Loader for views.
 *
 * @category   Loader
 * @package    Pandora FMS
 * @subpackage Enterprise
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
namespace PandoraFMS;

use HTML;

global $config;

require_once $config['homedir'].'/include/class/HTML.class.php';

/**
 * View class. Extends HTML to allow print forms and inputs.
 */
class View extends HTML
{


    /**
     * Render view.
     *
     * @param string     $page Page load view.
     * @param array|null $data Array data if necessary for view.
     *
     * @return void
     */
    public static function render(string $page, ?array $data=null)
    {
        global $config;

        if (is_array($data) === true) {
            extract($data);
        }

        $open = $config['homedir'].'/views/'.$page.'.php';
        $ent = $config['homedir'].'/'.ENTERPRISE_DIR.'/views/'.$page.'.php';

        if (file_exists($ent) === true) {
            include $ent;
        } else if (file_exists($open) === true) {
            include $open;
        } else {
            ui_print_error_message(__('View %s not found', $page), true);
        }
    }


}
