@extends('core/base::admin.layouts.master')

@section('title', 'Cronjob - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Cronjob</li>
@endsection

@section('content')
    <div class="row mb-5 d-block d-md-flex">
        <div class="col-12 col-md-3">
            <h2>Cronjob</h2>
            <p class="text-muted">Set up automated background tasks to keep your website running smoothly.</p>
        </div>

        <div class="col-12 col-md-9">
            <div class="card">
                <div class="card-body">
                    @if ($configured)
                        <div class="alert alert-success" role="alert">
                            <div class="d-flex gap-1">
                                <div>@include('core/base::admin.partials.icon', ['name' => 'circle-check'])</div>
                                <div class="w-100">
                                    <h4 class="alert-title mb-1">Cronjob is running</h4>
                                    @if ($lastRunAt)
                                        <div class="text-secondary">Last run: {{ $lastRunAt->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning" role="alert">
                            <div class="d-flex gap-1">
                                <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
                                <div class="w-100">
                                    <h4 class="alert-title mb-0">Cronjob is not configured yet</h4>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="card mt-4">
                        <div class="card-header">
                            <h4 class="card-title">
                            What is a Cronjob?
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="mb-3">A cronjob is an automated task that runs in the background on your server. It allows your website to perform important tasks automatically without you having to do anything manually.</p>
                            <div class="text-muted">
                                <strong>Examples:</strong>
                                Send abandoned cart reminders, process scheduled emails, clean up old data, generate reports, and more.
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="card-title">Your Cronjob Command</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Copy this command and add it to your hosting control panel. This command needs to run every minute to keep your automated tasks working.</p>
                            <div class="input-group">
                                <input type="text" class="form-control font-monospace" value="{{ $command }}" readonly aria-label="Cronjob command" data-cronjob-command>
                                <button type="button" class="btn btn-primary fs-3" data-cronjob-copy>
                                    @include('core/base::admin.partials.icon', ['name' => 'copy'])
                                    <span class="ms-2" data-cronjob-copy-label>Copy Command</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="card-title">
                                How to Set Up
                            </h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Select your hosting control panel below and follow the step-by-step instructions:</p>
                            
                            <ul class="nav nav-tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#cpanel-tab" type="button" aria-selected="false" role="tab" tabindex="-1">
                                        cPanel
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#plesk-tab" type="button" aria-selected="true" role="tab">
                                        Plesk
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#directadmin-tab" type="button" aria-selected="false" tabindex="-1" role="tab">
                                        DirectAdmin
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#ssh-tab" type="button" aria-selected="false" tabindex="-1" role="tab">
                                        SSH/Terminal
                                    </button>
                                </li>
                            </ul>
            
                            <div class="tab-content mt-3">
                                <div class="tab-pane fade active show" id="cpanel-tab" role="tabpanel">
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-blue-lt">1</span>
                                                </div>
                                                <div class="col">
                                                    Log in to your <strong>cPanel</strong> account
                                                </div>
                                            </div>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-blue-lt">2</span>
                                                </div>
                                                <div class="col">
                                                    Find and click on <strong>"Cron Jobs"</strong> under the "Advanced" section
                                                </div>
                                            </div>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-blue-lt">3</span>
                                                </div>
                                                <div class="col">
                                                    Under "Add New Cron Job", select <strong>"Once Per Minute (* * * * *)"</strong> from the timing dropdown
                                                </div>
                                            </div>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-blue-lt">4</span>
                                                </div>
                                                <div class="col">
                                                    <strong>Paste the command</strong> you copied above into the "Command" field
                                                </div>
                                            </div>
                                        </div>
                                        <div class="list-group-item">
                                            <div class="row align-items-center">
                                                <div class="col-auto">
                                                    <span class="badge bg-blue-lt">5</span>
                                                </div>
                                                <div class="col">
                                                    Click <strong>"Add New Cron Job"</strong> to save
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="tab-pane fade" id="plesk-tab" role="tabpanel">
                                    <div class="list-group list-group-flush">
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">1</span>
                                                    </div>
                                                    <div class="col">
                                                        Log in to your <strong>Plesk</strong> control panel
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">2</span>
                                                    </div>
                                                    <div class="col">
                                                        Go to <strong>"Scheduled Tasks"</strong> (or "Cron Jobs")
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">3</span>
                                                    </div>
                                                    <div class="col">
                                                        Click <strong>"Add Task"</strong> or <strong>"Schedule a Task"</strong>
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">4</span>
                                                    </div>
                                                    <div class="col">
                                                        Set the schedule to run <strong>every minute</strong> and paste the command
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">5</span>
                                                    </div>
                                                    <div class="col">
                                                        Click <strong>"OK"</strong> or <strong>"Apply"</strong> to save
                                                    </div>
                                                </div>
                                            </div>
                                                                </div>
                                </div>
                                
                                <div class="tab-pane fade" id="directadmin-tab" role="tabpanel">
                                    <div class="list-group list-group-flush">
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">1</span>
                                                    </div>
                                                    <div class="col">
                                                        Log in to your <strong>DirectAdmin</strong> panel
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">2</span>
                                                    </div>
                                                    <div class="col">
                                                        Navigate to <strong>"Advanced Features"</strong> → <strong>"Cron Jobs"</strong>
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">3</span>
                                                    </div>
                                                    <div class="col">
                                                        Click <strong>"Add Cron Job"</strong>
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">4</span>
                                                    </div>
                                                    <div class="col">
                                                        Set all time fields to <strong>*</strong> (every minute) and paste the command
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">5</span>
                                                    </div>
                                                    <div class="col">
                                                        Click <strong>"Add"</strong> to save the cronjob
                                                    </div>
                                                </div>
                                            </div>
                                                                </div>
                                </div>
            
                                
                                <div class="tab-pane fade" id="ssh-tab" role="tabpanel">
                                    <div class="list-group list-group-flush">
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">1</span>
                                                    </div>
                                                    <div class="col">
                                                        Connect to your server via <strong>SSH</strong> using Terminal or PuTTY
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">2</span>
                                                    </div>
                                                    <div class="col">
                                                        Type <code>crontab -e</code> and press Enter to edit the crontab file
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">3</span>
                                                    </div>
                                                    <div class="col">
                                                        Add a new line at the bottom and <strong>paste the command</strong>
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">4</span>
                                                    </div>
                                                    <div class="col">
                                                        Press <strong>Ctrl+X</strong>, then <strong>Y</strong>, then <strong>Enter</strong> to save (for nano editor)
                                                    </div>
                                                </div>
                                            </div>
                                                                        <div class="list-group-item">
                                                <div class="row align-items-center">
                                                    <div class="col-auto">
                                                        <span class="badge bg-blue-lt">5</span>
                                                    </div>
                                                    <div class="col">
                                                        The cronjob is now active and will run every minute
                                                    </div>
                                                </div>
                                            </div>
                                                                </div>
                                </div>
                            </div>
            
                            
                            <div class="border-top mt-4 pt-3">
                                <p class="text-muted mb-0">
                                    @include('core/base::admin.partials.icon', ['name' => 'help-circle'])
                                    Need help? Contact your hosting provider and ask them to set up a cronjob that runs every minute with the command shown above.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
  <script>
    document.querySelectorAll('[data-cronjob-copy]').forEach(function (button) {
      button.addEventListener('click', function () {
        var input = document.querySelector('[data-cronjob-command]')
        var label = button.querySelector('[data-cronjob-copy-label]')
        var originalLabel = label ? label.textContent : ''

        if (!input) {
          return
        }

        navigator.clipboard.writeText(input.value).then(function () {
          if (label) {
            label.textContent = 'Copied'

            window.setTimeout(function () {
              label.textContent = originalLabel
            }, 1500)
          }
        })
      })
    })
  </script>
@endpush
