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
     * @return GuzzleClient
     */
    public function createApiClient($apiKey)
    {
        return new GuzzleClient([
            'base_url' => [$this->baseApiUrl . '/{version}/', ['version' => static::API_VERSION]],
            'defaults' => [
                'headers' => [
                    'Authorization' => 'Token ' . $apiKey,
                    'Content-Type' => 'application/json'
                ],
            ]
        ]);
    }

    /**
     * @param $apiKey
     * @return GuzzleClient
     */
    public function createUploadClient($apiKey)
    {
        return new GuzzleClient([
            'base_url' => [$this->baseUploadUrl . '/{version}/', ['version' => static::API_VERSION]],
            'defaults' => [
                'headers' => [
                    'Authorization' => 'Token ' . $apiKey,
                    'Content-Type' => 'multipart/form-data'
                ],
            ]
        ]);
    }
} 
