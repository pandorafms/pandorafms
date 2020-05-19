<?php
/**
 * Widget Reports Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Reports
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

namespace PandoraFMS\Dashboard;

global $config;
require_once $config['homedir'].'/include/Image/image_functions.php';
require_once $config['homedir'].'/include/functions_reporting_html.php';
require_once $config['homedir'].'/include/functions_reports.php';
require_once $config['homedir'].'/include/functions_groups.php';

/**
 * Reports Widgets.
 */
class ReportsWidget extends Widget
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
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

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

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Custom report');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'reports';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['reportId']) === true) {
            $this->configurationRequired = true;
        }

        $this->overflow_scrollbars = false;
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

        if (isset($decoder['id_report']) === true) {
            $values['reportId'] = $decoder['id_report'];
        }

        if (isset($decoder['reportId']) === true) {
            $values['reportId'] = $decoder['reportId'];
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
        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        // Reports.
        $reports = \reports_get_reports(false, ['id_report', 'name']);
        $fields = array_reduce(
            $reports,
            function ($carry, $item) {
                $carry[$item['id_report']] = $item['name'];
                return $carry;
            },
            []
        );

        $inputs[] = [
            'label'     => __('Report'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'reportId',
                'selected' => $values['reportId'],
                'return'   => true,
                'style'    => 'width: inherit;',
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['reportId'] = \get_parameter('reportId', 0);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        $size = parent::getSize();

        $output = '';
        ob_start();
        if ($this->values['reportId'] !== 0) {
            $output .= '<div style="width:90%; height:100%; display:flex; flex-direction:column;">';
            $this->printReport();
            $output .= ob_get_clean();

            $output .= 'Click to view: <a href="?sec=reporting&sec2=operation/reporting/reporting_viewer&id='.$this->values['reportId'].'">'.__('Report').'</a>';
            $output .= '</div>';
        } else {
            $this->load_error = true;
        }

        return $output;
    }


    /**
     * Draw Report.
     *
     * @return mixed
     */
    public function printReport()
    {
        global $config;

        $id_report = $this->values['reportId'];

        // Get Report record (to get id_group).
        $report = db_get_row('treport', 'id_report', $id_report);

        // Include with the functions to calculate each kind of report.
        include_once $config['homedir'].'/include/functions_reporting.php';

        // Check if the report is a private report.
        if (empty($report) === true
            || ($report['private'] === true
            && ($report['id_user'] !== $config['id_user']
            && is_user_admin($config['id_user']) === false))
        ) {
            include $config['homedir'].'/general/noaccess.php';
            return '';
        }

        // Get different date to search the report.
        $utimestamp = get_system_time();
        $date = date('Y-m-j', $utimestamp);
        $time = date('h:iA', $utimestamp);

        $report['datetime'] = $utimestamp;

        // Evaluate if it's better to render blocks when are calculated
        // (enabling realtime flush) or if it's better to wait report to be
        // finished before showing anything (this could break the execution by
        // overflowing the running PHP memory on HUGE reports).
        $table = new \stdClass();
        $table->size = [];
        $table->style = [];
        $table->width = '99%';
        $table->class = 'databox report_table';
        $table->rowclass = [];
        $table->rowclass[0] = 'datos3';

        $report['group_name'] = groups_get_name($report['id_group']);

        $contents = db_get_all_rows_field_filter(
            'treport_content',
            'id_report',
            $id_report,
            '`order`'
        );

        if ($contents === false) {
            return '';
        }

        $report = reporting_make_reporting_data(
            null,
            $id_report,
            $date,
            $time,
            false,
            'dinamic'
        );

        reporting_html_print_report($report, true);
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Custom report');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'reports';
    }


}
