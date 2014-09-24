<?php

namespace BoxView\Response\Stream;

use GuzzleHttp\Stream\StreamInterface;
use GuzzleHttp\Stream\MetadataStreamInterface;
use GuzzleHttp\Stream\StreamDecoratorTrait;

class FileStream implements StreamInterface, MetadataStreamInterface
{
    use StreamDecoratorTrait;

    /** @var string */
    protected $serverMimeType;

    /** @var string */
    protected $fileInfo;

    /**
     * @param StreamInterface $stream
     * @param null $serverMimeType
     */
    public function __construct(StreamInterface $stream, $serverMimeType = null)
    {
        $this->stream = $stream;
        $this->serverMimeType = $serverMimeType;
    }

    /**
     * @return null|string
     */
    public function getServerMimeType()
    {
        return $this->serverMimeType;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return false;
    }

    /**
     * @param string $string
     * @return bool|int
     */
    public function write($string)
    {
        return false;
    }

    /**
     * @return \SplFileInfo
     * @throws \RuntimeException
     */
    public function getFile()
    {
        $filePath = $this->getMetadata('uri');
        if (!is_readable($filePath)) {
            throw new \RuntimeException("Can't interpret stream uri as file path");
        }
        $this->close();

        return new \SplFileInfo($filePath);
    }
} 
