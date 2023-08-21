<?php

declare(strict_types=1);

namespace TimAlexander\Phplexer\Lexer;

use IntlChar;
use TimAlexander\Phplexer\File\File;
use TimAlexander\Phplexer\Location\Location;
use TimAlexander\Phplexer\Token\Token;

class Lexer
{
    public const TOKEN_EOF = 0;
    public const TOKEN_WHITESPACE = 1;
    public const TOKEN_COMMENT = 2;
    public const TOKEN_STRING = 3;
    public const TOKEN_NUMBER = 4;
    public const TOKEN_STRING_LITERAL = 5;
    public const TOKEN_OCURLY = 6;
    public const TOKEN_CCURLY = 7;
    public const TOKEN_OPAREN = 8;
    public const TOKEN_CPAREN = 9;

    private int $cursor = 0;
    private int $line = 1;
    private int $column = 1;

    public array $tokens;

    public function __construct(private readonly File $file)
    {
        $this->tokens = $this->lex();
    }

    private static function isEmpty(string $char): bool
    {
        return IntlChar::isspace($char);
    }

    private static function isDigit(string $char): bool
    {
        return IntlChar::isdigit($char);
    }

    private static function isAlpha(string $char): bool
    {
        return IntlChar::isalpha($char);
    }

    private static function isAlphaNumeric(string $char): bool
    {
        return IntlChar::isalnum($char);
    }

    private static function isStringLiteral(string $char): bool
    {
        return $char === '"';
    }

    private static function isComment(string $char): bool
    {
        return $char === '#';
    }

    private function getCurrentChar(int $n = 1): string
    {
        $string = '';

        foreach (range(0, $n - 1) as $i) {
            if (!isset($this->file->contents[$this->cursor + $i])) {
                return '';
            }

            $string .= $this->file->contents[$this->cursor + $i] ?? '';
        }

        return $string;
    }

    private function nextToken(): Token
    {
        while (self::isEmpty($this->getCurrentChar())) {
            $this->cursor++;
            $this->column++;

            if ($this->getCurrentChar(2) === "\n") {
                $this->cursor++;
                $this->line++;
                $this->column = 1;
            }
        }

        // alpha
        if (self::isAlpha($this->getCurrentChar())) {
            $start = $this->cursor;
            while (!self::isEmpty($this->getCurrentChar()) && self::isAlphaNumeric($this->getCurrentChar())) {
                $this->cursor++;
                $this->column++;
            }
            $token = new Token(
                type: self::TOKEN_STRING,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // number
        if (self::isDigit($this->getCurrentChar())) {
            $start = $this->cursor;
            while (!self::isEmpty($this->getCurrentChar()) && self::isDigit($this->getCurrentChar())) {
                $this->cursor++;
                $this->column++;
            }
            $token = new Token(
                type: self::TOKEN_NUMBER,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // string literal
        if ($this->getCurrentChar() === '"') {
            $start = $this->cursor;
            $this->cursor++;
            $this->column++;

            while ($this->getCurrentChar() !== '"') {
                $this->cursor++;
                $this->column++;
            }

            $this->cursor++;
            $this->column++;

            $token = new Token(
                type: self::TOKEN_STRING_LITERAL,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // comment
        if (self::isComment($this->getCurrentChar())) {
            $start = $this->cursor;
            while ($this->getCurrentChar(2) !== "\n") {
                $this->cursor++;
                $this->column++;
            }
            $token = new Token(
                type: self::TOKEN_COMMENT,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // Curly braces
        if ($this->getCurrentChar() === '{') {
            $this->cursor++;
            $this->column++;
            $token = new Token(
                type: self::TOKEN_OCURLY,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
        }

        if ($this->getCurrentChar() === '}') {
            $this->cursor++;
            $this->column++;
            $token = new Token(
                type: self::TOKEN_CCURLY,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
        }

        // Parentheses
        if ($this->getCurrentChar() === '(') {
            $this->cursor++;
            $this->column++;
            $token = new Token(
                type: self::TOKEN_OPAREN,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
        }

        if ($this->getCurrentChar() === ')') {
            $this->cursor++;
            $this->column++;
            $token = new Token(
                type: self::TOKEN_CPAREN,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
        }

        // EOF
        if ($this->getCurrentChar() === '') {
            $token = new Token(
                type: self::TOKEN_EOF,
                value: '',
                location: new Location($this->line, $this->column, 1),
            );
        }

        if (!isset($token)) {
            throw new \RuntimeException('Unexpected token');
        }

        return $token;
    }

    private function lex(): array
    {
        $tokens = [];

        while ($token = $this->nextToken()) {
            $tokens[] = $token;
            if ($token->type === self::TOKEN_EOF) {
                break;
            }
        }

        return [];
    }
}
