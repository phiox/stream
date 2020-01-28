<?php

namespace Phiox;

use Phiox\Stream\Wrapper\File;

class Stream extends File implements StreamInterface
{

    /**
     * @var int Current stream position
     */
    protected $offset = 0;

    /**
     * Closes the stream.
     *
     * @return void
     */
    public function close()
    {
        $this->stream_close();
    }

    /**
     * Extend wrapper seek to ensure valid value in $this->offset
     *
     * @param  int       $offset
     * @param  int       $whence
     * @return bool|void
     */
    public function stream_seek($offset, $whence = SEEK_SET)
    {
        $ret = parent::stream_seek($offset, $whence);

        $this->offset = $this->stream_tell();

        return $ret;
    }

    /**
     * Extend wrapper read to ensure valid value in $this->offset
     *
     * @param  int         $count
     * @return string|void
     */
    public function stream_read($count)
    {
        $value = parent::stream_read($count);

        $this->offset = $this->stream_tell();

        return $value;
    }


    /**
     * Extend wrapper read to ensure valid value in $this->offset
     *
     * @param  string     $data
     * @return int|string
     */
    public function stream_write($data)
    {
        $ret = parent::stream_write($data);

        $this->offset = $this->stream_tell();

        return $ret;
    }

    /**
     * @return bool
     */
    public function isSeekable()
    {
        $meta = stream_get_meta_data($this->resource);

        return $meta['seekable'];
    }

    /**
     * @return bool
     */
    public function isReadable()
    {
        $meta = stream_get_meta_data($this->resource);

        $readable = [
            'r', 'w+', 'r+', 'x+', 'c+', 'rb', 'w+b', 'r+b',
            'x+b', 'c+b', 'rt', 'w+t', 'r+t', 'x+t', 'c+t', 'a+',
        ];

        return in_array($meta['mode'], $readable, true);
    }

    /**
     * @return bool
     */
    public function isWritable()
    {
        $meta = stream_get_meta_data($this->resource);

        $writable = [
            'w', 'w+', 'rw', 'r+', 'x+', 'c+', 'wb', 'w+b', 'r+b',
            'x+b', 'c+b', 'w+t', 'r+t', 'x+t', 'c+t', 'a', 'a+',
        ];

        return in_array($meta['mode'], $writable, true);
    }

    /**
     * Go to offset in stream.
     *
     * @param int $offset
     */
    public function seek($offset)
    {
        if ($offset < 0) {
            parent::stream_seek($offset, SEEK_END);
        } else {
            parent::stream_seek($offset);
        }

        $this->offset = $this->stream_tell();
    }

    /**
     * Check stream EOF.
     *
     * @return bool
     */
    public function isEnd()
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
     * Read data from stream.
     *
     * @param  int    $size
     * @return string
     */
    public function read($size = 1)
    {
        $data = parent::stream_read($size);

        if ($data !== false) {
            $this->offset += $size;
        } else {
            $this->offset = $this->stream_tell();
        }

        return $data;
    }

    /**
     * Write data to stream.
     *
     * @param mixed $data
     */
    public function write($data)
    {
        if (parent::stream_write($data)) {
            $this->offset += strlen($data);
        } else {
            $this->offset = $this->stream_tell();
        }
    }

    /**
     * Flush stream contents to target
     *
     * @param  null|resource $stream
     * @param  bool           $rewind
     * @return false|int
     */
    public function pipe($stream = null, $rewind = true)
    {
        if (!is_resource($stream)) {
            $stream = STDOUT;
        }

        return stream_copy_to_stream($stream, $this->resource, null, ($rewind ? 0 : null));
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->offset = 0;

        $this->stream_seek($this->offset);
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $this->stream_seek($this->offset);

        return $this->stream_read(1);
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

        $this->stream_seek($this->offset);
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->stream_eof();
    }

    /**
     * Destructor, closes stream automatically.
     */
    public function __destruct()
    {
        $this->close();
    }
}
