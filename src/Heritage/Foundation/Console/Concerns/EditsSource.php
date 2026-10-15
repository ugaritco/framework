<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use Heritage\Database\Console\Field;

/**
 * Adds entries to existing PHP source without rewriting it: an item to an array or a parameter list,
 * a member before the end of a class, an import. Each edit keeps the file's indentation and line
 * endings, and reports a place it cannot find by returning null.
 */
trait EditsSource
{
    /**
     * Add an item to the array or parameter list opened by the given pattern.
     *
     * @param  string  $source
     * @param  string  $opening  A regex matching up to and including the opening "[" or "(".
     * @param  string  $item  The item without its trailing comma; extra lines are indented relative to it.
     * @param  string|null  $before  Insert before the item line containing this text, when there is one.
     * @return string|null The edited source, or null when the list is not found.
     */
    protected function addToList(string $source, string $opening, string $item, ?string $before = null): ?string
    {
        if (! preg_match($opening, $source, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $open = $match[0][1] + strlen($match[0][0]);
        $close = $this->closingBracket($source, $open, substr($match[0][0], -1));

        if ($close === null) {
            return null;
        }

        $inner = substr($source, $open, $close - $open);
        $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";

        if (! str_contains($inner, "\n")) {
            if ($before !== null && ($position = strpos($inner, $before)) !== false) {
                return substr_replace($source, $item.', ', $open + $position, 0);
            }

            $content = rtrim($inner);
            $separator = trim($content) === '' ? '' : (str_ends_with($content, ',') ? ' ' : ', ');

            return substr_replace($source, $separator.$item, $open + strlen($content), 0);
        }

        $lineStart = strrpos(substr($source, 0, $close), "\n") + 1;
        $closingIndent = substr($source, $lineStart, $close - $lineStart);

        if (trim($closingIndent) !== '') {
            return null;
        }

        $indent = $closingIndent.'    ';
        $body = implode($eol, array_map(
            fn (string $line) => $line === '' ? '' : $indent.$line,
            explode("\n", str_replace("\r\n", "\n", $item))
        ));
        $lines = $body.','.$eol;

        if (trim($inner) === '//') {
            return substr_replace($source, $eol.$lines.$closingIndent, $open, $close - $open);
        }

        $spaced = (bool) preg_match('/,\r?\n[ \t]*\r?\n/', $inner);

        if ($before !== null && ($position = strpos($inner, $before)) !== false) {
            $at = strrpos(substr($source, 0, $open + $position), "\n") + 1;

            return substr_replace($source, $lines.($spaced ? $eol : ''), $at, 0);
        }

        if ($spaced) {
            $lines = $eol.$lines;
        }

        $content = rtrim($inner);
        $lastLine = trim(substr($content, (int) strrpos($content, "\n")));

        if ($content !== '' && ! str_ends_with($content, ',') && ! str_starts_with($lastLine, '//')) {
            $source = substr_replace($source, ',', $open + strlen($content), 0);
            $lineStart++;
        }

        return substr_replace($source, $lines, $lineStart, 0);
    }

    /**
     * The items of the array or parameter list opened by the given pattern, as written.
     *
     * @param  string  $source
     * @param  string  $opening  A regex matching up to and including the opening "[" or "(".
     * @return string|null The source between the brackets, or null when the list is not found.
     */
    protected function listContent(string $source, string $opening): ?string
    {
        if (! preg_match($opening, $source, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $open = $match[0][1] + strlen($match[0][0]);
        $close = $this->closingBracket($source, $open, substr($match[0][0], -1));

        return $close === null ? null : substr($source, $open, $close - $open);
    }

    /**
     * Add members (a method, a property) before the closing brace of the file's last class.
     *
     * @param  string  $source
     * @param  string  $members  The members, indented as class members.
     * @return string|null
     */
    protected function addToClass(string $source, string $members): ?string
    {
        $end = strrpos($source, '}');

        if ($end === false) {
            return null;
        }

        $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";
        $members = str_replace("\n", $eol, trim(str_replace("\r\n", "\n", $members), "\n"));

        return rtrim(substr($source, 0, $end)).$eol.$eol.$members.$eol.substr($source, $end);
    }

    /**
     * Add the hasMany() inverse of a foreign key to the source of its related model, unless the
     * model has a method of that name.
     *
     * @param  string  $source  The related model's source.
     * @param  Field  $field  The foreign key.
     * @param  string  $owner  The model holding the foreign key, in the related model's folder.
     * @return string
     */
    protected function addHasMany(string $source, Field $field, string $owner): string
    {
        if (preg_match('/function\s+'.preg_quote($field->inverse($owner), '/').'\s*\(/', $source)) {
            return $source;
        }

        $edited = $this->addToClass($source, $field->hasMany($owner));

        return $edited === null ? $source : $this->addImport($edited, 'Heritage\\Database\\Eloquent\\Relations\\HasMany');
    }

    /**
     * Import a class in its alphabetical place among the file's imports, unless it is imported already.
     *
     * @param  string  $source
     * @param  string  $class
     * @return string
     */
    protected function addImport(string $source, string $class): string
    {
        if (preg_match('/^use '.preg_quote($class, '/').';/m', $source)) {
            return $source;
        }

        $eol = str_contains($source, "\r\n") ? "\r\n" : "\n";
        $import = "use {$class};";

        if (preg_match_all('/^use [^;]+;/m', $source, $uses, PREG_OFFSET_CAPTURE)) {
            foreach ($uses[0] as [$use, $offset]) {
                if (strcmp($import, $use) < 0) {
                    return substr_replace($source, $import.$eol, $offset, 0);
                }
            }

            [$last, $offset] = end($uses[0]);

            return substr_replace($source, $eol.$import, $offset + strlen($last), 0);
        }

        return preg_replace('/^(namespace [^;]+;)/m', '$1'.$eol.$eol."use {$class};", $source, 1);
    }

    /**
     * The position of the bracket closing the one opened just before the given offset, skipping
     * strings and comments.
     *
     * @param  string  $source
     * @param  int  $offset  The position right after the opening bracket.
     * @param  string  $opening  "[" or "(".
     * @return int|null
     */
    protected function closingBracket(string $source, int $offset, string $opening): ?int
    {
        $closing = ['(' => ')', '[' => ']'][$opening];
        $depth = 1;
        $length = strlen($source);

        for ($i = $offset; $i < $length; $i++) {
            $char = $source[$i];

            if ($char === '\'' || $char === '"' || $char === '`') {
                for ($i++; $i < $length && $source[$i] !== $char; $i++) {
                    if ($source[$i] === '\\') {
                        $i++;
                    }
                }
            } elseif ($char === '/' && ($source[$i + 1] ?? '') === '/') {
                $i = ($newline = strpos($source, "\n", $i)) === false ? $length : $newline;
            } elseif ($char === '/' && ($source[$i + 1] ?? '') === '*') {
                $i = ($end = strpos($source, '*/', $i + 2)) === false ? $length : $end + 1;
            } elseif ($char === $opening) {
                $depth++;
            } elseif ($char === $closing && --$depth === 0) {
                return $i;
            }
        }

        return null;
    }
}
