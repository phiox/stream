<?php

namespace Phiox\Stream;

use Phiox\Stream;
use LimitIterator;

class LineStream extends Stream
{

    /**
     * @var int
     */
    protected $line = 1;

    /**
     * @param  int  $line
     * @return void
     */
    public function seek($line)
    {
        $this->rewind();

        while (!feof($this->resource) && $this->line < $line) {
            $this->offset = $this->stream_tell();

            fgets($this->resource);

            $this->line++;
        }

        $this->stream_seek($this->offset);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->line = 1;

        parent::rewind();
    }

    /**
     * @return mixed
     */
    public function current()
    {
        $start = $this->stream_tell();
        fgets($this->resource);

        $end = $this->stream_tell();
        $this->stream_seek($start);

        return new LimitIterator($this, $start, ($end - $start));
    }

    /**
     * @return string|float|int|bool|null Scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->line;
    }

    /**
     * @return void
     */
    public function next()
    {
        fgets($this->resource);

        ++$this->line;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !$this->isEnd();
    }
}
