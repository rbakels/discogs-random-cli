<?php
namespace App\Lib;

class Discogs
{
    private $username;

    private $token;

    public function __construct($username, $token)
    {
        $this->username = $username;
        $this->token = $token;
    }

    public function getClient()
    {
        $client = \Discogs\ClientFactory::factory([
            'defaults' => [
                'headers' => ['User-Agent' => 'discogs-randomizer/0.1 +https://github.com/rbakels/discogs-random-cli'],
            ]
        ]);

        $client->getHttpClient()->getEmitter()->attach(new \Discogs\Subscriber\ThrottleSubscriber());

        return \Discogs\ClientFactory::factory([
            'defaults' => [
                'query' => [
                    'token' => $this->token,
                ],
            ]
        ]);
    }

    public function retrieveAllItems()
    {
        $client = $this->getClient();

        $items = $client->getCollectionItemsByFolder([
            'username' => $this->username,
            'folder_id' => 0,
            'page' => 1,
            'per_page' => 100,
        ]);

        $page = $items['pagination']['page'];
        $maxPages = $items['pagination']['pages'];
        $releases = [];

        for ($page = 1; $page <= $maxPages; $page++) {
            $items = $client->getCollectionItemsByFolder([
                'username' => $this->username,
                'folder_id' => 0,
                'page' => $page,
                'per_page' => 100,
            ]);

            foreach ($items['releases'] as $key => $item) {
                $releases[$item['id']] = $item;
            }

            $maxPages = $items['pagination']['pages'];
        }

        return $releases;
    }

    public function retrieveItemById($id)
    {
        $client = $this->getClient();

        return $client->getRelease([
            'username' => $this->username,
            'id' => $id
        ]);
    }
}
