@extends('layouts.app')

@section('title', __('character.list_title'))

@section('content')
    <div class="container py-5">
        <h1 class="mu-section-title mb-3">{{ __('character.list_title') }}</h1>

        @if($characters->isEmpty())
            <div class="card p-4 text-muted">{{ __('character.empty') }}</div>
        @else
            <div class="card p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr>
                            <th class="ps-3">{{ __('character.name') }}</th>
                            <th>{{ __('character.class') }}</th>
                            <th class="text-end">{{ __('character.resets') }}</th>
                            <th class="text-end">{{ __('character.level') }}</th>
                            <th class="text-end">{{ __('character.points') }}</th>
                            <th class="text-end pe-3">{{ __('character.actions') }}</th>
                        </tr></thead>
                        <tbody>
                        @foreach($characters as $character)
                            <tr>
                                <td class="ps-3 fw-semibold">
                                    {{ $character['name'] }}
                                    @if($character['online'])
                                        <span class="mu-online-dot ms-1" title="online"></span>
                                    @endif
                                </td>
                                <td class="text-muted">{{ $character['className'] }}</td>
                                <td class="text-end">{{ number_format((int) $character['resets']) }}</td>
                                <td class="text-end">{{ number_format((int) $character['level']) }}</td>
                                <td class="text-end">{{ (int) $character['points'] }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('character.show', $character['id']) }}" class="btn btn-sm btn-outline-light">
                                        {{ __('character.view') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
