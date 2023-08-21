<?php

declare(strict_types=1);

namespace TimAlexander\Phplexer\File;

class File
{
    public readonly string $contents;

    public function __construct(private readonly string $filename)
    {
        if (!file_exists($filename)) {
            throw new \InvalidArgumentException("File $filename does not exist");
        }
        $this->contents = file_get_contents($filename);
    }
}
