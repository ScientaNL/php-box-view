<?php

namespace BoxView\Client;

use GuzzleHttp\Client as GuzzleClient;

/**
 * Class ClientFactory
 * @package BoxView\Client
 */
class ClientFactory
{
    const API_VERSION = 1;
    const BASE_API_URL = 'https://view-api.box.com';
    const BASE_UPLOAD_URL = 'https://upload.view-api.box.com';

    /** @var string */
    protected $baseApiUrl;

    /** @var string */
    protected $baseUploadUrl;

    /**
     * @param string|null $baseApiUrl
     * @param string|null $baseUploadUrl
     */
    public function __construct($baseApiUrl = null, $baseUploadUrl = null)
    {
        $this->baseApiUrl = $baseApiUrl ?: self::BASE_API_URL;
        $this->baseUploadUrl = $baseUploadUrl ?: self::BASE_UPLOAD_URL;
    }

    /**
     * @param $apiKey
     * @param array $options
     * @return GuzzleClient
     */
    public function createApiClient($apiKey, $options = [])
    {
        $config = [
            'base_uri' => $this->baseApiUrl . sprintf('/%s/', static::API_VERSION),
            'headers' => [
                'Authorization' => 'Token ' . $apiKey,
                'Content-Type' => 'application/json'
            ]
        ];
        return new GuzzleClient($config + $options);
    }

    /**
     * @param $apiKey
     * @param array $options
     * @return GuzzleClient
     */
    public function createUploadClient($apiKey, $options = [])
    {
        $config = [
            'base_uri' => $this->baseUploadUrl . sprintf('/%s/', static::API_VERSION),
            'headers' => [
                'Authorization' => 'Token ' . $apiKey,
                'Content-Type' => 'multipart/form-data'
            ]
        ];
        return new GuzzleClient($config + $options);
    }
} 
