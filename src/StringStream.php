<?php

namespace Cravid\Http;

/**
 *
 */
class StringStream extends Stream
{
    /**
     *
     */
    public function __construct($content)
    {
        if (!is_string($content)) {
            throw new \InvalidArgumentException(
                sprintf('Content has to be a string, given "%s".', gettype($stream))
            );
        }
        
        parent::__construct('php://temp,', 'r+');

        $this->write($content);
        $this->rewind();
    }
}