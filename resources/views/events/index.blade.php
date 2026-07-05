@extends('layouts.app')

@section('title', __('events.title'))
@section('meta_description', __('events.subtitle'))

@section('content')
    <div class="container py-5"
         data-events-now="{{ $serverNowMs }}"
         data-txt-live="{{ __('events.live') }}"
         data-txt-soon="{{ __('events.soon') }}"
         data-txt-opens="{{ __('events.opens_in') }}"
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
                             data-duration="{{ $ev['duration'] }}">
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
                                </div>

                                <details class="event-schedule mt-2">
                                    <summary class="small">
                                        <i class="fa-solid fa-calendar-day me-1"></i>{{ __('events.schedule') }}
                                        <span class="text-muted">({{ count($ev['times']) }} {{ __('events.slots') }})</span>
                                        <span class="event-tz text-uppercase text-muted"></span>
                                    </summary>
                                    <div class="event-chip-grid mt-2">
                                        @foreach($ev['times'] as $t)
                                            <span class="event-chip" data-utc="{{ $t }}">{{ $t }}</span>
                                        @endforeach
                                    </div>
                                </details>

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

        var pageLoad = Date.now();
        var LIVE = root.getAttribute('data-txt-live');
        var SOON = root.getAttribute('data-txt-soon');
        var OPENS = root.getAttribute('data-txt-opens');
        var ENDS = root.getAttribute('data-txt-ends');
        var SOON_MS = 3 * 60000;   // "starting soon" threshold: 3 minutes before the start

        // Read the current server time fresh each run (attribute + elapsed since load) so a
        // manual anchor change also re-syncs, and re-query cards so any late DOM change is seen.
        function serverNow() {
            return parseInt(root.getAttribute('data-events-now'), 10) + (Date.now() - pageLoad);
        }
        function pad(n) { return (n < 10 ? '0' : '') + n; }
        function fmt(ms) {
            var s = Math.max(0, Math.floor(ms / 1000));
            var h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), ss = s % 60;
            return (h > 0 ? pad(h) + ':' : '') + pad(m) + ':' + pad(ss);
        }

        // Convert each schedule chip's UTC "HH:mm" to the visitor's timezone label. Runs on
        // load and whenever the timezone changes.
        function renderTimes() {
            if (!window.MU_TZ) return;
            var tz = window.MU_TZ.get();
            var now = serverNow();
            var d = new Date(now);
            var offset = window.MU_TZ.offset(now, tz);
            document.querySelectorAll('.event-card').forEach(function (card) {
                var tzEl = card.querySelector('.event-tz');
                if (tzEl) tzEl.textContent = offset ? ' (' + offset + ')' : '';
                card.querySelectorAll('.event-chip').forEach(function (chip) {
                    var p = (chip.getAttribute('data-utc') || '').split(':');
                    if (p.length < 2) return;
                    var ms = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(), parseInt(p[0], 10), parseInt(p[1], 10), 0);
                    chip.textContent = window.MU_TZ.time(ms, tz).slice(0, 5);   // HH:mm in the chosen tz
                });
            });
        }

        function tick() {
            var now = serverNow();
            var d = new Date(now);
            document.querySelectorAll('.event-card').forEach(function (card) {
                var chips = Array.prototype.slice.call(card.querySelectorAll('.event-chip'));
                var dur = (parseInt(card.getAttribute('data-duration'), 10) || 0) * 60000;
                var statusEl = card.querySelector('.event-status');
                if (!chips.length || !statusEl) return;

                var runEnd = null, nextStart = null, nextChip = null;
                chips.forEach(function (chip) {
                    chip.classList.remove('is-next', 'is-past');
                    var p = (chip.getAttribute('data-utc') || '').split(':');
                    if (p.length < 2) return;
                    // Consider today's and tomorrow's occurrence of this daily slot.
                    for (var day = 0; day <= 1; day++) {
                        var start = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate() + day,
                                             parseInt(p[0], 10), parseInt(p[1], 10), 0);
                        var end = start + dur;
                        if (now >= start && now < end && (runEnd === null || end < runEnd)) runEnd = end;
                        if (start > now && (nextStart === null || start < nextStart)) { nextStart = start; nextChip = chip; }
                    }
                    // Dim slots already passed earlier today.
                    var todayStart = Date.UTC(d.getUTCFullYear(), d.getUTCMonth(), d.getUTCDate(),
                                              parseInt(p[0], 10), parseInt(p[1], 10), 0);
                    if (todayStart + dur <= now) chip.classList.add('is-past');
                });
                if (nextChip) nextChip.classList.add('is-next');

                var html;
                if (runEnd !== null) {
                    // Running now: informational gold badge (entry may already be closed).
                    html = '<span class="badge mu-badge-live">' + LIVE + '</span>' +
                        ' <span class="text-muted small ms-1">' + ENDS + ' ' + fmt(runEnd - now) + '</span>';
                } else if (nextStart !== null && nextStart - now <= SOON_MS) {
                    // Under 3 minutes to the next start: green "get ready to join".
                    html = '<span class="badge mu-badge-soon">● ' + SOON + '</span>' +
                        ' <span class="text-muted small ms-1">' + fmt(nextStart - now) + '</span>';
                } else if (nextStart !== null) {
                    // Further out: neutral countdown.
                    html = '<span class="text-muted small">' + OPENS + ' ' + fmt(nextStart - now) + '</span>';
                } else {
                    html = '';
                }
                statusEl.innerHTML = html;
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
