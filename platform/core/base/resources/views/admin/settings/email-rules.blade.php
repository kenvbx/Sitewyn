@extends('core/base::admin.layouts.master')

@section('title', 'Email rules - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.settings.edit') }}">Settings</a></li>
  <li class="breadcrumb-item active" aria-current="page">Email rules</li>
@endsection

@section('content')
  @if (session('status'))
    <div class="alert alert-success" role="alert">{{ session('status') }}</div>
  @endif

  <form method="POST" action="{{ route('admin.settings.email.rules.update', [], false) }}" class="needs-validation" data-admin-validate novalidate>
    @csrf
    @method('PUT')
    <input type="hidden" name="site_name" value="{{ $baseSettings['site_name'] }}">
    <input type="hidden" name="site_logo" value="{{ $baseSettings['site_logo'] }}">
    <input type="hidden" name="robots_txt" value="{{ $baseSettings['robots_txt'] }}">
    <input type="hidden" name="active_theme" value="{{ $baseSettings['active_theme'] }}">

    <div class="row mb-5 d-block d-md-flex">
      <div class="col-12 col-md-3">
        <h2>Email rules</h2>
        <p class="text-muted">Configure email rules for validation</p>
      </div>

      <div class="col-12 col-md-9">
        <div class="card">
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label" for="email-rules-blacklisted-domains">Blacklisted Email Domains</label>
              <input type="text" name="email_rules_blacklisted_domains" id="email-rules-blacklisted-domains" value="{{ old('email_rules_blacklisted_domains', $settings['email_rules_blacklisted_domains']) }}" class="form-control" maxlength="5000">
              <div class="form-hint">Enter a list of email domains to be blacklisted. E.g. gmail.com, yahoo.com.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email-rules-blacklisted-addresses">Blacklisted Email Addresses</label>
              <input type="text" name="email_rules_blacklisted_addresses" id="email-rules-blacklisted-addresses" value="{{ old('email_rules_blacklisted_addresses', $settings['email_rules_blacklisted_addresses']) }}" class="form-control" maxlength="5000">
              <div class="form-hint">Enter a list of specific email addresses to be blacklisted. E.g. mail@example.com.</div>
            </div>

            <div class="mb-3">
              <label class="form-label" for="email-rules-exception-emails">Exception Emails</label>
              <input type="text" name="email_rules_exception_emails" id="email-rules-exception-emails" value="{{ old('email_rules_exception_emails', $settings['email_rules_exception_emails']) }}" class="form-control" maxlength="5000">
              <div class="form-hint">These emails will be excluded from the validation rules.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="checkbox" name="email_rules_strict_validation" value="1" class="form-check-input" @checked(old('email_rules_strict_validation', $settings['email_rules_strict_validation']))>
                <span class="form-check-label">Strict Email Validation</span>
              </label>
              <div class="form-hint ms-4">Perform RFC-like email validation with strict rules.</div>
            </div>

            <div class="mb-3">
              <label class="form-check">
                <input type="checkbox" name="email_rules_dns_check_validation" value="1" class="form-check-input" @checked(old('email_rules_dns_check_validation', $settings['email_rules_dns_check_validation']))>
                <span class="form-check-label">DNS Check Validation</span>
              </label>
              <div class="form-hint ms-4">Check if there are DNS records indicating the server accepts emails.</div>
            </div>

            <div class="mb-0">
              <label class="form-check">
                <input type="checkbox" name="email_rules_spoofing_detection" value="1" class="form-check-input" @checked(old('email_rules_spoofing_detection', $settings['email_rules_spoofing_detection']))>
                <span class="form-check-label">Spoofing Detection</span>
              </label>
              <div class="form-hint ms-4">Detect potential email spoofing attempts.</div>
            </div>
          </div>
        </div>

        <div class="mt-4">
          <button type="submit" class="btn btn-primary btn-lg">
            @include('core/base::admin.partials.icon', ['name' => 'save'])
            <span class="ms-2">Save settings</span>
          </button>
        </div>
      </div>
    </div>
  </form>
@endsection
