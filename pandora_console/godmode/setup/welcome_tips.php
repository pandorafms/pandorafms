<?php
/**
 * Controller View tips in setup
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Dashboards
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
global $config;
require_once $config['homedir'].'/include/class/TipsWindow.class.php';
$view = get_parameter('view', '');
$action = get_parameter('action', '');
try {
    $tipsWindow = new TipsWindow();
} catch (Exception $e) {
    echo '[TipsWindow]'.$e->getMessage();
    return;
}

if ($view === 'create' || $view === 'edit') {
    // IF exists actions
    if ($action === 'create' || $action === 'edit') {
        $files = $_FILES;
        $id_lang = get_parameter('id_lang', '');
        $id_profile = get_parameter('id_profile', '');
        $title = get_parameter('title', '');
        $text = get_parameter('text', '');
        $url = get_parameter('url', '');
        $enable = get_parameter_switch('enable', '');
        $errors = [];

        if (count($files) > 0) {
            $e = $tipsWindow->validateImages($files);
            if ($e !== false) {
                $errors = $e;
            }
        }

        if (empty($id_lang) === true) {
            $errors[] = __('Language is empty');
        }

        if (empty($title) === true) {
            $errors[] = __('Title is empty');
        }

        if (empty($text) === true) {
            $errors[] = __('Text is empty');
        }

        switch ($action) {
            case 'create':
                if (count($errors) === 0) {
                    if (count($files) > 0) {
                        $uploadImages = $tipsWindow->uploadImages($files);
                    }

                    $response = $tipsWindow->createTip($id_lang, $id_profile, $title, $text, $url, $enable, $uploadImages);

                    if ($response === 0) {
                        $errors[] = __('Error in insert tip');
                    }
                }

                $tipsWindow->viewCreate($errors);
            return;

            case 'edit':
                $idTip = get_parameter('idTip', '');
                $imagesToDelete = get_parameter('images_to_delete', '');
                if (empty($idTip) === false) {
                    if (count($errors) === 0) {
                        if (empty($imagesToDelete) === false) {
                            $imagesToDelete = json_decode(io_safe_output($imagesToDelete), true);
                            $tipsWindow->deleteImagesFromTip($idTip, $imagesToDelete);
                        }

                        if (count($files) > 0) {
                            $uploadImages = $tipsWindow->uploadImages($files);
                        }

                        $response = $tipsWindow->updateTip($idTip, $id_profile, $id_lang, $title, $text, $url, $enable, $uploadImages);

                        if ($response === 0) {
                            $errors[] = __('Error in update tip');
                        }
                    }

                    $tipsWindow->viewEdit($idTip, $errors);
                }
            return;

            default:
                $tipsWindow->draw();
            return;
        }


        return;
    }

    // If not exists actions
    switch ($view) {
        case 'create':
            $tipsWindow->viewCreate();
        return;

        case 'edit':
            $idTip = get_parameter('idTip', '');
            if (empty($idTip) === false) {
                $tipsWindow->viewEdit($idTip);
            }
        return;

        default:
            $tipsWindow->draw();
        return;
    }
}

if ($action === 'delete') {
    $idTip = get_parameter('idTip', '');
    $errors = [];
    if (empty($idTip) === true) {
        $errors[] = __('Tip required');
    }

    if (count($errors) === 0) {
        $response = $tipsWindow->deleteTip($idTip);

        if ($response === 0) {
            $errors[] = __('Error in delete tip');
        }
    }

    $tipsWindow->draw($errors);
    return;
}

$tipsWindow->draw();
