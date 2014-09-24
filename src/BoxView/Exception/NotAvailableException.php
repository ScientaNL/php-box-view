<?php

namespace BoxView\Exception;

use GuzzleHttp\Message\Response;

/**
 * Class UnexpectedResponseException
 * @package BoxView\Exception
 */
class NotAvailableException extends \RuntimeException
{
    /** @var Response */
    private $response;

    /** @var int */
    private $retrySeconds;

    /** @var \DateTime */
    private $responseDateTime;

    /**
     * @param string $message
     * @param Response $response
     * @param \DateTime $responseDateTime
     */
    public function __construct($message, Response $response, \DateTime $responseDateTime)
    {
        parent::__construct($message, intval($response->getStatusCode()));
        $this->response = $response;
        $this->responseDateTime = $responseDateTime;
        $this->retrySeconds = $response->hasHeader('Retry-After') ? intval($response->getHeader('Retry-After')) : null;
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param Response $response Response received
     * @param \DateTime $responseDateTime
     *
     * @return self
     */
    public static function create(Response $response, \DateTime $responseDateTime)
    {
        $label = 'Unsuccessful response';
        $message = $label . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase();

        return new self($message, $response, $responseDateTime);
    }

    /**
     * @return int|null
     */
    public function getSecondsForRetry()
    {
        return $this->retrySeconds;
    }

    /**
     * @return \DateTime|null
     */
    public function getDateTimeForRetry()
    {
        if ($this->retrySeconds === null) {
            return null;
        }
        return \DateTime::createFromFormat('U', $this->responseDateTime->getTimestamp())
            ->modify('+'.$this->retrySeconds.' seconds');
    }
}
