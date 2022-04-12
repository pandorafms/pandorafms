<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName
namespace UpdateManager;

require_once 'constants.php';

/**
 * Software repository abstraction layer.
 */
abstract class Repo
{

    /**
     * Path of the package repository.
     *
     * @var string
     */
    protected $path;

    /**
     * Files in the package repository.
     *
     * @var array
     */
    protected $files;


    /**
     * Class Constructor.
     *
     * @param string $path Path of the package repository.
     *
     * @return void
     * @throws \Exception On error.
     */
    public function __construct($path=false)
    {
        if ($path === false) {
            throw new \Exception('no repository path was provided');
        }

        $this->path = $path;
        $this->files = false;
    }


    /**
     * Load repository files.
     *
     * @return void
     */
    abstract protected function load();


    /**
     * Reload repository files.
     *
     * @return void
     */
    abstract public function reload();


    /**
     * Return a list of all the packages in the repository.
     *
     * @return array The list of packages.
     */
    public function all_packages()
    {
        $this->load();
        return array_values($this->files);
    }


    /**
     * Return a list of packages newer than then given package.
     *
     * @param integer $current_package Current package number.
     *
     * @return array The list of packages as an array.
     */
    public function newer_packages($current_package=0)
    {
        $this->load();
        $new_packages = [];

        foreach ($this->files as $utimestamp => $file_name) {
            if ($utimestamp <= $current_package) {
                break;
            }

            $new_packages[] = $file_name;
        }

        // Return newer packages in ascending order so that the client
        // can update sequentially!
        return array_reverse($new_packages);
    }


    /**
     * Return the name of the newest package.
     *
     * @param integer $current_package Current package number.
     *
     * @return string The name of the newest package inside an array.
     */
    public function newest_package($current_package=0)
    {
        $this->load();
        $newest_package = [];

        reset($this->files);
        $newest_utimestamp = key($this->files);

        if ($newest_utimestamp > $current_package) {
            $newest_package[0] = $this->files[$newest_utimestamp];
        }

        return $newest_package;
    }


    /**
     * Send back the requested package.
     *
     * @param string $package_name Name of the package.
     *
     * @return void
     * @throws \Exception \Exception if the file was not found.
     */
    public function send_package($package_name)
    {
        $this->load();

        // Check the file exists in the repo.
        if ($package_name == false || ! in_array($package_name, array_values($this->files))) {
            throw new \Exception('file not found in repository');
        }

        // Check if the file exists in the filesystem.
        $file = $this->path.'/'.$package_name;
        if (! file_exists($file)) {
            throw new \Exception('file not found');
        }

        // Do not set headers if we are debugging!
        if ($_ENV['UM_DEBUG'] == '') {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename='.basename($file));
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: '.filesize($file));
            ob_clean();
            flush();
        }

        // Do not time out if the file is too big!
        set_time_limit(0);
        readfile($file);
    }


    /**
     * Send back the signature for the given package.
     *
     * @param string $package_name Name of the package.
     *
     * @return void
     * @throws \Exception \Exception if the file was not found.
     */
    public function send_package_signature($package_name)
    {
        $this->load();

        // Check the file exists in the repo.
        if ($package_name == false || ! in_array($package_name, array_values($this->files))) {
            throw new \Exception('file not found in repository');
        }

        // Check if the file exists in the filesystem.
        $file = $this->path.'/'.$package_name.SIGNATURE_EXTENSION;
        if (! file_exists($file)) {
            throw new \Exception('file not found');
        }

        // Send the signature.
        readfile($file);
    }


    /**
     * Send back the requested server package. This function simply calls send_package.
     * Repos may implement their own version on top of it.
     *
     * @param string $file Name of the package.
     *
     * @return void
     */
    public function send_server_package($file)
    {
        $this->send_package($file);
    }


    /**
     * Send back the sinature for the given server package. This function
     * simply calls send_package_signature.  * Repos may implement their own
     * version on top of it.
     *
     * @param string $file Name of the package.
     *
     * @return void
     */
    public function send_server_package_signature($file)
    {
        $this->send_package_signature($file);
    }


    /**
     * Return the number of packages in the repository.
     *
     * @param array $db DB object connected to the database.
     *
     * @return integer The total number of packages.
     */
    public function package_count($db=false)
    {
        $this->load();
        return count($this->files);
    }


    /**
     * Check if a package is in the repository.
     *
     * @param string $package_name Package name.
     *
     * @return boolean True if file is in the repository, FALSE if not.
     */
    public function package_exists($package_name)
    {
        $file = $this->path.'/'.$package_name;
        if (file_exists($file)) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Check if a package is signed.
     *
     * @param string $package_name Package name.
     * @param string $verify       Verify the signature.
     *
     * @return boolean True if the package is signed, FALSE if not.
     */
    public function package_signed($package_name, $verify=false)
    {
        $file = $this->path.'/'.$package_name;
        $file_sig = $file.SIGNATURE_EXTENSION;

        // No signature found.
        if (!file_exists($file_sig)) {
            return false;
        }

        // No need to verify the signature.
        if ($verify === false) {
            return true;
        }

        // Read the signature.
        $signature = base64_decode(file_get_contents($file_sig));
        if ($signature === false) {
            return false;
        }

        // Compute the hash of the data.
        $hex_hash = hash_file('sha512', $file);
        if ($hex_hash === false) {
            return false;
        }

        // Verify the signature.
        if (openssl_verify($hex_hash, $signature, PUB_KEY, 'sha512') === 1) {
            return true;
        }

        return false;
    }


}
