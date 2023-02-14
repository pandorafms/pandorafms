<?php
/**
 * Tips to Pandora FMS feature.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Tips Window
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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
use PandoraFMS\View;
global $config;

/**
 * Class TipsWindow.
 */
class TipsWindow
{

    /**
     * Allowed methods to be called using AJAX request.
     *
     * @var array
     */
    public $AJAXMethods = [
        'getRandomTip',
        'renderView',
        'setShowTipsAtStartup',
    ];

    /**
     * Url of controller.
     *
     * @var string
     */
    public $ajaxController;

    /**
     * Total tips
     *
     * @var integer
     */
    public $totalTips;

    /**
     * Array of tips
     *
     * @var array
     */
    public $tips = [];


    /**
     * Generates a JSON error.
     *
     * @param string $msg Error message.
     *
     * @return void
     */
    public function error(string $msg)
    {
        echo json_encode(
            ['error' => $msg]
        );
    }


    /**
     * Checks if target method is available to be called using AJAX.
     *
     * @param string $method Target method.
     *
     * @return boolean True allowed, false not.
     */
    public function ajaxMethod($method)
    {
        global $config;

        // Check access.
        check_login();

        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Constructor.
     *
     * @param boolean $must_run        Must run or not.
     * @param string  $ajax_controller Controller.
     *
     * @return object
     * @throws Exception On error.
     */
    public function __construct(
        $ajax_controller='include/ajax/tips_window.ajax'
    ) {
        $this->ajaxController = $ajax_controller;

        return $this;
    }


    /**
     * Main method.
     *
     * @return void
     */
    public function run()
    {
        global $config;
        $user_info = users_get_user_by_id($config['id_user']);

        if ((bool) $user_info['show_tips_startup'] === false) {
            return;
        }

        ui_require_css_file('tips_window');
        ui_require_css_file('jquery.bxslider');
        ui_require_javascript_file('tipsWindow');
        ui_require_javascript_file('jquery.bxslider.min');
        echo '<div id="tips_window_modal"></div>';
        $this->totalTips = $this->getTotalTips();
        if ($this->totalTips > 0) {
            ?>

                <script>
                    var totalTips = <?php echo $this->totalTips; ?>;
                    var url = '<?php echo ui_get_full_url('ajax.php'); ?>';
                    var page = '<?php echo $this->ajaxController; ?>';
                </script>
                <script>
                if(totalTips > 0){
                    load_tips_modal({
                        target: $('#tips_window_modal'),
                        url: '<?php echo ui_get_full_url('ajax.php'); ?>',
                        onshow: {
                            page: '<?php echo $this->ajaxController; ?>',
                            method: 'renderView',
                        }
                    });
                }
                </script>

            <?php
        }
    }


    public function renderView()
    {
        $initialTip = $this->getRandomTip(true);
        View::render(
            'dashboard/tipsWindow',
            [
                'title' => $initialTip['title'],
                'text'  => $initialTip['text'],
                'url'   => $initialTip['url'],
                'files' => $initialTip['files'],
                'id'    => $initialTip['id'],
            ]
        );
    }


    public function getRandomTip($return=false)
    {
        $exclude = get_parameter('exclude', '');

        $sql = 'SELECT id, title, text, url
                FROM twelcome_tip';

        if (empty($exclude) === false && $exclude !== null) {
            $exclude = implode(',', json_decode($exclude, true));
            if ($exclude !== '') {
                $sql .= sprintf(' WHERE id NOT IN (%s)', $exclude);
            }
        }

        $sql .= ' ORDER BY RAND()';

        $tip = db_get_row_sql($sql);
        $tip['files'] = $this->getFilesFromTip($tip['id']);

        if ($return) {
            if (empty($tip) === false) {
                return $tip;
            } else {
                return false;
            }
        } else {
            if (empty($tip) === false) {
                echo json_encode(['success' => true, 'data' => $tip]);
                return;
            } else {
                echo json_encode(['success' => false]);
                return;
            }
        }
    }


    public function getTotalTips()
    {
        return db_get_sql('SELECT count(*) FROM twelcome_tip');
    }


    public function getFilesFromTip($idTip)
    {
        if (empty($idTip) === true) {
            return false;
        }

        $sql = sprintf('SELECT filename, path FROM twelcome_tip_file WHERE twelcome_tip_file = %s', $idTip);

        return db_get_all_rows_sql($sql);

    }


    public function setShowTipsAtStartup()
    {
        global $config;
        $show_tips_startup = get_parameter('show_tips_startup', '');
        if ($show_tips_startup !== '' && $show_tips_startup !== null) {
            $result = db_process_sql_update(
                'tusuario',
                ['show_tips_startup' => $show_tips_startup],
                ['id_user' => $config['id_user']]
            );

            if ($result !== false) {
                echo json_encode(['success' => true]);
                return;
            } else {
                echo json_encode(['success' => false]);
                return;
            }
        } else {
            echo json_encode(['success' => false]);
            return;
        }

    }
}