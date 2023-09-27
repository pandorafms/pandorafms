<?php
/**
 * MonitoringElements element for tactical view.
 *
 * @category   General
 * @package    Pandora FMS
 * @subpackage TacticalView
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

use PandoraFMS\TacticalView\Element;

/**
 * MonitoringElements, this class contain all logic for this section.
 */
class MonitoringElements extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Monitoring elements');
    }


    /**
     * Returns the html of the tags grouped by modules.
     *
     * @return string
     */
    public function getTagsGraph():string
    {
        $sql = 'SELECT name, count(*) AS total
                FROM ttag_module t
                LEFT JOIN ttag ta ON ta.id_tag = t.id_tag
                GROUP BY t.id_tag
                ORDER BY total DESC
                LIMIT 10;';
        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        foreach ($rows as $key => $row) {
            $labels[] = $this->controlSizeText($row['name']);
            $data[] = $row['total'];
        }

        $options = [
            'labels'       => $labels,
            'legend'       => [
                'position' => 'bottom',
                'align'    => 'right',
                'display'  => false,
            ],
            'cutout'       => 80,
            'nodata_image' => ['width' => '100%'],
        ];
        $pie = ring_graph($data, $options);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 80%; max-height: 220px;',
            ],
            true
        );

        return $output;
    }


    /**
     * Returns the html of the groups grouped by modules.
     *
     * @return string
     */
    public function getModuleGroupGraph():string
    {
        $sql = 'SELECT name, count(*) AS total
                FROM tagente_modulo m
                LEFT JOIN tmodule_group g ON g.id_mg = m.id_module_group
                WHERE name <> ""
                GROUP BY m.id_module_group
                ORDER BY total DESC
                LIMIT 10';
        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        foreach ($rows as $key => $row) {
            $labels[] = $this->controlSizeText($row['name']);
            $data[] = $row['total'];
        }

        $options = [
            'labels'       => $labels,
            'legend'       => [
                'position' => 'bottom',
                'align'    => 'right',
                'display'  => false,
            ],
            'cutout'       => 80,
            'nodata_image' => ['width' => '100%'],
        ];
        $pie = ring_graph($data, $options);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 80%; max-height: 220px;',
            ],
            true
        );

        return $output;
    }


    /**
     * Returns the html of the agent grouped by modules.
     *
     * @return string
     */
    public function getAgentGroupsGraph():string
    {
        $sql = 'SELECT gr.nombre, count(*) AS total
                FROM tagente a
                LEFT JOIN tagent_secondary_group g ON g.id_agent = a.id_agente
                LEFT JOIN tgrupo gr ON gr.id_grupo = a.id_grupo
                GROUP BY a.id_grupo
                ORDER BY total DESC
                LIMIT 10';
        $rows = db_process_sql($sql);

        $labels = [];
        $data = [];
        foreach ($rows as $key => $row) {
            $labels[] = $this->controlSizeText($row['nombre']);
            $data[] = $row['total'];
        }

        $options = [
            'labels'       => $labels,
            'legend'       => [
                'position' => 'bottom',
                'align'    => 'right',
                'display'  => false,
            ],
            'cutout'       => 80,
            'nodata_image' => ['width' => '100%'],
        ];
        $pie = ring_graph($data, $options);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 80%; max-height: 220px;',
            ],
            true
        );

        return $output;
    }


    /**
     * Returns the html of monitoring by status.
     *
     * @return string
     */
    public function getMonitoringStatusGraph():string
    {
        // TODO add labels.
        $pie = graph_agent_status(false, '', '', true, true, false, true);
        $output = html_print_div(
            [
                'content' => $pie,
                'style'   => 'margin: 0 auto; max-width: 80%; max-height: 220px;',
            ],
            true
        );

        return $output;
    }


}
