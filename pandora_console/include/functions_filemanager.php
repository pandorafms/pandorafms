<?php

/**
 * Images File Manager functions.
 *
 * @category   Functions
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Get global data.
// Constants.
define('MIME_UNKNOWN', 0);
define('MIME_DIR', 1);
define('MIME_IMAGE', 2);
define('MIME_ZIP', 3);
define('MIME_TEXT', 4);

if (function_exists('mime_content_type') === false) {


    /**
     * Gets the MIME type of a file.
     *
     * Help function in case mime_magic is not loaded on PHP.
     *
     * @param string $filename Filename to get MIME type.
     *
     * @return The MIME type of the file.
     */
    function mime_content_type(string $filename)
    {
        $ext_fields = explode('.', $filename);
        $ext = array_pop($ext_fields);
        $ext = strtolower($ext);
        if (array_key_exists($ext, MIME_TYPES) === true) {
            return MIME_TYPES[$ext];
        } else if (function_exists('finfo_open') === true) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            db_pandora_audit(
                AUDIT_LOG_FILE_MANAGER,
                'Warning: Cannot find finfo_open function. Fileinfo extension is not enabled. Please add "extension=fileinfo.so" or "extension=fileinfo.dll" in your php.ini'
            );

            return 'unknown';
        }
    }


}

global $config;

require_once $config['homedir'].'/vendor/autoload.php';


/**
 * Upload file.
 *
 * @param boolean $upload_file_or_zip     Upload file or zip.
 * @param string  $default_real_directory String with default directory.
 * @param string  $destination_directory  String with destination directory.
 * @param array   $filterFilesType        If come filled, filter uploaded files with this extensions.
 *
 * @throws Exception Exception.
 * @return void
 */
function upload_file($upload_file_or_zip, $default_real_directory, $destination_directory, $filterFilesType=[])
{
    global $config;
    $config['filemanager'] = [];
    $config['filemanager']['correct_upload_file'] = 0;
    $config['filemanager']['message'] = '';

    check_login();

    if (!check_acl($config['id_user'], 0, 'AW')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access File manager'
        );
        include 'general/noaccess.php';
        return;
    }

    if ($upload_file_or_zip === true) {
        $decompress = (bool) get_parameter('decompress', false);
        if ($decompress === false) {
            $upload_file = true;
            $upload_zip = false;
        } else {
            $upload_file = false;
            $upload_zip = true;
        }
    } else {
        $upload_file = (bool) get_parameter('upload_file');
        $upload_zip  = (bool) get_parameter('upload_zip');
    }

    // Upload file.
    if ($upload_file === true) {
        if (isset($_FILES['file']) === true && empty($_FILES['file']['name']) === false) {
            $filename       = $_FILES['file']['name'];
            $real_directory = filemanager_safe_directory($destination_directory);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);

            $umask          = io_safe_output((string) get_parameter('umask'));
            // $parse_all_queries = explode('&', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY));
            // $parse_sec2_query = explode('=', $parse_all_queries[1]);
            // $check_extension = true;
            // if ($parse_sec2_query[1] === 'operation/snmpconsole/snmp_mib_uploader') {
            // if ((strtolower($extension) !== 'mib' && strtolower($extension) !== 'zip')) {
            // $check_extension = false;
            // } else {
            // $check_extension = true;
            // }
            // }
            // (strtolower($extension) !== 'mib' && strtolower($extension) !== 'zip')
            if (strpos($real_directory, $default_real_directory) !== 0) {
                // Perform security check to determine whether received upload
                // directory is part of the default path for caller uploader and
                // user is not trying to access an external path (avoid
                // execution of PHP files in directories that are not explicitly
                // controlled by corresponding .htaccess).
                $config['filemanager']['message'] = ui_print_error_message(__('Security error'));
            } else {
                $result = false;
                // Copy file to directory and change name.
                $nombre_archivo = sprintf('%s/%s', $real_directory, $filename);
                try {
                    if (isset($_FILES['file']['type']) === true && empty($_FILES['file']['type']) === false) {
                        $type = $_FILES['file']['type'];
                    } else {
                        $type = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                    }

                    if (empty($filterFilesType) === true || in_array($type, $filterFilesType) === true) {
                        $result = copy($_FILES['file']['tmp_name'], $nombre_archivo);
                    } else {
                        $types_allowed = implode(', ', $filterFilesType);
                        $error_message = 'The uploaded file is not allowed. Only '.$types_allowed.' files can be uploaded.';
                        throw new Exception(__($error_message));
                    }
                } catch (Exception $ex) {
                    db_pandora_audit(
                        AUDIT_LOG_FILE_MANAGER,
                        'Error Uploading files: '.$ex->getMessage()
                    );
                    $config['filemanager']['message'] = ui_print_error_message(__('Upload error').': '.$ex->getMessage());
                }

                if ($result === true) {
                    // If umask is provided, set.
                    if (empty($umask) === false) {
                        try {
                            chmod($nombre_archivo, $umask);
                        } catch (Exception $ex) {
                            $config['filemanager']['message'] = ui_print_error_message(__('Issue setting umask: %s', $ex->getMessage()));
                        }
                    }

                    // Upload performed properly.
                    $config['filemanager']['message'] .= ui_print_success_message(__('Upload correct'));
                    $config['filemanager']['correct_upload_file'] = 1;
                    // Delete temporal file.
                    unlink($_FILES['file']['tmp_name']);
                }
            }
        }
    }

    // Upload zip.
    if ($upload_zip === true) {
        $parse_all_queries = explode('&', parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY));
        $parse_sec2_query = explode('=', $parse_all_queries[1]);
        if (isset($_FILES['file']) === true
            && empty($_FILES['file']['name']) === false
        ) {
            $filename = $_FILES['file']['name'];
            $filepath = $_FILES['file']['tmp_name'];
            $real_directory = filemanager_safe_directory($destination_directory);
            $secure = true;
            try {
                $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
                if (empty($filterFilesType) === true || in_array($ext, $filterFilesType) === true) {
                    // Security control structure.
                    $zip = new \ZipArchive;
                    if ($zip->open($filepath) === true) {
                        for ($i = 0; $i < $zip->numFiles; $i++) {
                            $unzip_filename = $zip->getNameIndex($i);
                            $extension = pathinfo($unzip_filename, PATHINFO_EXTENSION);
                            if (in_array(strtolower($extension), $filterFilesType) === false) {
                                $error_message = 'The uploaded file is not allowed. Only '.$types_allowed.' files can be uploaded.';
                                $secure = false;
                                throw new Exception(__($error_message));
                            }
                        }
                    }
                }
            } catch (Exception $ex) {
                db_pandora_audit(
                    AUDIT_LOG_FILE_MANAGER,
                    'Error Uploading files: '.$ex->getMessage()
                );
                $config['filemanager']['message'] = ui_print_error_message(__('Upload error').': '.$ex->getMessage());
            }

            if (strpos($real_directory, $default_real_directory) !== 0 || $secure === false) {
                // Perform security check to determine whether received upload
                // directory is part of the default path for caller uploader
                // and user is not trying to access an external path (avoid
                // execution of PHP files in directories that are not explicitly
                // controlled by corresponding .htaccess).
                ui_print_error_message(__('Security error'));
            } else {
                if (PandoraFMS\Tools\Files::unzip($filepath, $real_directory) === false) {
                    $config['filemanager']['message'] = ui_print_error_message(__('It was impossible to uncompress your file'));
                } else {
                    unlink($_FILES['file']['tmp_name']);
                    $config['filemanager']['message'] = ui_print_success_message(__('Upload correct'));
                    $config['filemanager']['correct_upload_file'] = 1;
                }
            }
        }
    }
}


