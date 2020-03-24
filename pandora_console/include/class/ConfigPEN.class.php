<?php
/**
 * PEN Configuration feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Wizard Setup
 * @version    0.0.1
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2020 Artica Soluciones Tecnologicas
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

global $config;

require_once $config['homedir'].'/include/class/HTML.class.php';
/**
 * Config PEN Class
 */
class ConfigPEN extends HTML
{

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $baseUrl;


    /**
     * Constructor
     */
    public function __construct()
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

        $this->baseUrl = 'index.php?sec=configuration_wizard_setup&sec2=godmode/modules/private_enterprise_numbers';

    }


    /**
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // Require specific CSS and JS.
        ui_require_css_file('wizard');
        ui_require_css_file('discovery');

        // Header section.
        // Breadcrums.
        $this->setBreadcrum([]);

        $this->prepareBreadcrum(
            [
                [
                    'link'     => '',
                    'label'    => __('Wizard Setup'),
                    'selected' => false,
                ],
                [
                    'link'     => $this->baseUrl,
                    'label'    => __('Private Enterprise Numbers'),
                    'selected' => true,
                ],
            ],
            true
        );

        ui_print_page_header(
            __('Private Enterprise Numbers'),
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

        $this->createMainTable();

    }


    /**
     * Undocumented function
     *
     * @return void
     */
    private function createMainTable()
    {
        global $config;
        // Get the count of PENs.
        $countPENs = db_get_value(
            'count(*)',
            'tnetwork_profile'
        );

        // Get all the data.
        $resultPENs = db_get_all_rows_filter(
            'tnetwork_profile',
            [
                'order' => 'id_np',
                'limit' => $config['block_size'],
            ]
        );

        hd($resultPENs);

        ui_pagination($countPENs, false, $this->offset);
        // Create the table with Module Block list.
        $table = new StdClasS();
        $table->class = 'databox data';
        $table->width = '75%';
        $table->styleTable = 'margin: 2em auto 0;border: 1px solid #ddd;background: white;';
        $table->rowid = [];
        $table->data = [];

        $table->cellpadding = 0;
        $table->cellspacing = 0;
        $table->width = '100%';
        $table->class = 'info_table';

        $table->head = [];
        $table->head[0] = html_print_checkbox('all_delete', 0, false, true, false);
        $table->head[1] = __('PEN');
        $table->head[2] = __('Manufacturer ID');
        $table->head[3] = __('Description');
        $table->head[4] = '<span style="margin-right:7%;">'.__('Action').'</span>';

        $table->size = [];
        $table->size[0] = '20px';
        $table->size[1] = '10%';
        $table->size[2] = '25%';
        $table->size[4] = '70px';

        $table->align = [];
        $table->align[3] = 'left';

        $table->data = [];

        foreach ($resultPENs as $row) {
            $data = [];
            $data[0] = html_print_checkbox_extended('delete_multiple[]', $row['id_np'], false, false, '', 'class="check_delete"', true);
            $data[1] = '<span id="pen_id_'.$row['id_np'].'" style="padding: 5px;" contenteditable="false">'.$row['id_np'].'</span>';
            $data[2] = '<span id="pen_name_'.$row['id_np'].'" style="padding: 5px;" contenteditable="false">'.$row['name'].'</span>';
            $data[3] = '<span id="pen_desc_'.$row['id_np'].'" style="padding: 5px;" contenteditable="false">'.ui_print_truncate_text(io_safe_output($row['description']), 'description', true, true, true, '[&hellip;]').'</span>';
            $table->cellclass[][3] = 'action_buttons';
            $data[4] = html_print_input_image(
                'edit_pen_'.$row['id_np'].'_',
                'images/edit.png',
                $row['id_np'],
                'max-width: 27px;',
                true,
                [
                    'title'   => 'Edit',
                    'onclick' => 'javascript:modifyPENLine(event)',
                ]
            );
            $data[4] .= html_print_input_image(
                'delete_pen_'.$row['id_np'].'_',
                'images/cross.png',
                $row['id_np'],
                '',
                true,
                [
                    'title'   => 'Delete PEN',
                    'onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                ]
            );

            array_push($table->data, $data);
        }

        html_print_table($table);

    }


}
