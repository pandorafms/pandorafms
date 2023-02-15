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
use LDAP\Result;
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
        $userInfo = users_get_user_by_id($config['id_user']);

        if ((bool) $userInfo['show_tips_startup'] === false) {
            return;
        }

        $_SESSION['showed_tips_window'] = true;
        ui_require_css_file('tips_window');
        ui_require_css_file('jquery.bxslider');
        ui_require_javascript_file('tipsWindow');
        ui_require_javascript_file('jquery.bxslider.min');
        echo '<div id="tips_window_modal"></div>';
        $totalTips = $this->getTotalTipsEnabled();
        if ($totalTips > 0) {
            ?>

                <script>
                    var totalTips = <?php echo $totalTips; ?>;
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


    public function getTipById($idTip)
    {
        $tip = db_get_row(
            'twelcome_tip',
            'id',
            $idTip,
        );
        if ($tip !== false) {
            $tip['title'] = io_safe_output($tip['title']);
            $tip['text'] = io_safe_output($tip['text']);
            $tip['url'] = io_safe_output($tip['url']);
        }

        return $tip;
    }


    public function getRandomTip($return=false)
    {
        global $config;
        $exclude = get_parameter('exclude', '');

        $sql = 'SELECT id, title, text, url
                FROM twelcome_tip
                WHERE enable = "1" ';

        if (empty($exclude) === false && $exclude !== null) {
            $exclude = implode(',', json_decode($exclude, true));
            if ($exclude !== '') {
                $sql .= sprintf(' AND id NOT IN (%s)', $exclude);
            }
        }

        $sql .= ' ORDER BY CASE WHEN id_lang = "'.$config['language'].'" THEN id_lang END DESC, RAND()';

        $tip = db_get_row_sql($sql);

        if (empty($tip) === false) {
            $tip['files'] = $this->getFilesFromTip($tip['id']);

            $tip['title'] = io_safe_output($tip['title']);
            $tip['text'] = io_safe_output($tip['text']);
            $tip['url'] = io_safe_output($tip['url']);
        }

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


    public function getTotalTipsEnabled()
    {
        return db_get_sql('SELECT count(*) FROM twelcome_tip WHERE enable = "1"');
    }


    public function getFilesFromTip($idTip)
    {
        if (empty($idTip) === true) {
            return false;
        }

        $sql = sprintf('SELECT id, filename, path FROM twelcome_tip_file WHERE twelcome_tip_file = %s', $idTip);

        return db_get_all_rows_sql($sql);

    }


    public function deleteImagesFromTip($idTip, $imagesToRemove)
    {
        foreach ($imagesToRemove as $id => $image) {
            unlink($image);
            db_process_sql_delete(
                'twelcome_tip_file',
                [
                    'id'                => $id,
                    'twelcome_tip_file' => $idTip,
                ]
            );
        }
    }


    public function setShowTipsAtStartup()
    {
        global $config;
        $showTipsStartup = get_parameter('show_tips_startup', '');
        if ($showTipsStartup !== '' && $showTipsStartup !== null) {
            $result = db_process_sql_update(
                'tusuario',
                ['show_tips_startup' => $showTipsStartup],
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


    public function draw($errors=null)
    {
        ui_require_css_file('tips_window');

        if ($errors !== null) {
            if (count($errors) > 0) {
                foreach ($errors as $key => $value) {
                    ui_print_error_message($value);
                }
            } else {
                ui_print_success_message(__('Tip deleted'));
            }
        }

        try {
            $columns = [
                'language',
                'title',
                'text',
                'enable',
                'actions',
            ];

            $columnNames = [
                __('Language'),
                __('Title'),
                __('Text'),
                __('Enable'),
                __('Actions'),
            ];

            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => 'list_tips_windows',
                    'class'               => 'info_table',
                    'style'               => 'width: 100%',
                    'columns'             => $columns,
                    'column_names'        => $columnNames,
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


    public function deleteTip($idTip)
    {
        $files = $this->getFilesFromTip($idTip);
        if ($files !== false) {
            if (count($files) > 0) {
                foreach ($files as $key => $file) {
                    unlink($file['path'].'/'.$file['filename']);
                }
            }
        }

        return db_process_sql_delete(
            'twelcome_tip',
            ['id' => $idTip]
        );
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
        $orderDatatable = get_datatable_order(true);
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

            if (isset($orderDatatable)) {
                $order = sprintf(
                    ' ORDER BY %s %s',
                    $orderDatatable['field'],
                    $orderDatatable['direction']
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
                'SELECT id, name AS language, title, text, url, enable
                FROM twelcome_tip t
                LEFT JOIN tlanguage l ON t.id_lang = l.id_language
                %s %s %s',
                $filter,
                $order,
                $pagination
            );

            $data = db_get_all_rows_sql($sql);

            foreach ($data as $key => $row) {
                if ($row['enable'] === '1') {
                    $data[$key]['enable'] = '<span class="enable"></span>';
                } else {
                    $data[$key]['enable'] = '<span class="disable"></span>';
                }

                $data[$key]['title'] = io_safe_output($row['title']);
                $data[$key]['text'] = io_safe_output($row['text']);
                $data[$key]['url'] = io_safe_output($row['url']);
                $data[$key]['actions'] = '<div class="buttons_actions">';
                $data[$key]['actions'] .= '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=edit&idTip='.$row['id'].'">';
                $data[$key]['actions'] .= html_print_input_image(
                    'button_edit_tip',
                    'images/edit.png',
                    '',
                    '',
                    true
                );
                $data[$key]['actions'] .= '</a>';
                $data[$key]['actions'] .= '<form name="grupo" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&action=delete">';
                $data[$key]['actions'] .= html_print_input_image(
                    'button_delete_tip',
                    'images/delete.png',
                    '',
                    '',
                    true,
                    ['onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;']
                );
                $data[$key]['actions'] .= html_print_input_hidden('idTip', $row['id'], true);
                $data[$key]['actions'] .= '</form>';
                $data[$key]['actions'] .= '</div>';
            }

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
        ui_require_javascript_file('tipsWindow');
        ui_require_css_file('tips_window');

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
        $table->data[0][0] = __('Images');
        $table->data[0][1] .= html_print_div(['id' => 'inputs_images'], true);
        $table->data[0][1] .= html_print_button(__('Add image'), 'button_add_image', false, '', '', true);
        $table->data[1][0] = __('Language');
        $table->data[1][1] = html_print_select_from_sql(
            'SELECT id_language, name FROM tlanguage',
            'id_lang',
            '',
            '',
            '',
            '0',
            true
        );
        $table->data[2][0] = __('Title');
        $table->data[2][1] = html_print_input_text('title', '', '', 35, 100, true);
        $table->data[3][0] = __('Text');
        $table->data[3][1] = html_print_textarea('text', 5, 1, '', '', true);
        $table->data[4][0] = __('Url');
        $table->data[4][1] = html_print_input_text('url', '', '', 35, 100, true);
        $table->data[5][0] = __('Enable');
        $table->data[5][1] = html_print_checkbox_switch('enable', true, true, true);

        echo '<form name="grupo" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=create&action=create" enctype="multipart/form-data">';
        html_print_table($table);
        echo '<div class="action-buttons" style="width: '.$table->width.'">';
        html_print_submit_button(__('Send'), 'submit_button', false, ['class' => 'sub next']);

        echo '</div>';
        echo '</form>';
    }


    public function viewEdit($idTip, $errors=null)
    {
        $tip = $this->getTipById($idTip);
        if ($tip === false) {
            return;
        }

        $files = $this->getFilesFromTip($idTip);

        ui_require_javascript_file('tipsWindow');
        ui_require_css_file('tips_window');

        if ($errors !== null) {
            if (count($errors) > 0) {
                foreach ($errors as $key => $value) {
                    ui_print_error_message($value);
                }
            } else {
                ui_print_success_message(__('Tip edited'));
            }
        }

        $outputImagesTip = '';
        if (empty($files) === false) {
            foreach ($files as $key => $value) {
                $namePath = $value['path'].$value['filename'];
                $imageTip = html_print_image($namePath, true);
                $imageTip .= html_print_input_image(
                    'delete_image_tip',
                    'images/delete.png',
                    '',
                    '',
                    true,
                    ['onclick' => 'deleteImage(this, \''.$value['id'].'\', \''.$namePath.'\')']
                );
                $outputImagesTip .= html_print_div(
                    [
                        'class'   => 'image-tip',
                        'content' => $imageTip,
                    ],
                    true
                );
            }
        }

        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'databox filters';

        $table->style[0] = 'font-weight: bold';

        $table->data = [];
        $table->data[0][0] = __('Images');
        $table->data[0][1] .= $outputImagesTip;
        $table->data[0][1] .= html_print_div(['id' => 'inputs_images'], true);
        $table->data[0][1] .= html_print_input_hidden('images_to_delete', '{}', true);
        $table->data[0][1] .= html_print_button(__('Add image'), 'button_add_image', false, '', '', true);
        $table->data[1][0] = __('Language');
        $table->data[1][1] = html_print_select_from_sql(
            'SELECT id_language, name FROM tlanguage',
            'id_lang',
            $tip['id_lang'],
            '',
            '',
            '0',
            true
        );
        $table->data[2][0] = __('Title');
        $table->data[2][1] = html_print_input_text('title', $tip['title'], '', 35, 100, true);
        $table->data[3][0] = __('Text');
        $table->data[3][1] = html_print_textarea('text', 5, 1, $tip['text'], '', true);
        $table->data[4][0] = __('Url');
        $table->data[4][1] = html_print_input_text('url', $tip['url'], '', 35, 100, true);
        $table->data[5][0] = __('Enable');
        $table->data[5][1] = html_print_checkbox_switch('enable', 1, ($tip['enable'] === '1') ? true : false, true);

        echo '<form name="grupo" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=edit&action=edit&idTip='.$tip['id'].'" enctype="multipart/form-data">';
        html_print_table($table);
        echo '<div class="action-buttons" style="width: '.$table->width.'">';
        html_print_submit_button(__('Send'), 'submit_button', false, ['class' => 'sub next']);

        echo '</div>';
        echo '</form>';
    }


    public function updateTip($id, $id_lang, $title, $text, $url, $enable, $images=null)
    {
        db_process_sql_begin();
        hd($enable, true);
        $idTip = db_process_sql_update(
            'twelcome_tip',
            [
                'id_lang' => $id_lang,
                'title'   => io_safe_input($title),
                'text'    => io_safe_input($text),
                'url'     => io_safe_input($url),
                'enable'  => $enable,
            ],
            ['id' => $id]
        );
        if ($idTip === false) {
            db_process_sql_rollback();
            return false;
        }

        if ($images !== null) {
            foreach ($images as $key => $image) {
                $res = db_process_sql_insert(
                    'twelcome_tip_file',
                    [
                        'twelcome_tip_file' => $id,
                        'filename'          => $image,
                        'path'              => 'images/tips/',
                    ]
                );
                if ($res === false) {
                    db_process_sql_rollback();
                    return false;
                }
            }
        }

        db_process_sql_commit();

        return true;
    }


    public function createTip($id_lang, $title, $text, $url, $enable, $images=null)
    {
        db_process_sql_begin();
        $idTip = db_process_sql_insert(
            'twelcome_tip',
            [
                'id_lang' => $id_lang,
                'title'   => io_safe_input($title),
                'text'    => io_safe_input($text),
                'url'     => io_safe_input($url),
                'enable'  => $enable,
            ]
        );
        if ($idTip === false) {
            db_process_sql_rollback();
            return false;
        }

        if ($images !== null) {
            foreach ($images as $key => $image) {
                $res = db_process_sql_insert(
                    'twelcome_tip_file',
                    [
                        'twelcome_tip_file' => $idTip,
                        'filename'          => $image,
                        'path'              => 'images/tips/',
                    ]
                );
                if ($res === false) {
                    db_process_sql_rollback();
                    return false;
                }
            }
        }

        db_process_sql_commit();

        return true;
    }


    public function validateImages($files)
    {
        $formats = [
            'image/png',
            'image/jpg',
            'image/jpeg',
            'image/gif',
        ];
        $errors = [];
        $maxsize = 6097152;

        foreach ($files as $key => $file) {
            if ($file['error'] !== 0) {
                $errors[] = __('Incorrect file');
            }

            if (in_array($file['type'], $formats) === false) {
                $errors[] = __('Format image invalid');
            }

            if ($file['size'] > $maxsize) {
                $errors[] = __('Image size too large');
            }
        }

        if (count($errors) > 0) {
            return $errors;
        } else {
            return false;
        }
    }


    public function uploadImages($files)
    {
        $dir = 'images/tips/';
        $imagesOk = [];
        foreach ($files as $key => $file) {
            $name = str_replace(' ', '_', $file['name']);
            $name = str_replace('.', uniqid().'.', $name);
            move_uploaded_file($file['tmp_name'], $dir.'/'.$name);
            $imagesOk[] = $name;
        }

        return $imagesOk;
    }


}