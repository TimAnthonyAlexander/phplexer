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
    public const TOKEN_PHP_OPEN = 10;
    public const TOKEN_SEMICOLON = 11;

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
        return IntlChar::isspace($char) ?? false;
    }

    private static function isDigit(string $char): bool
    {
        return IntlChar::isdigit($char) ?? false;
    }

    private static function isAlpha(string $char): bool
    {
        return IntlChar::isalpha($char) ?? false;
    }

    private static function isAlphaNumeric(string $char): bool
    {
        return IntlChar::isalnum($char) ?? false;
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

    private function moveCursor(int $n = 1): void
    {
        foreach (range(0, $n - 1) as $i) {
            if (!isset($this->file->contents[$this->cursor + $i])) {
                return;
            }

            $this->cursor++;
            $this->column++;

            if ($this->getCurrentChar(1) === "\n") {
                $this->line++;
                $this->column = 1;
            }
        }
    }

    private function nextToken(): Token
    {
        while (self::isEmpty($this->getCurrentChar())) {
            $this->moveCursor();
        }

        // alpha
        if (self::isAlpha($this->getCurrentChar())) {
            $start = $this->cursor;
            while (!self::isEmpty($this->getCurrentChar()) && self::isAlphaNumeric($this->getCurrentChar())) {
                $this->moveCursor();
            }
            return new Token(
                type: self::TOKEN_STRING,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // number
        if (self::isDigit($this->getCurrentChar())) {
            $start = $this->cursor;
            while (!self::isEmpty($this->getCurrentChar()) && self::isDigit($this->getCurrentChar())) {
                $this->moveCursor();
            }
            return new Token(
                type: self::TOKEN_NUMBER,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // string literal
        if (self::isStringLiteral($this->getCurrentChar())) {
            $start = $this->cursor;
            $this->moveCursor();

            while (!self::isStringLiteral($this->getCurrentChar())) {
                $this->moveCursor();
            }

            $this->moveCursor();

            return new Token(
                type: self::TOKEN_STRING_LITERAL,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // comment
        if (self::isComment($this->getCurrentChar())) {
            $start = $this->cursor;
            while ($this->getCurrentChar(1) !== "\n") {
                $this->moveCursor();
            }
            return new Token(
                type: self::TOKEN_COMMENT,
                value: substr($this->file->contents, $start, $this->cursor - $start),
                location: new Location($this->line, $this->column, $this->cursor - $start),
            );
        }

        // Curly braces
        if ($this->getCurrentChar() === '{') {
            $token = new Token(
                type: self::TOKEN_OCURLY,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        if ($this->getCurrentChar() === '}') {
            $token = new Token(
                type: self::TOKEN_CCURLY,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        // Parentheses
        if ($this->getCurrentChar() === '(') {
            $token = new Token(
                type: self::TOKEN_OPAREN,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        if ($this->getCurrentChar() === ')') {
            $token = new Token(
                type: self::TOKEN_CPAREN,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        // Semi-colon
        if ($this->getCurrentChar() === ';') {
            $token = new Token(
                type: self::TOKEN_SEMICOLON,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        // PHP open
        if ($this->getCurrentChar(5) === '<?php') {
            $token = new Token(
                type: self::TOKEN_PHP_OPEN,
                value: $this->getCurrentChar(5),
                location: new Location($this->line, $this->column, 5),
            );
            $this->moveCursor(5);
            return $token;
        }

        // EOF
        if ($this->getCurrentChar() === '') {
            return new Token(
                type: self::TOKEN_EOF,
                value: '',
                location: new Location($this->line, $this->column, 1),
            );
        }

        throw new \RuntimeException('Unexpected token "' . $this->getCurrentChar() . '" at ' . $this->line . ':' . $this->column . ' (' . $this->cursor . ')');
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

        return $tokens;
    }
}