if (isset($_SERVER['CONTENT_LENGTH']) === true) {
    // Control the max_post_size exceed.
    if (intval($_SERVER['CONTENT_LENGTH']) > 0 && empty($_POST) === true && empty($_FILES) === true) {
        $config['filemanager']['correct_upload_file'] = 0;
        $config['filemanager']['message'] = ui_print_error_message(__('File size seems to be too large. Please check your php.ini configuration or contact with the administrator'), '', true);
    }
}


function create_text_file($default_real_directory, $destination_directory)
{
    global $config;

    $config['filemanager'] = [];
    $config['filemanager']['correct_upload_file'] = 0;
    $config['filemanager']['message'] = null;

    check_login();

    if (!check_acl($config['id_user'], 0, 'AW')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access File manager'
        );
        include 'general/noaccess.php';
        return;
    }

    $filename = filemanager_safe_directory((string) get_parameter('name_file'));

    if (empty($filename) === false) {
        $real_directory = filemanager_safe_directory($destination_directory);
        $umask          = (string) get_parameter('umask');

        if (strpos($real_directory, $default_real_directory) !== 0) {
            // Perform security check to determine whether received upload
            // directory is part of the default path for caller uploader and
            // user is not trying to access an external path (avoid execution
            // of PHP files in directories that are not explicitly controlled by
            // corresponding .htaccess).
            ui_print_error_message(__('Security error'));
        } else {
            $nombre_archivo = $real_directory.'/'.$filename;

            try {
                // Create the file.
                $result = touch($nombre_archivo);

                if ($result === true) {
                    if ($umask !== '') {
                        chmod($nombre_archivo, $umask);
                    }

                    ui_print_success_message(__('File created correctly'));
                    $config['filemanager']['correct_upload_file'] = 1;
                } else {
                    throw new Exception(__('Error creating file'));
                }
            } catch (Exception $ex) {
                $config['filemanager']['message'] = ui_print_error_message($ex->getMessage());
            }
        }
    } else {
        $config['filemanager']['message'] = ui_print_error_message(__('Error creating file with empty name'));
    }
}


