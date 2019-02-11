<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
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

        // In home
        $items['tactical'] = [
            'name'      => __('Tactical view'),
            'filename'  => 'tactical.php',
            'menu_item' => true,
            'icon'      => 'tactical_view',
        ];
        $items['events'] = [
            'name'      => __('Events'),
            'filename'  => 'events.php',
            'menu_item' => true,
            'icon'      => 'events',
        ];
        $items['groups'] = [
            'name'      => __('Groups'),
            'filename'  => 'groups.php',
            'menu_item' => true,
            'icon'      => 'groups',
        ];

        if (!$system->getConfig('metaconsole')) {
            $items['alerts'] = [
                'name'      => __('Alerts'),
                'filename'  => 'alerts.php',
                'menu_item' => true,
                'icon'      => 'alerts',
            ];

            $items['agents'] = [
                'name'      => __('Agents'),
                'filename'  => 'agents.php',
                'menu_item' => true,
                'icon'      => 'agents',
            ];
            $items['modules'] = [
                'name'      => __('Modules'),
                'filename'  => 'modules.php',
                'menu_item' => true,
                'icon'      => 'modules',
            ];
            $items['visualmaps'] = [
                'name'      => __('Visual consoles'),
                'filename'  => 'visualmaps.php',
                'menu_item' => true,
                'icon'      => 'visual_console',
            ];

            // Not in home
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
            $items['visualmap'] = [
                'name'      => __('Visualmap'),
                'filename'  => 'visualmap.php',
                'menu_item' => false,
                'icon'      => '',
            ];
        }

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
                    'icon' => $data['icon'],
                    'pos'  => 'right',
                    'text' => $data['name'],
                    'href' => "index.php?page=$page",
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
                    'icon' => 'back',
                    'pos'  => 'left',
                    'text' => __('Logout'),
                    'href' => 'index.php?action=logout',
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
                'placeholder' => __('Global search'),
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
