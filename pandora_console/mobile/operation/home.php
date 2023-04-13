<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
class Home
{

    protected $global_search = '';

    protected $pagesItems = [];


    function __construct()
    {
        $this->global_search = '';
    }


    public function getPagesItems()
    {
        if (empty($this->pagesItems)) {
            $this->loadPagesItems();
        }

        return $this->pagesItems;
    }


    protected function loadPagesItems()
    {
        $system = System::getInstance();

        $items = [];

        // In home.
        $items['tactical'] = [
            'name'      => __('Tactical view'),
            'filename'  => 'tactical.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-tactical_view ui-widget-icon-floatbeginning',
        ];
        $items['events'] = [
            'name'      => __('Events'),
            'filename'  => 'events.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-events ui-widget-icon-floatbeginning',
        ];
        $items['groups'] = [
            'name'      => __('Groups'),
            'filename'  => 'groups.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-groups ui-widget-icon-floatbeginning',
        ];

        if ((bool) $system->getConfig('legacy_vc', false) === false) {
            // Show Visual consoles only if new system is enabled.
            $items['visualmaps'] = [
                'name'      => __('Visual consoles'),
                'filename'  => 'visualmaps.php',
                'menu_item' => true,
                'icon'      => 'ui-icon-visual_console ui-widget-icon-floatbeginning',
            ];
        }

        $items['alerts'] = [
            'name'      => __('Alerts'),
            'filename'  => 'alerts.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-alerts ui-widget-icon-floatbeginning',
        ];

        $items['agents'] = [
            'name'      => __('Agents'),
            'filename'  => 'agents.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-agents ui-widget-icon-floatbeginning',
        ];

        $items['modules'] = [
            'name'      => __('Modules'),
            'filename'  => 'modules.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-modules ui-widget-icon-floatbeginning',
        ];

        $items['server_status'] = [
            'name'      => __('Server status'),
            'filename'  => 'server_status.php',
            'menu_item' => true,
            'icon'      => 'ui-icon-server-status ui-widget-icon-floatbeginning',
        ];

        if ((int) $system->getConfig('enterprise_installed', false) === 1) {
            $items['services'] = [
                'name'      => __('Services'),
                'filename'  => 'services.php',
                'menu_item' => true,
                'icon'      => 'ui-icon-services ui-widget-icon-floatbeginning',
            ];
        }

        // Not in home.
        $items['agent'] = [
            'name'      => __('Agent'),
            'filename'  => 'agent.php',
            'menu_item' => false,
            'icon'      => '',
        ];
        $items['module_graph'] = [
            'name'      => __('Module graph'),
            'filename'  => 'module_graph.php',
            'menu_item' => false,
            'icon'      => '',
        ];

        $this->pagesItems = $items;
    }


    protected function loadButtons($ui)
    {
        if (empty($this->pagesItems) && $this->pagesItems !== false) {
            $this->loadPagesItems();
        }

        foreach ($this->pagesItems as $page => $data) {
            if ($data['menu_item']) {
                $options = [
                    'icon'  => $data['icon'],
                    'pos'   => 'right',
                    'text'  => $data['name'],
                    'href'  => "index.php?page=$page",
                    'class' => $data['class'],
                ];
                $ui->contentAddHtml($ui->createButton($options));
            }
        }
    }


    public function show($error=false)
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        include_once $system->getConfig('homedir').'/include/functions_graph.php';

        $ui->createPage();
        if ($system->getRequest('hide_logout', 0)) {
            $left_button = null;
        } else {
            $left_button = $ui->createHeaderButton(
                [
                    'icon'  => 'ui-icon-back',
                    'pos'   => 'left',
                    'text'  => __('Logout'),
                    'href'  => 'index.php?action=logout',
                    'class' => 'header-button-left',
                ]
            );
        }

        $user_logged = '';
        $id_user = $system->getConfig('id_user');
        if (!empty($id_user)) {
            $user_logged = "<span id=\"user_logged\">$id_user</span>";
        }

        $ui->createHeader(__('Home'), $left_button, $user_logged);
        $ui->showFooter(false);
        $ui->beginContent();
            $ui->beginForm('index.php?page=agents');
            $options = [
                'name'        => 'free_search',
                'value'       => $this->global_search,
                'placeholder' => __('Agent search'),
            ];
            $ui->formAddInputSearch($options);
            $ui->endForm();

            // List of buttons
            $this->loadButtons($ui);

            if (!empty($error)) {
                $ui->addDialog($error);
            }

            $ui->endContent();
            $ui->showPage();
    }


}
