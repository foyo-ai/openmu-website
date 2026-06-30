@extends('layouts.app')

@section('title', $character['name'])

@section('content')
    <div class="container py-5" style="max-width: 820px">
        <a href="{{ route('character.index') }}" class="small text-muted">← {{ __('character.back') }}</a>
        <h1 class="mu-section-title mt-3 mb-4">
            {{ $character['name'] }}
            @if($character['online'])
                <span class="badge bg-success align-middle ms-2" style="font-size:.5em">● ONLINE</span>
            @endif
        </h1>

        <div class="card p-4 mb-4">
            <div class="row g-3 text-center">
                @foreach([
                    'class'  => $character['className'],
                    'level'  => number_format((int) $character['level']),
                    'resets' => number_format((int) $character['resets']),
                    'points' => (int) $character['points'],
                    'master_points' => (int) $character['masterPoints'],
                    'kills'  => (int) $character['kills'],
                ] as $key => $val)
                    <div class="col-6 col-md-4">
                        <div class="mu-stat">
                            <div class="mu-stat-value">{{ $val }}</div>
                            <div class="mu-stat-label">{{ __('character.' . $key) }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <p class="text-muted small"><i class="fa-solid fa-circle-info me-1"></i>{{ __('character.offline_note') }}</p>

        <div class="card p-4">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('character-points.edit', $character['id']) }}" class="btn btn-primary">
                    <i class="fa-solid fa-plus me-1"></i>{{ __('character.add_points') }}
                </a>
                <button class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#renameModal">
                    <i class="fa-solid fa-pen me-1"></i>{{ __('character.rename') }}
                </button>
                <form method="POST" action="{{ route('character.reset', $character['id']) }}"
                      onsubmit="return confirm('{{ __('character.reset_confirm') }}')">
                    @csrf
                    <button class="btn btn-outline-light"><i class="fa-solid fa-rotate me-1"></i>{{ __('character.reset') }}</button>
                </form>
                <form method="POST" action="{{ route('character.clearpk', $character['id']) }}"
                      onsubmit="return confirm('{{ __('character.clearpk_confirm') }}')">
                    @csrf
                    <button class="btn btn-outline-light"><i class="fa-solid fa-skull me-1"></i>{{ __('character.clear_pk') }}</button>
                </form>
                <form method="POST" action="{{ route('character.unstick', $character['id']) }}"
                      onsubmit="return confirm('{{ __('character.unstick_confirm') }}')">
                    @csrf
                    <button class="btn btn-outline-light"><i class="fa-solid fa-house me-1"></i>{{ __('character.unstick') }}</button>
                </form>
                <button class="btn btn-outline-danger ms-auto" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fa-solid fa-trash me-1"></i>{{ __('character.delete') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Rename modal --}}
    <div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('character.rename', $character['id']) }}" class="modal-content">
                @csrf @method('PATCH')
                <div class="modal-header"><h5 class="modal-title">{{ __('character.rename') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">{{ __('character.rename_label') }}</label>
                    <input type="text" name="name" class="form-control" maxlength="10" pattern="[A-Za-z0-9]+" required
                           value="{{ $character['name'] }}">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">{{ __('character.cancel') }}</button>
                    <button class="btn btn-primary">{{ __('character.save') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete modal --}}
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('character.destroy', $character['id']) }}" class="modal-content">
                @csrf @method('DELETE')
                <div class="modal-header"><h5 class="modal-title">{{ __('character.delete_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="text-muted">{{ __('character.delete_warning') }}</p>
                    <label class="form-label">{{ __('character.security_code') }}</label>
                    <input type="password" name="security_code" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">{{ __('character.cancel') }}</button>
                    <button class="btn btn-danger">{{ __('character.delete') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
