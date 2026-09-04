<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Language;
use Sitewyn\Core\Base\Support\AdminFontCatalog;
use Sitewyn\Core\Base\Support\LanguageCatalog;
use Sitewyn\Core\Base\Support\ThemeManager;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:255'],
            'site_logo' => ['nullable', 'string', 'max:2048'],
            'robots_txt' => ['nullable', 'string', 'max:2000'],
            // Nullable so payloads without the field (older clients) keep the
            // current theme; a present value must be a discoverable theme.
            'active_theme' => ['nullable', 'string', Rule::in(app(ThemeManager::class)->availableSlugs())],
            'admin_emails' => ['nullable', 'array', 'max:4'],
            'admin_emails.*' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'front_site_language_direction' => ['nullable', 'string', Rule::in(['ltr', 'rtl'])],
            'site_language' => ['nullable', 'string', Rule::in($this->languageCodes())],
            'send_error_reporting_via_email' => ['nullable', 'boolean'],
            'redirect_404_to_homepage' => ['nullable', 'boolean'],
            'clear_old_request_logs' => ['nullable', 'string', Rule::in(['1_month', '3_months', '6_months', '1_year', 'never'])],
            'clear_old_audit_logs' => ['nullable', 'string', Rule::in(['1_month', '3_months', '6_months', '1_year', 'never'])],
            'email_mailer' => ['nullable', 'string', Rule::in($this->emailMailers())],
            'email_log_channel' => ['nullable', 'string', Rule::in($this->emailLogChannels())],
            'email_smtp_port' => ['nullable', 'integer', 'between:1,65535'],
            'email_smtp_host' => ['nullable', 'string', 'max:255'],
            'email_smtp_username' => ['nullable', 'string', 'max:255'],
            'email_smtp_password' => ['nullable', 'string', 'max:255'],
            'email_smtp_local_domain' => ['nullable', 'string', 'max:255'],
            'email_smtp_encryption' => ['nullable', 'string', Rule::in(['none', 'tls', 'ssl'])],
            'email_sendmail_path' => ['nullable', 'string', 'max:255'],
            'email_mailgun_domain' => ['nullable', 'string', 'max:255'],
            'email_mailgun_endpoint' => ['nullable', 'string', 'max:255'],
            'email_sendgrid_key' => ['nullable', 'string', 'max:255'],
            'email_ses_key' => ['nullable', 'string', 'max:255'],
            'email_ses_region' => ['nullable', 'string', 'max:255'],
            'email_sender_name' => ['nullable', 'string', 'max:255'],
            'email_sender_email' => ['nullable', 'email', 'max:255'],
            'default_email_language' => ['nullable', 'string', Rule::in(array_merge(['auto'], $this->languageCodes()))],
            'email_template_logo' => ['nullable', 'string', 'max:255'],
            'email_template_logo_url' => ['nullable', 'string', 'max:2048'],
            'email_template_contact_email_address' => ['nullable', 'email', 'max:255'],
            'email_template_copyright' => ['nullable', 'string', 'max:500'],
            'email_template_logo_height' => ['nullable', 'integer', 'between:1,500'],
            'email_template_custom_css' => ['nullable', 'string', 'max:10000'],
            'email_template_social_link_labels' => ['nullable', 'array', 'max:20'],
            'email_template_social_link_labels.*' => ['nullable', 'string', 'max:120'],
            'email_template_social_link_urls' => ['nullable', 'array', 'max:20'],
            'email_template_social_link_urls.*' => ['nullable', 'string', 'max:2048'],
            'email_template_social_link_icon_images' => ['nullable', 'array', 'max:20'],
            'email_template_social_link_icon_images.*' => ['nullable', 'string', 'max:255'],
            'email_template_social_link_icon_urls' => ['nullable', 'array', 'max:20'],
            'email_template_social_link_icon_urls.*' => ['nullable', 'string', 'max:2048'],
            'email_template_statuses' => ['nullable', 'array'],
            'email_template_statuses.*' => ['string', 'max:120'],
            'email_rules_blacklisted_domains' => ['nullable', 'string', 'max:5000'],
            'email_rules_blacklisted_addresses' => ['nullable', 'string', 'max:5000'],
            'email_rules_exception_emails' => ['nullable', 'string', 'max:5000'],
            'email_rules_strict_validation' => ['nullable', 'boolean'],
            'email_rules_dns_check_validation' => ['nullable', 'boolean'],
            'email_rules_spoofing_detection' => ['nullable', 'boolean'],
            'phone_number_enable_country_code' => ['nullable', 'boolean'],
            'phone_number_available_countries_all' => ['nullable', 'boolean'],
            'phone_number_available_countries' => ['nullable', 'array', 'max:250'],
            'phone_number_available_countries.*' => ['string', 'size:2'],
            'phone_number_minimum_length' => ['nullable', 'integer', 'between:1,30', 'lte:phone_number_maximum_length'],
            'phone_number_maximum_length' => ['nullable', 'integer', 'between:1,30', 'gte:phone_number_minimum_length'],
            'media_driver' => ['nullable', 'string', Rule::in($this->mediaDrivers())],
            'media_s3_access_key_id' => ['nullable', 'string', 'max:255'],
            'media_s3_secret_key' => ['nullable', 'string', 'max:255'],
            'media_s3_default_region' => ['nullable', 'string', 'max:255'],
            'media_s3_bucket' => ['nullable', 'string', 'max:255'],
            'media_s3_url' => ['nullable', 'string', 'max:2048'],
            'media_s3_endpoint' => ['nullable', 'string', 'max:2048'],
            'media_s3_custom_path' => ['nullable', 'string', 'max:255'],
            'media_s3_use_path_style_endpoint' => ['nullable', 'boolean'],
            'media_r2_access_key_id' => ['nullable', 'string', 'max:255'],
            'media_r2_secret_key' => ['nullable', 'string', 'max:255'],
            'media_r2_bucket' => ['nullable', 'string', 'max:255'],
            'media_r2_url' => ['nullable', 'string', 'max:2048'],
            'media_r2_endpoint' => ['nullable', 'string', 'max:2048'],
            'media_r2_use_path_style_endpoint' => ['nullable', 'boolean'],
            'media_do_spaces_access_key_id' => ['nullable', 'string', 'max:255'],
            'media_do_spaces_secret_key' => ['nullable', 'string', 'max:255'],
            'media_do_spaces_default_region' => ['nullable', 'string', 'max:255'],
            'media_do_spaces_bucket' => ['nullable', 'string', 'max:255'],
            'media_do_spaces_endpoint' => ['nullable', 'string', 'max:2048'],
            'media_do_spaces_cdn_enabled' => ['nullable', 'boolean'],
            'media_do_spaces_cdn_custom_domain' => ['nullable', 'string', 'max:2048'],
            'media_do_spaces_use_path_style_endpoint' => ['nullable', 'boolean'],
            'media_wasabi_access_key_id' => ['nullable', 'string', 'max:255'],
            'media_wasabi_secret_key' => ['nullable', 'string', 'max:255'],
            'media_wasabi_default_region' => ['nullable', 'string', 'max:255'],
            'media_wasabi_bucket' => ['nullable', 'string', 'max:255'],
            'media_wasabi_root' => ['nullable', 'string', 'max:255'],
            'media_wasabi_cdn_enabled' => ['nullable', 'boolean'],
            'media_bunnycdn_zone_name' => ['nullable', 'string', 'max:255'],
            'media_bunnycdn_hostname' => ['nullable', 'string', 'max:255'],
            'media_bunnycdn_access_password' => ['nullable', 'string', 'max:255'],
            'media_bunnycdn_region' => ['nullable', 'string', Rule::in($this->bunnyCdnRegions())],
            'media_backblaze_access_key_id' => ['nullable', 'string', 'max:255'],
            'media_backblaze_secret_key' => ['nullable', 'string', 'max:255'],
            'media_backblaze_default_region' => ['nullable', 'string', 'max:255'],
            'media_backblaze_bucket' => ['nullable', 'string', 'max:255'],
            'media_backblaze_endpoint' => ['nullable', 'string', 'max:2048'],
            'media_backblaze_use_path_style_endpoint' => ['nullable', 'boolean'],
            'media_backblaze_cdn_enabled' => ['nullable', 'boolean'],
            'media_use_original_name_for_file_path' => ['nullable', 'boolean'],
            'media_convert_file_name_to_uuid' => ['nullable', 'boolean'],
            'media_keep_original_file_size_quality' => ['nullable', 'boolean'],
            'media_image_quality' => ['nullable', 'integer', 'between:70,100'],
            'media_turn_off_automatic_url_translation_into_latin' => ['nullable', 'boolean'],
            'media_users_can_only_view_own_media' => ['nullable', 'boolean'],
            'media_convert_image_to_webp' => ['nullable', 'boolean'],
            'media_default_placeholder_image' => ['nullable', 'string', 'max:255'],
            'media_default_placeholder_image_url' => ['nullable', 'string', 'max:2048'],
            'media_max_upload_filesize' => ['nullable', 'numeric', 'min:1', 'max:2048'],
            'media_reduce_large_image_size' => ['nullable', 'boolean'],
            'media_customize_upload_path' => ['nullable', 'boolean'],
            'media_enable_chunk_upload' => ['nullable', 'boolean'],
            'media_enable_watermark' => ['nullable', 'boolean'],
            'media_image_processing_library' => ['nullable', 'string', Rule::in(['gd', 'imagick'])],
            'media_enable_thumbnail_sizes' => ['nullable', 'boolean'],
            'media_thumbnail_thumb_width' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_thumb_height' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_featured_width' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_featured_height' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_medium_width' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_medium_height' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_small_width' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_small_height' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_size_270x180_width' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_size_270x180_height' => ['nullable', 'integer', 'between:0,5000'],
            'media_thumbnail_crop_position' => ['nullable', 'string', Rule::in($this->mediaThumbnailCropPositions())],
            'permalink_pages_prefix' => ['nullable', 'string', 'max:120'],
            'permalink_blog_posts_prefix' => ['nullable', 'string', 'max:120'],
            'permalink_blog_categories_prefix' => ['nullable', 'string', 'max:120'],
            'permalink_blog_tags_prefix' => ['nullable', 'string', 'max:120'],
            'permalink_galleries_prefix' => ['nullable', 'string', 'max:120'],
            'permalink_member_prefix' => ['nullable', 'string', 'max:120'],
            'permalink_single_page_postfix' => ['nullable', 'string', 'max:20'],
            'permalink_turn_off_automatic_url_translation_into_latin' => ['nullable', 'boolean'],
            'admin_logo' => ['nullable', 'string', 'max:255'],
            'admin_logo_url' => ['nullable', 'string', 'max:2048'],
            'admin_logo_height' => ['nullable', 'integer', 'between:1,500'],
            'admin_favicon' => ['nullable', 'string', 'max:255'],
            'admin_favicon_url' => ['nullable', 'string', 'max:2048'],
            'admin_favicon_type' => ['nullable', 'string', Rule::in(['ico', 'png', 'svg', 'gif', 'jpeg', 'webp'])],
            'admin_login_screen_backgrounds' => ['nullable', 'array', 'max:20'],
            'admin_login_screen_backgrounds.*' => ['nullable', 'string', 'max:255'],
            'admin_login_screen_background_urls' => ['nullable', 'array', 'max:20'],
            'admin_login_screen_background_urls.*' => ['nullable', 'string', 'max:2048'],
            'admin_title' => ['nullable', 'string', 'max:255'],
            'admin_primary_font' => ['nullable', 'string', Rule::in(app(AdminFontCatalog::class)->keys())],
            'admin_primary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_secondary_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_heading_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_text_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_link_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_link_hover_color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'admin_language' => ['nullable', 'string', Rule::in(array_merge(['default'], $this->adminLanguageCodes()))],
            'admin_language_direction' => ['nullable', 'string', Rule::in(['ltr', 'rtl'])],
            'admin_rich_editor' => ['nullable', 'string', Rule::in(['ckeditor', 'tinymce'])],
            'admin_enable_page_visual_builder' => ['nullable', 'boolean'],
            'admin_layout' => ['nullable', 'string', Rule::in(['vertical', 'horizontal'])],
            'admin_container_width' => ['nullable', 'string', Rule::in(['default', 'large', 'full'])],
            'admin_show_menu_item_icon' => ['nullable', 'boolean'],
            'admin_show_admin_bar' => ['nullable', 'boolean'],
            'admin_show_guidelines' => ['nullable', 'boolean'],
            'admin_show_get_started_wizard' => ['nullable', 'boolean'],
            'admin_custom_css' => ['nullable', 'string', 'max:20000'],
            'admin_header_js' => ['nullable', 'string', 'max:20000'],
            'admin_body_js' => ['nullable', 'string', 'max:20000'],
            'admin_footer_js' => ['nullable', 'string', 'max:20000'],
            'api_enabled' => ['nullable', 'boolean'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'api_push_notifications_enabled' => ['nullable', 'boolean'],
            'api_fcm_project_id' => ['nullable', 'string', 'max:255'],
            'api_fcm_service_account_json' => ['nullable', 'string', 'max:20000'],
            'cache_admin_menu' => ['nullable', 'boolean'],
            'cache_front_menu' => ['nullable', 'boolean'],
            'cache_user_avatar' => ['nullable', 'boolean'],
            'cache_shortcodes' => ['nullable', 'boolean'],
            'cache_shortcodes_duration' => ['nullable', 'integer', 'between:0,86400'],
            'cache_widgets' => ['nullable', 'boolean'],
            'cache_widgets_duration' => ['nullable', 'integer', 'between:0,86400'],
            'cache_installed_plugins' => ['nullable', 'boolean'],
            'cache_size_warning_threshold' => ['nullable', 'integer', 'between:1,10240'],
            'cache_auto_clear_when_size_exceeds_threshold' => ['nullable', 'boolean'],
            'cache_sitemap' => ['nullable', 'boolean'],
            'cache_sitemap_timeout' => ['nullable', 'integer', 'between:1,10080'],
            'cache_public_headers' => ['nullable', 'boolean'],
            'cache_public_duration' => ['nullable', 'integer', 'between:0,86400'],
            'datatables_pagination_type' => ['nullable', 'string', Rule::in(['default', 'dropdown'])],
            'datatables_show_column_visibility' => ['nullable', 'boolean'],
            'datatables_show_export_button' => ['nullable', 'boolean'],
            'datatables_enable_table_responsive' => ['nullable', 'boolean'],
            'website_tracking_type' => ['nullable', 'string', Rule::in(['gtm', 'ga', 'custom'])],
            'website_tracking_gtm_container_id' => ['nullable', 'string', 'max:30', 'regex:/^GTM-[A-Z0-9]+$/i'],
            'website_tracking_gtm_debug_mode' => ['nullable', 'boolean'],
            'website_tracking_gtm_include_customer_data' => ['nullable', 'boolean'],
            'website_tracking_ga_measurement_id' => ['nullable', 'string', 'max:30', 'regex:/^G-[A-Z0-9]+$/i'],
            'website_tracking_custom_header_script' => ['nullable', 'string', 'max:50000'],
            'website_tracking_custom_body_code' => ['nullable', 'string', 'max:50000'],
            'optimize_page_speed_enabled' => ['nullable', 'boolean'],
            'optimize_collapse_whitespace' => ['nullable', 'boolean'],
            'optimize_elide_attributes' => ['nullable', 'boolean'],
            'optimize_inline_css' => ['nullable', 'boolean'],
            'optimize_insert_dns_prefetch' => ['nullable', 'boolean'],
            'optimize_remove_comments' => ['nullable', 'boolean'],
            'optimize_remove_quotes' => ['nullable', 'boolean'],
            'optimize_defer_javascript' => ['nullable', 'boolean'],
            'blog_schema_enabled' => ['nullable', 'boolean'],
            'blog_schema_type' => ['nullable', 'string', Rule::in(['Article', 'BlogPosting', 'NewsArticle'])],
            'blog_anchor_links_enabled' => ['nullable', 'boolean'],
            'member_allow_login' => ['nullable', 'boolean'],
            'member_allow_register' => ['nullable', 'boolean'],
            'member_verify_email' => ['nullable', 'boolean'],
            'member_verification_expiration' => ['nullable', 'integer', 'between:1,10080'],
            'member_post_approval' => ['nullable', 'boolean'],
            'member_default_avatar' => ['nullable', 'string', 'max:255'],
            'member_default_avatar_url' => ['nullable', 'string', 'max:2048'],
            'member_show_terms_policy_checkbox' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function languageCodes(): array
    {
        $codes = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->pluck('code')
            ->all();

        return $codes === [] ? ['en'] : $codes;
    }

    /**
     * @return array<int, string>
     */
    private function adminLanguageCodes(): array
    {
        return array_keys(app(LanguageCatalog::class)->languageOptions());
    }

    /**
     * @return array<int, string>
     */
    private function emailMailers(): array
    {
        return collect(config('mail.mailers', []))
            ->keys()
            ->reject(fn (string $mailer): bool => in_array($mailer, ['failover', 'roundrobin'], true))
            ->merge(['smtp', 'sendmail', 'mailgun', 'sendgrid', 'ses'])
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function emailLogChannels(): array
    {
        return [
            '',
            'stack',
            'single',
            'daily',
            'monthly',
            'slack',
            'papertrail',
            'stderr',
            'syslog',
            'errorlog',
            'null',
            'emergency',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function mediaDrivers(): array
    {
        return [
            'public',
            's3',
            'cloudflare_r2',
            'digitalocean_spaces',
            'wasabi',
            'bunnycdn',
            'backblaze_b2',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function bunnyCdnRegions(): array
    {
        return [
            'de',
            'ny',
            'la',
            'sg',
            'syd',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function mediaThumbnailCropPositions(): array
    {
        return [
            'top-left',
            'top',
            'top-right',
            'left',
            'center',
            'right',
            'bottom-left',
            'bottom',
            'bottom-right',
        ];
    }
}
