<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * List of visual consoles, for mobile view.
 *
 * @category   Common Class
 * @package    Pandora FMS
 * @subpackage OpenSource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
ob_start();
require_once '../include/functions_visual_map.php';
ob_get_clean();
// Fixed unused javascript code.

/**
 * Class to generate a list of current visual consoles defined.
 */
class Visualmaps
{

    /**
     * ACL allowed.
     *
     * @var boolean
     */
    private $allowed = false;

    /**
     * Perms needed to access this feature.
     *
     * @var string
     */
    private $acl = 'VR';

    /**
     * Default filters.
     *
     * @var array
     */
    private $defaultFilters = [];

    /**
     * Group.
     *
     * @var integer
     */
    private $group = 0;

    /**
     * Type. Something about filtering.
     *
     * @var boolean
     */
    private $type = 0;

    /**
     * CV favourites.
     *
     * @var boolean
     */
    private $favourite = true;


    /**
     * Builder.
     */
    public function __construct()
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->allowed = true;
        } else {
            $this->allowed = false;
        }
    }


    /**
     * Prepare filters for current view.
     *
     * @return void
     */
    private function getFilters()
    {
        $system = System::getInstance();
        $user = User::getInstance();

        $this->defaultFilters['group'] = true;
        $this->defaultFilters['type'] = true;

        $this->group = (int) $system->getRequest('group', __('Group'));
        if (!$user->isInGroup($this->acl, $this->group)) {
            $this->group = 0;
        }

        if (($this->group === __('Group')) || ($this->group == 0)) {
            $this->group = 0;
        } else {
            $this->default = false;
            $this->defaultFilters['group'] = false;
        }

        $this->type = $system->getRequest('type', __('Type'));
        if (($this->type === __('Type')) || ($this->type === '0')) {
            $this->type = '0';
        } else {
            $this->default = false;
            $this->defaultFilters['type'] = false;
        }
    }


    /**
     * Run view.
     *
     * @return void
     */
    public function show()
    {
        if (!$this->allowed) {
            $this->show_fail_acl();
        } else {
            $this->getFilters();
            $this->show_visualmaps();
        }
    }


    /**
     * Show a message about failed ACL access.
     *
     * @return void
     */
    private function show_fail_acl()
    {
        $error['type'] = 'onStart';
        $error['title_text'] = __('You don\'t have access to this page');
        $error['content_text'] = System::getDefaultACLFailText();

        // Redirect to main page.
        if (class_exists('HomeEnterprise') === true) {
            $home = new HomeEnterprise();
        } else {
            $home = new Home();
        }

        $home->show($error);
    }


    /**
     * Show visual console list header.
     *
     * @return void
     */
    private function show_visualmaps()
    {
        $ui = Ui::getInstance();

        $ui->createPage();
        $ui->createDefaultHeader(
            __('Visual consoles'),
            $ui->createHeaderButton(
                [
                    'icon'  => 'ui-icon-back',
                    'pos'   => 'left',
                    'text'  => __('Back'),
                    'href'  => 'index.php?page=home',
                    'class' => 'header-button-left',
                ]
            )
        );
        $ui->showFooter(false);
        $ui->beginContent();
            $this->listVisualmapsHtml();

        $output = '<script type="text/javascript">';
        $output .= 'function loadVisualConsole(id) {';
        $output .= ' var dimensions = "&width="+$(window).width();';
        $output .= ' dimensions += "&height="+$(window).height();';
        $output .= ' window.location.href = "';
        $output .= ui_get_full_url('/', false, false, false);
        $output .= 'mobile/index.php?page=visualmap&id="';
        $output .= '+id+dimensions;';
        $output .= '};';
        $output .= '</script>';

        $ui->contentAddHtml($output);

        $ui->endContent();
        $ui->showPage();
    }


    /**
     * Show list of visual consoles.
     *
     * @return void
     */
    private function listVisualmapsHtml()
    {
        $system = System::getInstance();
        $this->favourite = (bool) $system->getRequest('favourite', true);
        $ui = Ui::getInstance();

        $visualmaps = visual_map_get_user_layouts(
            false,
            false,
            false,
            true,
            $this->favourite
        );

        if ($this->favourite === true) {
            $ui->contentAddHtml(
                $ui->createButton(
                    [
                        'icon'  => '',
                        'pos'   => 'right',
                        'text'  => __('All visual consoles'),
                        'href'  => 'index.php?page=visualmaps&favourite=0',
                        'class' => 'visual-console-button',
                    ]
                )
            );
        } else {
            $ui->contentAddHtml(
                $ui->createButton(
                    [
                        'icon'  => '',
                        'pos'   => 'right',
                        'text'  => __('Favourite visual consoles'),
                        'href'  => 'index.php?page=visualmaps&favourite=1',
                        'class' => 'visual-console-button',
                    ]
                )
            );
        }

        $ui->contentAddHtml('<div class="hr-full"></div>');

        if (empty($visualmaps) === true) {
            $ui->contentAddHtml('<p class="no-data">'.__('There are no favorite maps to show').'</p>');
        } else {
            $table = new Table();
            // Without header jquery.mobile crashes.
            $table->addHeader(['']);
            $table->id = 'list_visualmaps';
            foreach ($visualmaps as $map) {
                $link = '<a class="ui-link" data-ajax="false" ';
                $link .= ' href="#" onclick="loadVisualConsole(';
                $link .= $map['id'].')">'.io_safe_output($map['name']).'</a>';

                $row = $link;
                $row .= ui_print_group_icon(
                    $map['id_group'],
                    true,
                    'groups_small',
                    '',
                    false
                );
                $table->addRow([ $map['id'].' flex-center' => $row]);
            }

            $ui->contentAddHtml('<div class="white-card p-tb-0px p-lr-0px">');
            $ui->contentAddHtml($table->getHTML());
            $ui->contentAddHtml('</div>');
            $ui->contentAddLinkListener('list_visualmaps');
        }
    }


}
