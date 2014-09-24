<?php

namespace BoxView\Exception;

use GuzzleHttp\Exception\ClientException;

/**
 * Class RequestException
 * @package BoxView\Exception
 */
class RequestException extends ClientException
{
    /** @var ClientException */
    protected $guzzleException;

    /**
     * @param ClientException $exception
     */
    public function __construct(ClientException $exception)
    {
        $this->guzzleException = $exception;
        parent::__construct(
            $exception->getMessage(),
            $exception->getRequest(),
            $exception->getResponse(),
            $exception
        );
    }

    /**
     * @return ClientException
     */
    public function getGuzzleException()
    {
        return $this->guzzleException;
    }
} 
