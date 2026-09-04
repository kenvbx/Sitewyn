<?php

namespace Sitewyn\Core\Base\Http\Controllers\Admin;

use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use Sitewyn\Core\Base\Http\Requests\Admin\UpdateSettingsRequest;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\AdminFontCatalog;
use Sitewyn\Core\Base\Support\LanguageCatalog;
use Sitewyn\Core\Base\Support\RobotsTxt;
use Sitewyn\Core\Base\Support\SettingStore;
use Sitewyn\Core\Base\Support\ThemeManager;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingStore $settings,
        private readonly ThemeManager $themes,
    ) {}

    public function edit(): View
    {
        return view('core/base::admin.settings.edit', [
            'settings' => [
                'site_name' => $this->settings->get('site_name', config('app.name', 'Sitewyn')),
                'site_logo' => $this->settings->get('site_logo'),
                // Prefill the live robots.txt body (default when unconfigured)
                // so what the admin sees is what crawlers get.
                'robots_txt' => RobotsTxt::content($this->settings->get('robots_txt')),
                'active_theme' => $this->settings->get('active_theme', ThemeManager::DEFAULT_THEME),
            ],
            'themeOptions' => $this->themes->all()->pluck('name', 'slug')->all(),
            'sections' => $this->sections(),
        ]);
    }

    public function general(): View
    {
        return view('core/base::admin.settings.general', [
            'settings' => $this->generalSettings(),
            'timezoneOptions' => $this->timezoneOptions(),
            'languageOptions' => $this->languageOptions(),
            'logRetentionOptions' => $this->logRetentionOptions(),
            'cronjobUrl' => route('admin.system.cronjob', [], false),
        ]);
    }

    public function email(): View
    {
        return view('core/base::admin.settings.email', [
            'settings' => $this->emailSettings(),
            'baseSettings' => $this->generalSettings(),
            'mailerOptions' => $this->mailerOptions(),
            'logChannelOptions' => $this->logChannelOptions(),
            'languageOptions' => ['auto' => 'Auto (use default site language)'] + $this->languageOptions(),
            'templateGroups' => $this->emailTemplateGroups(),
        ]);
    }

    public function emailTemplates(): View
    {
        return view('core/base::admin.settings.email-templates', [
            'settings' => $this->emailTemplateSettings(),
            'baseSettings' => $this->generalSettings(),
            'templateGroups' => $this->emailTemplateGroups(),
        ]);
    }

    public function emailRules(): View
    {
        return view('core/base::admin.settings.email-rules', [
            'settings' => $this->emailRuleSettings(),
            'baseSettings' => $this->generalSettings(),
        ]);
    }

    public function phoneNumber(): View
    {
        return view('core/base::admin.settings.phone-number', [
            'settings' => $this->phoneNumberSettings(),
            'baseSettings' => $this->generalSettings(),
            'countryOptions' => $this->phoneCountryOptions(),
        ]);
    }

    public function media(): View
    {
        return view('core/base::admin.settings.media', [
            'settings' => $this->mediaSettings(),
            'baseSettings' => $this->generalSettings(),
            'driverOptions' => $this->mediaDriverOptions(),
            'driverCredentialGroups' => $this->mediaDriverCredentialGroups(),
            'imageProcessingLibraries' => $this->imageProcessingLibraries(),
            'thumbnailCropPositions' => $this->thumbnailCropPositions(),
        ]);
    }

    public function permalink(): View
    {
        return view('core/base::admin.settings.permalink', [
            'settings' => $this->permalinkSettings(),
            'baseSettings' => $this->generalSettings(),
            'fields' => $this->permalinkFields(),
            'baseUrl' => url('/'),
        ]);
    }

    public function adminAppearance(): View
    {
        $settings = $this->adminAppearanceSettings();
        $selectedFont = app(AdminFontCatalog::class)->find($settings['admin_primary_font']);

        return view('core/base::admin.settings.admin-appearance', [
            'settings' => $settings,
            'baseSettings' => $this->generalSettings(),
            'faviconTypeOptions' => $this->adminFaviconTypeOptions(),
            'selectedFont' => $selectedFont,
            'languageOptions' => ['default' => 'Default (follow site language)'] + app(LanguageCatalog::class)->languageOptions(),
        ]);
    }

    public function googleFonts(Request $request): JsonResponse
    {
        return response()->json(app(AdminFontCatalog::class)->search(
            $request->string('q')->toString(),
            $request->integer('page', 1),
        ));
    }

    public function api(): View
    {
        return view('core/base::admin.settings.api', [
            'settings' => $this->apiSettings(),
            'baseSettings' => $this->generalSettings(),
            'apiBaseUrl' => url('/api/v1'),
            'apiDocsUrl' => url('/docs'),
        ]);
    }

    public function cache(): View
    {
        return view('core/base::admin.settings.cache', [
            'settings' => $this->cacheSettings(),
            'baseSettings' => $this->generalSettings(),
            'sitemapUrl' => url('/sitemap.xml'),
        ]);
    }

    public function datatables(): View
    {
        return view('core/base::admin.settings.datatables', [
            'settings' => $this->datatablesSettings(),
            'baseSettings' => $this->generalSettings(),
            'paginationTypeOptions' => $this->datatablesPaginationTypeOptions(),
        ]);
    }

    public function websiteTracking(): View
    {
        return view('core/base::admin.settings.website-tracking', [
            'settings' => $this->websiteTrackingSettings(),
            'baseSettings' => $this->generalSettings(),
            'trackingTypeOptions' => $this->websiteTrackingTypeOptions(),
        ]);
    }

    public function optimize(): View
    {
        return view('core/base::admin.settings.optimize', [
            'settings' => $this->optimizeSettings(),
            'baseSettings' => $this->generalSettings(),
            'filters' => $this->optimizeFilters(),
        ]);
    }

    public function blog(): View
    {
        return view('core/base::admin.settings.blog', [
            'settings' => $this->blogSettings(),
            'baseSettings' => $this->generalSettings(),
            'schemaTypeOptions' => $this->blogSchemaTypeOptions(),
        ]);
    }

    public function members(): View
    {
        return view('core/base::admin.settings.members', [
            'settings' => $this->memberSettings(),
            'baseSettings' => $this->generalSettings(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $values = [
            'site_name' => $validated['site_name'],
            'site_logo' => $validated['site_logo'] ?? null,
            // A cleared robots.txt stores null so the live default kicks back in.
            'robots_txt' => $validated['robots_txt'] ?? null,
            // Absent field keeps the current theme (validation allows null).
            'active_theme' => $validated['active_theme'] ?? $this->settings->get('active_theme', ThemeManager::DEFAULT_THEME),
        ];

        if ($request->has('admin_emails')) {
            $values['admin_emails'] = json_encode(
                collect($validated['admin_emails'] ?? [])
                    ->filter()
                    ->values()
                    ->all()
            );
        }

        foreach ([
            'email_mailer',
            'email_log_channel',
            'email_smtp_port',
            'email_smtp_host',
            'email_smtp_username',
            'email_smtp_password',
            'email_smtp_local_domain',
            'email_smtp_encryption',
            'email_sendmail_path',
            'email_mailgun_domain',
            'email_mailgun_endpoint',
            'email_sendgrid_key',
            'email_ses_key',
            'email_ses_region',
            'email_sender_name',
            'email_sender_email',
            'default_email_language',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $values[$key] = $validated[$key];
            }
        }

        if ($request->has('email_template_statuses')) {
            $values['email_template_statuses'] = json_encode(array_values($validated['email_template_statuses'] ?? []));
        }

        foreach ([
            'timezone',
            'front_site_language_direction',
            'site_language',
            'clear_old_request_logs',
            'clear_old_audit_logs',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $values[$key] = $validated[$key];
            }
        }

        foreach ([
            'send_error_reporting_via_email',
            'redirect_404_to_homepage',
        ] as $key) {
            if ($request->has($key)) {
                $values[$key] = $request->boolean($key) ? '1' : '0';
            }
        }

        $this->settings->setMany($values);
        $this->settings->applyApplicationConfig();

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->to($request->input('_redirect', route('admin.settings.edit', [], false)));
    }

    public function sendTestEmail(): RedirectResponse
    {
        $settings = $this->emailSettings();

        Mail::raw('This is a test email from '.config('app.name', 'Sitewyn').'.', function ($message) use ($settings): void {
            $message
                ->to($settings['email_sender_email'])
                ->subject('Test email from '.config('app.name', 'Sitewyn'));
        });

        return redirect()
            ->route('admin.settings.email')
            ->with('status', 'Test email has been sent.');
    }

    public function updateEmail(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $values = [];

        foreach ([
            'email_mailer',
            'email_log_channel',
            'email_smtp_port',
            'email_smtp_host',
            'email_smtp_username',
            'email_smtp_password',
            'email_smtp_local_domain',
            'email_smtp_encryption',
            'email_sendmail_path',
            'email_mailgun_domain',
            'email_mailgun_endpoint',
            'email_sendgrid_key',
            'email_ses_key',
            'email_ses_region',
            'email_sender_name',
            'email_sender_email',
            'default_email_language',
        ] as $key) {
            if (array_key_exists($key, $validated)) {
                $values[$key] = $validated[$key];
            }
        }

        $values['email_template_statuses'] = json_encode(array_values($validated['email_template_statuses'] ?? []));

        $this->settings->setMany($values);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.email');
    }

    public function updateEmailTemplates(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'email_template_logo' => $validated['email_template_logo'] ?? null,
            'email_template_logo_url' => $validated['email_template_logo_url'] ?? null,
            'email_template_contact_email_address' => $validated['email_template_contact_email_address'] ?? null,
            'email_template_copyright' => $validated['email_template_copyright'] ?? null,
            'email_template_logo_height' => isset($validated['email_template_logo_height'])
                ? (string) $validated['email_template_logo_height']
                : null,
            'email_template_custom_css' => $validated['email_template_custom_css'] ?? null,
            'email_template_social_links' => json_encode($this->socialLinksFrom($validated)),
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.email.templates');
    }

    public function updateEmailRules(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'email_rules_blacklisted_domains' => $validated['email_rules_blacklisted_domains'] ?? null,
            'email_rules_blacklisted_addresses' => $validated['email_rules_blacklisted_addresses'] ?? null,
            'email_rules_exception_emails' => $validated['email_rules_exception_emails'] ?? null,
            'email_rules_strict_validation' => $request->boolean('email_rules_strict_validation') ? '1' : '0',
            'email_rules_dns_check_validation' => $request->boolean('email_rules_dns_check_validation') ? '1' : '0',
            'email_rules_spoofing_detection' => $request->boolean('email_rules_spoofing_detection') ? '1' : '0',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.email.rules');
    }

    public function updatePhoneNumber(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $selectedCountries = collect($validated['phone_number_available_countries'] ?? [])
            ->filter(fn (mixed $country): bool => is_string($country) && array_key_exists($country, $this->phoneCountryOptions()))
            ->values()
            ->all();

        $this->settings->setMany([
            'phone_number_enable_country_code' => $request->boolean('phone_number_enable_country_code') ? '1' : '0',
            'phone_number_available_countries_all' => $request->boolean('phone_number_available_countries_all') ? '1' : '0',
            'phone_number_available_countries' => json_encode($selectedCountries),
            'phone_number_minimum_length' => (string) ($validated['phone_number_minimum_length'] ?? 8),
            'phone_number_maximum_length' => (string) ($validated['phone_number_maximum_length'] ?? 15),
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.phone-number');
    }

    public function updateMedia(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $thumbnailSizes = [];

        foreach ($this->defaultThumbnailSizes() as $key => $size) {
            $thumbnailSizes[$key] = [
                'width' => (int) ($validated["media_thumbnail_{$key}_width"] ?? $size['width']),
                'height' => (int) ($validated["media_thumbnail_{$key}_height"] ?? $size['height']),
            ];
        }

        $this->settings->setMany([
            'media_driver' => $validated['media_driver'] ?? 'public',
            ...$this->validatedMediaDriverSettings($validated, $request),
            'media_use_original_name_for_file_path' => $request->boolean('media_use_original_name_for_file_path') ? '1' : '0',
            'media_convert_file_name_to_uuid' => $request->boolean('media_convert_file_name_to_uuid') ? '1' : '0',
            'media_keep_original_file_size_quality' => $request->boolean('media_keep_original_file_size_quality') ? '1' : '0',
            'media_image_quality' => (string) ($validated['media_image_quality'] ?? 75),
            'media_turn_off_automatic_url_translation_into_latin' => $request->boolean('media_turn_off_automatic_url_translation_into_latin') ? '1' : '0',
            'media_users_can_only_view_own_media' => $request->boolean('media_users_can_only_view_own_media') ? '1' : '0',
            'media_convert_image_to_webp' => $request->boolean('media_convert_image_to_webp') ? '1' : '0',
            'media_default_placeholder_image' => $validated['media_default_placeholder_image'] ?? null,
            'media_default_placeholder_image_url' => $validated['media_default_placeholder_image_url'] ?? null,
            'media_max_upload_filesize' => (string) ($validated['media_max_upload_filesize'] ?? 2),
            'media_reduce_large_image_size' => $request->boolean('media_reduce_large_image_size') ? '1' : '0',
            'media_customize_upload_path' => $request->boolean('media_customize_upload_path') ? '1' : '0',
            'media_enable_chunk_upload' => $request->boolean('media_enable_chunk_upload') ? '1' : '0',
            'media_enable_watermark' => $request->boolean('media_enable_watermark') ? '1' : '0',
            'media_image_processing_library' => $validated['media_image_processing_library'] ?? 'gd',
            'media_enable_thumbnail_sizes' => $request->boolean('media_enable_thumbnail_sizes') ? '1' : '0',
            'media_thumbnail_sizes' => json_encode($thumbnailSizes),
            'media_thumbnail_crop_position' => $validated['media_thumbnail_crop_position'] ?? 'center',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.media');
    }

    public function generateMediaThumbnails(): RedirectResponse
    {
        admin_flash()->success(__('Thumbnail generation has been queued.'));

        return redirect()
            ->route('admin.settings.media');
    }

    public function updatePermalink(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $values = [
            'permalink_turn_off_automatic_url_translation_into_latin' => $request->boolean('permalink_turn_off_automatic_url_translation_into_latin') ? '1' : '0',
        ];

        foreach ($this->permalinkFields() as $field) {
            $key = $field['key'];
            $value = (string) ($validated[$key] ?? '');

            $values[$key] = $field['type'] === 'postfix'
                ? trim($value)
                : trim($value, " \t\n\r\0\x0B/");
        }

        $this->settings->setMany($values);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.permalink');
    }

    public function updateAdminAppearance(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $backgrounds = collect($validated['admin_login_screen_backgrounds'] ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();
        $backgroundUrls = collect($validated['admin_login_screen_background_urls'] ?? [])
            ->filter(fn (mixed $value): bool => is_string($value) && $value !== '')
            ->values()
            ->all();

        $this->settings->setMany([
            'admin_logo' => $validated['admin_logo'] ?? null,
            'admin_logo_url' => $validated['admin_logo_url'] ?? null,
            'admin_logo_height' => (string) ($validated['admin_logo_height'] ?? 32),
            'admin_favicon' => $validated['admin_favicon'] ?? null,
            'admin_favicon_url' => $validated['admin_favicon_url'] ?? null,
            'admin_favicon_type' => $validated['admin_favicon_type'] ?? 'ico',
            'admin_login_screen_backgrounds' => json_encode($backgrounds),
            'admin_login_screen_background_urls' => json_encode($backgroundUrls),
            'admin_title' => $validated['admin_title'] ?? config('app.name', 'Sitewyn').' Admin',
            'admin_primary_font' => $validated['admin_primary_font'] ?? 'inter',
            'admin_primary_color' => $validated['admin_primary_color'] ?? '#206bc4',
            'admin_secondary_color' => $validated['admin_secondary_color'] ?? '#6c7a91',
            'admin_heading_color' => $validated['admin_heading_color'] ?? '#182433',
            'admin_text_color' => $validated['admin_text_color'] ?? '#182433',
            'admin_link_color' => $validated['admin_link_color'] ?? '#206bc4',
            'admin_link_hover_color' => $validated['admin_link_hover_color'] ?? '#1a569d',
            'admin_language' => $validated['admin_language'] ?? 'default',
            'admin_language_direction' => $validated['admin_language_direction'] ?? 'ltr',
            'admin_rich_editor' => $validated['admin_rich_editor'] ?? 'ckeditor',
            'admin_enable_page_visual_builder' => $request->boolean('admin_enable_page_visual_builder') ? '1' : '0',
            'admin_layout' => $validated['admin_layout'] ?? 'vertical',
            'admin_container_width' => $validated['admin_container_width'] ?? 'default',
            'admin_show_menu_item_icon' => $request->boolean('admin_show_menu_item_icon') ? '1' : '0',
            'admin_show_admin_bar' => $request->boolean('admin_show_admin_bar') ? '1' : '0',
            'admin_show_guidelines' => $request->boolean('admin_show_guidelines') ? '1' : '0',
            'admin_show_get_started_wizard' => $request->boolean('admin_show_get_started_wizard') ? '1' : '0',
            'admin_custom_css' => $validated['admin_custom_css'] ?? null,
            'admin_header_js' => $validated['admin_header_js'] ?? null,
            'admin_body_js' => $validated['admin_body_js'] ?? null,
            'admin_footer_js' => $validated['admin_footer_js'] ?? null,
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.admin-appearance');
    }

    public function updateApi(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'api_enabled' => $request->boolean('api_enabled') ? '1' : '0',
            'api_key' => $validated['api_key'] ?? null,
            'api_push_notifications_enabled' => $request->boolean('api_push_notifications_enabled') ? '1' : '0',
            'api_fcm_project_id' => $validated['api_fcm_project_id'] ?? null,
            'api_fcm_service_account_json' => $validated['api_fcm_service_account_json'] ?? null,
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.api');
    }

    public function updateCache(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'cache_admin_menu' => $request->boolean('cache_admin_menu') ? '1' : '0',
            'cache_front_menu' => $request->boolean('cache_front_menu') ? '1' : '0',
            'cache_user_avatar' => $request->boolean('cache_user_avatar') ? '1' : '0',
            'cache_shortcodes' => $request->boolean('cache_shortcodes') ? '1' : '0',
            'cache_shortcodes_duration' => $validated['cache_shortcodes_duration'] ?? '1800',
            'cache_widgets' => $request->boolean('cache_widgets') ? '1' : '0',
            'cache_widgets_duration' => $validated['cache_widgets_duration'] ?? '1800',
            'cache_installed_plugins' => $request->boolean('cache_installed_plugins') ? '1' : '0',
            'cache_size_warning_threshold' => $validated['cache_size_warning_threshold'] ?? '50',
            'cache_auto_clear_when_size_exceeds_threshold' => $request->boolean('cache_auto_clear_when_size_exceeds_threshold') ? '1' : '0',
            'cache_sitemap' => $request->boolean('cache_sitemap') ? '1' : '0',
            'cache_sitemap_timeout' => $validated['cache_sitemap_timeout'] ?? '60',
            'cache_public_headers' => $request->boolean('cache_public_headers') ? '1' : '0',
            'cache_public_duration' => $validated['cache_public_duration'] ?? '120',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.cache');
    }

    public function updateDatatables(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'datatables_pagination_type' => $validated['datatables_pagination_type'] ?? 'default',
            'datatables_show_column_visibility' => $request->boolean('datatables_show_column_visibility') ? '1' : '0',
            'datatables_show_export_button' => $request->boolean('datatables_show_export_button') ? '1' : '0',
            'datatables_enable_table_responsive' => $request->boolean('datatables_enable_table_responsive') ? '1' : '0',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.datatables');
    }

    public function updateWebsiteTracking(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'website_tracking_type' => $validated['website_tracking_type'] ?? 'gtm',
            'website_tracking_gtm_container_id' => $validated['website_tracking_gtm_container_id'] ?? null,
            'website_tracking_gtm_debug_mode' => $request->boolean('website_tracking_gtm_debug_mode') ? '1' : '0',
            'website_tracking_gtm_include_customer_data' => $request->boolean('website_tracking_gtm_include_customer_data') ? '1' : '0',
            'website_tracking_ga_measurement_id' => $validated['website_tracking_ga_measurement_id'] ?? null,
            'website_tracking_custom_header_script' => $validated['website_tracking_custom_header_script'] ?? null,
            'website_tracking_custom_body_code' => $validated['website_tracking_custom_body_code'] ?? null,
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.website-tracking');
    }

    public function updateOptimize(UpdateSettingsRequest $request): RedirectResponse
    {
        $this->settings->setMany([
            'optimize_page_speed_enabled' => $request->boolean('optimize_page_speed_enabled') ? '1' : '0',
            'optimize_collapse_whitespace' => $request->boolean('optimize_collapse_whitespace') ? '1' : '0',
            'optimize_elide_attributes' => $request->boolean('optimize_elide_attributes') ? '1' : '0',
            'optimize_inline_css' => $request->boolean('optimize_inline_css') ? '1' : '0',
            'optimize_insert_dns_prefetch' => $request->boolean('optimize_insert_dns_prefetch') ? '1' : '0',
            'optimize_remove_comments' => $request->boolean('optimize_remove_comments') ? '1' : '0',
            'optimize_remove_quotes' => $request->boolean('optimize_remove_quotes') ? '1' : '0',
            'optimize_defer_javascript' => $request->boolean('optimize_defer_javascript') ? '1' : '0',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.optimize');
    }

    public function updateBlog(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'blog_schema_enabled' => $request->boolean('blog_schema_enabled') ? '1' : '0',
            'blog_schema_type' => $validated['blog_schema_type'] ?? 'BlogPosting',
            'blog_anchor_links_enabled' => $request->boolean('blog_anchor_links_enabled') ? '1' : '0',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.blog');
    }

    public function updateMembers(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->settings->setMany([
            'member_allow_login' => $request->boolean('member_allow_login') ? '1' : '0',
            'member_allow_register' => $request->boolean('member_allow_register') ? '1' : '0',
            'member_verify_email' => $request->boolean('member_verify_email') ? '1' : '0',
            'member_verification_expiration' => (string) ($validated['member_verification_expiration'] ?? 60),
            'member_post_approval' => $request->boolean('member_post_approval') ? '1' : '0',
            'member_default_avatar' => $validated['member_default_avatar'] ?? null,
            'member_default_avatar_url' => $validated['member_default_avatar_url'] ?? null,
            'member_show_terms_policy_checkbox' => $request->boolean('member_show_terms_policy_checkbox') ? '1' : '0',
        ]);

        admin_flash()->success(__('Settings updated successfully.'));

        return redirect()
            ->route('admin.settings.members');
    }

    /**
     * @return array<int, array{title: string, items: array<int, array{title: string, description: string, icon: string, url: string}>}>
     */
    private function sections(): array
    {
        return [
            [
                'title' => 'Common',
                'items' => [
                    ['title' => 'General', 'description' => 'View and update your general settings and activate license', 'icon' => 'settings', 'url' => route('admin.settings.general', [], false)],
                    ['title' => 'Email', 'description' => 'View and update your email settings and email templates', 'icon' => 'request-log', 'url' => route('admin.settings.email', [], false)],
                    ['title' => 'Email templates', 'description' => 'Email templates using HTML & system variables.', 'icon' => 'request-log', 'url' => route('admin.settings.email.templates', [], false)],
                    ['title' => 'Email rules', 'description' => 'Configure email rules for validation', 'icon' => 'request-log', 'url' => route('admin.settings.email.rules', [], false)],
                    ['title' => 'Phone Number', 'description' => 'Configure phone number field settings', 'icon' => 'users', 'url' => route('admin.settings.phone-number', [], false)],
                    ['title' => 'Media', 'description' => 'View and update your media settings', 'icon' => 'category', 'url' => route('admin.settings.media', [], false)],
                    ['title' => 'Permalink', 'description' => 'View and update your permalink settings', 'icon' => 'route', 'url' => route('admin.settings.permalink', [], false)],
                    ['title' => 'Languages', 'description' => 'View and update your website languages', 'icon' => 'globe', 'url' => route('admin.settings.languages.index', [], false)],
                    ['title' => 'Admin appearance', 'description' => 'View and update logo, favicon, layout,...', 'icon' => 'settings', 'url' => route('admin.settings.admin-appearance', [], false)],
                    ['title' => 'API Settings', 'description' => 'View and update your API settings', 'icon' => 'key', 'url' => route('admin.settings.api', [], false)],
                    ['title' => 'Cache', 'description' => 'Configure caching for optimized speed', 'icon' => 'reload', 'url' => route('admin.settings.cache', [], false)],
                    ['title' => 'Datatables', 'description' => 'Settings for datatables', 'icon' => 'database', 'url' => route('admin.settings.datatables', [], false)],
                    ['title' => 'Website Tracking', 'description' => 'Choose your preferred analytics and tracking method. Only one option can be active at a time.', 'icon' => 'globe', 'url' => route('admin.settings.website-tracking', [], false)],
                    ['title' => 'Optimize', 'description' => 'Minify HTML output, inline CSS, remove comments...', 'icon' => 'bolt', 'url' => route('admin.settings.optimize', [], false)],
                ],
            ],
            [
                'title' => 'Localization',
                'items' => [
                    ['title' => 'Locales', 'description' => 'View, download and import locales', 'icon' => 'globe', 'url' => route('admin.translations.locales.index', [], false)],
                    ['title' => 'Theme Translations', 'description' => 'Manage the theme translations', 'icon' => 'globe', 'url' => '#'],
                    ['title' => 'Other Translations', 'description' => 'Manage the other translations (admin, plugins, packages...)', 'icon' => 'request-log', 'url' => '#'],
                ],
            ],
            [
                'title' => 'Others',
                'items' => [
                    ['title' => 'FOB Comment', 'description' => 'Configure settings for FOB Comment', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Social Login', 'description' => 'View and update your social login settings', 'icon' => 'users', 'url' => '#'],
                    ['title' => 'Blog', 'description' => 'View and update blog settings', 'icon' => 'post', 'url' => route('admin.settings.blog', [], false)],
                    ['title' => 'Contact', 'description' => 'Settings for contact plugin', 'icon' => 'request-log', 'url' => '#'],
                    ['title' => 'Captcha', 'description' => 'View and update reCAPTCHA and Math CAPTCHA.', 'icon' => 'reload', 'url' => '#'],
                    ['title' => 'Google Analytics', 'description' => 'Config Credentials for Google Analytics', 'icon' => 'audit', 'url' => '#'],
                    ['title' => 'Member', 'description' => 'View and update member settings', 'icon' => 'users', 'url' => route('admin.settings.members', [], false)],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generalSettings(): array
    {
        return [
            'site_name' => $this->settings->get('site_name', config('app.name', 'Sitewyn')),
            'site_logo' => $this->settings->get('site_logo'),
            'robots_txt' => RobotsTxt::content($this->settings->get('robots_txt')),
            'active_theme' => $this->settings->get('active_theme', ThemeManager::DEFAULT_THEME),
            'admin_emails' => $this->adminEmails(),
            'timezone' => $this->settings->get('timezone', config('app.timezone', 'UTC')),
            'front_site_language_direction' => $this->settings->get('front_site_language_direction', 'ltr'),
            'site_language' => $this->settings->get('site_language', $this->defaultLanguageCode()),
            'send_error_reporting_via_email' => $this->settings->get('send_error_reporting_via_email', '0') === '1',
            'redirect_404_to_homepage' => $this->settings->get('redirect_404_to_homepage', '0') === '1',
            'clear_old_request_logs' => $this->settings->get('clear_old_request_logs', '1_month'),
            'clear_old_audit_logs' => $this->settings->get('clear_old_audit_logs', '1_month'),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function adminEmails(): array
    {
        $stored = $this->settings->get('admin_emails');
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        if (is_array($decoded) && $decoded !== []) {
            return collect($decoded)
                ->filter(fn (mixed $email): bool => is_string($email) && $email !== '')
                ->values()
                ->all();
        }

        return [config('mail.from.address', 'admin@example.com')];
    }

    /**
     * @return array<string, string>
     */
    private function timezoneOptions(): array
    {
        return collect(DateTimeZone::listIdentifiers())
            ->mapWithKeys(fn (string $timezone): array => [$timezone => $timezone])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function languageOptions(): array
    {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Language $language): array => [$language->code => $language->name.' - '.$language->code])
            ->all();

        return $languages === [] ? ['en' => 'English - en'] : $languages;
    }

    private function defaultLanguageCode(): string
    {
        return (string) (Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->value('code') ?? 'en');
    }

    /**
     * @return array<string, string>
     */
    private function logRetentionOptions(): array
    {
        return [
            '1_month' => '1 Month',
            '3_months' => '3 Months',
            '6_months' => '6 Months',
            '1_year' => '1 Year',
            'never' => 'Never',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emailSettings(): array
    {
        return [
            'email_mailer' => $this->settings->get('email_mailer'),
            'email_log_channel' => $this->settings->get('email_log_channel', config('mail.mailers.log.channel') ?: 'single'),
            'email_smtp_port' => $this->settings->get('email_smtp_port'),
            'email_smtp_host' => $this->settings->get('email_smtp_host'),
            'email_smtp_username' => $this->settings->get('email_smtp_username'),
            'email_smtp_password' => $this->settings->get('email_smtp_password'),
            'email_smtp_local_domain' => $this->settings->get('email_smtp_local_domain', parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost'),
            'email_smtp_encryption' => $this->settings->get('email_smtp_encryption', 'none'),
            'email_sendmail_path' => $this->settings->get('email_sendmail_path', config('mail.mailers.sendmail.path', '/usr/sbin/sendmail -bs -i')),
            'email_mailgun_domain' => $this->settings->get('email_mailgun_domain', config('services.mailgun.domain')),
            'email_mailgun_endpoint' => $this->settings->get('email_mailgun_endpoint', config('services.mailgun.endpoint', 'api.mailgun.net')),
            'email_sendgrid_key' => $this->settings->get('email_sendgrid_key', config('services.sendgrid.key')),
            'email_ses_key' => $this->settings->get('email_ses_key', config('services.ses.key')),
            'email_ses_region' => $this->settings->get('email_ses_region', config('services.ses.region', 'us-east-1')),
            'email_sender_name' => $this->settings->get('email_sender_name', config('mail.from.name', config('app.name', 'Sitewyn'))),
            'email_sender_email' => $this->settings->get('email_sender_email', config('mail.from.address', 'hello@example.com')),
            'default_email_language' => $this->settings->get('default_email_language', 'auto'),
            'email_template_statuses' => $this->emailTemplateStatuses(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emailTemplateSettings(): array
    {
        return [
            'email_template_logo' => $this->settings->get('email_template_logo'),
            'email_template_logo_url' => $this->settings->get('email_template_logo_url'),
            'email_template_contact_email_address' => $this->settings->get('email_template_contact_email_address'),
            'email_template_copyright' => $this->settings->get('email_template_copyright', '©'.now()->year.' Your Company. All rights reserved.'),
            'email_template_logo_height' => $this->settings->get('email_template_logo_height', '40'),
            'email_template_custom_css' => $this->settings->get('email_template_custom_css'),
            'email_template_social_links' => $this->emailTemplateSocialLinks(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emailRuleSettings(): array
    {
        return [
            'email_rules_blacklisted_domains' => $this->settings->get('email_rules_blacklisted_domains'),
            'email_rules_blacklisted_addresses' => $this->settings->get('email_rules_blacklisted_addresses'),
            'email_rules_exception_emails' => $this->settings->get('email_rules_exception_emails'),
            'email_rules_strict_validation' => $this->settings->get('email_rules_strict_validation', '0') === '1',
            'email_rules_dns_check_validation' => $this->settings->get('email_rules_dns_check_validation', '0') === '1',
            'email_rules_spoofing_detection' => $this->settings->get('email_rules_spoofing_detection', '0') === '1',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function phoneNumberSettings(): array
    {
        $storedCountries = $this->settings->get('phone_number_available_countries');
        $decodedCountries = is_string($storedCountries) ? json_decode($storedCountries, true) : null;
        $countryOptions = $this->phoneCountryOptions();

        $selectedCountries = is_array($decodedCountries)
            ? collect($decodedCountries)
                ->filter(fn (mixed $country): bool => is_string($country) && array_key_exists($country, $countryOptions))
                ->values()
                ->all()
            : array_keys($countryOptions);

        return [
            'phone_number_enable_country_code' => $this->settings->get('phone_number_enable_country_code', '1') === '1',
            'phone_number_available_countries_all' => $this->settings->get('phone_number_available_countries_all', '1') === '1',
            'phone_number_available_countries' => $selectedCountries,
            'phone_number_minimum_length' => $this->settings->get('phone_number_minimum_length', '8'),
            'phone_number_maximum_length' => $this->settings->get('phone_number_maximum_length', '15'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaSettings(): array
    {
        $storedThumbnailSizes = $this->settings->get('media_thumbnail_sizes');
        $decodedThumbnailSizes = is_string($storedThumbnailSizes) ? json_decode($storedThumbnailSizes, true) : null;
        $thumbnailSizes = collect($this->defaultThumbnailSizes())
            ->map(function (array $size, string $key) use ($decodedThumbnailSizes): array {
                $storedSize = is_array($decodedThumbnailSizes) && is_array($decodedThumbnailSizes[$key] ?? null)
                    ? $decodedThumbnailSizes[$key]
                    : [];

                return [
                    'label' => $size['label'],
                    'width' => (string) ($storedSize['width'] ?? $size['width']),
                    'height' => (string) ($storedSize['height'] ?? $size['height']),
                ];
            })
            ->all();

        return [
            'media_driver' => $this->settings->get('media_driver', 'public'),
            ...$this->storedMediaDriverSettings(),
            'media_use_original_name_for_file_path' => $this->settings->get('media_use_original_name_for_file_path', '0') === '1',
            'media_convert_file_name_to_uuid' => $this->settings->get('media_convert_file_name_to_uuid', '0') === '1',
            'media_keep_original_file_size_quality' => $this->settings->get('media_keep_original_file_size_quality', '0') === '1',
            'media_image_quality' => $this->settings->get('media_image_quality', '75'),
            'media_turn_off_automatic_url_translation_into_latin' => $this->settings->get('media_turn_off_automatic_url_translation_into_latin', '0') === '1',
            'media_users_can_only_view_own_media' => $this->settings->get('media_users_can_only_view_own_media', '0') === '1',
            'media_convert_image_to_webp' => $this->settings->get('media_convert_image_to_webp', '0') === '1',
            'media_default_placeholder_image' => $this->settings->get('media_default_placeholder_image'),
            'media_default_placeholder_image_url' => $this->settings->get('media_default_placeholder_image_url'),
            'media_max_upload_filesize' => $this->settings->get('media_max_upload_filesize', '2'),
            'media_reduce_large_image_size' => $this->settings->get('media_reduce_large_image_size', '0') === '1',
            'media_customize_upload_path' => $this->settings->get('media_customize_upload_path', '0') === '1',
            'media_enable_chunk_upload' => $this->settings->get('media_enable_chunk_upload', '0') === '1',
            'media_enable_watermark' => $this->settings->get('media_enable_watermark', '0') === '1',
            'media_image_processing_library' => $this->settings->get('media_image_processing_library', 'gd'),
            'media_enable_thumbnail_sizes' => $this->settings->get('media_enable_thumbnail_sizes', '1') === '1',
            'media_thumbnail_sizes' => $thumbnailSizes,
            'media_thumbnail_crop_position' => $this->settings->get('media_thumbnail_crop_position', 'center'),
            'server_max_upload_filesize' => $this->serverMaxUploadFilesize(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function permalinkSettings(): array
    {
        $settings = [
            'permalink_turn_off_automatic_url_translation_into_latin' => $this->settings->get('permalink_turn_off_automatic_url_translation_into_latin', '0') === '1',
        ];

        foreach ($this->permalinkFields() as $field) {
            $settings[$field['key']] = $this->settings->get($field['key'], $field['default']);
        }

        return $settings;
    }

    /**
     * @return array<string, mixed>
     */
    private function adminAppearanceSettings(): array
    {
        return [
            'admin_logo' => $this->settings->get('admin_logo'),
            'admin_logo_url' => $this->settings->get('admin_logo_url'),
            'admin_logo_height' => $this->settings->get('admin_logo_height', '32'),
            'admin_favicon' => $this->settings->get('admin_favicon'),
            'admin_favicon_url' => $this->settings->get('admin_favicon_url'),
            'admin_favicon_type' => $this->settings->get('admin_favicon_type', 'ico'),
            'admin_login_screen_backgrounds' => $this->jsonStringList('admin_login_screen_backgrounds'),
            'admin_login_screen_background_urls' => $this->jsonStringList('admin_login_screen_background_urls'),
            'admin_title' => $this->settings->get('admin_title', config('app.name', 'Sitewyn').' Admin'),
            'admin_primary_font' => $this->settings->get('admin_primary_font', 'inter'),
            'admin_primary_color' => $this->settings->get('admin_primary_color', '#206bc4'),
            'admin_secondary_color' => $this->settings->get('admin_secondary_color', '#6c7a91'),
            'admin_heading_color' => $this->settings->get('admin_heading_color', '#182433'),
            'admin_text_color' => $this->settings->get('admin_text_color', '#182433'),
            'admin_link_color' => $this->settings->get('admin_link_color', '#206bc4'),
            'admin_link_hover_color' => $this->settings->get('admin_link_hover_color', '#1a569d'),
            'admin_language' => $this->settings->get('admin_language', 'default'),
            'admin_language_direction' => $this->settings->get('admin_language_direction', 'ltr'),
            'admin_rich_editor' => $this->settings->get('admin_rich_editor', 'ckeditor'),
            'admin_enable_page_visual_builder' => $this->settings->get('admin_enable_page_visual_builder', '1') === '1',
            'admin_layout' => $this->settings->get('admin_layout', 'vertical'),
            'admin_container_width' => $this->settings->get('admin_container_width', 'default'),
            'admin_show_menu_item_icon' => $this->settings->get('admin_show_menu_item_icon', '1') === '1',
            'admin_show_admin_bar' => $this->settings->get('admin_show_admin_bar', '1') === '1',
            'admin_show_guidelines' => $this->settings->get('admin_show_guidelines', '0') === '1',
            'admin_show_get_started_wizard' => $this->settings->get('admin_show_get_started_wizard', '1') === '1',
            'admin_custom_css' => $this->settings->get('admin_custom_css'),
            'admin_header_js' => $this->settings->get('admin_header_js'),
            'admin_body_js' => $this->settings->get('admin_body_js'),
            'admin_footer_js' => $this->settings->get('admin_footer_js'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function adminFaviconTypeOptions(): array
    {
        return [
            'ico' => 'ICO',
            'png' => 'PNG',
            'svg' => 'SVG',
            'gif' => 'GIF',
            'jpeg' => 'JPEG',
            'webp' => 'WebP',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apiSettings(): array
    {
        return [
            'api_enabled' => $this->settings->get('api_enabled', '0') === '1',
            'api_key' => $this->settings->get('api_key'),
            'api_push_notifications_enabled' => $this->settings->get('api_push_notifications_enabled', '0') === '1',
            'api_fcm_project_id' => $this->settings->get('api_fcm_project_id'),
            'api_fcm_service_account_json' => $this->settings->get('api_fcm_service_account_json'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cacheSettings(): array
    {
        return [
            'cache_admin_menu' => $this->settings->get('cache_admin_menu', '1') === '1',
            'cache_front_menu' => $this->settings->get('cache_front_menu', '1') === '1',
            'cache_user_avatar' => $this->settings->get('cache_user_avatar', '1') === '1',
            'cache_shortcodes' => $this->settings->get('cache_shortcodes', '1') === '1',
            'cache_shortcodes_duration' => $this->settings->get('cache_shortcodes_duration', '1800'),
            'cache_widgets' => $this->settings->get('cache_widgets', '1') === '1',
            'cache_widgets_duration' => $this->settings->get('cache_widgets_duration', '1800'),
            'cache_installed_plugins' => $this->settings->get('cache_installed_plugins', '1') === '1',
            'cache_size_warning_threshold' => $this->settings->get('cache_size_warning_threshold', '50'),
            'cache_auto_clear_when_size_exceeds_threshold' => $this->settings->get('cache_auto_clear_when_size_exceeds_threshold', '0') === '1',
            'cache_sitemap' => $this->settings->get('cache_sitemap', '1') === '1',
            'cache_sitemap_timeout' => $this->settings->get('cache_sitemap_timeout', '60'),
            'cache_public_headers' => $this->settings->get('cache_public_headers', '1') === '1',
            'cache_public_duration' => $this->settings->get('cache_public_duration', '120'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function datatablesSettings(): array
    {
        return [
            'datatables_pagination_type' => $this->settings->get('datatables_pagination_type', 'default'),
            'datatables_show_column_visibility' => $this->settings->get('datatables_show_column_visibility', '0') === '1',
            'datatables_show_export_button' => $this->settings->get('datatables_show_export_button', '0') === '1',
            'datatables_enable_table_responsive' => $this->settings->get('datatables_enable_table_responsive', '1') === '1',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function datatablesPaginationTypeOptions(): array
    {
        return [
            'default' => 'Default',
            'dropdown' => 'Dropdown',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function websiteTrackingSettings(): array
    {
        return [
            'website_tracking_type' => $this->settings->get('website_tracking_type', 'gtm'),
            'website_tracking_gtm_container_id' => $this->settings->get('website_tracking_gtm_container_id'),
            'website_tracking_gtm_debug_mode' => $this->settings->get('website_tracking_gtm_debug_mode', '0') === '1',
            'website_tracking_gtm_include_customer_data' => $this->settings->get('website_tracking_gtm_include_customer_data', '0') === '1',
            'website_tracking_ga_measurement_id' => $this->settings->get('website_tracking_ga_measurement_id'),
            'website_tracking_custom_header_script' => $this->settings->get('website_tracking_custom_header_script'),
            'website_tracking_custom_body_code' => $this->settings->get('website_tracking_custom_body_code'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function websiteTrackingTypeOptions(): array
    {
        return [
            'gtm' => 'Google Tag Manager (Recommended)',
            'ga' => 'Google Analytics Only',
            'custom' => 'Custom Tracking Code',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function optimizeSettings(): array
    {
        return [
            'optimize_page_speed_enabled' => $this->settings->get('optimize_page_speed_enabled', '1') === '1',
            'optimize_collapse_whitespace' => $this->settings->get('optimize_collapse_whitespace', '1') === '1',
            'optimize_elide_attributes' => $this->settings->get('optimize_elide_attributes', '1') === '1',
            'optimize_inline_css' => $this->settings->get('optimize_inline_css', '1') === '1',
            'optimize_insert_dns_prefetch' => $this->settings->get('optimize_insert_dns_prefetch', '1') === '1',
            'optimize_remove_comments' => $this->settings->get('optimize_remove_comments', '1') === '1',
            'optimize_remove_quotes' => $this->settings->get('optimize_remove_quotes', '1') === '1',
            'optimize_defer_javascript' => $this->settings->get('optimize_defer_javascript', '0') === '1',
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, description: string}>
     */
    private function optimizeFilters(): array
    {
        return [
            [
                'key' => 'optimize_collapse_whitespace',
                'label' => 'Collapse white space',
                'description' => 'This filter reduces bytes transmitted in an HTML file by removing unnecessary whitespace.',
            ],
            [
                'key' => 'optimize_elide_attributes',
                'label' => 'Elide attributes',
                'description' => 'This filter reduces the transfer size of HTML files by removing attributes from tags when the specified value is equal to the default value for that attribute. This can save a modest number of bytes, and may make the document more compressible by canonicalizing the affected tags.',
            ],
            [
                'key' => 'optimize_inline_css',
                'label' => 'Inline CSS',
                'description' => 'This filter transforms the inline "style" attribute of tags into classes by moving the CSS to the header.',
            ],
            [
                'key' => 'optimize_insert_dns_prefetch',
                'label' => 'Insert DNS prefetch',
                'description' => 'This filter injects tags in the HEAD to enable the browser to do DNS prefetching.',
            ],
            [
                'key' => 'optimize_remove_comments',
                'label' => 'Remove comments',
                'description' => 'This filter eliminates HTML, JS and CSS comments. The filter reduces the transfer size of HTML files by removing the comments. Depending on the HTML file, this filter can significantly reduce the number of bytes transmitted on the network.',
            ],
            [
                'key' => 'optimize_remove_quotes',
                'label' => 'Remove quotes',
                'description' => 'This filter eliminates unnecessary quotation marks from HTML attributes. While required by the various HTML specifications, browsers permit their omission when the value of an attribute is composed of a certain subset of characters (alphanumerics and some punctuation characters).',
            ],
            [
                'key' => 'optimize_defer_javascript',
                'label' => 'Defer javascript',
                'description' => 'Defers the execution of javascript in the HTML. If necessary cancel deferring in some script, use data-pagespeed-no-defer as script attribute to cancel deferring.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blogSettings(): array
    {
        return [
            'blog_schema_enabled' => $this->settings->get('blog_schema_enabled', '1') === '1',
            'blog_schema_type' => $this->settings->get('blog_schema_type', 'BlogPosting'),
            'blog_anchor_links_enabled' => $this->settings->get('blog_anchor_links_enabled', '0') === '1',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function blogSchemaTypeOptions(): array
    {
        return [
            'Article' => 'Article',
            'BlogPosting' => 'BlogPosting',
            'NewsArticle' => 'NewsArticle',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function memberSettings(): array
    {
        return [
            'member_allow_login' => $this->settings->get('member_allow_login', '1') === '1',
            'member_allow_register' => $this->settings->get('member_allow_register', '1') === '1',
            'member_verify_email' => $this->settings->get('member_verify_email', '0') === '1',
            'member_verification_expiration' => $this->settings->get('member_verification_expiration', '60'),
            'member_post_approval' => $this->settings->get('member_post_approval', '1') === '1',
            'member_default_avatar' => $this->settings->get('member_default_avatar'),
            'member_default_avatar_url' => $this->settings->get('member_default_avatar_url'),
            'member_show_terms_policy_checkbox' => $this->settings->get('member_show_terms_policy_checkbox', '1') === '1',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function jsonStringList(string $key): array
    {
        $stored = $this->settings->get($key);
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        return is_array($decoded)
            ? collect($decoded)->filter(fn (mixed $value): bool => is_string($value) && $value !== '')->values()->all()
            : [];
    }

    /**
     * @return array<int, array{key: string, label: string, placeholder: string, default: string, type: string}>
     */
    private function permalinkFields(): array
    {
        return [
            [
                'key' => 'permalink_pages_prefix',
                'label' => 'Prefix for Pages',
                'placeholder' => 'e.g., pages',
                'default' => '',
                'type' => 'prefix',
            ],
            [
                'key' => 'permalink_blog_posts_prefix',
                'label' => 'Prefix for Blog posts',
                'placeholder' => 'e.g., blog posts',
                'default' => '',
                'type' => 'prefix',
            ],
            [
                'key' => 'permalink_blog_categories_prefix',
                'label' => 'Prefix for Blog categories',
                'placeholder' => 'e.g., blog categories',
                'default' => '',
                'type' => 'prefix',
            ],
            [
                'key' => 'permalink_blog_tags_prefix',
                'label' => 'Prefix for Blog tags',
                'placeholder' => 'e.g., blog tags',
                'default' => 'tag',
                'type' => 'prefix',
            ],
            [
                'key' => 'permalink_galleries_prefix',
                'label' => 'Prefix for Galleries',
                'placeholder' => 'e.g., galleries',
                'default' => 'galleries',
                'type' => 'prefix',
            ],
            [
                'key' => 'permalink_member_prefix',
                'label' => 'Prefix for Botble\\Member\\Models\\Member',
                'placeholder' => 'e.g., author',
                'default' => 'author',
                'type' => 'prefix',
            ],
            [
                'key' => 'permalink_single_page_postfix',
                'label' => 'Postfix for single page URL',
                'placeholder' => '.html',
                'default' => '',
                'type' => 'postfix',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mediaDriverOptions(): array
    {
        return [
            'public' => 'Local disk',
            's3' => 'Amazon S3',
            'cloudflare_r2' => 'Cloudflare R2',
            'digitalocean_spaces' => 'DigitalOcean Spaces',
            'wasabi' => 'Wasabi',
            'bunnycdn' => 'BunnyCDN',
            'backblaze_b2' => 'Backblaze B2',
        ];
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function mediaDriverCredentialGroups(): array
    {
        return [
            's3' => [
                ['key' => 'media_s3_access_key_id', 'label' => 'AWS Access Key ID', 'placeholder' => 'Ex: AKIAIKYXBSNBXXXXXX'],
                ['key' => 'media_s3_secret_key', 'label' => 'AWS Secret Key', 'placeholder' => 'Ex: +fivlGCeTJCVVnzpM2WfzzrFIMLHGhxxxxxx'],
                ['key' => 'media_s3_default_region', 'label' => 'AWS Default Region', 'placeholder' => 'Ex: ap-southeast-1'],
                ['key' => 'media_s3_bucket', 'label' => 'AWS Bucket', 'placeholder' => 'Ex: sitewyn'],
                ['key' => 'media_s3_url', 'label' => 'AWS URL', 'placeholder' => 'Ex: https://s3-ap-southeast-1.amazonaws.com/sitewyn'],
                ['key' => 'media_s3_endpoint', 'label' => 'AWS Endpoint (Optional)', 'placeholder' => 'Optional'],
                ['key' => 'media_s3_custom_path', 'label' => 'Custom S3 Path (Optional)', 'placeholder' => 'Optional custom path in S3 bucket (e.g., uploads/media)', 'hint' => 'Optional custom path in S3 bucket (e.g., uploads/media)'],
                ['key' => 'media_s3_use_path_style_endpoint', 'label' => 'Use path style endpoint', 'type' => 'select', 'options' => $this->yesNoOptions()],
            ],
            'cloudflare_r2' => [
                ['key' => 'media_r2_access_key_id', 'label' => 'R2 Access Key ID', 'placeholder' => 'Ex: AKIAIKYXBSNBXXXXXX'],
                ['key' => 'media_r2_secret_key', 'label' => 'R2 Secret Key', 'placeholder' => 'Ex: +fivlGCeTJCVVnzpM2WfzzrFIMLHGhxxxxxx'],
                ['key' => 'media_r2_bucket', 'label' => 'R2 Bucket', 'placeholder' => 'Ex: sitewyn'],
                ['key' => 'media_r2_url', 'label' => 'R2 URL', 'placeholder' => 'Ex: https://pub-f70218cc331a40689xxx.r2.dev'],
                ['key' => 'media_r2_endpoint', 'label' => 'R2 Endpoint', 'placeholder' => 'Ex: https://xxx.r2.cloudflarestorage.com'],
                ['key' => 'media_r2_use_path_style_endpoint', 'label' => 'Use path style endpoint', 'type' => 'select', 'options' => $this->yesNoOptions()],
            ],
            'digitalocean_spaces' => [
                ['key' => 'media_do_spaces_access_key_id', 'label' => 'DO Spaces Access Key ID', 'placeholder' => 'Ex: AKIAIKYXBSNBXXXXXX'],
                ['key' => 'media_do_spaces_secret_key', 'label' => 'DO Spaces Secret Key', 'placeholder' => 'Ex: +fivlGCeTJCVVnzpM2WfzzrFIMLHGhxxxxxx'],
                ['key' => 'media_do_spaces_default_region', 'label' => 'DO Spaces Default Region', 'placeholder' => 'Ex: SGP1'],
                ['key' => 'media_do_spaces_bucket', 'label' => 'DO Spaces Bucket', 'placeholder' => 'Ex: sitewyn'],
                ['key' => 'media_do_spaces_endpoint', 'label' => 'DO Spaces Endpoint', 'placeholder' => 'Ex: https://sitewyn.sfo2.digitaloceanspaces.com'],
                ['key' => 'media_do_spaces_cdn_enabled', 'label' => 'Is DO Spaces CDN enabled?', 'type' => 'checkbox', 'hint' => 'When enabled, media files will be served through DigitalOcean Spaces CDN for faster global content delivery. You can optionally configure a custom CDN domain below.'],
                ['key' => 'media_do_spaces_cdn_custom_domain', 'label' => 'Do Spaces CDN custom domain', 'placeholder' => 'https://your-custom-domain.com'],
                ['key' => 'media_do_spaces_use_path_style_endpoint', 'label' => 'Use path style endpoint', 'type' => 'select', 'options' => $this->yesNoOptions()],
            ],
            'wasabi' => [
                ['key' => 'media_wasabi_access_key_id', 'label' => 'Wasabi Access Key ID', 'placeholder' => 'Ex: AKIAIKYXBSNBXXXXXX'],
                ['key' => 'media_wasabi_secret_key', 'label' => 'Wasabi Secret Key', 'placeholder' => 'Ex: +fivlGCeTJCVVnzpM2WfzzrFIMLHGhxxxxxx'],
                ['key' => 'media_wasabi_default_region', 'label' => 'Wasabi Default Region', 'placeholder' => 'Ex: us-east-1'],
                ['key' => 'media_wasabi_bucket', 'label' => 'Wasabi Bucket', 'placeholder' => 'Ex: sitewyn'],
                ['key' => 'media_wasabi_root', 'label' => 'Wasabi Root', 'placeholder' => 'Default: /', 'hint' => 'To reuse existing images, simply designate the Wasabi root as "/", then upload all current files from public/storage to your Wasabi root directory.'],
                ['key' => 'media_wasabi_cdn_enabled', 'label' => 'Is Wasabi CDN enabled?', 'type' => 'checkbox', 'hint' => 'When enabled, media files will be served through a custom CDN domain for faster global content delivery. You must configure a custom CDN domain below.'],
            ],
            'bunnycdn' => [
                ['key' => 'media_bunnycdn_zone_name', 'label' => 'Zone Name (The name of your storage zone)', 'placeholder' => 'Ex: sitewyn'],
                ['key' => 'media_bunnycdn_hostname', 'label' => 'Hostname', 'placeholder' => 'Ex: sitewyn.b-cdn.net'],
                ['key' => 'media_bunnycdn_access_password', 'label' => 'FTP & API Access Password (The storage zone API Access Password)', 'placeholder' => 'Ex: 9a734df7-844b-...'],
                ['key' => 'media_bunnycdn_region', 'label' => 'Region (The storage zone region)', 'type' => 'select', 'options' => $this->bunnyCdnRegionOptions()],
            ],
            'backblaze_b2' => [
                ['key' => 'media_backblaze_access_key_id', 'label' => 'Backblaze Access Key ID', 'placeholder' => 'Ex: 005febe473bdd490000xxxxxxx'],
                ['key' => 'media_backblaze_secret_key', 'label' => 'Backblaze Secret Key', 'placeholder' => 'Ex: K005C3JkwgUkUSh+4bZLoTkiBxxxxxx'],
                ['key' => 'media_backblaze_default_region', 'label' => 'Backblaze Default Region', 'placeholder' => 'Ex: eu-central-003'],
                ['key' => 'media_backblaze_bucket', 'label' => 'Backblaze Bucket', 'placeholder' => 'Ex: sitewyn'],
                ['key' => 'media_backblaze_endpoint', 'label' => 'Backblaze Endpoint', 'placeholder' => 'Ex: https://s3.eu-central-003.backblazeb2.com'],
                ['key' => 'media_backblaze_use_path_style_endpoint', 'label' => 'Use path style endpoint', 'type' => 'select', 'options' => $this->yesNoOptions()],
                ['key' => 'media_backblaze_cdn_enabled', 'label' => 'Is Backblaze CDN enabled?', 'type' => 'checkbox', 'hint' => 'When enabled, media files will be served through a CDN for faster global content delivery. You must configure a custom CDN domain below (e.g., Cloudflare CDN or Backblaze CDN).'],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function yesNoOptions(): array
    {
        return [
            '0' => 'No',
            '1' => 'Yes',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function bunnyCdnRegionOptions(): array
    {
        return [
            'de' => 'Falkenstein',
            'ny' => 'New York',
            'la' => 'Los Angeles',
            'sg' => 'Singapore',
            'syd' => 'Sydney',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function storedMediaDriverSettings(): array
    {
        $settings = [];

        foreach ($this->mediaDriverCredentialGroups() as $fields) {
            foreach ($fields as $field) {
                $key = $field['key'];
                $default = $field['default'] ?? '';

                if (($field['type'] ?? 'text') === 'select') {
                    $default = array_key_first($field['options'] ?? []) ?? '';
                }

                $settings[$key] = ($field['type'] ?? 'text') === 'checkbox'
                    ? $this->settings->get($key, '0') === '1'
                    : $this->settings->get($key, (string) $default);
            }
        }

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, string|null>
     */
    private function validatedMediaDriverSettings(array $validated, UpdateSettingsRequest $request): array
    {
        $settings = [];

        foreach ($this->mediaDriverCredentialGroups() as $fields) {
            foreach ($fields as $field) {
                $key = $field['key'];

                $settings[$key] = ($field['type'] ?? 'text') === 'checkbox'
                    ? ($request->boolean($key) ? '1' : '0')
                    : ($validated[$key] ?? null);
            }
        }

        return $settings;
    }

    /**
     * @return array<string, string>
     */
    private function imageProcessingLibraries(): array
    {
        return [
            'gd' => 'GD Library',
            'imagick' => 'Imagick',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function thumbnailCropPositions(): array
    {
        return [
            'top-left' => 'Top left',
            'top' => 'Top',
            'top-right' => 'Top right',
            'left' => 'Left',
            'center' => 'Center',
            'right' => 'Right',
            'bottom-left' => 'Bottom left',
            'bottom' => 'Bottom',
            'bottom-right' => 'Bottom right',
        ];
    }

    /**
     * @return array<string, array{label: string, width: int, height: int}>
     */
    private function defaultThumbnailSizes(): array
    {
        return [
            'thumb' => ['label' => 'Thumb (Default: 150x150)', 'width' => 150, 'height' => 150],
            'featured' => ['label' => 'Featured (Default: 565x375)', 'width' => 565, 'height' => 375],
            'medium' => ['label' => 'Medium (Default: 540x360)', 'width' => 540, 'height' => 360],
            'small' => ['label' => 'Small (Default: 375x250)', 'width' => 375, 'height' => 250],
            'size_270x180' => ['label' => '270X180 (Default: 270x180)', 'width' => 270, 'height' => 180],
        ];
    }

    private function serverMaxUploadFilesize(): string
    {
        return ini_get('upload_max_filesize') ?: '2M';
    }

    /**
     * @return array<string, string>
     */
    private function phoneCountryOptions(): array
    {
        return [
            'AF' => 'Afghanistan',
            'AL' => 'Albania',
            'DZ' => 'Algeria',
            'AS' => 'American Samoa',
            'AD' => 'Andorra',
            'AO' => 'Angola',
            'AI' => 'Anguilla',
            'AG' => 'Antigua and Barbuda',
            'AR' => 'Argentina',
            'AM' => 'Armenia',
            'AW' => 'Aruba',
            'AU' => 'Australia',
            'AT' => 'Austria',
            'AZ' => 'Azerbaijan',
            'BS' => 'Bahamas',
            'BH' => 'Bahrain',
            'BD' => 'Bangladesh',
            'BB' => 'Barbados',
            'BY' => 'Belarus',
            'BE' => 'Belgium',
            'BZ' => 'Belize',
            'BJ' => 'Benin',
            'BM' => 'Bermuda',
            'BT' => 'Bhutan',
            'BO' => 'Bolivia',
            'BA' => 'Bosnia and Herzegovina',
            'BW' => 'Botswana',
            'BR' => 'Brazil',
            'BN' => 'Brunei Darussalam',
            'BG' => 'Bulgaria',
            'BF' => 'Burkina Faso',
            'BI' => 'Burundi',
            'KH' => 'Cambodia',
            'CM' => 'Cameroon',
            'CA' => 'Canada',
            'CV' => 'Cape Verde',
            'KY' => 'Cayman Islands',
            'CF' => 'Central African Republic',
            'TD' => 'Chad',
            'CL' => 'Chile',
            'CN' => 'China',
            'CO' => 'Colombia',
            'KM' => 'Comoros',
            'CG' => 'Congo',
            'CD' => 'Congo (Democratic Republic)',
            'CK' => 'Cook Islands',
            'CR' => 'Costa Rica',
            'CI' => 'Cote d’Ivoire',
            'HR' => 'Croatia',
            'CU' => 'Cuba',
            'CY' => 'Cyprus',
            'CZ' => 'Czech Republic',
            'DK' => 'Denmark',
            'DJ' => 'Djibouti',
            'DM' => 'Dominica',
            'DO' => 'Dominican Republic',
            'EC' => 'Ecuador',
            'EG' => 'Egypt',
            'SV' => 'El Salvador',
            'GQ' => 'Equatorial Guinea',
            'ER' => 'Eritrea',
            'EE' => 'Estonia',
            'ET' => 'Ethiopia',
            'FK' => 'Falkland Islands',
            'FO' => 'Faroe Islands',
            'FJ' => 'Fiji',
            'FI' => 'Finland',
            'FR' => 'France',
            'GF' => 'French Guiana',
            'PF' => 'French Polynesia',
            'GA' => 'Gabon',
            'GM' => 'Gambia',
            'GE' => 'Georgia',
            'DE' => 'Germany',
            'GH' => 'Ghana',
            'GI' => 'Gibraltar',
            'GR' => 'Greece',
            'GL' => 'Greenland',
            'GD' => 'Grenada',
            'GP' => 'Guadeloupe',
            'GU' => 'Guam',
            'GT' => 'Guatemala',
            'GN' => 'Guinea',
            'GW' => 'Guinea-Bissau',
            'GY' => 'Guyana',
            'HT' => 'Haiti',
            'HN' => 'Honduras',
            'HK' => 'Hong Kong',
            'HU' => 'Hungary',
            'IS' => 'Iceland',
            'IN' => 'India',
            'ID' => 'Indonesia',
            'IR' => 'Iran',
            'IQ' => 'Iraq',
            'IE' => 'Ireland',
            'IL' => 'Israel',
            'IT' => 'Italy',
            'JM' => 'Jamaica',
            'JP' => 'Japan',
            'JO' => 'Jordan',
            'KZ' => 'Kazakhstan',
            'KE' => 'Kenya',
            'KI' => 'Kiribati',
            'KW' => 'Kuwait',
            'KG' => 'Kyrgyzstan',
            'LA' => 'Laos',
            'LV' => 'Latvia',
            'LB' => 'Lebanon',
            'LS' => 'Lesotho',
            'LR' => 'Liberia',
            'LY' => 'Libya',
            'LI' => 'Liechtenstein',
            'LT' => 'Lithuania',
            'LU' => 'Luxembourg',
            'MO' => 'Macao',
            'MG' => 'Madagascar',
            'MW' => 'Malawi',
            'MY' => 'Malaysia',
            'MV' => 'Maldives',
            'ML' => 'Mali',
            'MT' => 'Malta',
            'MH' => 'Marshall Islands',
            'MQ' => 'Martinique',
            'MR' => 'Mauritania',
            'MU' => 'Mauritius',
            'MX' => 'Mexico',
            'FM' => 'Micronesia',
            'MD' => 'Moldova',
            'MC' => 'Monaco',
            'MN' => 'Mongolia',
            'ME' => 'Montenegro',
            'MS' => 'Montserrat',
            'MA' => 'Morocco',
            'MZ' => 'Mozambique',
            'MM' => 'Myanmar',
            'NA' => 'Namibia',
            'NR' => 'Nauru',
            'NP' => 'Nepal',
            'NL' => 'Netherlands',
            'NC' => 'New Caledonia',
            'NZ' => 'New Zealand',
            'NI' => 'Nicaragua',
            'NE' => 'Niger',
            'NG' => 'Nigeria',
            'NU' => 'Niue',
            'KP' => 'North Korea',
            'MK' => 'North Macedonia',
            'MP' => 'Northern Mariana Islands',
            'NO' => 'Norway',
            'OM' => 'Oman',
            'PK' => 'Pakistan',
            'PW' => 'Palau',
            'PS' => 'Palestine',
            'PA' => 'Panama',
            'PG' => 'Papua New Guinea',
            'PY' => 'Paraguay',
            'PE' => 'Peru',
            'PH' => 'Philippines',
            'PL' => 'Poland',
            'PT' => 'Portugal',
            'PR' => 'Puerto Rico',
            'QA' => 'Qatar',
            'RO' => 'Romania',
            'RU' => 'Russia',
            'RW' => 'Rwanda',
            'WS' => 'Samoa',
            'SM' => 'San Marino',
            'ST' => 'Sao Tome and Principe',
            'SA' => 'Saudi Arabia',
            'SN' => 'Senegal',
            'RS' => 'Serbia',
            'SC' => 'Seychelles',
            'SL' => 'Sierra Leone',
            'SG' => 'Singapore',
            'SK' => 'Slovakia',
            'SI' => 'Slovenia',
            'SB' => 'Solomon Islands',
            'SO' => 'Somalia',
            'ZA' => 'South Africa',
            'KR' => 'South Korea',
            'SS' => 'South Sudan',
            'ES' => 'Spain',
            'LK' => 'Sri Lanka',
            'SD' => 'Sudan',
            'SR' => 'Suriname',
            'SZ' => 'Swaziland',
            'SE' => 'Sweden',
            'CH' => 'Switzerland',
            'SY' => 'Syria',
            'TW' => 'Taiwan',
            'TJ' => 'Tajikistan',
            'TZ' => 'Tanzania',
            'TH' => 'Thailand',
            'TL' => 'Timor-Leste',
            'TG' => 'Togo',
            'TK' => 'Tokelau',
            'TO' => 'Tonga',
            'TT' => 'Trinidad and Tobago',
            'TN' => 'Tunisia',
            'TR' => 'Turkey',
            'TM' => 'Turkmenistan',
            'TC' => 'Turks and Caicos Islands',
            'TV' => 'Tuvalu',
            'UG' => 'Uganda',
            'UA' => 'Ukraine',
            'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'UY' => 'Uruguay',
            'UZ' => 'Uzbekistan',
            'VU' => 'Vanuatu',
            'VA' => 'Vatican',
            'VE' => 'Venezuela',
            'VN' => 'Vietnam',
            'VG' => 'Virgin Islands (British)',
            'VI' => 'Virgin Islands (US)',
            'WF' => 'Wallis and Futuna',
            'EH' => 'Western Sahara',
            'YE' => 'Yemen',
            'ZM' => 'Zambia',
            'ZW' => 'Zimbabwe',
        ];
    }

    /**
     * @return array<int, array{label: string, url: string, icon_image: string, icon_url: string}>
     */
    private function emailTemplateSocialLinks(): array
    {
        $stored = $this->settings->get('email_template_social_links');
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        if (! is_array($decoded)) {
            return [];
        }

        return collect($decoded)
            ->filter(fn (mixed $link): bool => is_array($link))
            ->map(fn (array $link): array => [
                'label' => (string) ($link['label'] ?? ''),
                'url' => (string) ($link['url'] ?? ''),
                'icon_image' => (string) ($link['icon_image'] ?? ''),
                'icon_url' => (string) ($link['icon_url'] ?? ''),
            ])
            ->filter(fn (array $link): bool => $link['label'] !== '' || $link['url'] !== '' || $link['icon_image'] !== '' || $link['icon_url'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<int, array{label: string, url: string, icon_image: string, icon_url: string}>
     */
    private function socialLinksFrom(array $validated): array
    {
        $labels = $validated['email_template_social_link_labels'] ?? [];
        $urls = $validated['email_template_social_link_urls'] ?? [];
        $iconImages = $validated['email_template_social_link_icon_images'] ?? [];
        $iconUrls = $validated['email_template_social_link_icon_urls'] ?? [];

        return collect($labels)
            ->map(fn (mixed $label, int $index): array => [
                'label' => trim((string) $label),
                'url' => trim((string) ($urls[$index] ?? '')),
                'icon_image' => trim((string) ($iconImages[$index] ?? '')),
                'icon_url' => trim((string) ($iconUrls[$index] ?? '')),
            ])
            ->filter(fn (array $link): bool => $link['label'] !== '' || $link['url'] !== '' || $link['icon_image'] !== '' || $link['icon_url'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function mailerOptions(): array
    {
        return collect(['', 'smtp', 'sendmail', 'mailgun', 'sendgrid', 'ses'])
            ->merge(collect(config('mail.mailers', []))->keys())
            ->reject(fn (string $mailer): bool => in_array($mailer, ['failover', 'roundrobin'], true))
            ->unique()
            ->mapWithKeys(fn (string $mailer): array => [
                $mailer => match ($mailer) {
                    '' => '— Select —',
                    'smtp' => 'SMTP',
                    'sendmail' => 'Sendmail',
                    'mailgun' => 'Mailgun',
                    'sendgrid' => 'SendGrid',
                    'ses' => 'SES',
                    'log' => 'Log (for testing only, will not send email)',
                    'array' => 'Array (for testing only)',
                    default => ucfirst($mailer),
                },
            ])
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private function logChannelOptions(): array
    {
        return [
            '' => '— Select —',
            'stack' => 'stack',
            'single' => 'single',
            'daily' => 'daily',
            'monthly' => 'monthly',
            'slack' => 'slack',
            'papertrail' => 'papertrail',
            'stderr' => 'stderr',
            'syslog' => 'syslog',
            'errorlog' => 'errorlog',
            'null' => 'null',
            'emergency' => 'emergency',
        ];
    }

    /**
     * @return array<string, array<int, array{key: string, template: string, description: string, enabled: bool, muted?: bool}>>
     */
    private function emailTemplateGroups(): array
    {
        $templates = [
            'Base template' => [
                ['key' => 'base.header', 'template' => 'Email template header', 'description' => 'Template for header of emails'],
                ['key' => 'base.footer', 'template' => 'Email template footer', 'description' => 'Template for footer of emails'],
                ['key' => 'base.test', 'template' => 'Test email', 'description' => 'Template for test emails sent from email settings'],
            ],
            'ACL' => [
                ['key' => 'acl.reset_password', 'template' => 'Reset password', 'description' => 'Send email to user when requesting reset password'],
            ],
            'Contact' => [
                ['key' => 'contact.notice_admin', 'template' => 'Send notice to administrator', 'description' => 'Email template for notifying administrator upon receiving a new contact submission'],
                ['key' => 'contact.confirm_sender', 'template' => 'Send confirmation to sender', 'description' => 'Email template for confirming the sender that the message has been sent successfully', 'muted' => true],
                ['key' => 'contact.admin_reply', 'template' => 'Admin reply to contact', 'description' => 'Email template for admin replies to contact messages'],
            ],
            'Comment' => [
                ['key' => 'comment.admin_new', 'template' => 'Admin notification for new comment', 'description' => 'Send email to admin when a new comment is posted'],
                ['key' => 'comment.reply', 'template' => 'Notify commenter of reply', 'description' => 'Send email to commenter when someone replies to their comment'],
            ],
            'Member' => [
                ['key' => 'member.confirm_email', 'template' => 'Confirm email', 'description' => 'Send email to user when they register an account to verify their email'],
                ['key' => 'member.reset_password', 'template' => 'Reset password', 'description' => 'Send email to user when requesting reset password'],
                ['key' => 'member.new_pending_post', 'template' => 'New pending post', 'description' => 'Send email to admin when a new post created'],
            ],
        ];

        $enabled = $this->emailTemplateStatuses();

        return collect($templates)
            ->map(fn (array $items): array => collect($items)
                ->map(fn (array $item): array => $item + ['enabled' => in_array($item['key'], $enabled, true)])
                ->all())
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function emailTemplateStatuses(): array
    {
        $stored = $this->settings->get('email_template_statuses');
        $decoded = is_string($stored) ? json_decode($stored, true) : null;

        if (is_array($decoded)) {
            return collect($decoded)
                ->filter(fn (mixed $key): bool => is_string($key) && $key !== '')
                ->values()
                ->all();
        }

        return [
            'base.header',
            'base.footer',
            'base.test',
            'acl.reset_password',
            'contact.notice_admin',
            'contact.admin_reply',
            'comment.admin_new',
            'comment.reply',
            'member.confirm_email',
            'member.reset_password',
            'member.new_pending_post',
        ];
    }
}
