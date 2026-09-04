<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Setting;
use Sitewyn\Core\Base\Support\AdminFontCatalog;
use Sitewyn\Core\Base\Support\SettingStore;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_settings_page_requires_permission(): void
    {
        $user = User::factory()->create([
            'is_super_admin' => false,
            'is_active' => true,
        ]);

        $this->actingAs($user, 'admin')
            ->get('/admin/settings')
            ->assertForbidden();
    }

    public function test_super_admin_can_view_settings_hub(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Common')
            ->assertSee('General')
            ->assertSee('View and update your general settings and activate license')
            ->assertSee('href="/admin/settings/general"', false)
            ->assertSee('href="/admin/settings/cache"', false)
            ->assertSee('href="/admin/settings/datatables"', false)
            ->assertSee('href="/admin/settings/website-tracking"', false)
            ->assertSee('href="/admin/settings/optimize"', false)
            ->assertSee('href="/admin/settings/blog"', false)
            ->assertSee('href="/admin/settings/members"', false)
            ->assertSee('href="/admin/translations/locales"', false)
            ->assertSee('Email templates')
            ->assertSee('Email rules')
            ->assertSee('Phone Number')
            ->assertSee('Website Tracking')
            ->assertSee('Localization')
            ->assertSee('Theme Translations')
            ->assertSee('Others')
            ->assertSee('Google Analytics');
    }

    public function test_blog_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.blog'));
        $this->assertSame('/admin/settings/blog', route('admin.settings.blog', [], false));
        $this->assertSame('/admin/settings/blog', route('admin.settings.blog.update', [], false));
    }

    public function test_member_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.members'));
        $this->assertSame('/admin/settings/members', route('admin.settings.members', [], false));
        $this->assertSame('/admin/settings/members', route('admin.settings.members.update', [], false));
    }

    public function test_general_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.general'));
        $this->assertSame('/admin/settings/general', route('admin.settings.general', [], false));
    }

    public function test_email_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.email'));
        $this->assertSame('/admin/settings/email', route('admin.settings.email', [], false));
    }

    public function test_email_template_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.email.templates'));
        $this->assertSame('/admin/settings/email/templates', route('admin.settings.email.templates', [], false));
    }

    public function test_email_rules_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.email.rules'));
        $this->assertSame('/admin/settings/email/rules', route('admin.settings.email.rules', [], false));
    }

    public function test_phone_number_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.phone-number'));
        $this->assertSame('/admin/settings/phone-number', route('admin.settings.phone-number', [], false));
    }

    public function test_media_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.media'));
        $this->assertSame('/admin/settings/media', route('admin.settings.media', [], false));
        $this->assertTrue(Route::has('admin.settings.media.generate-thumbnails'));
        $this->assertSame('/admin/settings/media/generate-thumbnails', route('admin.settings.media.generate-thumbnails', [], false));
    }

    public function test_permalink_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.permalink'));
        $this->assertSame('/admin/settings/permalink', route('admin.settings.permalink', [], false));
    }

    public function test_admin_appearance_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.admin-appearance'));
        $this->assertSame('/admin/settings/admin-appearance', route('admin.settings.admin-appearance', [], false));
        $this->assertTrue(Route::has('admin.settings.admin-appearance.google-fonts'));
        $this->assertSame('/admin/settings/admin-appearance/google-fonts', route('admin.settings.admin-appearance.google-fonts', [], false));
    }

    public function test_api_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.api'));
        $this->assertSame('/admin/settings/api', route('admin.settings.api', [], false));
    }

    public function test_cache_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.cache'));
        $this->assertSame('/admin/settings/cache', route('admin.settings.cache', [], false));
    }

    public function test_datatables_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.datatables'));
        $this->assertSame('/admin/settings/datatables', route('admin.settings.datatables', [], false));
    }

    public function test_website_tracking_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.website-tracking'));
        $this->assertSame('/admin/settings/website-tracking', route('admin.settings.website-tracking', [], false));
    }

    public function test_optimize_settings_route_is_registered(): void
    {
        $this->assertTrue(Route::has('admin.settings.optimize'));
        $this->assertSame('/admin/settings/optimize', route('admin.settings.optimize', [], false));
    }

    public function test_admin_font_catalog_uses_google_fonts_metadata(): void
    {
        Cache::forget('sitewyn.admin.google_fonts');
        Cache::forget('sitewyn.admin.google_fonts.v2');
        Http::fake([
            'fonts.google.com/metadata/fonts' => Http::response(")]}'\n".json_encode([
                'familyMetadataList' => [
                    ['family' => 'Roboto', 'category' => 'sans-serif', 'subsets' => ['latin'], 'popularity' => 2, 'lastModified' => '2026-01-01'],
                    ['family' => 'Open Sans', 'category' => 'sans-serif', 'subsets' => ['latin'], 'popularity' => 3, 'lastModified' => '2026-01-01'],
                    ['family' => 'Noto Sans', 'category' => 'sans-serif', 'subsets' => ['latin'], 'popularity' => 17, 'lastModified' => '2026-01-01'],
                    ['family' => 'Source Sans 3', 'category' => 'sans-serif', 'subsets' => ['latin'], 'popularity' => 41, 'lastModified' => '2026-01-01'],
                ],
            ]), 200),
        ]);

        $catalog = app(AdminFontCatalog::class);
        $options = $catalog->options();

        $this->assertArrayHasKey('roboto', $options);
        $this->assertArrayHasKey('open_sans', $options);
        $this->assertArrayHasKey('noto_sans', $options);
        $this->assertArrayHasKey('source_sans_3', $options);
        $this->assertStringContainsString('fonts.googleapis.com', $catalog->stylesheetUrl('roboto'));

        Http::assertSent(fn ($request): bool => $request->url() === 'https://fonts.google.com/metadata/fonts');
        Cache::forget('sitewyn.admin.google_fonts');
        Cache::forget('sitewyn.admin.google_fonts.v2');
    }

    public function test_super_admin_can_search_google_fonts_for_admin_appearance(): void
    {
        Cache::forget('sitewyn.admin.google_fonts');
        Cache::forget('sitewyn.admin.google_fonts.v2');
        Http::fake([
            'fonts.google.com/metadata/fonts' => Http::response(")]}'\n".json_encode([
                'familyMetadataList' => [
                    ['family' => 'Inter', 'category' => 'sans-serif', 'subsets' => ['latin'], 'popularity' => 1, 'lastModified' => '2026-01-01'],
                    ['family' => 'League Gothic', 'category' => 'display', 'subsets' => ['latin'], 'popularity' => 20, 'lastModified' => '2026-01-01'],
                    ['family' => 'League Spartan', 'category' => 'sans-serif', 'subsets' => ['latin'], 'popularity' => 21, 'lastModified' => '2026-01-01'],
                ],
            ]), 200),
        ]);

        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->getJson('/admin/settings/admin-appearance/google-fonts?q=league')
            ->assertOk()
            ->assertJsonPath('results.0.id', 'league_gothic')
            ->assertJsonPath('results.0.text', 'League Gothic')
            ->assertJsonPath('results.0.stylesheet_url', 'https://fonts.googleapis.com/css2?family=League+Gothic:wght@400;500;600;700&display=swap')
            ->assertJsonPath('pagination.more', false);

        Cache::forget('sitewyn.admin.google_fonts');
        Cache::forget('sitewyn.admin.google_fonts.v2');
    }

    public function test_super_admin_can_view_general_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/general')
            ->assertOk()
            ->assertSee('License')
            ->assertSee('Setup license code')
            ->assertSee('Deactivate license')
            ->assertSee('General Information')
            ->assertSee('Admin Email')
            ->assertSee('+ Add more')
            ->assertSee('You can add maximum 4 emails')
            ->assertSee('Timezone')
            ->assertSee('Front site language direction')
            ->assertSee('Left to Right')
            ->assertSee('Right to Left')
            ->assertSee('Site language')
            ->assertSee('Send error reporting via email')
            ->assertSee('Redirect all Not Found requests to homepage')
            ->assertSee('Clear old Request Logs')
            ->assertSee('Clear old Audit Logs')
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_email_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('Email')
            ->assertSee('View and update your email settings and email templates')
            ->assertSee('Mailer')
            ->assertSee('— Select —')
            ->assertSee('Sendmail')
            ->assertSee('SendGrid')
            ->assertDontSee('Failover')
            ->assertDontSee('Roundrobin')
            ->assertSee('data-email-mailer-fields="smtp" class="d-none"', false)
            ->assertSee('Sender name')
            ->assertSee('Sender email')
            ->assertSee('Default email language')
            ->assertSee('Save settings')
            ->assertSee('Send test email')
            ->assertSee('Email Setup Tips')
            ->assertSee('Email template status')
            ->assertSee('Base template')
            ->assertSee('ACL')
            ->assertSee('Contact')
            ->assertSee('Comment')
            ->assertSee('Member');
    }

    public function test_super_admin_can_view_smtp_fields_when_smtp_is_selected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_mailer' => 'smtp',
            'email_smtp_port' => '587',
            'email_smtp_host' => 'smtp.example.com',
            'email_smtp_username' => 'smtp-user',
            'email_smtp_password' => 'smtp-secret',
            'email_smtp_local_domain' => 'cms.example.com',
            'email_smtp_encryption' => 'tls',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('data-email-mailer-fields="smtp" class=""', false)
            ->assertSee('Port')
            ->assertSee('Ex: 587')
            ->assertSee('Host')
            ->assertSee('Ex: smtp.gmail.com')
            ->assertSee('Username')
            ->assertSee('Password')
            ->assertSee('Local domain')
            ->assertSee('Encryption')
            ->assertSee('cms.example.com');
    }

    public function test_super_admin_can_view_mailgun_fields_when_mailgun_is_selected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_mailer' => 'mailgun',
            'email_mailgun_domain' => 'mg.example.com',
            'email_mailgun_endpoint' => 'api.mailgun.net',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('data-email-mailer-fields="mailgun" class=""', false)
            ->assertSee('Domain')
            ->assertSee('Ex: mg.yourdomain.com')
            ->assertSee('The domain name you registered with Mailgun')
            ->assertSee('Endpoint')
            ->assertSee('api.mailgun.net')
            ->assertSee('Mailgun API endpoint (api.mailgun.net for US, api.eu.mailgun.net for EU)');
    }

    public function test_super_admin_can_view_ses_fields_when_ses_is_selected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_mailer' => 'ses',
            'email_ses_key' => 'AKIAIOSFODNN7EXAMPLE',
            'email_ses_region' => 'us-east-1',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('data-email-mailer-fields="ses" class=""', false)
            ->assertSee('Key')
            ->assertSee('Ex: AKIAIOSFODNN7EXAMPLE')
            ->assertSee('Your AWS access key ID')
            ->assertSee('Region')
            ->assertSee('us-east-1')
            ->assertSee('The AWS region where your SES service is configured');
    }

    public function test_super_admin_can_view_sendgrid_fields_when_sendgrid_is_selected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_mailer' => 'sendgrid',
            'email_sendgrid_key' => 'SG.example',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('data-email-mailer-fields="sendgrid" class=""', false)
            ->assertSee('SendGrid')
            ->assertSee('Key')
            ->assertSee('Ex: SG.xxxxxx')
            ->assertSee('Your SendGrid API key');
    }

    public function test_super_admin_can_view_sendmail_fields_when_sendmail_is_selected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_mailer' => 'sendmail',
            'email_sendmail_path' => '/usr/sbin/sendmail -bs -i',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('data-email-mailer-fields="sendmail" class=""', false)
            ->assertSee('Sendmail')
            ->assertSee('Sendmail Path')
            ->assertSee('/usr/sbin/sendmail -bs -i')
            ->assertSee('Default:');
    }

    public function test_super_admin_can_view_extended_log_channel_options_when_log_is_selected(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_mailer' => 'log',
            'email_log_channel' => 'single',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email')
            ->assertOk()
            ->assertSee('data-email-mailer-fields="log"', false)
            ->assertSee('Log channel')
            ->assertSee('value="stack"', false)
            ->assertSee('value="single"', false)
            ->assertSee('value="daily"', false)
            ->assertSee('value="monthly"', false)
            ->assertSee('value="slack"', false)
            ->assertSee('value="papertrail"', false)
            ->assertSee('value="stderr"', false)
            ->assertSee('value="syslog"', false)
            ->assertSee('value="errorlog"', false)
            ->assertSee('value="null"', false)
            ->assertSee('value="emergency"', false);
    }

    public function test_settings_hub_links_to_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('View and update your email settings and email templates')
            ->assertSee('href="/admin/settings/email"', false);
    }

    public function test_settings_hub_links_to_email_template_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSeeText('Email templates using HTML & system variables.')
            ->assertSee('href="/admin/settings/email/templates"', false);
    }

    public function test_settings_hub_links_to_email_rules_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Configure email rules for validation')
            ->assertSee('href="/admin/settings/email/rules"', false);
    }

    public function test_settings_hub_links_to_phone_number_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Configure phone number field settings')
            ->assertSee('href="/admin/settings/phone-number"', false);
    }

    public function test_settings_hub_links_to_media_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('View and update your media settings')
            ->assertSee('href="/admin/settings/media"', false);
    }

    public function test_settings_hub_links_to_permalink_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('View and update your permalink settings')
            ->assertSee('href="/admin/settings/permalink"', false);
    }

    public function test_settings_hub_links_to_admin_appearance_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('View and update logo, favicon, layout,...')
            ->assertSee('href="/admin/settings/admin-appearance"', false);
    }

    public function test_settings_hub_links_to_api_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('API Settings')
            ->assertSee('View and update your API settings')
            ->assertSee('href="/admin/settings/api"', false);
    }

    public function test_super_admin_can_view_email_rules_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_rules_blacklisted_domains' => 'gmail.com, yahoo.com',
            'email_rules_blacklisted_addresses' => 'blocked@example.com',
            'email_rules_exception_emails' => 'owner@example.com',
            'email_rules_strict_validation' => '1',
            'email_rules_dns_check_validation' => '1',
            'email_rules_spoofing_detection' => '0',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email/rules')
            ->assertOk()
            ->assertSee('Email rules')
            ->assertSee('Configure email rules for validation')
            ->assertSee('Blacklisted Email Domains')
            ->assertSee('Enter a list of email domains to be blacklisted. E.g. gmail.com, yahoo.com.')
            ->assertSee('Blacklisted Email Addresses')
            ->assertSee('Enter a list of specific email addresses to be blacklisted. E.g. mail@example.com.')
            ->assertSee('Exception Emails')
            ->assertSee('These emails will be excluded from the validation rules.')
            ->assertSee('Strict Email Validation')
            ->assertSee('Perform RFC-like email validation with strict rules.')
            ->assertSee('DNS Check Validation')
            ->assertSee('Check if there are DNS records indicating the server accepts emails.')
            ->assertSee('Spoofing Detection')
            ->assertSee('Detect potential email spoofing attempts.')
            ->assertSee('Save settings')
            ->assertSee('gmail.com, yahoo.com')
            ->assertSee('blocked@example.com')
            ->assertSee('owner@example.com')
            ->assertSee('name="email_rules_strict_validation" value="1" class="form-check-input" checked', false)
            ->assertSee('name="email_rules_dns_check_validation" value="1" class="form-check-input" checked', false);
    }

    public function test_super_admin_can_view_phone_number_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'phone_number_enable_country_code' => '1',
            'phone_number_available_countries_all' => '0',
            'phone_number_available_countries' => '["VN","US","JP"]',
            'phone_number_minimum_length' => '7',
            'phone_number_maximum_length' => '20',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/phone-number')
            ->assertOk()
            ->assertSee('Phone Number')
            ->assertSee('Configure phone number field settings')
            ->assertSee('Enable Country Code Selection')
            ->assertSee('When enabled, phone number fields will display a country code selector with automatic country detection.')
            ->assertSee('All')
            ->assertSee('Select all countries to be available in the phone country code selector.')
            ->assertSee('Available Countries')
            ->assertSee('Vietnam')
            ->assertSee('Vanuatu')
            ->assertSee('Virgin Islands (British)')
            ->assertSee('Western Sahara')
            ->assertSee('Minimum Length')
            ->assertSee('Maximum Length')
            ->assertSee('For local format (without country code):')
            ->assertSee('For international format (with country code enabled):')
            ->assertSee('Save settings')
            ->assertSee('value="7"', false)
            ->assertSee('value="20"', false)
            ->assertSee('value="VN"', false)
            ->assertSee('value="US"', false)
            ->assertSee('data-phone-country-all', false);
    }

    public function test_phone_country_selector_is_hidden_when_country_code_selection_is_disabled(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'phone_number_enable_country_code' => '0',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/phone-number')
            ->assertOk()
            ->assertSee('data-phone-country-toggle', false)
            ->assertSee('d-none', false)
            ->assertSee('data-phone-country-visible="0"', false)
            ->assertSee('Minimum Length');
    }

    public function test_super_admin_can_view_media_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'media_driver' => 'public',
            'media_default_placeholder_image_url' => 'https://example.com/placeholder.png',
            'media_image_processing_library' => 'imagick',
            'media_enable_thumbnail_sizes' => '1',
            'media_thumbnail_sizes' => '{"thumb":{"width":160,"height":160},"featured":{"width":600,"height":400},"medium":{"width":540,"height":360},"small":{"width":375,"height":250},"size_270x180":{"width":270,"height":180}}',
            'media_thumbnail_crop_position' => 'bottom-right',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/media')
            ->assertOk()
            ->assertSee('Media')
            ->assertSee('Settings for media')
            ->assertSee('Driver')
            ->assertSee('Local disk')
            ->assertSee('Amazon S3')
            ->assertSee('Cloudflare R2')
            ->assertSee('DigitalOcean Spaces')
            ->assertSee('Wasabi')
            ->assertSee('BunnyCDN')
            ->assertSee('Backblaze B2')
            ->assertSee('AWS Access Key ID')
            ->assertSee('R2 Access Key ID')
            ->assertSee('DO Spaces Access Key ID')
            ->assertSee('Wasabi Access Key ID')
            ->assertSee('Zone Name (The name of your storage zone)')
            ->assertSee('Backblaze Access Key ID')
            ->assertSee('Use path style endpoint')
            ->assertSee('Use original name for file path')
            ->assertSee('Convert file name to UUID')
            ->assertSee('Keep original file size and quality')
            ->assertSee('Image quality')
            ->assertSee('Turn off automatic URL translation into Latin')
            ->assertSee('Users can only view their own media')
            ->assertSee('Convert JPG, JPEG, PNG image to WebP')
            ->assertSee('Default placeholder image')
            ->assertSee('Choose image')
            ->assertSee('Add from URL')
            ->assertSee('data-bs-target="#media-default-placeholder-image-media-picker-modal"', false)
            ->assertSee('id="media-placeholder-url-modal"', false)
            ->assertSee('Max upload filesize (MB)')
            ->assertSee('Reduce large image size when uploading')
            ->assertSee('Customize upload path')
            ->assertSee('Enable the chunk upload')
            ->assertSee('Enable watermark')
            ->assertSee('Image processing library')
            ->assertSee('GD Library')
            ->assertSee('Imagick')
            ->assertSee('Enable thumbnail sizes')
            ->assertSee('Media thumbnails sizes:')
            ->assertSee('Thumb (Default: 150x150)')
            ->assertSee('Featured (Default: 565x375)')
            ->assertSee('Medium (Default: 540x360)')
            ->assertSee('Small (Default: 375x250)')
            ->assertSee('270X180 (Default: 270x180)')
            ->assertSee('Thumbnail crop position')
            ->assertSee('Set width or height to 0 if you just want to crop by width or height.')
            ->assertSee('After adjusting the thumbnail sizes, you must click on the')
            ->assertSee('Save settings')
            ->assertSee('Generate thumbnails')
            ->assertSee('https://example.com/placeholder.png')
            ->assertSee('value="160"', false)
            ->assertSee('value="600"', false)
            ->assertSee('value="bottom-right"', false);
    }

    public function test_super_admin_can_view_permalink_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'permalink_blog_tags_prefix' => 'tag',
            'permalink_galleries_prefix' => 'galleries',
            'permalink_member_prefix' => 'author',
            'permalink_single_page_postfix' => '.html',
            'permalink_turn_off_automatic_url_translation_into_latin' => '1',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/permalink')
            ->assertOk()
            ->assertSee('Permalink settings')
            ->assertSee('Manage permalink for all modules.')
            ->assertSee('Prefix for Pages')
            ->assertSee('Prefix for Blog posts')
            ->assertSee('Prefix for Blog categories')
            ->assertSee('Prefix for Blog tags')
            ->assertSee('Prefix for Galleries')
            ->assertSee('Prefix for Botble\\Member\\Models\\Member')
            ->assertSee('Postfix for single page URL')
            ->assertSee('Preview:')
            ->assertSee('/tag/your-url-here')
            ->assertSee('/galleries/your-url-here')
            ->assertSee('/author/your-url-here')
            ->assertSee('/your-url-here.html')
            ->assertSee('Turn off automatic URL translation into Latin?')
            ->assertSee('Translations:')
            ->assertSee('English')
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_admin_appearance_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'admin_logo_url' => 'https://example.com/admin-logo.png',
            'admin_favicon_url' => 'https://example.com/admin-favicon.ico',
            'admin_login_screen_background_urls' => '["https:\/\/example.com\/login-bg.jpg"]',
            'admin_title' => 'Sitewyn Control',
            'admin_primary_font' => 'roboto',
            'admin_primary_color' => '#111111',
            'admin_secondary_color' => '#222222',
            'admin_heading_color' => '#333333',
            'admin_text_color' => '#444444',
            'admin_link_color' => '#555555',
            'admin_link_hover_color' => '#666666',
            'admin_language_direction' => 'rtl',
            'admin_rich_editor' => 'tinymce',
            'admin_layout' => 'horizontal',
            'admin_container_width' => 'full',
            'admin_show_guidelines' => '1',
            'admin_custom_css' => '.page { color: #111; }',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/admin-appearance')
            ->assertOk()
            ->assertSee('Admin appearance')
            ->assertSee('View and update logo, favicon, layout,...')
            ->assertSee('Admin logo')
            ->assertSee('data-bs-target="#admin-logo-media-picker-modal"', false)
            ->assertSee('id="admin-logo-url-modal"', false)
            ->assertSee('Logo height (px)')
            ->assertSee('Admin favicon')
            ->assertSee('data-bs-target="#admin-favicon-media-picker-modal"', false)
            ->assertSee('id="admin-favicon-url-modal"', false)
            ->assertSee('Admin favicon type')
            ->assertSee('ICO')
            ->assertSee('PNG')
            ->assertSee('SVG')
            ->assertSee('GIF')
            ->assertSee('JPEG')
            ->assertSee('WebP')
            ->assertSee('Login screen backgrounds (~1366 × 768)')
            ->assertSee('Click here to add more images.')
            ->assertSee('id="admin-background-url-modal"', false)
            ->assertSee('Admin title')
            ->assertSee('Primary font')
            ->assertSee('Fonts are loaded from Google Fonts API while searching')
            ->assertSee('data-admin-font-select', false)
            ->assertSee('data-font-api-url="/admin/settings/admin-appearance/google-fonts"', false)
            ->assertSee('Roboto')
            ->assertSee('Primary color')
            ->assertSee('Secondary color')
            ->assertSee('Heading color')
            ->assertSee('Text color')
            ->assertSee('Link color')
            ->assertSee('Link hover color')
            ->assertSee('Admin language')
            ->assertSee('Default (follow site language)')
            ->assertSee('Select admin language')
            ->assertSee('data-admin-language-select', false)
            ->assertSee('Vietnamese - Tiếng Việt')
            ->assertSee('Admin language direction')
            ->assertSee('Left to Right')
            ->assertSee('Right to Left')
            ->assertSee('Rich Editor')
            ->assertSee('CKEditor')
            ->assertSee('TinyMCE')
            ->assertSee('Enable page visual builder')
            ->assertSee('Layout')
            ->assertSee('Vertical')
            ->assertSee('Horizontal')
            ->assertSee('Container width')
            ->assertSee('Show menu item icon')
            ->assertSee('Show admin bar for logged-in admins, even in the front site')
            ->assertSee('Show guidelines')
            ->assertSee('Show Get Started wizard')
            ->assertSee('Custom CSS')
            ->assertSee('Header JS')
            ->assertSee('Body JS')
            ->assertSee('Footer JS')
            ->assertSee('Save settings')
            ->assertSee('https://example.com/admin-logo.png')
            ->assertSee('https://example.com/login-bg.jpg')
            ->assertSee('Sitewyn Control')
            ->assertSee('value="roboto"', false)
            ->assertSee('value="#111111"', false)
            ->assertSee('.page { color: #111; }');
    }

    public function test_super_admin_can_view_api_settings_form_when_api_is_disabled(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/api')
            ->assertOk()
            ->assertSee('API settings')
            ->assertSee('Configure your API access and security settings')
            ->assertSee('Enable API')
            ->assertSee('Enable or disable the REST API for your website. When disabled, all API endpoints will be inaccessible.')
            ->assertSee('data-api-settings-panel', false)
            ->assertSee('data-api-enabled-toggle', false)
            ->assertSee('Save settings')
            ->assertSee('class="d-none" data-api-settings-panel', false);
    }

    public function test_super_admin_can_view_api_settings_form_when_api_is_enabled(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'api_enabled' => '1',
            'api_key' => 'existing-api-key',
            'api_push_notifications_enabled' => '1',
            'api_fcm_project_id' => 'sitewyn-mobile',
            'api_fcm_service_account_json' => '{"type":"service_account"}',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/api')
            ->assertOk()
            ->assertSee('Security Settings')
            ->assertSee('API Key')
            ->assertSee('existing-api-key')
            ->assertSee('Generate Random Key')
            ->assertSee('Push Notifications (FCM v1 API)')
            ->assertSee('Enable Push Notifications')
            ->assertSee('Firebase Project ID')
            ->assertSee('sitewyn-mobile')
            ->assertSee('Service account JSON')
            ->assertSee('Help & Documentation', false)
            ->assertSee('Generate API Documentation')
            ->assertSee('composer require knuckleswtf/scribe')
            ->assertSee('php artisan scribe:generate')
            ->assertSee(url('/docs'))
            ->assertSee('Usage Examples')
            ->assertSee('X-API-KEY: your-api-key-here')
            ->assertSee(url('/api/v1/pages'))
            ->assertSee('data-api-key-warning', false)
            ->assertSee('d-none', false);
    }

    public function test_super_admin_can_view_cache_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/cache')
            ->assertOk()
            ->assertSee('Cache')
            ->assertSee('Configure caching for optimized speed')
            ->assertSee('Cache admin menu')
            ->assertSee('Cache admin menu for optimized speed. This option should be disabled if you are developing or customizing the admin menu.')
            ->assertSee('Cache front menu')
            ->assertSee('Cache user avatar')
            ->assertSee('Cache shortcodes (UI blocks)')
            ->assertSee('Important Notice')
            ->assertSee('Cache duration (seconds)')
            ->assertSee('value="1800"', false)
            ->assertSee('Cache widgets')
            ->assertSee('Cache installed plugins')
            ->assertSee('Cache plugin list for 30 minutes')
            ->assertSee('Cache size warning threshold (MB)')
            ->assertSee('value="50"', false)
            ->assertSee('Auto-clear cache when size exceeds threshold')
            ->assertSee('php artisan schedule:run')
            ->assertSee('Cache sitemap')
            ->assertSee(url('/sitemap.xml'))
            ->assertSee('Sitemap cache timeout (in minutes)')
            ->assertSee('Public cache headers (CDN / reverse proxy)')
            ->assertSee('X-Public-Cache-Skip')
            ->assertSee('Public cache duration (seconds)')
            ->assertSee('value="120"', false)
            ->assertSee('data-cache-toggle="shortcodes"', false)
            ->assertSee('data-cache-panel="widgets"', false)
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_cache_settings_form_with_disabled_panels(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'cache_shortcodes' => '0',
            'cache_widgets' => '0',
            'cache_sitemap' => '0',
            'cache_public_headers' => '0',
            'cache_shortcodes_duration' => '900',
            'cache_widgets_duration' => '600',
            'cache_sitemap_timeout' => '30',
            'cache_public_duration' => '300',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/cache')
            ->assertOk()
            ->assertSee('class="d-none" data-cache-panel="shortcodes"', false)
            ->assertSee('class="d-none" data-cache-panel="widgets"', false)
            ->assertSee('class="d-none" data-cache-panel="sitemap"', false)
            ->assertSee('class="d-none" data-cache-panel="public-headers"', false)
            ->assertSee('value="900"', false)
            ->assertSee('value="600"', false)
            ->assertSee('value="30"', false)
            ->assertSee('value="300"', false);
    }

    public function test_super_admin_can_view_datatables_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/datatables')
            ->assertOk()
            ->assertSee('Datatables')
            ->assertSee('Settings for datatables')
            ->assertSee('Pagination type')
            ->assertSee('Default')
            ->assertSee('Dropdown')
            ->assertSee('Choose how pagination controls are displayed: Default shows page numbers, Dropdown shows a compact dropdown selector')
            ->assertSee('Show column visibility by default')
            ->assertSee('Enable the column visibility toggle button in data tables to allow users to show/hide columns')
            ->assertSee('Show export button by default')
            ->assertSee('Display export options (CSV, Excel, PDF) in data tables for downloading table data')
            ->assertSee('Enable table responsive')
            ->assertSee('Automatically adjust table columns to fit different screen sizes for better mobile experience')
            ->assertSee('Save settings')
            ->assertSee('name="datatables_enable_table_responsive" value="1" class="form-check-input" checked', false);
    }

    public function test_super_admin_can_view_datatables_settings_form_with_stored_values(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'datatables_pagination_type' => 'dropdown',
            'datatables_show_column_visibility' => '1',
            'datatables_show_export_button' => '1',
            'datatables_enable_table_responsive' => '0',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/datatables')
            ->assertOk()
            ->assertSee('<option value="dropdown" selected>Dropdown</option>', false)
            ->assertSee('name="datatables_show_column_visibility" value="1" class="form-check-input" checked', false)
            ->assertSee('name="datatables_show_export_button" value="1" class="form-check-input" checked', false)
            ->assertSee('name="datatables_enable_table_responsive" value="1" class="form-check-input"', false);
    }

    public function test_super_admin_can_view_website_tracking_settings_form_for_gtm(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/website-tracking')
            ->assertOk()
            ->assertSee('Website Tracking')
            ->assertSee('Choose your preferred analytics and tracking method. Only one option can be active at a time.')
            ->assertSee('Google Tag Manager (Recommended)')
            ->assertSee('Google Analytics Only')
            ->assertSee('Custom Tracking Code')
            ->assertSee('Best for managing multiple tracking services')
            ->assertSee('Setup Instructions')
            ->assertSee('Create GTM Account')
            ->assertSee('Find Container ID')
            ->assertSee('GTM Container ID')
            ->assertSee('GTM-XXXXXXX')
            ->assertSee('Enable GTM Debug Mode')
            ->assertSee('Include customer data on purchase (Enhanced Conversions)')
            ->assertSee('Adding GA4 Tracking with GTM')
            ->assertSee('How to Verify Your Setup')
            ->assertSee('Common Issues')
            ->assertSee('data-website-tracking-panel="gtm"', false)
            ->assertSee('data-website-tracking-toggle', false)
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_website_tracking_settings_form_for_ga4(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'website_tracking_type' => 'ga',
            'website_tracking_ga_measurement_id' => 'G-76NX8HY29D',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/website-tracking')
            ->assertOk()
            ->assertSee('Simple setup for basic Google Analytics tracking')
            ->assertSee('Create GA4 Property')
            ->assertSee('Find Measurement ID')
            ->assertSee('Google Analytics Only')
            ->assertSee('G-76NX8HY29D')
            ->assertSee('Common Mistakes')
            ->assertSee('Check Realtime Report')
            ->assertSee('class="d-none" data-website-tracking-panel="gtm"', false);
    }

    public function test_super_admin_can_view_website_tracking_settings_form_for_custom_code(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'website_tracking_type' => 'custom',
            'website_tracking_custom_header_script' => '<script>window.analyticsLoaded = true</script>',
            'website_tracking_custom_body_code' => '<noscript>Tracking fallback</noscript>',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/website-tracking')
            ->assertOk()
            ->assertSee('For advanced users who need to add custom tracking scripts')
            ->assertSee('Popular Analytics Services')
            ->assertSee('Matomo')
            ->assertSee('Plausible')
            ->assertSee('Facebook Pixel')
            ->assertSee('Best Practices')
            ->assertSee('Step 1: Header tracking script')
            ->assertSee('Step 2: Body tracking code (Optional)')
            ->assertSee('Paste any noscript or additional code that goes after the opening')
            ->assertSee('window.analyticsLoaded = true')
            ->assertSee('Tracking fallback')
            ->assertSee('class="d-none" data-website-tracking-panel="ga"', false);
    }

    public function test_super_admin_can_view_optimize_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/optimize')
            ->assertOk()
            ->assertSee('Optimize')
            ->assertSee('Minify HTML output, inline CSS, remove comments...')
            ->assertSee('Enable optimize page speed?')
            ->assertSee('Collapse white space')
            ->assertSee('This filter reduces bytes transmitted in an HTML file by removing unnecessary whitespace.')
            ->assertSee('Elide attributes')
            ->assertSee('This filter reduces the transfer size of HTML files by removing attributes from tags')
            ->assertSee('Inline CSS')
            ->assertSee('This filter transforms the inline "style" attribute of tags into classes by moving the CSS to the header.')
            ->assertSee('Insert DNS prefetch')
            ->assertSee('This filter injects tags in the HEAD to enable the browser to do DNS prefetching.')
            ->assertSee('Remove comments')
            ->assertSee('This filter eliminates HTML, JS and CSS comments.')
            ->assertSee('Remove quotes')
            ->assertSee('This filter eliminates unnecessary quotation marks from HTML attributes.')
            ->assertSee('Defer javascript')
            ->assertSee('data-pagespeed-no-defer')
            ->assertSee('data-optimize-page-speed-panel', false)
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_optimize_settings_form_when_page_speed_is_disabled(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'optimize_page_speed_enabled' => '0',
            'optimize_defer_javascript' => '1',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/optimize')
            ->assertOk()
            ->assertSee('class="border rounded p-4 d-none" data-optimize-page-speed-panel', false)
            ->assertSee('name="optimize_defer_javascript" value="1" class="form-check-input" checked', false);
    }

    public function test_super_admin_can_view_blog_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/blog')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Settings')
            ->assertSee('Others')
            ->assertSee('Blog')
            ->assertSee('View and update blog settings')
            ->assertSee('Enable Schema for blog posts')
            ->assertSee('Learn more:')
            ->assertSee('https://schema.org/Article')
            ->assertSee('Schema type')
            ->assertSee('BlogPosting')
            ->assertSee('Article')
            ->assertSee('NewsArticle')
            ->assertSee('Add anchor links to post headings')
            ->assertSee('Gives each h2 and h3 in a post body an id')
            ->assertSee('data-blog-schema-toggle', false)
            ->assertSee('data-blog-schema-panel', false)
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_blog_settings_form_when_schema_is_disabled(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'blog_schema_enabled' => '0',
            'blog_schema_type' => 'NewsArticle',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/blog')
            ->assertOk()
            ->assertSee('class="border rounded p-4 mb-4 d-none" data-blog-schema-panel', false)
            ->assertSee('value="NewsArticle" selected', false);
    }

    public function test_super_admin_can_view_member_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/members')
            ->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('Settings')
            ->assertSee('Member')
            ->assertSee('View and update member settings')
            ->assertSee('Allow visitors to login?')
            ->assertSee('When it is enabled, visitors can log in to your site if they have an account.')
            ->assertSee('Allow visitors to register account?')
            ->assertSeeText("Verify account's email?")
            ->assertSee('Verification link expiration (minutes)')
            ->assertSee('Maximum is 10080 minutes (7 days).')
            ->assertSee('Enable post approval?')
            ->assertSee('Default avatar')
            ->assertSee('Choose image')
            ->assertSee('Add from URL')
            ->assertSee('Show Terms and Policy checkbox?')
            ->assertSee('data-member-avatar-preview', false)
            ->assertSee('member-default-avatar-media-picker-modal', false)
            ->assertSee('member-avatar-url-modal', false)
            ->assertSee('Save settings');
    }

    public function test_super_admin_can_view_email_template_settings_form(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        app(SettingStore::class)->setMany([
            'email_template_logo_url' => 'https://example.com/logo.png',
            'email_template_contact_email_address' => 'support@example.com',
            'email_template_logo_height' => '48',
            'email_template_social_links' => '[{"label":"Facebook","url":"https://facebook.com/sitewyn","icon_image":"42","icon_url":"https://example.com/facebook.png"}]',
        ]);

        $this->actingAs($admin, 'admin')
            ->get('/admin/settings/email/templates')
            ->assertOk()
            ->assertSee('Email Template Settings')
            ->assertSee('View and update your email templates settings')
            ->assertSee('Logo')
            ->assertSee('Choose image')
            ->assertSee('or Add from URL')
            ->assertSee('data-bs-target="#email-template-logo-media-picker-modal"', false)
            ->assertSee('Media gallery')
            ->assertSee('data-media-picker-endpoint="'.route('admin.media.picker', [], false).'"', false)
            ->assertSee('id="email-template-logo-url-modal"', false)
            ->assertSee('Add from URL')
            ->assertSee('Download image to local storage')
            ->assertSee('If it is unchecked, the image will be displayed from the original URL')
            ->assertSee('Contact email address')
            ->assertSee('e.g: example@domain.com')
            ->assertSee('Copyright')
            ->assertSee('Logo height (px)')
            ->assertSee('Email template custom CSS')
            ->assertSee('Social Links')
            ->assertSee('Icon Image (Supports only PNG, JPG, JPEG, and GIF formats.)')
            ->assertSee('data-bs-target="#email-template-social-icon-picker-media-picker-modal"', false)
            ->assertSee('id="email-template-social-icon-url-modal"', false)
            ->assertSee('Add new')
            ->assertSee('Save settings')
            ->assertSee('Email templates')
            ->assertSeeText('Email templates using HTML & system variables.')
            ->assertSee('Base template')
            ->assertSee('Email template header')
            ->assertSee('Email template footer')
            ->assertSee('Test email')
            ->assertSee('ACL')
            ->assertSee('Reset password')
            ->assertSee('Contact')
            ->assertSee('Send notice to administrator')
            ->assertSee('Send confirmation to sender')
            ->assertSee('Admin reply to contact')
            ->assertSee('Comment')
            ->assertSee('Admin notification for new comment')
            ->assertSee('Notify commenter of reply')
            ->assertSee('Member')
            ->assertSee('Confirm email')
            ->assertSee('New pending post')
            ->assertSee('support@example.com')
            ->assertSee('https://facebook.com/sitewyn')
            ->assertSee('https://example.com/facebook.png');
    }

    public function test_super_admin_can_update_general_settings_and_refresh_cache(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        Cache::put('sitewyn.settings', [
            'site_name' => 'Cached Name',
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => '/storage/site-logo.svg',
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'site_name',
            'value' => 'Sitewyn Personal',
            'group' => 'general',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'site_logo',
            'value' => '/storage/site-logo.svg',
            'group' => 'general',
        ]);
        $this->assertSame('Sitewyn Personal', app(SettingStore::class)->get('site_name'));
        $this->assertSame('Sitewyn Personal', config('app.name'));
    }

    public function test_super_admin_can_update_extended_general_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/general')
            ->put('/admin/settings', [
                '_redirect' => '/admin/settings/general',
                'site_name' => 'Sitewyn Personal',
                'admin_emails' => ['admin@example.com', 'owner@example.com'],
                'timezone' => 'Asia/Ho_Chi_Minh',
                'front_site_language_direction' => 'rtl',
                'site_language' => 'en',
                'send_error_reporting_via_email' => '1',
                'redirect_404_to_homepage' => '1',
                'clear_old_request_logs' => '3_months',
                'clear_old_audit_logs' => '6_months',
            ])
            ->assertRedirect('/admin/settings/general')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', [
            'key' => 'admin_emails',
            'value' => '["admin@example.com","owner@example.com"]',
            'group' => 'general',
        ]);
        $this->assertDatabaseHas('settings', ['key' => 'timezone', 'value' => 'Asia/Ho_Chi_Minh']);
        $this->assertDatabaseHas('settings', ['key' => 'front_site_language_direction', 'value' => 'rtl']);
        $this->assertDatabaseHas('settings', ['key' => 'send_error_reporting_via_email', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'redirect_404_to_homepage', 'value' => '1']);
        $this->assertDatabaseHas('settings', ['key' => 'clear_old_request_logs', 'value' => '3_months']);
        $this->assertDatabaseHas('settings', ['key' => 'clear_old_audit_logs', 'value' => '6_months']);
    }

    public function test_super_admin_can_update_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->put('/admin/settings/email', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_mailer' => 'log',
                'email_log_channel' => 'papertrail',
                'email_smtp_port' => null,
                'email_smtp_host' => null,
                'email_smtp_username' => null,
                'email_smtp_password' => null,
                'email_smtp_local_domain' => 'localhost',
                'email_smtp_encryption' => 'none',
                'email_sender_name' => 'Sitewyn Mailer',
                'email_sender_email' => 'mailer@example.com',
                'default_email_language' => 'auto',
                'email_template_statuses' => [
                    'base.header',
                    'base.footer',
                    'acl.reset_password',
                    'contact.notice_admin',
                ],
            ])
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_mailer', 'value' => 'log', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_log_channel', 'value' => 'papertrail', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_local_domain', 'value' => 'localhost', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_encryption', 'value' => 'none', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_sender_name', 'value' => 'Sitewyn Mailer', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_sender_email', 'value' => 'mailer@example.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'default_email_language', 'value' => 'auto', 'group' => 'general']);
        $this->assertDatabaseHas('settings', [
            'key' => 'email_template_statuses',
            'value' => '["base.header","base.footer","acl.reset_password","contact.notice_admin"]',
            'group' => 'general',
        ]);
    }

    public function test_super_admin_can_update_smtp_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->put('/admin/settings/email', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_mailer' => 'smtp',
                'email_smtp_port' => '587',
                'email_smtp_host' => 'smtp.gmail.com',
                'email_smtp_username' => 'sitewyn',
                'email_smtp_password' => 'secret-password',
                'email_smtp_local_domain' => 'cms.example.com',
                'email_smtp_encryption' => 'tls',
                'email_sender_name' => 'Sitewyn Mailer',
                'email_sender_email' => 'mailer@example.com',
                'default_email_language' => 'auto',
            ])
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_mailer', 'value' => 'smtp', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_port', 'value' => '587', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_host', 'value' => 'smtp.gmail.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_username', 'value' => 'sitewyn', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_password', 'value' => 'secret-password', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_local_domain', 'value' => 'cms.example.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_smtp_encryption', 'value' => 'tls', 'group' => 'general']);
    }

    public function test_super_admin_can_update_mailgun_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->put('/admin/settings/email', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_mailer' => 'mailgun',
                'email_mailgun_domain' => 'mg.example.com',
                'email_mailgun_endpoint' => 'api.eu.mailgun.net',
                'email_sender_name' => 'Sitewyn Mailer',
                'email_sender_email' => 'mailer@example.com',
                'default_email_language' => 'auto',
            ])
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_mailer', 'value' => 'mailgun', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_mailgun_domain', 'value' => 'mg.example.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_mailgun_endpoint', 'value' => 'api.eu.mailgun.net', 'group' => 'general']);
    }

    public function test_super_admin_can_update_ses_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->put('/admin/settings/email', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_mailer' => 'ses',
                'email_ses_key' => 'AKIAIOSFODNN7EXAMPLE',
                'email_ses_region' => 'ap-southeast-1',
                'email_sender_name' => 'Sitewyn Mailer',
                'email_sender_email' => 'mailer@example.com',
                'default_email_language' => 'auto',
            ])
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_mailer', 'value' => 'ses', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_ses_key', 'value' => 'AKIAIOSFODNN7EXAMPLE', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_ses_region', 'value' => 'ap-southeast-1', 'group' => 'general']);
    }

    public function test_super_admin_can_update_sendgrid_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->put('/admin/settings/email', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_mailer' => 'sendgrid',
                'email_sendgrid_key' => 'SG.example',
                'email_sender_name' => 'Sitewyn Mailer',
                'email_sender_email' => 'mailer@example.com',
                'default_email_language' => 'auto',
            ])
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_mailer', 'value' => 'sendgrid', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_sendgrid_key', 'value' => 'SG.example', 'group' => 'general']);
    }

    public function test_super_admin_can_update_sendmail_email_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->put('/admin/settings/email', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_mailer' => 'sendmail',
                'email_sendmail_path' => '/usr/sbin/sendmail -bs -i',
                'email_sender_name' => 'Sitewyn Mailer',
                'email_sender_email' => 'mailer@example.com',
                'default_email_language' => 'auto',
            ])
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_mailer', 'value' => 'sendmail', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_sendmail_path', 'value' => '/usr/sbin/sendmail -bs -i', 'group' => 'general']);
    }

    public function test_super_admin_can_update_email_template_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email/templates')
            ->put('/admin/settings/email/templates', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_template_logo' => 'logo-id',
                'email_template_logo_url' => 'https://example.com/email-logo.png',
                'email_template_contact_email_address' => 'mail@example.com',
                'email_template_copyright' => 'Copyright 2026',
                'email_template_logo_height' => '56',
                'email_template_custom_css' => '.email-body { color: #222; }',
                'email_template_social_link_labels' => ['Facebook', 'GitHub', ''],
                'email_template_social_link_urls' => ['https://facebook.com/sitewyn', 'https://github.com/kenvbx/Sitewyn', ''],
                'email_template_social_link_icon_images' => ['42', '', ''],
                'email_template_social_link_icon_urls' => ['https://example.com/facebook.png', 'https://example.com/github.png', ''],
            ])
            ->assertRedirect('/admin/settings/email/templates')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_template_logo', 'value' => 'logo-id', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_template_logo_url', 'value' => 'https://example.com/email-logo.png', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_template_contact_email_address', 'value' => 'mail@example.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_template_copyright', 'value' => 'Copyright 2026', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_template_logo_height', 'value' => '56', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_template_custom_css', 'value' => '.email-body { color: #222; }', 'group' => 'general']);
        $this->assertDatabaseHas('settings', [
            'key' => 'email_template_social_links',
            'value' => '[{"label":"Facebook","url":"https:\/\/facebook.com\/sitewyn","icon_image":"42","icon_url":"https:\/\/example.com\/facebook.png"},{"label":"GitHub","url":"https:\/\/github.com\/kenvbx\/Sitewyn","icon_image":"","icon_url":"https:\/\/example.com\/github.png"}]',
            'group' => 'general',
        ]);
    }

    public function test_super_admin_can_update_email_rules_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email/rules')
            ->put('/admin/settings/email/rules', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'email_rules_blacklisted_domains' => 'gmail.com, yahoo.com',
                'email_rules_blacklisted_addresses' => 'blocked@example.com, bad@example.com',
                'email_rules_exception_emails' => 'owner@example.com',
                'email_rules_strict_validation' => '1',
                'email_rules_dns_check_validation' => '1',
            ])
            ->assertRedirect('/admin/settings/email/rules')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'email_rules_blacklisted_domains', 'value' => 'gmail.com, yahoo.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_rules_blacklisted_addresses', 'value' => 'blocked@example.com, bad@example.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_rules_exception_emails', 'value' => 'owner@example.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_rules_strict_validation', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_rules_dns_check_validation', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'email_rules_spoofing_detection', 'value' => '0', 'group' => 'general']);
    }

    public function test_super_admin_can_update_phone_number_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/phone-number')
            ->put('/admin/settings/phone-number', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'phone_number_enable_country_code' => '1',
                'phone_number_available_countries_all' => '0',
                'phone_number_available_countries' => ['VN', 'US', 'JP'],
                'phone_number_minimum_length' => '7',
                'phone_number_maximum_length' => '20',
            ])
            ->assertRedirect('/admin/settings/phone-number')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'phone_number_enable_country_code', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'phone_number_available_countries_all', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'phone_number_available_countries', 'value' => '["VN","US","JP"]', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'phone_number_minimum_length', 'value' => '7', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'phone_number_maximum_length', 'value' => '20', 'group' => 'general']);
    }

    public function test_super_admin_can_update_media_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/media')
            ->put('/admin/settings/media', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'media_driver' => 'backblaze_b2',
                'media_backblaze_access_key_id' => '005febe473bdd490000xxxxxxx',
                'media_backblaze_secret_key' => 'K005C3JkwgUkUSh+4bZLoTkiBxxxxxx',
                'media_backblaze_default_region' => 'eu-central-003',
                'media_backblaze_bucket' => 'sitewyn',
                'media_backblaze_endpoint' => 'https://s3.eu-central-003.backblazeb2.com',
                'media_backblaze_use_path_style_endpoint' => '1',
                'media_backblaze_cdn_enabled' => '1',
                'media_use_original_name_for_file_path' => '1',
                'media_convert_file_name_to_uuid' => '1',
                'media_keep_original_file_size_quality' => '0',
                'media_image_quality' => '82',
                'media_turn_off_automatic_url_translation_into_latin' => '1',
                'media_users_can_only_view_own_media' => '1',
                'media_convert_image_to_webp' => '1',
                'media_default_placeholder_image' => '42',
                'media_default_placeholder_image_url' => 'https://example.com/placeholder.png',
                'media_max_upload_filesize' => '12',
                'media_reduce_large_image_size' => '1',
                'media_customize_upload_path' => '1',
                'media_enable_chunk_upload' => '1',
                'media_enable_watermark' => '1',
                'media_image_processing_library' => 'imagick',
                'media_enable_thumbnail_sizes' => '1',
                'media_thumbnail_thumb_width' => '160',
                'media_thumbnail_thumb_height' => '160',
                'media_thumbnail_featured_width' => '600',
                'media_thumbnail_featured_height' => '400',
                'media_thumbnail_medium_width' => '540',
                'media_thumbnail_medium_height' => '360',
                'media_thumbnail_small_width' => '375',
                'media_thumbnail_small_height' => '250',
                'media_thumbnail_size_270x180_width' => '270',
                'media_thumbnail_size_270x180_height' => '180',
                'media_thumbnail_crop_position' => 'bottom-right',
            ])
            ->assertRedirect('/admin/settings/media')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'media_driver', 'value' => 'backblaze_b2', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_access_key_id', 'value' => '005febe473bdd490000xxxxxxx', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_secret_key', 'value' => 'K005C3JkwgUkUSh+4bZLoTkiBxxxxxx', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_default_region', 'value' => 'eu-central-003', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_bucket', 'value' => 'sitewyn', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_endpoint', 'value' => 'https://s3.eu-central-003.backblazeb2.com', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_use_path_style_endpoint', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_backblaze_cdn_enabled', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_use_original_name_for_file_path', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_convert_file_name_to_uuid', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_keep_original_file_size_quality', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_image_quality', 'value' => '82', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_turn_off_automatic_url_translation_into_latin', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_users_can_only_view_own_media', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_convert_image_to_webp', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_default_placeholder_image', 'value' => '42', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_default_placeholder_image_url', 'value' => 'https://example.com/placeholder.png', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_max_upload_filesize', 'value' => '12', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_reduce_large_image_size', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_customize_upload_path', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_enable_chunk_upload', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_enable_watermark', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_image_processing_library', 'value' => 'imagick', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'media_enable_thumbnail_sizes', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', [
            'key' => 'media_thumbnail_sizes',
            'value' => '{"thumb":{"width":160,"height":160},"featured":{"width":600,"height":400},"medium":{"width":540,"height":360},"small":{"width":375,"height":250},"size_270x180":{"width":270,"height":180}}',
            'group' => 'general',
        ]);
        $this->assertDatabaseHas('settings', ['key' => 'media_thumbnail_crop_position', 'value' => 'bottom-right', 'group' => 'general']);
    }

    public function test_super_admin_can_update_permalink_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/permalink')
            ->put('/admin/settings/permalink', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'permalink_pages_prefix' => '/pages/',
                'permalink_blog_posts_prefix' => 'blog',
                'permalink_blog_categories_prefix' => 'category',
                'permalink_blog_tags_prefix' => 'tag',
                'permalink_galleries_prefix' => 'galleries',
                'permalink_member_prefix' => 'author',
                'permalink_single_page_postfix' => '.html',
                'permalink_turn_off_automatic_url_translation_into_latin' => '1',
            ])
            ->assertRedirect('/admin/settings/permalink')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'permalink_pages_prefix', 'value' => 'pages', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_blog_posts_prefix', 'value' => 'blog', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_blog_categories_prefix', 'value' => 'category', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_blog_tags_prefix', 'value' => 'tag', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_galleries_prefix', 'value' => 'galleries', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_member_prefix', 'value' => 'author', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_single_page_postfix', 'value' => '.html', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'permalink_turn_off_automatic_url_translation_into_latin', 'value' => '1', 'group' => 'general']);
    }

    public function test_super_admin_can_update_admin_appearance_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/admin-appearance')
            ->put('/admin/settings/admin-appearance', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'admin_logo' => '11',
                'admin_logo_url' => 'https://example.com/admin-logo.png',
                'admin_logo_height' => '40',
                'admin_favicon' => '12',
                'admin_favicon_url' => 'https://example.com/admin-favicon.webp',
                'admin_favicon_type' => 'webp',
                'admin_login_screen_backgrounds' => ['21', '22'],
                'admin_login_screen_background_urls' => [
                    'https://example.com/login-bg-1.jpg',
                    'https://example.com/login-bg-2.jpg',
                ],
                'admin_title' => 'Sitewyn Control',
                'admin_primary_font' => 'poppins',
                'admin_primary_color' => '#206bc4',
                'admin_secondary_color' => '#6c7a91',
                'admin_heading_color' => '#182433',
                'admin_text_color' => '#182433',
                'admin_link_color' => '#206bc4',
                'admin_link_hover_color' => '#1a569d',
                'admin_language' => 'vi',
                'admin_language_direction' => 'rtl',
                'admin_rich_editor' => 'tinymce',
                'admin_enable_page_visual_builder' => '1',
                'admin_layout' => 'horizontal',
                'admin_container_width' => 'large',
                'admin_show_menu_item_icon' => '1',
                'admin_show_admin_bar' => '1',
                'admin_show_guidelines' => '1',
                'admin_show_get_started_wizard' => '0',
                'admin_custom_css' => '.page { color: #111; }',
                'admin_header_js' => '<script>window.headerLoaded = true</script>',
                'admin_body_js' => '<script>window.bodyLoaded = true</script>',
                'admin_footer_js' => '<script>window.footerLoaded = true</script>',
            ])
            ->assertRedirect('/admin/settings/admin-appearance')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'admin_logo', 'value' => '11', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_logo_url', 'value' => 'https://example.com/admin-logo.png', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_logo_height', 'value' => '40', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_favicon', 'value' => '12', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_favicon_url', 'value' => 'https://example.com/admin-favicon.webp', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_favicon_type', 'value' => 'webp', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_login_screen_backgrounds', 'value' => '["21","22"]', 'group' => 'general']);
        $this->assertDatabaseHas('settings', [
            'key' => 'admin_login_screen_background_urls',
            'value' => '["https:\/\/example.com\/login-bg-1.jpg","https:\/\/example.com\/login-bg-2.jpg"]',
            'group' => 'general',
        ]);
        $this->assertDatabaseHas('settings', ['key' => 'admin_title', 'value' => 'Sitewyn Control', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_primary_font', 'value' => 'poppins', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_primary_color', 'value' => '#206bc4', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_language', 'value' => 'vi', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_language_direction', 'value' => 'rtl', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_rich_editor', 'value' => 'tinymce', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_enable_page_visual_builder', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_layout', 'value' => 'horizontal', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_container_width', 'value' => 'large', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_show_guidelines', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_show_get_started_wizard', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_custom_css', 'value' => '.page { color: #111; }', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_header_js', 'value' => '<script>window.headerLoaded = true</script>', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_body_js', 'value' => '<script>window.bodyLoaded = true</script>', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'admin_footer_js', 'value' => '<script>window.footerLoaded = true</script>', 'group' => 'general']);
    }

    public function test_super_admin_can_update_api_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/api')
            ->put('/admin/settings/api', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'api_enabled' => '1',
                'api_key' => 'secret-api-key',
                'api_push_notifications_enabled' => '1',
                'api_fcm_project_id' => 'sitewyn-mobile',
                'api_fcm_service_account_json' => '{"type":"service_account"}',
            ])
            ->assertRedirect('/admin/settings/api')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'api_enabled', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'api_key', 'value' => 'secret-api-key', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'api_push_notifications_enabled', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'api_fcm_project_id', 'value' => 'sitewyn-mobile', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'api_fcm_service_account_json', 'value' => '{"type":"service_account"}', 'group' => 'general']);
    }

    public function test_super_admin_can_update_cache_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/cache')
            ->put('/admin/settings/cache', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'cache_admin_menu' => '0',
                'cache_front_menu' => '1',
                'cache_user_avatar' => '0',
                'cache_shortcodes' => '1',
                'cache_shortcodes_duration' => '900',
                'cache_widgets' => '0',
                'cache_widgets_duration' => '600',
                'cache_installed_plugins' => '1',
                'cache_size_warning_threshold' => '80',
                'cache_auto_clear_when_size_exceeds_threshold' => '1',
                'cache_sitemap' => '1',
                'cache_sitemap_timeout' => '45',
                'cache_public_headers' => '0',
                'cache_public_duration' => '300',
            ])
            ->assertRedirect('/admin/settings/cache')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'cache_admin_menu', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_front_menu', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_user_avatar', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_shortcodes', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_shortcodes_duration', 'value' => '900', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_widgets', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_widgets_duration', 'value' => '600', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_installed_plugins', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_size_warning_threshold', 'value' => '80', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_auto_clear_when_size_exceeds_threshold', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_sitemap', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_sitemap_timeout', 'value' => '45', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_public_headers', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'cache_public_duration', 'value' => '300', 'group' => 'general']);
    }

    public function test_super_admin_can_update_datatables_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/datatables')
            ->put('/admin/settings/datatables', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'datatables_pagination_type' => 'dropdown',
                'datatables_show_column_visibility' => '1',
                'datatables_show_export_button' => '1',
                'datatables_enable_table_responsive' => '0',
            ])
            ->assertRedirect('/admin/settings/datatables')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'datatables_pagination_type', 'value' => 'dropdown', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'datatables_show_column_visibility', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'datatables_show_export_button', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'datatables_enable_table_responsive', 'value' => '0', 'group' => 'general']);
    }

    public function test_super_admin_can_update_website_tracking_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/website-tracking')
            ->put('/admin/settings/website-tracking', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'website_tracking_type' => 'custom',
                'website_tracking_gtm_container_id' => 'GTM-NZMK3KH2',
                'website_tracking_gtm_debug_mode' => '1',
                'website_tracking_gtm_include_customer_data' => '1',
                'website_tracking_ga_measurement_id' => 'G-76NX8HY29D',
                'website_tracking_custom_header_script' => '<script>window.analyticsLoaded = true</script>',
                'website_tracking_custom_body_code' => '<noscript>Tracking fallback</noscript>',
            ])
            ->assertRedirect('/admin/settings/website-tracking')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_type', 'value' => 'custom', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_gtm_container_id', 'value' => 'GTM-NZMK3KH2', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_gtm_debug_mode', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_gtm_include_customer_data', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_ga_measurement_id', 'value' => 'G-76NX8HY29D', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_custom_header_script', 'value' => '<script>window.analyticsLoaded = true</script>', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'website_tracking_custom_body_code', 'value' => '<noscript>Tracking fallback</noscript>', 'group' => 'general']);
    }

    public function test_website_tracking_helpers_render_active_tracking_code(): void
    {
        app(SettingStore::class)->setMany([
            'website_tracking_type' => 'gtm',
            'website_tracking_gtm_container_id' => 'gtm-nzmk3kh2',
        ]);

        $this->assertStringContainsString('GTM-NZMK3KH2', site_tracking_head());
        $this->assertStringContainsString('googletagmanager.com/ns.html?id=GTM-NZMK3KH2', site_tracking_body());

        app(SettingStore::class)->setMany([
            'website_tracking_type' => 'ga',
            'website_tracking_ga_measurement_id' => 'g-76nx8hy29d',
        ]);

        $this->assertStringContainsString('gtag/js?id=G-76NX8HY29D', site_tracking_head());
        $this->assertSame('', site_tracking_body());

        app(SettingStore::class)->setMany([
            'website_tracking_type' => 'custom',
            'website_tracking_custom_header_script' => '<script>window.analyticsLoaded = true</script>',
            'website_tracking_custom_body_code' => '<noscript>Tracking fallback</noscript>',
        ]);

        $this->assertSame('<script>window.analyticsLoaded = true</script>', site_tracking_head());
        $this->assertSame('<noscript>Tracking fallback</noscript>', site_tracking_body());
    }

    public function test_super_admin_can_update_optimize_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/optimize')
            ->put('/admin/settings/optimize', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'optimize_page_speed_enabled' => '1',
                'optimize_collapse_whitespace' => '1',
                'optimize_elide_attributes' => '0',
                'optimize_inline_css' => '1',
                'optimize_insert_dns_prefetch' => '1',
                'optimize_remove_comments' => '0',
                'optimize_remove_quotes' => '1',
                'optimize_defer_javascript' => '1',
            ])
            ->assertRedirect('/admin/settings/optimize')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'optimize_page_speed_enabled', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_collapse_whitespace', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_elide_attributes', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_inline_css', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_insert_dns_prefetch', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_remove_comments', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_remove_quotes', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'optimize_defer_javascript', 'value' => '1', 'group' => 'general']);
    }

    public function test_super_admin_can_update_blog_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/blog')
            ->put('/admin/settings/blog', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'blog_schema_enabled' => '1',
                'blog_schema_type' => 'NewsArticle',
                'blog_anchor_links_enabled' => '1',
            ])
            ->assertRedirect('/admin/settings/blog')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'blog_schema_enabled', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'blog_schema_type', 'value' => 'NewsArticle', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'blog_anchor_links_enabled', 'value' => '1', 'group' => 'general']);
    }

    public function test_super_admin_can_update_member_settings(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/members')
            ->put('/admin/settings/members', [
                'site_name' => 'Sitewyn Personal',
                'site_logo' => null,
                'robots_txt' => null,
                'active_theme' => 'default',
                'member_allow_login' => '1',
                'member_allow_register' => '0',
                'member_verify_email' => '1',
                'member_verification_expiration' => '120',
                'member_post_approval' => '1',
                'member_default_avatar' => '15',
                'member_default_avatar_url' => 'https://example.com/avatar.png',
                'member_show_terms_policy_checkbox' => '0',
            ])
            ->assertRedirect('/admin/settings/members')
            ->assertSessionHas('status');

        $this->assertDatabaseHas('settings', ['key' => 'member_allow_login', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_allow_register', 'value' => '0', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_verify_email', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_verification_expiration', 'value' => '120', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_post_approval', 'value' => '1', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_default_avatar', 'value' => '15', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_default_avatar_url', 'value' => 'https://example.com/avatar.png', 'group' => 'general']);
        $this->assertDatabaseHas('settings', ['key' => 'member_show_terms_policy_checkbox', 'value' => '0', 'group' => 'general']);
    }

    public function test_super_admin_can_generate_media_thumbnails(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/media')
            ->post('/admin/settings/media/generate-thumbnails')
            ->assertRedirect('/admin/settings/media')
            ->assertSessionHas('status');
    }

    public function test_super_admin_can_send_test_email(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings/email')
            ->post('/admin/settings/email/test')
            ->assertRedirect('/admin/settings/email')
            ->assertSessionHas('status', 'Test email has been sent.');
    }

    public function test_settings_validation_keeps_site_name_required(): void
    {
        $admin = User::factory()->create([
            'is_super_admin' => true,
            'is_active' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->from('/admin/settings')
            ->put('/admin/settings', [
                'site_name' => '',
                'site_logo' => str_repeat('a', 2049),
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHasErrors(['site_name', 'site_logo']);

        $this->assertSame(0, Setting::query()->count());
    }
}
