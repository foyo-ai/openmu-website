@extends('layouts.app')

@section('title', __('ranking.title'))
@section('meta_description', __('ranking.subtitle'))

@php
    // Names of the logged-in player's characters (lowercased) for highlighting,
    // and their rows ranked outside the visible top list.
    $mineNames   = collect($mine)->pluck('name')->map(fn ($n) => mb_strtolower((string) $n))->all();
    $mineOutside = collect($mine)->filter(fn ($r) => ($r['rank'] ?? 0) > $top)->values();
@endphp

@section('content')
    <style>
        .mu-rank-you { background: rgba(212, 175, 55, 0.10); }
        .mu-rank-you td { box-shadow: inset 3px 0 0 transparent; }
        .mu-rank-you td:first-child { box-shadow: inset 3px 0 0 #d4af37; }
        .mu-rank-sep td { border-top: 2px solid rgba(212, 175, 55, 0.35); letter-spacing: 0.08em; padding-top: 0.6rem; }
    </style>
    <div class="container py-5">
        <h1 class="mu-section-title mb-2">{{ __('ranking.title') }}</h1>
        <p class="text-muted mb-1">{{ __('ranking.subtitle') }} <span class="badge bg-secondary align-middle">{{ __('ranking.top10') }}</span></p>
        @if($generatedAt)
            <p class="text-muted small">
                <i class="fa-regular fa-clock"></i>
                {{ __('ranking.updated_at') }}
                <strong>{{ $generatedAt->copy()->setTimezone(config('server.timezone'))->format('H:i:s') }}</strong>
                ({{ __('clock.server') }}) · {{ __('ranking.updates_note') }}
            </p>
        @endif

        <ul class="nav nav-pills gap-2 my-4">
            @foreach(['players', 'killers', 'guilds'] as $t)
                <li class="nav-item">
                    <a class="nav-link {{ $tab === $t ? 'active' : '' }}"
                       href="{{ route('rankings.index', ['tab' => $t]) }}">{{ __('ranking.tab_' . $t) }}</a>
                </li>
            @endforeach
        </ul>

        <div class="card p-0">
            <div class="table-responsive">
                <table class="table table-hover mu-rank-table align-middle mb-0">
                    @if($tab === 'players')
                        <thead><tr>
                            <th class="ps-3">{{ __('ranking.rank') }}</th><th>{{ __('ranking.name') }}</th>
                            <th>{{ __('ranking.class') }}</th><th class="text-end">{{ __('ranking.resets') }}</th>
                            <th class="text-end pe-3">{{ __('ranking.level') }}</th>
                        </tr></thead>
                        <tbody>
                        @forelse($players as $row)
                            <tr @class(['mu-rank-you' => in_array(mb_strtolower((string) $row['name']), $mineNames, true)])>
                                <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                <td class="fw-semibold">{{ $row['name'] }}@if(in_array(mb_strtolower((string) $row['name']), $mineNames, true))<span class="badge bg-warning text-dark ms-2">{{ __('ranking.you') }}</span>@endif</td>
                                <td class="text-muted">{{ $row['className'] }}</td>
                                <td class="text-end">{{ number_format($row['resets']) }}</td>
                                <td class="text-end pe-3">{{ number_format($row['level']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('ranking.empty') }}</td></tr>
                        @endforelse
                        @if($mineOutside->isNotEmpty())
                            <tr class="mu-rank-sep"><td colspan="5" class="ps-3 text-muted small text-uppercase">{{ __('ranking.your_rank') }}</td></tr>
                            @foreach($mineOutside as $row)
                                <tr class="mu-rank-you">
                                    <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                    <td class="fw-semibold">{{ $row['name'] }}<span class="badge bg-warning text-dark ms-2">{{ __('ranking.you') }}</span></td>
                                    <td class="text-muted">{{ $row['className'] }}</td>
                                    <td class="text-end">{{ number_format($row['resets']) }}</td>
                                    <td class="text-end pe-3">{{ number_format($row['level']) }}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    @elseif($tab === 'killers')
                        <thead><tr>
                            <th class="ps-3">{{ __('ranking.rank') }}</th><th>{{ __('ranking.name') }}</th>
                            <th>{{ __('ranking.class') }}</th><th class="text-end pe-3">{{ __('ranking.kills') }}</th>
                        </tr></thead>
                        <tbody>
                        @forelse($killers as $row)
                            <tr @class(['mu-rank-you' => in_array(mb_strtolower((string) $row['name']), $mineNames, true)])>
                                <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                <td class="fw-semibold">{{ $row['name'] }}@if(in_array(mb_strtolower((string) $row['name']), $mineNames, true))<span class="badge bg-warning text-dark ms-2">{{ __('ranking.you') }}</span>@endif</td>
                                <td class="text-muted">{{ $row['className'] }}</td>
                                <td class="text-end pe-3">{{ number_format($row['kills']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ranking.empty') }}</td></tr>
                        @endforelse
                        @if($mineOutside->isNotEmpty())
                            <tr class="mu-rank-sep"><td colspan="4" class="ps-3 text-muted small text-uppercase">{{ __('ranking.your_rank') }}</td></tr>
                            @foreach($mineOutside as $row)
                                <tr class="mu-rank-you">
                                    <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                    <td class="fw-semibold">{{ $row['name'] }}<span class="badge bg-warning text-dark ms-2">{{ __('ranking.you') }}</span></td>
                                    <td class="text-muted">{{ $row['className'] }}</td>
                                    <td class="text-end pe-3">{{ number_format($row['kills']) }}</td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    @else
                        <thead><tr>
                            <th class="ps-3">{{ __('ranking.rank') }}</th><th>{{ __('ranking.guild') }}</th>
                            <th class="text-end">{{ __('ranking.members') }}</th><th class="text-end pe-3">{{ __('ranking.score') }}</th>
                        </tr></thead>
                        <tbody>
                        @forelse($guilds as $row)
                            <tr>
                                <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td class="text-end">{{ number_format($row['members']) }}</td>
                                <td class="text-end pe-3">{{ number_format($row['score']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ranking.empty') }}</td></tr>
                        @endforelse
                        </tbody>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
