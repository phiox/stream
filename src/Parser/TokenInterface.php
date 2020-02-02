<?php

namespace Phiox\Decorator\Parser;

interface TokenInterface
{
    const VALUE = 1;

    const SEQUENCE = 2;

    const RANGE = 4;

    const OPTIONAL_MATCH = 8;

    const FIRST_MATCH = 16;

    const LONGEST_MATCH = 32;

    const NO_MATCH = 64;
}