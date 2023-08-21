<?php

declare(strict_types=1);

namespace TimAlexander\Phplexer\Token;

use TimAlexander\Phplexer\Location\Location;

class Token
{
    public function __construct(
        public int $type,
        public string $value,
        public Location $location,
    ) {
    }
}
