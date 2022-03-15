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
            $this->show_visualmap();
            return;
        }

        // Padding.
        $this->height -= 40;
        $this->width -= 0;

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
                    'icon'  => 'ui-icon-back',
                    'pos'   => 'left',
                    'text'  => __('Back'),
                    'href'  => 'index.php?page=visualmaps',
                    'class' => 'header-button-left',
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
        $ratio_h = ($size['height'] / $visualConsoleData['height']);

        $visualConsoleData['width'] = $size['width'];
        $visualConsoleData['height'] = ($size['width'] * $ratio_visualconsole);

        if ($visualConsoleData['height'] > $size['height']) {
            $ratio_t = $ratio_h;

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

        $output .= '<style id="css_cv_'.$uniq.'" type="text/css">';
        $output .= css_label_styles_visual_console($uniq, $ratio_t);
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
                'page'    => 'include/ajax/visual_console.ajax',
                'ratio'   => $ratio_t,
                'size'    => $size,
                'cellId'  => $uniq,
                'mobile'  => true,
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
        $output .= ui_get_full_url('/', false, false, false);
        $output .= 'mobile/index.php?page=visualmap&id='.$visualConsoleId;
        $output .= '" });';

        $output .= '</script>';

        $ui->contentAddHtml($output);

        $javascript = ob_get_clean();
        $ui->contentAddHtml($javascript);

        $ui->endContent();
        $ui->showPage();
    }


}
