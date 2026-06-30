@extends('layouts.app')

@section('title', $character['name'])

@section('content')
    <div class="container py-5" style="max-width: 640px">
        <a href="{{ route('character.show', $character['id']) }}" class="small text-muted">← {{ $character['name'] }}</a>
        <h1 class="mu-section-title mt-3 mb-4">{{ __('character.add_points') }}</h1>

        <div class="card p-4">
            <div class="d-flex justify-content-between mb-3">
                <span class="text-muted">{{ __('character.points') }}</span>
                <span class="fw-semibold text-gradient-gold">{{ (int) $character['points'] }}</span>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('character-points.update', $character['id']) }}">
                @csrf @method('PATCH')
                @php($isLord = in_array($character['className'], ['Dark Lord', 'Lord Emperor'], true))
                @foreach(['strength', 'agility', 'vitality', 'energy'] as $stat)
                    <div class="row mb-3 align-items-center">
                        <label class="col-4 form-label mb-0">{{ ucfirst($stat) }}</label>
                        <div class="col-8">
                            <input type="number" min="0" name="{{ $stat }}" value="{{ old($stat, 0) }}" class="form-control">
                        </div>
                    </div>
                @endforeach
                @if($isLord)
                    <div class="row mb-3 align-items-center">
                        <label class="col-4 form-label mb-0">Leadership</label>
                        <div class="col-8">
                            <input type="number" min="0" name="leadership" value="{{ old('leadership', 0) }}" class="form-control">
                        </div>
                    </div>
                @endif

                <button class="btn btn-primary mt-2">{{ __('character.save') }}</button>
            </form>
        </div>
    </div>
@endsection
