@extends('layouts.app')

@section('title', __('account.title'))

@section('content')
    <div class="container py-5" style="max-width: 720px">
        <h1 class="mu-section-title mb-4">{{ __('account.title') }}</h1>
        <div class="card p-4">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-screwdriver-wrench text-muted fs-3"></i>
                <p class="mb-0 text-muted">{{ __('account.coming_soon') }}</p>
            </div>
        </div>
    </div>
@endsection
