<?php

namespace BoxView\Response\Stream;

use GuzzleHttp\Stream\Utils;
use GuzzleHttp\Stream\Stream;

class TempFileStream extends FileStream
{
    /** @var FileStream */
    protected $fileStream;

    /**
     * @return \SplFileInfo
     * @throws \RuntimeException
     */
    public function getFile()
    {
        if ($this->fileStream === null) {
            throw new \RuntimeException("Temp file must be created first with the createFileStream method.");
        }
        return $this->fileStream->getFile();
    }

    /**
     * @param $saveToPath
     * @param bool $overwriteExistingFile
     * @return \SplFileInfo
     * @throws \InvalidArgumentException
     */
    public function createFileStream($saveToPath, $overwriteExistingFile = true)
    {
        $saveDir = pathinfo($saveToPath, PATHINFO_DIRNAME);
        if (!is_dir($saveDir) || !is_writable($saveDir)) {
            throw new \InvalidArgumentException(
                "The path to save the file to is not writable"
            );
        }

        $mode = $overwriteExistingFile ? 'w+' : 'x+';
        $saveTo = new Stream(Utils::open($saveToPath, $mode));
        $this->seek(0);
        Utils::copyToStream($this->stream, $saveTo);
        $this->stream->close();

        $this->fileStream = new FileStream($saveTo);
        return $this->fileStream;
    }
} 
