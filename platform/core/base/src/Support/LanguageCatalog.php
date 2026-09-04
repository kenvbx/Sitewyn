<?php

namespace Sitewyn\Core\Base\Support;

use Locale;
use ResourceBundle;

class LanguageCatalog
{
    /**
     * @return array<string, array{code: string, name: string, native_name: string, locale: string, flag: string, text_direction: string}>
     */
    public function languages(): array
    {
        $rtlCodes = config('sitewyn-languages.rtl', []);
        $presets = config('sitewyn-languages.presets', []);
        $languages = [];

        foreach ($this->languageCodes() as $code) {
            $preset = is_array($presets[$code] ?? null) ? $presets[$code] : [];
            $name = $preset['name'] ?? Locale::getDisplayLanguage($code, 'en');
            $nativeName = $preset['native_name'] ?? Locale::getDisplayLanguage($code, $code);
            $locale = $preset['locale'] ?? $this->defaultLocaleFor($code);

            $languages[$code] = [
                'code' => $code,
                'name' => $name !== '' ? $name : mb_strtoupper($code),
                'native_name' => $nativeName !== '' ? $nativeName : ($name !== '' ? $name : mb_strtoupper($code)),
                'locale' => $locale,
                'flag' => $preset['flag'] ?? $this->flagForLocale($locale),
                'text_direction' => $preset['text_direction'] ?? (in_array($code, $rtlCodes, true) ? 'rtl' : 'ltr'),
            ];
        }

        uasort($languages, fn (array $first, array $second): int => strnatcasecmp($first['name'], $second['name']));

        return $languages;
    }

    /**
     * @return array<string, string>
     */
    public function languageOptions(): array
    {
        return collect($this->languages())
            ->mapWithKeys(fn (array $language, string $code): array => [
                $code => $language['native_name'] !== $language['name']
                    ? $language['name'].' - '.$language['native_name']
                    : $language['name'],
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function localeOptions(): array
    {
        $locales = ResourceBundle::getLocales('') ?: [];
        $options = [];

        foreach ($locales as $locale) {
            if (! is_string($locale) || $locale === 'root') {
                continue;
            }

            $options[$locale] = $locale;
        }

        foreach (config('sitewyn-languages.presets', []) as $preset) {
            if (is_array($preset) && is_string($preset['locale'] ?? null)) {
                $options[$preset['locale']] = $preset['locale'];
            }
        }

        natcasesort($options);

        return $options;
    }

    /**
     * @return array<string, array{name: string, emoji: string}>
     */
    public function flagOptions(): array
    {
        $options = [
            'un' => ['name' => 'Universal', 'emoji' => '🌐'],
        ];

        foreach ($this->localeOptions() as $locale => $label) {
            $region = $this->regionForLocale($locale);

            if ($region === null || isset($options[$region])) {
                continue;
            }

            $options[$region] = [
                'name' => Locale::getDisplayRegion('-'.$region, 'en') ?: mb_strtoupper($region),
                'emoji' => $this->countryEmoji($region),
            ];
        }

        uasort($options, fn (array $first, array $second): int => strnatcasecmp($first['name'], $second['name']));

        return $options;
    }

    /**
     * @return array{locale: string, flag: string, text_direction: string}
     */
    public function defaultsFor(string $code): array
    {
        $language = $this->languages()[$code] ?? null;

        if ($language === null) {
            return [
                'locale' => $code,
                'flag' => 'un',
                'text_direction' => 'ltr',
            ];
        }

        return [
            'locale' => $language['locale'],
            'flag' => $language['flag'],
            'text_direction' => $language['text_direction'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function languageCodes(): array
    {
        $codes = [];
        $locales = ResourceBundle::getLocales('') ?: [];

        foreach ($locales as $locale) {
            if (! is_string($locale) || $locale === 'root' || str_contains($locale, '_')) {
                continue;
            }

            $code = mb_strtolower($locale);

            if (preg_match('/^[a-z]{2}$/', $code) === 1) {
                $codes[] = $code;
            }
        }

        foreach (array_keys(config('sitewyn-languages.presets', [])) as $code) {
            if (is_string($code) && preg_match('/^[a-z]{2}$/', $code) === 1) {
                $codes[] = $code;
            }
        }

        $codes = array_values(array_unique($codes));
        sort($codes);

        return $codes;
    }

    private function defaultLocaleFor(string $code): string
    {
        $candidate = collect($this->localeOptions())
            ->keys()
            ->first(fn (string $locale): bool => str_starts_with($locale, $code.'_'));

        return $candidate ?? $code;
    }

    private function flagForLocale(string $locale): string
    {
        return $this->regionForLocale($locale) ?? 'un';
    }

    private function regionForLocale(string $locale): ?string
    {
        if (! str_contains($locale, '_')) {
            return null;
        }

        $region = mb_strtolower((string) str($locale)->afterLast('_'));

        return preg_match('/^[a-z]{2}$/', $region) === 1 ? $region : null;
    }

    private function countryEmoji(string $countryCode): string
    {
        $countryCode = mb_strtoupper($countryCode);

        if (preg_match('/^[A-Z]{2}$/', $countryCode) !== 1) {
            return '🌐';
        }

        $regionalIndicators = array_map(
            fn (string $letter): string => mb_chr(127397 + ord($letter), 'UTF-8'),
            str_split($countryCode),
        );

        return implode('', $regionalIndicators);
    }
}
