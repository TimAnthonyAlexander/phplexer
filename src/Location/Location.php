<?php

declare(strict_types=1);

namespace TimAlexander\Phplexer\Location;

class Location
{
    public function __construct(
        public int $line,
        public int $column,
        public int $length,
    ) {
    }
}
