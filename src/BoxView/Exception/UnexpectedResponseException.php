<?php

namespace BoxView\Exception;

use Psr\Http\Message\ResponseInterface;

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
     * @param ResponseInterface $response
     */
    public function __construct($message = '', ResponseInterface $response)
    {
        parent::__construct($message, intval($response->getStatusCode()));
        $this->response = $response;
    }

    /**
     * Factory method to create a new exception with a normalized error message
     *
     * @param ResponseInterface $response Response received
     *
     * @return self
     */
    public static function create(ResponseInterface $response)
    {
        $label = 'Unsuccessful response';
        $message = $label . ' [status code] ' . $response->getStatusCode()
            . ' [reason phrase] ' . $response->getReasonPhrase();

        return new self($message, $response);
    }

}
