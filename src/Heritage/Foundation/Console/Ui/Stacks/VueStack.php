<?php

declare(strict_types=1);

namespace Heritage\Foundation\Console\Ui\Stacks;

use Heritage\Foundation\Console\Ui\Blueprint;
use Heritage\Support\Str;

/**
 * The Vue panel: single file components with vee-validate forms, zod schemas, the shared DataTable,
 * reka-ui components, laravel-vue-i18n and Wayfinder routes, as the company module's are written.
 */
class VueStack implements Stack
{
    /**
     * The Lucide icon of each field kind in the summary.
     *
     * @var array<string, string>
     */
    protected const KIND_ICONS = [
        'string' => 'Type', 'text' => 'AlignLeft', 'integer' => 'Hash', 'decimal' => 'Coins',
        'boolean' => 'ToggleLeft', 'date' => 'Calendar', 'datetime' => 'Clock', 'foreign' => 'Link2',
    ];

    /**
     * The partials of a model's pages, by the operation they belong to.
     *
     * @var array<string, string>
     */
    protected const PARTIALS = [
        'index/Index.vue' => 'index', 'index/data.ts' => 'index', 'index/columns.ts' => 'index',
        'store/Dialog.vue' => 'create', 'store/Store.vue' => 'create', 'store/form.ts' => 'create',
        'update/Dialog.vue' => 'edit', 'update/Update.vue' => 'edit', 'update/form.ts' => 'edit',
        'destroy/Dialog.vue' => 'destroy', 'destroy/Destroy.vue' => 'destroy', 'destroy/form.ts' => 'destroy',
        'show/Dialog.vue' => 'show', 'summary/Summary.vue' => 'show',
    ];

    /**
     * The partials of a model's translations tab.
     *
     * @var array<int, string>
     */
    protected const TRANSLATION_PARTIALS = [
        'index.ts', 'index/Index.vue', 'index/columns.ts',
        'store/Dialog.vue', 'store/Store.vue', 'store/form.ts',
        'update/Dialog.vue', 'update/Update.vue', 'update/form.ts',
        'destroy/Dialog.vue', 'destroy/Destroy.vue', 'destroy/form.ts',
        'show/Dialog.vue', 'summary/Summary.vue',
    ];

    /**
     * {@inheritdoc}
     */
    public function name(): string
    {
        return 'vue';
    }

    /**
     * {@inheritdoc}
     */
    public function detects(array $dependencies): bool
    {
        return isset($dependencies['vue']) || isset($dependencies['@inertiajs/vue3']);
    }

    /**
     * {@inheritdoc}
     */
    public function iconPackage(): string
    {
        return 'lucide-vue-next';
    }

