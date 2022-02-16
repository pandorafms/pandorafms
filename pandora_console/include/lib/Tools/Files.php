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
     * @param string $filename Path to target zip file.
     * @param string $path     Directory to be zipped.
     *
     * @return integer The number of files added to the zip file.
     */
    public static function zip(string $filename, string $path)
    {
        $added_files = 0;
        $zip = new \ZipArchive();
        $zip->open(
            $filename,
            (\ZipArchive::CREATE | \ZipArchive::OVERWRITE)
        );

        if (substr($path, (strlen($path) - 1), 1) !== '/') {
            $path .= '/';
        }

        $rdi = new \RecursiveDirectoryIterator(
            $path
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
                $relativePath = str_replace(
                    $path,
                    '',
                    $filePath
                );

                // Add current file to archive.
                $zip->addFile($filePath, $relativePath);
                $added_files++;

                // Keep file permissions.
                $zip->setExternalAttributesName(
                    $relativePath,
                    \ZipArchive::OPSYS_UNIX,
                    (\fileperms($filePath) << 16)
                );
            }
        }

        $zip->close();

        return $added_files;
    }


    /**
     * Test if given file is zip file.
     *
     * @param string $file File.
     *
     * @return boolean
     */
    public static function testZip(string $file):bool
    {
        if (file_exists($file) === false) {
            return false;
        }

        $zip = new \ZipArchive;
        if (defined('\ZipArchive::RDONLY') === true) {
            // PHP >= 7.4.
            if ($zip->open($file, (\ZipArchive::RDONLY)) === true) {
                $zip->close();
                return true;
            }
        } else {
            // PHP < 7.4.
            if ($zip->open($file, (\ZipArchive::CHECKCONS)) === true) {
                $zip->close();
                return true;
            }
        }

        return false;
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
     *     'a/',
     *     'b/b.txt',
     *     'c/'
     *    ]
     *    This example will exclude 'a' and 'c' subdirectories and 'b/b.txt'
     *    file from ellimination.
     *    Specify full relative paths when definining exclusions.
     *    If you specifies a directory, there's no need to specify content.
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

        if (substr($folder, (strlen($folder) - 1), 1) !== '/') {
            $folder .= '/';
        }

        $pd = opendir($folder);
        if ((bool) $pd === true) {
            while (($pf = readdir($pd)) !== false) {
                if ($pf !== '.' && $pf !== '..') {
                    $pf = $folder.$pf;

                    if (is_dir($pf) === true) {
                        // It's a directory.
                        if (in_array($pf.'/', $exclusions) === false) {
                            self::rmrf($pf.'/');
                        }
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


    /**
     * Create a temporary directory with unique name.
     *
     * @param string $directory The directory where the temporary filename will be created.
     * @param string $prefix    The prefix of the generated temporary filename.
     *                          Windows use only the first three characters of prefix.
     *
     * @return string|false The new temporary filename, or false on failure.
     */
    public static function tempdirnam(string $directory, string $prefix='')
    {
        $filename = tempnam($directory, $prefix);
        if ($filename === false) {
            return false;
        }

        if (file_exists($filename) === true) {
            unlink($filename);
            mkdir($filename);
            if (is_dir($filename) === true && is_writable($filename) === true) {
                return $filename;
            }
        }

        return false;
    }


    /**
     * Apply permissions recursively on path.
     *
     * @param string  $path       Path to a file or directory.
     * @param integer $file_perms Permissions to be applied to files found.
     * @param integer $dir_perms  Permissions to be applied to directories found.
     *
     * @return void
     */
    public static function chmod(
        string $path,
        int $file_perms=0644,
        int $dir_perms=0755
    ) {
        if (is_dir($path) === true) {
            // Permissions over directories.
            $dh = opendir($path);
            if ($dh === false) {
                return;
            }

            while (false !== ($fh = readdir($dh))) {
                if (is_dir($fh) === true) {
                    if ($fh === '.' || $fh === '..') {
                        continue;
                    }

                    // Recursion: directory.
                    self::chmod($path.'/'.$fh, $file_perms, $dir_perms);
                } else {
                    // Recursion: file.
                    self::chmod($path.'/'.$fh, $file_perms, $dir_perms);
                }
            }

            closedir($dh);
        } else {
            // Permissions over files.
            chmod($path, $file_perms);
        }
    }


    /**
     * Move from source to destination
     *
     * @param string  $source       Source (directory or file).
     * @param string  $destination  Destination (directory).
     * @param boolean $content_only Moves only content if directories.
     *
     * @return boolean True if success False if not.
     */
    public static function move(
        string $source,
        string $destination,
        bool $content_only=false
    ):bool {
        if (is_dir($destination) === false
            || is_writable($destination) === false
        ) {
            return false;
        }

        if (is_file($source) === true
            || (is_dir($source) === true && $content_only === false)
        ) {
            return rename($source, $destination.'/'.basename($source));
        }

        // Dir, but content only.
        if (is_dir($source) !== true || $content_only !== true) {
            return false;
        }

        // Get array of all source files.
        $files = scandir($source);
        // Identify directories.
        $source = $source.'/';
        $destination = $destination.'/';
        // Cycle through all source files.
        foreach ($files as $file) {
            if (in_array($file, ['.', '..']) === true) {
                continue;
            }

            // If we copied this successfully, mark it for deletion.
            $return = rename($source.$file, $destination.$file);
            if ($return === false) {
                return $return;
            }
        }

        return $return;
    }


}
