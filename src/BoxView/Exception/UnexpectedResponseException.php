<?php

namespace BoxView\Exception;

use GuzzleHttp\Message\Response;

/**
 * Class UnexpectedResponseException
 * @package BoxView\Exception
 */
class UnexpectedResponseException extends \RuntimeException
{
    /** @var Response */
    private $response;

    /**
     * @param string $message
     * @param Response $response
     */
    public function __construct($message = '', Response $response)
    {
        parent::__construct($message, intval($response->getStatusCode()));
        $this->response = $response;
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param Response $response Response received
     *
     * @return self
     */
    public static function create(Response $response)
    {
        $label = 'Unsuccessful response';
        $message = $label . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase();

        return new self($message, $response);
    }

}
