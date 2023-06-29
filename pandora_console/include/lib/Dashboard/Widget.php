<?php


namespace PandoraFMS\Dashboard;

/**
 * Dashboard manager.
 */
class Widget
{

    /**
     * Dasboard ID.
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
     * Widget Id.
     *
     * @var integer
     */
    protected $widgetId;

    /**
     * Values widget.
     *
     * @var array
     */
    private $values;

    /**
     * Target node Id.
     *
     * @var integer
     */
    protected $nodeId;

    /**
     * Should we show select node in metaconsole environments?
     *
     * @var boolean
     */
    private $showSelectNodeMeta;


    /**
     * Contructor widget.
     *
     * @param integer $cellId      Cell Id.
     * @param integer $dashboardId Dashboard Id.
     * @param integer $widgetId    Widget Id.
     */
    public function __construct(
        int $cellId,
        int $dashboardId,
        int $widgetId
    ) {
        // Check exists Cell id.
        if (empty($widgetId) === false) {
            $this->widgetId = $widgetId;
            $this->cellId = $cellId;
            $this->dashboardId = $dashboardId;
            $this->fields = $this->get();
            $this->className = $this->fields['class_name'];

            $cellClass = new Cell($this->cellId, $this->dashboardId);
            $this->dataCell = $cellClass->get();
            $this->values = $this->decoders($this->getOptionsWidget());
            if (isset($this->values['node']) === true) {
                $this->nodeId = $this->values['node'];
            }
        }

        return $this;
    }


    /**
     * Retrieve a cell definition.
     *
     * @return array cell data.
     */
    public function get()
    {
        $sql = sprintf(
            'SELECT *
            FROM twidget
            WHERE id = %d',
            $this->widgetId
        );

        $data = \db_get_row_sql($sql);

        if ($data === false) {
            return [];
        }

        return $data;
    }


    /**
     * Get options Cell widget configuration.
     *
     * @return array
     */
    public function getOptionsWidget():array
    {
        $result = [];
        if (empty($this->dataCell['options']) === false) {
            $result = \json_decode($this->dataCell['options'], true);

            // Hack retrocompatibility.
            if ($result === null) {
                $result = \unserialize($this->dataCell['options']);
            }
        }

        return $result;
    }


    /**
     * Get options Cell widget configuration.
     *
     * @return array
     */
    public function getPositionWidget():array
    {
        global $config;

        $result = [];
        if (empty($this->dataCell['position']) === false) {
            $result = \json_decode($this->dataCell['position'], true);

            // Hack retrocompatibility.
            if ($result === null) {
                $result = \unserialize($this->dataCell['position']);
            }
        }

        return $result;
    }


    /**
     * Insert widgets.
     *
     * @return void
     */
    public function install()
    {
        $id = db_get_value(
            'id',
            'twidget',
            'unique_name',
            $this->getName()
        );

        if ($id !== false) {
            return;
        }

        $values = [
            'unique_name' => $this->getName(),
            'description' => $this->getDescription(),
            'options'     => '',
            'page'        => $this->page,
            'class_name'  => $this->className,
        ];

        $res = db_process_sql_insert('twidget', $values);
        return $res;
    }


    /**
     * Get all dashboard user can you see.
     *
     * @param integer     $offset Offset query.
     * @param integer     $limit  Limit query.
     * @param string|null $search Search word.
     *
     * @return array Return info all dasboards.
     */
    static public function getWidgets(
        int $offset=-1,
        int $limit=-1,
        ?string $search=''
    ):array {
        global $config;

        $sql_limit = '';
        if ($offset !== -1 && $limit !== -1) {
            $sql_limit = ' LIMIT '.$offset.','.$limit;
        }

        $sql_search = '';
        if (empty($search) === false) {
            $sql_search = 'AND description LIKE "%'.addslashes($search).'%" ';
        }

        // User admin view all dashboards.
        $sql_widget = \sprintf(
            'SELECT * FROM twidget
            WHERE 1=1
            %s
            ORDER BY `description` %s',
            $sql_search,
            $sql_limit
        );

        $widgets = \db_get_all_rows_sql($sql_widget);

        if ($widgets === false) {
            $widgets = [];
        }

        return $widgets;
    }


