<?php

namespace BoxViewTest\Exception;

use BoxView\Exception as BoxException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

class RequestExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testClientExceptionCreation()
    {
        $request = new Request('GET', 'http://test.com/test');
        $response = new Response(200);
        $clientException = new ClientException('foo', $request, $response);
        $exception = new BoxException\RequestException($clientException);

        $this->assertSame('foo', $exception->getMessage());
        $this->assertSame($request, $exception->getRequest());
        $this->assertSame($response, $exception->getResponse());
        $this->assertSame($clientException, $exception->getPrevious());
    }
} 
