<?php
namespace App\Lib;

use Symfony\Component\Console\Output\OutputInterface;
use App\Exception\AuthenticationMissingException;

class Application {
    private Discogs $discogs;

    private Files $files;

    private Authentication $authentication;

    private OutputInterface $output;
    
    public function __construct(OutputInterface $output) {
        $this->output = $output;

        $this->authentication = new Authentication($output);
        $this->files = new Files($output);

        if (!$this->authentication->hasAuthentication()) {
            throw new AuthenticationMissingException("No valid authentication found");
        }

        $authenticationInformation = $this->authentication->getAuthenticationInformation();
        $this->discogs = new Discogs($authenticationInformation['username'], $authenticationInformation['token']);
    }

    public function getBasicInformationOfRelease($id)
    {
        $releases = $this->getAllReleases();
        $releaseFromLocalStorage = $releases[$id];

        $result = [];

        $releaseInformation = $this->discogs->retrieveItemById($id);

        $result['id'] = $id;
        $result['artist'] = $releaseInformation['artists_sort'];
        $result['title'] = $releaseInformation['title'];
        $result['released'] = $releaseInformation['released_formatted'];
        $result['genres'] = join(', ', $releaseInformation['styles']);
        $result['images']['source']['thumbnail'] = $releaseFromLocalStorage['basic_information']['thumb'];
        $result['images']['source']['cover'] = $releaseFromLocalStorage['basic_information']['cover_image'];
        
        $result['images']['local']['directory'] = $this->files->getStorageImageDirectory() . "/" . sha1($id);
        $this->files->mkdirIfNotExists($result['images']['local']['directory']);

        $result['images']['local']['thumbnail'] = $result['images']['local']['directory'] . "/thumb.jpeg";
        $this->downloadFile($result['images']['source']['thumbnail'], $result['images']['local']['thumbnail']);
        
        $result['images']['local']['cover'] = $result['images']['local']['directory'] . "/cover.jpeg";
        $this->downloadFile($result['images']['source']['cover'], $result['images']['local']['cover']);

        return $result;
    }

    public function registerSuggestedReleases($releaseId)
    {
        $suggestedReleases = $this->getAllSuggestedReleases();

        $suggestedReleases[] = $releaseId;

        $filename = $this->files->getSuggestedReleasesFilename();
        file_put_contents($filename, json_encode($suggestedReleases));
    }

    public function getAllSuggestedReleases()
    {
        $filename = $this->files->getSuggestedReleasesFilename();

        if (!file_exists($filename)) {
            return [];
        }

        return json_decode(file_get_contents($filename), true);
    }

    public function getAllReleases()
    {
        $releases = [];

        if (!$this->checkIfReleasesExistsInLocalStorage()) {
            $this->output->writeln("Retrieving items from collections.", OutputInterface::VERBOSITY_DEBUG);

            $releases = $this->discogs->retrieveAllItems($this->username, $this->token);
    
            $this->output->writeln("Saving items from collections to local storage.", OutputInterface::VERBOSITY_DEBUG);

            $this->application->saveReleases($releases);
        } else {
            $this->output->writeln("Retrieving items from local storage.", OutputInterface::VERBOSITY_DEBUG);

            $releases = $this->getReleasesFromLocalStorage();
        }

        return $releases;
    }

    private function downloadFile($url, $targetFilename) {
        $client = $this->discogs->getClient();
        $content = $client->getHttpClient()->get($url)->getBody()->getContents();
        
        file_put_contents($targetFilename, $content);
    }

    private function getReleasesFromLocalStorage()
    {
        $storageDirectory = $this->files->getStorageDirectory();
        $filename = $storageDirectory . "/releases.json";

        return json_decode(file_get_contents($filename), true);
    }  

    private function saveReleases($releases)
    {
        $filename = $this->files->getReleasesFilename();

        file_put_contents($filename, json_encode($releases));
    }   

    private function checkIfGivenReleasesExistsInLocalStorage()
    {
        $filename = $this->files->getReleasesFilename();

        return file_exists($filename);
    }

    private function checkIfReleasesExistsInLocalStorage()
    {
        $filename = $this->files->getReleasesFilename();

        return file_exists($filename);
    }
}