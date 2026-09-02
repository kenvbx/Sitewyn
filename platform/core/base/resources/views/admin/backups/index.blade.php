@extends('core/base::admin.layouts.master')

@section('title', 'Backups - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Backups')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item"><a href="{{ route('admin.system') }}">Platform Administration</a></li>
  <li class="breadcrumb-item active" aria-current="page">Backups</li>
@endsection

@section('content')
  @php
    $humanSize = function (int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1).' KB';
        }

        return $bytes.' B';
    };
  @endphp

  <div class="alert alert-warning mb-4" role="alert">
    <div class="d-flex">
      <div>@include('core/base::admin.partials.icon', ['name' => 'alert-circle'])</div>
      <div>
        <div>- This simple backup feature is ideal for website having less than 1GB of data. A quick and easy way to create backups.</div>
        <div class="mt-3">- For larger websites with over 1GB of images or files, consider using the backup features provided by your hosting or VPS provider.</div>
        <div class="mt-3">- To back up your database, the PHP function <code>proc_open()</code> or <code>system()</code> must be enabled. Contact your hosting provider to enable these functions if needed.</div>
        <div class="mt-3">- It is not a full backup, only uploaded files and database are included.</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header justify-content-end">
      <form method="POST" action="{{ route('admin.system.backups.create', [], false) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary" aria-label="Generate backup">Generate backup</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-vcenter card-table" id="admin-backups-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Size</th>
            <th>Created at</th>
            <th class="w-1 text-end">Operations</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($backups as $backup)
            <tr>
              <td class="fw-medium">{{ $backup['name'] }}</td>
              <td class="text-secondary">Backup current data for demo purposes</td>
              <td class="text-secondary">{{ $humanSize($backup['sizeBytes']) }}</td>
              <td class="text-secondary">{{ $backup['createdAt'] }}</td>
              <td class="d-flex gap-1 justify-content-end">
                <a href="{{ route('admin.system.backups.download-database', $backup['name'], false) }}"
                   class="btn btn-icon btn-sm btn-success"
                   title="Download database backup"
                   aria-label="Download database backup {{ $backup['name'] }}">
                  @include('core/base::admin.partials.icon', ['name' => 'database'])
                </a>
                <a href="{{ route('admin.system.backups.download-uploads', $backup['name'], false) }}"
                   class="btn btn-icon btn-sm btn-primary"
                   title="Download backup of 'uploads' folder"
                   aria-label="Download backup of uploads folder {{ $backup['name'] }}">
                  @include('core/base::admin.partials.icon', ['name' => 'download'])
                </a>
                <button type="button" class="btn btn-icon btn-sm btn-info"
                        data-bs-toggle="modal" data-bs-target="#backup-restore-{{ $loop->index }}"
                        title="Restore this backup"
                        aria-label="Restore this backup {{ $backup['name'] }}">
                  @include('core/base::admin.partials.icon', ['name' => 'reload'])
                </button>
                <button type="button" class="btn btn-icon btn-sm btn-danger"
                        data-bs-toggle="modal" data-bs-target="#backup-delete-{{ $loop->index }}"
                        title="Delete"
                        aria-label="Delete {{ $backup['name'] }}">
                  @include('core/base::admin.partials.icon', ['name' => 'trash'])
                </button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-secondary py-5">No backups yet. Generate your first backup above.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  @foreach ($backups as $backup)
    <x-admin-modal id="backup-restore-{{ $loop->index }}" title="Restore backup">
      <p>Restore <strong>{{ $backup['name'] }}</strong>?</p>
      <p class="text-danger">
        Restore will <strong>delete all current database data and media files</strong> and replace
        them with the contents of this backup. This cannot be undone — make sure the current site
        state is not needed, or create a fresh backup first.
      </p>
      <p class="text-secondary mb-0">Database structure is not changed: it always comes from the current migrations.</p>

      <form method="POST" action="{{ route('admin.system.backups.restore', $backup['name'], false) }}" id="backup-restore-form-{{ $loop->index }}">
        @csrf
      </form>

      <x-slot:footer>
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="backup-restore-form-{{ $loop->index }}">Restore backup</button>
      </x-slot:footer>
    </x-admin-modal>

    <x-admin-modal id="backup-delete-{{ $loop->index }}" title="Delete backup">
      <p>Delete <strong>{{ $backup['name'] }}</strong>?</p>
      <p class="text-secondary mb-0">The archive file is removed permanently. Your live data is not touched.</p>

      <form method="POST" action="{{ route('admin.system.backups.delete', $backup['name'], false) }}" id="backup-delete-form-{{ $loop->index }}">
        @csrf
      </form>

      <x-slot:footer>
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="backup-delete-form-{{ $loop->index }}">Delete backup</button>
      </x-slot:footer>
    </x-admin-modal>
  @endforeach
@endsection