    /**
     * Install Widgets.
     *
     * @param integer $cellId Cell ID.
     *
     * @return void
     */
    public static function dashboardInstallWidgets(int $cellId)
    {
        global $config;

        $dir = $config['homedir'].'/include/lib/Dashboard/Widgets/';
        $handle = opendir($dir);
        if ($handle === false) {
            return;
        }

        $ignores = [
            '.',
            '..',
        ];

        while (false !== ($file = readdir($handle))) {
            if (in_array($file, $ignores) === true) {
                continue;
            }

            $filepath = realpath($dir.'/'.$file);
            if (is_readable($filepath) === false
                || is_dir($filepath) === true
                || preg_match('/.*\.php$/', $filepath) === false
            ) {
                continue;
            }

            $name = preg_replace('/.php/', '', $file);
            $className = 'PandoraFMS\Dashboard';
            $not_installed = false;
            switch ($name) {
                case 'agent_module':
                    $className .= '\AgentModuleWidget';
                break;

                case 'alerts_fired':
                    $className .= '\AlertsFiredWidget';
                break;

                case 'clock':
                    $className .= '\ClockWidget';
                break;

                case 'custom_graph':
                    $className .= '\CustomGraphWidget';
                break;

                case 'events_list':
                    $className .= '\EventsListWidget';
                break;

                case 'example':
                    $className .= '\WelcomeWidget';
                break;

                case 'graph_module_histogram':
                    $className .= '\GraphModuleHistogramWidget';
                break;

                case 'groups_status':
                    $className .= '\GroupsStatusWidget';
                break;

                case 'maps_made_by_user':
                    $className .= '\MapsMadeByUser';
                break;

                case 'maps_status':
                    $className .= '\MapsStatusWidget';
                break;

                case 'module_icon':
                    $className .= '\ModuleIconWidget';
                break;

                case 'module_status':
                    $className .= '\ModuleStatusWidget';
                break;

                case 'module_table_value':
                    $className .= '\ModuleTableValueWidget';
                break;

                case 'module_value':
                    $className .= '\ModuleValueWidget';
                break;

                case 'monitor_health':
                    $className .= '\MonitorHealthWidget';
                break;

                case 'network_map':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\NetworkMapWidget';
                break;

                case 'post':
                    $className .= '\PostWidget';
                break;

                case 'reports':
                    $className .= '\ReportsWidget';
                break;

                case 'service_map':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\ServiceMapWidget';
                break;

                case 'service_view':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\ServiceViewWidget';
                break;

                case 'single_graph':
                    $className .= '\SingleGraphWidget';
                break;

                case 'sla_percent':
                    $className .= '\SLAPercentWidget';
                break;

                case 'system_group_status':
                    $className .= '\SystemGroupStatusWidget';
                break;

                case 'tactical':
                    $className .= '\TacticalWidget';
                break;

                case 'top_n_events_by_module':
                    $className .= '\TopNEventByModuleWidget';
                break;

                case 'top_n_events_by_group':
                    $className .= '\TopNEventByGroupWidget';
                break;

                case 'top_n':
                    $className .= '\TopNWidget';
                break;

                case 'tree_view':
                    $className .= '\TreeViewWidget';
                break;

                case 'url':
                    $className .= '\UrlWidget';
                break;

                case 'wux_transaction_stats':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\WuxStatsWidget';
                break;

                case 'wux_transaction':
                    if (\enterprise_installed() === false) {
                        $not_installed = true;
                    }

                    $className .= '\WuxWidget';
                break;

                case 'os_quick_report':
                    $className .= '\OsQuickReportWidget';
                break;

                case 'GroupedMeterGraphs':
                case 'ColorModuleTabs':
                case 'BlockHistogram':
                case 'DataMatrix':
                case 'EventCardboard':
                case 'ModulesByStatus':
                case 'AvgSumMaxMinModule':
                case 'BasicChart':
                    $className .= '\\'.$name;
                break;

                case 'heatmap':
                    $className .= '\HeatmapWidget';
                break;

                default:
                    $className = false;
                break;
            }

            if ($not_installed === false && $className !== false) {
                include_once $filepath;
                $instance = new $className($cellId, 0, 0);
                if (method_exists($instance, 'install') === true) {
                    $instance->install();
                }
            }
        }

        closedir($handle);
    }


    /**
     * Draw html.
     *
     * @return string Html data.
     */
    public function printHtml()
    {
        global $config;

        $output = '';

        if ((bool) \is_metaconsole() === true) {
            \enterprise_include_once('include/functions_metaconsole.php');
            if ($this->nodeId > 0) {
                if (\metaconsole_connect(null, $this->nodeId) !== NOERR) {
                    $output .= '<div class="container-center">';
                    $output .= \ui_print_info_message(
                        __('Failed to connect to node %d', $this->nodeId),
                        '',
                        true
                    );
                    $output .= '</div>';
                    return $output;
                }

                $config['metaconsole'] = false;
            }
        }

        if ($this->configurationRequired === true) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_info_message(
                __('Please configure this widget before usage'),
                '',
                true
            );
            $output .= '</div>';
        } else if ($this->loadError === true) {
            $output .= '<div class="container-center">';
            $output .= \ui_print_error_message(
                __('Widget cannot be loaded').'. '.__('Please, configure the widget again to recover it'),
                '',
                true
            );
            $output .= '</div>';
        } else {
            $output .= $this->load();
        }

        if ((bool) \is_metaconsole() === true) {
            if ($this->nodeId > 0) {
                \metaconsole_restore_db();
                $config['metaconsole'] = true;
            }
        }

