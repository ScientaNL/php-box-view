<?php

namespace BoxViewTest\Exception;

use BoxView\Exception as BoxException;
use GuzzleHttp\Message\Response as GuzzleResponse;

class NotAvailableExceptionTest extends \PHPUnit_Framework_TestCase
{
    public function testRuntimeExceptionCreation()
    {
        $response = new GuzzleResponse(202, ['Retry-After' => '5']);
        $exception = new BoxException\NotAvailableException('fooBar', $response, new \DateTime());

        $this->assertInstanceOf('RuntimeException', $exception);
        $this->assertSame(202, $exception->getCode());
        $this->assertEquals('fooBar', $exception->getMessage());
    }

    public function testExceptionFactory()
    {
        $response = new GuzzleResponse(202, ['Retry-After' => '5']);
        $exception = BoxException\NotAvailableException::create($response, new \DateTime());

        $this->assertInstanceOf('BoxView\Exception\NotAvailableException', $exception);
        $this->assertNotEmpty($exception->getMessage());
        $this->assertSame(202, $exception->getCode());
    }

    public function testSecondsForRetry()
    {
        $response = new GuzzleResponse(202, ['Retry-After' => '5']);
        $exception = new BoxException\NotAvailableException('', $response, new \DateTime());

        $this->assertSame(5, $exception->getSecondsForRetry());
    }

    public function testDateTimeForRetry()
    {
        $responseDateTime = new \DateTime('2014-09-09 00:00:00');
        $response = new GuzzleResponse(202, ['Retry-After' => '15']);
        $exception = new BoxException\NotAvailableException('', $response, $responseDateTime);

        $this->assertEquals((new \DateTime('2014-09-09 00:00:15'))->getTimestamp(), $exception->getDateTimeForRetry()->getTimestamp());
    }
} 
