<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;

require_once $config['homedir'].'/include/class/HTML.class.php';

class ManageBlock extends HTML
{

    private $ajax_controller;


    public function __construct($ajax_controller)
    {
        global $config;

        // Check access.
        check_login();

        if (! check_acl($config['id_user'], 0, 'AR')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access event viewer'
            );

            if (is_ajax()) {
                echo json_encode(['error' => 'noaccess']);
            }

            include 'general/noaccess.php';
            exit;
        }

        $this->ajaxController = $ajax_controller;

        $this->setBreadcrum([]);

        return $this;
    }


    /**
     * Run MiFuncionalidad (main page).
     *
     * @return void
     */
    public function run()
    {
        $this->prepareBreadcrum(
            [
                [
                    'link'     => 'mishuevos',
            // $this->url,
                    'label'    => __('Configuration'),
                    'selected' => 0,
                ],
                [
                    'link'     => 'url',
                // $this->url,
                    'label'    => __('Module Blocks'),
                    'selected' => 1,
                ],
            ],
            true
        );

        ui_print_page_header(
            __('Manage module blocks'),
            '',
            false,
            '',
            true,
            '',
            false,
            '',
            GENERIC_SIZE_TEXT,
            '',
            $this->printHeader(true)
        );

        // $this->printForm(
        // [
        // 'form'   => $form,
        // 'inputs' => $inputs,
        // ],
        // true
        // );

    }


}
