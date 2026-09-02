@extends('core/base::admin.layouts.master')

@section('breadcrumbs')
  <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
  <li class="breadcrumb-item active" aria-current="page">Settings</li>
@endsection

@section('content')
    <div class="row row-cards">
        @foreach ($sections as $section)
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">{{ $section['title'] }}</h2>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach ($section['items'] as $item)
                            <div class="col-12 col-md-6 col-xl-4">
                                <a href="{{ $item['url'] }}" class="text-reset text-decoration-none">
                                    <div class="d-flex align-items-start gap-3">
                                        <span class="avatar avatar-lg bg-secondary-lt text-secondary flex-shrink-0">
                                            @include('core/base::admin.partials.icon', ['name' => $item['icon']])
                                        </span>
                                        <span class="d-block">
                                            <span class="d-block h3 text-primary mb-1">{{ $item['title'] }}</span>
                                            <span class="d-block text-secondary">{{ $item['description'] }}</span>
                                        </span>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
@endsection
