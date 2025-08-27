<?php
namespace App\Helper;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NominatimService
{
    private HttpClientInterface $http;

    public function __construct(HttpClientInterface $http)
    {
        $this->http = $http;
    }

    public function search(string $query): array
    {
        $response = $this->http->request('GET', 'https://nominatim.openstreetmap.org/search', [
            'query' => [
                'q' => $query,
                'format' => 'json',
                'limit' => 5,
                'countrycodes' => 'FR',
                'addressdetails' => 1, /// ----------mama mia
            ],
            'headers' => [
                'User-Agent' => 'MySymfonyAppDev/1.0 (dev@localhost)',
            ],
        ]);

        $results = $response->toArray();

        return $results;
    }
}
