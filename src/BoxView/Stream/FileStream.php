<?php

namespace BoxView\Stream;

class FileStream extends AbstractDecoratorStream
{
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
