<?php
/**
 * Massive Operations Functions
 *
 * @category   Configuration
 * @package    Pandora FMS
 * @subpackage Massive Operations
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


/**
 * Generate a action button for submit the form.
 *
 * @param string  $action       Action to send in form.
 * @param string  $buttonAction Action of the button: Create, Update or Delete.
 * @param string  $tableWidth   Set the table width for the container.
 * @param boolean $return       If true, return a formed string.
 *
 * @return string
 */
function attachActionButton(
    string $action,
    string $buttonAction,
    string $tableWidth,
    bool $return=false
) {
    switch ($buttonAction) {
        case 'add':
            $caption = 'Add';
            $class = 'add';
        break;

        case 'copy':
            $caption = 'Copy';
            $class = 'wand';
        break;

        case 'create':
            $caption = 'Create';
            $class = 'upd';
        break;

        case 'update':
            $caption = 'Update';
            $class = 'upd';
        break;

        case 'delete':
            $caption = 'Delete';
            $class = 'delete';
        break;

        default:
            // Do none.
        break;
    }

    return html_print_div(
        [
            'class'   => 'action-buttons',
            'style'   => sprintf('width: %s', $tableWidth),
            'content' => html_print_input_hidden(
                $action,
                1
            ).html_print_button(
                __($caption),
                'go',
                false,
                '',
                sprintf('class="sub %s"', $class),
                true
            ),
        ],
        $return
    );
}
