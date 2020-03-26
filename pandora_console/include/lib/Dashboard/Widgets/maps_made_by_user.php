<?php
/**
 * Widget Maps by users Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Maps by users
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
// Load Visual Console.
use Models\VisualConsole\Container as VisualConsole;

/**
 * Maps by users Widgets.
 */
class MapsMadeByUser extends Widget
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
     * Cell Id.
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

        // Include.
        include_once $config['homedir'].'/include/graphs/functions_d3.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';

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

        // Cell Id.
        $this->cellId = $cellId;

        // Options.
        $this->values = $this->getOptionsWidget();

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Visual Console');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'maps_made_by_user';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['vcId']) === true) {
            $this->configurationRequired = true;
        }

        $this->overflow_scrollbars = false;
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

        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        $fields = \visual_map_get_user_layouts($config['id_user'], true);

        // Event Type.
        $inputs[] = [
            'label'     => __('Event type'),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'vcId',
                'selected' => $values['vcId'],
                'return'   => true,
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

        $values['vcId'] = \get_parameter('vcId', 0);

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

        $visualConsole = null;
        try {
            $visualConsole = VisualConsole::fromDB(
                ['id' => $this->values['vcId']]
            );
        } catch (Throwable $e) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access visual console without Id'
            );
            include 'general/noaccess.php';
            exit;
        }

        $size['width'] = ($size['width'] + 30);

        $visualConsoleData = $visualConsole->toArray();
        $ratio_visualconsole = ($visualConsoleData['height'] / $visualConsoleData['width']);
        $ratio_t = ($size['width'] / $visualConsoleData['width']);
        $radio_h = ($size['height'] / $visualConsoleData['height']);

        $visualConsoleData['width'] = $size['width'];
        $visualConsoleData['height'] = ($size['width'] * $ratio_visualconsole);

        if ($visualConsoleData['height'] > $size['height']) {
            $ratio_t = $radio_h;

            $visualConsoleData['height'] = $size['height'];
            $visualConsoleData['width'] = ($size['height'] / $ratio_visualconsole);
        }

        $groupId = $visualConsoleData['groupId'];
        $visualConsoleName = $visualConsoleData['name'];

        // ACL.
        $aclRead = check_acl($config['id_user'], $groupId, 'VR');
        $aclWrite = check_acl($config['id_user'], $groupId, 'VW');
        $aclManage = check_acl($config['id_user'], $groupId, 'VM');

        if ($aclRead === 0 && $aclWrite === 0 && $aclManage === 0) {
            db_pandora_audit(
                'ACL Violation',
                'Trying to access visual console without group access'
            );
            include 'general/noaccess.php';
            exit;
        }

        $uniq = uniqid();

        $output = '<div class="container-center">';
        // Style.
        $style = 'width:'.$visualConsoleData['width'].'px;';
        // Class.
        $class = 'visual-console-container-dashboard c-'.$uniq;
        // Id.
        $id = 'visual-console-container-'.$this->cellId;
        $output .= '<div style="'.$style.'" class="'.$class.'" id="'.$id.'">';
        $output .= '</div>';
        $output .= '</div>';

        // Check groups can access user.
        $aclUserGroups = [];
        if (users_can_manage_group_all('AR') === true) {
            $aclUserGroups = array_keys(
                users_get_groups(false, 'AR')
            );
        }

        $ignored_params['refr'] = '';
        \ui_require_javascript_file(
            'tiny_mce',
            'include/javascript/tiny_mce/'
        );
        \ui_require_javascript_file(
            'pandora_visual_console',
            'include/javascript/',
            true
        );
        \include_javascript_d3();
        \visual_map_load_client_resources();

        // Load Visual Console Items.
        $visualConsoleItems = VisualConsole::getItemsFromDB(
            $this->values['vcId'],
            $aclUserGroups,
            $ratio_t
        );

        // Horrible trick! due to the use of tinyMCE
        // it is necessary to modify specific classes of each
        // of the visual consoles.
        $output .= '<style type="text/css">';
        $output .= '.c-'.$uniq.', .c-'.$uniq.' *:not(.parent_graph p table tr td span) { font-size: '.(8 * $ratio_t).'pt; line-height:'.(8 * ($ratio_t) * 1.5).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_4pt, .c-'.$uniq.' .visual_font_size_4pt * { font-size: '.(4 * $ratio_t).'pt; line-height:'.(4 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_6pt, .c-'.$uniq.' .visual_font_size_6pt * { font-size: '.(6 * $ratio_t).'pt; line-height:'.(6 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_8pt, .c-'.$uniq.' .visual_font_size_8pt * { font-size: '.(8 * $ratio_t).'pt; line-height:'.(8 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_10pt, .c-'.$uniq.' .visual_font_size_10pt * { font-size: '.(10 * $ratio_t).'pt; line-height:'.(10 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_12pt, .c-'.$uniq.' .visual_font_size_12pt * { font-size: '.(12 * $ratio_t).'pt; line-height:'.(12 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_14pt, .c-'.$uniq.' .visual_font_size_14pt * { font-size: '.(14 * $ratio_t).'pt; line-height:'.(14 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_18pt, .c-'.$uniq.' .visual_font_size_18pt * { font-size: '.(18 * $ratio_t).'pt; line-height:'.(18 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_24pt, .c-'.$uniq.' .visual_font_size_24pt * { font-size: '.(24 * $ratio_t).'pt; line-height:'.(24 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_28pt, .c-'.$uniq.' .visual_font_size_28pt * { font-size: '.(28 * $ratio_t).'pt; line-height:'.(28 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_36pt, .c-'.$uniq.' .visual_font_size_36pt * { font-size: '.(36 * $ratio_t).'pt; line-height:'.(36 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_48pt, .c-'.$uniq.' .visual_font_size_48pt * { font-size: '.(48 * $ratio_t).'pt; line-height:'.(48 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_60pt, .c-'.$uniq.' .visual_font_size_60pt * { font-size: '.(60 * $ratio_t).'pt; line-height:'.(60 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_72pt, .c-'.$uniq.' .visual_font_size_72pt * { font-size: '.(72 * $ratio_t).'pt; line-height:'.(72 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_84pt, .c-'.$uniq.' .visual_font_size_84pt * { font-size: '.(84 * $ratio_t).'pt; line-height:'.(84 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_96pt, .c-'.$uniq.' .visual_font_size_96pt * { font-size: '.(96 * $ratio_t).'pt; line-height:'.(96 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_116pt, .c-'.$uniq.' .visual_font_size_116pt * { font-size: '.(116 * $ratio_t).'pt; line-height:'.(116 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_128pt, .c-'.$uniq.' .visual_font_size_128pt * { font-size: '.(128 * $ratio_t).'pt; line-height:'.(128 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_140pt, .c-'.$uniq.' .visual_font_size_140pt * { font-size: '.(140 * $ratio_t).'pt; line-height:'.(140 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_154pt, .c-'.$uniq.' .visual_font_size_154pt * { font-size: '.(154 * $ratio_t).'pt; line-height:'.(154 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .visual_font_size_196pt, .c-'.$uniq.' .visual_font_size_196pt * { font-size: '.(196 * $ratio_t).'pt; line-height:'.(196 * ($ratio_t)).'pt; }';
        $output .= '.c-'.$uniq.' .flot-text, .c-'.$uniq.' .flot-text * { font-size: '.(8 * $ratio_t).'pt !important; line-height:'.(8 * ($ratio_t)).'pt !important; }';
        $output .= '.c-'.$uniq.' .visual-console-item .digital-clock span.time {font-size: '.(50 * $ratio_t).'px !important; line-height: '.(50 * $ratio_t).'px !important;}';
        $output .= '.c-'.$uniq.' .visual-console-item .digital-clock span.date {font-size: '.(25 * $ratio_t).'px !important; line-height: '.(25 * $ratio_t).'px !important;}';
        $output .= '.c-'.$uniq.' .visual-console-item .digital-clock span.timezone {font-size: '.(25 * $ratio_t).'px !important; line-height: '.(25 * $ratio_t).'px !important;}';
        $output .= '.c-'.$uniq.' .visual-console-item .donut-graph * {font-size: '.(8 * $ratio_t).'px !important; line-height: '.(8 * $ratio_t).'px !important;}';
        $output .= '.c-'.$uniq.' .visual-console-item .donut-graph g rect {width:'.(25 * $ratio_t).' !important; height: '.(15 * $ratio_t).' !important;}';
        $output .= '</style>';

        $visualConsoleItems = array_reduce(
            $visualConsoleItems,
            function ($carry, $item) use ($ratio_t) {
                $carry[] = $item->toArray();
                return $carry;
            },
            []
        );

        $settings = \json_encode(
            [
                'props'   => $visualConsoleData,
                'items'   => $visualConsoleItems,
                'baseUrl' => ui_get_full_url('/', false, false, false),
                'ratio'   => $ratio_t,
                'size'    => $size,
                'cellId'  => $this->cellId,
            ]
        );

        $output .= '<script type="text/javascript">';
        $output .= '$(document).ready(function () {';
        $output .= 'dashboardLoadVC('.$settings.')';
        $output .= '});';
        $output .= '</script>';

        return $output;
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Visual Console');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'maps_made_by_user';
    }


}
