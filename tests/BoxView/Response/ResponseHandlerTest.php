<?php

namespace BoxViewTest\Exception;

use BoxView\Exception as BoxException;
use BoxView\Response\Entity\AbstractEntity;
use BoxView\Response\ResponseHandler;
use GuzzleHttp\Psr7\Response;

class ResponseHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ResponseHandler
     */
    protected $handler;

    protected function setUp()
    {
        $this->handler = new ResponseHandler();
    }

    /**
     * @expectedException \BoxView\Exception\UnexpectedResponseException
     */
    public function testUnexpectedDocument()
    {
        $response = new Response(202);
        $this->handler->getDocument($response);
    }

    /**
     * @dataProvider jsonDocumentProvider
     */
    public function testGetDocument($jsonData, $isValidDoc)
    {
        $json = \GuzzleHttp\json_encode($jsonData);
        $response = new Response(200, ['Content-Type' => 'application/json'], \GuzzleHttp\Psr7\stream_for($json));
        $entity = $this->handler->getDocument($response);

        foreach ($jsonData as $key => $value) {
            $method = AbstractEntity::camel('get_' . $key);

            $this->assertTrue(
                method_exists($entity, $method),
                "Entity does not have method $method"
            );

            if ($method == 'getCreatedAt') {
                $this->assertSame(strtotime($value), $entity->{$method}()->getTimestamp());
            } else {
                $this->assertSame($value, $entity->{$method}());
            }
        }
    }

    public function testGetDocuments()
    {
        $jsonData = $this->getDummyDocumentData(true);
        $json = \GuzzleHttp\json_encode($jsonData);
        $json = sprintf('{ "document_collection": { "total_count": %d, "entries": %s } }', count($jsonData), $json);
        $response = new Response(200, ['Content-Type' => 'application/json'], \GuzzleHttp\Psr7\stream_for($json));
        $entities = $this->handler->getDocuments($response);

        foreach ($jsonData as $value) {
            $id = $value['id'];
            $this->assertTrue($entities->containsKey($id));
            $this->assertSame($id, $entities->get($id)->getId());
        }
    }

    /**
     * @dataProvider jsonDocumentProvider
     */
    public function testGetDocumentForCreation($jsonData, $isValidDoc)
    {
        $json = \GuzzleHttp\json_encode($jsonData);
        $response = new Response(200, ['Content-Type' => 'application/json'], \GuzzleHttp\Psr7\stream_for($json));
        $entity = $this->handler->getDocumentForCreation($response);

        foreach ($jsonData as $key => $value) {
            $method = AbstractEntity::camel('get_' . $key);

            $this->assertTrue(
                method_exists($entity, $method),
                "Entity does not have method $method"
            );

            if ($method == 'getCreatedAt') {
                $this->assertSame(strtotime($value), $entity->{$method}()->getTimestamp());
            } else {
                $this->assertSame($value, $entity->{$method}());
            }
        }
    }

    public function testDocumentDeleted()
    {
        $this->assertTrue($this->handler->documentDeleted(new Response(204)));
        $this->assertTrue($this->handler->documentDeleted(new Response('204')));
        $this->assertFalse($this->handler->documentDeleted(new Response(200)));
    }

    /**
     * @expectedException \BoxView\Exception\NotAvailableException
     */
    public function testGetBodyStreamNotYet()
    {
        $this->handler->getBodyStream(new Response(202));
    }

    public function testGetBodyStream()
    {
        $resource = fopen('data://text/plain;base64,SSBsb3ZlIFBIUAo=', 'r');
        $response = new Response(200, ['Content-Type' => 'text/plain'], \GuzzleHttp\Psr7\stream_for($resource));
        $stream = $this->handler->getBodyStream($response);

        $this->assertInstanceOf('GuzzleHttp\Psr7\Stream', $stream);
        $this->assertEquals('I love PHP', trim($stream->__toString()));


        $temp_file = tempnam(sys_get_temp_dir(), 'import Foobar');
        $resource = fopen($temp_file, 'w+');
        fwrite($resource, 'data');
        $response = new Response(200, ['Content-Type' => 'text/x-python'], \GuzzleHttp\Psr7\stream_for($resource));
        $stream = $this->handler->getBodyStream($response);

        $this->assertInstanceOf('\BoxView\Stream\FileStream', $stream);
        $this->assertEquals('text/x-python', $stream->getServerMimeType());
        $this->assertFalse($stream->eof());
        $stream->read(4);
        $this->assertTrue($stream->eof());
        $stream->close();


        $response = new Response(200, ['Content-Type' => 'application/json'], \GuzzleHttp\Psr7\stream_for('{ "foo" : "bar" }'));
        $stream = $this->handler->getBodyStream($response);

        $this->assertInstanceOf('\BoxView\Stream\TempStream', $stream);
        $this->assertEquals('application/json', $stream->getServerMimeType());
        $this->assertEquals('{ "foo" : "bar" }', $stream->__toString());
    }

    /**
     * @expectedException \BoxView\Exception\UnexpectedResponseException
     */
    public function testGetNoSession()
    {
        $response = new Response(200, ['Content-Type' => 'application/json']);
        $this->handler->getSession($response);
    }

    /**
     * @expectedException \BoxView\Exception\NotAvailableException
     */
    public function testGetNotAvailableSession()
    {
        $response = new Response(202, ['Content-Type' => 'application/json']);
        $this->handler->getSession($response);
    }

    /**
     * @dataProvider jsonSessionProvider
     */
    public function testGetSession($jsonData)
    {
        $json = \GuzzleHttp\json_encode($jsonData);
        $response = new Response(201, ['Content-Type' => 'application/json'], \GuzzleHttp\Psr7\stream_for($json));
        $entity = $this->handler->getSession($response);

        foreach ($jsonData as $key => $value) {
            $method = AbstractEntity::camel('get_' . $key);

            $this->assertTrue(
                method_exists($entity, $method),
                "Entity does not have method $method"
            );

            if ($method == 'getExpiresAt') {
                $this->assertSame(strtotime($value), $entity->{$method}()->getTimestamp());
            } else {
                $this->assertSame($value, $entity->{$method}());
            }
        }
    }

    public function testGetContentType()
    {
        $response = new Response(200, ['Content-Type' => 'application/json']);
        $headerLine = $this->handler->getContentType($response);
        $this->assertSame('application/json', $headerLine);
    }

    public function jsonDocumentProvider()
    {
        $docs = [];

        foreach ($this->getDummyDocumentData(true) as $doc)
            $docs[] = [$doc, true];
        foreach ($this->getDummyDocumentData(false) as $doc)
            $docs[] = [$doc, false];

        return $docs;
    }

    public function getDummyDocumentData($isValid = null)
    {
        $validDocuments = [
            [
                "type" => "document",
                "id" => "foo",
                "status" => "done",
                "name" => "bar",
                "created_at" => "2016-08-30T00:17:37Z"
            ],
            [
                "type" => "document",
                "id" => "bar",
                "status" => "processing",
                "name" => "baz",
                "created_at" => "2016-09-30T00:17:37Z"
            ],
            [
                "type" => "document",
                "id" => "Barbaz",
                "status" => "queued",
                "name" => "Foobar",
                "created_at" => "2016-02-29T00:17:37Z"
            ],
            [
                "type" => "document",
                "id" => "baz",
                "status" => "error"
            ]
        ];

        $invalidDocuments = [
            [
                "type" => "bar",
                "id" => "baz",
                "status" => "error"
            ],
            [
                "type" => "document",
                "status" => "error"
            ]
        ];

        if ($isValid === null)
            return array_merge($validDocuments, $invalidDocuments);

        return (bool)$isValid ? $validDocuments : $invalidDocuments;
    }

    public function jsonSessionProvider()
    {
        return [
            [[
                "type" => "session",
                "id" => "foo",
                "expires_at" => "3915-10-29T01:31:48.677Z",
                "urls" => [
                    "view" => "https://view-api.box.com/1/sessions/foo/view",
                    "assets" => "https://view-api.box.com/1/sessions/foo/assets/",
                    "realtime" => "https://view-api.box.com/sse/foo"
                ],
            ]],
            [[
                "type" => "session",
                "id" => "foo",
                "expires_at" => "2016-10-29T01:31:48.677Z",
                "urls" => [],
            ]],
            [[
                "type" => "session",
                "id" => "foo",
                "expires_at" => "1915-10-29T01:31:48.677Z",
                "urls" => [
                    "view" => "https://view-api.box.com/1/sessions/foo/view",
                    "assets" => "https://view-api.box.com/1/sessions/foo/assets/",
                    "realtime" => "https://view-api.box.com/sse/foo",
                    "bar" => "baz"
                ],
            ]],
            [[
                "type" => "session",
                "id" => "foo"
            ]]
        ];
    }
}
