<?php

namespace App\Services;

use Dotenv\Dotenv;
use Square\SquareClient;
use Square\Environment;

class LocationInfo
{
    private $currency;
    private $country;
    private $location_id;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(base_path());
        $dotenv->load();

        $access_token = env('SQUARE_ACCESS_TOKEN');
        $square_client = new SquareClient([
            'accessToken' => $access_token,
            'environment' => env('ENVIRONMENT'),
        ]);

        $location_api = $square_client->getLocationsApi();
        $response = $location_api->retrieveLocation(env('SQUARE_LOCATION_ID'));

        if ($response->isSuccess()) {
            $location = $response->getResult()->getLocation();
            $this->location_id = $location->getId();
            $this->currency = $location->getCurrency();
            $this->country = $location->getCountry();
        } else {
            // Handle errors
            throw new \Exception('Unable to retrieve location from Square API.');
        }
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getId()
    {
        return $this->location_id;
    }
}
