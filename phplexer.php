#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace phplexer;

use TimAlexander\Phplexer\File\File;
use TimAlexander\Phplexer\Lexer\Lexer;

require_once __DIR__ . '/vendor/autoload.php';

$arg1 = $argv[1] ?? null;

if ($arg1 === null) {
    echo "Usage: ./phplexer.php <filename>\n";
    exit(1);
}

$file = new File($arg1);

$lexer = new Lexer($file);

foreach ($lexer->tokens as $token) {
    echo $token->type . ': "' . $token->value . '"' . "\n";
}
