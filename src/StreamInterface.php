<?php

namespace Phiox;

use SeekableIterator;
use Phiox\Stream\WrapperInterface;

interface StreamInterface extends SeekableIterator, WrapperInterface
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
     * @param false|resource $stream
     */
    public function pipe($stream = STDOUT);

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
    public function isEnd();

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
