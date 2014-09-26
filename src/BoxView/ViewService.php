<?php

namespace BoxView;

use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Post\PostFile;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use BoxView\Client\ClientManager as ClientManager;
use BoxView\Response as BoxResponse;
use BoxView\Exception as BoxException;

/**
 * Class ViewService
 * @package BoxView
 */
class ViewService
{
    /** @var ClientManager */
    protected $clientManager;

    /** @var ClientManager */
    protected $responseHandler;

    /**
     * @param ClientManager $clientManager
     * @param Response\ResponseHandler $responseHandler
     */
    public function __construct(ClientManager $clientManager, BoxResponse\ResponseHandler $responseHandler = null)
    {
        $this->clientManager = $clientManager;
        $this->responseHandler = $responseHandler ?: new BoxResponse\ResponseHandler();
    }

    /**
     * @param int $limit
     * @param \DateTime $createdBefore
     * @param \DateTime $createdAfter
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getDocuments($limit = 10, \DateTime $createdBefore = null, \DateTime $createdAfter = null)
    {
        $queryData = [
            'limit' => $limit,
            'created_before' => $createdBefore ? $createdBefore->getTimestamp() : null,
            'created_after' => $createdAfter ? $createdBefore->getTimestamp() : null,
        ];

        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('GET', 'documents', ['query' => $queryData]);

        return $this->responseHandler->getDocuments($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @return Response\Entity\Document
     */
    public function getDocument($documentId)
    {
        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('GET', ['documents/{id}', ['id' => $documentId]]);

        return $this->responseHandler->getDocument($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @return bool
     */
    public function readyToView($documentId)
    {
        $document = $this->getDocument($documentId);
        return strtolower($document->getStatus()) === 'done';
    }

    /**
     * @param $documentId
     * @param $name
     * @return Response\Entity\Document
     */
    public function updateDocument($documentId, $name)
    {
        $putData = [ 'name' => $name ];

        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('PUT', ['documents/{id}', ['id' => $documentId]], ['json' => $putData]);

        return $this->responseHandler->getDocument($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @return bool
     */
    public function deleteDocument($documentId)
    {
        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('DELETE', ['documents/{id}', ['id' => $documentId]]);
        return $this->responseHandler->documentDeleted($this->sendRequest($client, $request));
    }

    /**
     * Proxy method for creating a document from a file or url
     *
     * @param $file
     * @param string $name
     * @param bool $nonSvg
     * @param array $thumbnails
     * @return Response\Entity\Document
     */
    public function createDocument($file, $name = '', $nonSvg = false, array $thumbnails = [])
    {
        if (filter_var($file, FILTER_VALIDATE_URL)) {
            return $this->createDocumentFromUrl($file, $name, $nonSvg, $thumbnails);
        }

        return $this->createDocumentFromFile($file, $name, $nonSvg, $thumbnails);
    }

    /**
     * @param $file
     * @param string $name
     * @param bool $nonSvg
     * @param array $thumbnails
     * @param null $fileName
     * @param array $fileHeaders
     * @return Response\Entity\Document
     */
    public function createDocumentFromFile($file, $name = '', $nonSvg = false, array $thumbnails = [], $fileName = null, $fileHeaders = [])
    {
        if ($file instanceof \SplFileInfo) {
            $file = $file->getPathname();
        }
        if (!is_readable($file)) {
            throw new \InvalidArgumentException(
                "The given file must be a path to, or an \\SplFileInfo instance of a readable file"
            );
        }

        $client = $this->clientManager->getUploadClient();
        $request = $client->createRequest('POST', 'documents');

        /** @var \GuzzleHttp\Post\PostBody $postBody */
        $postBody = $request->getBody();
        $postBody->addFile(new PostFile('file', fopen($file, 'r'), $fileName, $fileHeaders));
        $postBody->setField('non_svg', $nonSvg ? 'true' : 'false');
        $postBody->setField('name', $name);
        $postBody->setField('thumbnails', implode(',', $thumbnails));

        return $this->responseHandler->getDocumentForCreation($this->sendRequest($client, $request));
    }

    /**
     * @param $fileUrl
     * @param string $name
     * @param bool $nonSvg
     * @param array $thumbnails
     * @return Response\Entity\Document
     */
    public function createDocumentFromUrl($fileUrl, $name = '', $nonSvg = false, array $thumbnails = [])
    {
        $postData = [
            'url' => $fileUrl,
            'non_svg' => $nonSvg ? 'true' : 'false',
            'name' => $name,
            'thumbnails' => implode(',', $thumbnails)
        ];

        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('POST', 'documents', ['json' => $postData]);

        return $this->responseHandler->getDocumentForCreation($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @param $width
     * @param $height
     * @param null $saveToPath
     * @return \GuzzleHttp\Stream\StreamInterface
     * @throws \InvalidArgumentException
     */
    public function getDocumentThumbnail($documentId, $width, $height, $saveToPath = null)
    {
        $requestOptions = [
            'query' => [
                'width' => $width,
                'height' => $height
            ]
        ];

        if ($saveToPath !== null) {
            $saveDir = pathinfo($saveToPath, PATHINFO_DIRNAME);
            if (!is_dir($saveDir) || !is_writable($saveDir)) {
                throw new \InvalidArgumentException(
                    "The path to save the file to is not writable"
                );
            }
            $resource = fopen($saveToPath, 'w');
            $stream = Stream::factory($resource);
            $requestOptions['save_to'] = $stream;
        }

        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('GET', ['documents/{id}/thumbnail', ['id' => $documentId]], $requestOptions);

        return $this->responseHandler->getDocumentFileStream($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @param string $extension
     * @param null $saveToPath
     * @return \GuzzleHttp\Stream\StreamInterface
     * @throws \InvalidArgumentException
     */
    public function getDocumentContent($documentId, $extension = '', $saveToPath = null)
    {
        if (!empty($extension) && strpos($extension, '.') !== 0) {
            $extension = '.' . $extension;
        }

        $requestOptions = [];
        if ($saveToPath !== null) {
            $saveDir = pathinfo($saveToPath, PATHINFO_DIRNAME);
            if (!is_dir($saveDir) || !is_writable($saveDir)) {
                throw new \InvalidArgumentException(
                    "The path to save the file to is not writable"
                );
            }
            $resource = fopen($saveToPath, 'w');
            $stream = Stream::factory($resource);
            $requestOptions['save_to'] = $stream;
        }

        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest(
            'GET',
            [ 'documents/{id}/content{extension}', [ 'id' => $documentId, 'extension' => $extension ] ],
            $requestOptions
        );

        return $this->responseHandler->getDocumentFileStream($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @return null|string
     */
    public function getDocumentMimeType($documentId)
    {
        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('HEAD', [ 'documents/{id}/content', [ 'id' => $documentId] ]);
        return $this->responseHandler->getContentType($this->sendRequest($client, $request));
    }

    /**
     * @param $documentId
     * @param int|\DateTime $expiration
     * @param bool $isDownloadable
     * @param bool $isTextSelectable
     * @return Response\Entity\Document
     */
    public function createSession($documentId, $expiration = 60, $isDownloadable = false, $isTextSelectable = true)
    {
        $postData = [
            'document_id' => $documentId,
            'is_downloadable' => $isDownloadable ? 'true' : 'false',
            'is_text_selectable' => $isTextSelectable ? 'true' : 'false'
        ];

        if ($expiration instanceof \DateTime) {
            $postData['expires_at'] = $expiration->format(\DateTime::RFC3339);
        } else {
            $postData['duration'] = $expiration;
        }

        $client = $this->clientManager->getApiClient();
        $request = $client->createRequest('POST', 'sessions', ['json' => $postData]);

        return $this->responseHandler->getSession($this->sendRequest($client, $request));
    }

    /**
     * @param GuzzleClient $client
     * @param RequestInterface $request
     * @return \GuzzleHttp\Message\Response
     * @throws Exception\BadRequestException
     * @throws Exception\NotFoundException
     * @throws Exception\RemovedException
     * @throws Exception\TooManyRequestsException
     * @throws Exception\RequestException
     */
    protected function sendRequest(GuzzleClient $client, RequestInterface $request)
    {
        try
        {
            return $client->send($request);
        }
        catch (ClientException $e)
        {
            switch ($e->getResponse()->getStatusCode())
            {
                case '400':
                    throw new BoxException\BadRequestException($e);
                    break;
                case '404':
                    throw new BoxException\NotFoundException($e);
                    break;
                case '410':
                    throw new BoxException\RemovedException($e);
                    break;
                case '429':
                    throw new BoxException\TooManyRequestsException($e);
                    break;
                default:
                    throw new BoxException\RequestException($e);
            }
        }
    }
} 
