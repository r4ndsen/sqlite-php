<?php

declare(strict_types=1);

namespace r4ndsen\SQLite;

use r4ndsen\SQLite\Exception\MissingParameterException;

final class QueryParser
{
    // Placeholders are never replaced in query parts matching this regex.
    private const string SKIP_BINDING_PARAM_REGEX = '#^(?:[\'"`\[]|--|/\*)#';

    // String literals, quoted identifiers and comments as SQLite reads them (quotes are escaped by doubling them).
    private const string STATEMENT_SPLIT_REGEX = <<<'REGEX'
        #('(?:[^']|'')*'|"(?:[^"]|"")*"|`(?:[^`]|``)*`|\[[^\]]*\]|--[^\n]*|/\*.*?(?:\*/|\z))#s
        REGEX;

    // How many times has a named placeholder been used?
    private array $count = [
        '__' => null,
    ];
    private string $finalStatement;
    private array $finalValues = [];
    private int $num = 0;

    public function __construct(
        private readonly string $statement,
        private array $values = [],
    ) {
        $this->rebuild();
    }

    public function getStatement(): string
    {
        return $this->finalStatement;
    }

    public function getValues(): array
    {
        return $this->finalValues;
    }

    private function expandNamedPlaceholder(string $prefix, array $values): string
    {
        $i = 0;
        $expanded = [];
        foreach ($values as $value) {
            $name = sprintf('%s_%u', $prefix, $i++);
            $expanded[] = ':' . $name;
            $this->finalValues[$name] = $value;
        }

        return implode(', ', $expanded);
    }

    // Given a query string, split it into parts
    private function getParts(string $queryString): array
    {
        return (array) preg_split(
            pattern: self::STATEMENT_SPLIT_REGEX,
            subject: $queryString,
            flags: PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );
    }

    // Given an original placeholder name, return a replacement name.
    private function getPlaceholderName(string $placeholder): string
    {
        if (!isset($this->count[$placeholder])) {
            $this->count[$placeholder] = 0;

            return $placeholder;
        }

        $count = ++$this->count[$placeholder];

        return $placeholder . '__' . $count;
    }

    // Bind or quote a named placeholder in a query subpart
    private function prepareNamedPlaceholder(string $sub): string
    {
        $orig = substr($sub, 1);
        if (\array_key_exists($orig, $this->values) === false) {
            throw new MissingParameterException(sprintf("Parameter '%s' is missing from the bound values", $orig));
        }

        $name = $this->getPlaceholderName($orig);

        // is the corresponding data element an array?
        $bind_array = \is_array($this->values[$orig]);
        if ($bind_array) {
            // expand to multiple placeholders
            return $this->expandNamedPlaceholder($name, $this->values[$orig]);
        }

        // not an array, retain the placeholder for later
        $this->finalValues[$name] = $this->values[$orig];

        return ':' . $name;
    }

    // Bind or quote a numbered placeholder ("?" or "?NNN") in a query subpart
    private function prepareNumberedPlaceholder(string $sub): string
    {
        // a plain "?" takes the number following the largest one assigned so far
        $num = $sub === '?' ? $this->num + 1 : (int) substr($sub, 1);
        $this->num = max($this->num, $num);

        if (\array_key_exists($num, $this->values) === false) {
            throw new MissingParameterException(sprintf('Parameter %u is missing from the bound values', $num));
        }

        $expanded = [];
        $values = (array) $this->values[$num];
        if ($this->values[$num] === null) {
            $values[] = null;
        }
        foreach ($values as $value) {
            $count = ++$this->count['__'];
            $name = '__' . $count;
            $expanded[] = ':' . $name;
            $this->finalValues[$name] = $value;
        }

        return implode(', ', $expanded);
    }

    // Prepares the sub-parts of a query with placeholders
    private function prepareValuePlaceholders(array $subs): string
    {
        $str = '';
        foreach ($subs as $sub) {
            $str .= match ($sub[0]) {
                '?'     => $this->prepareNumberedPlaceholder($sub),
                ':'     => $this->prepareNamedPlaceholder($sub),
                default => $sub,
            };
        }

        return $str;
    }

    private function rebuild(): void
    {
        // match standard PDO execute() behavior of zero-indexed arrays
        if (\array_key_exists(0, $this->values)) {
            array_unshift($this->values, null);
        }

        $this->finalStatement = $this->rebuildStatement($this->statement);
    }

    // Rebuilds a single statement part
    private function rebuildPart(string $part): string
    {
        if (preg_match(self::SKIP_BINDING_PARAM_REGEX, $part)) {
            return $part;
        }

        // split into sub-parts by ":name", "?" and "?NNN"
        $subs = (array) preg_split(
            '/(?<!:)(:[a-zA-Z_]\w*)|(\?\d*)/u',
            $part,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        return $this->prepareValuePlaceholders($subs);
    }

    private function rebuildParts(array $parts): string
    {
        $statement = '';
        foreach ($parts as $part) {
            $statement .= $this->rebuildPart($part);
        }

        return $statement;
    }

    private function rebuildStatement(string $statement): string
    {
        $parts = $this->getParts($statement);

        return $this->rebuildParts($parts);
    }
}
