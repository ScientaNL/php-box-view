<?php

namespace BoxView\Exception;

use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * Class RequestException
 * @package BoxView\Exception
 */
class RequestException extends GuzzleRequestException
{
    /** @var GuzzleRequestException */
    protected $guzzleException;

    /**
     * @param GuzzleRequestException $exception
     */
    public function __construct(GuzzleRequestException $exception)
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
     * @return GuzzleRequestException
     */
    public function getGuzzleException()
    {
        return $this->guzzleException;
    }
} 
