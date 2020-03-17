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
/**
 * Class ManageBlock
 */
class ManageBlock extends HTML
{

    /**
     * Var that contain very cool stuff
     *
     * @var string
     */
    private $ajaxController;

    /**
     * Undocumented function
     *
     * @param array $ajax_controller
     */
    private $countNetworkTemplates;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $offset;

    /**
     * Table with module blocks
     *
     * @var [type]
     */
    private $resultModuleBlocksTable;


    /**
     * Constructor
     *
     * @param string $ajax_controller Pues hace cosas to wapas.
     */
    public function __construct(string $ajax_controller)
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

        $this->offset = (int) get_parameter('offset', 0);

        $this->countNetworkTemplates = db_get_value(
            'count(*)',
            'tnetwork_profile'
        );

        $this->resultModuleBlocksTable = db_get_all_rows_filter(
            'tnetwork_profile',
            [
                'order'  => 'name',
                'limit'  => $config['block_size'],
                'offset' => $this->offset,
            ]
        );

        $this->ajaxController = $ajax_controller;

        return $this;
    }


    /**
     * Run main page.
     *
     * @return void
     */
    public function run()
    {
        // Header section.
        // Breadcrums.
        $this->setBreadcrum([]);

        $this->prepareBreadcrum(
            [
                [
                    'link'     => '',
                    'label'    => __('Configuration'),
                    'selected' => false,
                ],
                [
                    'link'     => $this->url,
                    'label'    => __('Module Blocks'),
                    'selected' => true,
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

        ui_pagination($this->countNetworkTemplates, false, $this->offset);

        echo $this->moduleBlockList();

        // $this->printForm(
        // [
        // 'form'   => $form,
        // 'inputs' => $inputs,
        // ],
        // true
        // );

    }


    /**
     * Undocumented function
     *
     * @return html Formed table
     */
    public function moduleBlockList()
    {
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
        ;
        $table->head[1] = __('Name');
        $table->head[2] = __('Description');
        $table->head[3] = '<span style="margin-right:7%;">'.__('Action').'</span>';
        $table->size = [];
        $table->size[0] = '20px';
        $table->size[2] = '65%';
        $table->size[3] = '15%';

        $table->align = [];
        $table->align[3] = 'left';

        $table->data = [];

        hd($this->resultModuleBlocksTable);

        foreach ($this->resultModuleBlocksTable as $row) {
            $data = [];
            $data[0] = $row['id_np'];
            $data[1] = '<a href="index.php?sec=gmodules&amp;sec2=godmode/modules/manage_network_templates_form&amp;id_np='.$row['id_np'].'">'.io_safe_output($row['name']).'</a>';
            $data[2] = 'description';
            // $data[2] = ui_print_truncate_text(io_safe_output($row['description']), 'description', true, true, true, '[&hellip;]');
            $table->cellclass[][3] = 'action_buttons';
            $data[3] = html_print_input_image(
                'delete_profile',
                'images/cross.png',
                $row['id_np'],
                '',
                true,
                ['onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;']
            );
            $data[3] .= html_print_input_image(
                'export_profile',
                'images/csv.png',
                $row['id_np'],
                '',
                true,
                ['title' => 'Export to CSV']
            );
            $data[3] = '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates'.'&delete_profile=1&delete_profile='.$row['id_np'].'" '.'onclick="if (!confirm(\''.__('Are you sure?').'\')) return false;">'.html_print_image('images/cross.png', true, ['title' => __('Delete')]).'</a>';
            $data[3] .= '<a href="index.php?sec=gmodules&sec2=godmode/modules/manage_network_templates'.'&export_profile='.$row['id_np'].'">'.html_print_image('images/csv.png', true, ['title' => __('Export to CSV')]).'</a>';

            array_push($table->data, $data);
        }

        $output = '<div style="margin-top: 40px; text-align: center;"><span style="font-size: 1.9em; font-family: "lato-bolder", "Open Sans", sans-serif !important;">'.__('Summary').'</span></div>';
        $output .= html_print_table($table, true).'</div>';

        return $output;
    }


}
