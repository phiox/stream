<?php

namespace Phiox\Stream\Tests;

use PHPUnit_Framework_TestCase;
use Phiox\Stream;

class SimpleTest extends PHPUnit_Framework_TestCase
{
    public function testWrappedFileIteration()
    {
        $stream = Stream::fromFile(__DIR__ . DIRECTORY_SEPARATOR . 'file.txt');
        //$tmp = new Stream(new Stream\Wrapper\Memory());
        //$stream->pipe($tmp);

        $contents = "This is an example file, used to test wrapping the native PHP stream file:// protocol";
        $length = strlen($contents);

        var_dump($length, fread($stream->getResource(), $length));

        $stream2 = Stream::fromString($contents);
        var_dump($length, fread($stream2->getResource(), $length));

        /*
            if (is_iterable($stream)) {
                print "The streams is allegedly traversable, let's see about that. :/";
            }

            foreach ($stream as $index => $byte) {
                $this->assertEquals($contents[$index], $byte);
            }
        */
    }
}
