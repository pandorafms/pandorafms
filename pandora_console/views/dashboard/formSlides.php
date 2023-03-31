<?php
/**
 * Dashboards View header dashboard Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Dashboards
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

// Includes.
require_once $config['homedir'].'/include/class/HTML.class.php';

$form = [
    'id'       => 'slides-form',
    'action'   => $url,
    'onsubmit' => 'return false;',
    'class'    => 'modal-dashboard',
    'enctype'  => 'multipart/form-data',
    'method'   => 'POST',
];

$inputs[] = [
    'arguments' => [
        'type'  => 'hidden',
        'name'  => 'refr',
        'value' => $refr,
    ],
];

$inputs[] = [
    'arguments' => [
        'type'  => 'hidden',
        'name'  => 'slides',
        'value' => 1,
    ],
];

$inputs[] = [
    'arguments' => [
        'type'  => 'hidden',
        'name'  => 'pure',
        'value' => 1,
    ],
];

$fields = array_reduce(
    $dashboards,
    function ($carry, $item) {
        $carry[$item['id']] = $item['name'];
        return $carry;
    },
    []
);

$inputs[] = [
    'id'        => 'select-dashboard-slices',
    'arguments' => [
        'type'     => 'select',
        'fields'   => $fields,
        'name'     => 'slidesIds[]',
        'selected' => '',
        'return'   => true,
        'multiple' => true,
        'sort'     => false,
        'required' => 'required',
    ],
];

HTML::printForm(
    [
        'form'   => $form,
        'inputs' => $inputs,
    ]
);
