<?php
// phpcs:disable PSR1.Methods.CamelCapsMethodName
namespace UpdateManager;

require_once __DIR__.'/RepoDisk.php';


/**
 * Class to implement distributed updates.
 */
class RepoMC extends RepoDisk
{

    /**
     * Base server name.
     *
     * @var string
     */
    private $FNAME = 'pandorafms_server_enterprise-7.0NG.%s_x86_64.tar.gz';


    /**
     * Class Constructor.
     *
     * @param string|null $path      Path of the package repository.
     * @param string      $extension Files to include.
     * @param string|null $skel      BAse name to identify server filename.
     *
     * @throws \Exception On error.
     */
    public function __construct(
        ?string $path=null,
        string $extension='oum',
        ?string $skel=null
    ) {
        if ($skel !== null) {
            $this->FNAME = $skel;
        }

        parent::__construct($path, $extension);
    }


    /**
     * Get the server package that corresponds to a given version.
     *
     * @param float   $version Version string.
     * @param boolean $dry_run Only check if the package exists.
     *
     * @return boolean
     */
    public function send_server_package($version, bool $dry_run=false)
    {
        $package_name = sprintf($this->FNAME, $version);
        $this->load();

        // Check if the package exists.
        if ($dry_run === true) {
            return $this->package_exists($package_name);
        }

        parent::send_server_package($package_name);

        return true;
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

            $new_packages[] = [
                'file_name'   => $file_name,
                'version'     => $utimestamp,
                'description' => 'Update available from the Metaconsole.',
            ];
        }

        // Return newer packages in ascending order so that the client
        // can update sequentially!
        return array_reverse($new_packages);
    }


    /**
     * Retrieve OUM signature.
     *
     * @param integer $package_name Console package name.
     *
     * @return string Signature codified in base64.
     */
    public function send_package_signature($package_name)
    {
        $signature_file = $this->path.'/'.$package_name.SIGNATURE_EXTENSION;
        $this->load();

        if ($this->package_exists($package_name) === true
            && file_exists($signature_file) === true
        ) {
            $signature = file_get_contents($signature_file);
            return $signature;
        }

        return '';
    }


    /**
     * Retrieve server package signature.
     *
     * @param integer $version Server package version.
     *
     * @return string Signature codified in base64.
     */
    public function send_server_package_signature($version)
    {
        $package_name = sprintf($this->FNAME, $version);
        $signature_file = $this->path.'/'.$package_name.SIGNATURE_EXTENSION;
        $this->load();

        if ($this->package_exists($package_name) === true
            && file_exists($signature_file) === true
        ) {
            $signature = file_get_contents($signature_file);
            return $signature;
        }

        return '';
    }


}
