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
ob_start();
require_once '../include/functions_visual_map.php';
ob_get_clean();
// Fixed unused javascript code.
class Visualmaps
{

    private $correct_acl = false;

    private $acl = 'VR';

    private $default = true;

    private $default_filters = [];

    private $group = 0;

    private $type = 0;

    private $list_types = null;


    function __construct()
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->correct_acl = true;
        } else {
            $this->correct_acl = false;
        }
    }


    private function getFilters()
    {
        $system = System::getInstance();
        $user = User::getInstance();

        $this->default_filters['group'] = true;
        $this->default_filters['type'] = true;

        $this->group = (int) $system->getRequest('group', __('Group'));
        if (!$user->isInGroup($this->acl, $this->group)) {
            $this->group = 0;
        }

        if (($this->group === __('Group')) || ($this->group == 0)) {
            $this->group = 0;
        } else {
            $this->default = false;
            $this->default_filters['group'] = false;
        }

        $this->type = $system->getRequest('type', __('Type'));
        if (($this->type === __('Type')) || ($this->type === '0')) {
            $this->type = '0';
        } else {
            $this->default = false;
            $this->default_filters['type'] = false;
        }
    }


    public function show()
    {
        if (!$this->correct_acl) {
            $this->show_fail_acl();
        } else {
            $this->getFilters();
            $this->show_visualmaps();
        }
    }


    private function show_fail_acl()
    {
        $error['type'] = 'onStart';
        $error['title_text'] = __('You don\'t have access to this page');
        $error['content_text'] = System::getDefaultACLFailText();
        if (class_exists('HomeEnterprise')) {
            $home = new HomeEnterprise();
        } else {
            $home = new Home();
        }

        $home->show($error);
    }


    private function show_visualmaps()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Visual consoles'),
            $ui->createHeaderButton(
                [
                    'icon' => 'back',
                    'pos'  => 'left',
                    'text' => __('Back'),
                    'href' => 'index.php?page=home',
                ]
            )
        );
        $ui->showFooter(false);
        $ui->beginContent();
            $this->listVisualmapsHtml();
        $ui->endContent();
        $ui->showPage();
    }


    private function listVisualmapsHtml()
    {
        $system = System::getInstance();
        $ui = Ui::getInstance();

        // Create filter
        $where = [];
        // Order by type field
        $where['order'] = 'type';

        if ($this->group != '0') {
            $where['id_group'] = $this->group;
        }

        if ($this->type != '0') {
            $where['type'] = $this->type;
        }

        // Only display maps of "All" group if user is administrator
        // or has "RR" privileges, otherwise show only maps of user group
        $id_user = $system->getConfig('id_user');
        $own_info = get_user_info($id_user);
        if ($own_info['is_admin'] || $system->checkACL($this->acl)) {
            $maps = visual_map_get_user_layouts();
        } else {
            $maps = visual_map_get_user_layouts($id_user, false, false, false);
        }

        if (empty($maps)) {
            $maps = [];
        }

        $list = [];
        foreach ($maps as $map) {
            $row = [];
            $row[__('Name')] = '<a class="ui-link" data-ajax="false" href="index.php?page=visualmap&id='.$map['id'].'">'.io_safe_output($map['name']).'</a>';
            // $row[__('Type')] = $map['type'];
            $row[__('Group')] = ui_print_group_icon($map['id_group'], true, 'groups_small', '', false);
            $list[] = $row;
        }

        if (count($maps) == 0) {
            $ui->contentAddHtml('<p style="color: #ff0000;">'.__('No maps defined').'</p>');
        } else {
            $table = new Table();
            $table->id = 'list_visualmaps';
            $table->importFromHash($list);
            $ui->contentAddHtml($table->getHTML());
        }

        $ui->contentAddLinkListener('list_visualmaps');
    }


}
