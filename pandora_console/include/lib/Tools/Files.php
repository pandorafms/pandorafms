<?php
/**
 * Class to manage some advanced operations over files.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage Tools
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
namespace PandoraFMS\Tools;

global $config;

/**
 * Files class definition.
 */
class Files
{


    /**
     * Create Zip.
     *
     * @param string $name Name file.zip genarate.
     * @param string $dir  Directory to conver zip.
     *
     * @return void
     */
    public static function zip(string $name, string $dir)
    {
        // Generate a collections zip for Metaconsole.
        $zip = new \ZipArchive();
        $zip->open(
            $name,
            (\ZipArchive::CREATE | \ZipArchive::OVERWRITE)
        );

        $rdi = new \RecursiveDirectoryIterator(
            $dir
        );
        $files = new \RecursiveIteratorIterator(
            $rdi,
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            // Skip directories
            // (they would be added automatically).
            if ($file->isDir() === false) {
                // Get real and relative
                // path for current file.
                $filePath = $file->getRealPath();
                $relativePath = substr(
                    $filePath,
                    (strlen($dir) + 1)
                );

                // Add current file to archive.
                $zip->addFile($filePath, $relativePath);

                // Keep file permissions.
                $zip->setExternalAttributesName(
                    $relativePath,
                    \ZipArchive::OPSYS_UNIX,
                    (\fileperms($filePath) << 16)
                );
            }
        }

        $zip->close();
    }


    /**
     * Uncompress a zip file keeping the file permissions.
     *
     * @param string $file        File.
     * @param string $target_path Target path.
     *
     * @return boolean
     */
    public static function unzip(string $file, string $target_path):bool
    {
        $zip = new \ZipArchive;
        if ($zip->open($file) === true) {
            $idx = 0;
            $s = $zip->statIndex($idx);
            while ($s !== false && $s !== null) {
                if ($zip->extractTo($target_path, $s['name']) === true) {
                    if ($zip->getExternalAttributesIndex($idx, $opsys, $attr) === true
                        && $opsys === \ZipArchive::OPSYS_UNIX
                    ) {
                        chmod(
                            $target_path.'/'.$s['name'],
                            (($attr >> 16) & 0777)
                        );
                    }
                }

                $s = $zip->statIndex(++$idx);
            };

            $zip->close();
            return true;
        }

        return false;
    }


    /**
     * Completely deletes a folder or only its content.
     *
     * @param string  $folder       Folder to delete.
     * @param boolean $content_only Remove only folder content.
     * @param array   $exclusions   Name of folders or files to avoid deletion
     *    [
     *     'a',
     *     'a/b.txt'
     *    ]
     *    Specify full paths when definining exclusions and don't forget to
     *   exclude containing folder also.
     *
     * @return void
     */
    public static function rmrf(
        string $folder,
        bool $content_only=false,
        array $exclusions=[]
    ):void {
        if (is_dir($folder) !== true || is_readable($folder) !== true) {
            return;
        }

        $pd = opendir($folder);
        if ((bool) $pd === true) {
            while (($pf = readdir($pd)) !== false) {
                if ($pf !== '.' && $pf !== '..') {
                    $pf = $folder.$pf;

                    if (is_dir($pf) === true) {
                        // It's a directory.
                        self::rmrf($pf.'/');
                    } else {
                        // It's a file.
                        if (in_array($pf, $exclusions) === false) {
                            unlink($pf);
                        }
                    }
                }
            }

            closedir($pd);
            if ($content_only === false) {
                if (in_array($pf, $exclusions) === false) {
                    rmdir($folder);
                }
            }
        }
    }


}
