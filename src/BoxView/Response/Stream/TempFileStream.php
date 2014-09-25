<?php

namespace BoxView\Response\Stream;

use GuzzleHttp\Stream\Utils;
use GuzzleHttp\Stream\Stream;

class TempFileStream extends FileStream
{
    /**
     * @param $saveToPath
     * @param bool $overwriteExistingFile
     * @return \SplFileInfo
     * @throws \InvalidArgumentException
     */
    public function createFile($saveToPath, $overwriteExistingFile = true)
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
        $this->stream = $saveTo;

        return parent::getFile();
    }
} 
