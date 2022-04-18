<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName
namespace UpdateManager;

require_once __DIR__.'/Repo.php';

/**
 * Disk repository abstraction layer.
 */
class RepoDisk extends Repo
{


    /**
     * Class Constructor.
     *
     * @param string $path      Path of the package repository.
     * @param string $extension File extension.
     *
     * @throws \Exception On error.
     */
    public function __construct($path=false, $extension='oum')
    {
        // Check the repository can be opened.
        $dh = opendir($path);
        if ($dh === false) {
            throw new \Exception('error opening repository '.$path);
        }

        closedir($dh);

        parent::__construct($path);

        $this->extension = $extension;
    }


    /**
     * Delete a directory and its contents recursively
     *
     * @param string $dirname Directory to be cleaned.
     *
     * @return boolean
     */
    public static function delete_dir($dirname)
    {
        if (is_dir($dirname)) {
            $dir_handle = @opendir($dirname);
        }

        if (!$dir_handle) {
            return false;
        }

        while ($file = readdir($dir_handle)) {
            if ($file != '.' && $file != '..') {
                if (!is_dir($dirname.'/'.$file)) {
                    @unlink($dirname.'/'.$file);
                } else {
                    self::delete_dir($dirname.'/'.$file);
                }
            }
        }

        closedir($dir_handle);
        @rmdir($dirname);

        return true;
    }


    /**
     * Load repository files.
     *
     * @return void
     * @throws \Exception On Error.
     */
    protected function load()
    {
        if ($this->files !== false) {
            return;
        }

        // Read files in the repository.
        $dh = opendir($this->path);
        if ($dh === false) {
            throw new \Exception('error opening repository');
        }

        $this->files = [];
        while ($file_name = readdir($dh)) {
            // Files must contain a version number.
            if (preg_match('/([\d\.]+?)\_x86_64.'.$this->extension.'$/', $file_name, $utimestamp) === 1
                || preg_match('/([\d\.]+?)\.'.$this->extension.'$/', $file_name, $utimestamp) === 1
            ) {
                // Add the file to the repository.
                $this->files[$utimestamp[1]] = $file_name;
            }
        }

        closedir($dh);

        // Sort them according to the package UNIX timestamp.
        krsort($this->files);
    }


    /**
     * Reload repository files.
     *
     * @return void
     */
    public function reload()
    {
        $this->files = false;
        $this->load();
    }


}
