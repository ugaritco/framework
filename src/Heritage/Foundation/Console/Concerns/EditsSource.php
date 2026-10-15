<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Concerns;

use Heritage\Database\Console\Field;

/**
 * Adds entries to existing PHP source without rewriting it: a member before the end of a class, an
 * import. Each edit keeps the file's indentation and line endings.
 */
trait EditsSource
{
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
}
