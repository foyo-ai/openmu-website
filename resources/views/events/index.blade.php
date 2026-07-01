@extends('layouts.app')

@section('title', __('events.title'))
@section('meta_description', __('events.subtitle'))

@section('content')
    <div class="container py-5"
         data-events-now="{{ $serverNowMs }}"
         data-txt-live="{{ __('events.live') }}"
         data-txt-starts="{{ __('events.starts_in') }}"
         data-txt-ends="{{ __('events.ends_in') }}">
        <h1 class="mu-section-title mb-1">{{ __('events.title') }}</h1>
        <p class="text-muted">{{ __('events.subtitle') }}</p>

        @if(empty($events))
            <p class="text-muted">{{ __('events.empty') }}</p>
        @else
            <div class="row g-4">
                @foreach($events as $ev)
                    <div class="col-md-6 col-lg-4">
                        <div class="card mu-feature h-100 p-0 overflow-hidden event-card"
                             data-times="{{ implode(',', $ev['times']) }}" data-duration="{{ $ev['duration'] }}">
                            @if($ev['image'])
                                <img src="{{ $ev['image'] }}" class="card-img-top" alt="{{ $ev['name'] }}" style="height:150px;object-fit:cover">
                            @endif
                            <div class="p-4">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="mu-feature-icon" style="font-size:1.35rem;margin:0"><i class="fa-solid fa-{{ $ev['icon'] }}"></i></span>
                                    <h3 class="h5 mb-0">{{ $ev['name'] }}</h3>
                                </div>

                                <div class="event-status mb-3"></div>

                                <div class="small text-muted">
                                    <div><i class="fa-regular fa-clock me-1"></i>{{ __('events.duration') }}: {{ $ev['duration'] }} {{ __('events.minutes') }}</div>
                                    <div class="mt-1"><i class="fa-solid fa-calendar-day me-1"></i>{{ __('events.times') }}: <span class="event-times">{{ implode(' · ', $ev['times']) }}</span> <span class="event-tz text-uppercase"></span></div>
                                </div>

                                @if($ev['reward'] || $ev['rate'])
                                    <hr class="my-3">
                                    @if($ev['reward'])
                                        <div class="small"><i class="fa-solid fa-gift me-1 text-warning"></i>{{ __('events.reward') }}: <strong>{{ $ev['reward'] }}</strong></div>
                                    @endif
                                    @if($ev['rate'])
                                        <div class="small mt-1"><i class="fa-solid fa-dice me-1 text-warning"></i>{{ __('events.rate') }}: {{ $ev['rate'] }}</div>
                                    @endif
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
    (function () {
        var root = document.querySelector('[data-events-now]');
        if (!root) return;

        var anchor = parseInt(root.getAttribute('data-events-now'), 10);   // server UTC epoch (ms)
        var pageLoad = Date.now();
        var LIVE = root.getAttribute('data-txt-live');
        var STARTS = root.getAttribute('data-txt-starts');
        var ENDS = root.getAttribute('data-txt-ends');
        var cards = Array.prototype.slice.call(document.querySelectorAll('.event-card'));

        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function fmt(ms) {
            var s = Math.max(0, Math.floor(ms / 1000));
            var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), ss = s % 60;
            return (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(ss);
        }

        // Render each event's daily start times in the visitor's chosen timezone (the UTC
        // "HH:mm" from the game, converted via the shared MU_TZ helper). Re-runs on tz change.
        function renderTimes() {
            if (!window.MU_TZ) return;
            var tz = window.MU_TZ.get();
            var now = anchor + (Date.now() - pageLoad);
            var d = new Date(now);
            var offset = window.MU_TZ.offset(now, tz);
            cards.forEach(function (card) {
                var times = (card.getAttribute('data-times') || '').split(',').filter(Boolean);
                var el = card.querySelector('.event-times');
                var tzEl = card.querySelector('.event-tz');
                if (!el || !times.length) return;
                var local = times.map(function (t) {
                    var p = t.split(':');
                    var ms = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), parseInt(p[0], 10), parseInt(p[1], 10), 0);
                    return window.MU_TZ.time(ms, tz).slice(0, 5);   // HH:mm in the chosen tz
                }).sort();
                el.textContent = local.join(' · ');
                if (tzEl) tzEl.textContent = offset ? '(' + offset + ')' : '';
            });
        }

        function tick() {
            var now = anchor + (Date.now() - pageLoad);     // current server time (UTC ms)
            var d = new Date(now);
            cards.forEach(function (card) {
                var times = (card.getAttribute('data-times') || '').split(',').filter(Boolean);
                var dur = (parseInt(card.getAttribute('data-duration'), 10) || 0) * 60000;
                var statusEl = card.querySelector('.event-status');
                if (!times.length || !statusEl) return;

                var running = null, nextStart = null;
                for (var day = 0; day <= 1; day++) {
                    times.forEach(function (t) {
                        var p = t.split(':');
                        var start = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate() + day,
                                             parseInt(p[0], 10), parseInt(p[1], 10), 0);
                        var end = start + dur;
                        if (now >= start && now < end && (running === null || end < running)) running = end;
                        if (start > now && (nextStart === null || start < nextStart)) nextStart = start;
                    });
                }

                if (running !== null) {
                    statusEl.innerHTML = '<span class="badge bg-success">● ' + LIVE + '</span>' +
                        ' <span class="text-muted small ms-1">' + ENDS + ' ' + fmt(running - now) + '</span>';
                } else if (nextStart !== null) {
                    statusEl.innerHTML = '<span class="badge" style="background:rgba(233,196,106,.15);color:#e9c46a;border:1px solid rgba(233,196,106,.3)">' +
                        STARTS + ' ' + fmt(nextStart - now) + '</span>';
                }
            });
        }

        renderTimes();
        document.addEventListener('mu-tz-change', renderTimes);
        tick();
        setInterval(tick, 1000);
    })();
    </script>
    @endpush
@endsection
