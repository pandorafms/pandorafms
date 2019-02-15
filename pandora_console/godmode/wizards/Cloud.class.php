<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Cloud
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

/**
 * Undocumented class
 */
class Cloud extends Wizard
{

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $values = [];

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $result;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $msg;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $icon;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $label;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $url;

    /**
     * Stores all needed parameters to create a recon task.
     *
     * @var array
     */
    public $task;


    /**
     * Undocumented function.
     *
     * @param integer $page  Start page, by default 0.
     * @param string  $msg   Mensajito.
     * @param string  $icon  Mensajito.
     * @param string  $label Mensajito.
     *
     * @return class Cloud
     */
    public function __construct(
        int $page=0,
        string $msg='Foo',
        string $icon='cloud.png',
        string $label='Cloud'
    ) {
        $this->setBreadcrum([]);

        $this->task = [];
        $this->msg = $msg;
        $this->icon = $icon;
        $this->label = $label;
        $this->page = $page;
        $this->url = ui_get_full_url(
            'index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=cloud'
        );

        return $this;
    }


    /**
     * Run AmazonWS class. Entry point.
     *
     * @return void
     */
    public function run()
    {
        global $config;

        // Load styles.
        parent::run();

        $mode = get_parameter('mode', null);

        if ($mode === null) {
            $this->setBreadcrum(['<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=cloud">Cloud</a>']);
            $this->printHeader();

            echo '<a href="'.$this->url.'&mode=amazonws" alt="importcsv">Amazon WS</a>';

            return;
        }

        if ($mode == 'amazonws') {
            $this->setBreadcrum(
                [
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=cloud">Cloud</a>',
                    '<a href="index.php?sec=gservers&sec2=godmode/servers/discovery&wiz=cloud&mode=amazonaws">Amazon AWS</a>',
                ]
            );
            $this->printHeader();
            return $this->runAmazonAWS();
        }

        return null;
    }


    /**
     * Checks if environment is ready,
     * returns array
     *   icon: icon to be displayed
     *   label: label to be displayed
     *
     * @return array With data.
     **/
    public function load()
    {
        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,
        ];
    }


    // /////////////////////////////////////////////////////////////////////////
    // Extra methods.
    // /////////////////////////////////////////////////////////////////////////


    /**
     * Amazon AWS pages manager.
     *
     * @return void
     */
    public function runAmazonAWS()
    {
        global $config;

        check_login();

        if (! check_acl($config['id_user'], 0, 'PM')) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access Agent Management'
            );
            include 'general/noaccess.php';
            return;
        }

        // -------------------------------.
        // Page 0. wizard starts HERE.
        // -------------------------------.
        if (!isset($this->page) || $this->page == 0) {
            if (isset($this->page) === false
                || $this->page == 0
            ) {
                $this->printForm(
                    [
                        'form'   => [
                            'action' => '#',
                            'method' => 'POST',
                        ],
                        'inputs' => [
                            [
                                'label'     => __('AWS access key ID'),
                                'arguments' => [
                                    'name'  => 'aws_id',
                                    'value' => '',
                                    'type'  => 'text',
                                ],
                            ],
                            [
                                'label'     => __('AWS secret access key'),
                                'arguments' => [
                                    'name'  => 'aws_id',
                                    'value' => '',
                                    'type'  => 'text',
                                ],
                            ],
                            [
                                'arguments' => [
                                    'name'   => 'page',
                                    'value'  => ($this->page + 1),
                                    'type'   => 'hidden',
                                    'return' => true,
                                ],
                            ],
                            [
                                'arguments' => [
                                    'name'       => 'submit',
                                    'label'      => __('Validate'),
                                    'type'       => 'submit',
                                    'attributes' => 'class="sub wand"',
                                    'return'     => true,
                                ],
                            ],
                        ],
                    ]
                );
            }
        }

        if ($this->page == 1) {
            echo 'TODO';
            // TODOS.
        }

        if ($this->page == 100) {
            return [
                'result' => $this->result,
                'id'     => $this->id,
                'msg'    => $this->msg,
            ];
        }
    }


}
