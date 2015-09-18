<?php

namespace Cravid\Http;

/**
 * Describes a data stream.
 *
 * Typically, an instance will wrap a PHP stream; this interface provides
 * a wrapper around the most common operations, including serialization of
 * the entire stream to a string.
 */
class Stream implements \Psr\Http\Message\StreamInterface
{
    /**
     * The underlying stream resource.
     *
     * @var resource|bool
     */
    private $stream = false;


    /**
     * Creates the stream.
     *
     */
    public function __construct($filename, $mode, $include_path = true, $context = null)
    {
        if ($context) { 
            $this->stream = fopen($filename, $mode, $include_path, $context);
        } else {
            $this->stream = fopen($filename, $mode, $include_path);
        }

        if ($this->stream === false) {
            throw new \RuntimeException(sprintf('Could not open stream with mode "%s".', $mode));
        }
    }

    /**
     * Closes the stream when destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        try {
            $this->rewind();
            return $this->getContents();
        }
        catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        $resource = $this->detach();

        if (is_resource($resource)) {
            fclose($resource);
        }
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any.
     */
    public function detach()
    {
        if (!is_resource($this->stream)) {
            return null;
        }

        $result = $this->stream;
        $this->stream = false;

        return $result;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!is_resource($this->stream)) {
            return null;
        }

        $fstat = fstat($this->stream);

        return isset($fstat['size']) ? $fstat['size'] : null;
    }

    /**
     * Returns the current position of the file read/write pointer.
     *
     * @return int Position of the file pointer.
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        $result = ftell($this->stream);

        if ($result === false) {
            throw new \RuntimeException('Unable to determine stream position');
        }

        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        return !is_resource($this->stream) || feof($this->stream);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        return $this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable');
        }

        $result = fseek($this->stream, $offset, $whence);

        if ($result === -1) {
            throw new \RuntimeException(sprintf('Failed seeking offset %d with.', $offset));
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable.');
        }

        try {
            $this->seek(0);
        }
        catch (\RuntimeException $e) {
            throw new \RuntimeException(sprintf('Could not rewind the stream.', $offset), $e->getCode(), $e);
        }
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        $mode = $this->getMetadata('mode');

        if (!$mode) {
            return false;
        }

        return $mode !== 'r';
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isWritable()) {
            throw new \RuntimeException('Stream is not writable.');
        }

        $result = fwrite($this->stream, $string);

        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed writing "%s" to the stream.', $string));
        }

        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        $mode = $this->getMetadata('mode');

        if (!$mode) {
            return false;
        }

        return (bool)strpos($mode, 'r') || (bool)strpos($mode, '+');
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable.');
        }

        $result = fread($this->stream, $length);

        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed reading %d bytes from the stream.', $length));
        }

        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        $contents = stream_get_contents($this->stream);

        if ($contents === false) {
            throw new \RuntimeException('Unable to read contents from stream.');
        }

        return $contents;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key Specific metadata to retrieve.
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        if (!is_resource($this->stream)) {
            return null;
        }

        $meta = stream_get_meta_data($this->stream);

        if ($key !== null && isset($meta[$key])) {
            return $meta[$key];
        }

        return $meta;
    }
}
