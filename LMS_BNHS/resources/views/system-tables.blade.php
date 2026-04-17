@extends('layouts.app')

@section('content')
    @php
        $cards = $schemaCards ?? [];
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Database Tables</span>
        </div>

        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('report-cards.index') }}">Report Card</a>
            <a class="btn btn-gold btn-sm" href="{{ route('gradebook.index') }}">Compute All</a>
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    <div class="schema-head">
        <div>
            <div class="schema-title">Database Schema — SHS Grading System</div>
            <div class="schema-sub">Shared <code>student_id</code> connects to the Attendance mobile app for one unified school-wide database.</div>
        </div>
    </div>

    <div class="schema-grid">
        @foreach ($cards as $card)
            <div class="schema-card">
                <div class="schema-hd">
                    <div class="schema-hd-left">
                        <span class="schema-icon">{{ $card['icon'] ?? '🗃️' }}</span>
                        <span class="schema-name">{{ $card['name'] ?? 'table' }}</span>
                    </div>
                    @if (!empty($card['badge']))
                        <span class="schema-badge">{{ $card['badge'] }}</span>
                    @endif
                </div>

                <div class="schema-body">
                    @foreach (($card['fields'] ?? []) as $field)
                        <div class="schema-row">
                            <div class="schema-col schema-col--left">
                                @if (!empty($field['tag']))
                                    <span class="schema-tag schema-tag--{{ strtolower($field['tag']) }}">{{ $field['tag'] }}</span>
                                @endif
                                <span class="schema-field">{{ $field['name'] ?? '' }}</span>
                            </div>
                            <div class="schema-col schema-col--right">{{ $field['type'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endsection

