<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CMS Language Presets
    |--------------------------------------------------------------------------
    |
    | PHP Intl provides the broad locale/language catalog. These presets add
    | CMS-specific defaults that Intl does not know: preferred locale, flag,
    | native display name, and text direction. Add project-specific languages
    | here without touching controllers or Blade views.
    |
    */

    'rtl' => ['ar', 'dv', 'fa', 'he', 'ku', 'ps', 'sd', 'ug', 'ur', 'yi'],

    'presets' => [
        'en' => ['name' => 'English', 'native_name' => 'English', 'locale' => 'en_US', 'flag' => 'us'],
        'ar' => ['name' => 'Arabic', 'native_name' => 'العربية', 'locale' => 'ar', 'flag' => 'sa', 'text_direction' => 'rtl'],
        'vi' => ['name' => 'Vietnamese', 'native_name' => 'Tiếng Việt', 'locale' => 'vi', 'flag' => 'vn'],
        'fr' => ['name' => 'French', 'native_name' => 'Français', 'locale' => 'fr', 'flag' => 'fr'],
        'id' => ['name' => 'Indonesian', 'native_name' => 'Bahasa Indonesia', 'locale' => 'id', 'flag' => 'id'],
        'tr' => ['name' => 'Turkish', 'native_name' => 'Türkçe', 'locale' => 'tr', 'flag' => 'tr'],
        'de' => ['name' => 'German', 'native_name' => 'Deutsch', 'locale' => 'de', 'flag' => 'de'],
        'es' => ['name' => 'Spanish', 'native_name' => 'Español', 'locale' => 'es', 'flag' => 'es'],
        'it' => ['name' => 'Italian', 'native_name' => 'Italiano', 'locale' => 'it', 'flag' => 'it'],
        'ja' => ['name' => 'Japanese', 'native_name' => '日本語', 'locale' => 'ja', 'flag' => 'jp'],
        'ko' => ['name' => 'Korean', 'native_name' => '한국어', 'locale' => 'ko', 'flag' => 'kr'],
        'pt' => ['name' => 'Portuguese', 'native_name' => 'Português', 'locale' => 'pt', 'flag' => 'pt'],
        'ru' => ['name' => 'Russian', 'native_name' => 'Русский', 'locale' => 'ru', 'flag' => 'ru'],
        'th' => ['name' => 'Thai', 'native_name' => 'ไทย', 'locale' => 'th', 'flag' => 'th'],
        'zh' => ['name' => 'Chinese', 'native_name' => '中文', 'locale' => 'zh_CN', 'flag' => 'cn'],
    ],
];
