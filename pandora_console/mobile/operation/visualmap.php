<?php
// phpcs:disable Squiz.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 * Visual console mobile viewer class.
 *
 * @category   Mix
 * @package    Pandora FMS
 * @subpackage OpenSource
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
require_once '../include/functions_visual_map.php';
use Models\VisualConsole\Container as VisualConsole;

/**
 * Visual console view handler class.
 */
class Visualmap
{

    /**
     * Undocumented variable
     *
     * @var boolean
     */
    private $validAcl = false;

    /**
     * Undocumented variable
     *
     * @var boolean
     */
    private $acl = 'VR';

    /**
     * Undocumented variable
     *
     * @var boolean
     */
    private $id = 0;

    /**
     * Undocumented variable
     *
     * @var boolean
     */
    private $visualmap = null;

    /**
     * View widh.
     *
     * @var integer
     */
    private $width;

    /**
     * View height.
     *
     * @var integer
     */
    private $height;

    /**
     * Rotate view.
     *
     * @var boolean
     */
    private $rotate = false;


    /**
     * Constructor.
     */
    public function __construct()
    {
    }


    /**
     * Verifies ACL access.
     *
     * @param integer $groupID Target group id.
     *
     * @return void
     */
    private function checkVisualmapACL(int $groupID=0)
    {
        $system = System::getInstance();

        if ($system->checkACL($this->acl)) {
            $this->validAcl = true;
        } else {
            $this->validAcl = false;
        }
    }


    /**
     * Retrieve filters.
     *
     * @return void
     */
    private function getFilters()
    {
        $system = System::getInstance();

        $this->id = (int) $system->getRequest('id', 0);
        $this->width = (int) $system->getRequest('width', 0);
        $this->height = (int) $system->getRequest('height', 0);

        if ($this->width < $this->height) {
            $w = $this->width;
            $this->width = $this->height;
            $this->height = $w;
            $this->rotate = true;
        }
    }


    /**
     * Renders the view.
     *
     * @return void
     */
    public function show()
    {
        $this->getFilters();

        if (empty($this->width) === true
            && empty($this->height) === true
        ) {
            // Reload forcing user to send width and height.
            $ui = Ui::getInstance();
            $ui->retrieveViewPort();
        }

        $this->height -= 39;

        $this->visualmap = db_get_row(
            'tlayout',
            'id',
            $this->id
        );

        if (empty($this->visualmap)) {
            $this->show_fail_acl();
        }

        $this->checkVisualmapACL($this->visualmap['id_group']);
        if (!$this->validAcl) {
            $this->show_fail_acl();
        }

        $this->show_visualmap();
    }


    /**
     * Shows an error if ACL fails.
     *
     * @param string $msg Optional message.
     *
     * @return void
     */
    private function show_fail_acl(string $msg='')
    {
        $error['type'] = 'onStart';
        if (empty($msg) === false) {
            $error['title_text'] = __('Error');
            $error['content_text'] = $msg;
        } else {
            $error['title_text'] = __('You don\'t have access to this page');
            $error['content_text'] = System::getDefaultACLFailText();
        }

        if (class_exists('HomeEnterprise') === true) {
            $home = new HomeEnterprise();
        } else {
            $home = new Home();
        }

        $home->show($error);
    }


    /**
     * Ajax call manager.
     *
     * @param string $parameter2 Not sure why is doing this stuff.
     *
     * @return void
     */
    public function ajax(string $parameter2='')
    {
        $system = System::getInstance();
        $this->checkVisualmapACL($this->visualmap['id_group']);
        if ((bool) $this->validAcl === false) {
            $this->show_fail_acl();
        } else {
            switch ($parameter2) {
                case 'render_map':
                    $map_id = $system->getRequest('map_id', '0');
                    $width = $system->getRequest('width', '400');
                    $height = $system->getRequest('height', '400');
                    visual_map_print_visual_map(
                        $map_id,
                        false,
                        true,
                        $width,
                        $height
                    );
                exit;

                default:
                exit;
            }
        }
    }


    /**
     * Generates HTML code to view target Visual console.
     *
     * @return void
     */
    private function show_visualmap()
    {
        $ui = Ui::getInstance();
        $system = System::getInstance();

        include_once $system->getConfig('homedir').'/vendor/autoload.php';

        // Query parameters.
        $visualConsoleId = (int) $system->getRequest('id');

        // Refresh interval in seconds.
        $refr = (int) get_parameter('refr', $system->getConfig('vc_refr'));

        // Check groups can access user.
        $aclUserGroups = [];
        if (!users_can_manage_group_all('AR')) {
            $aclUserGroups = array_keys(users_get_groups(false, 'AR'));
        }

        // Load Visual Console.
        $visualConsole = null;
        try {
            $visualConsole = VisualConsole::fromDB(['id' => $visualConsoleId]);
        } catch (Throwable $e) {
            $this->show_fail_acl($e->getMessage());
            exit;
        }

        $ui->createPage();
        $ui->createDefaultHeader(
            sprintf(
                '%s',
                $this->visualmap['name']
            ),
            $ui->createHeaderButton(
                [
                    'icon' => 'back',
                    'pos'  => 'left',
                    'text' => __('Back'),
                    'href' => 'index.php?page=visualmaps',
                ]
            )
        );

        $ui->require_css('visual_maps');
        $ui->require_css('register');
        $ui->require_css('dashboards');
        $ui->require_javascript('pandora_visual_console');
        $ui->require_javascript('pandora_dashboards');
        $ui->require_javascript('jquery.cookie');
        $ui->require_css('modal');
        $ui->require_css('form');

        $ui->showFooter(false);
        $ui->beginContent();
        $ui->contentAddHtml(
            include_javascript_d3(true)
        );

        $size = [
            'width'  => $this->width,
            'height' => $this->height,
        ];

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

        $uniq = uniqid();

        $output = '<div class="container-center" style="position:relative;">';
        // Style.
        $style = 'width:'.$visualConsoleData['width'].'px;';
        $style .= 'height:'.$visualConsoleData['height'].'px;';
        $style .= 'background-size: cover;';

        // Class.
        $class = 'visual-console-container-dashboard c-'.$uniq;
        // Id.
        $id = 'visual-console-container-'.$uniq;
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
            $visualConsoleId,
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
                'cellId'  => $uniq,
            ]
        );

        $output .= '<script type="text/javascript">';
        $output .= '$(document).ready(function () {';
        $output .= 'dashboardLoadVC('.$settings.')';
        $output .= '});';
        if ($this->rotate === true) {
            $output .= "$('.container-center').css('transform', 'rotate(90deg)');";
        }

        $output .= '$( window ).on( "orientationchange", function( event )';
        $output .= ' { window.location.href = "';
        $output .= ui_get_full_url(
            '/mobile/index.php?page=visualmap&id='.$visualConsoleId
        );
        $output .= '" });';

        $output .= '</script>';

        $ui->contentAddHtml($output);

        // Load Visual Console Items.
        $visualConsoleItems = VisualConsole::getItemsFromDB(
            $visualConsoleId,
            $aclUserGroups
        );

        $javascript = ob_get_clean();
        $ui->contentAddHtml($javascript);

        $ui->endContent();
        $ui->showPage();
    }


}
