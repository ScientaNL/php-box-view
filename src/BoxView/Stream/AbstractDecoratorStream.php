<?php

namespace BoxView\Stream;

use Psr\Http\Message\StreamInterface;

abstract class AbstractDecoratorStream implements StreamInterface
{
    /**
     * @var StreamInterface
     */
    protected $stream;

    /**
     * @var string
     */
    protected $serverMimeType;

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

    public function __toString() {
        return $this->stream->__toString();
    }

    public function close() {
        return $this->stream->close();
    }

    public function detach() {
        return $this->stream->detach();
    }

    public function getSize() {
        return $this->stream->getSize();
    }

    public function tell() {
        return $this->stream->tell();
    }

    public function eof() {
        return $this->stream->eof();
    }

    public function isSeekable() {
        return $this->stream->isSeekable();
    }

    public function seek($offset, $whence = SEEK_SET) {
        return $this->stream->seek($offset, $whence);
    }

    public function rewind() {
        return $this->stream->rewind();
    }

    public function isWritable() {
        return $this->stream->isWritable();
    }

    public function write($string) {
        return $this->stream->write($string);
    }

    public function isReadable() {
        return $this->stream->isReadable();
    }

    public function read($length) {
        return $this->stream->read($length);
    }

    public function getContents() {
        return $this->stream->getContents();
    }

    public function getMetadata($key = null) {
        return $this->stream->getMetadata($key);
    }

    // route all other method calls directly to Stream
    public function __call($method, $args)
    {
        return call_user_func_array([$this->stream, $method], $args);
    }
} 
