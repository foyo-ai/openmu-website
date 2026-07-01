@push('head')
<style>
    .mu-clock { display: inline-flex; align-items: center; gap: .4rem; }
    .mu-clock-time { font-variant-numeric: tabular-nums; color: #e9c46a; font-weight: 600; }
    .mu-clock-tz { background: transparent; color: inherit; border: 1px solid rgba(255,255,255,.18);
                   border-radius: .25rem; padding: .02rem .3rem; font-size: .78rem; }
    .mu-clock-tz option { color: #000; }
</style>
@endpush

@php
    // Timezones offered in the picker; the visitor's own zone is auto-added client-side.
    $zones = [
        'UTC', 'Asia/Ho_Chi_Minh', 'Asia/Bangkok', 'Asia/Singapore', 'Asia/Manila', 'Asia/Jakarta',
        'Asia/Tokyo', 'Asia/Seoul', 'Asia/Shanghai', 'Asia/Kolkata', 'Asia/Dubai',
        'Europe/London', 'Europe/Paris', 'Europe/Moscow',
        'America/New_York', 'America/Chicago', 'America/Los_Angeles', 'Australia/Sydney',
    ];
@endphp

<span class="mu-clock small text-muted">
    <i class="fa-regular fa-clock"></i>
    <span>{{ __('clock.server') }}:</span>
    <span class="mu-clock-time" data-servertime="{{ now()->getTimestampMs() }}" data-live data-offset>--:--:--</span>
    <select class="mu-clock-tz" data-clock-tz aria-label="{{ __('clock.timezone') }}">
        @foreach($zones as $z)<option value="{{ $z }}">{{ str_replace('_', ' ', $z) }}</option>@endforeach
    </select>
</span>

@push('scripts')
<script>
(function () {
    var sel = document.querySelector('[data-clock-tz]');
    if (!sel || !window.MU_TZ) return;
    var cur = window.MU_TZ.get();
    var has = false;
    for (var i = 0; i < sel.options.length; i++) { if (sel.options[i].value === cur) { has = true; break; } }
    if (!has) {
        var o = document.createElement('option');
        o.value = cur; o.textContent = cur.replace(/_/g, ' ');
        sel.insertBefore(o, sel.firstChild);
    }
    sel.value = cur;
    sel.addEventListener('change', function () { window.MU_TZ.set(sel.value); });
})();
</script>
@endpush
