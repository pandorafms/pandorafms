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
        'getTips',
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

        $_SESSION['showed_tips_window'] = true;
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


    public function draw()
    {
        try {
            $columns = [
                'title',
                'text',
            ];

            $column_names = [
                __('Title'),
                __('Text'),
            ];

            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => 'list_tips_windows',
                    'class'               => 'info_table',
                    'style'               => 'width: 100%',
                    'columns'             => $columns,
                    'column_names'        => $column_names,
                    'ajax_url'            => $this->ajaxController,
                    'ajax_data'           => ['method' => 'getTips'],
                    'no_sortable_columns' => [-1],
                    'order'               => [
                        'field'     => 'title',
                        'direction' => 'asc',
                    ],
                    'search_button_class' => 'sub filter float-right',
                    'form'                => [
                        'inputs' => [
                            [
                                'label' => __('Search by title'),
                                'type'  => 'text',
                                'name'  => 'filter_title',
                                'size'  => 12,
                            ],
                        ],
                    ],
                ]
            );
            echo '<div class="action-buttons w100p">';
            echo '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=create">';
            html_print_submit_button(
                __('Create tip'),
                'create',
                false,
                'class="sub next"'
            );
            echo '</a>';
            echo '</div>';
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    public function getTips()
    {
        global $config;

        // Init data.
        $data = [];
        // Count of total records.
        $count = 0;
        // Catch post parameters.
        $start  = get_parameter('start', 0);
        $length = get_parameter('length', $config['block_size']);
        $order_datatable = get_datatable_order(true);
        $filters = get_parameter('filter', []);
        $pagination = '';
        $filter = '';
        $order = '';

        try {
            ob_start();

            if (key_exists('filter_title', $filters) === true) {
                if (empty($filters['filter_title']) === false) {
                    $filter = ' WHERE title like "%'.$filters['filter_title'].'%"';
                }
            }

            if (isset($order_datatable)) {
                $order = sprintf(
                    ' ORDER BY %s %s',
                    $order_datatable['field'],
                    $order_datatable['direction']
                );
            }

            if (isset($length) && $length > 0
                && isset($start) && $start >= 0
            ) {
                $pagination = sprintf(
                    ' LIMIT %d OFFSET %d ',
                    $length,
                    $start
                );
            }

            $sql = sprintf(
                'SELECT title, text, url
                FROM twelcome_tip %s %s %s',
                $filter,
                $order,
                $pagination
            );

            $data = db_get_all_rows_sql($sql);

            if (empty($data) === true) {
                $total = 0;
                $data = [];
            } else {
                $total = $this->getTotalTips();
            }

            echo json_encode(
                [
                    'data'            => $data,
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $total,
                ]
            );
            // Capture output.
            $response = ob_get_clean();
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        json_decode($response);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo $response;
        } else {
            echo json_encode(
                [
                    'success' => false,
                    'error'   => $response,
                ]
            );
        }

        exit;
    }


    public function viewCreate($errors=null)
    {
        if ($errors !== null) {
            if (count($errors) > 0) {
                foreach ($errors as $key => $value) {
                    ui_print_error_message($value);
                }
            } else {
                ui_print_success_message(__('Tip created'));
            }
        }

        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'databox filters';

        $table->style[0] = 'font-weight: bold';

        $table->data = [];
        $table->data[0][0] = __('Language');
        $table->data[0][1] = html_print_select_from_sql(
            'SELECT id_language, name FROM tlanguage',
            'id_lang',
            '',
            '',
            '',
            '0',
            true
        );
        $table->data[1][0] = __('Title');
        $table->data[1][1] = html_print_input_text('title', '', '', 35, 100, true);
        $table->data[2][0] = __('Text');
        $table->data[2][1] = html_print_textarea('text', 5, 1, '', '', true);
        $table->data[3][0] = __('Url');
        $table->data[3][1] = html_print_input_text('url', '', '', 35, 100, true);
        $table->data[4][0] = __('Enable');
        $table->data[4][1] = html_print_checkbox_switch('enable', true, true, true);

        echo '<form name="grupo" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=create&action=create" >';
        html_print_table($table);
        echo '<div class="action-buttons" style="width: '.$table->width.'">';
        html_print_submit_button(__('Send'), 'submit_button', false, ['class' => 'sub next']);

        echo '</div>';
        echo '</form>';
    }


    public function createTip($id_lang, $title, $text, $url, $enable)
    {
        return db_process_sql_insert(
            'twelcome_tip',
            [
                'id_lang' => $id_lang,
                'title'   => $title,
                'text'    => $text,
                'url'     => $url,
                'enable'  => $enable,
            ]
        );
    }


}