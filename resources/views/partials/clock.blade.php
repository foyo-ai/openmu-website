@push('head')
<style>
    .mu-clock { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; align-items: center; }
    .mu-clock-item { display: inline-flex; align-items: center; gap: .35rem; }
    .mu-clock-time { font-variant-numeric: tabular-nums; color: #e9c46a; font-weight: 600; }
    .mu-clock-off { opacity: .7; }
    .mu-clock-tz { background: transparent; color: inherit; border: 1px solid rgba(255,255,255,.18);
                   border-radius: .25rem; padding: .05rem .35rem; font-size: .8rem; margin-left: .25rem; }
    .mu-clock-tz option { color: #000; }
</style>
@endpush

@php
    // Common timezones offered in the picker. The visitor's own zone is auto-detected
    // client-side and added if it's not already here.
    $zones = [
        'Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'Asia/Singapore', 'Asia/Manila', 'Asia/Jakarta',
        'Asia/Kuala_Lumpur', 'Asia/Tokyo', 'Asia/Seoul', 'Asia/Shanghai', 'Asia/Kolkata',
        'Asia/Dubai', 'Europe/London', 'Europe/Paris', 'Europe/Moscow',
        'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Australia/Sydney', 'UTC',
    ];
@endphp

<div class="mu-clock small" data-server-now="{{ now()->getTimestampMs() }}" data-server-tz="{{ config('server.timezone') }}">
    <span class="mu-clock-item">
        <i class="fa-regular fa-clock"></i>
        {{ __('clock.server') }}:
        <span class="mu-clock-time" data-clock="server">--:--:--</span>
        <span class="mu-clock-off" data-clock-off="server"></span>
    </span>
    <span class="mu-clock-item">
        {{ __('clock.local') }}:
        <span class="mu-clock-time" data-clock="local">--:--:--</span>
        <span class="mu-clock-off" data-clock-off="local"></span>
        <select class="mu-clock-tz" data-clock-tz aria-label="{{ __('clock.timezone') }}">
            @foreach($zones as $z)
                <option value="{{ $z }}">{{ str_replace('_', ' ', $z) }}</option>
            @endforeach
        </select>
    </span>
</div>

@push('scripts')
<script>
(function () {
    var el = document.querySelector('.mu-clock');
    if (!el) return;

    var serverNow = parseInt(el.getAttribute('data-server-now'), 10);   // ms epoch, server-authoritative
    var serverTz  = el.getAttribute('data-server-tz') || 'UTC';
    var pageLoad  = Date.now();                                          // client clock at render

    var elServer    = el.querySelector('[data-clock="server"]');
    var elLocal     = el.querySelector('[data-clock="local"]');
    var elServerOff = el.querySelector('[data-clock-off="server"]');
    var elLocalOff  = el.querySelector('[data-clock-off="local"]');
    var select      = el.querySelector('[data-clock-tz]');

    // Auto-detect the visitor's timezone (their actual device zone; more accurate than IP).
    var detected = 'UTC';
    try { detected = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'; } catch (e) {}
    var userTz = localStorage.getItem('muss6_tz') || detected;

    // Make sure the (saved or detected) zone exists as an option.
    function ensureOption(tz) {
        if (!tz) return;
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === tz) return;
        }
        var o = document.createElement('option');
        o.value = tz; o.textContent = tz.replace(/_/g, ' ');
        select.insertBefore(o, select.firstChild);
    }
    ensureOption(userTz);
    select.value = userTz;

    function fmtTime(epoch, tz) {
        try {
            return new Intl.DateTimeFormat('en-GB', {
                timeZone: tz, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
            }).format(new Date(epoch));
        } catch (e) {
            return new Intl.DateTimeFormat('en-GB', {
                hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false,
            }).format(new Date(epoch));
        }
    }

    function fmtOffset(epoch, tz) {
        try {
            var parts = new Intl.DateTimeFormat('en-US', { timeZone: tz, timeZoneName: 'shortOffset' })
                .formatToParts(new Date(epoch));
            for (var i = 0; i < parts.length; i++) {
                if (parts[i].type === 'timeZoneName') return '(' + parts[i].value + ')';
            }
        } catch (e) {}
        return '';
    }

    function tick() {
        var now = serverNow + (Date.now() - pageLoad);
        elServer.textContent    = fmtTime(now, serverTz);
        elLocal.textContent     = fmtTime(now, userTz);
        elServerOff.textContent = fmtOffset(now, serverTz);
        elLocalOff.textContent  = fmtOffset(now, userTz);
    }

    select.addEventListener('change', function () {
        userTz = select.value;
        localStorage.setItem('muss6_tz', userTz);
        tick();
    });

    tick();
    setInterval(tick, 1000);
})();
</script>
@endpush
