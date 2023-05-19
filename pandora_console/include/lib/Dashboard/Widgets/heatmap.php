<?php
/**
 * Widget Heatmap Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Heatmap
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
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

namespace PandoraFMS\Dashboard;

global $config;

require_once $config['homedir'].'/include/class/Heatmap.class.php';

use PandoraFMS\Heatmap;

/**
 * Heatmap Widgets.
 */
class HeatmapWidget extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Dashboard ID.
     *
     * @var integer
     */
    protected $dashboardId;

    /**
     * Cell ID.
     *
     * @var integer
     */
    protected $cellId;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0
    ) {
        global $config;

        // Includes.
        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Cell Id.
        $this->cellId = $cellId;

        // Widget ID.
        $this->widgetId = $widgetId;

        // Dashboard ID.
        $this->dashboardId = $dashboardId;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Heatmap');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'heatmap';
        }
    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['search']) === true) {
            $values['search'] = $decoder['search'];
        }

        if (isset($decoder['type']) === true) {
            $values['type'] = $decoder['type'];
        }

        if (isset($decoder['groups']) === true) {
            $values['groups'] = $decoder['groups'];
        }

        if (isset($decoder['tags']) === true) {
            $values['tags'] = $decoder['tags'];
        }

        if (isset($decoder['module_groups']) === true) {
            $values['module_groups'] = $decoder['module_groups'];
        }

        return $values;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        global $config;
        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $values = $this->values;

        // Search.
        $inputs[] = [
            'label'     => \__('Search'),
            'arguments' => [
                'name'   => 'search',
                'type'   => 'text',
                'class'  => 'event-widget-input',
                'value'  => $values['search'],
                'return' => true,
                'size'   => 30,
            ],
        ];

        $inputs[] = [
            'label'     => __('Type'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => [
                    0 => __('Group agents'),
                    1 => __('Group modules by tag'),
                    2 => __('Group modules by module group'),
                    3 => __('Group modules by agents'),
                ],
                'name'     => 'type',
                'selected' => $values['type'],
                'script'   => 'type_change()',
                'return'   => true,
            ],
        ];

        // Filters.
        $inputs[] = [
            'label'     => __('Groups'),
            'style'     => ($values['type'] === '0' || $values['type'] === '3') ? '' : 'display:none',
            'id'        => 'li_groups',
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groups[]',
                'returnAllGroup' => true,
                'privilege'      => 'AR',
                'selected'       => explode(',', $values['groups'][0]),
                'return'         => true,
                'multiple'       => true,
            ],
        ];

        if (tags_has_user_acl_tags($config['id_user']) === false) {
            $tags = db_get_all_rows_sql(
                'SELECT id_tag, name FROM ttag WHERE id_tag ORDER BY name'
            );
        } else {
            $user_tags = tags_get_user_tags($config['id_user'], 'AR');
            if (empty($user_tags) === false) {
                $id_user_tags = array_keys($user_tags);
                $tags = db_get_all_rows_sql(
                    'SELECT id_tag, name FROM ttag
                    WHERE id_tag IN ('.implode(',', $id_user_tags).')
                    ORDER BY name'
                );
            } else {
                $tags = db_get_all_rows_sql(
                    'SELECT id_tag, name FROM ttag WHERE id_tag ORDER BY name'
                );
            }
        }

        $inputs[] = [
            'label'     => __('Tag'),
            'style'     => ($values['type'] === '1') ? '' : 'display:none',
            'id'        => 'li_tags',
            'arguments' => [
                'type'     => 'select',
                'fields'   => $tags,
                'name'     => 'tags[]',
                'selected' => explode(',', $values['tags'][0]),
                'return'   => true,
                'multiple' => true,
            ],
        ];

        $module_groups_aux = db_get_all_rows_sql(
            'SELECT id_mg, name FROM tmodule_group ORDER BY name'
        );

        $module_groups = [];
        foreach ($module_groups_aux as $key => $module_group) {
            $module_groups[$module_group['id_mg']] = $module_group['name'];
        }

        $inputs[] = [
            'label'     => __('Module group'),
            'style'     => ($values['type'] === '2') ? '' : 'display:none',
            'id'        => 'li_module_groups',
            'arguments' => [
                'type'          => 'select',
                'fields'        => $module_groups,
                'name'          => 'module_groups[]',
                'selected'      => explode(',', $values['module_groups'][0]),
                'return'        => true,
                'multiple'      => true,
                'nothing'       => __('Not assigned'),
                'nothing_value' => 0,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost(): array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['search'] = \get_parameter('search', '');
        $values['type'] = \get_parameter('type', 0);

        switch ((int) $values['type']) {
            case 2:
                $values['module_groups'] = \get_parameter('module_groups', 0);
            break;

            case 1:
                $values['tags'] = \get_parameter('tags', 0);
            break;

            case 0:
            case 3:
                $values['groups'] = \get_parameter('groups', 0);
            break;

            default:
                // Do nothing.
            break;
        }

        return $values;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Heatmap');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'heatmap';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => 500,
            'height' => 300,
        ];

        return $size;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        \ui_require_css_file('heatmap', 'include/styles/', true);

        $values = $this->values;
        $search = (empty($values['search']) === false) ? $values['search'] : '';
        $type = (empty($values['type']) === false) ? $values['type'] : 0;
        $filter = [];
        if (isset($values['groups'])) {
            $filter = explode(',', $values['groups'][0]);
        }

        if (isset($values['tags'])) {
            $filter = explode(',', $values['tags'][0]);
        }

        if (isset($values['module_groups'])) {
            $filter = explode(',', $values['module_groups'][0]);
        }

        // Public dashboard.
        $auth_hash = get_parameter('auth_hash', '');
        $public_user = get_parameter('id_user', '');

        // Control call flow.
        $heatmap = new Heatmap($type, $filter, null, 300, 400, 200, $search, 0, true, $auth_hash, $public_user);
        // AJAX controller.
        if (is_ajax() === true) {
            $method = get_parameter('method');

            if ($method === 'drawWidget') {
                // Run.
                $heatmap->run();
            } else {
                if (method_exists($heatmap, $method) === true) {
                    if ($heatmap->ajaxMethod($method) === true) {
                        $heatmap->{$method}();
                    } else {
                        echo 'Unavailable method';
                    }
                } else {
                    echo 'Method not found';
                }

                // Stop any execution.
                exit;
            }
        } else {
            // Run.
            $heatmap->run();

            // Dialog.
            echo '<div id="config_dialog" style="padding:15px" class="invisible"></div>';
        }

        return '';
    }


}
