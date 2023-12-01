<?php
/**
 * ITSM View detailed ticket
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage ITSM
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

global $config;

// Header tabs.
ui_print_standard_header(
    __('ITSM Detailed'),
    '',
    false,
    'ITSM_tab',
    false,
    $headerTabs,
    [
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm',
            'label' => __('ITSM'),
        ],
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm&operation=list',
            'label' => __('ITSM Tickets'),
        ],
        [
            'link'  => 'index.php?sec=ITSM&sec2=operation/ITSM/itsm',
            'label' => __('ITSM Detailed'),
        ],
    ]
);

if (empty($error) === false) {
    ui_print_error_message($error);
}

if (empty($error_upload) === false) {
    ui_print_error_message($error_upload);
}

if (empty($error_comment) === false) {
    ui_print_error_message($error_comment);
}

if (empty($error_delete_attachment) === false) {
    ui_print_error_message($error_delete_attachment);
}

if (empty($successfullyMsg) === false) {
    ui_print_success_message($successfullyMsg);
}

if (empty($incidence) === true) {
    ui_print_info_message(__('Incidence not found'));
} else {
    $nameIncidence = '--';
    if (empty($incidence['idIncidenceType']) === false) {
        $nameIncidence = $objectTypes[$incidence['idIncidenceType']];
    }

    // Details box.
    $details_box = '<div class="pandoraitsm_details_box pandoraitsm_details_box_five">';
    $details_box .= '<div class="pandoraitsm_details_titles">'.__('Status').'</div>';
    $details_box .= '<div class="pandoraitsm_details_titles">'.__('Resolution').'</div>';
    $details_box .= '<div class="pandoraitsm_details_titles">'.__('Group').'</div>';
    $details_box .= '<div class="pandoraitsm_details_titles">'.__('Priority').'</div>';
    $details_box .= '<div class="pandoraitsm_details_titles">'.__('Type').'</div>';
    $details_box .= '<div>';
    $details_box .= html_print_image('images/heart.png', true, ['class' => 'invert_filter']);
    $details_box .= '</div>';
    $details_box .= '<div>';
    $details_box .= html_print_image('images/builder@svg.svg', true, ['class' => 'invert_filter']);
    $details_box .= '</div>';
    $details_box .= '<div>';
    $details_box .= html_print_image('images/user_green.png', true, ['class' => 'invert_filter']);
    $details_box .= '</div>';
    $details_box .= '<div>';
    $details_box .= $priorityDiv;
    $details_box .= '</div>';
    $details_box .= '<div>';
    $details_box .= html_print_image('images/incidents.png', true, ['class' => 'invert_filter']);
    $details_box .= '</div>';
    $details_box .= '<div>'.$status[$incidence['status']].'</div>';
    $details_box .= '<div>';
    $details_box .= ($incidence['resolution'] !== 'NOTRESOLVED') ? $resolutions[$incidence['resolution']] : '--';
    $details_box .= '</div>';
    $details_box .= '<div>'.$groups[$incidence['idGroup']].'</div>';
    $details_box .= '<div>'.$priorities[$incidence['priority']].'</div>';
    $details_box .= '<div>';
    $details_box .= $nameIncidence;
    $details_box .= '</div>';
    $details_box .= '</div>';

    // People box.
    $people_box = '<div class="pandoraitsm_details_box pandoraitsm_details_box_three">';
    $people_box .= '<div>';
    $people_box .= html_print_image('images/header_user_green.png', true, ['width' => '21']);
    $people_box .= '</div>';
    $people_box .= '<div>';
    $people_box .= html_print_image('images/header_user_green.png', true, ['width' => '21']);
    $people_box .= '</div>';
    $people_box .= '<div>';
    $people_box .= html_print_image('images/header_user_green.png', true, ['width' => '21']);
    $people_box .= '</div>';

    $people_box .= '<div class="pandoraitsm_details_titles">'.__('Created by').':</div>';
    $people_box .= '<div class="pandoraitsm_details_titles">'.__('Owned by').':</div>';
    $people_box .= '<div class="pandoraitsm_details_titles">'.__('Closed by').':</div>';

    $people_box .= '<div>';
    $people_box .= (empty($incidence['idCreator']) === false) ? $users[$incidence['idCreator']]['fullName'] : '--';
    $people_box .= '</div>';
    $people_box .= '<div>';
    $people_box .= (empty($incidence['owner']) === false) ? $users[$incidence['owner']]['fullName'] : '--';
    $people_box .= '</div>';
    $people_box .= '<div>';
    $people_box .= (empty($incidence['closedBy']) === false) ? $users[$incidence['closedBy']]['fullName'] : '--';
    $people_box .= '</div>';
    $people_box .= '</div>';

    // Dates box.
    $dates_box = '<div class="pandoraitsm_details_box pandoraitsm_details_box_three">';
    $dates_box .= '<div>';
    $dates_box .= html_print_image('images/tick.png', true, ['class' => 'invert_filter']);
    $dates_box .= '</div>';
    $dates_box .= '<div>';
    $dates_box .= html_print_image('images/update.png', true, ['width' => '21', 'class' => 'invert_filter']);
    $dates_box .= '</div>';
    $dates_box .= '<div>';
    $dates_box .= html_print_image('images/mul.png', true, ['class' => 'invert_filter']);
    $dates_box .= '</div>';

    $dates_box .= '<div class="pandoraitsm_details_titles">'.__('Created at').':</div>';
    $dates_box .= '<div class="pandoraitsm_details_titles">'.__('Updated at').':</div>';
    $dates_box .= '<div class="pandoraitsm_details_titles">'.__('Closed at').':</div>';

    $dates_box .= '<div>'.$incidence['startDate'].'</div>';
    $dates_box .= '<div>'.$incidence['updateDate'].'</div>';
    $dates_box .= '<div>';
    $dates_box .= (($incidence['closeDate'] === '0000-00-00 00:00:00') ? '--' : $incidence['closeDate']);
    $dates_box .= '</div>';
    $dates_box .= '</div>';

    // Show details, people and dates.
    echo '<div class="ITSM_details">';
    ui_toggle(
        $details_box,
        __('Details'),
        '',
        'details_box',
        false,
        false,
        '',
        'ITSM_details_content white-box-content',
        'ITSM_details_shadow box-flat white_table_graph'
    );
    ui_toggle(
        $people_box,
        __('People'),
        '',
        'people_box',
        false,
        false,
        '',
        'ITSM_details_content white-box-content',
        'ITSM_details_shadow box-flat white_table_graph'
    );
    ui_toggle(
        $dates_box,
        __('Dates'),
        '',
        'dates_box',
        false,
        false,
        '',
        'ITSM_details_content white-box-content',
        'ITSM_details_shadow box-flat white_table_graph'
    );
    echo '</div>';

    // Show description.
    $description_box = '<div class="ITSM_details_description">';
    $description_box .= str_replace("\r\n", '</br>', $incidence['description']);
    $description_box .= '</div>';
    ui_toggle($description_box, __('Description'), '', '', false);

    if (empty($inventories) === false) {
        $inventories_box = '<div class="ITSM_details_description">';
        $inventories_box .= '<ul>';
        foreach ($inventories as $inventory) {
            $inventories_box .= '<li>';
            if (empty($inventory['idPandora']) === true) {
                $inventories_box .= $inventory['name'];
            } else {
                $id_agent = explode('-', $inventory['idPandora'])[1];
                $url_agent = $config['homeurl'];
                $url_agent .= 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agent;
                $inventories_box .= '<a href="'.$url_agent.'" target="_blanK" title="'.__('Agent').'">';
                $inventories_box .= $inventory['name'];
                $inventories_box .= '</a>';
            }

            $inventories_box .= '</li>';
        }

        $inventories_box .= '</ul>';
        $inventories_box .= '</div>';
        ui_toggle($inventories_box, __('Related to inventory object'), '', '', false);
    }

    // Files section table.
    $table_files_section = new stdClass();
    $table_files_section->width = '100%';
    $table_files_section->id = 'files_section_table';
    $table_files_section->class = 'databox filters';
    $table_files_section->head = [];

    $table_files_section->data = [];
    $table_files_section->size = [];
    $table_files_section->size[0] = '20%';
    $table_files_section->size[1] = '60%';
    $table_files_section->size[2] = '20%';

    $table_files_section->data[0][0] = '<div class="label_select">';
    $table_files_section->data[0][0] .= '<p class="input_label">'.__('File name').':</p>';
    $table_files_section->data[0][0] .= html_print_input_file('userfile', true, ['required' => true]);
    $table_files_section->data[0][1] = '<div class="label_select">';
    $table_files_section->data[0][1] .= '<p class="input_label">';
    $table_files_section->data[0][1] .= __('Attachment description');
    $table_files_section->data[0][1] .= ':</p>';
    $table_files_section->data[0][1] .= html_print_textarea(
        'file_description',
        3,
        20,
        '',
        '',
        true,
        'w100p'
    );

    $table_files_section->data[0][2] = '<div class="w100p">';
    $table_files_section->data[0][2] .= html_print_submit_button(
        __('Upload'),
        'accion',
        false,
        [
            'icon'  => 'wand',
            'mode'  => 'mini secondary',
            'class' => 'right',
        ],
        true
    );
    $table_files_section->data[0][2] .= '</div>';

    // Files list table.
    $table_files = new stdClass();
    $table_files->width = '100%';
    $table_files->class = 'info_table';
    $table_files->head = [];

    $table_files->head[0] = __('Filename');
    $table_files->head[1] = __('Timestamp');
    $table_files->head[2] = __('Description');
    $table_files->head[3] = __('User');
    $table_files->head[4] = __('Size');
    $table_files->head[5] = __('Delete');

    $table_files->data = [];

    $url = \ui_get_full_url('index.php?sec=manageTickets&sec2=operation/ITSM/itsm');
    foreach ($files['data'] as $key => $file) {
        $onClick = 'downloadIncidenceAttachment('.$file['idIncidence'].','.$file['idAttachment'];
        $onClick .= ',\''.ui_get_full_url('ajax.php').'\',\''.$file['filename'].'\')';

        $table_files->data[$key][0] = '<a href="#" onclick="'.$onClick.'">'.$file['filename'].'</a>';
        $table_files->data[$key][1] = $file['timestamp'];
        $table_files->data[$key][2] = $file['description'];
        $table_files->data[$key][3] = $file['idUser'];
        $table_files->data[$key][4] = $file['size'];
        $urlDelete = $url.'&operation=detail&idIncidence='.$file['idIncidence'].'&idAttachment='.$file['idAttachment'];
        $onclickDelete = 'javascript:if (!confirm(\''.__('Are you sure?').'\')) return false;';
        $table_files->data[$key][5] .= '<a href="'.$urlDelete.'" onClick="'.$onclickDelete.'">';
        $table_files->data[$key][5] .= html_print_image(
            'images/delete.svg',
            true,
            [
                'title' => __('Delete'),
                'class' => 'invert_filter main_menu_icon',
            ]
        );
        $table_files->data[$key][5] .= '</a>';
    }

    $upload_file_form = '<div class="w100p">';
    $upload_file_form .= '<form method="post" id="file_control" enctype="multipart/form-data">';
    $upload_file_form .= '<h4>'.__('Add attachment').'</h4>';
    $upload_file_form .= html_print_table($table_files_section, true);
    $upload_file_form .= html_print_input_hidden('upload_file', true, true);
    $upload_file_form .= '<h4>'.__('Attached files').'</h4>';
    $upload_file_form .= html_print_table($table_files, true);
    $upload_file_form .= '</form>';
    $upload_file_form .= '</div>';

    echo '<div class="ui_toggle">';
    ui_toggle(
        $upload_file_form,
        __('Attached files'),
        '',
        '',
        true,
        false,
        'white-box-content',
        'w98p'
    );
    echo '</div>';

    // Comments section table.
    $table_comments_section = new stdClass();
    $table_comments_section->width = '100%';
    $table_comments_section->id = 'files_section_table';
    $table_comments_section->class = 'databox filters';
    $table_comments_section->head = [];
    $table_comments_section->size = [];
    $table_comments_section->size[0] = '80%';
    $table_comments_section->size[1] = '20%';

    $table_comments_section->data = [];
    $table_comments_section->data[0][0] = '<div class="label_select">';
    $table_comments_section->data[0][0] .= '<p class="input_label">';
    $table_comments_section->data[0][0] .= __('Description');
    $table_comments_section->data[0][0] .= ':</p>';
    $table_comments_section->data[0][0] .= html_print_textarea(
        'comment_description',
        3,
        20,
        '',
        '',
        true,
        'w100p'
    );

    $table_comments_section->data[0][1] = '<div class="w100p">';
    $table_comments_section->data[0][1] .= html_print_submit_button(
        __('Add'),
        'accion',
        false,
        [
            'icon'  => 'wand',
            'mode'  => 'mini secondary',
            'class' => 'right',
        ],
        true
    );
    $table_comments_section->data[0][1] .= '</div>';

    // Comments list table.
    $comment_table = '';
    if (empty($wus) === false) {
        foreach ($wus['data'] as $wu) {
            $comment_table .= '<div class="comment_title">';
            $comment_table .= $wu['idUser'];
            $comment_table .= '<span>&nbspsaid&nbsp</span>';
            $comment_table .= $wu['timestamp'];
            $comment_table .= '<span class="float-right">';
            $comment_table .= $wu['duration'];
            $comment_table .= '&nbspHours</span>';
            $comment_table .= '</div>';
            $comment_table .= '<div class="comment_body">';
            $comment_table .= $wu['description'];
            $comment_table .= '</div>';
        }
    } else {
        $comment_table = __('No comments found');
    }

    $upload_comment_form = '<div class="w100p">';
    $upload_comment_form .= '<form method="post" id="comment_form" enctype="multipart/form-data">';
    $upload_comment_form .= '<h4>'.__('Add comment').'</h4>';
    $upload_comment_form .= html_print_table($table_comments_section, true);
    $upload_comment_form .= html_print_input_hidden('addComment', 1, true);
    $upload_comment_form .= '</form>';
    $upload_comment_form .= '<h4>'.__('Comments').'</h4>';
    $upload_comment_form .= $comment_table;
    $upload_comment_form .= '</div>';

    echo '<div class="ui_toggle">';
    ui_toggle(
        $upload_comment_form,
        __('Comments'),
        '',
        '',
        true,
        false,
        'white-box-content',
        'w98p'
    );
    echo '</div>';
}
