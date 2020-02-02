<?php

namespace Phiox\Parser;

use Phiox\Decorator\Parser\TokenInterface;

class LineToken implements TokenInterface
{

    /**
     *
     */
    const RULES = [
        self::LONGEST_MATCH => [
            self::SEQUENCE => "\r\n",
            self::VALUE => "\n",
        ],
    ];

    /** @var int */
    public $offset;

    /** @var */
    public $length;


}