<?php
/**
 * Functions for Inventory view.
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
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas
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
function inventory_get_data(
    $agents_ids,
    $inventory_module_name,
    $utimestamp,
    $inventory_search_string='',
    $export_csv=false,
    $return_mode=false,
    $order_by_agent=false,
    $node='',
    $pagination_url_parameters=[],
    $regular_expression=''
) {
    global $config;

    $out = '';
    $where = [];

    array_push(
        $where,
        'tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory'
    );

    // Discart empty first position.
    if (isset($agents_ids[0]) === true && empty($agents_ids[0]) === true) {
        unset($agents_ids[0]);
    }

    // If there are no agents selected.
    if (empty($agents_ids) === true) {
        return ERR_NODATA;
    }

    if (array_search(-1, $agents_ids) === false) {
        array_push($where, 'id_agente IN ('.implode(',', $agents_ids).')');
    }

    if ($inventory_module_name[0] !== '0'
        && $inventory_module_name !== ''
        && $inventory_module_name !== 'all'
    ) {
        array_push($where, "tmodule_inventory.name IN ('".implode("','", (array) $inventory_module_name)."')");
    }

    if ($inventory_search_string != '') {
        array_push($where, "tagent_module_inventory.data LIKE '%".$inventory_search_string."%'");
    }

    $offset = (int) get_parameter('offset');

    $sql = 'SELECT *
		FROM tmodule_inventory, tagent_module_inventory 
		WHERE 
			'.implode(' AND ', $where).'
		ORDER BY tmodule_inventory.id_module_inventory LIMIT '.$offset.', '.$config['block_size'];

    $sql_count = 'SELECT COUNT(*)
        FROM tmodule_inventory, tagent_module_inventory 
        WHERE '.implode(' AND ', $where);

    $rows = db_get_all_rows_sql($sql);

    $count = db_get_sql($sql_count);

    // Prepare pagination.
    $url = sprintf(
        '?sec=estado&sec2=operation/inventory/inventory&agent_id=%s&agent=%s&id_group=%s&export=%s&module_inventory_general_view=%s&search_string=%s&utimestamp=%s&order_by_agent=%s&submit_filter=%d',
        $pagination_url_parameters['inventory_id_agent'],
        $pagination_url_parameters['inventory_agent'],
        $pagination_url_parameters['inventory_id_group'],
        $export_csv,
        $inventory_module_name,
        $inventory_search_string,
        $utimestamp,
        $order_by_agent,
        1
    );

    if (is_metaconsole() === true) {
        $url .= '&id_server='.$pagination_url_parameters['id_server'];
        $url .= '&inventory_id_server='.$pagination_url_parameters['inventory_id_server'];
    }

    $out .= ui_pagination($count, $url, $offset, 0, true);

    if (($rows == null) || (count($rows) == 0)) {
        if ($return_mode !== false) {
            return __('No changes found');
        }

        return ERR_NODATA;
    }

    if ($export_csv || $return_mode === 'csv') {
        $out_csv = '';
        $agent_inventory = [];

        foreach ($rows as $row) {
            if ($utimestamp > 0) {
                $data_row = db_get_row_sql(
                    'SELECT data, timestamp
                    FROM tagente_datos_inventory
                    WHERE utimestamp <= '.$utimestamp.'
                    AND id_agent_module_inventory = '.$row['id_agent_module_inventory'].' ORDER BY utimestamp DESC'
                );
                if ($data_row !== false) {
                    $row['data'] = $data_row['data'];
                    $row['timestamp'] = $data_row['timestamp'];
                }
            }

            if (!$order_by_agent) {
                $agent_name = db_get_value('alias', 'tagente', 'id_agente', $row['id_agente']);

                $out_csv .= __('Agent alias').' --> '.io_safe_output($agent_name)."\n";
                $out_csv .= __('Timestamp').' = '.$row['timestamp']."\n";
                $out_csv .= io_safe_output($row['data_format'])."\n";

                // Filter data by search string.
                if ($inventory_search_string !== '') {
                    $str = io_safe_output($row['data']);
                    $matches = [];
                    $re = '/.*'.$inventory_search_string.'.*\n/m';
                    if (preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0)) {
                        // Print the entire match result.
                        foreach ($matches as $match) {
                            $out_csv .= $match[0];
                        }

                        $out_csv .= "\n\n";
                    }
                } else {
                    $out_csv .= io_safe_output($row['data'])."\n\n";
                }
            } else {
                $agent_name = db_get_value('alias', 'tagente', 'id_agente', $row['id_agente']);
                $agent_inventory_temp = [];
                $agent_inventory[$agent_name][] = [
                    'name'         => $row['name'],
                    'data_formtat' => $row['data_format'],
                    'data'         => $row['data'],
                ];
            }
        }

        if ($order_by_agent) {
            if (empty($agent_inventory) === false) {
                foreach ($agent_inventory as $alias => $agent_data) {
                    $out_csv .= __('Agent alias').' --> '.io_safe_output($alias)."\n";
                    $out_csv .= __('Timestamp').' = '.$row['timestamp']."\n";

                    foreach ($agent_data as $data) {
                        $out_csv .= io_safe_output($data['name'])."\n";
                        $out_csv .= io_safe_output($data['data_format'])."\n";

                            // Filter data by search string.
                        if ($inventory_search_string !== '') {
                              $str = io_safe_output($data['data']);
                              $matches = [];
                              $re = '/.*'.$inventory_search_string.'.*\n/m';
                            if (preg_match_all($re, $str, $matches, PREG_SET_ORDER, 0)) {
                                // Print the entire match result.
                                foreach ($matches as $match) {
                                          $out_csv .= $match[0];
                                }

                                $out_csv .= "\n\n";
                            }
                        } else {
                              $out_csv .= io_safe_output($row['data'])."\n\n";
                        }
                    }
                }
            }
        }
    }

    if ($export_csv) {
        $name_file = 'inventory_'.md5(
            $inventory_module_name.$utimestamp.$inventory_search_string
        ).'.csv';
        file_put_contents(
            $config['attachment_store'].'/'.$name_file,
            $out_csv
        );

        echo "<a class='bolder' download='".$name_file."' href='".$config['homeurl'].'/attachment/'.$name_file."'>".__('Get CSV file').'</a>';
        return;
    } else if ($return_mode === 'csv') {
        return $out_csv;
    } else if ($return_mode === 'hash') {
        if ($utimestamp > 0) {
            $timestamp = db_get_value_sql(
                "SELECT timestamp
				FROM tagente_datos_inventory
				WHERE utimestamp = $utimestamp"
            );
        } else {
            $timestamp = db_get_value_sql(
                'SELECT timestamp
				FROM tagente_datos_inventory
				WHERE utimestamp = 
					(SELECT MAX(tagente_datos_inventory.utimestamp) 
					FROM tagente_datos_inventory, tmodule_inventory,
						tagent_module_inventory 
					WHERE '.implode(' AND ', $where).'
						AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
						AND tagent_module_inventory.id_agent_module_inventory = tagente_datos_inventory.id_agent_module_inventory)'
            );
        }

        $out_array = [];
        foreach ($rows as $k => $row) {
            $out_array[$k]['timestamp'] = $timestamp;
            $out_array[$k]['id_module_inventory'] = $row['id_module_inventory'];
            $out_array[$k]['id_os'] = $row['id_os'];
            $out_array[$k]['name'] = io_safe_output($row['name']);
            $out_array[$k]['description'] = io_safe_output($row['description']);
            $out_array[$k]['interpreter'] = $row['interpreter'];
            $out_array[$k]['id_agent_module_inventory'] = $row['id_agent_module_inventory'];
            $out_array[$k]['id_agente'] = $row['id_agente'];
            $agent_name = db_get_value('alias', 'tagente', 'id_agente', $row['id_agente']);
            $out_array[$k]['agent_name'] = $agent_name;
            $out_array[$k]['target'] = $row['target'];
            $out_array[$k]['interval'] = $row['interval'];
            $out_array[$k]['username'] = $row['username'];

            $items = explode(';', io_safe_output($row['data_format']));

            $data = [];
            if (empty($row['data']) === false) {
                $data_rows = explode("\n", io_safe_output($row['data']));
                $data = [];
                foreach ($data_rows as $data_row) {
                    $cells = explode(';', $data_row);

                    $temp_row = [];
                    $i = 0;
                    foreach ($cells as $cell) {
                        $temp_row[$items[$i]] = $cell;
                        $i++;
                    }

                    if ($regular_expression !== '') {
                        if (is_array(preg_grep('/'.$regular_expression.'/', $temp_row))) {
                            if (count(preg_grep('/'.$regular_expression.'/', $temp_row)) > 0) {
                                $data[] = $temp_row;
                            }
                        }
                    } else {
                        $data[] = $temp_row;
                    }
                }
            }

            $out_array[$k]['data'] = $data;
            $out_array[$k]['timestamp'] = io_safe_output($row['timestamp']);
            $out_array[$k]['flag'] = io_safe_output($row['flag']);
        }

        return $out_array;
    } else if ($return_mode === 'array') {
        $out_array = [];
        foreach ($rows as $k => $row) {
            $out_array[$k]['id_module_inventory'] = $row['id_module_inventory'];
            $out_array[$k]['id_os'] = $row['id_os'];
            $out_array[$k]['name'] = io_safe_output($row['name']);
            $out_array[$k]['description'] = io_safe_output($row['description']);
            $out_array[$k]['interpreter'] = $row['interpreter'];
            $out_array[$k]['data_format'] = $row['data_format'];
            $out_array[$k]['id_agent_module_inventory'] = $row['id_agent_module_inventory'];
            $out_array[$k]['id_agente'] = $row['id_agente'];
            $out_array[$k]['target'] = $row['target'];
            $out_array[$k]['interval'] = $row['interval'];
            $out_array[$k]['username'] = $row['username'];
            $out_array[$k]['data'] = '<![CDATA['.io_safe_output($row['data']).']]>';
            $out_array[$k]['timestamp'] = io_safe_output($row['timestamp']);
            $out_array[$k]['flag'] = io_safe_output($row['flag']);
        }

        if (empty($out_array) === true) {
            return __('No data found');
        }

        return $out_array;
    }

    $idModuleInventory = null;

    $rowTable = 1;

    // Timestamp filter only allowed in nodes for performance.
    if (is_metaconsole() === false) {
        if ($utimestamp > 0) {
            $timestamp = db_get_value_sql(
                "SELECT timestamp
                FROM tagente_datos_inventory
                WHERE utimestamp = $utimestamp"
            );
        } else {
            $timestamp = db_get_value_sql(
                'SELECT timestamp
                FROM tagente_datos_inventory
                WHERE utimestamp = 
                    (SELECT MAX(tagente_datos_inventory.utimestamp) 
                    FROM tagente_datos_inventory, tmodule_inventory,
                        tagent_module_inventory 
                    WHERE '.implode(' AND ', $where).'
                        AND tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
                        AND tagent_module_inventory.id_agent_module_inventory = tagente_datos_inventory.id_agent_module_inventory)'
            );
        }
    }

    // TODO: Workaround.
    $timestamp = 'Last';
    if (!$order_by_agent) {
        $countRows = 0;
        foreach ($rows as $row) {
            // Check for not show more elements that allowed in config.
            if ($countRows >= (int) $config['meta_num_elements']) {
                break;
            }

            $countRows++;

            // Continue.
            if (is_metaconsole() === false && $utimestamp > 0) {
                $data_row = db_get_row_sql(
                    "SELECT data, timestamp
											FROM tagente_datos_inventory
											WHERE utimestamp <= '".$utimestamp."'
											AND id_agent_module_inventory = ".$row['id_agent_module_inventory'].' ORDER BY utimestamp DESC'
                );
                if ($data_row !== false) {
                    $row['data'] = $data_row['data'];
                    $row['timestamp'] = $data_row['timestamp'];
                } else {
                    // Continue to next row in case there is no data for that timestamp.
                    continue;
                }
            }

            if ($idModuleInventory != $row['id_module_inventory']) {
                if (isset($table) === true) {
                    $out .= "<div class='overflow'>";
                    $out .= html_print_table($table, true);
                    $out .= '</div>';
                    unset($table);
                    $rowTable = 1;
                }

                $table = new stdClass();
                $table->width = '100%';
                $table->align = [];
                $table->cellpadding = 0;
                $table->cellspacing = 0;
                $table->class = 'info_table inventory_tables';
                $table->head = [];
                $table->head[0] = '<span>'.$row['name'].'</span> <span>'.html_print_image('images/timestamp.png', true, ['title' => __('Timestamp'), 'style' => 'vertical-align:middle']).' ('.$timestamp.')</span>';
                $table->headstyle[0] = 'text-align:center';

                $subHeadTitles = explode(';', io_safe_output($row['data_format']));

                $table->head_colspan = [];
                $table->head_colspan[0] = (2 + count($subHeadTitles));
                $total_fields = count($subHeadTitles);
                $table->rowspan = [];

                $table->data = [];

                $iterator = 1;

                $table->data[0][0] = __('Agent');
                foreach ($subHeadTitles as $titleData) {
                    $table->data[0][$iterator] = $titleData;
                    $iterator++;
                }

                $table->data[0][] = __('Timestamp');
                $iterator++;
            }

            // Setting for link the agent with the proper server.
            if (is_metaconsole() === true && empty($node) === false) {
                $loginHash = metaconsole_get_servers_url_hash($node);
                $urlToAgent = sprintf(
                    '%sindex.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=%s%s',
                    $node['server_url'],
                    $row['id_agente'],
                    $loginHash
                );
            } else {
                $urlToAgent = sprintf(
                    'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=%s',
                    $row['id_agente']
                );
            }

            $agent_name = db_get_value_sql(
                'SELECT alias
				FROM tagente
				WHERE id_agente = '.$row['id_agente']
            );

            $table->data[$rowTable][0] = html_print_anchor(
                [
                    'href'    => $urlToAgent,
                    'content' => '<strong>'.$agent_name.'</strong>',
                ],
                true
            );

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
                if ($rowPair === true) {
                    $table->rowclass[$iterator] = 'rowPair';
                } else {
                    $table->rowclass[$iterator] = 'rowOdd';
                }

                $rowPair = !$rowPair;
                $iterator++;

                // Because SQL query extract all rows (row1;row2;row3...) and only I want the row has
                // the search string.
                if ($inventory_search_string && preg_match('/'.io_safe_output($inventory_search_string).'/', ($dataRowInventory)) == 0) {
                    continue;
                }

                if ($rowTable > $numRowHasNameAgent) {
                    $table->data[$rowTable][0] = '';
                }

                $arrayDataColumnInventory = explode(SEPARATOR_COLUMN, $dataRowInventory);
                // SPLIT ROW IN COLUMNS.
                $iterator2 = 1;

                foreach ($arrayDataColumnInventory as $dataColumnInventory) {
                    $table->data[$rowTable][$iterator2] = $dataColumnInventory;
                    $iterator2++;
                }

                // Fill unfilled cells with empty string.
                $countArray = count($arrayDataColumnInventory);
                for ($i = 0; $i < ($total_fields - $countArray); $i++) {
                    $table->data[$rowTable][$iterator2] = '';
                    $iterator2++;
                }

                $table->data[$rowTable][$iterator2] = $row['timestamp'];

                $iterator1++;

                $rowTable++;
                if ($rowPair === true) {
                    $table->rowclass[$rowTable] = 'rowPair';
                } else {
                    $table->rowclass[$rowTable] = 'rowOdd';
                }
            }

            if ($rowPair === true) {
                $table->rowclass[$iterator] = 'rowPair';
            } else {
                $table->rowclass[$iterator] = 'rowOdd';
            }

            $rowPair = !$rowPair;
            if ($rowPair) {
                $table->rowclass[($iterator + 1)] = 'rowPair';
            } else {
                $table->rowclass[($iterator + 1)] = 'rowOdd';
            }

            if ($iterator1 > 5) {
                // PRINT COUNT TOTAL.
                $table->data[$rowTable][0] = '';
                $table->data[$rowTable][1] = '<b>'.__('Total').': </b>'.$iterator1;
                $countSubHeadTitles = count($subHeadTitles);
                for ($row_i = 2; $row_i <= $countSubHeadTitles; $row_i++) {
                    $table->data[$rowTable][$row_i] = '';
                }

                $rowTable++;
            }

            $idModuleInventory = $row['id_module_inventory'];
        }
    } else {
        $agent_data = [];
        foreach ($rows as $row) {
            $agent_data[$row['id_agente']][] = $row;
        }

        foreach ($agent_data as $id_agent => $rows) {
            $agent_name = db_get_value_sql(
                'SELECT alias
				FROM tagente
				WHERE id_agente = '.$id_agent
            );

            $out .= '<div id="container_left container_left_class">';
            $out .= '<h5>'.$agent_name.'</h5>';
            $out .= '<div id="tabla_elems" class="mx_height400 overflow-y center">';

            foreach ($rows as $row) {
                if ($utimestamp > 0) {
                    $data_row = db_get_row_sql(
                        "SELECT data, timestamp
												FROM tagente_datos_inventory
												WHERE utimestamp <= '".$utimestamp."'
												AND id_agent_module_inventory = ".$row['id_agent_module_inventory'].' ORDER BY utimestamp DESC'
                    );

                    if ($data_row !== false) {
                        $row['data'] = $data_row['data'];
                        $row['timestamp'] = $data_row['timestamp'];
                    } else {
                        continue;
                    }
                }

                $table = new stdClass();
                $table->colspan = [];
                if ($idModuleInventory != $row['id_module_inventory']) {
                    $table->width = '98%';
                    $table->align = [];
                    $table->styleTable = 'margin:0 auto; text-align:left;';
                    $table->cellpadding = 0;
                    $table->cellspacing = 0;
                    $table->class = 'databox data';
                    $table->head = [];
                    $table->head[0] = $row['name'].' - ('.$timestamp.')';
                    $table->headstyle[0] = 'text-align:center';

                    $subHeadTitles = explode(';', io_safe_output($row['data_format']));

                    $table->head_colspan = [];
                    $table->head_colspan[0] = (2 + count($subHeadTitles));
                    $total_fields = count($subHeadTitles);
                    $table->rowspan = [];

                    $table->data = [];

                    $iterator = 0;

                    foreach ($subHeadTitles as $titleData) {
                        $table->data[0][$iterator] = $titleData;
                        $iterator++;
                    }

                    $table->data[0][] = __('Timestamp');
                    $iterator++;
                }

                $rowTable = 1;

                $arrayDataRowsInventory = explode(SEPARATOR_ROW, io_safe_output($row['data']));
                // SPLIT DATA IN ROWS
                // Remove the empty item caused by a line ending with a new line.
                $len = count($arrayDataRowsInventory);
                if (end($arrayDataRowsInventory) == '') {
                    $len--;
                    unset($arrayDataRowsInventory[$len]);
                }

                $iterator1 = 0;
                $rowPair = true;

                foreach ($arrayDataRowsInventory as $dataRowInventory) {
                    if ($rowPair === true) {
                        $table->rowclass[$iterator] = 'rowPair';
                    } else {
                        $table->rowclass[$iterator] = 'rowOdd';
                    }

                    $rowPair = !$rowPair;
                    $iterator++;

                    // Because SQL query extract all rows (row1;row2;row3...) and only I want the row has
                    // the search string.
                    if ($inventory_search_string && preg_match('/'.io_safe_output($inventory_search_string).'/', ($dataRowInventory)) == 0) {
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

                    // Fill unfilled cells with empty string.
                    $countArrayDataColumnInventory = count($arrayDataColumnInventory);
                    for ($i = 0; $i < ($total_fields - $countArrayDataColumnInventory); $i++) {
                        $table->data[$rowTable][$iterator2] = '';
                        $iterator2++;
                    }

                    $table->data[$rowTable][$iterator2] = $row['timestamp'];

                    $iterator1++;

                    $rowTable++;
                    if ($rowPair === true) {
                        $table->rowclass[$rowTable] = 'rowPair';
                    } else {
                        $table->rowclass[$rowTable] = 'rowOdd';
                    }
                }

                if ($iterator1 > 5) {
                    // PRINT COUNT TOTAL.
                    $table->data[$rowTable][0] = '';
                    $table->data[$rowTable][1] = '<b>'.__('Total').': </b>'.$iterator1;
                    $countSubHeadTitles = count($subHeadTitles);
                    for ($row_i = 2; $row_i <= $countSubHeadTitles; $row_i++) {
                        $table->data[$rowTable][$row_i] = '';
                    }

                    $rowTable++;
                }

                $idModuleInventory = $row['id_module_inventory'];

                if (isset($table) === true) {
                    $out .= html_print_table($table, true);
                }

                $out .= '</br>';
            }

            $out .= '</div>';
            $out .= '</br>';
            $out .= '</div>';
            $out .= '</br>';
        }

        return $out;
    }

    if (isset($table) === true) {
        $out .= html_print_table($table, true);
        $out .= ui_pagination($count, $url, $offset, 0, true);
    }

    return $out;
}


function inventory_get_datatable(
    $agents_ids,
    $inventory_module_name,
    $utimestamp,
    $inventory_search_string='',
    $export_csv=false,
    $return_mode=false,
    $order_by_agent=false
) {
    global $config;

    $offset = (int) get_parameter('offset');

    $where = [];

    array_push(
        $where,
        'tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory'
    );

    // Discart empty first position.
    if (isset($agents_ids[0]) === true && empty($agents_ids[0]) === true) {
        unset($agents_ids[0]);
    }

    // If there are no agents selected.
    if (empty($agents_ids) === true) {
        return ERR_NODATA;
    }

    if (array_search(-1, $agents_ids) === false) {
        array_push($where, 'tagent_module_inventory.id_agente IN ('.implode(',', $agents_ids).')');
    }

    if ($inventory_module_name[0] !== '0'
        && $inventory_module_name !== ''
        && $inventory_module_name !== 'all'
    ) {
        array_push($where, "tmodule_inventory.name IN ('".implode("','", (array) $inventory_module_name)."')");
    }

    if ($inventory_search_string != '') {
        array_push($where, "tagent_module_inventory.data LIKE '%".$inventory_search_string."%'");
    }

    if ($utimestamp > 0) {
        array_push($where, 'tagente_datos_inventory.utimestamp <= '.$utimestamp.' ');
    }

    $sql = sprintf(
        'SELECT tmodule_inventory.*,
            tagent_module_inventory.*,
            tagente.alias as name_agent,
            tagente_datos_inventory.utimestamp as last_update,
            tagente_datos_inventory.timestamp as last_update_timestamp,
            tagente_datos_inventory.data as data_inventory
        FROM tmodule_inventory
        INNER JOIN tagent_module_inventory
            ON tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
        INNER JOIN tagente_datos_inventory
            ON tagent_module_inventory.id_agent_module_inventory = tagente_datos_inventory.id_agent_module_inventory
        LEFT JOIN tagente
            ON tagente.id_agente = tagent_module_inventory.id_agente

        WHERE %s
        ORDER BY tmodule_inventory.id_module_inventory
        ',
        implode(' AND ', $where)
    );

    if ($inventory_module_name[0] !== '0'
        && $inventory_module_name !== ''
        && $inventory_module_name !== 'all'
    ) {
        $sql .= sprintf(
            'LIMIT %d, %d',
            $offset,
            $config['block_size']
        );
    }

    $rows = db_get_all_rows_sql($sql);

    if ($order_by_agent === false) {
        $modules = [];
        foreach ($rows as $row) {
            if ($row['utimestamp'] !== $row['last_update']) {
                $row['timestamp'] = $row['last_update_timestamp'];
            }

            $data_rows = explode(PHP_EOL, $row['data_inventory']);
            foreach ($data_rows as $data_key => $data_value) {
                if (empty($data_value) === false) {
                    $row['data'] = $data_value;
                    $modules[$row['name']][$row['name_agent'].'-'.$data_key.'-'.$data_value] = $row;
                }
            }
        }

        return $modules;
    } else {
        $agents_rows = [];
        $agent_data = [];
        $rows_tmp = [];
        foreach ($rows as $row) {
            $replace_agent_data = false;
            if (isset($agent_data[$row['id_agente']]) === true) {
                foreach ($agent_data[$row['id_agente']] as $key => $compare_data) {
                    if ($compare_data['id_module_inventory'] === $row['id_module_inventory']
                        && $row['last_update'] > $compare_data['last_update']
                    ) {
                        $agent_data[$row['id_agente']][$key] = $row;
                        $replace_agent_data = true;
                    }
                }
            }

            if ($replace_agent_data === false) {
                $agent_data[$row['id_agente']][] = $row;
            }
        }

        foreach ($agent_data as $id_agent => $data_agent) {
            foreach ($data_agent as $key => $agent_row) {
                if (isset($rows_tmp['agent']) === false) {
                    $rows_tmp['agent'] = $agent_row['name_agent'];
                }

                $data_agent[$key]['timestamp'] = $agent_row['last_update_timestamp'];
                $data_agent[$key]['utimestamp'] = $agent_row['last_update'];

                if ($utimestamp > 0) {
                    $data_row = db_get_row_sql(
                        sprintf(
                            'SELECT `data`,
                                `timestamp`, 
                                `utimestamp`
                            FROM tagente_datos_inventory
                            WHERE utimestamp = "%s"
                                AND id_agent_module_inventory = %d
                            ORDER BY utimestamp DESC',
                            $utimestamp,
                            $agent_row['id_agent_module_inventory']
                        )
                    );

                    if ($data_row !== false) {
                        $data_agent[$key]['data'] = $data_row['data'];
                    } else {
                        continue;
                    }
                }
            }

            $rows_tmp['row'] = $data_agent;
            array_push($agents_rows, $rows_tmp);
        }

        return $agents_rows;
    }
}


function get_data_basic_info_sql($params, $count=false)
{
    $table = 'tagente';
    if (is_metaconsole() === true) {
        $table = 'tmetaconsole_agent';
    }

    $where = 'WHERE 1=1 ';
    if ($params['id_agent'] > 0 && $count === true) {
        $where .= sprintf(' AND id_agente = %d', $params['id_agent']);
    } else if ($params['id_agent'] > 0 && $count === false) {
        $where .= sprintf(' AND %s.id_agente = %d', $table, $params['id_agent']);
    }

    if ($params['id_group'] > 0) {
        $where .= sprintf(' AND id_grupo = %d', $params['id_group']);
    }

    if ($params['search'] > 0) {
        $where .= sprintf(
            ' AND ( alias LIKE "%%%s%%" )',
            $params['search']
        );
    }

    if ($params['order'] > 0) {
        $str_split = explode(' ', $params['order']);
        switch ($str_split[0]) {
            case 'alias':
                $params['order'] = 'alias '.$str_split[1];
            break;

            case 'ip':
                $params['order'] = 'direccion '.$str_split[1];
            break;

            case 'secondoaryIp':
                $params['order'] = 'fixed_ip '.$str_split[1];
            break;

            case 'group':
                $params['order'] = 'id_grupo '.$str_split[1];
            break;

            case 'secondaryGroups':
                $params['order'] = 'tagent_secondary_group.id_group '.$str_split[1];
            break;

            case 'description':
                $params['order'] = 'comentarios '.$str_split[1];
            break;

            case 'os':
                $params['order'] = 'id_os '.$str_split[1];
            break;

            case 'interval':
                $params['order'] = 'intervalo '.$str_split[1];
            break;

            case 'lastContact':
                $params['order'] = 'ultimo_contacto '.$str_split[1];
            break;

            case 'lastStatusChange':
                $params['order'] = 'tagente_estado.last_status_change '.$str_split[1];
            break;

            case 'customFields':
                $params['order'] = 'tagent_custom_data.id_field '.$str_split[1];
            break;

            case 'valuesCustomFields':
                $params['order'] = 'tagent_custom_data.description '.$str_split[1];
            break;

            default:
                $params['order'] = 'alias '.$str_split[1];
            break;
        }
    }

    $limit_condition = '';
    $order_condition = '';
    $fields = 'count(*)';
    $innerjoin = '';
    $groupby = '';

    if ($count !== true) {
        $fields = '*';
        $innerjoin = 'LEFT JOIN tagente_estado ON '.$table.'.id_agente = tagente_estado.id_agente ';
        $innerjoin .= 'LEFT JOIN tagent_secondary_group ON '.$table.'.id_agente = tagent_secondary_group.id_agent ';
        $innerjoin .= 'LEFT JOIN tagent_custom_data ON '.$table.'.id_agente = tagent_custom_data.id_agent ';
        $groupby = 'GROUP BY '.$table.'.id_agente';
        $limit_condition = sprintf(
            'LIMIT %d, %d',
            $params['start'],
            $params['length']
        );

        $order_condition = sprintf('ORDER BY %s', $params['order']);
    }

    $sql = sprintf(
        'SELECT %s
        FROM %s
        %s
        %s
        %s
        %s
        %s',
        $fields,
        $table,
        $innerjoin,
        $where,
        $groupby,
        $order_condition,
        $limit_condition
    );

    if ($count !== true) {
        $result = db_get_all_rows_sql($sql);
        if ($result === false) {
            $result = [];
        }
    } else {
        $result = db_get_sql($sql);
        if ($result === false) {
            $result = 0;
        }
    }

    return $result;
}


function inventory_get_dates($module_inventory_name, $inventory_agent, $inventory_id_group)
{
    $sql = 'SELECT tagente_datos_inventory.utimestamp,
			tagente_datos_inventory.timestamp
		FROM tmodule_inventory, tagent_module_inventory,
			tagente_datos_inventory, tagente
		WHERE
			tmodule_inventory.id_module_inventory = tagent_module_inventory.id_module_inventory
			AND tagente_datos_inventory.id_agent_module_inventory = tagent_module_inventory.id_agent_module_inventory
			AND tagente.id_agente = tagent_module_inventory.id_agente';

    if ($inventory_agent !== 0) {
        $sql .= ' AND tagent_module_inventory.id_agente IN ('."'".implode(',', (array) $inventory_agent)."'".')';
    }

    if ($inventory_id_group !== 0) {
        $sql .= " AND tagente.id_grupo = $inventory_id_group";
    }

    if (is_string($module_inventory_name) === true
        && $module_inventory_name !== '0'
    ) {
        $sql .= " AND tmodule_inventory.name IN ('".str_replace(',', "','", $module_inventory_name)."')";
    }

    $sql .= ' ORDER BY tagente_datos_inventory.utimestamp DESC';

    $dates_raw = db_get_all_rows_sql($sql);

    if ($dates_raw == false) {
        return [];
    }

    $dates = [];
    foreach ($dates_raw as $date) {
        $dates[$date['utimestamp']] = $date['timestamp'];
    }

    return $dates;
}


function inventory_get_agents($filter=false, $fields=false)
{
    $inventory_agents_id = db_get_all_rows_sql(
        'SELECT DISTINCT(id_agente)
		FROM tagent_module_inventory'
    );

    if ($inventory_agents_id == false) {
        $inventory_agents_id = [];
        return [];
    }

    $ids = [];
    foreach ($inventory_agents_id as $ia) {
        $ids[] = $ia['id_agente'];
    }

    $filter['id_agente'] = $ids;

    $agents = agents_get_agents($filter, $fields);

    if ($agents === false) {
        $agents = [];
    }

    return $agents;
}


function inventory_get_changes(
    $id_agent,
    $module_names,
    $start_utimestamp,
    $end_utimestamp,
    $return_mode=false
) {
    global $config;

    $any_inventory_modules = false;
    if (empty($module_names)) {
        $any_inventory_modules = true;
    } else if (((string) ($module_names[0])) === '0') {
        $any_inventory_modules = true;
    }

    $module_names = (array) $module_names;

    if ($id_agent[0] == -1) {
        // Any agent
        $sql = sprintf(
            "SELECT evento, utimestamp
			FROM tevento
			WHERE utimestamp >= %d 
				AND utimestamp <= %d 
				AND event_type = 'configuration_change'",
            $start_utimestamp,
            $end_utimestamp
        );
    } else {
        $sql = sprintf(
            "SELECT evento, utimestamp
			FROM tevento
			WHERE id_agente IN (%s) 
				AND utimestamp >= %d 
				AND utimestamp <= %d 
				AND event_type = 'configuration_change'",
            implode(',', (array) $id_agent),
            $start_utimestamp,
            $end_utimestamp
        );
    }

    $events = db_get_all_rows_sql($sql);

    if ($events === false) {
        return ERR_NODATA;
    }

    $inventory_changes = [];
    $are_data = false;

    foreach ($events as $k => $event) {
        $changes = io_safe_output($event['evento']);
        $changes = explode("\n", $changes);

        $check = preg_match(
            '/agent \'(.*)\' module \'(.*)\'/',
            end($changes),
            $matches
        );

        $agent_name = $matches[1];
        $module_name = $matches[2];

        if (!$any_inventory_modules) {
            if (!in_array($module_name, $module_names)) {
                continue;
            }
        }

        $are_data = true;

        $inventory_changes[$k]['agent_name'] = $matches[1];
        $inventory_changes[$k]['module_name'] = $module_name;
        $inventory_changes[$k]['utimestamp'] = $event['utimestamp'];
        $changes[0] = str_replace('Configuration changes  (', '', $changes[0]);

        unset($changes[(count($changes) - 1)]);
        $state = '';
        foreach ($changes as $ch) {
            if (preg_match('/NEW RECORD: (.*)/', $ch)) {
                $ch = preg_replace('/NEW RECORD: /', '', $ch);
                $ch = preg_replace('/^\'/', '', $ch);
                $ch = '<pre>'.$ch.'</pre>';
                $state = 'new';
            }

            if (preg_match('/\s*DELETED RECORD: (.*)/', $ch)) {
                $ch = preg_replace('/\s*DELETED RECORD/', '', $ch);
                $ch = preg_replace('/^\'/', '', $ch);
                $ch = '<pre>'.$ch.'</pre>';
                $state = 'deleted';
            }

            $inventory_changes[$k][$state][] = $ch;
        }
    }

    if ($are_data === false) {
        if ($return_mode !== false) {
            switch ($return_mode) {
                case 'array':
                return ERR_NODATA;

                    break;
                default:
                return __('No changes found');
                    break;
            }
        }

        return ERR_NODATA;
    }

    switch ($return_mode) {
        case 'csv':
            $out_csv = '';
            foreach ($inventory_changes as $ic) {
                $out_csv .= __('Agent').SEPARATOR_COLUMN_CSV.$ic['agent_name']."\n";
                $out_csv .= __('Module').SEPARATOR_COLUMN_CSV.$ic['module_name']."\n";
                $out_csv .= __('Date').SEPARATOR_COLUMN_CSV.date($config['date_format'], $ic['utimestamp'])."\n";
                if (isset($ic['new'])) {
                    foreach ($ic['new'] as $icc) {
                        $out_csv .= __('Added').SEPARATOR_COLUMN_CSV.$icc."\n";
                    }
                }

                if (isset($ic['deleted'])) {
                    foreach ($ic['deleted'] as $icc) {
                        $out_csv .= __('Deleted').SEPARATOR_COLUMN_CSV.$icc."\n";
                    }
                }
            }
        return $out_csv;

            break;
        case 'array':
            $out_array = [];

            foreach ($inventory_changes as $k => $ic) {
                $out_array[$k]['agent'] = $ic['agent_name'];
                $out_array[$k]['module'] = $ic['module_name'];
                $out_array[$k]['date'] = date($config['date_format'], $ic['utimestamp']);

                if (isset($ic['new'])) {
                    foreach ($ic['new'] as $icc) {
                        $out_array[$k]['added'][] = $icc;
                    }
                }

                if (isset($ic['deleted'])) {
                    foreach ($ic['deleted'] as $icc) {
                        $out_array[$k]['deleted'][] = $icc;
                    }
                }
            }

            if (empty($out_array)) {
                return ERR_NODATA;
            }
        return $out_array;

            break;
    }

    $out = '<table class="w100p">';
    foreach ($inventory_changes as $ic) {
        $out .= '<tr><td>';

        unset($table);
        $table->width = '98%';
        $table->style[0] = 'text-align:50%';
        $table->style[1] = 'text-align:50%';

        $table->data[0][0] = '<b>'.__('Agent').'</b>: '.$ic['agent_name'];
        $table->data[0][1] = '<b>'.__('Module').'</b>: '.$ic['module_name'];

        $timestamp = date($config['date_format'], $ic['utimestamp']);

        $table->colspan[1][0] = 2;
        $table->data[1][0] = '<div class="w100p center"><b><center>('.$timestamp.')</center></b></div>';
        $row = 2;

        if (isset($ic['new'])) {
            foreach ($ic['new'] as $icc) {
                $table->colspan[$row][0] = 2;
                $table->data[$row][0] = '<b>'.__('Added').'</b>: '.$icc;
                $row++;
            }
        }

        if (isset($ic['deleted'])) {
            foreach ($ic['deleted'] as $icc) {
                $table->colspan[$row][0] = 2;
                $table->data[$row][0] = '<b>'.__('Deleted').'</b>: '.$icc;
                $row++;
            }
        }

        $out .= html_print_table($table, true);

        $out .= '</td></tr>';
    }

    $out .= '</table>';

    return $out;
}


/**
 * Get a list with inventory modules
 *
 * @param mixed An integer can be place here to get a response
 *         to paginate. If this parameter is false, return full list
 *
 * @return array with inventory modules (paginated or not)
 */
