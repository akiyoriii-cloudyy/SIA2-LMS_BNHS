@extends('layouts.app')

@section('title', 'Mobile App')

@section('content')
    <div class="page-head">
        <div>
            <h1>Mobile App</h1>
            <div class="crumbs muted">Attendance / Mobile App</div>
        </div>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
            <a class="btn" href="{{ asset('mobile-attendance.html') }}" target="_blank" rel="noreferrer" style="width:auto;">
                Open in new tab
            </a>
        </div>
    </div>

    {{-- 3 KEY SCREENS reference (aligned to 1st image) --}}
    <section class="mobile-ref-section" aria-label="3 key screens reference">
        <h2 class="mobile-ref-title">MOBILE APP – 3 KEY SCREENS</h2>
        <div class="mobile-ref-grid">
            {{-- Screen A: Attendance Taking --}}
            <div>
                <div class="mobile-ref-frame" aria-label="Screen A">
                    <div class="mobile-ref-screen">
                        <div class="mobile-ref-header">
                            <div class="mobile-ref-brand">
                                <div class="mobile-ref-title-txt">EduTrack</div>
                                <div class="mobile-ref-sub">Grade 11 STEM A - Feb 20, 2026</div>
                            </div>
                            <span class="mobile-ref-pill online">• Online</span>
                        </div>
                        <div class="mobile-ref-body">
                            <div class="mobile-ref-banner">
                                <span class="ic">▲</span>
                                <div>Cruz, B. - 5 absences this week. SMS sent.</div>
                            </div>
                            <div class="mobile-ref-row">
                                <div class="mobile-ref-avatar">SA</div>
                                <div style="min-width:0;flex:1;">
                                    <div class="name">Santos, Ana</div>
                                    <div class="lrn">LRN: 110001</div>
                                </div>
                                <div style="display:flex;gap:4px;">
                                    <span class="mobile-ref-sb active">P</span>
                                    <span class="mobile-ref-sb">A</span>
                                    <span class="mobile-ref-sb">L</span>
                                </div>
                            </div>
                            <div class="mobile-ref-row">
                                <div class="mobile-ref-avatar">CB</div>
                                <div style="min-width:0;flex:1;">
                                    <div class="name">Cruz, Bryan</div>
                                    <div class="lrn">LRN: 110002</div>
                                </div>
                                <div style="display:flex;gap:4px;">
                                    <span class="mobile-ref-sb">P</span>
                                    <span class="mobile-ref-sb active">A</span>
                                    <span class="mobile-ref-sb">L</span>
                                </div>
                            </div>
                            <button type="button" class="mobile-ref-btn">Submit Attendance</button>
                        </div>
                        <div class="mobile-ref-nav">
                            <div class="active">✓<br>Attend.</div>
                            <div>👥<br>Students</div>
                            <div>📊<br>Reports</div>
                            <div>⚙<br>Settings</div>
                        </div>
                    </div>
                </div>
                <div class="mobile-ref-label">Screen A: Attendance Taking</div>
            </div>

            {{-- Screen B: Offline Mode --}}
            <div>
                <div class="mobile-ref-frame" aria-label="Screen B">
                    <div class="mobile-ref-screen">
                        <div class="mobile-ref-header">
                            <div class="mobile-ref-brand">
                                <div class="mobile-ref-title-txt">EduTrack</div>
                                <div class="mobile-ref-sub">Stored locally - syncs on reconnect</div>
                            </div>
                            <span class="mobile-ref-pill offline">X Offline</span>
                        </div>
                        <div class="mobile-ref-body">
                            <div class="mobile-ref-banner" style="background:#f8fafc;">
                                <span style="width:12px;height:12px;border-radius:99px;background:#ea580c;flex-shrink:0;"></span>
                                Offline - Local SQLite active
                            </div>
                            <div class="mobile-ref-banner">
                                <div>
                                    <div>Records saved locally.</div>
                                    <div style="font-size:9px;color:#777;">3 entries pending sync. SMS queued for delivery.</div>
                                </div>
                            </div>
                            <div class="mobile-ref-row">
                                <div class="mobile-ref-avatar">SA</div>
                                <div style="min-width:0;flex:1;">
                                    <div class="name">Santos, Ana</div>
                                    <div class="lrn">Saved locally ✓</div>
                                </div>
                                <span class="mobile-ref-sb active">P</span>
                            </div>
                            <div class="mobile-ref-row">
                                <div class="mobile-ref-avatar">CB</div>
                                <div style="min-width:0;flex:1;">
                                    <div class="name">Cruz, Bryan</div>
                                    <div class="lrn">Saved locally ✓</div>
                                </div>
                                <span class="mobile-ref-sb active">A</span>
                            </div>
                            <div style="font-size:9px;color:#777;text-align:center;margin-top:8px;">Data will auto-sync when Wi-Fi is restored. SMS alerts will fire upon reconnect.</div>
                        </div>
                        <div class="mobile-ref-nav">
                            <div>✓<br>Attend.</div>
                            <div class="active">👥<br>Students</div>
                            <div>📊<br>Reports</div>
                            <div>⚙<br>Settings</div>
                        </div>
                    </div>
                </div>
                <div class="mobile-ref-label">Screen B: Offline Mode</div>
            </div>

            {{-- Screen C: Student Profile --}}
            <div>
                <div class="mobile-ref-frame" aria-label="Screen C">
                    <div class="mobile-ref-screen">
                        <div class="mobile-ref-header">
                            <div class="mobile-ref-brand">
                                <div class="mobile-ref-title-txt">Student Profile</div>
                                <div class="mobile-ref-sub">Cruz, Bryan M.</div>
                            </div>
                            <button type="button" style="background:transparent;border:1px solid rgba(0,0,0,.2);padding:4px 8px;border-radius:6px;font-size:10px;cursor:default;">&lt; Back</button>
                        </div>
                        <div class="mobile-ref-body">
                            <div class="mobile-ref-row" style="flex-wrap:wrap;">
                                <div class="mobile-ref-avatar">CB</div>
                                <div style="min-width:0;flex:1;">
                                    <div class="name">Cruz, Bryan M.</div>
                                    <div class="lrn">LRN: 110002 • Grade 11 STEM A</div>
                                </div>
                            </div>
                            <div style="padding:8px;border:1px solid rgba(0,0,0,.12);border-radius:8px;margin-top:6px;font-size:10px;">
                                <div><strong>Parent:</strong> Cruz, Maria (Mother)</div>
                                <div class="lrn">Contact: +63917-000-0002</div>
                                <div class="lrn">Address: [address here]</div>
                            </div>
                            <div style="margin-top:8px;">
                                <div class="name" style="margin-bottom:4px;">Attendance This Week</div>
                                <div style="display:grid;grid-template-columns:repeat(5,1fr);gap:4px;">
                                    @foreach(['Mon','Tue','Wed','Thu','Fri'] as $d)
                                    <div style="text-align:center;border:1px solid rgba(0,0,0,.15);border-radius:4px;padding:4px;">
                                        <div style="font-size:9px;color:#777;">{{ $d }}</div>
                                        <span class="mobile-ref-sb active" style="width:100%;margin-top:2px;">A</span>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="mobile-ref-banner" style="margin-top:6px;">
                                    <span class="ic">▲</span>
                                    <div>5/5 absences - SMS sent to parent on Feb 20, 2026.</div>
                                </div>
                            </div>
                        </div>
                        <div class="mobile-ref-nav">
                            <div>✓<br>Attend.</div>
                            <div class="active">👥<br>Students</div>
                            <div>📊<br>Reports</div>
                            <div>⚙<br>Settings</div>
                        </div>
                    </div>
                </div>
                <div class="mobile-ref-label">Screen C: Student Profile</div>
            </div>
        </div>
    </section>

    <div class="phone-preview-shell">
        <div class="phone-frame" aria-label="Mobile app preview">
            <iframe
                class="phone-iframe"
                src="{{ asset('mobile-attendance.html') }}"
                title="EduTrack Mobile Attendance"
                loading="lazy"
            ></iframe>
        </div>
    </div>
@endsection
