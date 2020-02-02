<?php

namespace Phiox\Stream\Wrapper;

use Phiox\Stream\StreamInterface;

class Memory extends AbstractWrapper implements StreamInterface
{
    /**
     * @var resource Stream resource-handle
     */
    protected $resource;

    /**
     * @var int Current stream position
     */
    protected $offset = 0;

    /**
     * @var string Wrapper protocol
     */
    protected $protocol = 'php';

    /**
     * Create new stream from (optional) string
     *
     * @param  mixed          $data Stream data
     * @return false|resource       Stream handle
     */
    public function __construct($data = null)
    {
        $this->register('php');

        $stream = fopen('php://temp', 'wb');

        if (!empty($data)) {
            fwrite($stream, $data);
            fseek($stream, 0);
        }

        return $stream;
    }

    /**
     * Get wrapped resource-handle
     *
     * @return mixed|resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Call method on the wrapped stream resource.
     *
     * @param  string $function
     * @param  mixed  $arguments
     * @return mixed
     */
    protected function callInner($function, $arguments)
    {
        $res = parent::callInner($function, $arguments);

        if ($this->getResource()) {
            $this->offset = parent::callInner('ftell', [$this->resource]);
        }

        return $res;
    }

    /**
     * Go to offset in stream.
     *
     * @param int $offset
     */
    public function seek($offset)
    {
        if ($offset < 0) {
            $this->callInner('fseek', [$this->resource, $offset, SEEK_END]);
        } else {
            $this->callInner('fseek', [$this->resource, $offset]);
        }
    }

    /**
     * Check stream EOF.
     *
     * @return bool
     */
    public function isEof()
    {
        return $this->stream_eof();
    }

    /**
     * Get stream size
     *
     * @return int
     */
    public function getSize()
    {
        $stat = $this->stream_stat();

        return $stat['size'];
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Read data from stream.
     *
     * @param  int    $size
     * @return string
     */
    public function read($size = 1)
    {
        return $this->callInner('fread', [$this->resource, $size]);
    }

    /**
     * Write data to stream.
     *
     * @param mixed $data
     */
    public function write($data)
    {
        $this->callInner('fwrite', [$this->resource, $data]);
    }

    /**
     * Copy stream contents to target in chunks. Default stream buffer-size is 8kb.
     *
     * @param  null|resource $stream
     * @param  bool          $rewind
     * @return false|int
     */
    public function pipe($stream = null, $rewind = true)
    {
        if ($stream instanceof StreamInterface) {
            $stream = $stream->getResource();
        } else if (!is_resource($stream)) {
            $stream = STDOUT;
        }

        if ($rewind) {
            $this->rewind();
        }

        return stream_copy_to_stream(
            $this->getResource(),
            $stream
        );
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->callInner('fseek', [$this->resource, 0]);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->callInner('fseek', [$this->resource, $this->offset]);

        return $this->callInner('fread', [$this->resource, 1]);
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
        ++$this->offset;

        $this->callInner('fseek', [$this->resource, $this->offset]);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->stream_eof();
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        return $this->readable;
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        return $this->writable;
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        return $this->seekable;
    }

    /**
     * Closes the stream.
     *
     * @return void
     */
    public function close()
    {
        $this->stream_close();
    }
}
