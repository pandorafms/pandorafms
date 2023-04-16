<?php
/**
 * Cluster View: Edit
 *
 * @category   View
 * @package    Pandora FMS
 * @subpackage Cluster View
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
// Prepare header and breadcrums.
$i = 0;
$bc = [];
$extra = '&op='.$wizard->operation;

if ($wizard->id !== null) {
    $extra .= '&id='.$wizard->id;
}

$bc[] = [
    'link'     => $wizard->parentUrl,
    'label'    => __('Cluster list'),
    'selected' => false,
];

$labels = $wizard->getLabels();
foreach ($labels as $key => $label) {
    $bc[] = [
        'link'     => $wizard->url.(($key >= 0) ? $extra.'&page='.$key : ''),
        'label'    => __($label),
        'selected' => ($wizard->page == $key),
    ];
}

$wizard->prepareBreadcrum($bc);

$header_str = __(ucfirst($wizard->getOperation())).' ';
$header_str .= (($cluster->name() !== null) ? $cluster->name() : __('cluster '));
$header_str .= ' &raquo; '.__($labels[$wizard->page]);

// Header.
$buttons = [];

$main_page = '<a href="'.$wizard->parentUrl.'">';
$main_page .= html_print_image(
    'images/logs@svg.svg',
    true,
    [
        'title' => __('Cluster list'),
        'class' => 'main_menu_icon invert_filter',
    ]
);
$main_page .= '</a>';

$buttons = [
    [
        'active' => false,
        'text'   => $main_page,
    ],
];

if ($cluster !== null) {
    if ($cluster->id() !== null) {
        $view = '<a href="'.$wizard->parentUrl.'&op=view&id='.$cluster->id().'">';
        $view .= html_print_image(
            'images/details.svg',
            true,
            [
                'title' => __('View this cluster'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        $view .= '</a>';

        $buttons[] = [
            'active' => false,
            'text'   => $view,
        ];
    }
}

ui_print_page_header(
    $header_str,
    '',
    false,
    'cluster_view',
    true,
    // Buttons.
    $buttons,
    false,
    '',
    GENERIC_SIZE_TEXT,
    '',
    $wizard->printHeader(true)
);

// Check if any error ocurred.
if (empty($wizard->errMessages) === false) {
    foreach ($wizard->errMessages as $msg) {
        ui_print_error_message(__($msg));
    }
}

$buttons_input = '';
if (empty($form) === false) {
    // Print form (prepared in ClusterWizard).
    $submit = $form['submit-external-input'];
    unset($form['submit-external-input']);

    HTML::printForm($form, false, ($wizard->page < 6));
    $buttons_input .= HTML::printInput($submit);
}

// Print always go back button.
$buttons_input .= HTML::printForm($wizard->getGoBackForm(), true);

html_print_action_buttons($buttons_input);
