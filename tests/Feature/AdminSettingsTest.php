<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Sitewyn\Core\Base\Models\Setting;
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
            ->assertSee('Email templates')
            ->assertSee('Email rules')
            ->assertSee('Phone Number')
            ->assertSee('Website Tracking')
            ->assertSee('Localization')
            ->assertSee('Theme Translations')
            ->assertSee('Others')
            ->assertSee('Google Analytics');
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
