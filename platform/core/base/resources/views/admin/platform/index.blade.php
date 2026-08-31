@extends('core/base::admin.layouts.master')

@section('title', 'Platform Administration - ' . config('app.name', 'Sitewyn') . ' Admin')

@section('pretitle', 'System')

@section('page-title', 'Platform Administration')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Platform Administration</li>
@endsection

@push('styles')
  <style>
    /* --- Platform Administration hub (Botble-style tool cards) --- */
    .platform-card {
      display: flex;
      align-items: flex-start;
      gap: .75rem;
      height: 100%;
      padding: 1rem 1.25rem;
      border: 1px solid var(--tblr-border-color);
      border-radius: .5rem;
      background-color: var(--tblr-bg-surface);
      color: inherit;
      text-decoration: none;
      cursor: pointer;
      transition: border-color .15s ease-in-out, background-color .15s ease-in-out;
    }

    .platform-card:hover,
    .platform-card:focus {
      border-color: rgba(37, 99, 235, .4);
      background-color: rgba(37, 99, 235, .04);
      color: inherit;
      text-decoration: none;
    }

    .platform-card-icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex: 0 0 auto;
      width: 2.5rem;
      height: 2.5rem;
      border-radius: .5rem;
      background-color: rgba(37, 99, 235, .08);
      color: #2563eb;
    }

    .platform-card-icon .icon {
      width: 1.25rem;
      height: 1.25rem;
    }

    .platform-card-body {
      display: flex;
      flex-direction: column;
      gap: .125rem;
      min-width: 0;
    }

    .platform-card-title {
      color: #2563eb;
      font-weight: 500;
    }

    .platform-card-description {
      color: var(--tblr-secondary);
      font-size: .8125rem;
    }
  </style>
@endpush

@section('content')
  @if ($cards->isEmpty())
    <div class="card">
      <div class="empty">
        <div class="empty-icon">@include('core/base::admin.partials.icon', ['name' => 'shield'])</div>
        <p class="empty-title">No administration tools available for your account.</p>
        <p class="empty-subtitle text-secondary">Ask a super admin to grant your role the permissions you need.</p>
      </div>
    </div>
  @else
    <div class="row g-3">
      @foreach ($cards as $card)
        <div class="col-sm-6 col-lg-4">
          <a href="{{ $card['url'] }}" class="platform-card" data-platform-card="{{ \Illuminate\Support\Str::slug($card['title']) }}">
            <span class="platform-card-icon" aria-hidden="true">
              @include('core/base::admin.partials.icon', ['name' => $card['icon']])
            </span>
            <span class="platform-card-body">
              <span class="platform-card-title">{{ $card['title'] }}</span>
              <span class="platform-card-description">{{ $card['description'] }}</span>
            </span>
          </a>
        </div>
      @endforeach
    </div>
  @endif
@endsection
