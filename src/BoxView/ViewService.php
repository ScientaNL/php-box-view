<?php

namespace BoxView;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;
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

        return $this->responseHandler->getDocuments($this->sendRequest(
            $this->clientManager->getApiClient(),
            'GET', 'documents',
            ['query' => $queryData]
        ));
    }

    /**
     * @param $documentId
     * @return Response\Entity\Document
     */
    public function getDocument($documentId)
    {
        return $this->responseHandler->getDocument($this->sendRequest(
            $this->clientManager->getApiClient(),
            'GET', sprintf('documents/%s', $documentId)
        ));
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

        return $this->responseHandler->getDocument($this->sendRequest(
            $this->clientManager->getApiClient(),
            'PUT', sprintf('documents/%s', $documentId),
            ['json' => $putData]
        ));
    }

    /**
     * @param $documentId
     * @return bool
     */
    public function deleteDocument($documentId)
    {
        return $this->responseHandler->documentDeleted($this->sendRequest(
            $this->clientManager->getApiClient(),
            'DELETE', sprintf('documents/%s', $documentId)
        ));
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

        return $this->responseHandler->getDocumentForCreation($this->sendRequest(
            $this->clientManager->getUploadClient(),
            'POST', 'documents',
            ['multipart' => [
                [
                    'name'     => 'file',
                    'filename' => $fileName,
                    'contents' => fopen($file, 'r'),
                    'headers'  => $fileHeaders
                ],
                [
                    'name'     => 'non_svg',
                    'contents' => $nonSvg ? 'true' : 'false'
                ],
                [
                    'name'     => 'name',
                    'contents' => $name
                ],
                [
                    'name'     => 'thumbnails',
                    'contents' => implode(',', $thumbnails)
                ]
            ]]
        ));
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

        return $this->responseHandler->getDocumentForCreation($this->sendRequest(
            $this->clientManager->getApiClient(),
            'POST', 'documents',
            ['json' => $postData]
        ));
    }

    /**
     * @param $documentId
     * @param $width
     * @param $height
     * @param null $saveToPath
     * @return \Psr\Http\Message\StreamInterface
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
            if ( !is_dir($saveDir) || file_exists($saveToPath) || !is_writable($saveDir) ) {
                throw new \InvalidArgumentException(
                    "The path to save the file to is not writable"
                );
            }
            $resource = fopen($saveToPath, 'x+');
            $requestOptions['sink'] = $resource;
        }

        return $this->responseHandler->getBodyStream($this->sendRequest(
            $this->clientManager->getApiClient(),
            'GET', sprintf('documents/%s/thumbnail', $documentId),
            $requestOptions
        ));
    }

    /**
     * @param $documentId
     * @param string $extension
     * @param null $saveToPath
     * @return \Psr\Http\Message\StreamInterface
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
            if ( !is_dir($saveDir) || file_exists($saveToPath) || !is_writable($saveDir) ) {
                throw new \InvalidArgumentException(
                    "The path to save the file to is not writable"
                );
            }
            $resource = fopen($saveToPath, 'x+');
            $requestOptions['sink'] = $resource;
        }

        return $this->responseHandler->getBodyStream($this->sendRequest(
            $this->clientManager->getApiClient(),
            'GET', sprintf('documents/%s/content%s', $documentId, $extension),
            $requestOptions
        ));
    }

    /**
     * @param $documentId
     * @param string $extension
     * @return int
     */
    public function checkDocumentContent($documentId, $extension = '')
    {
        if (!empty($extension) && strpos($extension, '.') !== 0) {
            $extension = '.' . $extension;
        }

        return $this->responseHandler->getStatusCode($this->sendRequest(
            $this->clientManager->getApiClient(),
            'HEAD', sprintf('documents/%s/content%s', $documentId, $extension)
        ));
    }

    /**
     * @param $documentId
     * @return null|string
     */
    public function getDocumentMimeType($documentId)
    {
        return $this->responseHandler->getContentType($this->sendRequest(
            $this->clientManager->getApiClient(),
            'HEAD', sprintf('documents/%s/content', $documentId)
        ));
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

        return $this->responseHandler->getSession($this->sendRequest(
            $this->clientManager->getApiClient(),
            'POST', 'sessions',
            ['json' => $postData]
        ));
    }

    /**
     * @param GuzzleClient $client
     * @param $method
     * @param $uri
     * @param array $options
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws Exception\BadRequestException
     * @throws Exception\NotFoundException
     * @throws Exception\RemovedException
     * @throws Exception\TooManyRequestsException
     * @throws Exception\RequestException
     */
    protected function sendRequest(GuzzleClient $client, $method, $uri, array $options = [])
    {
        try
        {
            return $client->request($method, $uri, $options);
        }
        catch (RequestException $e)
        {
            $statusCode = $e->hasResponse() ? $e->getResponse()->getStatusCode() : null;
            switch ($statusCode)
            {
                case 400:
                    throw new BoxException\BadRequestException($e);
                    break;
                case 404:
                    throw new BoxException\NotFoundException($e);
                    break;
                case 410:
                    throw new BoxException\RemovedException($e);
                    break;
                case 429:
                    throw new BoxException\TooManyRequestsException($e);
                    break;
                default:
                    throw new BoxException\RequestException($e);
            }
        }
    }
} 