// CREATE DIR.
$create_dir = (bool) get_parameter('create_dir');
if ($create_dir === true) {
    global $config;

    $sec2 = get_parameter('sec2');
    if ($sec2 === 'enterprise/godmode/agentes/collections' || $sec2 === 'advanced/collections') {
        $homedir_filemanager = io_safe_output($config['attachment_store']).'/collection';
    } else {
        $homedir_filemanager = io_safe_output($config['homedir']);
    }

    $config['filemanager'] = [];
    $config['filemanager']['correct_create_dir'] = 0;
    $config['filemanager']['message'] = null;

    $directory = filemanager_safe_directory((string) get_parameter('directory'));
    $hash      = (string) get_parameter('hash');
    $testHash  = md5($directory.$config['server_unique_identifier']);

    if ($hash !== $testHash) {
        ui_print_error_message(__('Security error.'));
    } else {
        $dirname = filemanager_safe_directory((string) get_parameter('dirname'));

        if (empty($dirname) === false) {
            // Create directory.
            try {
                // If directory exists, add an slash at end.
                if (empty($directory) === false) {
                    $directory .= '/';
                }

                $result = mkdir($homedir_filemanager.'/'.$directory.$dirname);

                if ($result === true) {
                    $config['filemanager']['message'] = ui_print_success_message(__('Directory created'), '', true);
                    $config['filemanager']['correct_create_dir'] = 1;
                } else {
                    throw new Exception(__('Something gone wrong creating directory'));
                }
            } catch (Exception $ex) {
                $config['filemanager']['message'] = ui_print_error_message($ex->getMessage(), '', true);
            }
        } else {
            $config['filemanager']['message'] = ui_print_error_message(__('Error creating file with empty name'), '', true);
        }
    }
}

// DELETE FILE OR DIR.
$delete_file = (bool) get_parameter('delete_file');
if ($delete_file === true) {
    global $config;

    $config['filemanager'] = [];
    $config['filemanager']['delete'] = 0;
    $config['filemanager']['message'] = null;

    $filename = (string) get_parameter('filename');
    $filename = io_safe_output($filename);
    $hash     = get_parameter('hash', '');
    $testHash = md5($filename.$config['server_unique_identifier']);

    if ($hash !== $testHash) {
        $config['filemanager']['message'] = ui_print_error_message(__('Security error'), '', true);
    } else {
        $config['filemanager']['message'] = ui_print_success_message(__('Deleted'), '', true);

        if (is_dir($filename) === true) {
            if (rmdir($filename) === true) {
                $config['filemanager']['delete'] = 1;
            } else {
                $config['filemanager']['delete'] = 0;
            }
        } else {
            if (unlink($filename) === true) {
                $config['filemanager']['delete'] = 1;
            } else {
                $config['filemanager']['delete'] = 0;
            }
        }

        if ($config['filemanager']['delete'] == 0) {
            $config['filemanager']['message'] = ui_print_error_message(__('Deleted'), '', true);
        }
    }
}


/**
 * Recursive delete directory and empty or not directory.
 *
 * @param string $dir The dir to deletete.
 *
 * @return void
 */
function filemanager_delete_directory($dir)
{
    // Windows compatibility
    $dir = str_replace('\\', '/', $dir);

    if ($handle = opendir($dir)) {
        while (false !== ($file = readdir($handle))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($dir.$file)) {
                    if (!rmdir($dir.$file)) {
                        filemanager_delete_directory($dir.$file.'/');
                    }
                } else {
                    unlink($dir.$file);
                }
            }
        }

        closedir($handle);
        rmdir($dir);
    }
}


/**
 * Read a directory recursibly and return a array with the files with
 * the absolute path and relative
 *
 * @param string  $dir            Absoute dir to scan.
 * @param string  $relative_path  Relative path to scan, by default ''.
 * @param boolean $add_empty_dirs Add empty dirs.
 *
 * @return array The files in the dirs, empty array for empty dir of files.
 */
function filemanager_read_recursive_dir($dir, $relative_path='', $add_empty_dirs=false)
{
    $return = [];

    // Windows compatibility.
    $dir = str_replace('\\', '/', $dir);
    $relative_path = str_replace('\\', '/', $relative_path);

    if ($handle = opendir($dir)) {
        while (false !== ($entry = readdir($handle))) {
            if (($entry !== '.') && ($entry !== '..')) {
                if (is_dir($dir.$entry) === true) {
                    $return[] = [
                        'relative' => $relative_path.$entry,
                        'absolute' => $dir.$entry,
                        'dir'      => true,
                    ];

                    $return = array_merge(
                        $return,
                        filemanager_read_recursive_dir(
                            $dir.$entry.'/',
                            $relative_path.$entry.'/',
                            '',
                            $add_empty_dirs
                        )
                    );
                } else {
                    $return[] = [
                        'relative' => $relative_path.$entry,
                        'absolute' => $dir.$entry,
                        'dir'      => false,
                    ];
                }
            }
        }

        closedir($handle);
    }

    return $return;
}


/**
 * The main function to show the directories and files.
 *
 * @param string  $real_directory      The string of dir as realpath.
 * @param string  $relative_directory  The string of dir as relative path.
 * @param string  $url                 The url to set in the forms and some links in the explorer.
 * @param string  $father              The directory father don't navigate bottom this.
 * @param boolean $editor              The flag to set the edition of text files.
 * @param boolean $readOnly            If true, only can read the files.
 * @param string  $url_file            The url to put in the files instead the default. By default empty string and use the url of filemanager.
 * @param boolean $download_button     The flag to show download button, by default false.
 * @param string  $umask               The umask as hex values to set the new files or updload.
 * @param boolean $homedir_filemanager Homedir filemanager.
 * @param array   $options             Associative array. ['all' => true] will show all options. Check function for valid options.
 */