function inventory_get_modules_list($offset=false)
{
    global $config;

    $filter = [];
    if (is_numeric($offset)) {
        $filter['limit'] = $config['block_size'];
        $filter['offset'] = $offset;
    }

    return db_get_all_rows_filter(
        'tmodule_inventory LEFT JOIN tconfig_os
			ON tmodule_inventory.id_os = tconfig_os.id_os',
        $filter,
        [
            'tmodule_inventory.id_module_inventory',
            'tmodule_inventory.name',
            'tmodule_inventory.description',
            'tmodule_inventory.interpreter',
            'tconfig_os.name AS os_name',
            'tconfig_os.id_os',
        ]
    );
}


/**
 * Validate the modules inventory
 *
 * @param array with inventory modules data.
 *
 * @return boolean True if the values are valid
 */
function inventory_validate_inventory_module($values)
{
    return !(empty($values['name']) || empty($values['id_os']) ||
        empty($values['data_format'])
    );
}


/**
 * Insert the module inventory data into database
 *
 * @param array with inventory modules data.
 *
 * @return boolean False if values are invalid or cannot put it on database
 */
function inventory_create_inventory_module($values)
{
    if (!inventory_validate_inventory_module($values)) {
        return false;
    }

    return db_process_sql_insert('tmodule_inventory', $values);
}


