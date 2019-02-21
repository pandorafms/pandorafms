<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
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

require_once __DIR__.'/Wizard.main.php';
require_once $config['homedir'].'/include/functions_users.php';

/**
 * Defined as wizard to guide user to explore running tasks.
 */
class ConsoleTasks extends Wizard
{

    /**
     * Number of pages to control breadcrum.
     *
     * @var integer
     */
    public $MAXPAGES = 1;

    /**
     * Labels for breadcrum.
     *
     * @var array
     */
    public $pageLabels = ['Base'];


    /**
     * Constructor.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Custom default mesage.
     * @param string  $icon  Custom icon.
     * @param string  $label Custom label.
     *
     * @return class HostDevices
     */
    public function __construct(
        int $page=0,
        string $msg='Default message. Not set.',
        string $icon='images/wizard/csv_image.svg',
        string $label='Console Tasks'
    ) {
        $this->setBreadcrum([]);

        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = __($label);
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=ctask'
        );

        return $this;
    }


    /**
     * Implements run method.
     *
     * @return mixed Returns null if wizard is ongoing. Result if done.
     */
    public function run()
    {
        global $config;

        // Load styles.
        parent::run();
        echo 'hola';

        for ($i = 0; $i < $this->MAXPAGES; $i++) {
            $breadcrum[] = [
                'link'     => 'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=app&mode=vmware&page='.$i.'&task='.$this->task['id_rt'],
                'label'    => $this->label.' '.$this->pageLabels[$i],
                'selected' => (($i == $this->page) ? 1 : 0),
            ];
        }

        if ($this->page < $this->MAXPAGES) {
            // Avoid to print header out of wizard.
            $this->prepareBreadcrum($breadcrum);
            $this->printHeader();
        }

        $this->printGoBackButton();
        return null;

    }


    /**
     * Implements load method.
     *
     * @return mixed Skeleton for button.
     */
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,

        ];
    }


}
