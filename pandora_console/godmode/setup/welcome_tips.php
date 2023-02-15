<?php
/**
 * Welcome Tips
 *
 * @category   Welcome Tips
 * @package    Pandora FMS
 * @subpackage Opensource
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
// Require needed class.
require_once $config['homedir'].'/include/class/TipsWindow.class.php';
$view = get_parameter('view', '');
$action = get_parameter('action', '');
try {
    $tipsWindow = new TipsWindow();
} catch (Exception $e) {
    echo '[TipsWindow]'.$e->getMessage();
    return;
}

if ($view === 'create') {
    if ($action === 'create') {
        $secure_input = get_parameter('secure_input', '');
        $id_lang = get_parameter('id_lang', '');
        $title = get_parameter('title', '');
        $text = get_parameter('text', '');
        $url = get_parameter('url', '');
        $enable = get_parameter_switch('enable', '');
        $errors = [];

        if (empty($id_lang) === true) {
            $errors[] = __('Language is empty');
        }

        if (empty($title) === true) {
            $errors[] = __('Title is empty');
        }

        if (empty($text) === true) {
            $errors[] = __('Text is empty');
        }

        if (count($errors) === 0) {
            $response = $tipsWindow->createTip($id_lang, $title, $text, $url, $enable);
            if ($response === false) {
                $errors[] = __('Error in insert data');
            }
        }

        $tipsWindow->viewCreate($errors);
    } else {
        $tipsWindow->viewCreate();
    }

    return;
}

$tipsWindow->draw();
