@extends('core/base::admin.layouts.master')

@section('title', 'Email Settings - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Email</li>
@endsection

@section('content')
    @php
        $selectedMailer = old('email_mailer', $settings['email_mailer']);
        $showSmtpSettings = $selectedMailer === 'smtp';
        $showSendmailSettings = $selectedMailer === 'sendmail';
        $showMailgunSettings = $selectedMailer === 'mailgun';
        $showSendgridSettings = $selectedMailer === 'sendgrid';
        $showSesSettings = $selectedMailer === 'ses';
        $showLogSettings = $selectedMailer === 'log';
    @endphp

    @if (session('status'))
        <div class="alert alert-success" role="alert">{{ session('status') }}</div>
    @endif

    <form id="email-settings-form" method="POST" action="{{ route('admin.settings.email.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
        <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
        <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
        <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

        <div class="row mb-5 d-block d-md-flex">
            <div class="col-12 col-md-3">
                <h2>Email</h2>
                <p class="text-muted">View and update your email settings and email templates</p>
            </div>

            <div class="col-12 col-md-9">
                <div class="card">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label" for="email-mailer">Mailer</label>
                            <select name="email_mailer" id="email-mailer" class="form-select" data-email-mailer>
                                @foreach ($mailerOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedMailer === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3 {{ $showLogSettings ? '' : 'd-none' }}" data-email-mailer-fields="log">
                            <label class="form-label" for="email-log-channel">Log channel</label>
                            <select name="email_log_channel" id="email-log-channel" class="form-select">
                                @foreach ($logChannelOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($settings['email_log_channel'] === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">Select which logging channel to use for email logs</div>
                        </div>

                        <div data-email-mailer-fields="smtp" class="{{ $showSmtpSettings ? '' : 'd-none' }}">
                            <div class="mb-3">
                                <label class="form-label" for="email-smtp-port">Port</label>
                                <input type="number" name="email_smtp_port" id="email-smtp-port" class="form-control" value="{{ old('email_smtp_port', $settings['email_smtp_port']) }}" placeholder="Ex: 587" min="1" max="65535">
                                <div class="form-hint">The port used by your mail server (common ports: 25, 465, 587)</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-smtp-host">Host</label>
                                <input type="text" name="email_smtp_host" id="email-smtp-host" class="form-control" value="{{ old('email_smtp_host', $settings['email_smtp_host']) }}" placeholder="Ex: smtp.gmail.com" maxlength="255">
                                <div class="form-hint">SMTP host address</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-smtp-username">Username</label>
                                <input type="text" name="email_smtp_username" id="email-smtp-username" class="form-control" value="{{ old('email_smtp_username', $settings['email_smtp_username']) }}" placeholder="Username to login to mail server" maxlength="255">
                                <div class="form-hint">Your mail server login username</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-smtp-password">Password</label>
                                <div class="input-group input-group-flat">
                                    <input type="password" name="email_smtp_password" id="email-smtp-password" class="form-control" value="{{ old('email_smtp_password', $settings['email_smtp_password']) }}" placeholder="Password to login to mail server" maxlength="255" autocomplete="new-password">
                                    <span class="input-group-text">
                                        <button type="button" class="link-secondary border-0 bg-transparent p-0" title="Show password" aria-label="Show password" aria-pressed="false" data-admin-password-toggle="email-smtp-password">
                                            <span class="admin-password-icon-show">@include('core/base::admin.partials.icon', ['name' => 'eye'])</span>
                                            <span class="admin-password-icon-hide d-none">@include('core/base::admin.partials.icon', ['name' => 'eye-off'])</span>
                                        </button>
                                    </span>
                                </div>
                                <div class="form-hint">Your mail server login password</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-smtp-local-domain">Local domain</label>
                                <input type="text" name="email_smtp_local_domain" id="email-smtp-local-domain" class="form-control" value="{{ old('email_smtp_local_domain', $settings['email_smtp_local_domain']) }}" maxlength="255">
                                <div class="form-hint">The domain that will be used to identify the server when communicating with remote SMTP servers</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-smtp-encryption">Encryption</label>
                                <select name="email_smtp_encryption" id="email-smtp-encryption" class="form-select">
                                    <option value="none" @selected(old('email_smtp_encryption', $settings['email_smtp_encryption']) === 'none')>None</option>
                                    <option value="tls" @selected(old('email_smtp_encryption', $settings['email_smtp_encryption']) === 'tls')>TLS</option>
                                    <option value="ssl" @selected(old('email_smtp_encryption', $settings['email_smtp_encryption']) === 'ssl')>SSL</option>
                                </select>
                                <div class="form-hint">Choose the encryption method for secure email transmission</div>
                            </div>
                        </div>

                        <div data-email-mailer-fields="sendmail" class="{{ $showSendmailSettings ? '' : 'd-none' }}">
                            <div class="mb-3">
                                <label class="form-label" for="email-sendmail-path">Sendmail Path</label>
                                <input type="text" name="email_sendmail_path" id="email-sendmail-path" class="form-control" value="{{ old('email_sendmail_path', $settings['email_sendmail_path']) }}" maxlength="255">
                                <div class="form-hint">Default: <code>/usr/sbin/sendmail -bs -i</code></div>
                            </div>
                        </div>

                        <div data-email-mailer-fields="mailgun" class="{{ $showMailgunSettings ? '' : 'd-none' }}">
                            <div class="mb-3">
                                <label class="form-label" for="email-mailgun-domain">Domain</label>
                                <input type="text" name="email_mailgun_domain" id="email-mailgun-domain" class="form-control" value="{{ old('email_mailgun_domain', $settings['email_mailgun_domain']) }}" placeholder="Ex: mg.yourdomain.com" maxlength="255">
                                <div class="form-hint">The domain name you registered with Mailgun</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-mailgun-endpoint">Endpoint</label>
                                <input type="text" name="email_mailgun_endpoint" id="email-mailgun-endpoint" class="form-control" value="{{ old('email_mailgun_endpoint', $settings['email_mailgun_endpoint']) }}" maxlength="255">
                                <div class="form-hint">Mailgun API endpoint (api.mailgun.net for US, api.eu.mailgun.net for EU)</div>
                            </div>
                        </div>

                        <div data-email-mailer-fields="sendgrid" class="{{ $showSendgridSettings ? '' : 'd-none' }}">
                            <div class="mb-3">
                                <label class="form-label" for="email-sendgrid-key">Key</label>
                                <input type="text" name="email_sendgrid_key" id="email-sendgrid-key" class="form-control" value="{{ old('email_sendgrid_key', $settings['email_sendgrid_key']) }}" placeholder="Ex: SG.xxxxxx" maxlength="255">
                                <div class="form-hint">Your SendGrid API key</div>
                            </div>
                        </div>

                        <div data-email-mailer-fields="ses" class="{{ $showSesSettings ? '' : 'd-none' }}">
                            <div class="mb-3">
                                <label class="form-label" for="email-ses-key">Key</label>
                                <input type="text" name="email_ses_key" id="email-ses-key" class="form-control" value="{{ old('email_ses_key', $settings['email_ses_key']) }}" placeholder="Ex: AKIAIOSFODNN7EXAMPLE" maxlength="255">
                                <div class="form-hint">Your AWS access key ID</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label" for="email-ses-region">Region</label>
                                <input type="text" name="email_ses_region" id="email-ses-region" class="form-control" value="{{ old('email_ses_region', $settings['email_ses_region']) }}" maxlength="255">
                                <div class="form-hint">The AWS region where your SES service is configured</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email-sender-name">Sender name</label>
                            <input type="text" name="email_sender_name" id="email-sender-name" class="form-control" value="{{ old('email_sender_name', $settings['email_sender_name']) }}" maxlength="255">
                            <div class="form-hint">The name that will appear in the From field of emails sent by the system</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email-sender-email">Sender email</label>
                            <input type="email" name="email_sender_email" id="email-sender-email" class="form-control" value="{{ old('email_sender_email', $settings['email_sender_email']) }}" maxlength="255">
                            <div class="form-hint">The email address that will be used as the sender for all emails sent by the system</div>
                        </div>

                        <div class="mb-0">
                            <label class="form-label" for="default-email-language">Default email language</label>
                            <select name="default_email_language" id="default-email-language" class="form-select">
                                @foreach ($languageOptions as $value => $label)
                                    <option value="{{ $value }}" @selected(old('default_email_language', $settings['default_email_language']) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="form-hint">Select the default language for customer emails. When set to "Auto", the system will use the default site language. This prevents customer emails from being sent in the staff member's dashboard language.</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 d-flex flex-wrap gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        @include('core/base::admin.partials.icon', ['name' => 'settings'])
                        <span class="ms-2">Save settings</span>
                    </button>
                    <button type="submit" class="btn btn-info btn-lg" form="email-test-form">Send test email</button>
                </div>

                <div class="alert alert-info mt-4" role="alert">
                    <div class="d-flex gap-2">
                        <div>@include('core/base::admin.partials.icon', ['name' => 'info-circle'])</div>
                        <div>
                            <div class="fw-bold mb-1">Email Setup Tips</div>
                            <ul class="mb-0">
                                <li>For Gmail: Use smtp.gmail.com as host, port 587 with TLS or port 465 with SSL. Enable "Less secure app access" or use an App Password.</li>
                                <li>Common ports: 25 (unencrypted), 587 (TLS/STARTTLS), 465 (SSL/TLS), 2525 (alternative).</li>
                                <li>Use TLS for port 587 (recommended), SSL for port 465, or None for port 25 (not recommended for production).</li>
                                <li>Always use "Send Test Email" button to verify your configuration before saving.</li>
                                <li>For better deliverability, consider using email services like Mailgun, SendGrid, or Amazon SES instead of SMTP.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-5 d-block d-md-flex">
            <div class="col-12 col-md-3">
                <h2>Email template status</h2>
                <p class="text-muted">Turn on/off email template</p>
            </div>

            <div class="col-12 col-md-9">
                @foreach ($templateGroups as $group => $templates)
                <div class="card mb-4">
                    <div class="card-header">
                    <h2 class="card-title">{{ $group }}</h2>
                    </div>
                    <div class="table-responsive">
                    <table class="table table-vcenter card-table">
                        <thead>
                        <tr>
                            <th>Template</th>
                            <th>Description</th>
                            <th class="w-1 text-end">Operations</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($templates as $template)
                            <tr>
                            <td class="{{ ($template['muted'] ?? false) ? 'text-decoration-line-through text-secondary' : '' }}">{{ $template['template'] }}</td>
                            <td>{{ $template['description'] }}</td>
                            <td class="text-end">
                                <label class="form-check form-switch m-0 justify-content-end">
                                <input class="form-check-input" type="checkbox" name="email_template_statuses[]" value="{{ $template['key'] }}" @checked($template['enabled'])>
                                </label>
                            </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </form>

    <form id="email-test-form" method="POST" action="{{ route('admin.settings.email.test', [], false) }}">
        @csrf
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var mailer = document.querySelector('[data-email-mailer]')
            var groups = document.querySelectorAll('[data-email-mailer-fields]')

            function syncMailerFields() {
                groups.forEach(function (group) {
                    group.classList.toggle('d-none', group.getAttribute('data-email-mailer-fields') !== mailer.value)
                })
            }

            if (mailer) {
                mailer.addEventListener('change', syncMailerFields)
                syncMailerFields()
            }

            document.querySelectorAll('[data-admin-password-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    var input = document.getElementById(button.getAttribute('data-admin-password-toggle'))

                    if (! input) {
                        return
                    }

                    var show = input.type === 'password'
                    var label = show ? 'Hide password' : 'Show password'

                    input.type = show ? 'text' : 'password'
                    button.querySelector('.admin-password-icon-show').classList.toggle('d-none', ! show)
                    button.querySelector('.admin-password-icon-hide').classList.toggle('d-none', show)
                    button.setAttribute('aria-label', label)
                    button.setAttribute('title', label)
                    button.setAttribute('aria-pressed', show ? 'true' : 'false')
                })
            })
        })
    </script>
@endpush
