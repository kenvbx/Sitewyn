@extends('core/base::admin.layouts.master')

@section('title', 'Backups - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Backups')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
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

  <x-admin-card
    title="Database and media backups"
    subtitle="Each backup is a ZIP archive with a JSON dump of every database table and a mirror of the media files. Restoring replaces ALL current data and media."
  >
    <div class="mb-3">
      <form method="POST" action="{{ route('admin.backups.create', [], false) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-primary" aria-label="Create backup">Create backup</button>
      </form>
    </div>

    <div class="table-responsive">
      <table class="table table-vcenter" id="admin-backups-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Size</th>
            <th>Created at</th>
            <th class="w-1"></th>
          </tr>
        </thead>
        <tbody>
          @forelse ($backups as $backup)
            <tr>
              <td class="fw-medium">{{ $backup['name'] }}</td>
              <td class="text-secondary">{{ $humanSize($backup['sizeBytes']) }}</td>
              <td class="text-secondary">{{ $backup['createdAt'] }}</td>
              <td class="d-flex gap-1 justify-content-end">
                <a href="{{ route('admin.backups.download', $backup['name'], false) }}"
                   class="btn btn-sm btn-outline-primary"
                   aria-label="Download {{ $backup['name'] }}">Download</a>
                <button type="button" class="btn btn-sm btn-outline-warning"
                        data-bs-toggle="modal" data-bs-target="#backup-restore-{{ $loop->index }}"
                        aria-label="Restore {{ $backup['name'] }}">Restore</button>
                <button type="button" class="btn btn-sm btn-outline-danger"
                        data-bs-toggle="modal" data-bs-target="#backup-delete-{{ $loop->index }}"
                        aria-label="Delete {{ $backup['name'] }}">Delete</button>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="4" class="text-center text-secondary py-5">No backups yet. Create your first backup above.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </x-admin-card>

  @foreach ($backups as $backup)
    <x-admin-modal id="backup-restore-{{ $loop->index }}" title="Restore backup">
      <p>Restore <strong>{{ $backup['name'] }}</strong>?</p>
      <p class="text-danger">
        Restore will <strong>delete all current database data and media files</strong> and replace
        them with the contents of this backup. This cannot be undone — make sure the current site
        state is not needed, or create a fresh backup first.
      </p>
      <p class="text-secondary mb-0">Database structure is not changed: it always comes from the current migrations.</p>

      <form method="POST" action="{{ route('admin.backups.restore', $backup['name'], false) }}" id="backup-restore-form-{{ $loop->index }}">
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

      <form method="POST" action="{{ route('admin.backups.delete', $backup['name'], false) }}" id="backup-delete-form-{{ $loop->index }}">
        @csrf
      </form>

      <x-slot:footer>
        <button type="button" class="btn me-auto" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger" form="backup-delete-form-{{ $loop->index }}">Delete backup</button>
      </x-slot:footer>
    </x-admin-modal>
  @endforeach
@endsection
