<?php

namespace BoxViewTest\Client;

use BoxView\Client as BoxClient;
use GuzzleHttp\Client as GuzzleClient;

class ClientManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testDefaultFactoryForEmptyManager()
    {
        $manager = new BoxClient\ClientManager('');
        $client = $manager->getApiClient();
        $this->assertInstanceOf('GuzzleHttp\Client', $client);
    }

    public function testClientCreationForApiClient()
    {
        $apiKey = 'fooBar';
        $factory = $this->getClientFactory();

        $factory->expects($this->once())
            ->method('createApiClient')
            ->with($this->equalTo($apiKey));

        $manager = new BoxClient\ClientManager($apiKey, $factory);
        $manager->getApiClient();
    }

    public function testClientCreationForUploadClient()
    {
        $apiKey = 'fooBar';
        $factory = $this->getClientFactory();

        $factory->expects($this->once())
            ->method('createUploadClient')
            ->with($this->equalTo($apiKey));

        $manager = new BoxClient\ClientManager($apiKey, $factory);
        $manager->getUploadClient();
    }

    public function testSetGetApiClient()
    {
        $factory = $this->getClientFactory();
        $factory->expects($this->never())
            ->method($this->anything());

        $clientStub = new GuzzleClient();

        $manager = new BoxClient\ClientManager('', $factory);
        $manager->setApiClient($clientStub);
        $this->assertSame($clientStub, $manager->getApiClient());
    }

    public function testSetGetUploadClient()
    {
        $factory = $this->getClientFactory();
        $factory->expects($this->never())
            ->method($this->anything());

        $clientStub = new GuzzleClient();

        $manager = new BoxClient\ClientManager('', $factory);
        $manager->setUploadClient($clientStub);
        $this->assertSame($clientStub, $manager->getUploadClient());
    }

    private function getClientFactory()
    {
        $factory = $this->getMockBuilder('BoxView\Client\ClientFactory')
            ->setMethods(['createApiClient', 'createUploadClient'])
            ->getMock();

        return $factory;
    }
} 
