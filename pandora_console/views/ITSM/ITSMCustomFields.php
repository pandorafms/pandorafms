<?php
/**
 * ITSM View dashboard
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage ITSM
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

global $config;

if (empty($error) === false) {
    ui_print_error_message($error);
}

if (empty($customFields) === true) {
    ui_print_info_message(
        [
            'no_close' => true,
            'message'  => __('Incidence type not fields'),
        ]
    );
} else {
    $output = '<div class="incidence-type-custom-fields">';
    foreach ($customFields as $field) {
        $options = [
            'name'     => 'custom-fields['.$field['idIncidenceTypeField'].']',
            'required' => $field['isRequired'],
            'return'   => true,
        ];

        $class = '';

        switch ($field['type']) {
            case 'COMBO':
                $options['type'] = 'select';
                $fieldsValues = explode(',', $field['comboValue']);
                $options['fields'] = array_combine($fieldsValues, $fieldsValues);
                $options['selected'] = ($fieldsData[$field['idIncidenceTypeField']] ?? null);
            break;

            case 'TEXT':
                $options['value'] = ($fieldsData[$field['idIncidenceTypeField']] ?? null);
                $options['type'] = 'text';
            break;

            case 'CHECKBOX':
                $options['checked'] = ($fieldsData[$field['idIncidenceTypeField']] ?? null);
                $options['type'] = 'checkbox';
            break;

            case 'DATE':
                $options['value'] = ($fieldsData[$field['idIncidenceTypeField']] ?? null);
                $options['type'] = 'text';
            break;

            case 'NUMERIC':
                $options['value'] = ($fieldsData[$field['idIncidenceTypeField']] ?? null);
                $options['type'] = 'number';
            break;

            case 'TEXTAREA':
                $options['value'] = ($fieldsData[$field['idIncidenceTypeField']] ?? null);
                $options['type'] = 'textarea';
                $options['rows'] = 4;
                $options['columns'] = 0;
                $class = 'incidence-type-custom-fields-textarea';
            break;

            default:
                // Not posible.
            break;
        }

        $output .= html_print_label_input_block(
            $field['label'],
            html_print_input($options),
            ['div_class' => $class]
        );
    }

    $output .= '</div>';

    echo $output;
}
