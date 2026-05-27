@php
    use App\Services\AttendanceMonthlyReportService;

    $byDay = $byDay ?? [];
@endphp
@if ($byDay === [])
    <span class="muted">—</span>
@else
    <span class="amr-daily-attendance">
        @foreach (collect($byDay)->sortKeys(SORT_NATURAL) as $day => $status)
            <span
                class="amr-day-chip amr-day-{{ strtolower((string) $status) }}"
                title="Day {{ (int) $day }}: {{ ucfirst((string) $status) }}"
            >{{ $day }}:{{ AttendanceMonthlyReportService::statusMarker((string) $status) }}</span>
        @endforeach
    </span>
@endif