        return $output;
    }


    /**
     * Generates inputs for form.
     *
     * @return array Of inputs.
     */
    public function getFormInputs(): array
    {
        global $config;

        $inputs = [];

        $values = $this->values;

        // Default values.
        if (isset($values['title']) === false) {
            $values['title'] = $this->getDescription();
        }

        if (empty($values['background']) === true) {
            $values['background'] = '#ffffff';

            if ($config['style'] === 'pandora_black'
                && is_metaconsole() === false
            ) {
                $values['background'] = '#222222';
            }
        }

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'dashboardId',
                'value' => $this->dashboardId,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'cellId',
                'value' => $this->cellId,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'widgetId',
                'value' => $this->widgetId,
            ],
        ];

        $inputs[] = [
            'label'     => __('Title'),
            'arguments' => [
                'type'   => 'text',
                'name'   => 'title',
                'value'  => $values['title'],
                'return' => true,
                'size'   => 0,
            ],
        ];

        $inputs[] = [
            'label'     => __('Background'),
            'arguments' => [
                'wrapper' => 'div',
                'name'    => 'background',
                'type'    => 'color',
                'value'   => $values['background'],
                'return'  => true,
            ],
        ];

        if ((bool) \is_metaconsole() === true
            && $this->shouldSelectNode() === true
        ) {
            \enterprise_include_once('include/functions_metaconsole.php');
            $servers = \metaconsole_get_servers();
            if (is_array($servers) === true) {
                $servers = array_reduce(
                    $servers,
                    function ($carry, $item) {
                        $carry[$item['id']] = $item['server_name'];
                        return $carry;
                    }
                );
            } else {
                $servers = [];
            }

            $inputs[] = [
                'label'     => __('Node'),
                'arguments' => [
                    'wrapper'       => 'div',
                    'name'          => 'node',
                    'type'          => 'select',
                    'fields'        => $servers,
                    'selected'      => $values['node'],
                    'nothing'       => __('This metaconsole'),
                    'nothing_value' => -1,
                    'return'        => true,
                ],
            ];
        }

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        $values = [];
        $values['title'] = \get_parameter('title', '');
        $values['background'] = \get_parameter('background', '#ffffff');
        if ((bool) \is_metaconsole() === true) {
            if ($this->shouldSelectNode() === true) {
                $values['node'] = \get_parameter('node', null);
            } else {
                $values['node'] = \get_parameter('metaconsoleId', null);
            }
        }

        return $values;

    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder):array
    {
        $values = [];

        if (isset($decoder['title']) === true) {
            $values['title'] = $decoder['title'];
        }

        if (isset($decoder['background-color']) === true) {
            $values['background'] = $decoder['background-color'];
        }

        if (isset($decoder['background']) === true) {
            $values['background'] = $decoder['background'];
        }

        if (isset($decoder['node']) === true) {
            $values['node'] = $decoder['node'];
        }

        return $values;

    }


    /**
     * Size Cell.
     *
     * @return array
     */
    protected function getSize():array
    {
        $gridWidth = $this->gridWidth;
        if ($this->gridWidth === 0) {
            $gridWidth = 1170;
        }

        if ($this->width === 0) {
            $width = (((int) $this->position['width'] / 12 * $gridWidth) - 60);
        } else {
            $width = (((int) $this->width / 12 * $gridWidth) - 60);
        }

        if ($this->height === 0) {
            $height = ((((int) $this->position['height'] - 1) * 80) + 60 - 30);
        } else {
            $height = ((((int) $this->height - 1) * 80) + 60 - 30);
        }

        $result = [
            'width'  => $width,
            'height' => $height,
        ];

        return $result;
    }


    /**
     * Should select for nodes been shown while in metaconsole environment?
     *
     * @return boolean
     */
    protected function shouldSelectNode():bool
    {
        if ($this->showSelectNodeMeta !== null) {
            return (bool) $this->showSelectNodeMeta;
        }

        switch ($this->className) {
            case 'EventsListWidget':
            case 'ReportsWidget':
            case 'MapsMadeByUser':
            case 'AlertsFiredWidget':
                $this->showSelectNodeMeta = true;
            break;

            default:
                $this->showSelectNodeMeta = false;
            break;
        }

        return (bool) $this->showSelectNodeMeta;
    }


    /**
     * Get description should be implemented for each child.
     *
     * @return string
     */
    public static function getDescription()
    {
        return '**NOT DEFINED**';
    }


    /**
     * Load should be implemented for each child.
     *
     * @return string
     */
    public function load()
    {
        return '**NOT DEFINED**';
    }


    /**
     * Get name should be implemented for each child.
     *
     * @return string
     */
    public static function getName()
    {
        return '**NOT DEFINED**';
    }


    /**
     * Return aux javascript code for forms.
     *
     * @return string
     */
    public function getFormJS()
    {
        return '';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration():array
    {
        $size = [
            'width'  => 400,
            'height' => 650,
        ];

        return $size;
    }


}
