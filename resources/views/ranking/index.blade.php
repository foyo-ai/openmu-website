@extends('layouts.app')

@section('title', __('ranking.title'))
@section('meta_description', __('ranking.subtitle'))

@section('content')
    <div class="container py-5">
        <h1 class="mu-section-title mb-2">{{ __('ranking.title') }}</h1>
        <p class="text-muted">{{ __('ranking.subtitle') }}</p>

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
                            <tr>
                                <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td class="text-muted">{{ $row['className'] }}</td>
                                <td class="text-end">{{ number_format($row['resets']) }}</td>
                                <td class="text-end pe-3">{{ number_format($row['level']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">{{ __('ranking.empty') }}</td></tr>
                        @endforelse
                        </tbody>
                    @elseif($tab === 'killers')
                        <thead><tr>
                            <th class="ps-3">{{ __('ranking.rank') }}</th><th>{{ __('ranking.name') }}</th>
                            <th>{{ __('ranking.class') }}</th><th class="text-end pe-3">{{ __('ranking.kills') }}</th>
                        </tr></thead>
                        <tbody>
                        @forelse($killers as $row)
                            <tr>
                                <td class="ps-3 mu-rank-pos">{{ $row['rank'] }}</td>
                                <td class="fw-semibold">{{ $row['name'] }}</td>
                                <td class="text-muted">{{ $row['className'] }}</td>
                                <td class="text-end pe-3">{{ number_format($row['kills']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-4">{{ __('ranking.empty') }}</td></tr>
                        @endforelse
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
