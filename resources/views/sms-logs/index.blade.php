@extends('layouts.app')

@section('content')
    @php
        $smsTemplate = "BNHS Notice:\n[Student Name] has [Absences Count] absences for week starting [Week Start].\nPlease coordinate with the school.";
    @endphp

    <div class="page-head">
        <div>
            <h1>SMS API Integration</h1>
            <div class="crumbs muted">Attendance / SMS Logs</div>
        </div>
        <div class="sms-provider">
            <span class="sms-provider-label">{{ $smsProvider ?? 'Semaphore PH' }}</span>
        </div>
    </div>

    <div class="sms-api-grid">
        <div>
            <div class="card sms-card">
                <div class="sms-card-title">API ENDPOINTS</div>

                <div class="sms-endpoints">
                    <div class="sms-endpoint">
                        <div class="sms-method">POST</div>
                        <div>
                            <div class="sms-path">/api/mobile/sync/attendance</div>
                            <div class="muted sms-desc">Bulk sync for offline records. Absence rule runs and SMS can be queued.</div>
                        </div>
                    </div>

                    <div class="sms-endpoint">
                        <div class="sms-method">GET</div>
                        <div>
                            <div class="sms-path">/api/mobile/roster?school_year_id={id}&amp;section_id={id}</div>
                            <div class="muted sms-desc">Returns roster, weekly absence count, and SMS status per learner.</div>
                        </div>
                    </div>

                    <div class="sms-endpoint">
                        <div class="sms-method">GET</div>
                        <div>
                            <div class="sms-path">/api/mobile/enrollments/{enrollment}/profile</div>
                            <div class="muted sms-desc">Student profile + attendance this week + SMS status.</div>
                        </div>
                    </div>

                    <div class="sms-endpoint">
                        <div class="sms-method">POST</div>
                        <div>
                            <div class="sms-path">/api/auth/login</div>
                            <div class="muted sms-desc">Mobile login. Returns bearer token used by the endpoints above.</div>
                        </div>
                    </div>

                    <div class="sms-endpoint">
                        <div class="sms-method">GET</div>
                        <div>
                            <div class="sms-path">/sms-logs</div>
                            <div class="muted sms-desc">Web view for queued/sent/failed SMS logs.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div>
            <div class="card sms-card">
                <div class="sms-card-title">SMS MESSAGE TEMPLATE</div>
                <div class="sms-template">
                    {!! nl2br(e($smsTemplate)) !!}
                    <div class="muted" style="margin-top:10px; font-size:12px;">— EduTrack System</div>
                </div>
            </div>

            <div class="card sms-card">
                <div class="sms-card-head">
                    <div class="sms-card-title" style="margin:0;">SMS LOG TABLE</div>

                    <form method="GET" action="{{ route('sms-logs.index') }}" class="sms-filter">
                        <select name="status" aria-label="Filter by status">
                            <option value="" @selected($status === '')>All</option>
                            <option value="queued" @selected($status === 'queued')>Queued</option>
                            <option value="sending" @selected($status === 'sending')>Sending</option>
                            <option value="sent" @selected($status === 'sent')>Sent</option>
                            <option value="failed" @selected($status === 'failed')>Failed</option>
                        </select>
                        <button class="btn" type="submit" style="width:auto; padding:8px 12px;">Filter</button>
                    </form>
                </div>

                <div class="table-wrap">
                    <table class="table sms-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Sent At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                @php
                                    $statusLabel = (string) ($log->status ?? 'queued');
                                    $statusClass = 'sms-status--' . preg_replace('/[^a-z]/', '', strtolower($statusLabel));
                                @endphp
                                <tr>
                                    <td>{{ $log->student?->full_name ?? '-' }}</td>
                                    <td>{{ $log->phone_number }}</td>
                                    <td>
                                        <span class="sms-status {{ $statusClass }}">
                                            {{ strtolower($statusLabel) === 'sent' ? 'Sent ✓' : ucfirst($statusLabel) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->created_at?->format('M d, h:ia') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="muted">No logs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
