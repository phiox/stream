<?php

namespace Phiox\Stream;

use Phiox\Stream;
use Psr\Http\Message\StreamInterface as PsrStreamInterface;

/**
 * PSR obviously doesn't know about the different kinds of PHP streams,
 * and in a drunken state of "fuck-you-assholes-we-do-http-bodies" decided
 * to force these retarded methods onto all Streams for eternity. The end.
 *
 * @class PsrStream
 */
class PsrStream extends Stream implements PsrStreamInterface
{
    /**
     * @return string
     */
    public function __toString()
    {
        $this->stream_seek(0);
        return $this->getContents();
    }

    /**
     * @return string
     */
    public function getContents()
    {
        return stream_get_contents($this->resource);
    }

    /**
     * @return void
     */
    public function detach()
    {
        $this->close();
    }

    /**
     * @return int
     */
    public function tell()
    {
        return $this->stream_tell();
    }

    /**
     * @return bool
     */
    public function eof()
    {
        return $this->stream_eof();
    }

    /**
     * @param  string $key
     * @return string|array|null
     */
    public function getMetaData($key = null)
    {
        $meta = (array)stream_get_meta_data($this->resource);

        return (is_null($key) ? $meta : (isset($meta[$key]) ? $meta[$key] : null));
    }
}