    /**
     * {@inheritdoc}
     */
    public function panelFiles(string $module, string $icon): array
    {
        $replace = [...$this->moduleReplacements($module), '[[ icon ]]' => $icon];

        return array_map(fn (array $file) => [...$file, 'replace' => $replace], [
            ['path' => 'navigation.ts', 'stub' => 'vue/panel/navigation.ts'],
            ['path' => 'layouts/'.Str::studly($module).'Layout.vue', 'stub' => 'vue/panel/Layout.vue'],
            ['path' => 'layouts/index.ts', 'stub' => 'vue/panel/layouts.index.ts'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function summaryFiles(string $module): array
    {
        $replace = $this->moduleReplacements($module);

        return array_map(fn (array $file) => [...$file, 'replace' => $replace], [
            ['path' => 'pages/artifact/Show.vue', 'stub' => 'vue/panel/Show.vue'],
            ['path' => 'types/artifact/summary.d.ts', 'stub' => 'vue/panel/summary.d.ts'],
        ]);
    }

    /**
     * The placeholders naming a panel module in its stubs.
     *
     * @param  string  $module
     * @return array<string, string>
     */
    protected function moduleReplacements(string $module): array
    {
        return [
            '[[ module ]]' => $module,
            '[[ Module ]]' => Str::studly($module),
            '[[ moduleCamel ]]' => Str::camel($module),
            '[[ title ]]' => Str::headline($module),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function panelRegistrations(string $module, string $icon, string $group): array
    {
        return [
            [
                'path' => 'resources/js/app.ts',
                'opening' => '/import\.meta\.glob<DefineComponent>\(\[/',
                'key' => "modules/{$module}/pages",
                'item' => "'./apps/panel/modules/{$module}/pages/**/*.vue'",
                'place' => 'the module pages glob',
            ],
            [
                'path' => 'vite.config.ts',
                'opening' => '/alias:\s*\{/',
                'key' => "'@module/{$module}'",
                'item' => "'@module/{$module}': path.resolve(projectRoot, 'resources/js/apps/panel/modules/{$module}')",
                'place' => 'resolve.alias',
            ],
            [
                'path' => 'vite.config.ts',
                'opening' => '/additionalLangPaths:\s*\[/',
                'key' => "artifacts/{$module}/lang",
                'item' => "'artifacts/{$module}/lang'",
                'place' => 'i18n additionalLangPaths',
            ],
            [
                'path' => 'tsconfig.json',
                'opening' => '/"paths":\s*\{/',
                'key' => "\"@module/{$module}/*\"",
                'item' => "\"@module/{$module}/*\": [\n    \"./resources/js/apps/panel/modules/{$module}/*\"\n]",
                'place' => 'compilerOptions.paths',
                'trailingComma' => false,
            ],
            [
                'path' => 'resources/js/apps/panel/composables/usePanelModuleNav.ts',
                'opening' => "/key: '".preg_quote($group, '/')."',[\\s\\S]*?items: \\[/",
                'key' => "'/panel/artifact/{$module}'",
                'item' => "{\n    title: '".Str::headline($module)."',\n    titleKey: '{$module}::artifact.name',\n    href: '/panel/artifact/{$module}',\n    icon: {$icon},\n}",
                'place' => "the [{$group}] group",
                'icons' => [$icon],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function pageFiles(Blueprint $blueprint, array $operations): array
    {
        $model = $blueprint->model;
        $kebab = Str::kebab($model);
        $pages = "pages/{$kebab}";
        $files = [['path' => "types/{$kebab}/{$kebab}.d.ts", 'stub' => 'vue/pages/types.d.ts']];

        foreach (['Index' => 'index', 'Create' => 'create', 'Edit' => 'edit', 'Show' => 'show'] as $page => $operation) {
            if (in_array($operation, $operations, true)) {
                $files[] = ['path' => "{$pages}/{$page}.vue", 'stub' => "vue/pages/{$page}.vue"];
            }
        }

        if (in_array('show', $operations, true)) {
            $files[] = ['path' => "layouts/{$model}Layout.vue", 'stub' => 'vue/pages/Layout.vue'];
        }

        $files[] = ['path' => "{$pages}/partials/index.ts", 'stub' => 'vue/pages/partials.index.ts'];

        foreach (self::PARTIALS as $partial => $operation) {
            if (! in_array($operation, $operations, true)) {
                continue;
            }

            if ($blueprint->isTranslatable() && in_array($operation, ['create', 'edit'], true) && ! str_ends_with($partial, 'Dialog.vue')) {
                array_push($files, ...$this->translatableFormFiles($blueprint, "{$pages}/partials", $partial));

                continue;
            }

            $files[] = [
                'path' => "{$pages}/partials/".str_replace(['index/data.ts', 'index/columns.ts'], ['index/data/data.ts', 'index/data/columns.ts'], $partial),
                'stub' => 'vue/pages/partials/'.$partial,
            ];
        }

        if ($blueprint->isTranslatable()) {
            $files[] = ['path' => "types/{$kebab}/{$kebab}-translation.d.ts", 'stub' => 'vue/translation/types.d.ts'];

            if (in_array('translations', $operations, true)) {
                foreach (self::TRANSLATION_PARTIALS as $partial) {
                    $files[] = [
                        'path' => "{$pages}/partials/translation/".str_replace('index/columns.ts', 'index/data/columns.ts', $partial),
                        'stub' => "vue/translation/{$partial}",
                    ];
                }
            }
        }

        return [...$files, ...$this->relationFiles($blueprint)];
    }

    /**
     * {@inheritdoc}
     */
    public function pageReplacements(Blueprint $blueprint, array $operations): array
    {
        $artifact = $blueprint->artifact;
        $model = $blueprint->model;
        $kebab = Str::kebab($model);
        $camel = Str::camel($model);
        $lang = $blueprint->lang();
        $label = $blueprint->labelField();
        $confirm = $blueprint->confirmField();
        $partials = "@module/{$artifact}/pages/{$kebab}/partials";
        $has = fn (string $operation) => in_array($operation, $operations, true);
        $needsNumber = (bool) array_filter($blueprint->fields, fn (array $field) => in_array($field['kind'], ['integer', 'decimal'], true));
        $actions = array_filter(['DestroyDialog' => $has('destroy'), 'ShowDialog' => $has('show'), 'UpdateDialog' => $has('edit')]);

        return [
            '[[ module ]]' => $artifact,
            '[[ Module ]]' => Str::studly($artifact),
            '[[ moduleCamel ]]' => Str::camel($artifact),
            '[[ Model ]]' => $model,
            '[[ Models ]]' => Str::pluralStudly($model),
            '[[ model ]]' => $kebab,
            '[[ modelCamel ]]' => $camel,
            '[[ models ]]' => Str::camel(Str::pluralStudly($model)),
            '[[ label ]]' => Str::lower(Str::headline($model)),
            '[[ labels ]]' => Str::lower(Str::headline(Str::pluralStudly($model))),
            '[[ lang ]]' => $lang,
            '[[ dataLabel ]]' => $this->labelExpression('data', $label),
            '[[ propsLabel ]]' => $this->labelExpression('props.data', $label),
            '[[ entityLabel ]]' => $this->labelExpression($camel, $label),
            '[[ entityConfirm ]]' => "{$camel}.{$confirm}",
            '[[ confirmLabel ]]' => $confirm === 'id' ? 'id' : str_replace('_', ' ', $confirm),
            '[[ confirmTrans ]]' => $confirm === 'id' ? "'#'" : "trans('{$lang}.attributes.{$confirm}.label')",
            '[[ confirmTemplate ]]' => $confirm === 'id' ? "'#'" : "\$t('{$lang}.attributes.{$confirm}.label')",
            '[[ typeFields ]]' => $this->typeFields($blueprint->fields),
            '[[ formHelpers ]]' => $needsNumber
                ? "\n/** An empty input as undefined, any other value as a number. */\nconst toNumber = (value: unknown) =>\n    value === '' || value === null || value === undefined ? undefined : Number(value);\n"
                : '',
            '[[ storeValues ]]' => $this->storeValues($blueprint, $blueprint->fields),
            '[[ updateValues ]]' => $this->updateValues($blueprint, $blueprint->fields, $camel),
            '[[ schemaFields ]]' => $this->schemaFields($blueprint, $blueprint->fields),
            '[[ formImports ]]' => $this->formImports($blueprint, $blueprint->fields),
            '[[ formFields ]]' => $this->formFields($blueprint, $blueprint->fields, $lang),
            '[[ relationExports ]]' => $this->relationExports($blueprint),
            '[[ partialExports ]]' => $this->partialExports($blueprint, $operations),
            '[[ indexPartials ]]' => $has('create') ? 'Read, StoreDialog' : 'Read',
            '[[ indexHref ]]' => $has('index') ? 'index().url' : "'#'",
            '[[ layoutPartials ]]' => implode(', ', array_keys(array_filter(['DestroyDialog' => $has('destroy'), 'UpdateDialog' => $has('edit')]))),
            '[[ showDialogImports ]]' => $this->importList(array_keys(array_filter(['DestroyDialog' => $has('destroy'), 'Summary' => true, 'UpdateDialog' => $has('edit')])), $partials),
            '[[ actionImports ]]' => $actions === [] ? '' : "\n".$this->importList(array_keys($actions), $partials),
            '[[ rowActions ]]' => implode(', ', array_keys(array_filter(['quick view' => $has('show'), 'update' => $has('edit'), 'delete' => $has('destroy')]))),
            '[[ storeForm ]]' => "{$partials}/store/form",
            '[[ updateForm ]]' => "{$partials}/update/form",
            '[[ storeComponent ]]' => $blueprint->isTranslatable() ? 'StoreFeature' : 'Store',
            '[[ updateComponent ]]' => $blueprint->isTranslatable() ? 'UpdateFeature' : 'Update',
            '[[ columnsPath ]]' => "{$partials}/index/data/columns",
            '[[ columnImports ]]' => $this->columnImports($blueprint, $label, $has('show')),
            '[[ columnSetup ]]' => $this->columnSetup($blueprint),
            '[[ columns ]]' => $this->tableColumns($blueprint, $lang, $label, $has('show')),
            '[[ summaryIcons ]]' => $this->summaryIcons($blueprint, $blueprint->fields),
            '[[ summaryImports ]]' => $this->summaryImports($blueprint),
            '[[ summarySetup ]]' => $this->summarySetup($blueprint),
            '[[ summaryItems ]]' => $this->summaryItems($blueprint, $blueprint->fields, $lang, $camel),
            '@index' => $has('index'),
            '@show' => $has('show'),
            '@create' => $has('create'),
            '@edit' => $has('edit'),
            '@destroy' => $has('destroy'),
            '@translations' => $has('translations'),
            ...$this->translationReplacements($blueprint, $lang, $camel, $has('translations')),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function navigationEdit(Blueprint $blueprint, string $icon): array
    {
        $artifact = $blueprint->artifact;
        $kebab = Str::kebab($blueprint->model);
        $models = Str::camel(Str::pluralStudly($blueprint->model));

        return [
            'path' => "resources/js/apps/panel/modules/{$artifact}/navigation.ts",
            'opening' => '/'.Str::camel($artifact).'Entities = \(\): \w+\[\] => \[/',
            'key' => "href: {$models}()",
            'item' => "{\n    title: trans('{$blueprint->lang()}.plural_definite'),\n    href: {$models}(),\n    icon: {$icon},\n}",
            'place' => 'the module entities',
            'icons' => [$icon],
            'imports' => ["'@/routes/{$kebab}'" => "import { index as {$models} } from '@/routes/{$kebab}';"],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function componentTypes(): array
    {
        return ['blank' => false, 'dialog' => false, 'form' => true, 'table' => true, 'select' => true, 'summary' => true];
    }

    /**
     * {@inheritdoc}
     */
    public function componentFiles(string $type, string $path, string $importPath, ?Blueprint $blueprint): array
    {
        $name = basename($path);
        $directory = dirname($path);
        $summary = Str::headline($name).($blueprint ? ' of the '.Str::lower(Str::headline($blueprint->model)) : '');

        if ($blueprint === null) {
            return [[
                'path' => "{$path}.vue",
                'stub' => $type === 'dialog' ? 'vue/components/Dialog.vue' : 'vue/components/Blank.vue',
                'replace' => ['[[ summary ]]' => $summary],
            ]];
        }

        $model = $blueprint->model;
        $kebab = Str::kebab($model);
        $partials = "pages/{$kebab}/partials";

        return match ($type) {
            'blank', 'dialog' => [[
                'path' => "{$path}.vue",
                'stub' => $type === 'dialog' ? 'vue/components/Dialog.vue' : 'vue/components/Blank.vue',
                'replace' => ['[[ summary ]]' => $summary],
            ]],
            'form' => [
                ['path' => "{$path}.vue", 'stub' => 'vue/pages/partials/'.$this->formStub($blueprint, 'store/Store.vue'), 'replace' => ['[[ storeForm ]]' => "{$importPath}.form"]],
                ['path' => "{$path}.form.ts", 'stub' => 'vue/pages/partials/'.$this->formStub($blueprint, 'store/form.ts')],
                ...$this->ensured($this->relationFiles($blueprint)),
            ],
            'table' => [
                ['path' => "{$path}.vue", 'stub' => 'vue/pages/partials/index/Index.vue', 'replace' => ['[[ columnsPath ]]' => "{$importPath}.columns"]],
                ['path' => "{$path}.columns.ts", 'stub' => 'vue/pages/partials/index/columns.ts'],
                ...$this->ensured([['path' => "{$partials}/index/data/data.ts", 'stub' => 'vue/pages/partials/index/data.ts']]),
                ...$this->ensured($this->relationFiles($blueprint)),
            ],
            'select' => [
                [
                    'path' => "{$path}.vue",
                    'stub' => 'vue/pages/partials/relation/Select.vue',
                    'replace' => [...$this->selectReplacements($blueprint), '[[ optionsPath ]]' => dirname($importPath)."/use{$model}Options"],
                ],
                ['path' => "{$directory}/use{$model}Options.ts", 'stub' => 'vue/pages/partials/relation/useOptions.ts', 'replace' => $this->selectReplacements($blueprint)],
            ],
            'summary' => [
                ['path' => "{$path}.vue", 'stub' => 'vue/pages/partials/summary/Summary.vue'],
                ...$this->ensured($this->relationFiles($blueprint)),
            ],
        };
    }

    /**
     * The stub of a create or update partial: its variant with a section per locale for a
     * translatable model.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $partial
     * @return string
     */
    protected function formStub(Blueprint $blueprint, string $partial): string
    {
        return $blueprint->isTranslatable() && in_array($partial, ['store/Store.vue', 'store/form.ts', 'update/Update.vue', 'update/form.ts'], true)
            ? preg_replace('/\.(vue|ts)$/', '.translatable.$1', $partial)
            : $partial;
    }

    /**
     * The files of a create or update form of a translatable model, as the Branch pages are
     * written: the form of its own fields (posting to the capability route) in `store/` or
     * `update/`, which its dialog shows, and the feature form with a section per locale
     * (posting to the `{model}-feature` route) in `store/feature/` or `update/feature/`,
     * which the create and edit pages show.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $partials  The partials directory of the model's pages.
     * @param  string  $partial  The partial, as listed in PARTIALS.
     * @return array<int, array<string, mixed>>
     */
    protected function translatableFormFiles(Blueprint $blueprint, string $partials, string $partial): array
    {
        [$folder, $file] = explode('/', $partial);
        $alias = "@module/{$blueprint->artifact}/pages/".Str::kebab($blueprint->model)."/partials/{$folder}";
        $plain = $blueprint->plain();
        $formKey = $folder === 'store' ? '[[ storeForm ]]' : '[[ updateForm ]]';

        $own = [
            '[[ formFields ]]' => $this->formFields($blueprint, $plain, $blueprint->lang()),
            '[[ formImports ]]' => $this->formImports($blueprint, $plain),
            '[[ schemaFields ]]' => $this->schemaFields($blueprint, $plain),
            '[[ storeValues ]]' => $this->storeValues($blueprint, $plain),
            '[[ updateValues ]]' => $this->updateValues($blueprint, $plain, Str::camel($blueprint->model)),
            $formKey => "{$alias}/form",
        ];

        return [
            ['path' => "{$partials}/{$partial}", 'stub' => "vue/pages/partials/{$partial}", 'replace' => $own],
            [
                'path' => "{$partials}/{$folder}/feature/{$file}",
                'stub' => 'vue/pages/partials/'.$this->formStub($blueprint, $partial),
                'replace' => [$formKey => "{$alias}/feature/form"],
            ],
        ];
    }

    /**
     * The given files, written only when missing.
     *
     * @param  array<int, array<string, mixed>>  $files
     * @return array<int, array<string, mixed>>
     */
    protected function ensured(array $files): array
    {
        return array_map(fn (array $file) => [...$file, 'ensure' => true], $files);
    }

    /**
     * The placeholders of a select of the model's own records.
     *
     * @param  Blueprint  $blueprint
     * @return array<string, string>
     */
    protected function selectReplacements(Blueprint $blueprint): array
    {
        $model = $blueprint->model;

        return [
            '[[ label ]]' => 'record',
            '[[ field ]]' => Str::snake($model).'_id',
            '[[ Related ]]' => $model,
            '[[ relatedRoute ]]' => Str::kebab($model),
            '[[ relatedLabel ]]' => Str::lower(Str::headline($model)),
            '[[ relatedLabels ]]' => Str::lower(Str::headline(Str::pluralStudly($model))),
            '[[ relatedLabelField ]]' => $blueprint->labelField(),
            '[[ fieldLabel ]]' => $blueprint->lang().'.singular',
            '[[ fieldPlaceholder ]]' => $blueprint->lang().'.singular',
            '[[ requiredMark ]]' => '',
        ];
    }

    /**
     * The select and the options loader of each foreign key field.
     *
     * @param  Blueprint  $blueprint
     * @return array<int, array<string, mixed>>
     */
    protected function relationFiles(Blueprint $blueprint): array
    {
        $directory = 'pages/'.Str::kebab($blueprint->model).'/partials/relations';
        $alias = "@module/{$blueprint->artifact}/{$directory}";
        $lang = $blueprint->lang();
        $files = [];

        foreach ($blueprint->foreign() as $name => $field) {
            $related = $field['related'];
            $replace = [
                '[[ field ]]' => $name,
                '[[ Related ]]' => $related,
                '[[ relatedRoute ]]' => Str::kebab($related),
                '[[ relatedLabel ]]' => Str::lower(Str::headline($related)),
                '[[ relatedLabels ]]' => Str::lower(Str::headline(Str::pluralStudly($related))),
                '[[ relatedLabelField ]]' => $blueprint->relatedLabels[$related] ?? 'id',
                '[[ requiredMark ]]' => $field['required'] ? "\n                <span class=\"text-red\">*</span>" : '',
                '[[ optionsPath ]]' => "{$alias}/use{$related}Options",
                '[[ fieldLabel ]]' => "{$lang}.attributes.{$name}.label",
                '[[ fieldPlaceholder ]]' => "{$lang}.attributes.{$name}.placeholder",
            ];

            $files[] = ['path' => "{$directory}/".$this->selectName($name).'.vue', 'stub' => 'vue/pages/partials/relation/Select.vue', 'replace' => $replace];
            $files[] = ['path' => "{$directory}/use{$related}Options.ts", 'stub' => 'vue/pages/partials/relation/useOptions.ts', 'replace' => $replace];
        }

        return $files;
    }

    /**
     * An import of names, on one line when it fits in 80 columns.
     *
     * @param  array<int, string>  $names
     * @param  string  $from
     * @return string
     */
    protected function importList(array $names, string $from): string
    {
        $line = 'import { '.implode(', ', $names)." } from '{$from}';";

        return strlen($line) <= 80
            ? $line
            : "import {\n".implode('', array_map(fn (string $name) => "    {$name},\n", $names))."} from '{$from}';";
    }

    /**
     * The exports of the partials registry for the given operations.
     *
     * @param  Blueprint  $blueprint
     * @param  array<int, string>  $operations
     * @return string
     */
    protected function partialExports(Blueprint $blueprint, array $operations): string
    {
        $partials = "@module/{$blueprint->artifact}/pages/".Str::kebab($blueprint->model).'/partials';
        $label = Str::lower(Str::headline($blueprint->model));
        $feature = $blueprint->isTranslatable();
        $groups = [
            'destroy' => ['Delete confirmation form, and the dialog opening it', ['Destroy' => 'destroy/Destroy', 'DestroyDialog' => 'destroy/Dialog']],
            'index' => ['The '.Str::lower(Str::headline(Str::pluralStudly($blueprint->model))).' table', ['Read' => 'index/Index']],
            'show' => ['Quick view dialog', ['ShowDialog' => 'show/Dialog']],
            'create' => $feature
                ? ['Create forms (its own fields, which the dialog opens, and the feature form with its translations, which the create page shows)', ['Store' => 'store/Store', 'StoreFeature' => 'store/feature/Store', 'StoreDialog' => 'store/Dialog']]
                : ['Create form, and the dialog opening it', ['Store' => 'store/Store', 'StoreDialog' => 'store/Dialog']],
            'summary' => ["Summary of a {$label}", ['Summary' => 'summary/Summary']],
            'edit' => $feature
                ? ['Update forms (its own fields, which the dialog opens, and the feature form with its translations, which the edit page shows)', ['Update' => 'update/Update', 'UpdateFeature' => 'update/feature/Update', 'UpdateDialog' => 'update/Dialog']]
                : ['Update form, and the dialog opening it', ['Update' => 'update/Update', 'UpdateDialog' => 'update/Dialog']],
        ];
        $blocks = [];

        foreach ($groups as $operation => [$comment, $exports]) {
            if (! in_array($operation === 'summary' ? 'show' : $operation, $operations, true)) {
                continue;
            }

            $lines = ["// {$comment}"];

            foreach ($exports as $name => $file) {
                $lines[] = "export { default as {$name} } from '{$partials}/{$file}.vue';";
            }

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    /**
     * The expression naming a record: its label field, or its id when that is empty.
     *
     * @param  string  $record
     * @param  string  $label
     * @return string
     */
    protected function labelExpression(string $record, string $label): string
    {
        return $label === 'id' ? "{$record}.id" : "{$record}.{$label} || {$record}.id";
    }

    /**
     * The placeholders of a translatable model's stubs: its forms with a section per locale, the
     * translations tab of its layout and the partials of its translations; empty for the shared
     * stubs when the model is not translatable or its translations tab is not generated.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $lang
     * @param  string  $camel
     * @param  bool  $withTab  Whether the translations tab is generated.
     * @return array<string, string>
     */
    protected function translationReplacements(Blueprint $blueprint, string $lang, string $camel, bool $withTab): array
    {
        $translatable = $blueprint->translatable();
        $empty = array_fill_keys(['[[ typeImports ]]', '[[ typeTranslations ]]', '[[ tabTriggers ]]', '[[ tabContents ]]', '[[ showImports ]]', '[[ showSlots ]]'], '');

        if ($translatable === []) {
            return $empty;
        }

        $artifact = $blueprint->artifact;
        $model = $blueprint->model;
        $plain = $blueprint->plain();
        $kebab = Str::kebab($model);
        $label = $blueprint->labelField($translatable);
        $label = $label === 'id' ? array_key_first($translatable) : $label;
        $slug = isset($plain['slug']) && $translatable[$label]['kind'] === 'string';
        $values = fn (string $record, int $indent) => implode("\n", array_map(
            fn (string $name) => str_repeat(' ', $indent)."{$name}: ".($record === '' ? "''" : "{$record}.{$name} ?? ''").',',
            array_keys($translatable)
        ));
        $tab = $withTab ? [
            '[[ tabTriggers ]]' => "\n                <TabsTrigger value=\"locales\">\n                    {{ \$t('pages.translations') }}\n                </TabsTrigger>",
            '[[ tabContents ]]' => "\n\n            <TabsContent value=\"locales\" class=\"mt-4 flex-1 overflow-y-auto pr-2\">\n                <slot name=\"locales\" />\n            </TabsContent>",
            '[[ showImports ]]' => "\nimport { Index as Translations } from '@module/{$artifact}/pages/{$kebab}/partials/translation';",
            '[[ showSlots ]]' => "\n\n        <template #locales>\n            <Translations\n                :translations=\"data.translations ?? {}\"\n                :{$camel}=\"data\"\n            />\n        </template>",
        ] : [];

        return [
            ...$empty,
            '[[ typeImports ]]' => "import { {$model}Translation } from '@module/{$artifact}/types/{$kebab}/{$kebab}-translation';\n",
            '[[ typeTranslations ]]' => "\n    translations: z.record(z.string(), {$model}Translation).optional(),",
            ...$tab,
            '[[ plainStoreValues ]]' => $this->storeValues($blueprint, $plain),
            '[[ plainUpdateValues ]]' => preg_replace('/^/m', '    ', $this->updateValues($blueprint, $plain, $camel)),
            '[[ plainSchemaFields ]]' => $this->schemaFields($blueprint, $plain),
            '[[ plainFormFields ]]' => $this->formFields($blueprint, $plain, $lang),
            '[[ localeFormFields ]]' => $this->formFields($blueprint, $translatable, $lang, 'code', 4),
            '[[ localeSchemaFields ]]' => preg_replace('/^/m', '        ', $this->schemaFields($blueprint, $translatable)),
            '[[ translationFormFields ]]' => $this->formFields($blueprint, $translatable, $lang),
            '[[ translationFormImports ]]' => $this->formImports($blueprint, $translatable),
            '[[ routeParameter ]]' => Str::snake($model),
            '[[ translationParameter ]]' => Str::snake($model).'_translation',
            '[[ translationSchemaFields ]]' => $this->schemaFields($blueprint, $translatable),
            '[[ translationTypeFields ]]' => $this->typeFields($translatable),
            '[[ translationBlank ]]' => $values('', 4),
            '[[ translationShape ]]' => implode('; ', array_map(fn (string $name) => "{$name}: string", array_keys($translatable))),
            '[[ translationValues ]]' => $values('translation', 4),
            '[[ translationRecordValues ]]' => $values('translation', 22),
            '[[ translationFlatValues ]]' => $values($camel, 18),
            '[[ translationLabel ]]' => $label,
            '[[ translationLabelTitle ]]' => str_replace('_', ' ', $label),
            '[[ translationColumns ]]' => $this->translationColumns($model, $translatable, $lang, $label),
            '[[ translationSummaryIcons ]]' => $this->summaryIcons($blueprint, $translatable),
            '[[ translationSummaryItems ]]' => $this->summaryItems($blueprint, $translatable, $lang, 'translation'),
            '[[ slugImports ]]' => $slug ? "\nimport { Slugifier } from '@/utils/slug/Slugifier';" : '',
            '[[ slugVue ]]' => $slug ? ', watch' : '',
            '[[ slugSetup ]]' => $slug ? <<<TS


                /** The slug follows the {$label} of the current locale until it is typed by hand. */
                const slugifier = new Slugifier();
                const slugTyped = ref(false);
                let generatedSlug = '';

                watch(
                    () => values.translations?.[currentLocale]?.{$label},
                    (value) => {
                        if (!value || slugTyped.value) return;

                        generatedSlug = slugifier.generate(value, currentLocale);
                        setFieldValue('slug', generatedSlug);
                    },
                );

                watch(
                    () => values.slug,
                    (value) => {
                        slugTyped.value = !!value && value !== generatedSlug;
                    },
                );
                TS : '',
        ];
    }

    /**
     * The table columns of the translations: the locale, then each translated field but texts.
     *
     * @param  string  $model
     * @param  array<string, array<string, mixed>>  $fields  The translatable fields.
     * @param  string  $lang
     * @param  string  $label
     * @return string
     */
    protected function translationColumns(string $model, array $fields, string $lang, string $label): string
    {
        $blocks = [];

        foreach ($fields as $name => $field) {
            if ($field['kind'] === 'text') {
                continue;
            }

            $title = "trans('{$lang}.attributes.{$name}.label')";
            $class = $name === $label ? "{ class: 'font-bold' }" : '{}';

            $blocks[] = <<<TS
                    /** The translated {$name}. */
                    {
                        accessorKey: '{$name}',
                        header: ({ column }) =>
                            h(DataTableColumnHeader, {
                                column,
                                title: {$title},
                            }),
                        cell: ({ row }) => h('div', {$class}, String(row.getValue('{$name}') ?? '')),
                        filterFn: contains,
                        enableSorting: true,
                        enableHiding: true,
                        meta: {
                            title: {$title},
                            searchable: true,
                            filterable: false,
                            filterOption: [],
                        } as {$model}TranslationColumnMeta,
                    },
            TS;
        }

        return implode("\n\n", $blocks);
    }

    /**
     * The zod entries of the record schema.
     *
     * @param  array<string, array<string, mixed>>  $fields
     * @return string
     */
    protected function typeFields(array $fields): string
    {
        $lines = [];

        foreach ($fields as $name => $field) {
            $type = match ($field['kind']) {
                'integer', 'foreign' => 'z.number()',
                'decimal' => 'z.union([z.string(), z.number()])',
                'boolean' => 'z.boolean()',
                'json' => 'z.any()',
                default => 'z.string()',
            };

            if ($field['kind'] !== 'json' && ($field['nullable'] || $field['translatable'])) {
                $type .= '.nullable()';
            }

            $lines[] = "    {$name}: {$type},";
        }

        return implode("\n", $lines);
    }

    /**
     * The initial values of the create form.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @return string
     */
    protected function storeValues(Blueprint $blueprint, array $fields): string
    {
        $lines = [];

        foreach ($blueprint->editable($fields) as $name => $field) {
            $value = match ($field['kind']) {
                'integer', 'decimal', 'foreign' => 'undefined as number | undefined',
                'boolean' => $name === 'active' ? 'true' : 'false',
                default => "''",
            };

            $lines[] = "    {$name}: {$value},";
        }

        return implode("\n", $lines);
    }

    /**
     * The values of a record in the shape of the update form.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @param  string  $record
     * @return string
     */
    protected function updateValues(Blueprint $blueprint, array $fields, string $record): string
    {
        $lines = [];

        foreach ($blueprint->editable($fields) as $name => $field) {
            $value = "{$record}.{$name}";
            $value = match ($field['kind']) {
                'date' => "{$value}?.slice(0, 10) ?? ''",
                'datetime' => "{$value}?.slice(0, 16) ?? ''",
                'decimal' => "{$value} == null ? undefined : Number({$value})",
                'integer', 'foreign' => "{$value} ?? undefined",
                'boolean' => "{$value} ?? false",
                default => "{$value} ?? ''",
            };

            $lines[] = "    {$name}: {$value},";
        }

        return implode("\n", $lines);
    }

    /**
     * The zod entries of the create and update form schemas, bounded by the validation rules.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @return string
     */
    protected function schemaFields(Blueprint $blueprint, array $fields): string
    {
        $lines = [];

        foreach ($blueprint->editable($fields) as $name => $field) {
            $optional = ! $field['required'];
            $min = preg_match('/\bmin:(\d+)/', $field['rule'], $m) ? (int) $m[1] : null;
            $max = preg_match('/\bmax:(\d+)/', $field['rule'], $m) ? (int) $m[1] : null;

            $type = match ($field['kind']) {
                'string', 'text' => 'z.string()'
                    .($optional ? '' : '.min('.max($min ?? 1, 1).')')
                    .($max !== null ? ".max({$max})" : ''),
                'integer' => 'z.preprocess(toNumber, z.number().int()'.($optional ? '.optional()' : '').')',
                'decimal' => 'z.preprocess(toNumber, z.number()'.($optional ? '.optional()' : '').')',
                'foreign' => 'z.number()',
                'boolean' => 'z.boolean()',
                default => 'z.string()'.($optional ? '' : '.min(1)'),
            };

            if ($optional && ! in_array($field['kind'], ['integer', 'decimal'], true)) {
                $type .= '.nullable().optional()';
            }

            $lines[] = "        {$name}: {$type},";
        }

        return implode("\n", $lines);
    }

    /**
     * The imports of the form components the fields use.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @return string
     */
    protected function formImports(Blueprint $blueprint, array $fields): string
    {
        $kinds = array_column($blueprint->editable($fields), 'kind');
        $imports = [];

        if (in_array('boolean', $kinds, true)) {
            $imports[] = "import { Checkbox } from '@/components/ui/checkbox';";
        }

        if (array_intersect(['string', 'integer', 'decimal', 'date', 'datetime'], $kinds)) {
            $imports[] = "import { Input } from '@/components/ui/input';";
        }

        if (in_array('text', $kinds, true)) {
            $imports[] = "import { Textarea } from '@/components/ui/textarea';";
        }

        foreach ($blueprint->foreign($fields) as $name => $field) {
            $select = $this->selectName($name);
            $imports[] = "import {$select} from '@module/{$blueprint->artifact}/pages/".Str::kebab($blueprint->model)."/partials/relations/{$select}.vue';";
        }

        return $imports === [] ? '' : "\n".implode("\n", $imports);
    }

    /**
     * The select component of a foreign key field.
     *
     * @param  string  $name
     * @return string
     */
    protected function selectName(string $name): string
    {
        return Str::studly(Str::beforeLast($name, '_id')).'Select';
    }

    /**
     * The form field of each field, in the markup of the create and update forms.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @param  string  $lang
     * @param  string|null  $locale  The variable holding the locale code of a locale section, whose
     *                               fields are named translations.{code}.{field}.
     * @param  int  $indent  The spaces added before every line.
     * @return string
     */
    protected function formFields(Blueprint $blueprint, array $fields, string $lang, ?string $locale = null, int $indent = 0): string
    {
        $blocks = $this->fieldBlocks($blueprint, $fields, $lang);

        if ($locale !== null) {
            $blocks = array_map(fn (string $block) => preg_replace(
                '/ name="(\w+)"/',
                ' :name="`translations.${'.$locale.'}.$1`"',
                $block
            ), $blocks);
        }

        $markup = implode("\n\n", $blocks);

        return $indent > 0 ? preg_replace('/^(?=.)/m', str_repeat(' ', $indent), $markup) : $markup;
    }

    /**
     * The markup of each field's form field.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @param  string  $lang
     * @return array<int, string>
     */
    protected function fieldBlocks(Blueprint $blueprint, array $fields, string $lang): array
    {
        $blocks = [];

        foreach ($blueprint->editable($fields) as $name => $field) {
            $title = Str::headline($name);

            if ($field['kind'] === 'foreign') {
                $blocks[] = "        <!-- {$title} -->\n        <".$this->selectName($name).' />';

                continue;
            }

            $label = "{{ \$t('{$lang}.attributes.{$name}.label') }}";
            $mark = $field['required'] ? "\n                    <span class=\"text-red\">*</span>" : '';

            if ($field['kind'] === 'boolean') {
                $blocks[] = <<<VUE
                        <!-- {$title} -->
                        <FormField v-slot="{ componentField }" name="{$name}">
                            <FormItem
                                class="col-span-6 flex flex-row items-center gap-4 space-y-0"
                            >
                                <FormControl>
                                    <Checkbox v-bind="componentField" />
                                </FormControl>
                                <FormLabel>
                                    {$label}
                                </FormLabel>
                                <FormMessage />
                            </FormItem>
                        </FormField>
                VUE;

                continue;
            }

            $control = match ($field['kind']) {
                'text' => '<Textarea v-bind="componentField" />',
                'integer' => "<Input\n                        type=\"number\"\n                        step=\"1\"\n                        v-bind=\"componentField\"\n                    />",
                'decimal' => "<Input\n                        type=\"number\"\n                        step=\"0.01\"\n                        v-bind=\"componentField\"\n                    />",
                'date' => '<Input type="date" v-bind="componentField" />',
                'datetime' => '<Input type="datetime-local" v-bind="componentField" />',
                default => '<Input type="text" v-bind="componentField" />',
            };

            $blocks[] = <<<VUE
                    <!-- {$title} -->
                    <FormField v-slot="{ componentField }" name="{$name}">
                        <FormItem class="col-span-6">
                            <FormLabel>
                                {$label}{$mark}
                            </FormLabel>
                            <FormControl>
                                {$control}
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
            VUE;
        }

        return $blocks;
    }

    /**
     * The exports of the relation selects, for the partials registry.
     *
     * @param  Blueprint  $blueprint
     * @return string
     */
    protected function relationExports(Blueprint $blueprint): string
    {
        $lines = [];

        foreach ($blueprint->foreign() as $name => $field) {
            $select = $this->selectName($name);
            $lines[] = "export { default as {$select} } from '@module/{$blueprint->artifact}/pages/".Str::kebab($blueprint->model)."/partials/relations/{$select}.vue';";
        }

        return $lines === [] ? '' : "\n// Selects of the related records\n".implode("\n", $lines)."\n";
    }

    /**
     * The imports the table columns need, beyond those of the stub.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $label
     * @param  bool  $linkShow  Whether the label links to the record's page.
     * @return string
     */
    protected function columnImports(Blueprint $blueprint, string $label, bool $linkShow): string
    {
        $artifact = $blueprint->artifact;
        $kebab = Str::kebab($blueprint->model);
        $fields = $blueprint->editable();
        $kinds = array_column($fields, 'kind');
        $booleans = array_filter($fields, fn (array $field) => $field['kind'] === 'boolean');
        $link = $linkShow && $label !== 'id';
        $imports = [];

        if ($booleans !== [] || isset($fields['slug'])) {
            $imports[] = "import { Badge } from '@/components/ui/badge';";
        }

        if ($booleans !== []) {
            $imports[] = "import { getStatusUi } from '@/utils/status/status-ui';";
            $data = array_filter([
                isset($booleans['active']) ? 'getStatuses' : null,
                array_diff_key($booleans, ['active' => true]) !== [] ? 'getBooleans' : null,
            ]);
            $imports[] = 'import { '.implode(', ', $data)." } from '@module/{$artifact}/pages/{$kebab}/partials/index/data/data';";
        }

        if ($link) {
            $imports[] = "import { show } from '@/routes/{$kebab}';";
        }

        if ($link || in_array('foreign', $kinds, true)) {
            $imports[] = "import { Link } from '@inertiajs/vue3';";
        }

        foreach ($blueprint->relatedModels($fields) as $related) {
            $imports[] = "import { show as show{$related} } from '@/routes/".Str::kebab($related)."';";
            $imports[] = "import { use{$related}Options } from '@module/{$artifact}/pages/{$kebab}/partials/relations/use{$related}Options';";
        }

        return $imports === [] ? '' : implode("\n", $imports);
    }

    /**
     * The table helpers the columns need: the option filter and the labels of the related records.
     *
     * @param  Blueprint  $blueprint
     * @return string
     */
    protected function columnSetup(Blueprint $blueprint): string
    {
        $lines = [];

        if (in_array('boolean', array_column($blueprint->fields, 'kind'), true)) {
            $lines[] = "\n/** Matches the rows whose cell is one of the chosen filter options. */\nconst oneOf: FilterFn<{$blueprint->model}> = (row, id, value: unknown[]) =>\n    value.includes(row.getValue(id));";
        }

        foreach ($blueprint->relatedModels() as $related) {
            $lines[] = "\n/** The labels of the ".Str::lower(Str::headline(Str::pluralStudly($related))).', loaded once. */'
                ."\nconst { labelOf: ".Str::camel($related)."Label } = use{$related}Options();";
        }

        return implode("\n", $lines);
    }

    /**
     * The table columns of the fields: the label links to the record, slugs and booleans are
     * badges, foreign keys link to their record; text and json fields are left out.
     *
     * @param  Blueprint  $blueprint
     * @param  string  $lang
     * @param  string  $label
     * @param  bool  $linkShow  Whether the label links to the record's page.
     * @return string
     */
    protected function tableColumns(Blueprint $blueprint, string $lang, string $label, bool $linkShow): string
    {
        $model = $blueprint->model;
        $blocks = [];

        foreach ($blueprint->editable() as $name => $field) {
            if ($field['kind'] === 'text') {
                continue;
            }

            $title = $name === 'active' ? "trans('statuses.status')" : "trans('{$lang}.attributes.{$name}.label')";
            $accessor = "accessorKey: '{$name}',";
            $filter = 'contains';
            $searchable = 'true';
            $filterable = 'false';
            $options = '[]';
            $sorting = 'true';

            if ($name === $label && $linkShow) {
                $comment = 'The '.str_replace('_', ' ', $name).', linking to the record.';
                $cell = "({ row }) =>\n            h(\n                Link,\n                {\n                    href: show.url(row.original.id),\n                    class: 'font-medium text-primary hover:underline',\n                },\n                () => String(row.getValue('{$name}') || row.original.id),\n            )";
            } elseif ($name === 'slug') {
                $comment = 'The slug, as a badge.';
                $cell = "({ row }) =>\n            h(Badge, { variant: 'default' }, () => String(row.getValue('slug')))";
            } elseif ($field['kind'] === 'boolean') {
                $active = $name === 'active';
                $comment = $active ? 'Activation status, filtered by its options.' : 'Whether the record is '.str_replace('_', ' ', $name).', filtered by yes / no.';
                $labels = $active
                    ? "active: trans('statuses.activation.active'),\n                    inactive: trans('statuses.activation.inactive'),"
                    : "active: trans('statuses.boolean.yes'),\n                    inactive: trans('statuses.boolean.no'),";
                $cell = "({ row }) => {\n            const ui = getStatusUi({\n                status: row.getValue('{$name}') ? 'active' : 'inactive',\n                variant: 'soft',\n                labels: {\n                    {$labels}\n                },\n            });\n\n            return h(Badge, { variant: ui.badgeVariant as any }, () => ui.label);\n        }";
                $filter = 'oneOf';
                $searchable = 'false';
                $filterable = 'true';
                $options = $active ? 'getStatuses()' : 'getBooleans()';
                $sorting = 'false';
            } elseif ($field['kind'] === 'foreign') {
                $related = $field['related'];
                $relatedLabel = Str::camel($related).'Label';
                $comment = 'The '.Str::lower(Str::headline($related)).' of the record, linking to it.';
                $accessor = "id: '{$name}',\n        accessorFn: (record) => {$relatedLabel}(record.{$name}),";
                $cell = "({ row }) =>\n            row.original.{$name}\n                ? h(\n                      Link,\n                      {\n                          href: show{$related}.url(row.original.{$name}),\n                          class: 'text-primary hover:underline',\n                      },\n                      () => row.getValue('{$name}') as string,\n                  )\n                : ''";
            } else {
                $comment = 'The '.str_replace('_', ' ', $name).'.';
                $cell = "({ row }) => h('div', String(row.getValue('{$name}') ?? ''))";
            }

            $blocks[] = <<<TS
                /** {$comment} */
                {
                    {$accessor}
                    header: ({ column }) =>
                        h(DataTableColumnHeader, {
                            column,
                            title: {$title},
                        }),
                    cell: {$cell},
                    filterFn: {$filter},
                    enableSorting: {$sorting},
                    enableHiding: true,
                    meta: {
                        title: {$title},
                        searchable: {$searchable},
                        filterable: {$filterable},
                        filterOption: {$options},
                    } as {$model}ColumnMeta,
                },
            TS;
        }

        return $blocks === [] ? '' : "\n".implode("\n\n", $blocks)."\n";
    }

    /**
     * The Lucide icons of the summary, sorted.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @return string
     */
    protected function summaryIcons(Blueprint $blueprint, array $fields): string
    {
        $icons = ['CalendarDays', 'RefreshCw'];

        foreach ($blueprint->editable($fields) as $field) {
            $icons[] = self::KIND_ICONS[$field['kind']];
        }

        $icons = array_unique($icons);
        sort($icons);

        return implode(', ', $icons);
    }

    /**
     * The imports the summary needs for the related records.
     *
     * @param  Blueprint  $blueprint
     * @return string
     */
    protected function summaryImports(Blueprint $blueprint): string
    {
        $related = $blueprint->relatedModels();

        if ($related === []) {
            return '';
        }

        $imports = ["import { Link } from '@inertiajs/vue3';"];

        foreach ($related as $name) {
            $imports[] = "import { show as show{$name} } from '@/routes/".Str::kebab($name)."';";
            $imports[] = "import { use{$name}Options } from '@module/{$blueprint->artifact}/pages/".Str::kebab($blueprint->model)."/partials/relations/use{$name}Options';";
        }

        return implode("\n", $imports);
    }

    /**
     * The labels of the related records, for the summary.
     *
     * @param  Blueprint  $blueprint
     * @return string
     */
    protected function summarySetup(Blueprint $blueprint): string
    {
        $lines = [];

        foreach ($blueprint->relatedModels() as $related) {
            $lines[] = "\n/** The labels of the ".Str::lower(Str::headline(Str::pluralStudly($related))).'. */'
                ."\nconst { labelOf: ".Str::camel($related)."Label } = use{$related}Options();";
        }

        return implode("\n", $lines);
    }

    /**
     * The summary item of each field.
     *
     * @param  Blueprint  $blueprint
     * @param  array<string, array<string, mixed>>  $fields
     * @param  string  $lang
     * @param  string  $record
     * @return string
     */
    protected function summaryItems(Blueprint $blueprint, array $fields, string $lang, string $record): string
    {
        $blocks = [];

        foreach ($blueprint->editable($fields) as $name => $field) {
            $value = "{$record}.{$name}";
            $icon = self::KIND_ICONS[$field['kind']];
            $title = Str::headline($name);

            $content = match ($field['kind']) {
                'boolean' => $name === 'active'
                    ? "<span class=\"text-xs font-semibold break-words\">\n                    {{\n                        {$value}\n                            ? \$t('statuses.activation.active')\n                            : \$t('statuses.activation.inactive')\n                    }}\n                </span>"
                    : "<span class=\"text-xs font-semibold break-words\">\n                    {{\n                        {$value}\n                            ? \$t('statuses.boolean.yes')\n                            : \$t('statuses.boolean.no')\n                    }}\n                </span>",
                'foreign' => "<Link\n                    v-if=\"{$value}\"\n                    :href=\"show{$field['related']}.url({$value})\"\n                    class=\"text-xs font-semibold break-words text-primary hover:underline\"\n                >\n                    {{ ".Str::camel($field['related'])."Label({$value}) }}\n                </Link>",
                default => "<span class=\"text-xs font-semibold break-words\">\n                    {{ {$value} ?? '—' }}\n                </span>",
            };

            $blocks[] = <<<VUE
                    <!-- {$title} -->
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="shrink-0 rounded-lg bg-muted p-2">
                            <{$icon} class="h-4 w-4 text-muted-foreground" />
                        </div>
                        <div class="flex min-w-0 flex-col gap-1">
                            <span
                                class="truncate text-xs font-medium tracking-wider text-muted-foreground uppercase"
                            >
                                {{ \$t('{$lang}.attributes.{$name}.label') }}
                            </span>
                            {$content}
                        </div>
                    </div>
            VUE;
        }

        return implode("\n\n", $blocks);
    }
}
