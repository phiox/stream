<?php

namespace Phiox\Stream;

use SeekableIterator;

interface StreamInterface extends SeekableIterator
{

    /**
     * StreamInterface
     *
     * @param  int   $length
     * @return mixed
     */
    public function read($length = 1024);

    /**
     * StreamInterface
     *
     * @param  mixed $data
     * @return bool
     */
    public function write($data);

    /**
     * StreamInterface
     *
     * @param  false|resource $stream
     * @param  bool           $rewind
     * @return false|int
     */
    public function pipe($stream = STDOUT, $rewind = true);

    /**
     * StreamInterface
     *
     * @return void
     */
    public function close();

    /**
     * StreamInterface
     *
     * @return int|null Size in bytes or null if unknown
     */
    public function getSize();

    /**
     * StreamInterface
     *
     * @return bool
     */
    public function isSeekable();

    /**
     * StreamInterface
     *
     * @return bool
     */
    public function isReadable();

    /**
     * StreamInterface
     *
     * @return bool
     */
    public function isWritable();

    /**
     * StreamInterface
     *
     * @return bool
     */
    public function isEof();

    /**
     * StreamInterface
     *
     * @return int
     */
    public function getOffset();

    /**
     * StreamInterface
     *
     * @return resource
     */
    public function getResource();

    /**
     * SeekableIterator & StreamInterface
     *
     * @param  int  $position
     * @return void
     */
    public function seek($position);

    /**
     * Iterator
     *
     * @return mixed
     */
    public function current();

    /**
     * Iterator
     *
     * @return string|float|int|bool|null Scalar on success, or null on failure.
     */
    public function key();

    /**
     * Iterator
     *
     * @return void
     */
    public function next();

    /**
     * Iterator
     *
     * @return void
     */
    public function rewind();

    /**
     * Iterator
     *
     * @return bool
     */
    public function valid();
}
