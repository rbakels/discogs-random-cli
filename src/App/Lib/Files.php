<?php
namespace App\Lib;

use Symfony\Component\Console\Output\OutputInterface;

class Files {
    private OutputInterface $output;
    
    public function __construct(OutputInterface $output) {
        $this->output = $output;
    }

    public function getSuggestedReleasesFilename() {
        $dirname = $this->getStorageDirectory();

        return $dirname . "/suggested-releases.json";
    }

    public function getLoginFilename() {
        $dirname = $this->getStorageDirectory();

        return $dirname . "/authentication.json";
    }

    public function getReleasesFilename() {
        $dirname = $this->getStorageDirectory();

        return $dirname . "/releases.json";
    }

    public function getStorageImageDirectory()
    {
        $dirname = $this->getHomeDirectory() . "/.discogs-randomizer/images";
        
        if (!file_exists($filename)) {
            mkdir($dirname, 0755);
        }

        return $dirname;
    }

    public function getStorageDirectory()
    {
        $dirname = $this->getHomeDirectory() . "/.discogs-randomizer";
        
        return $this->mkdirIfNotExists($dirname);
    }

    public function mkdirIfNotExists($directory)
    {        
        if (!file_exists($directory)) {
            mkdir($directory, 0755);
        }

        return $directory;
    }

    public function getHomeDirectory()
    {
        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        $home = getenv('HOME');

        if (!empty($home)) {
            // home should never end with a trailing slash.
            $home = rtrim($home, '/');
        } else if (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
            // home on windows
            $home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
            // If HOMEPATH is a root directory the path can end with a slash. Make sure
            // that doesn't happen.
            $home = rtrim($home, '\\/');
        }

        return empty($home) ? NULL : $home;
    }
}