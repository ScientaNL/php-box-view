<?php

namespace BoxView\Response;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use BoxView\Exception\UnexpectedResponseException;
use BoxView\Exception\NotAvailableException;
use BoxView\Stream\FileStream;
use BoxView\Stream\TempStream;

/**
 * Class ResponseHandler
 * @package BoxView\Response
 */
class ResponseHandler
{
    /**
     * @param ResponseInterface $response
     * @return ArrayCollection
     */
    public function getDocuments(ResponseInterface $response)
    {
        if ($response->getStatusCode() != '200') {
            $this->handleUnexpectedResponse($response);
        }

        $json = \GuzzleHttp\json_decode($response->getBody(), true);
        $documentData = $json['document_collection'];
        $jsonDocuments = isset($documentData['entries']) ? $documentData['entries'] : [];

        $documents = new ArrayCollection();
        foreach ($jsonDocuments as $jsonDoc) {
            $document = new Entity\Document($jsonDoc);
            $documents->set($document->getId(), $document);
        }

        return $documents;
    }

    /**
     * @param ResponseInterface $response
     * @return Entity\Document
     */
    public function getDocument(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '200') {
            $this->handleUnexpectedResponse($response);
        }

        return new Entity\Document(\GuzzleHttp\json_decode($response->getBody(), true));
    }

    /**
     * @param ResponseInterface $response
     * @return Entity\Document
     */
    public function getDocumentForCreation(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '200' && $statusCode != '201' && $statusCode != '202') {
            $this->handleUnexpectedResponse($response);
        }

        return new Entity\Document(\GuzzleHttp\json_decode($response->getBody(), true));
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     */
    public function documentDeleted(ResponseInterface $response)
    {
        return $response->getStatusCode() == '204';
    }

    /**
     * @param ResponseInterface $response
     * @return StreamInterface
     * @throws \BoxView\Exception\NotAvailableException
     */
    public function getBodyStream(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '200' && $statusCode != '202') {
            $this->handleUnexpectedResponse($response);
        }

        if ($statusCode == '202') {
            throw NotAvailableException::create($response, new \DateTime());
        }

        $serverMimeType = $response->hasHeader('Content-Type') ? $response->getHeaderLine('Content-Type') : null;

        $stream = $response->getBody();
        switch (strtolower($stream->getMetadata('wrapper_type')))
        {
            case 'php':
                $responseStream = new TempStream($stream, $serverMimeType);
                break;
            case 'plainfile':
                $responseStream = new FileStream($stream, $serverMimeType);
                break;
            default:
                $responseStream = $stream;
        }

        return $responseStream;
    }

    /**
     * @param ResponseInterface $response
     * @return Entity\Document
     * @throws \BoxView\Exception\NotAvailableException
     */
    public function getSession(ResponseInterface $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '201' && $statusCode != '202') {
            $this->handleUnexpectedResponse($response);
        }

        if ($statusCode == '202') {
            throw NotAvailableException::create($response, new \DateTime());
        }

        return new Entity\Session(\GuzzleHttp\json_decode($response->getBody(), true));
    }

    /**
     * @param ResponseInterface $response
     * @return int
     */
    public function getStatusCode(ResponseInterface $response)
    {
        return $response->getStatusCode();
    }

    /**
     * @param ResponseInterface $response
     * @return null|string
     */
    public function getContentType(ResponseInterface $response)
    {
        return $response->hasHeader('Content-Type') ? $response->getHeaderLine('Content-Type') : null;
    }

    /**
     * @param ResponseInterface $response
     * @throws \BoxView\Exception\UnexpectedResponseException
     */
    protected function handleUnexpectedResponse(ResponseInterface $response)
    {
        throw UnexpectedResponseException::create($response);
    }
} 
