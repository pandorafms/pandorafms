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

require_once $config['homedir'].'/include/functions_networkmap.php';
require_once $config['homedir'].'/include/functions_pandora_networkmap.php';

enterprise_include_once('include/functions_pandora_networkmap.php');

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
     * Content of tmap.
     *
     * @var array
     */
    public $map;

    /**
     * Data origin, network.
     *
     * @var string
     */
    public $network;

    /**
     * Data origin, group id.
     *
     * @var integer
     */
    public $idGroup;

    /**
     * Data origin, Discovery task.
     *
     * @var integer
     */
    public $idTask;

    /**
     * Graph definition
     *
     * @var array
     */
    public $graph;

    /**
     * Dot string with graph definition.
     *
     * @var string
     */
    public $dotGraph;

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
     * @param mixed $options Could define in array as:
     *   id_map => target discovery task id.
     *   id_group => target group.
     *   network => target CIDR.
     *   graph => target graph (already built).
     *   nodes => target agents or nodes.
     *   relations => target array of relationships.
     *   mode => simple (0) or advanced (1).
     *   map_options => Map options.
     *
     * @return object New networkmap manager.
     */
    public function __construct($options=false)
    {
        // Default mapOptions values.
        // Default neato.
        $this->mapOptions['generation_method'] = 3;
        $this->mapOptions['simple'] = 0;
        $this->mapOptions['font_size'] = 12;
        $this->mapOptions['layout'] = 'spring1';
        $this->mapOptions['nooverlap'] = 1;
        $this->mapOptions['zoom'] = 1;
        $this->mapOptions['ranksep'] = 0.5;
        $this->mapOptions['center'] = 0;
        $this->mapOptions['regen'] = 0;
        $this->mapOptions['pure'] = 0;
        $this->mapOptions['show_snmp_modules'] = false;
        $this->mapOptions['cut_names'] = false;
        $this->mapOptions['relative'] = true;
        $this->mapOptions['text_filter'] = '';
        $this->mapOptions['dont_show_subgroups'] = false;
        $this->mapOptions['strict_user'] = false;
        $this->mapOptions['size_canvas'] = null;
        $this->mapOptions['old_mode'] = false;
        $this->mapOptions['map_filter'] = [
            'node_radius' => 40,
            'x_offs'      => '',
            'y_offs'      => '',
            'z_dash'      => 0.31,
            'node_sep'    => 0.1,
            'rank_sep'    => 1,
            'mindist'     => 1,
            'kval'        => 0.1,
        ];

        if (is_array($options)) {
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

            if (is_array($options['map_options'])) {
                foreach ($options['map_options'] as $k => $v) {
                    $this->mapOptions[$k] = $v;
                }
            }

            // Load from Discovery task.
            if ($options['id_map']) {
                $this->idMap = $options['id_map'];
                // Update nodes and relations.
                $this->loadMap();

                if (empty($this->nodes)
                    || empty($this->relations)
                ) {
                    $this->createMap();
                }
            } else {
                if ($options['id_group']) {
                    $this->idGroup = $options['id_group'];
                }

                if ($options['id_task']) {
                    $this->idTask = $options['id_task'];
                }

                if ($options['network']) {
                    $this->network = $options['network'];
                }

                $this->createMap();
            }
        }

        return $this;

    }


    /**
     * Creates a new map based on a target.
     *
     * Target is specified from constructor arguments.
     *   options:
     *    - id_task  => create a map from task.
     *    - id_group => create a map from group.
     *    - network  => create a map from network.
     *
     * @return void
     */
    public function createMap()
    {
        if ($this->idMap) {
            $this->loadMap();

            return;
        }

        if ($this->network) {
            $this->nodes = networkmap_get_new_nodes_from_ip_mask(
                $this->network
            );
        }

        if ($this->idTask) {
            // Retrieve data from target task.
            $this->loadMap();
        }
    }


    /**
     * Loads a map from a target map ID.
     *
     * @return void.
     */
    public function loadMap()
    {
        if ($this->idMap) {
            $this->map = db_get_row('tmap', 'id', $this->idMap);

            // Retrieve or update nodes and relations.
            $this->getNodes();
            $this->getRelations();

            // Nodes and relations.
            $this->graph = networkmap_process_networkmap($this->idMap);
        } else {
            // Simulated map.
            $this->idMap = '0';
            // No tmap definition. Paint data.
            if ($this->idTask) {
                $recon_task = db_get_row_filter(
                    'trecon_task',
                    ['id_rt' => $networkmap['source_data']]
                );
                $this->network = $recon_task['subnet'];
            }

            $this->graph = $this->generateNetworkMap();

            // Simulate map entry.
            $this->map = ['id' => '0'];
        }
    }


    /**
     * Return nodes of current map.
     *
     * @return array Nodes.
     */
    public function getNodes()
    {
        if ($this->nodes) {
            return $this->nodes;
        }

        if ($this->idMap !== false) {
            if (enterprise_installed()) {
                // Enterprise environment: LOAD.
                $this->nodes = enterprise_hook(
                    'get_nodes_from_db',
                    [$this->idMap]
                );
            }
        }

        return $this->nodes;

    }


    /**
     * Generates a nodes - relationships array using graphviz dot
     * schema.
     *
     * @return array Node - relationship calculated.
     */
    public function generateNetworkMap()
    {
        /*
         * Let graphviz place the nodes.
         */

        switch ($this->mapOptions['generation_method']) {
            case 0:
                $filter = 'circo';
                $layout = 'circular';
            break;

            case 1:
                $filter = 'dot';
                $layout = 'flat';
            break;

            case 2:
                $filter = 'twopi';
                $layout = 'radial';
            break;

            case 3:
            default:
                $filter = 'neato';
                $layout = 'spring1';
            break;

            case 4:
                $filter = 'fdp';
                $layout = 'spring2';
            break;
        }

        $nodes_and_relations = [];
        // Generate dot file.
        $graph = networkmap_generate_dot(
            get_product_name(),
            $this->idGroup,
            $this->mapOptions['simple'],
            $this->mapOptions['font_size'],
            $this->mapOptions['layout'],
            $this->mapOptions['nooverlap'],
            $this->mapOptions['zoom'],
            $this->mapOptions['ranksep'],
            $this->mapOptions['center'],
            $this->mapOptions['regen'],
            $this->mapOptions['pure'],
            $this->mapOptions['id'],
            $this->mapOptions['show_snmp_modules'],
            $this->mapOptions['cut_names'],
            $this->mapOptions['relative'],
            $this->mapOptions['text_filter'],
            $this->network,
            $this->mapOptions['dont_show_subgroups'],
            // Strict user (strict_user).
            false,
            // Canvas size (size_canvas).
            null,
            $this->mapOptions['old_mode'],
            $this->mapOptions['map_filter']
        );

        switch (PHP_OS) {
            case 'WIN32':
            case 'WINNT':
            case 'Windows':
                $filename_dot = sys_get_temp_dir()."\\networkmap_".$filter;
            break;

            default:
                $filename_dot = sys_get_temp_dir().'/networkmap_'.$filter;
            break;
        }

        if ($simple) {
            $filename_dot .= '_simple';
        }

        if ($nooverlap) {
            $filename_dot .= '_nooverlap';
        }

        $filename_dot .= '_'.$this->idMap.'.dot';

        file_put_contents($filename_dot, $graph);

        switch (PHP_OS) {
            case 'WIN32':
            case 'WINNT':
            case 'Windows':
                $filename_plain = sys_get_temp_dir().'\\plain.txt';

                $cmd = io_safe_output(
                    $config['graphviz_bin_dir'].'\\'.$filter.'.exe -Tplain -o '.$filename_plain.' '.$filename_dot
                );
            break;

            default:
                $filename_plain = sys_get_temp_dir().'/plain.txt';

                $cmd = $filter.' -Tplain -o '.$filename_plain.' '.$filename_dot;
            break;
        }

        $r = system($cmd);

        unlink($filename_dot);

        $nodes = networkmap_loadfile(
            $this->idMap,
            $filename_plain,
            $relation_nodes,
            $graph
        );

        unlink($filename_plain);

        $id = $this->idMap;

        /*
         * Graphviz section ends here.
         */

        /*
         * Calculate references.
         */

        // Set the position of modules.
        foreach ($nodes as $key => $node) {
            if ($node['type'] == 'module') {
                // Search the agent of this module for to get the
                // position.
                foreach ($nodes as $key2 => $node2) {
                    if ($node2['id_agent'] != 0 && $node2['type'] == 'agent') {
                        if ($node2['id_agent'] == $node['id_agent']) {
                            $nodes[$key]['coords'][0] = ($nodes[$key2]['coords'][0] + $node['height'] / 2);
                            $nodes[$key]['coords'][1] = ($nodes[$key2]['coords'][1] + $node['width'] / 2);
                        }
                    }
                }
            }
        }

        $nodes_and_relations['nodes'] = [];
        $index = 0;
        $node_center = [];
        foreach ($nodes as $key => $node) {
            $nodes_and_relations['nodes'][$index]['id'] = $node['id'];
            $nodes_and_relations['nodes'][$index]['id_map'] = $id;

            $children_count = 0;
            foreach ($relation_nodes as $relation) {
                if (($relation['parent_type'] == 'agent') || ($relation['parent_type'] == '')) {
                    if ($nodes[$relation['id_parent']]['id_agent'] == $node['id_agent']) {
                        $children_count++;
                    }
                } else if ($relation['parent_type'] == 'module') {
                    if ($nodes[$relation['id_parent']]['id_module'] == $node['id_module']) {
                        $children_count++;
                    }
                }
            }

            if (empty($node_center) || $node_center['counter'] < $children_count) {
                $node_center['x'] = (int) $node['coords'][0];
                $node_center['y'] = (int) $node['coords'][1];
                $node_center['counter'] = $children_count;
            }

            $nodes_and_relations['nodes'][$index]['x'] = (int) $node['coords'][0];
            $nodes_and_relations['nodes'][$index]['y'] = (int) $node['coords'][1];

            if (($node['type'] == 'agent') || ($node['type'] == '')) {
                $nodes_and_relations['nodes'][$index]['source_data'] = $node['id_agent'];
                $nodes_and_relations['nodes'][$index]['type'] = 0;
            } else {
                $nodes_and_relations['nodes'][$index]['source_data'] = $node['id_module'];
                $nodes_and_relations['nodes'][$index]['id_agent'] = $node['id_agent'];
                $nodes_and_relations['nodes'][$index]['type'] = 1;
            }

            $style = [];
            $style['shape'] = 'circle';
            $style['image'] = $node['image'];
            $style['width'] = $node['width'];
            $style['height'] = $node['height'];
            $style['label'] = $node['text'];
            $nodes_and_relations['nodes'][$index]['style'] = json_encode($style);

            $index++;
        }

        $nodes_and_relations['relations'] = [];
        $index = 0;
        foreach ($relation_nodes as $relation) {
            $nodes_and_relations['relations'][$index]['id_map'] = $id;

            if (($relation['parent_type'] == 'agent') || ($relation['parent_type'] == '')) {
                $nodes_and_relations['relations'][$index]['id_parent'] = $relation['id_parent'];
                $nodes_and_relations['relations'][$index]['id_parent_source_data'] = $nodes[$relation['id_parent']]['id_agent'];
                $nodes_and_relations['relations'][$index]['parent_type'] = 0;
            } else if ($relation['parent_type'] == 'module') {
                $nodes_and_relations['relations'][$index]['id_parent'] = $relation['id_parent'];
                $nodes_and_relations['relations'][$index]['id_parent_source_data'] = $nodes[$relation['id_parent']]['id_module'];
                $nodes_and_relations['relations'][$index]['parent_type'] = 1;
            } else {
                $nodes_and_relations['relations'][$index]['id_parent'] = $relation['id_parent'];
                $nodes_and_relations['relations'][$index]['id_child_source_data'] = -2;
                $nodes_and_relations['relations'][$index]['parent_type'] = 3;
            }

            if (($relation['child_type'] == 'agent') || ($relation['child_type'] == '')) {
                $nodes_and_relations['relations'][$index]['id_child'] = $relation['id_child'];
                $nodes_and_relations['relations'][$index]['id_child_source_data'] = $nodes[$relation['id_child']]['id_agent'];
                $nodes_and_relations['relations'][$index]['child_type'] = 0;
            } else if ($relation['child_type'] == 'module') {
                $nodes_and_relations['relations'][$index]['id_child'] = $relation['id_child'];
                $nodes_and_relations['relations'][$index]['id_child_source_data'] = $nodes[$relation['id_child']]['id_module'];
                $nodes_and_relations['relations'][$index]['child_type'] = 1;
            } else {
                $nodes_and_relations['relations'][$index]['id_child'] = $relation['id_child'];
                $nodes_and_relations['relations'][$index]['id_child_source_data'] = -2;
                $nodes_and_relations['relations'][$index]['child_type'] = 3;
            }

            $index++;
        }

        if ($this->idMap > 0) {
            enterprise_hook(
                'save_generate_nodes',
                [
                    $id,
                    $nodes_and_relations,
                ]
            );

            $pandorafms_node = $nodes_and_relations['nodes'][0];
            $center = [
                'x' => $node_center['x'],
                'y' => $node_center['y'],
            ];

            $networkmap['center_x'] = $center['x'];
            $networkmap['center_y'] = $center['y'];
            db_process_sql_update(
                'tmap',
                [
                    'center_x' => $networkmap['center_x'],
                    'center_y' => $networkmap['center_y'],
                ],
                ['id' => $id]
            );
        } else {
            $this->map['center_x'] = $center['x'];
            $this->map['center_y'] = $center['y'];
        }

        return $nodes_and_relations;
    }


    /**
     * Return relations of current map.
     *
     * @return array Relations.
     */
    public function getRelations()
    {
        if ($this->relations) {
            return $this->relations;
        }

        if ($this->idMap !== false) {
            if (enterprise_installed()) {
                $this->relations = enterprise_hook(
                    'get_relations_from_db',
                    [$this->idMap]
                );
            }
        }

        return $this->relations;

    }


    /**
     * Transform node information into JS data.
     *
     * @return string HTML code with JS data.
     */
    public function loadMapData()
    {
        $networkmap = $this->map;
        $networkmap['filter'] = json_decode(
            $networkmap['filter'],
            true
        );

        if (empty($networkmap['filter'])) {
            // Direct print. Avoid extra options.
            $networkmap['source_period'] = 60;
            $networkmap['filter']['node_radius'] = 40;
            $networkmap['filter'] = $this->mapOptions['map_filter'];
        }

        // Hardcoded.
        $networkmap['filter']['holding_area'] = [
            500,
            500,
        ];

        $this->graph['relations'] = clean_duplicate_links(
            $this->graph['relations']
        );

        $output .= '<script type="text/javascript">
    ////////////////////////////////////////////////////////////////////
    // VARS FROM THE DB
    ////////////////////////////////////////////////////////////////////
    var url_background_grid = "'.ui_get_full_url('images/background_grid.png').'";
    ';
        $output .= 'var networkmap_id = '.$this->idMap.";\n";

        if (!empty($networkmap['filter'])) {
            if (empty($networkmap['filter']['x_offs'])) {
                $output .= "var x_offs =null;\n";
            } else {
                $output .= 'var x_offs ='.$networkmap['filter']['x_offs'].";\n";
            }

            if (empty($networkmap['filter']['y_offs'])) {
                $output .= "var y_offs =null;\n";
            } else {
                $output .= 'var y_offs ='.$networkmap['filter']['y_offs'].";\n";
            }

            if (empty($networkmap['filter']['y_offs'])) {
                $output .= "var z_dash =null;\n";
            } else {
                $output .= 'var z_dash = '.$networkmap['filter']['z_dash'].";\n";
            }
        } else {
            $output .= "var x_offs = null;\n";
            $output .= "var y_offs = null;\n";
            $output .= "var z_dash = null;\n";
        }

        $output .= 'var networkmap_refresh_time = 1000 * '.$networkmap['source_period'].";\n";
        $output .= 'var networkmap_center = [ '.$networkmap['center_x'].', '.$networkmap['center_y']."];\n";
        $output .= 'var networkmap_dimensions = [ '.$networkmap['width'].', '.$networkmap['height']."];\n";
        $output .= 'var enterprise_installed = '.((int) enterprise_installed()).";\n";
        $output .= 'var node_radius = '.$networkmap['filter']['node_radius'].";\n";
        $output .= 'var networkmap_holding_area_dimensions = '.json_encode($networkmap['filter']['holding_area']).";\n";
        $output .= "var networkmap = {'nodes': [], 'links':  []};\n";
        $nodes = $this->graph['nodes'];

        if (empty($nodes)) {
            $nodes = [];
        }

        $count_item_holding_area = 0;
        $count = 0;
        $nodes_graph = [];

        foreach ($nodes as $key => $node) {
            $style = json_decode($node['style'], true);
            $node['style'] = json_decode($node['style'], true);

            // Only agents can be show.
            if (isset($node['type'])) {
                if ($node['type'] == 1) {
                    continue;
                }
            } else {
                $node['type'] = '';
            }

            $item = networkmap_db_node_to_js_node(
                $node,
                $count,
                $count_item_holding_area
            );
            if ($item['deleted']) {
                continue;
            }

            $output .= 'networkmap.nodes.push('.json_encode($item).");\n";
            $nodes_graph[$item['id']] = $item;
        }

        $relations = $this->graph['relations'];

        if ($relations === false) {
            $relations = [];
        }

        // Clean the relations and transform the module relations into
        // interfaces.
        networkmap_clean_relations_for_js($relations);

        $links_js = networkmap_links_to_js_links($relations, $nodes_graph);

        $array_aux = [];
        foreach ($links_js as $link_js) {
            if ($link_js['deleted']) {
                unset($links_js[$link_js['id']]);
            }

            if ($link_js['target'] == -1) {
                unset($links_js[$link_js['id']]);
            }

            if ($link_js['source'] == -1) {
                unset($links_js[$link_js['id']]);
            }

            if ($link_js['target'] == $link_js['source']) {
                unset($links_js[$link_js['id']]);
            }

            if ($link_js['arrow_start'] == 'module' && $link_js['arrow_end'] == 'module') {
                $output .= 'networkmap.links.push('.json_encode($link_js).");\n";
                $array_aux[$link_js['id_agent_start']] = 1;
                unset($links_js[$link_js['id']]);
            }
        }

        foreach ($links_js as $link_js) {
            if (($link_js['id_agent_end'] === 0) && $array_aux[$link_js['id_agent_start']] === 1) {
                continue;
            } else {
                $output .= 'networkmap.links.push('.json_encode($link_js).");\n";
            }
        }

        $output .= '
        ////////////////////////////////////////////////////////////////////
        // INTERFACE STATUS COLORS
        ////////////////////////////////////////////////////////////////////
        ';

        $module_color_status = [];
        $module_color_status[] = [
            'status_code' => AGENT_MODULE_STATUS_NORMAL,
            'color'       => COL_NORMAL,
        ];
        $module_color_status[] = [
            'status_code' => AGENT_MODULE_STATUS_CRITICAL_BAD,
            'color'       => COL_CRITICAL,
        ];
        $module_color_status[] = [
            'status_code' => AGENT_MODULE_STATUS_WARNING,
            'color'       => COL_WARNING,
        ];
        $module_color_status[] = [
            'status_code' => AGENT_STATUS_ALERT_FIRED,
            'color'       => COL_ALERTFIRED,
        ];
        $module_color_status_unknown = COL_UNKNOWN;

        $output .= 'var module_color_status = '.json_encode($module_color_status).";\n";
        $output .= "var module_color_status_unknown = '".$module_color_status_unknown."';\n";

        $output .= '
        ////////////////////////////////////////////////////////////////////
        // Other vars
        ////////////////////////////////////////////////////////////////////
        ';

        $output .= "var translation_none = '".__('None')."';\n";
        $output .= "var dialog_node_edit_title = '".__('Edit node %s')."';\n";
        $output .= "var holding_area_title = '".__('Holding Area')."';\n";
        $output .= "var edit_menu = '".__('Show details and options')."';\n";
        $output .= "var interface_link_add = '".__('Add a interface link')."';\n";
        $output .= "var set_parent_link = '".__('Set parent interface')."';\n";
        $output .= "var set_as_children_menu = '".__('Set as children')."';\n";
        $output .= "var set_parent_menu = '".__('Set parent')."';\n";
        $output .= "var abort_relationship_menu = '".__('Abort the action of set relationship')."';\n";
        $output .= "var delete_menu = '".__('Delete')."';\n";
        $output .= "var add_node_menu = '".__('Add node')."';\n";
        $output .= "var set_center_menu = '".__('Set center')."';\n";
        $output .= "var refresh_menu = '".__('Refresh')."';\n";
        $output .= "var refresh_holding_area_menu = '".__('Refresh Holding area')."';\n";
        $output .= "var ok_button = '".__('Proceed')."';\n";
        $output .= "var message_to_confirm = '".__('Resetting the map will delete all customizations you have done, including manual relationships between elements, new items, etc.')."';\n";
        $output .= "var warning_message = '".__('WARNING')."';\n";
        $output .= "var ok_button = '".__('Proceed')."';\n";
        $output .= "var cancel_button = '".__('Cancel')."';\n";
        $output .= "var restart_map_menu = '".__('Restart map')."';\n";
        $output .= "var abort_relationship_interface = '".__('Abort the interface relationship')."';\n";
        $output .= "var abort_relationship_menu = '".__('Abort the action of set relationship')."';\n";

        $output .= '</script>';

        return $output;
    }


    /**
     * Show an advanced interface to manage dialogs.
     *
     * @return string HTML code with dialogs.
     */
    public function loadAdvancedInterface()
    {
        $list_networkmaps = get_networkmaps($this->idMap);
        if (empty($list_networkmaps)) {
            $list_networkmaps = [];
        }

        $output .= '<div id="open_version_dialog" style="display: none;">';
        $output .= __(
            'In the Open version of %s can not be edited nodes or map',
            get_product_name()
        );
        $output .= '</div>';

        $output .= '<div id="dialog_node_edit" style="display: none;" title="';
        $output .= __('Edit node').'">';
        $output .= '<div style="text-align: left; width: 100%;">';

        $table = new StdClass();
        $table->id = 'node_details';
        $table->width = '100%';

        $table->data = [];
        $table->data[0][0] = '<strong>'.__('Agent').'</strong>';
        $table->data[0][1] = '';
        $table->data[1][0] = '<strong>'.__('Adresses').'</strong>';
        $table->data[1][1] = '';
        $table->data[2][0] = '<strong>'.__('OS type').'</strong>';
        $table->data[2][1] = '';
        $table->data[3][0] = '<strong>'.__('Group').'</strong>';
        $table->data[3][1] = '';

        $output .= ui_toggle(
            html_print_table($table, true),
            __('Node Details'),
            __('Node Details'),
            false,
            true
        );

        $table = new StdClass();
        $table->id = 'interface_information';
        $table->width = '100%';

        $table->head['interface_name'] = __('Name');
        $table->head['interface_status'] = __('Status');
        $table->head['interface_graph'] = __('Graph');
        $table->head['interface_ip'] = __('Ip');
        $table->head['interface_mac'] = __('MAC');
        $table->data = [];
        $table->rowstyle['template_row'] = 'display: none;';
        $table->data['template_row']['interface_name'] = '';
        $table->data['template_row']['interface_status'] = '';
        $table->data['template_row']['interface_graph'] = '';
        $table->data['template_row']['interface_ip'] = '';
        $table->data['template_row']['interface_mac'] = '';

        $output .= ui_toggle(
            html_print_table($table, true),
            __('Interface Information (SNMP)'),
            __('Interface Information (SNMP)'),
            true,
            true
        );

        $table = new StdClass();
        $table->id = 'node_options';
        $table->width = '100%';

        $table->data = [];
        $table->data[0][0] = __('Shape');
        $table->data[0][1] = html_print_select(
            [
                'circle'  => __('Circle'),
                'square'  => __('Square'),
                'rhombus' => __('Rhombus'),
            ],
            'shape',
            '',
            'javascript:',
            '',
            0,
            true
        ).'&nbsp;<span id="shape_icon_in_progress" style="display: none;">'.html_print_image('images/spinner.gif', true).'</span><span id="shape_icon_correct" style="display: none;">'.html_print_image('images/dot_green.png', true).'</span><span id="shape_icon_fail" style="display: none;">'.html_print_image('images/dot_red.png', true).'</span>';
        $table->data['node_name'][0] = __('Name');
        $table->data['node_name'][1] = html_print_input_text(
            'edit_name_node',
            '',
            __('name node'),
            '20',
            '50',
            true
        );
        $table->data['node_name'][2] = html_print_button(
            __('Update node'),
            '',
            false,
            '',
            'class="sub"',
            true
        );

        $table->data['fictional_node_name'][0] = __('Name');
        $table->data['fictional_node_name'][1] = html_print_input_text(
            'edit_name_fictional_node',
            '',
            __('name fictional node'),
            '20',
            '50',
            true
        );
        $table->data['fictional_node_networkmap_link'][0] = __('Networkmap to link');
        $table->data['fictional_node_networkmap_link'][1] = html_print_select(
            $list_networkmaps,
            'edit_networkmap_to_link',
            '',
            '',
            '',
            0,
            true
        );
        $table->data['fictional_node_update_button'][0] = '';
        $table->data['fictional_node_update_button'][1] = html_print_button(
            __('Update fictional node'),
            '',
            false,
            'add_fictional_node();',
            'class="sub"',
            true
        );

        $output .= ui_toggle(
            html_print_table($table, true),
            __('Node options'),
            __('Node options'),
            true,
            true
        );

        $table = new StdClass();
        $table->id = 'relations_table';
        $table->width = '100%';

        $table->head = [];
        $table->head['node_source'] = __('Node source');
        $table->head['interface_source'] = __('Interface source');
        $table->head['interface_target'] = __('Interface Target');

        $table->head['node_target'] = __('Node target');
        $table->head['edit'] = '<span title="'.__('Edit').'">'.__('E.').'</span>';

        $table->data = [];
        $table->rowstyle['template_row'] = 'display: none;';
        $table->data['template_row']['node_source'] = '';
        $table->data['template_row']['interface_source'] = html_print_select(
            [],
            'interface_source',
            '',
            '',
            __('None'),
            0,
            true
        );
        $table->data['template_row']['interface_target'] = html_print_select(
            [],
            'interface_target',
            '',
            '',
            __('None'),
            0,
            true
        );

        $table->data['template_row']['node_target'] = '';
        $table->data['template_row']['edit'] = '';

        $table->data['template_row']['edit'] .= '<span class="edit_icon_correct" style="display: none;">'.html_print_image('images/dot_green.png', true).'</span><span class="edit_icon_fail" style="display: none;">'.html_print_image('images/dot_red.png', true).'</span><span class="edit_icon_progress" style="display: none;">'.html_print_image('images/spinner.gif', true).'</span><span class="edit_icon"><a class="edit_icon_link" title="'.__('Update').'" href="#">'.html_print_image('images/config.png', true).'</a></span>';

        $table->data['template_row']['edit'] .= '<a class="delete_icon" href="#">'.html_print_image('images/delete.png', true).'</a>';

        $table->colspan['no_relations']['0'] = 5;
        $table->cellstyle['no_relations']['0'] = 'text-align: center;';
        $table->data['no_relations']['0'] = __('There are not relations');

        $table->colspan['loading']['0'] = 5;
        $table->cellstyle['loading']['0'] = 'text-align: center;';
        $table->data['loading']['0'] = html_print_image(
            'images/wait.gif',
            true
        );

        $output .= ui_toggle(
            html_print_table($table, true),
            __('Relations'),
            __('Relations'),
            true,
            true
        );

        $output .= '</div></div>';

        $output .= '<div id="dialog_interface_link" style="display: none;" title="Interface link">';
        $output .= '<div style="text-align: left; width: 100%;">';

        $table = new stdClass();
        $table->id = 'interface_link_table';
        $table->width = '100%';
        $table->head['node_source_interface'] = __('Node source');
        $table->head['interface_source_select'] = __('Interface source');
        $table->head['interface_target_select'] = __('Interface Target');
        $table->head['node_target_interface'] = __('Node target');

        $table->data = [];

        $table->data['interface_row']['node_source_interface'] = html_print_label('', 'node_source_interface');

        $table->data['interface_row']['interface_source_select'] = html_print_select(
            [],
            'interface_source_select',
            '',
            '',
            __('None'),
            0,
            true
        );

        $table->data['interface_row']['interface_target_select'] = html_print_select(
            [],
            'interface_target_select',
            '',
            '',
            __('None'),
            0,
            true
        );

        $table->data['interface_row']['node_target_interface'] = html_print_label(
            '',
            'node_target_interface'
        );

        $output .= 'br><br>';

        $table->data['interface_row']['interface_link_button'] = html_print_button(
            __('Add interface link'),
            '',
            false,
            'add_interface_link_js();',
            'class="sub"',
            true
        );

        $output .= html_print_table($table, true);
        $output .= '</div></div>';

        $output .= '<div id="dialog_node_add" style="display: none;" title="';
        $output .= __('Add node').'">';
        $output .= '<div style="text-align: left; width: 100%;">';

        $table = new StdClass();
        $table->width = '100%';
        $table->data = [];

        $table->data[0][0] = __('Agent');
        $params = [];
        $params['return'] = true;
        $params['show_helptip'] = true;
        $params['input_name'] = 'agent_name';
        $params['input_id'] = 'agent_name';
        $params['print_hidden_input_idagent'] = true;
        $params['hidden_input_idagent_name'] = 'id_agent';
        $params['disabled_javascript_on_blur_function'] = true;
        $table->data[0][1] = ui_print_agent_autocomplete_input($params);
        $table->data[1][0] = '';
        $table->data[1][1] = html_print_button(
            __('Add agent node'),
            '',
            false,
            'add_agent_node();',
            'class="sub"',
            true
        ).html_print_image(
            'images/error_red.png',
            true,
            [
                'id'         => 'error_red',
                'style'      => 'vertical-align: bottom;display: none;',
                'class'      => 'forced_title',
                'alt'        => 'Esto es una prueba',
                'data-title' => 'data-use_title_for_force_title:1',
            ],
            false
        );

        $add_agent_node_html = html_print_table($table, true);
        $output .= ui_toggle(
            $add_agent_node_html,
            __('Add agent node'),
            __('Add agent node'),
            false,
            true
        );

        $table = new StdClass();
        $table->width = '100%';
        $table->data = [];
        $table->data[0][0] = __('Group');
        $table->data[0][1] = html_print_select_groups(
            false,
            'IW',
            false,
            'group_for_show_agents',
            -1,
            'choose_group_for_show_agents()',
            __('None'),
            -1,
            true
        );
        $table->data[1][0] = __('Agents');
        $table->data[1][1] = html_print_select(
            [-1 => __('None')],
            'agents_filter_group',
            -1,
            '',
            '',
            0,
            true,
            true,
            true,
            '',
            false,
            'width: 170px;',
            false,
            5
        );
        $table->data[2][0] = '';
        $table->data[2][1] = html_print_button(
            __('Add agent node'),
            '',
            false,
            'add_agent_node_from_the_filter_group();',
            'class="sub"',
            true
        );

        $add_agent_node_html = html_print_table($table, true);
        $output .= ui_toggle(
            $add_agent_node_html,
            __('Add agent node (filter by group)'),
            __('Add agent node'),
            true,
            true
        );

        $table = new StdClass();
        $table->width = '100%';
        $table->data = [];
        $table->data[0][0] = __('Name');
        $table->data[0][1] = html_print_input_text(
            'name_fictional_node',
            '',
            __('name fictional node'),
            '20',
            '50',
            true
        );
        $table->data[1][0] = __('Networkmap to link');
        $table->data[1][1] = html_print_select(
            $list_networkmaps,
            'networkmap_to_link',
            '',
            '',
            '',
            0,
            true
        );
        $table->data[2][0] = '';
        $table->data[2][1] = html_print_button(
            __('Add fictional node'),
            '',
            false,
            'add_fictional_node();',
            'class="sub"',
            true
        );
        $add_agent_node_html = html_print_table($table, true);
        $output .= ui_toggle(
            $add_agent_node_html,
            __('Add fictional point'),
            __('Add agent node'),
            true,
            true
        );

        $output .= '</div></div>';

        return $output;
    }


    /**
     * Loads advanced map controller (JS).
     *
     * @return string HTML code for advanced controller.
     */
    public function loadController()
    {
        $output = '';

        // Generate JS for advanced controller.
        $output .= '

<script type="text/javascript">
    ////////////////////////////////////////////////////////////////////////
    // document ready
    ////////////////////////////////////////////////////////////////////////

    $(document).ready(function() {
        init_graph({
            graph: networkmap,
            networkmap_center: networkmap_center,
            networkmap_dimensions: networkmap_dimensions,
            enterprise_installed: enterprise_installed,
            node_radius: node_radius,
            holding_area_dimensions: networkmap_holding_area_dimensions,
            url_background_grid: url_background_grid
        });
        init_drag_and_drop();
        init_minimap();
        function_open_minimap();
        
        $(document.body).on("mouseleave",
            ".context-menu-list",
            function(e) {
                try {
                    $("#networkconsole_'.$this->idMap.'").contextMenu("hide");
                }
                catch(err) {
                }
            }
        );
    });
</script>';

        if ($return === false) {
            echo $output;
        }

        return $output;

    }


    /**
     * Load networkmap HTML skel and JS requires.
     *
     * @return string HTML code for skel.
     */
    public function loadMapSkel()
    {
        global $config;

        ui_require_css_file('networkmap');
        ui_require_css_file('jquery.contextMenu', 'include/styles/js/');

        $output = '';
        $hide_minimap = '';
        if ($dashboard_mode) {
            $hide_minimap = 'none';
        }

        $networkmap = $this->map;
        $networkmap['filter'] = json_decode($networkmap['filter'], true);

        $networkmap['filter']['l2_network_interfaces'] = 1;

        $output .= '<script type="text/javascript" src="'.$config['homeurl'].'include/javascript/d3.3.5.14.js" charset="utf-8"></script>';
        $output .= '<script type="text/javascript" src="'.$config['homeurl'].'include/javascript/jquery.contextMenu.js"></script>';
        $output .= '<script type="text/javascript" src="'.$config['homeurl'].'include/javascript/functions_pandora_networkmap.js"></script>';

        // Open networkconsole_id div.
        $output .= '<div id="networkconsole_'.$networkmap['id'].'"';
        $output .= ' style="position: relative; overflow: hidden; background: #FAFAFA">';

        $output .= '<div style="display: '.$hide_minimap.';">';
        $output .= '<canvas id="minimap_'.$networkmap['id'].'"';
        $output .= ' style="position: absolute; left: 0px; top: 0px; border: 1px solid #bbbbbb;">';
        $output .= '</canvas>';

        $output .= '<div id="arrow_minimap_'.$networkmap['id'].'"';
        $output .= ' style="position: absolute; left: 0px; top: 0px;">';
        $output .= '<a title="'.__('Open Minimap').'" href="javascript: toggle_minimap();">';
        $output .= '<img id="image_arrow_minimap_'.$networkmap['id'].'"';
        $output .= ' src="images/minimap_open_arrow.png" />';
        $output .= '</a><div></div></div>';

        $output .= '<div id="hide_labels_'.$networkmap['id'].'"';
        $output .= ' style="position: absolute; right: 10px; top: 10px;">';
        $output .= '<a title="'.__('Hide Labels').'" href="javascript: hide_labels();">';
        $output .= '<img id="image_hide_show_labels" src="images/icono_borrar.png" />';
        $output .= '</a></div>';

        $output .= '<div id="holding_spinner_'.$networkmap['id'].'" ';
        $output .= ' style="display: none; position: absolute; right: 50px; top: 20px;">';
        $output .= '<img id="image_hide_show_labels" src="images/spinner.gif" />';
        $output .= '</div>';

        // Close networkconsole_id div.
        $output .= "</div>\n";

        return $output;
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
            $output .= $this->loadMapSkel();
            $output .= $this->loadMapData();
            $output .= $this->loadController();
            $output .= $this->loadAdvancedInterface();
        } else {
            // Simple mode, no tmap entries.
            $this->idMap = '0';
            $output .= $this->loadMapSkel();
            $output .= $this->loadMapData();
            $output .= $this->loadController();
        }

        if ($return === false) {
            echo $output;
        }

        return $output;
    }


}
