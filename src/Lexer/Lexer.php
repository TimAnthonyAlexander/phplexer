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
    public const TOKEN_FUNCTION = 12;
    public const TOKEN_CLASS = 13;
    public const TOKEN_EXTENDS = 14;
    public const TOKEN_IMPLEMENTS = 15;
    public const TOKEN_RETURN = 16;
    public const TOKEN_PUBLIC = 17;
    public const TOKEN_PROTECTED = 18;
    public const TOKEN_PRIVATE = 19;
    public const TOKEN_BACKSLASH = 20;
    public const TOKEN_COMMA = 21;
    public const TOKEN_BOOL = 22;
    public const TOKEN_DOT = 23;
    public const TOKEN_VARIABLE = 24;
    public const TOKEN_COLON = 25;
    public const TOKEN_NEW = 26;
    public const TOKEN_NULL = 27;
    public const TOKEN_DASH = 28;
    public const TOKEN_PLUS = 29;
    public const TOKEN_EQUAL = 30;
    public const TOKEN_NOT_EQUAL = 31;
    public const TOKEN_PLUS_EQUAL = 32;
    public const TOKEN_EQUAL_EQUAL = 33;
    public const TOKEN_EQUAL_EQUAL_EQUAL = 34;
    public const TOKEN_NOT_EQUAL_EQUAL = 35;
    public const TOKEN_DASH_EQUAL = 36;
    public const TOKEN_DASH_GT = 37;
    public const TOKEN_DASH_LT = 38;
    public const TOKEN_DASH_GT_EQUAL = 39;
    public const TOKEN_DASH_LT_EQUAL = 40;
    public const TOKEN_DASH_DASH = 41;
    public const TOKEN_DASH_COLON = 42;
    public const TOKEN_DOUBLE_AND = 43;
    public const TOKEN_DOUBLE_OR = 44;
    public const TOKEN_AND = 45;
    public const TOKEN_OR = 46;
    public const TOKEN_DOUBLE_COLON = 47;
    public const TOKEN_NAMESPACE = 48;

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
        return (IntlChar::isalnum($char) ?? false) || $char === '_';
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
            return $this->lexAlpha();
        }

        if ($this->getCurrentChar() === "$") {
            return $this->lexVariable();
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

        // Backslash
        if ($this->getCurrentChar() === '\\') {
            $token = new Token(
                type: self::TOKEN_BACKSLASH,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        // Comma
        if ($this->getCurrentChar() === ',') {
            $token = new Token(
                type: self::TOKEN_COMMA,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        // Bool
        if ($this->getCurrentChar(4) === 'true') {
            $token = new Token(
                type: self::TOKEN_BOOL,
                value: $this->getCurrentChar(4),
                location: new Location($this->line, $this->column, 4),
            );
            $this->moveCursor(4);
            return $token;
        }

        // Dot
        if ($this->getCurrentChar() === '.') {
            $token = new Token(
                type: self::TOKEN_DOT,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            return $token;
        }

        // Colon
        if ($this->getCurrentChar() === ':') {
            $start = $this->cursor;
            $token = new Token(
                type: self::TOKEN_COLON,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            if ($this->getCurrentChar() === ':') {
                $this->moveCursor();
                $token = new Token(
                    type: self::TOKEN_DOUBLE_COLON,
                    value: substr($this->file->contents, $start, $this->cursor - $start),
                    location: new Location($this->line, $this->column, 2),
                );
            }
            return $token;
        }

        // Dash
        if ($this->getCurrentChar() === '-') { #
            $start = $this->cursor;
            $token = new Token(
                type: self::TOKEN_DASH,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            if ($this->getCurrentChar() === '=') {
                $this->moveCursor();
                $token = new Token(
                    type: self::TOKEN_DASH_EQUAL,
                    value: substr($this->file->contents, $start, $this->cursor - $start),
                    location: new Location($this->line, $this->column, 2),
                );
            } else if ($this->getCurrentChar() === '>') {
                $this->moveCursor();
                $token = new Token(
                    type: self::TOKEN_DASH_GT,
                    value: substr($this->file->contents, $start, $this->cursor - $start),
                    location: new Location($this->line, $this->column, 2),
                );
            } else if ($this->getCurrentChar() === '-') {
                $this->moveCursor();
                $token = new Token(
                    type: self::TOKEN_DASH_DASH,
                    value: substr($this->file->contents, $start, $this->cursor - $start),
                    location: new Location($this->line, $this->column, 2),
                );
            } else if ($this->getCurrentChar() === '<') {
                $this->moveCursor();
                $token = new Token(
                    type: self::TOKEN_DASH_LT,
                    value: substr($this->file->contents, $start, $this->cursor - $start),
                    location: new Location($this->line, $this->column, 2),
                );
            } else if ($this->getCurrentChar() === ':') {
                $this->moveCursor();
                $token = new Token(
                    type: self::TOKEN_DASH_COLON,
                    value: substr($this->file->contents, $start, $this->cursor - $start),
                    location: new Location($this->line, $this->column, 2),
                );
            }
            return $token;
        }

        // Double and
        if ($this->getCurrentChar() === '&') {
            $token = new Token(
                type: self::TOKEN_AND,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            if ($this->getCurrentChar(2) === '&&') {
                $token = new Token(
                    type: self::TOKEN_DOUBLE_AND,
                    value: $this->getCurrentChar(2),
                    location: new Location($this->line, $this->column, 2),
                );
                $this->moveCursor(2);
            }
            return $token;
        }

        // Double or
        if ($this->getCurrentChar() === '|') {
            $token = new Token(
                type: self::TOKEN_OR,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            if ($this->getCurrentChar(2) === '||') {
                $token = new Token(
                    type: self::TOKEN_DOUBLE_OR,
                    value: $this->getCurrentChar(2),
                    location: new Location($this->line, $this->column, 2),
                );
                $this->moveCursor(2);
            }
            return $token;
        }

        // Plus
        if ($this->getCurrentChar() === '+') {
            $token = new Token(
                type: self::TOKEN_PLUS,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            if ($this->getCurrentChar() === '=') {
                $token = new Token(
                    type: self::TOKEN_PLUS_EQUAL,
                    value: $this->getCurrentChar(),
                    location: new Location($this->line, $this->column, 1),
                );
                $this->moveCursor();
            }
            return $token;
        }

        // Equal
        if ($this->getCurrentChar() === '=') {
            $token = new Token(
                type: self::TOKEN_EQUAL,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();
            if ($this->getCurrentChar() === '=') {
                $token = new Token(
                    type: self::TOKEN_EQUAL_EQUAL,
                    value: $this->getCurrentChar(),
                    location: new Location($this->line, $this->column, 1),
                );
                $this->moveCursor();
                if ($this->getCurrentChar() === '=') {
                    $token = new Token(
                        type: self::TOKEN_EQUAL_EQUAL_EQUAL,
                        value: $this->getCurrentChar(),
                        location: new Location($this->line, $this->column, 1),
                    );
                    $this->moveCursor();
                }
            }
            return $token;
        }

        // Not equal
        if ($this->getCurrentChar() === '!') {
            $token = new Token(
                type: self::TOKEN_NOT_EQUAL,
                value: $this->getCurrentChar(),
                location: new Location($this->line, $this->column, 1),
            );
            $this->moveCursor();

            if ($this->getCurrentChar() === '=') {
                $token = new Token(
                    type: self::TOKEN_NOT_EQUAL_EQUAL,
                    value: $this->getCurrentChar(),
                    location: new Location($this->line, $this->column, 1),
                );
                $this->moveCursor();
            }
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

    private function lexAlpha(): Token
    {
        $start = $this->cursor;

        // Namespace
        if ($this->getCurrentChar(9) === 'namespace') {
            $this->moveCursor(9);
            return new Token(
                type: self::TOKEN_NAMESPACE,
                value: 'namespace',
                location: new Location($this->line, $this->column, 9),
            );
        }

        // Check if class keyword
        if ($this->getCurrentChar(5) === 'class') {
            $this->moveCursor(5);
            return new Token(
                type: self::TOKEN_CLASS,
                value: 'class',
                location: new Location($this->line, $this->column, 5),
            );
        }

        // Check if extends keyword
        if ($this->getCurrentChar(7) === 'extends') {
            $this->moveCursor(7);
            return new Token(
                type: self::TOKEN_EXTENDS,
                value: 'extends',
                location: new Location($this->line, $this->column, 7),
            );
        }

        // Check if implements keyword
        if ($this->getCurrentChar(10) === 'implements') {
            $this->moveCursor(10);
            return new Token(
                type: self::TOKEN_IMPLEMENTS,
                value: 'implements',
                location: new Location($this->line, $this->column, 10),
            );
        }

        // Check if public keyword
        if ($this->getCurrentChar(6) === 'public') {
            $this->moveCursor(6);
            return new Token(
                type: self::TOKEN_PUBLIC,
                value: 'public',
                location: new Location($this->line, $this->column, 6),
            );
        }

        // Check if protected keyword
        if ($this->getCurrentChar(9) === 'protected') {
            $this->moveCursor(9);
            return new Token(
                type: self::TOKEN_PROTECTED,
                value: 'protected',
                location: new Location($this->line, $this->column, 9),
            );
        }

        // Check if private keyword
        if ($this->getCurrentChar(7) === 'private') {
            $this->moveCursor(7);
            return new Token(
                type: self::TOKEN_PRIVATE,
                value: 'private',
                location: new Location($this->line, $this->column, 7),
            );
        }

        // Check if new
        if ($this->getCurrentChar(3) === 'new') {
            $this->moveCursor(3);
            return new Token(
                type: self::TOKEN_NEW,
                value: 'new',
                location: new Location($this->line, $this->column, 3),
            );
        }

        // Check if null
        if (strtolower($this->getCurrentChar(4)) === 'null') {
            $this->moveCursor(4);
            return new Token(
                type: self::TOKEN_NULL,
                value: 'null',
                location: new Location($this->line, $this->column, 4),
            );
        }

        while (!self::isEmpty($this->getCurrentChar()) && self::isAlphaNumeric($this->getCurrentChar())) {
            $this->moveCursor();
        }

        return new Token(
            type: self::TOKEN_STRING,
            value: substr($this->file->contents, $start, $this->cursor - $start),
            location: new Location($this->line, $this->column, $this->cursor - $start),
        );
    }

    private function lexVariable(): Token
    {
        $start = $this->cursor;
        $this->moveCursor();

        while (!self::isEmpty($this->getCurrentChar()) && self::isAlphaNumeric($this->getCurrentChar())) {
            $this->moveCursor();
        }
        return new Token(
            type: self::TOKEN_VARIABLE,
            value: substr($this->file->contents, $start, $this->cursor - $start),
            location: new Location($this->line, $this->column, $this->cursor - $start),
        );
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
