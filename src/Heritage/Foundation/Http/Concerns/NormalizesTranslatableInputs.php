<?php

namespace Heritage\Foundation\Http\Concerns;

use Heritage\Translation\Locales;

/**
 * Trait NormalizesTranslatableInputs
 *
 * Handles translatable fields declared in a FormRequest's $translatable property:
 * - Normalizes every accepted input shape into the canonical payload
 *   ['translations' => ['ar' => ['name' => '...']]]: a flat field (['name' => '...']) becomes the
 *   current locale's entry, and FormData keys ("translations.ar.name") are folded in. The flat
 *   field is removed, so validated() exposes the translatable fields only under "translations".
 * - A locale sent as null (or an empty string) means "delete this locale's translation": it is
 *   kept out of validation and returned as null by validated(), which the model applies on save.
 * - Expands each field rule (e.g. 'name' => 'required|string') to "translations.*.name",
 *   applies $requiredLocales, and rejects locales the application does not accept.
 *
 * A field is translatable only when the request declares it; there are no implicit defaults.
 */
trait NormalizesTranslatableInputs
{
    /**
     * Locale codes sent as null, to be deleted (returned as null by validated()).
     *
     * @var array<int, string>
     */
    protected array $forgottenTranslations = [];

    /**
     * Normalize translatable inputs into the canonical translations structure.
     *
     * @return void
     */
    protected function normalizeTranslatableInputs(): void
    {
        $fields = $this->getTranslatableFields();

        if ($fields === []) {
            return;
        }

        $resolver = Locales::resolver();
        $translations = (array) $this->input('translations', []);
        $input = $this->input();

        foreach ($fields as $field) {
            if (array_key_exists($field, $input)) {
                $translations[$resolver->current()][$field] ??= $input[$field];
                unset($input[$field]);
            }
        }

        // Dot-notation keys sent by FormData or form bindings (e.g. "translations.ar.name")
        foreach ($input as $key => $value) {
            if (! str_starts_with((string) $key, 'translations.')) {
                continue;
            }

            $parts = explode('.', (string) $key);

            if (count($parts) === 3 && in_array($parts[2], $fields, true)) {
                $translations[$parts[1]][$parts[2]] = $value;
                unset($input[$key]);
            } elseif (count($parts) === 2) {
                $translations[$parts[1]] = $value;
                unset($input[$key]);
            }
        }

        // Accepted locales sent as null / '' are deletions; unknown ones stay so validation rejects them.
        $this->forgottenTranslations = [];

        foreach ($translations as $locale => $value) {
            if (($value === null || $value === '') && $resolver->isAvailable((string) $locale)) {
                $this->forgottenTranslations[] = (string) $locale;
                unset($translations[$locale]);
            }
        }

        if ($translations !== [] || array_key_exists('translations', $input)) {
            $input['translations'] = $translations;
        }

        $this->replace($input);
    }

    /**
     * Add the locales sent as null back to the validated data, so the model deletes them.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function withForgottenTranslations(array $validated): array
    {
        foreach ($this->forgottenTranslations as $locale) {
            $validated['translations'][$locale] = null;
        }

        return $validated;
    }

    /**
     * The translatable fields declared by the request's $translatable property
     * (same name as on the model).
     *
     * @return array<int, string>
     */
    protected function getTranslatableFields(): array
    {
        return property_exists($this, 'translatable') ? (array) $this->translatable : [];
    }

    /**
     * Locale codes that must be present when a translatable field is required, from
     * $requiredLocales: 'any' (default, at least one locale), 'default' (the application's
     * default locale), 'all' (every available locale) or an explicit list of codes.
     *
     * @return array<int, string>|null Null for 'any'.
     */
    protected function getRequiredLocales(): ?array
    {
        $required = property_exists($this, 'requiredLocales') ? $this->requiredLocales : 'any';
        $resolver = Locales::resolver();

        return match (true) {
            is_array($required) => array_values($required),
            $required === 'default' => [$resolver->default()],
            $required === 'all' => $resolver->available(),
            default => null,
        };
    }

    /**
     * Expand translatable field rules to "translations.*.{field}" and validate the locales.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function expandTranslatableRules(array $rules): array
    {
        $fields = $this->getTranslatableFields();
        $requiredLocales = $this->getRequiredLocales();
        $expanded = [];
        $hasTranslatable = false;
        $isRequired = false;

        foreach ($rules as $attribute => $rule) {
            if (! in_array($attribute, $fields, true)) {
                $expanded[$attribute] = $rule;

                continue;
            }

            $hasTranslatable = true;
            $required = $this->hasRequiredRule($rule);
            $isRequired = $isRequired || $required;

            if (! $required || $requiredLocales === null) {
                // Every submitted locale follows the field rules.
                $expanded["translations.*.{$attribute}"] = $rule;

                continue;
            }

            // Only the required locales must be filled; other submitted locales are optional.
            $expanded["translations.*.{$attribute}"] = $this->withoutRequiredRule($rule);

            foreach ($requiredLocales as $locale) {
                $expanded["translations.{$locale}.{$attribute}"] = $rule;
            }
        }

        if ($hasTranslatable && ! isset($expanded['translations'])) {
            $expanded['translations'] = [
                ...($isRequired ? ['required', 'array', 'min:1'] : ['nullable', 'array']),
                $this->supportedLocalesRule(),
            ];
        }

        return $expanded;
    }

    /**
     * Whether a rule set contains the plain "required" rule (not required_if, required_with...).
     */
    protected function hasRequiredRule(mixed $rule): bool
    {
        foreach ($this->ruleItems($rule) as $item) {
            if (is_string($item) && $this->ruleName($item) === 'required') {
                return true;
            }
        }

        return false;
    }

    /**
     * The rule set with "required" replaced by "nullable".
     *
     * @return array<int, mixed>
     */
    protected function withoutRequiredRule(mixed $rule): array
    {
        $items = array_values(array_filter(
            $this->ruleItems($rule),
            fn ($item) => ! is_string($item) || ! in_array($this->ruleName($item), ['required', 'nullable'], true)
        ));

        return ['nullable', ...$items];
    }

    /**
     * Split a rule definition into its items.
     *
     * @return array<int, mixed>
     */
    protected function ruleItems(mixed $rule): array
    {
        return is_string($rule) ? explode('|', $rule) : (array) $rule;
    }

    /**
     * The lower-cased name of a string rule ("min:2" => "min").
     */
    protected function ruleName(string $rule): string
    {
        return strtolower(trim(explode(':', $rule, 2)[0]));
    }

    /**
     * Rule rejecting translations for locales the application does not accept.
     */
    protected function supportedLocalesRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) {
            if (! is_array($value)) {
                return;
            }

            $resolver = Locales::resolver();
            $unknown = array_filter(array_keys($value), fn ($code) => ! $resolver->isAvailable((string) $code));

            if ($unknown !== []) {
                $fail('validation.translation_locales')->translate([
                    'locales' => implode(', ', $unknown),
                ]);
            }
        };
    }
}
