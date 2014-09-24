<?php

namespace BoxView\Client;

use GuzzleHttp\Client as GuzzleClient;

/**
 * Class ClientManager
 * @package BoxView\Client
 */
class ClientManager
{
    /** @var string */
    protected $apiKey;

    /** @var GuzzleClient */
    protected $apiClient;

    /** @var GuzzleClient */
    protected $uploadClient;

    /** @var ClientFactory */
    protected $clientFactory;

    /**
     * @param string $apiKey
     * @param ClientFactory $clientFactory
     */
    public function __construct($apiKey, ClientFactory $clientFactory = null)
    {
        $this->apiKey = $apiKey;
        $this->clientFactory = $clientFactory ?: new ClientFactory();
    }

    /**
     * @return GuzzleClient
     */
    public function getApiClient()
    {
        if ($this->apiClient === null) {
            $this->apiClient = $this->clientFactory->createApiClient($this->apiKey);
        }
        return $this->apiClient;
    }

    /**
     * @param GuzzleClient $client
     * @return $this
     */
    public function setApiClient(GuzzleClient $client)
    {
        $this->apiClient = $client;
        return $this;
    }

    /**
     * @return GuzzleClient
     */
    public function getUploadClient()
    {
        if ($this->uploadClient === null) {
            $this->uploadClient = $this->clientFactory->createUploadClient($this->apiKey);
        }
        return $this->uploadClient;
    }

    /**
     * @param GuzzleClient $client
     * @return $this
     */
    public function setUploadClient(GuzzleClient $client)
    {
        $this->uploadClient = $client;
        return $this;
    }
} 
