@extends('layouts.app')

@section('title', __('account.title'))

@section('content')
    <div class="container py-5" style="max-width: 760px">
        <h1 class="mu-section-title mb-4">{{ __('account.title') }}</h1>

        <div class="card p-4 mb-4">
            <div class="row">
                <div class="col-sm-4 text-muted">{{ __('account.login_name') }}</div>
                <div class="col-sm-8 fw-semibold">{{ auth()->user()->LoginName }}</div>
            </div>
        </div>

        {{-- Change password --}}
        <div class="card p-4 mb-4">
            <h2 class="h5 mb-3">{{ __('account.password_section') }}</h2>
            <form method="POST" action="{{ route('account.password') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label">{{ __('account.new_password') }}</label>
                    <input type="password" name="password" class="form-control @error('password', 'password') is-invalid @enderror" required>
                    @error('password', 'password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('account.confirm_password') }}</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('account.current_password') }}</label>
                    <input type="password" name="current_password" class="form-control @error('current_password', 'password') is-invalid @enderror" required>
                    @error('current_password', 'password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary">{{ __('account.save') }}</button>
            </form>
        </div>

        {{-- Change email --}}
        <div class="card p-4 mb-4">
            <h2 class="h5 mb-3">{{ __('account.email_section') }}</h2>
            <form method="POST" action="{{ route('account.email') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label">{{ __('account.email') }}</label>
                    <input type="email" name="EMail" value="{{ old('EMail', auth()->user()->EMail) }}"
                           class="form-control @error('EMail', 'email') is-invalid @enderror" required>
                    @error('EMail', 'email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('account.current_password') }}</label>
                    <input type="password" name="current_password" class="form-control @error('current_password', 'email') is-invalid @enderror" required>
                    @error('current_password', 'email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary">{{ __('account.save') }}</button>
            </form>
        </div>

        {{-- Change security code --}}
        <div class="card p-4 mb-4">
            <h2 class="h5 mb-3">{{ __('account.security_section') }}</h2>
            <form method="POST" action="{{ route('account.security') }}">
                @csrf @method('PUT')
                <div class="mb-3">
                    <label class="form-label">{{ __('account.security_code') }}</label>
                    <input type="text" name="SecurityCode" inputmode="numeric" maxlength="6" minlength="6"
                           class="form-control @error('SecurityCode', 'security') is-invalid @enderror" required>
                    @error('SecurityCode', 'security')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">{{ __('account.current_password') }}</label>
                    <input type="password" name="current_password" class="form-control @error('current_password', 'security') is-invalid @enderror" required>
                    @error('current_password', 'security')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button class="btn btn-primary">{{ __('account.save') }}</button>
            </form>
        </div>
    </div>
@endsection
