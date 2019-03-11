<?php


/**
 * Class to manage networkmaps in Pandora FMS
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage NetworkMap manager
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
global $config;

require_once $config['homedir'].'/include/functions_pandora_networkmap.php';

/**
 * Manage networkmaps in Pandora FMS
 */
class NetworkMap
{

    /**
     * Target map Id.
     *
     * @var integer
     */
    public $idMap;

    /**
     * Graph definition
     *
     * @var array
     */
    public $graph;

    /**
     * Node list.
     *
     * @var array
     */
    public $nodes;

    /**
     * Relationship map.
     *
     * @var array
     */
    public $relations;

    /**
     * Mode simple or advanced.
     *
     * @var integer
     */
    public $mode;

    /**
     * Array of map options
     *   height
     *   width
     *
     * @var array
     */
    public $mapOptions;


    /**
     * Base constructor.
     *
     * @param array $options Could define:
     *   id_map => target map to be painted.
     *   graph => target graph (already built)
     *   nodes => target agents or nodes.
     *   relations => target array of relationships.
     *   mode => simple (0) or advanced (1)
     *   map_options => ?
     *
     * @return object New networkmap manager.
     */
    public function __construct($options=[])
    {
        if (is_array($options)) {
            if (isset($options['id_map'])) {
                $this->idMap = $options['id_map'];
            }

            if (isset($options['graph'])) {
                $this->graph = $options['graph'];
            }

            if (isset($options['nodes'])) {
                $this->nodes = $options['nodes'];
            }

            if (isset($options['relations'])) {
                $this->relations = $options['relations'];
            }

            if (isset($options['mode'])) {
                $this->mode = $options['mode'];
            }

            if (isset($options['map_options'])) {
                $this->mapOptions = $options['map_options'];
            }
        }

        return $this;

    }


    /**
     * Print all components required to visualizate a network map.
     *
     * @param boolean $return Return as string or not.
     *
     * @return string HTML code.
     */
    public function printMap($return=false)
    {
        global $config;

        // ACL.
        $networkmap_read = check_acl(
            $config['id_user'],
            $networkmap['id_group'],
            'MR'
        );
        $networkmap_write = check_acl(
            $config['id_user'],
            $networkmap['id_group'],
            'MW'
        );
        $networkmap_manage = check_acl(
            $config['id_user'],
            $networkmap['id_group'],
            'MM'
        );

        if (!$networkmap_read
            && !$networkmap_write
            && !$networkmap_manage
        ) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access networkmap'
            );
            include 'general/noaccess.php';
            return '';
        }

        $user_readonly = !$networkmap_write && !$networkmap_manage;

        if (isset($this->idMap)) {
            $graph = networkmap_process_networkmap($this->idMap);

            ob_start();

            ui_require_css_file('networkmap');
            show_networkmap(
                $this->idMap,
                $user_readonly,
                $graph,
                get_parameter('pure', 0)
            );

            $output = ob_get_clean();
        } else if (isset($this->graph)) {
            // Build graph based on direct graph definition.
        }

        if ($return === false) {
            echo $output;
        }

        return $output;
    }


}
