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
        'renderPreview',
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
        // Check access.
        check_login();

        return in_array($method, $this->AJAXMethods);
    }


    /**
     * Constructor.
     *
     * @param string $ajax_controller Controller.
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
        $_SESSION['showed_tips_window'] = true;
        $userInfo = users_get_user_by_id($config['id_user']);

        if ((bool) $userInfo['show_tips_startup'] === false) {
            return;
        }

        ui_require_css_file('tips_window');
        if ($config['style'] === 'pandora_black' && is_metaconsole() === false) {
            ui_require_css_file('pandora_black');
        }

        ui_require_css_file('jquery.bxslider');
        ui_require_javascript_file('tipsWindow');
        ui_require_javascript_file('jquery.bxslider.min');
        echo '<div id="tips_window_modal"></div>';
        $totalTips = $this->getTotalTipsShowUser();
        if ($totalTips > 0) {
            echo '<script>';
            echo 'var totalTips = "'.$totalTips.'";';
            echo 'var url = "'.ui_get_full_url('ajax.php').'";';
            echo 'var page = "'.$this->ajaxController.'";';
            echo 'if(totalTips > 0){';
            echo '    load_tips_modal({';
            echo '        target: $("#tips_window_modal"),';
            echo '        url: "'.ui_get_full_url('ajax.php').'",';
            echo '        onshow: {';
            echo '            page: "'.$this->ajaxController.'",';
            echo '            method: "renderView",';
            echo '        }';
            echo '    });';
            echo '}';
            echo '</script>';
        }
    }


    /**
     * Render view modal with random tip
     *
     * @return void
     */
    public function renderView()
    {
        $initialTip = $this->getRandomTip(true);
        View::render(
            'dashboard/tipsWindow',
            [
                'title'     => $initialTip['title'],
                'text'      => $initialTip['text'],
                'url'       => $initialTip['url'],
                'files'     => $initialTip['files'],
                'id'        => $initialTip['id'],
                'totalTips' => $this->getTotalTipsShowUser(),
            ]
        );
    }


    /**
     * Render preview view modal with parameter
     *
     * @return void
     */
    public function renderPreview()
    {
        $title = get_parameter('title', '');
        $text = get_parameter('text', '');
        $url = get_parameter('url', '');
        $files = get_parameter('files', '');
        $totalFiles64 = get_parameter('totalFiles64', '');
        $files64 = false;

        if ($totalFiles64 > 0) {
            $files64 = [];
            for ($i = 0; $i < $totalFiles64; $i++) {
                $files64[] = get_parameter('file64_'.$i, '');
            }
        }

        if (empty($files) === false) {
            $files = explode(',', $files);
            foreach ($files as $key => $value) {
                $files[$key] = str_replace(ui_get_full_url('/'), '', $value);
            }
        }

        View::render(
            'dashboard/tipsWindow',
            [
                'title'   => $title,
                'text'    => $text,
                'url'     => $url,
                'preview' => true,
                'files'   => (empty($files) === false) ? $files : false,
                'files64' => (empty($files64) === false) ? $files64 : false,
            ]
        );
    }


    /**
     * Search a tip by id
     *
     * @param integer $idTip Id from tip.
     *
     * @return array   $tip
     */
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


    /**
     * Return a tip or print it in json
     *
     * @param boolean $return Param for return or print json.
     *
     * @return array $tip
     */
    public function getRandomTip($return=false)
    {
        global $config;
        $exclude = get_parameter('exclude', '');
        $userInfo = users_get_user_by_id($config['id_user']);
        $profilesUser = users_get_user_profile($config['id_user']);
        $language = ($userInfo['language'] !== 'default') ? $userInfo['language'] : $config['language'];

        $idProfilesFilter = '0';
        foreach ($profilesUser as $key => $profile) {
            $idProfilesFilter .= ','.$profile['id_perfil'];
        }

        $sql = 'SELECT id, title, text, url
                FROM twelcome_tip
                WHERE enable = "1" ';

        if (empty($exclude) === false && $exclude !== null) {
            $exclude = implode(',', json_decode($exclude, true));
            if ($exclude !== '') {
                $sql .= sprintf(' AND id NOT IN (%s)', $exclude);
            }
        }

        $sql .= sprintf(' AND id_profile IN (%s)', $idProfilesFilter);
        $sql .= sprintf(' AND id_lang = "%s"', $language);

        $sql .= ' ORDER BY CASE WHEN id_lang = "'.$language.'" THEN id_lang END DESC, RAND()';

        $tip = db_get_row_sql($sql);
        $check_tips = db_get_row_sql('SELECT count(*) AS tips FROM twelcome_tip WHERE id_lang = "'.$language.'"')['tips'];
        if (empty($tip) === false) {
            $tip['files'] = $this->getFilesFromTip($tip['id']);

            $tip['title'] = io_safe_output($tip['title']);
            $tip['text'] = io_safe_output($tip['text']);
            $tip['url'] = io_safe_output($tip['url']);
        } else if ($check_tips === '0') {
            $language = 'en_GB';
            $sql = 'SELECT id, title, text, url
            FROM twelcome_tip
            WHERE enable = "1" AND id_lang = "'.$language.'"';
            $sql .= ' ORDER BY CASE WHEN id_lang = "'.$language.'" THEN id_lang END DESC, RAND()';
            $tip = db_get_row_sql($sql);

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


    /**
     * Get totals tips that user can show
     *
     * @return array
     */
    public function getTotalTipsShowUser()
    {
        global $config;
        $profilesUser = users_get_user_profile($config['id_user']);
        $idProfilesFilter = '0';
        $userInfo = users_get_user_by_id($config['id_user']);
        $language = ($userInfo['language'] !== 'default') ? $userInfo['language'] : $config['language'];

        $check_tips = db_get_row_sql('SELECT count(*) AS tips FROM twelcome_tip WHERE id_lang = "'.$language.'"')['tips'];

        if ($check_tips === '0') {
            $language = 'en_GB';
        }

        foreach ($profilesUser as $key => $profile) {
            $idProfilesFilter .= ','.$profile['id_perfil'];
        }

        $sql = 'SELECT count(*)
                FROM twelcome_tip
                WHERE enable = "1" ';

        $sql .= sprintf(' AND id_profile IN (%s)', $idProfilesFilter);
        $sql .= sprintf(' AND id_lang = "%s"', $language);

        $sql .= ' ORDER BY CASE WHEN id_lang = "'.$language.'" THEN id_lang END DESC, RAND()';
        return db_get_sql($sql);
    }


    /**
     * Return files from tip
     *
     * @param integer $idTip Id from tip.
     *
     * @return array
     */
    public function getFilesFromTip($idTip)
    {
        if (empty($idTip) === true) {
            return false;
        }

        $sql = sprintf('SELECT id, filename, path FROM twelcome_tip_file WHERE twelcome_tip_file = %s', $idTip);

        return db_get_all_rows_sql($sql);

    }


    /**
     * Delete all images from tip in db and files
     *
     * @param integer $idTip          Id from tip.
     * @param array   $imagesToRemove Array with id and images path.
     *
     * @return void
     */
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


    /**
     * Update token user for show tips at startup
     *
     * @return void
     */
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


    /**
     * Draw table in list tips
     *
     * @param array $errors Array of errors if exists.
     *
     * @return void
     */
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
                [
                    'text'  => 'edit',
                    'class' => 'table_action_buttons',
                ],
                [
                    'text'  => 'delete',
                    'class' => 'table_action_buttons',
                ],
            ];

            $columnNames = [
                __('Language'),
                __('Title'),
                __('Text'),
                __('Enable'),
                __('Edit'),
                __('Delete'),
            ];

            // Load datatables user interface.
            ui_print_datatable(
                [
                    'id'                  => 'list_tips_windows',
                    'class'               => 'info_table',
                    'style'               => 'width: 99%',
                    'dom_elements'        => 'lpfti',
                    'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
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
            echo '<div class="action-buttons w100p" style="width: 100%">';
            $buttonCreate = html_print_button(
                __('Create tip'),
                'create',
                false,
                'window.location.replace("index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=create")',
                [
                    'class' => 'sub',
                    'icon'  => 'plus',
                ],
                true
            );
            html_print_action_buttons($buttonCreate);
            echo '</div>';
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }


    /**
     * Delete tip and his files.
     *
     * @param integer $idTip Id from tip.
     *
     * @return integer Status from sql query.
     */
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


    /**
     * Return tips for datatable
     *
     * @return void
     */
    public function getTips()
    {
        global $config;

        $data = [];
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
                'SELECT id, id_language AS language, title, text, url, enable
                FROM twelcome_tip t
                LEFT JOIN tlanguage l ON t.id_lang COLLATE utf8mb4_unicode_ci = CONVERT(l.id_language USING utf8mb4) COLLATE utf8mb4_unicode_ci
                %s %s %s',
                $filter,
                $order,
                $pagination
            );

            $data = db_get_all_rows_sql($sql);

            $sqlCount = sprintf(
                'SELECT count(*)
                FROM twelcome_tip t
                LEFT JOIN tlanguage l ON t.id_lang COLLATE utf8mb4_unicode_ci = CONVERT(l.id_language USING utf8mb4) COLLATE utf8mb4_unicode_ci
                %s',
                $filter
            );

            $total = db_get_sql($sqlCount);

            foreach ($data as $key => $row) {
                if ($row['enable'] === '1') {
                    $data[$key]['enable'] = '<span class="enable"></span>';
                } else {
                    $data[$key]['enable'] = '<span class="disable"></span>';
                }

                $data[$key]['title'] = io_safe_output($row['title']);
                $data[$key]['text'] = io_safe_output($row['text']);
                $data[$key]['url'] = io_safe_output($row['url']);
                $data[$key]['edit'] = '<a href="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=edit&idTip='.$row['id'].'">';
                $data[$key]['edit'] .= html_print_image(
                    'images/edit.svg',
                    true,
                    ['class' => 'main_menu_icon']
                );
                $data[$key]['edit'] .= '</a>';
                $data[$key]['delete'] .= '<form name="grupo" method="post" class="rowPair table_action_buttons" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&action=delete">';
                $data[$key]['delete'] .= html_print_input_image(
                    'button_delete_tip',
                    'images/delete.svg',
                    '',
                    '',
                    true,
                    [
                        'onclick' => 'if (!confirm(\''.__('Are you sure?').'\')) return false;',
                        'class'   => 'main_menu_icon invert_filter',
                    ]
                );
                $data[$key]['delete'] .= html_print_input_hidden('idTip', $row['id'], true);
                $data[$key]['delete'] .= '</form>';
            }

            if (empty($data) === true) {
                $total = 0;
                $data = [];
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


    /**
     * Render view create tips
     *
     * @param array $errors Array of errors if exists.
     *
     * @return void
     */
    public function viewCreate($errors=null)
    {
        ui_require_css_file('tips_window');
        ui_require_css_file('jquery.bxslider');
        ui_require_javascript_file('tipsWindow');
        ui_require_javascript_file('jquery.bxslider.min');

        if ($errors !== null) {
            if (count($errors) > 0) {
                foreach ($errors as $key => $value) {
                    ui_print_error_message($value);
                }
            } else {
                ui_print_success_message(__('Tip created'));
            }
        }

        $profiles = profile_get_profiles();

        echo '<script>
                var url = "'.ui_get_full_url('ajax.php').'";
                var page = "'.$this->ajaxController.'";
                var totalTips = 1;
              </script>';
        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'databox filter-table-adv';

        $table->style[0] = 'width: 50%';

        $table->data = [];
        $table->data[0][0] = html_print_label_input_block(
            __('Language'),
            html_print_select_from_sql(
                'SELECT id_language, name FROM tlanguage',
                'id_lang',
                '',
                '',
                '',
                '0',
                true,
                false,
                true,
                false,
                'width: 100%;'
            )
        );
        $table->data[0][1] = html_print_label_input_block(
            __('Profile'),
            html_print_select($profiles, 'id_profile', '0', '', __('All'), 0, true)
        );
        $table->data[1][0] = html_print_label_input_block(
            __('Title'),
            html_print_input_text('title', '', '', 35, 100, true)
        );
        $table->data[1][1] = html_print_label_input_block(
            __('Url'),
            html_print_input_text('url', '', '', 35, 100, true)
        );
        $table->data[2][0] = html_print_label_input_block(
            __('Text'),
            html_print_textarea('text', 5, 50, '', '', true),
        );
        $table->data[2][1] = html_print_label_input_block(
            __('Enable'),
            html_print_checkbox_switch('enable', true, true, true)
        );

        $inputImages = html_print_div(['id' => 'inputs_images'], true);
        $inputImages .= html_print_div(
            [
                'id'      => 'notices_images',
                'class'   => 'invisible',
                'content' => '<p>'.__('Wrong size, we recommend images of 464x260 px').'</p>',
            ],
            true
        );
        $inputImages .= html_print_div(
            [
                'id'      => 'notices_images',
                'class'   => 'invisible empty_input_images',
                'content' => '<p>'.__('Please select a image').'</p>',
            ],
            true
        );
        $inputImages .= html_print_button(__('Add image'), 'button_add_image', false, '', ['class' => 'button-add-image'], true);

        $table->data[3][0] = html_print_label_input_block(
            __('Images'),
            $inputImages
        );

        echo '<form method="post" class="max_floating_element_size" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=create&action=create" enctype="multipart/form-data">';
        html_print_table($table);
        echo '<div class="action-buttons" style="width: '.$table->width.'">';

        $actionButtons = html_print_submit_button(
            __('Send'),
            'submit_button',
            false,
            [
                'class' => 'sub',
                'icon'  => 'update',
            ],
            true
        );
        $actionButtons .= html_print_submit_button(
            __('Preview'),
            'preview_button',
            false,
            [
                'class' => 'sub preview',
                'id'    => 'prev_button',
                'icon'  => 'preview',
            ],
            true
        );

        html_print_action_buttons($actionButtons);
        echo '</div>';
        echo '</form>';
        html_print_div(['id' => 'tips_window_modal_preview']);
    }


     /**
      * Render view edit tips
      *
      * @param integer $idTip  Id from tips.
      * @param array   $errors Array of errors if exists.
      *
      * @return void
      */
    public function viewEdit($idTip, $errors=null)
    {
        $tip = $this->getTipById($idTip);
        if ($tip === false) {
            return;
        }

        $files = $this->getFilesFromTip($idTip);

        ui_require_css_file('tips_window');
        ui_require_css_file('jquery.bxslider');
        ui_require_javascript_file('tipsWindow');
        ui_require_javascript_file('jquery.bxslider.min');

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
                    'images/delete.svg',
                    '',
                    '',
                    true,
                    [
                        'onclick' => 'deleteImage(this, \''.$value['id'].'\', \''.$namePath.'\')',
                        'class'   => 'remove-image main_menu_icon',
                    ]
                );
                $outputImagesTip .= html_print_div(
                    [
                        'class'   => 'image_tip',
                        'content' => $imageTip,
                    ],
                    true
                );
            }
        }

        $profiles = profile_get_profiles();

        echo '<script>
                var url = "'.ui_get_full_url('ajax.php').'";
                var page = "'.$this->ajaxController.'";
                var totalTips = 1;
              </script>';
        $table = new stdClass();
        $table->width = '100%';
        $table->class = 'databox filter-table-adv';

        $table->style[0] = 'width: 50%';

        $table->data = [];

        $table->data[0][0] = html_print_label_input_block(
            __('Language'),
            html_print_select_from_sql(
                'SELECT id_language, name FROM tlanguage',
                'id_lang',
                $tip['id_lang'],
                '',
                '',
                '0',
                true,
                false,
                true,
                false,
                'width: 100%;'
            )
        );
        $table->data[0][1] = html_print_label_input_block(
            __('Profile'),
            html_print_select($profiles, 'id_profile', $tip['id_profile'], '', __('All'), 0, true)
        );
        $table->data[1][0] = html_print_label_input_block(
            __('Title'),
            html_print_input_text('title', $tip['title'], '', 35, 100, true)
        );
        $table->data[1][1] = html_print_label_input_block(
            __('Url'),
            html_print_input_text('url', $tip['url'], '', 35, 100, true)
        );
        $table->data[2][0] = html_print_label_input_block(
            __('Text'),
            html_print_textarea('text', 5, 50, $tip['text'], '', true),
        );
        $table->data[2][1] = html_print_label_input_block(
            __('Enable'),
            html_print_checkbox_switch('enable', 1, ($tip['enable'] === '1') ? true : false, true)
        );
        $inputImages = $outputImagesTip;
        $inputImages .= html_print_div(['id' => 'inputs_images'], true);
        $inputImages .= html_print_input_hidden('images_to_delete', '{}', true);
        $inputImages .= html_print_div(
            [
                'id'      => 'notices_images',
                'class'   => 'invisible',
                'content' => '<p>'.__('Wrong size, we recommend images of 464x260 px').'</p>',
            ],
            true
        );
        $inputImages .= html_print_div(
            [
                'id'      => 'notices_images',
                'class'   => 'invisible empty_input_images',
                'content' => '<p>'.__('Please select a image').'</p>',
            ],
            true
        );
        $inputImages .= html_print_button(__('Add image'), 'button_add_image', false, '', ['class' => 'button-add-image'], true);

        $table->data[3][0] = html_print_label_input_block(
            __('Images'),
            $inputImages
        );

        echo '<form class="max_floating_element_size" name="grupo" method="post" action="index.php?sec=gsetup&sec2=godmode/setup/setup&section=welcome_tips&view=edit&action=edit&idTip='.$tip['id'].'" enctype="multipart/form-data">';
        html_print_table($table);
        echo '<div class="action-buttons" style="width: '.$table->width.'">';
        $actionButtons = html_print_submit_button(
            __('Send'),
            'submit_button',
            false,
            [
                'class' => 'sub',
                'icon'  => 'update',
            ],
            true
        );
        $actionButtons .= html_print_submit_button(
            __('Preview'),
            'preview_button',
            false,
            [
                'class' => 'sub preview',
                'id'    => 'prev_button',
                'icon'  => 'preview',
            ],
            true
        );

        html_print_action_buttons($actionButtons);

        echo '</div>';
        echo '</form>';
        html_print_div(['id' => 'tips_window_modal_preview']);
    }


    /**
     * Udpdate tip
     *
     * @param integer $id         Id from tip.
     * @param integer $id_profile Id profile.
     * @param string  $id_lang    Id langugage.
     * @param string  $title      Title from tip.
     * @param string  $text       Text from tip.
     * @param string  $url        Url from tip.
     * @param boolean $enable     Indicates if the tip is activated.
     * @param array   $images     Images from tip.
     *
     * @return boolean
     */
    public function updateTip($id, $id_profile, $id_lang, $title, $text, $url, $enable, $images=null)
    {
        db_process_sql_begin();

        $idTip = db_process_sql_update(
            'twelcome_tip',
            [
                'id_lang'    => $id_lang,
                'id_profile' => (empty($id_profile) === false) ? $id_profile : 0,
                'title'      => $title,
                'text'       => $text,
                'url'        => $url,
                'enable'     => $enable,
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


    /**
     * Create tip
     *
     * @param string  $id_lang    Id langugage.
     * @param integer $id_profile Id profile.
     * @param string  $title      Title from tip.
     * @param string  $text       Text from tip.
     * @param string  $url        Url from tip.
     * @param boolean $enable     Indicates if the tip is activated.
     * @param array   $images     Images from tip.
     *
     * @return boolean
     */
    public function createTip($id_lang, $id_profile, $title, $text, $url, $enable, $images=null)
    {
        db_process_sql_begin();
        $idTip = db_process_sql_insert(
            'twelcome_tip',
            [
                'id_lang'    => $id_lang,
                'id_profile' => (empty($id_profile) === false) ? $id_profile : 0,
                'title'      => $title,
                'text'       => $text,
                'url'        => $url,
                'enable'     => $enable,
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


    /**
     * Validate images uploads for the user
     *
     * @param array $files List images for validate.
     *
     * @return boolean Return boolean or array errors.
     */
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


    /**
     * Upload images passed by user
     *
     * @param array $files List of files for upload.
     *
     * @return array List of names files.
     */
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
