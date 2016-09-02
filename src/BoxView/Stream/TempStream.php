<?php

namespace BoxView\Stream;

class TempStream extends AbstractDecoratorStream
{
    /**
     * @param $saveToPath
     * @param bool $overwriteExistingFile
     * @param bool $flush
     * @return \SplFileInfo
     * @throws \InvalidArgumentException
     */
    public function createFileStream($saveToPath, $overwriteExistingFile = true, $flush = true)
    {
        $saveDir = pathinfo($saveToPath, PATHINFO_DIRNAME);
        if ( !is_dir($saveDir) || !is_writable($saveDir) || $overwriteExistingFile && file_exists($saveToPath) ) {
            throw new \InvalidArgumentException(
                "The path to save the file to is not writable"
            );
        }

        $mode = $overwriteExistingFile ? 'w+' : 'x+';

        $fileStream = \GuzzleHttp\Psr7\stream_for(fopen($saveToPath, $mode));

        $this->rewind();
        \GuzzleHttp\Psr7\copy_to_stream($this->stream, $fileStream);

        if ($flush)
        {
            $this->close();
        }

        return $fileStream;
    }
}
