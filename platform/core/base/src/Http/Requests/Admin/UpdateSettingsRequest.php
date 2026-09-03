<?php

namespace Sitewyn\Core\Base\Http\Requests\Admin;

use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Sitewyn\Core\Base\Models\Language;
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