function filemanager_file_explorer(
    $real_directory,
    $relative_directory,
    $url,
    $father='',
    $editor=false,
    $readOnly=false,
    $url_file='',
    $download_button=false,
    $umask='',
    $homedir_filemanager=false,
    $options=[]
) {
    global $config;

    // Windows compatibility.
    $real_directory = str_replace('\\', '/', $real_directory);
    $relative_directory = str_replace('\\', '/', $relative_directory);
    $father = str_replace('\\', '/', $father);
    // Options.
    $allowZipFiles = (isset($options['all']) === true) || ((isset($options['allowZipFiles']) === true) && ($options['allowZipFiles'] === true));
    $allowCreateText = (isset($options['all']) === true) || ((isset($options['allowCreateText']) === true) && ($options['allowCreateText'] === true));
    $allowCreateFolder = (isset($options['allowCreateFolder'])) ? false : true;

    if (isset($options['denyCreateText']) === true) {
        if ($options['denyCreateText'] === true) {
            $allowCreateText = false;
        }
    }

    if ($homedir_filemanager === false) {
        $homedir_filemanager = $config['homedir'];
    }

    $hack_metaconsole = (is_metaconsole() === true) ? '../../' : '';

    ?>
    <script type="text/javascript">
        <?php if ($allowCreateFolder === true) : ?>
        function show_form_create_folder() {
            actions_dialog('create_folder');
            $("#create_folder").css("display", "block");
            check_opened_dialog('create_folder');
        }
        <?php endif ?>

        <?php if ($allowCreateText === true) : ?>
            function show_create_text_file() {
                actions_dialog('create_text_file');
                $("#create_text_file").css("display", "block");
                check_opened_dialog('create_text_file');
            }
        <?php endif ?>

        function show_upload_file() {
            actions_dialog('upload_file');
            $("#upload_file").css("display", "block");
            check_opened_dialog('upload_file');
        }

        function check_opened_dialog(check_opened) {
            <?php if ($allowCreateFolder === true) : ?>
                if (check_opened !== 'create_folder') {
                    if (($("#create_folder").hasClass("ui-dialog-content") && $('#create_folder').dialog('isOpen') === true)) {
                        $('#create_folder').dialog('close');
                    }
                }
            <?php endif ?>

            <?php if ($allowCreateText === true) : ?>
                if (check_opened !== 'create_text_file') {
                    if (($("#create_text_file").hasClass("ui-dialog-content") && $('#create_text_file').dialog('isOpen') === true)) {
                        $('#create_text_file').dialog('close');
                    }
                }
            <?php endif ?>
            if (check_opened !== 'upload_file') {
                if (($("#upload_file").hasClass("ui-dialog-content") && $('#upload_file').dialog('isOpen')) === true) {
                    $('#upload_file').dialog('close');
                }
            }
        }

        function actions_dialog(action) {
            $('.' + action).addClass('file_table_modal_active');
            var title_action = '';
            switch (action) {
                <?php if ($allowCreateFolder === true) : ?>
                    case 'create_folder':
                        title_action = "<?php echo __('Create a Directory'); ?>";
                        break;
                <?php endif ?>

                <?php if ($allowCreateText === true) : ?>
                case 'create_text_file':
                    title_action = "<?php echo __('Create File'); ?>";
                    break;
                <?php endif ?>

                case 'upload_file':
                    title_action = "<?php echo __('Upload Files'); ?>";
                    break;

                default:
                    break;
            }

            $('#' + action)
                .dialog({
                    title: title_action,
                    resizable: true,
                    draggable: true,
                    modal: true,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    },
                    width: 500,
                    minWidth: 500,
                    minHeight: 210,
                    maxWidth: 800,
                    maxHeight: 300,
                    close: function() {
                        $('.' + action).removeClass('file_table_modal_active');
                    }
                }).show();
        }

        function show_modal_real_path(path) {
            if (navigator.clipboard && window.isSecureContext) <?php $secure_con = true; ?>;
            else <?php $secure_con = false; ?>;
            $('#modal_real_path').addClass('file_table_modal_active');
            $('#real_path').empty();
            $('#real_path').html(path);
            title_action = "<?php echo __('Real path'); ?>";
            $('#modal_real_path')
                .dialog({
                    title: title_action,
                    resizable: true,
                    draggable: true,
                    modal: true,
                    overlay: {
                        opacity: 0.5,
                        background: "black"
                    },
                    width: 448,
                    minWidth: 448,
                    minHeight: 213,
                    maxWidth: 800,
                    maxHeight: 300,
                    close: function() {
                        $('#modal_real_path').removeClass('file_table_modal_active');
                    }
                }).show();

            $("#button-submit").on("click", copyToClipboard);
        }

        function copyToClipboard() {
            if (navigator.clipboard && window.isSecureContext) {
                window.navigator.clipboard.writeText($("#real_path").text()).then(function() {
                    $('#modal_real_path').dialog('close');
                });
            }
        }
    </script>
    <?php
    // List files.
    if (is_dir($real_directory) === false) {
        echo __('Directory %s doesn\'t exist!', $relative_directory);
        return;
    }

    $files = filemanager_list_dir($real_directory);

    if (empty($files) === false) {
        $table = new stdClass();
        $table->width = '100%';
        $table->id = 'table_filemanager';

        $table->class = 'info_table';
        $table->title = '<span>'.__('Index of %s', $relative_directory).'</span>';
        $table->colspan = [];
        $table->data = [];
        $table->head = [];
        $table->size = [];

        $table->align[1] = 'left';
        $table->align[2] = 'left';
        $table->align[3] = 'left';
        $table->align[4] = 'left';

        $table->size[0] = '24px';

        $table->head[0] = '';
        $table->head[1] = __('Name');
        $table->head[2] = __('Last modification');
        $table->head[3] = __('Size');
        $table->head[4] = __('Actions');

        $prev_dir = explode('/', $relative_directory);
        $count_prev_dir = count($prev_dir);
        $prev_dir_str = '';
        $prev_dir_count = count($prev_dir);
        for ($i = 0; $i < ($prev_dir_count - 1); $i++) {
            $prev_dir_str .= $prev_dir[$i];
            if ($i < ($prev_dir_count - 2)) {
                $prev_dir_str .= '/';
            }
        }

        if (($prev_dir_str != '') && ($father != $relative_directory)) {
            $table->data[0][0] = html_print_image('images/go_previous.png', true, ['class' => 'invert_filter']);
            $table->data[0][1] = '<a href="'.$url.'&directory='.$prev_dir_str.'&hash2='.md5($prev_dir_str.$config['server_unique_identifier']).'">';
            $table->data[0][1] .= __('Parent directory');
            $table->data[0][1] .= '</a>';

            $table->colspan[0][1] = 5;
        }

        foreach ($files as $fileinfo) {
            $fileinfo['realpath'] = str_replace('\\', '/', $fileinfo['realpath']);
            $relative_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $fileinfo['realpath']);

            $data = [];

            switch ($fileinfo['mime']) {
                case MIME_DIR:
                    $data[0] = html_print_image('images/mimetypes/directory.png', true, ['title' => __('Directory'), 'class' => 'invert_filter']);
                break;

                case MIME_IMAGE:
                    $data[0] = html_print_image('images/mimetypes/image.png', true, ['title' => __('Image'), 'class' => 'invert_filter']);
                break;

                case MIME_ZIP:
                    $data[0] = html_print_image('images/mimetypes/zip.png', true, ['title' => __('Compressed file'), 'class' => 'invert_filter']);
                break;

                case MIME_TEXT:
                    $data[0] = html_print_image('images/mimetypes/text.png', true, ['title' => __('Text file'), 'class' => 'invert_filter']);
                break;

                case MIME_UNKNOWN:
                    if ((int) $fileinfo['size'] === 0) {
                        if ((strstr($fileinfo['name'], '.txt') !== false) || (strstr($fileinfo['name'], '.conf') !== false) || (strstr($fileinfo['name'], '.sql') !== false) || (strstr($fileinfo['name'], '.pl') !== false)) {
                            $fileinfo['mime'] = MIME_TEXT;
                            $data[0] = html_print_image('images/mimetypes/text.png', true, ['title' => __('Text file'), 'class' => 'invert_filter']);
                        } else {
                            // Unknown.
                            $data[0] = '';
                        }
                    } else {
                        // Pdf.
                        $data[0] = '';
                    }
                break;

                default:
                    $data[0] = html_print_image('images/mimetypes/unknown.png', true, ['title' => __('Unknown'), 'class' => 'invert_filter']);
                break;
            }

            if ($fileinfo['is_dir']) {
                $data[1] = '<a href="'.$url.'&directory='.$relative_directory.'/'.io_safe_input($fileinfo['name']).'&hash2='.md5($relative_directory.'/'.$fileinfo['name'].$config['server_unique_identifier']).'">'.io_safe_input($fileinfo['name']).'</a>';
            } else if (empty($url_file) === false) {
                // Set the custom url file.
                $url_file_clean = str_replace('[FILE_FULLPATH]', $fileinfo['realpath'], $url_file);

                $data[1] = '<a href="'.$url_file_clean.'">'.io_safe_input($fileinfo['name']).'</a>';
            } else {
                $filename = base64_encode($relative_directory.'/'.$fileinfo['name']);
                $hash = md5($filename.$config['server_unique_identifier']);
                $data[1] = '<a href="'.$hack_metaconsole.'include/get_file.php?file='.urlencode($filename).'&hash='.$hash.'">'.io_safe_input($fileinfo['name']).'</a>';
            }

            // Notice that uploaded php files could be dangerous.
            if (pathinfo($fileinfo['realpath'], PATHINFO_EXTENSION) === 'php'
                && (is_readable($fileinfo['realpath']) === true || is_executable($fileinfo['realpath']) === true)
            ) {
                $error_message = __('This file could be executed by any user');
                $error_message .= '. '.__('Make sure it can\'t perform dangerous tasks');
                $data[1] = '<span class="error forced_title" data-title="'.$error_message.'" data-use_title_for_force_title="1">'.$data[1].'</span>';
            }

            $data[2] = ui_print_timestamp(
                $fileinfo['last_modified'],
                true,
                ['prominent' => true]
            );
            if ($fileinfo['is_dir']) {
                $data[3] = '';
            } else {
                $data[3] = ui_format_filesize($fileinfo['size']);
            }

            // Actions buttons
            // Delete button.
            $data[4] = '<div class="table_action_buttons flex flex-end">';
            $typefile = array_pop(explode('.', $fileinfo['name']));
            if (is_writable($fileinfo['realpath']) === true
                && (is_dir($fileinfo['realpath']) === false || count(scandir($fileinfo['realpath'])) < 3)
                && ($readOnly === false)
            ) {
                $data[4] .= '<form method="post" action="'.$url.'" style="">';
                $data[4] .= html_print_input_image(
                    'delete',
                    'images/delete.svg',
                    1,
                    'margin-top: 2px;height:21px',
                    true,
                    [
                        'title'   => __('Delete'),
                        'class'   => 'invert_filter main_menu_icon',
                        'onclick' => 'if (!confirm(\' '.__('Are you sure?').'\')) return false;',
                    ]
                );
                $data[4] .= html_print_input_hidden('filename', $fileinfo['realpath'], true);
                $data[4] .= html_print_input_hidden('hash', md5($fileinfo['realpath'].$config['server_unique_identifier']), true);
                $data[4] .= html_print_input_hidden('delete_file', 1, true);

                $relative_dir = str_replace($homedir_filemanager, '', str_replace('\\', '/', dirname($fileinfo['realpath'])));

                if ($relative_dir[0] === '/') {
                    $relative_dir = substr($relative_dir, 1);
                }

                $hash2 = md5($relative_dir.$config['server_unique_identifier']);

                $data[4] .= html_print_input_hidden('directory', $relative_dir, true);
                $data[4] .= html_print_input_hidden('hash2', $hash2, true);
                $data[4] .= '</form>';

                if (($editor) && ($readOnly === false)) {
                    if (($typefile !== 'bin') && ($typefile !== 'pdf') && ($typefile !== 'png') && ($typefile !== 'jpg')
                        && ($typefile !== 'iso') && ($typefile !== 'docx') && ($typefile !== 'doc') && ($fileinfo['mime'] != MIME_DIR)
                    ) {
                        $hash = md5($fileinfo['realpath'].$config['server_unique_identifier']);
                        $data[4] .= "<a style='vertical-align: top;' href='$url&edit_file=1&hash=".$hash.'&location_file='.$fileinfo['realpath']."' style='float: left;'>".html_print_image('images/edit.svg', true, ['style' => 'margin-top: 2px;', 'title' => __('Edit file'), 'class' => 'main_menu_icon invert_filter']).'</a>';
                    }
                }
            }

            if ((!$fileinfo['is_dir']) && ($download_button)) {
                $filename = base64_encode($fileinfo['name']);
                $hash = md5($filename.$config['server_unique_identifier']);
                $data[4] .= '<a href="'.$hack_metaconsole.'include/get_file.php?file='.urlencode($filename).'&hash='.$hash.'" style="vertical-align: 25%;">';
                $data[4] .= html_print_image('images/file.png', true, ['class' => 'invert_filter']);
                $data[4] .= '</a>';
            }

            if (is_writable($fileinfo['realpath']) === true
                && (is_dir($fileinfo['realpath']) === false || count(scandir($fileinfo['realpath'])) < 3)
                && ($readOnly === false)
            ) {
                $data[4] .= '<a href="javascript: show_modal_real_path(`'.$fileinfo['realpath'].'`);">'.html_print_image('images/enable.svg', true, ['style' => 'margin-top: 2px;', 'title' => __('Real path'), 'class' => 'invert_filter main_menu_icon']).'</a>';
            }

            $data[4] .= '</div>';

            array_push($table->data, $data);
        }
    } else {
        ui_print_info_message(
            [
                'no_close' => true,
                'message'  => __('No files or directories to show.'),
            ]
        );
    }

    if ($readOnly === false) {
        if (is_writable($real_directory) === true) {
            // The buttons to make actions.
            $tabs_dialog = '<ul id="file_table_modal">';
            if ($allowCreateFolder === true) {
                $tabs_dialog .= '
                <li class="create_folder">
                    <a href="javascript: show_form_create_folder();">'.html_print_image(
                    'images/create_directory.png',
                    true,
                    [
                        'title' => __('Create directory'),
                        'class' => 'invert_filter',
                    ]
                ).'<span>'.__('Create a Directory').'</span>
                    </a>
                </li>';
            }

            if ($allowCreateText === true) {
                $tabs_dialog .= '
                <li class="create_text_file">
                    <a href="javascript: show_create_text_file();">'.html_print_image(
                    'images/create_file.png',
                    true,
                    [
                        'title' => __('Create File'),
                        'class' => 'invert_filter',
                    ]
                ).'<span>'.__('Create File').'</span>
                    </a>
                </li>';
            }

            $tabs_dialog .= '
            <li class="upload_file">
                <a href="javascript: show_upload_file();">'.html_print_image(
                'images/upload_file.png',
                true,
                [
                    'title' => __('Upload Files'),
                    'class' => 'invert_filter',
                ]
            ).'<span>'.__('Upload Files').'</span>
                </a>
            </li>';

            $tabs_dialog .= '</ul>';

            // Create folder section.
            if ($allowCreateFolder === true) {
                $createFolderElements = $tabs_dialog;
                $createFolderElements .= sprintf('<form method="POST" action="%s">', $url);
                $createFolderElements .= html_print_input_text('dirname', '', '', 30, 255, true);
                $createFolderElements .= html_print_submit_button(__('Create'), 'crt', false, [ 'class' => 'submitButton', 'style' => 'float:right', 'icon' => 'next'], true);
                $createFolderElements .= html_print_input_hidden('directory', $relative_directory, true);
                $createFolderElements .= html_print_input_hidden('create_dir', 1, true);
                $createFolderElements .= html_print_input_hidden('hash', md5($relative_directory.$config['server_unique_identifier']), true);
                $createFolderElements .= html_print_input_hidden('hash2', md5($relative_directory.$config['server_unique_identifier']), true);
                $createFolderElements .= '</form>';

                html_print_div(
                    [
                        'id'      => 'create_folder',
                        'class'   => 'invisible',
                        'content' => $createFolderElements,
                    ]
                );
            }

            // Upload file section.
            $uploadFileElements = $tabs_dialog;
            $uploadFileElements .= sprintf('<form method="POST" action="%s" enctype="multipart/form-data">', $url);
            $uploadFileElements .= html_print_input_hidden('umask', $umask, true);

            if ($allowZipFiles === true) {
                $uploadFileElements .= ui_print_help_tip(__('The zip upload in this dir, easy to upload multiple files.'), true);
                $uploadFileElements .= html_print_input_file('file', true, false);
                $uploadFileElements .= html_print_div(
                    [
                        'id'      => 'checkbox-decompress',
                        'content' => html_print_checkbox(
                            'decompress',
                            1,
                            false,
                            true,
                        ).__('Decompress'),
                        'style'   => 'margin-top:25px',
                    ],
                    true
                );
                $uploadFileElements .= html_print_input_hidden('upload_file_or_zip', 1, true);
            } else {
                $uploadFileElements .= html_print_div(
                    [
                        'id'      => 'upload_file_input_full',
                        'content' => html_print_input_file(
                            'file',
                            true,
                            ['style' => 'border:0; padding:0; width:100%']
                        ),
                    ],
                    true
                );
                $uploadFileElements .= html_print_input_hidden('upload_file', 1, true);
            }

            $uploadFileElements .= html_print_submit_button(__('Go'), 'go', false, [ 'class' => 'submitButton', 'style' => 'float:right; margin-top: 10px;', 'icon' => 'next'], true);
            $uploadFileElements .= html_print_input_hidden('real_directory', $real_directory, true);
            $uploadFileElements .= html_print_input_hidden('directory', $relative_directory, true);
            $uploadFileElements .= html_print_input_hidden('hash', md5($real_directory.$relative_directory.$config['server_unique_identifier']), true);
            $uploadFileElements .= html_print_input_hidden('hash2', md5($relative_directory.$config['server_unique_identifier']), true);

            $uploadFileElements .= '</form>';

            html_print_div(
                [
                    'id'      => 'upload_file',
                    'class'   => 'invisible',
                    'content' => $uploadFileElements,
                ]
            );

            // Create text section.
            if ($allowCreateText === true) {
                $createTextElements = $tabs_dialog;
                $createTextElements .= '<form method="post" action="'.$url.'">';
                $createTextElements .= html_print_input_text('name_file', '', '', 30, 50, true);
                $createTextElements .= html_print_submit_button(__('Create'), 'create', false, [ 'class' => 'submitButton', 'style' => 'float:right', 'icon' => 'next'], true);
                $createTextElements .= html_print_input_hidden('real_directory', $real_directory, true);
                $createTextElements .= html_print_input_hidden('directory', $relative_directory, true);
                $createTextElements .= html_print_input_hidden('hash', md5($real_directory.$relative_directory.$config['server_unique_identifier']), true);
                $createTextElements .= html_print_input_hidden('umask', $umask, true);
                $createTextElements .= html_print_input_hidden('create_text_file', 1, true);
                $createTextElements .= '</form>';

                html_print_div(
                    [
                        'id'      => 'create_text_file',
                        'class'   => 'invisible',
                        'content' => $createTextElements,
                    ]
                );
            }

            echo "<div style='width: ".$table->width.";' class='file_table_buttons'>";

            if ($allowCreateFolder === true) {
                $buttons[] = html_print_button(
                    __('Create directory'),
                    'create_directory',
                    false,
                    'show_form_create_folder()',
                    [
                        'class' => 'margin-right-2 invert_filter secondary',
                        'icon'  => 'create_directory',
                    ],
                    true,
                    false
                );
            }

            if ($allowCreateText === true) {
                $buttons[] = html_print_button(
                    __('Create file'),
                    'create_text',
                    false,
                    'show_create_text_file()',
                    [
                        'class' => 'margin-right-2 invert_filter secondary',
                        'icon'  => 'create_file',
                    ],
                    true,
                    false
                );
            }

            $buttons[] = html_print_button(
                __('Upload file/s'),
                'upload_file',
                false,
                'show_upload_file()',
                [
                    'class' => 'margin-right-2 invert_filter secondary',
                    'icon'  => 'upload_file',
                ],
                true,
                false
            );

            // Show Modal Real Path
            $modal_real_path = "<div><b>Real path is:</b></div>
                                <div id='real_path'></div>";

            if (isset($_SERVER['HTTPS']) === true) {
                if ($_SERVER['HTTPS'] == 'on' || $_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
                    $modal_real_path .= "<div style='float:right;margin: 5em 0 0 auto';>".html_print_submit_button(__('Copy'), 'submit', false, ['icon' => 'wand', 'mode' => 'mini'], true).'</div>';
                }
            }

            html_print_div(
                [
                    'id'      => 'modal_real_path',
                    'class'   => 'invisible',
                    'content' => $modal_real_path,
                ]
            );

            echo '</div>';
        } else {
            echo "<div style='text-align: right; width: ".$table->width."; color:#AC4444; margin-bottom:10px;'>";
            echo "<image class='main_menu_icon invert_filter' src='images/info@svg.svg' />".__('The directory is read-only');
            echo '</div>';
        }
    }

    html_print_table($table);

    html_print_action_buttons(
        implode('', $buttons),
        ['type' => 'form_action']
    );
}


