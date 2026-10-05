<?php

namespace Heritage\Foundation\Http\Concerns;

/**
 * Trait NormalizesTranslatableInputs
 *
 * Provides effortless, zero-boilerplate handling for multilingual translatable fields in FormRequests:
 * - Automatically normalizes flat, single-locale inputs (e.g. ['name' => '...']) into canonical multi-locale payloads.
 * - Automatically expands natural field rules (e.g. 'name' => 'required|string') into comprehensive multi-locale validation rules.
 */
trait NormalizesTranslatableInputs
{
    /**
     * Normalize translatable inputs from flat single-locale fields into canonical translations structure.
     *
     * @return void
     */
    protected function normalizeTranslatableInputs(): void
    {
        $fields = $this->getTranslatableFields();
        $translations = (array) $this->input('translations', []);
        $locale = function_exists('app') ? app()->getLocale() : 'ar';
        $modified = false;

        foreach ($fields as $field) {
            if ($this->has($field) && ! isset($translations[$locale][$field])) {
                $translations[$locale][$field] = $this->input($field);
                $modified = true;
            }
        }

        // Support dot notation translation inputs from FormData or form bindings (e.g. translations.ar.name)
        foreach ($this->all() as $key => $value) {
            if (str_starts_with((string) $key, 'translations.')) {
                $parts = explode('.', (string) $key);
                if (count($parts) === 3) {
                    [, $loc, $attr] = $parts;
                    if (in_array($attr, $fields, true)) {
                        $translations[$loc][$attr] = $value;
                        $modified = true;
                    }
                }
            }
        }

        if ($modified) {
            $this->merge(['translations' => $translations]);
        }
    }

    /**
     * Get the list of translatable attribute names.
     *
     * @return array<int, string>
     */
    protected function getTranslatableFields(): array
    {
        if (property_exists($this, 'translatable')) {
            return (array) $this->translatable;
        }

        if (property_exists($this, 'translatableFields')) {
            return (array) $this->translatableFields;
        }

        return ['name', 'description', 'title'];
    }

    /**
     * Automatically expand natural field rules for translatable attributes.
     *
     * @param  array  $rules
     * @return array
     */
    protected function expandTranslatableRules(array $rules): array
    {
        $translatableFields = $this->getTranslatableFields();
        $expanded = [];
        $hasTranslatable = false;
        $isAnyTranslatableRequired = false;

        foreach ($rules as $attribute => $rule) {
            if (in_array($attribute, $translatableFields, true)) {
                $hasTranslatable = true;

                // Check if the attribute is marked as required
                $ruleString = is_array($rule) ? implode('|', array_filter($rule, 'is_string')) : (string) $rule;
                $isRequired = str_contains($ruleString, 'required');

                if ($isRequired) {
                    $isAnyTranslatableRequired = true;
                }

                // All provided locale entries must conform to the attribute rule
                $expanded["translations.*.{$attribute}"] = $rule;

                // If flat input was submitted directly, keep attribute rule for direct feedback
                if ($this->has($attribute) && ! $this->has('translations')) {
                    $expanded[$attribute] = $rule;
                }
            } else {
                $expanded[$attribute] = $rule;
            }
        }

        if ($hasTranslatable && ! isset($expanded['translations'])) {
            $expanded['translations'] = $isAnyTranslatableRequired ? 'required|array|min:1' : 'nullable|array';
        }

        return $expanded;
    }
}
