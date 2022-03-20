<?php
namespace App\Lib;

use Symfony\Component\Console\Output\OutputInterface;

class Authentication {
    private Files $files;

    private OutputInterface $output;

    public function __construct(OutputInterface $output) {
        $this->output = $output;
        $this->files = new Files($output);
    }

    public function login($username, $token)
    {
        $filename = $this->files->getLoginFilename();

        file_put_contents($filename, json_encode(['username' => $username, 'token' => $token]));
    }

    public function hasAuthentication()
    {
        $authentication = $this->getAuthenticationInformation();
        
        return !empty($authentication) && array_key_exists('username', $authentication) && array_key_exists('token', $authentication);
    }

    public function getAuthenticationInformation()
    {
        $filename = $this->files->getLoginFilename();

        return json_decode(file_get_contents($filename), true);
    }
}