/**
 * Check if a directory is writable.
 *
 * @param string $filepath Directory path to check.
 *
 * @return boolean Wheter the directory is writeable or not.
 */
function filemanager_get_file_info(string $filepath)
{
    global $config;

    $realpath = realpath($filepath);
    $filepath = str_replace('\\', '/', $filepath);
    $mimeExtend = mime_content_type($filepath);
    // Windows compatibility.
    $info = [
        'mime'          => MIME_UNKNOWN,
        'mime_extend'   => ($mimeExtend === false) ? '' : $mimeExtend,
        'link'          => 0,
        'is_dir'        => false,
        'name'          => basename($realpath),
        'url'           => str_replace('//', '/', $config['homeurl'].str_ireplace($config['homedir'], '', $realpath)),
        'realpath'      => $realpath,
        'size'          => filesize($realpath),
        'last_modified' => filemtime($realpath),
    ];

    $zip_mimes = [
        'application/zip',
        'application/x-rar-compressed',
        'application/x-gzip',
        'application/x-bzip2',
    ];
    if (is_dir($filepath) === true) {
        $info['mime'] = MIME_DIR;
        $info['is_dir'] = true;
        $info['size'] = 0;
    } else if (strpos($info['mime_extend'], 'image') !== false) {
        $info['mime'] = MIME_IMAGE;
    } else if (in_array($info['mime_extend'], $zip_mimes) === true) {
        $info['mime'] = MIME_ZIP;
    } else if (strpos($info['mime_extend'], 'text') !== false) {
        $info['mime'] = MIME_TEXT;
    }

    return $info;
}


