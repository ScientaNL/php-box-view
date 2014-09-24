<?php

namespace BoxViewTest\Client;

use BoxView\Client as BoxClient;

class ClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function testClassForApiClient()
    {
        $factory = new BoxClient\ClientFactory();
        $client = $factory->createApiClient('');
        $this->assertInstanceOf('GuzzleHttp\Client', $client);
    }

    public function testClassForUploadClient()
    {
        $factory = new BoxClient\ClientFactory();
        $client = $factory->createApiClient('');
        $this->assertInstanceOf('GuzzleHttp\Client', $client);
    }

    public function testDefaultBaseUrlForApiClient()
    {
        $factory = new BoxClient\ClientFactory();
        $client = $factory->createApiClient('fooBar');
        $this->assertEquals('https://view-api.box.com/1/', $client->getBaseUrl());
    }

    public function testDefaultBaseUrlForUploadClient()
    {
        $factory = new BoxClient\ClientFactory();
        $client = $factory->createUploadClient('fooBar');
        $this->assertEquals('https://upload.view-api.box.com/1/', $client->getBaseUrl());
    }

    public function testBaseUrlForApiClient()
    {
        $factory = new BoxClient\ClientFactory('foo://bar');
        $client = $factory->createApiClient('');
        $this->assertEquals('foo://bar/' . BoxClient\ClientFactory::API_VERSION . '/', $client->getBaseUrl());
    }

    public function testBaseUrlForUploadClient()
    {
        $factory = new BoxClient\ClientFactory(null, 'bar://foo');
        $client = $factory->createUploadClient('');
        $this->assertEquals('bar://foo/' . BoxClient\ClientFactory::API_VERSION . '/', $client->getBaseUrl());
    }

    public function testDefaultHeadersForApiClient()
    {
        $apiKey = 'fooBar';
        $factory = new BoxClient\ClientFactory();
        $headers = $factory->createApiClient($apiKey)->getDefaultOption('headers');
        $this->assertEquals($headers['Authorization'], 'Token ' . $apiKey);
        $this->assertEquals($headers['Content-Type'], 'application/json');
    }

    public function testDefaultHeadersForUploadClient()
    {
        $apiKey = 'fooBar';
        $factory = new BoxClient\ClientFactory();
        $headers = $factory->createUploadClient($apiKey)->getDefaultOption('headers');
        $this->assertEquals($headers['Authorization'], 'Token ' . $apiKey);
        $this->assertEquals($headers['Content-Type'], 'multipart/form-data');
    }
} 
