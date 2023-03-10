<?php
// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps
/**
 * Agent Inventory view.
 *
 * @category   Monitoring.
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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
require 'include/config.php';

// Check user credentials.
check_login();

if (! check_acl($config['id_user'], 0, 'AR') && ! check_acl($config['id_user'], 0, 'AW')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Agent Inventory view'
    );
    include 'general/noaccess.php';
    return;
}

global $id_agente;

$diff_view = (bool) get_parameter('diff_view', 0);
if ($diff_view === true) {
    // Show the diff.
    include 'enterprise/operation/agentes/agent_inventory.diff_view.php';

    return;
}


// Initialize data.
$module = (int) get_parameter('module_inventory_agent_view');
$utimestamp = (int) get_parameter('utimestamp', 0);
$search_string = (string) get_parameter('search_string');

$sqlGetData = sprintf(
    'SELECT *
	FROM tmodule_inventory, tagent_module_inventory
	WHERE tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
		AND id_agente = %d %s',
    $id_agente,
    ($module !== 0) ? 'AND tagent_module_inventory.id_module_inventory = '.$module : ''
);

$rows = db_get_all_rows_sql($sqlGetData);

if ($rows === false) {
    ui_print_empty_data(__('This agent has not modules inventory'));
    return;
}

// Get Module Inventory.
$sqlModuleInventoryAgentView = sprintf(
    'SELECT tmodule_inventory.id_module_inventory, tmodule_inventory.name
	    FROM tmodule_inventory, tagent_module_inventory
	    WHERE tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
        AND id_agente = %s',
    $id_agente
);

// Utimestamps.
$utimestamps = db_get_all_rows_sql(
    sprintf(
        'SELECT tagente_datos_inventory.utimestamp
            FROM tmodule_inventory, tagent_module_inventory, tagente_datos_inventory
            WHERE tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
            AND tagente_datos_inventory.id_agent_module_inventory = tagent_module_inventory.id_agent_module_inventory
            AND tagent_module_inventory.%s',
        ($module !== 0) ? 'id_module_inventory = '.$module : 'id_agente = '.$id_agente
    )
);

$utimestamps = (empty($utimestamps) === true) ? [] : extract_column($utimestamps, 'utimestamp');

$utimestampSelectValues = array_reduce(
    $utimestamps,
    function ($acc, $utimestamp) use ($config) {
        $acc[$utimestamp] = date($config['date_format'], $utimestamp);
        return $acc;
    },
    []
);

// Inventory module select.
$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filters';
$table->size = [];
$table->data = [];

$table->data[0][0] = __('Module');
$table->data[0][1] = html_print_select_from_sql(
    $sqlModuleInventoryAgentView,
    'module_inventory_agent_view',
    $module,
    'javascript:this.form.submit();',
    __('All'),
    0,
    true
);

$table->data[0][2] = __('Date');
$table->data[0][3] = html_print_select(
    $utimestampSelectValues,
    'utimestamp',
    $utimestamp,
    'javascript:this.form.submit();',
    __('Now'),
    0,
    true
);

$table->data[0][4] = __('Search');
$table->data[0][5] = html_print_input_text('search_string', $search_string, '', 25, 0, true);
$table->data[0][6] = html_print_submit_button(__('Search'), 'search_button', false, 'class="sub wand"', true);

// Show filters table.
echo sprintf(
    '<form method="post" action="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=inventory&id_agente=%s">%s</form>',
    $id_agente,
    html_print_table($table, true)
);

unset($table);

$idModuleInventory = null;
$rowTable = 1;
$printedTables = 0;

// Inventory module data.
foreach ($rows as $row) {
    if ($utimestamp > 0) {
        $data_row = db_get_row_sql(
            "SELECT data, timestamp
			FROM tagente_datos_inventory
			WHERE utimestamp <= '".$utimestamp."'
				AND id_agent_module_inventory = ".$row['id_agent_module_inventory'].'
			ORDER BY utimestamp DESC'
        );
        if ($data_row !== false) {
            $row['data'] = $data_row['data'];
            $row['timestamp'] = $data_row['timestamp'];
        }
    }

    if ($idModuleInventory != $row['id_module_inventory']) {
        if (isset($table) === true && $rowTable >= 1) {
            html_print_table($table);
            unset($table);
            $rowTable = 1;
            $printedTables++;
        }

        $table = new StdClass();
        $table->width = '98%';
        $table->align = [];
        $table->cellpadding = 4;
        $table->cellspacing = 4;
        $table->class = 'databox filters';
        $table->head = [];
        $table->head[0] = $row['name'].' - ('.date($config['date_format'], $row['utimestamp']).')';

        if ((bool) $row['block_mode'] === true) {
            $table->head[0] .= '&nbsp;&nbsp;&nbsp;<a href="index.php?sec=estado&sec2=operation/agentes/ver_agente&tab=inventory&id_agente='.$id_agente.'&utimestamp='.$utimestamp.'&id_agent_module_inventory='.$row['id_agent_module_inventory'].'&diff_view=1">'.html_print_image(
                'images/op_inventory.menu.png',
                true,
                [
                    'alt'   => __('Diff view'),
                    'title' => __('Diff view'),
                    'style' => 'vertical-align: middle;	opacity: 0.8;',
                ]
            ).'</a>';
        }

        $subHeadTitles = explode(';', io_safe_output($row['data_format']));

        $table->head_colspan = [];
        $table->head_colspan[0] = (1 + count($subHeadTitles));
        $total_fields = count($subHeadTitles);
        $table->rowspan = [];

        $table->data = [];

        $iterator = 0;

        foreach ($subHeadTitles as $titleData) {
            $table->data[0][$iterator] = $titleData;
            $table->cellstyle[0][$iterator] = 'background: #373737; color: #FFF;';

            $iterator++;
        }
    }

    if ($row['block_mode']) {
        $rowTable++;
        $table->data[$rowTable][0] = '<pre>'.$row['data'].'</pre>';
    } else {
        $arrayDataRowsInventory = explode(SEPARATOR_ROW, io_safe_output($row['data']));
        // SPLIT DATA IN ROWS
        // Remove the empty item caused by a line ending with a new line.
        $len = count($arrayDataRowsInventory);
        if (end($arrayDataRowsInventory) == '') {
            $len--;
            unset($arrayDataRowsInventory[$len]);
        }

        $iterator1 = 0;
        $numRowHasNameAgent = $rowTable;

        $rowPair = true;
        $iterator = 0;
        foreach ($arrayDataRowsInventory as $dataRowInventory) {
            $table->rowclass[$iterator] = ($rowPair === true) ? 'rowPair' : 'rowOdd';
            $rowPair = !$rowPair;
            $iterator++;

            // Because SQL query extract all rows (row1;row2;row3...) and only I want the row has
            // the search string.
            if ($search_string && preg_match('/'.io_safe_output($search_string).'/i', io_safe_output($dataRowInventory)) == 0) {
                continue;
            }

            if ($rowTable > $numRowHasNameAgent) {
                $table->data[$rowTable][0] = '';
            }

            $arrayDataColumnInventory = explode(SEPARATOR_COLUMN, $dataRowInventory);
            // SPLIT ROW IN COLUMNS.
            $iterator2 = 0;
            foreach ($arrayDataColumnInventory as $dataColumnInventory) {
                $table->data[$rowTable][$iterator2] = $dataColumnInventory;
                $iterator2++;
            }

            $iterator1++;
            $rowTable++;
        }

        if ($iterator1 > 5) {
            // PRINT COUNT TOTAL.
            $table->data[$rowTable][0] = '<b>'.__('Total').': </b>'.$iterator1;
            $rowTable++;
        }
    }

    $idModuleInventory = $row['id_module_inventory'];
}

if (isset($table) === true && $rowTable >= 1) {
    html_print_table($table);
    $printedTables++;
}

if ($printedTables === 0) {
    echo "<div class='nf'>".__('No data found.').'</div>';
}
