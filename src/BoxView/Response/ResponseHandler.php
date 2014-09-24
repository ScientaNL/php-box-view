<?php

namespace BoxView\Response;

use Doctrine\Common\Collections\ArrayCollection;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream as GuzzleStream;
use BoxView\Exception\UnexpectedResponseException;
use BoxView\Exception\NotAvailableException;

/**
 * Class ResponseHandler
 * @package BoxView\Response
 */
class ResponseHandler
{
    /**
     * @param Response $response
     * @return ArrayCollection
     */
    public function getDocuments(Response $response)
    {
        if ($response->getStatusCode() != '200') {
            $this->handleUnexpectedResponse($response);
        }

        $documentData = $response->json()['document_collection'];
        $jsonDocuments = isset($documentData['entries']) ? $documentData['entries'] : [];

        $documents = new ArrayCollection();
        foreach ($jsonDocuments as $jsonDoc) {
            $document = new Entity\Document($jsonDoc);
            $documents->set($document->getId(), $document);
        }

        return $documents;
    }

    /**
     * @param Response $response
     * @return Entity\Document
     */
    public function getDocument(Response $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '200') {
            $this->handleUnexpectedResponse($response);
        }

        return new Entity\Document($response->json());
    }

    /**
     * @param Response $response
     * @return Entity\Document
     */
    public function getDocumentForCreation(Response $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '200' && $statusCode != '201' && $statusCode != '202') {
            $this->handleUnexpectedResponse($response);
        }

        return new Entity\Document($response->json());
    }

    /**
     * @param Response $response
     * @return bool
     */
    public function documentDeleted(Response $response)
    {
        return $response->getStatusCode() == '204';
    }

    /**
     * @param Response $response
     * @return \GuzzleHttp\Stream\StreamInterface
     * @throws \BoxView\Exception\NotAvailableException
     */
    public function getDocumentFileStream(Response $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '200' && $statusCode != '202') {
            $this->handleUnexpectedResponse($response);
        }

        if ($statusCode == '202') {
            throw NotAvailableException::create($response, new \DateTime());
        }

        $serverMimeType = $response->hasHeader('Content-Type') ? $response->getHeader('Content-Type') : null;

        /** @var GuzzleStream $stream */
        $stream = $response->getBody();
        switch (strtolower($stream->getMetadata('wrapper_type')))
        {
            case 'php':
                $responseStream = new Stream\TempFileStream($stream, $serverMimeType);
                break;
            case 'plainfile':
                $responseStream = new Stream\FileStream($stream, $serverMimeType);
                break;
            default:
                $responseStream = $stream;
        }

        return $responseStream;
    }

    /**
     * @param Response $response
     * @return Entity\Document
     * @throws \BoxView\Exception\NotAvailableException
     */
    public function getSession(Response $response)
    {
        $statusCode = $response->getStatusCode();
        if ($statusCode != '201' && $statusCode != '202') {
            $this->handleUnexpectedResponse($response);
        }

        if ($statusCode == '202') {
            throw NotAvailableException::create($response, new \DateTime());
        }

        return new Entity\Session($response->json());
    }

    /**
     * @param Response $response
     * @return null|string
     */
    public function getContentType(Response $response)
    {
        return $response->hasHeader('Content-Type') ? $response->getHeader('Content-Type') : null;
    }

    /**
     * @param Response $response
     * @throws \BoxView\Exception\UnexpectedResponseException
     */
    protected function handleUnexpectedResponse(Response $response)
    {
        throw UnexpectedResponseException::create($response);
    }
} 
