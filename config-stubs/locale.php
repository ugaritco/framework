<?php

declare(strict_types=1);

/**
 * Ugarit Application Localization Configuration
 *
 * This file is the single source of truth for all language and locale definitions
 * used across the application. It defines every supported locale with its full
 * linguistic metadata including text direction, script code, regional tag, and
 * emoji flag for use in UI language switchers.
 *
 * Access via: config('locale.locale')
 *             config('locale.fallback_locale')
 *             config('locale.faker_locale')
 *             config('locale.translatable')
 *             config('locale.default_locale')
 *             config('locale.available_locales')
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Ugarit's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the default one
    | is not available. You may change the value to correspond to any of
    | the languages which are currently supported by your application.
    |
    */

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Translatable
    |--------------------------------------------------------------------------
    |
    | Determines whether the application supports multiple languages.
    |
    | true  → multi-language mode: locale detection and switching are active.
    | false → single-language mode: the app always runs in the default locale,
    |         and no locale switching or detection is performed.
    |
    | Environment variable: APP_TRANSLATABLE
    |
    */

    'translatable' => (bool) env('APP_TRANSLATABLE', true),

    /*
    |--------------------------------------------------------------------------
    | Default Locale
    |--------------------------------------------------------------------------
    |
    | The locale the application boots with on every request.
    | This value MUST correspond to one of the keys in 'available_locales' below.
    |
    | Environment variable: APP_DEFAULT_LOCALE
    |
    */

    'default_locale' => env('APP_DEFAULT_LOCALE', env('APP_LOCALE', 'ar')),

    /*
    |--------------------------------------------------------------------------
    | Available Locales
    |--------------------------------------------------------------------------
    |
    | The complete registry of locales the application recognizes and supports.
    | Each entry is keyed by its ISO 639-1 locale code and contains the full
    | linguistic metadata required by the framework and UI components.
    |
    | Fields per locale:
    |   - code      : ISO 639-1 language code (e.g. 'ar', 'en', 'fr').
    |   - name      : Language name written in English.
    |   - native    : Language name written in its own script.
    |   - direction : Text direction — 'rtl' (right-to-left) or 'ltr' (left-to-right).
    |   - script    : ISO 15924 four-letter script code (e.g. 'Arab', 'Latn', 'Cyrl').
    |   - regional  : BCP 47 / POSIX regional tag (e.g. 'ar_SA', 'en_US').
    |   - flag      : Emoji flag for UI display in language switchers.
    |
    | To add a new language  → add a new entry following the same structure.
    | To disable a language  → remove or comment out its entry.
    | To change the default  → update APP_DEFAULT_LOCALE in your .env file.
    |
    */

    'available_locales' => [

        /**
         * Arabic — العربية
         *
         * Right-to-left script (Arabic / Arab).
         * Regional tag points to Saudi Arabia (ar_SA) as the canonical reference.
         */
        'ar' => [
            'code'      => 'ar',
            'name'      => 'Arabic',
            'native'    => 'العربية',
            'direction' => 'rtl',
            'script'    => 'Arab',
            'regional'  => 'ar_SA',
            'flag'      => '🇸🇦',
        ],

        /**
         * English
         *
         * Left-to-right script (Latin / Latn).
         * Regional tag points to the United States (en_US) as the canonical reference.
         */
        'en' => [
            'code'      => 'en',
            'name'      => 'English',
            'native'    => 'English',
            'direction' => 'ltr',
            'script'    => 'Latn',
            'regional'  => 'en_US',
            'flag'      => '🇺🇸',
        ],

    ],

];
