<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui\Stacks;

use Heritage\Foundation\Console\Ui\Blueprint;

/**
 * A frontend stack the panel is generated in (Vue, React, ...): the files it writes from its stubs
 * (under stubs/{name}), their placeholders, and the edits registering them in the application.
 *
 * Paths of written files are relative to the artifact folder (resources/js/apps/panel/artifacts/{artifact}).
 * A file is given as ['path' => string, 'stub' => string, 'replace' => array<string, string|bool>,
 * 'ensure' => bool], 'replace' adding to the shared placeholders and 'ensure' writing it only when
 * it is missing. A placeholder named "@flag" keeps or drops the "[[ @flag ]] … [[ @end ]]" blocks
 * of a stub.
 *
 * An edit adds an item to a list of an existing file, given as ['path' => string (relative to the
 * application), 'opening' => regex, 'key' => string|array<int, string>, 'item' => string,
 * 'place' => string, 'trailingComma' => bool, 'icons' => array<int, string>, 'imports' =>
 * array<string, string>]: the item is added unless the list holds its key (or one of its keys);
 * the icons, and each import whose key is absent, are added when the item is.
 */
interface Stack
{
    /**
     * The name of the stack, also the folder of its stubs.
     *
     * @return string
     */
    public function name(): string;

    /**
     * Whether an application uses the stack, from the dependencies of its package.json.
     *
     * @param  array<string, string>  $dependencies
     * @return bool
     */
    public function detects(array $dependencies): bool;

    /**
     * The package the stubs import the Lucide icons from.
     *
     * @return string
     */
    public function iconPackage(): string;

    /**
     * The files of an artifact's panel.
     *
     * @param  string  $artifact
     * @param  string  $icon
     * @return array<int, array<string, mixed>>
     */
    public function panelFiles(string $artifact, string $icon): array;

    /**
     * The files of an artifact's summary page, which shows the data of its ShowArtifactCapability.
     *
     * @param  string  $artifact
     * @return array<int, array<string, mixed>>
     */
    public function summaryFiles(string $artifact): array;

    /**
     * The edits registering an artifact's panel in the application.
     *
     * @param  string  $artifact
     * @param  string  $icon
     * @param  string  $group  The group of the main sidebar listing the artifact.
     * @return array<int, array<string, mixed>>
     */
    public function panelRegistrations(string $artifact, string $icon, string $group): array;

    /**
     * The files of a model's pages, for the given operations.
     *
     * @param  Blueprint  $blueprint
     * @param  array<int, string>  $operations  Among index, show, create, edit, destroy, translations.
     * @return array<int, array<string, mixed>>
     */
    public function pageFiles(Blueprint $blueprint, array $operations): array;

    /**
     * The placeholders shared by the files of a model's pages.
     *
     * @param  Blueprint  $blueprint
     * @param  array<int, string>  $operations
     * @return array<string, string|bool>
     */
    public function pageReplacements(Blueprint $blueprint, array $operations): array;

    /**
     * The edit adding a model to its artifact's navigation.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $icon
     * @return array<string, mixed>
     */
    public function navigationEdit(Blueprint $blueprint, string $icon): array;

    /**
     * The component types, each with whether it needs a model.
     *
     * @return array<string, bool>
     */
    public function componentTypes(): array;

    /**
     * The files of a component.
     *
     * @param  string  $type
     * @param  string  $path  The component path relative to the artifact folder (or to resources/js), without extension.
     * @param  string  $importPath  The alias the component is imported by, without extension.
     * @param  Blueprint|null  $blueprint  The model the component presents, if any.
     * @return array<int, array<string, mixed>>
     */
    public function componentFiles(string $type, string $path, string $importPath, ?Blueprint $blueprint): array;
}
