<?php

use Sitewyn\Core\Base\Support\AdminFlash;
use Sitewyn\Core\Base\Support\SettingStore;

if (! function_exists('admin_flash')) {
    function admin_flash(): AdminFlash
    {
        return app(AdminFlash::class);
    }
}

if (! function_exists('site_setting')) {
    function site_setting(string $key, ?string $default = null): ?string
    {
        return app(SettingStore::class)->get($key, $default);
    }
}

if (! function_exists('site_tracking_head')) {
    function site_tracking_head(): string
    {
        $settings = app(SettingStore::class);

        return match ($settings->get('website_tracking_type', 'gtm')) {
            'gtm' => site_tracking_gtm_head((string) $settings->get('website_tracking_gtm_container_id')),
            'ga' => site_tracking_ga_head((string) $settings->get('website_tracking_ga_measurement_id')),
            'custom' => (string) $settings->get('website_tracking_custom_header_script'),
            default => '',
        };
    }
}

if (! function_exists('site_tracking_body')) {
    function site_tracking_body(): string
    {
        $settings = app(SettingStore::class);

        return match ($settings->get('website_tracking_type', 'gtm')) {
            'gtm' => site_tracking_gtm_body((string) $settings->get('website_tracking_gtm_container_id')),
            'custom' => (string) $settings->get('website_tracking_custom_body_code'),
            default => '',
        };
    }
}

if (! function_exists('site_tracking_gtm_head')) {
    function site_tracking_gtm_head(string $containerId): string
    {
        $containerId = site_tracking_gtm_container_id($containerId);

        if ($containerId === '') {
            return '';
        }

        return <<<HTML
<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','{$containerId}');</script>
<!-- End Google Tag Manager -->
HTML;
    }
}

if (! function_exists('site_tracking_gtm_body')) {
    function site_tracking_gtm_body(string $containerId): string
    {
        $containerId = site_tracking_gtm_container_id($containerId);

        if ($containerId === '') {
            return '';
        }

        return <<<HTML
<!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id={$containerId}"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
HTML;
    }
}

if (! function_exists('site_tracking_ga_head')) {
    function site_tracking_ga_head(string $measurementId): string
    {
        $measurementId = site_tracking_ga_measurement_id($measurementId);

        if ($measurementId === '') {
            return '';
        }

        return <<<HTML
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$measurementId}"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '{$measurementId}');
</script>
HTML;
    }
}

if (! function_exists('site_tracking_gtm_container_id')) {
    function site_tracking_gtm_container_id(string $containerId): string
    {
        $containerId = strtoupper(trim($containerId));

        return preg_match('/^GTM-[A-Z0-9]+$/', $containerId) === 1 ? $containerId : '';
    }
}

if (! function_exists('site_tracking_ga_measurement_id')) {
    function site_tracking_ga_measurement_id(string $measurementId): string
    {
        $measurementId = strtoupper(trim($measurementId));

        return preg_match('/^G-[A-Z0-9]+$/', $measurementId) === 1 ? $measurementId : '';
    }
}

if (! function_exists('site_blog_schema_enabled')) {
    function site_blog_schema_enabled(): bool
    {
        return site_setting('blog_schema_enabled', '1') === '1';
    }
}

if (! function_exists('site_blog_schema_type')) {
    function site_blog_schema_type(): string
    {
        $type = site_setting('blog_schema_type', 'BlogPosting') ?: 'BlogPosting';

        return in_array($type, ['Article', 'BlogPosting', 'NewsArticle'], true) ? $type : 'BlogPosting';
    }
}

if (! function_exists('site_blog_anchor_links_enabled')) {
    function site_blog_anchor_links_enabled(): bool
    {
        return site_setting('blog_anchor_links_enabled', '0') === '1';
    }
}

if (! function_exists('site_blog_content')) {
    function site_blog_content(?string $content): string
    {
        $content = (string) $content;

        if ($content === '' || ! site_blog_anchor_links_enabled()) {
            return $content;
        }

        return site_blog_add_heading_anchors($content);
    }
}

if (! function_exists('site_blog_add_heading_anchors')) {
    function site_blog_add_heading_anchors(string $content): string
    {
        $protectedBlocks = [];
        $protectedContent = preg_replace_callback(
            '/<(pre|code|script|style)\b[^>]*>.*?<\/\\1>/is',
            static function (array $matches) use (&$protectedBlocks): string {
                $placeholder = '___SITEWYN_PROTECTED_BLOCK_'.count($protectedBlocks).'___';
                $protectedBlocks[$placeholder] = $matches[0];

                return $placeholder;
            },
            $content,
        ) ?? $content;

        $anchoredContent = preg_replace_callback(
            '/<(h[23])([^>]*)>(.*?)<\/\\1>/is',
            static function (array $matches): string {
                if (preg_match('/\\sid\\s*=\\s*([\"\\\']).*?\\1/i', $matches[2]) === 1) {
                    return $matches[0];
                }

                $slug = str((string) strip_tags($matches[3]))->slug()->toString();

                if ($slug === '') {
                    return $matches[0];
                }

                return '<'.$matches[1].$matches[2].' id="'.$slug.'">'.$matches[3].'</'.$matches[1].'>';
            },
            $protectedContent,
        ) ?? $protectedContent;

        return strtr($anchoredContent, $protectedBlocks);
    }
}
