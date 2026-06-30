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

    // URL is the single source of truth. Do NOT auto-detect/redirect by browser
    // language or session — otherwise an English browser can't switch back to the
    // hidden default locale (vi at "/"), because it keeps getting bounced to /en.
    'useAcceptLanguageHeader' => false,
    'useSessionLocale' => false,
    'useCookieLocale'  => false,

    'localesOrder'  => ['vi', 'en'],
    'localesMapping' => [],
    'utf8suffix'    => env('LARAVELLOCALIZATION_UTF8SUFFIX', '.UTF-8'),
    'urlsIgnored'   => ['/skipped'],
];
