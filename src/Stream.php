<?php

namespace Phiox;

use Phiox\Stream\Wrapper\File;
use Phiox\Stream\Wrapper\Memory;
use Phiox\Stream\StreamInterface;

class Stream implements StreamInterface
{

    /**
     * @var StreamInterface $stream Decorated base-instance
     */
    protected $stream;

    /**
     * @var int Current stream position
     */
    protected $offset = 0;

    /**
     * Stream constructor.
     *
     * @param StreamInterface|null $stream
     */
    public function __construct(StreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * New instance from opened file
     *
     * @param  string $filename
     * @param  string $mode
     * @return Stream
     */
    public static function fromFile($filename, $mode = 'wb')
    {
        return new self(new File($filename, $mode));
    }

    /**
     * New instance from string data
     *
     * @param  string $data
     * @return Stream
     */
    public static function fromString($data)
    {
        return new self(new Memory($data));
    }

    /**
     * @return resource|void
     */
    public function getResource()
    {
        return $this->stream->getResource();
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        return $this->stream->isSeekable();
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return $this->stream->isReadable();
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->stream->isWritable();
    }

    /**
     * Seek to specified offset.
     *
     * @param int $offset
     */
    public function seek($offset)
    {
        $this->stream->seek($offset);
    }

    /**
     * Check stream EOF.
     *
     * @return bool
     */
    public function isEof()
    {
        return $this->stream->isEof();
    }

    /**
     * Get stream size
     *
     * @return int
     */
    public function getSize()
    {
        return $this->stream->getSize();
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->stream->getOffset();
    }

    /**
     * Read data from stream.
     *
     * @param  int    $size
     * @return string
     */
    public function read($size = 1)
    {
        return $this->stream->read($size);
    }

    /**
     * Write data to stream.
     *
     * @param mixed $data
     */
    public function write($data)
    {
        $this->stream->write($data);
    }

    /**
     * Copy stream contents to target in chunks. Default stream buffer-size is 8kb.
     *
     * @param  null|resource $stream
     * @param  bool           $rewind
     * @return false|int
     */
    public function pipe($stream = null, $rewind = true)
    {
        return $this->stream->pipe($stream, $rewind);
    }

    /**
     * @return void
     */
    public function rewind()
    {
         $this->stream->rewind();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        return $this->stream->current();
    }

    /**
     * @return string|float|int|bool|null Scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->offset;
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->stream->next();
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->stream->valid();
    }

    /**
     * Closes the stream.
     *
     * @return void
     */
    public function close()
    {
        $this->stream->close();
    }
}
