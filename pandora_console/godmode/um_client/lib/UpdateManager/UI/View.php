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
namespace UpdateManager\UI;

/**
 * View class.
 */
class View
{


    /**
     * Render view.
     *
     * @param string     $view View to be loaded.
     * @param array|null $data Array data if necessary for view.
     *
     * @return void
     */
    public static function render(string $view, ?array $data=null)
    {
        if (is_array($data) === true) {
            extract($data);
        }

        $path = __DIR__.'/../../../views/';
        $page = $path.$view.'.php';

        if (file_exists($page) === true) {
            include $page;
        } else {
            echo 'view '.$view.' not found';
        }
    }


}
