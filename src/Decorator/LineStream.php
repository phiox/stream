<?php

namespace Phiox\Stream\Decorator;

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
        $this->stream->rewind();
        $offset = $this->stream->getOffset();

        while (!$this->stream->isEof() && $this->line < $line) {
            $offset = $this->stream->getOffset();
            fgets($this->stream->getResource());
            $this->line++;
        }

        $this->stream->seek($offset);
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
        $start = $this->stream->getOffset();
        fgets($this->getResource());
        $end = $this->stream->getOffset();

        $this->stream->seek($start);

        return new LimitIterator($this->stream, $start, ($end - $start));
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
        fgets($this->stream->getResource());

        ++$this->line;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return !$this->stream->isEof();
    }
}
