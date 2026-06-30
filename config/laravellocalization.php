<?php

/**
 * mcamara/laravel-localization configuration.
 * Vietnamese is the default locale and is hidden from the URL (served at root);
 * English is served under the /en prefix.
 */
return [
    'supportedLocales' => [
        'vi' => ['name' => 'Vietnamese', 'script' => 'Latn', 'native' => 'Tiếng Việt', 'regional' => 'vi_VN'],
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
    ],

    // Default (vi) has no URL prefix; only /en is prefixed.
    'hideDefaultLocaleInURL' => true,

    // Detect the visitor's preferred language on first visit.
    'useAcceptLanguageHeader' => true,

    // Keep the chosen locale in the session.
    'useSessionLocale' => true,
    'useCookieLocale'  => false,

    'localesOrder'  => ['vi', 'en'],
    'localesMapping' => [],
    'utf8suffix'    => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),
    'urlsIgnored'   => ['/skipped'],
];