/**
 * Update the module inventory data into database
 *
 * @param int ID inventory module
 * @param array with inventory modules data.
 *
 * @return boolean False if values are invalid or cannot put it on database
 */
function inventory_update_inventory_module($id_module_inventory, $values)
{
    if (!inventory_validate_inventory_module($values)) {
        return false;
    }

    return db_process_sql_update(
        'tmodule_inventory',
        $values,
        ['id_module_inventory' => $id_module_inventory]
    );
}


/**
 * Returns inventory module names given agent id.
 *
 * @param  integer $id_agent
 * @param  string  $all
 * @param  integer $server_id
 * @param  string  $server_name
 * @return void
 */
function inventory_get_agent_modules($id_agent, $all='all', $server_id=0, $server_name=null)
{
    global $config;

    if ($config['metaconsole']) {
        $server_id = metaconsole_get_id_server($server_name);
    }

    switch ($all) {
        default:
        case 'all':
            $enabled = '1 = 1';
        break;
        case 'enabled':
            $enabled = 'disabled = 0';
        break;
    }

    if (is_array($id_agent)) {
        $count_id_agent = count(($id_agent));
        $id_agent = implode(',', $id_agent);
    } else {
        $count_id_agent = 1;
    }

    $sql = 'SELECT t1.id_module_inventory, name
		FROM tmodule_inventory t1, tagent_module_inventory t2
			WHERE t1.id_module_inventory = t2.id_module_inventory
			AND id_agente IN ('.$id_agent.') AND (
				SELECT count(name)
					FROM tmodule_inventory t3, tagent_module_inventory t4
						WHERE t3.id_module_inventory = t4.id_module_inventory
						AND t3.name = t1.name 
						AND t4.id_agente IN ('.$id_agent.')) = ('.$count_id_agent.')
		ORDER BY name';

    // Only in template editor from metaconsole.
    if ($config['metaconsole']) {
        $server_data = metaconsole_get_connection_by_id($server_id);

        if ($server_data === false) {
            return '';
        }

        $modules = [];

        // Establishes connection.
        if (metaconsole_load_external_db($server_data) !== NOERR) {
            return '';
        }

        $modules = db_get_all_rows_sql($sql);

        if ($modules == false) {
            $modules = [];
        }

        $result = [];
        foreach ($modules as $module) {
            $result[$module['name']] = io_safe_output($module['name']);
        }

        // Restore DB connection.
        metaconsole_restore_db();
    } else {
        $modules = db_get_all_rows_sql($sql);

        if ($modules == false) {
            $modules = [];
        }

        $result = [];
        foreach ($modules as $module) {
            $result[$module['name']] = io_safe_output($module['name']);
        }
    }

    return $result;
}