/**
 * Check if a directory is writable.
 *
 * @param string $dirpath Directory path to check.
 *
 * @return array List with files.
 */
function filemanager_list_dir($dirpath)
{
    $dirpath = str_replace('\\', '/', $dirpath);
    // Windows compatibility
    $files = [];
    $dirs = [];
    $dir = opendir($dirpath);
    while ($file = @readdir($dir)) {
        // Ignore hidden files.
        if ($file[0] === '.') {
            continue;
        }

        $info = filemanager_get_file_info($dirpath.'/'.$file);
        if ($info['is_dir']) {
            $dirs[$file] = $info;
        } else {
            $files[$file] = $info;
        }
    }

    ksort($files);
    ksort($dirs);
    closedir($dir);

    return array_merge($dirs, $files);
}


/**
 * A miminal security check to avoid directory traversal.
 *
 * @param string $directory      String with the complete directory.
 * @param string $safedDirectory String with a safe name directory.
 *
 * @return string Safe directory
 */
function filemanager_safe_directory(
    string $directory,
    string $safedDirectory=''
) {
    // Safe output.
    $directory = io_safe_output($directory);
    $forbiddenAttempting = false;
    // Banned directories.
    $bannedDirectory = [
        'include',
        'godmode',
        'operation',
        'reporting',
        'general',
        ENTERPRISE_DIR,
    ];

    if ((bool) preg_match('/(\.){2}/', $directory) !== false) {
        $directory = preg_replace('/(\.){2}/', '', (empty($safedDirectory) === true) ? $directory : $safedDirectory);
        $forbiddenAttempting = true;
    }

    if ((bool) preg_match('/(/\/\)+/', $directory) !== false) {
        $directory = preg_replace('/(/\/\)+/', '/', (empty($safedDirectory) === true) ? $directory : $safedDirectory);
        $forbiddenAttempting = true;
    }

    if (in_array($directory, $bannedDirectory) === true) {
        // Setted images for default (usually in file manager).
        $directory = (empty($safedDirectory) === false) ? $safedDirectory : 'images';
        $forbiddenAttempting = true;
    }

    if ($forbiddenAttempting === true) {
        db_pandora_audit(
            AUDIT_LOG_HACK_ATTEMPT,
            'Attempting to use a forbidden file or directory name'
        );
    }

    return $directory;
}
