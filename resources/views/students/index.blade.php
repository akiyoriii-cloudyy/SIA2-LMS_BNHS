@extends('layouts.app')

@section('content')
    @php
        $s = $stats ?? [];
        $totalStudents = (int) ($s['total_students'] ?? 0);
        $totalEnrollments = (int) ($s['total_enrollments'] ?? 0);
        $maleCount = (int) ($s['male'] ?? 0);
        $femaleCount = (int) ($s['female'] ?? 0);
        $blaanCount = (int) ($s['blaan'] ?? 0);
        $islamCount = (int) ($s['islam'] ?? 0);
        $guardiansTotal = (int) ($s['guardians'] ?? 0);

        $selectedSY = $schoolYears->firstWhere('id', $selectedSchoolYear);
        $selectedSec = $sections->firstWhere('id', $selectedSection);
        $scopeLabel = $selectedSec ? ('Grade '.$selectedSec->grade_level.' - '.$selectedSec->name) : '-';
        $syLabel = $selectedSY?->name ?? '-';

        $status = (string) ($status ?? 'active');
        $activeCount = (int) ($activeCount ?? 0);
        $deletedCount = (int) ($deletedCount ?? 0);
    @endphp

    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduGrade Pro</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Students</span>
        </div>

        <div class="dash-topbar-actions">
            <button class="btn btn-primary btn-sm" type="button" onclick="window.print()">Print</button>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-kpi-grid dash-kpi-grid--5">
        <div class="dash-kpi kpi-gold">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">ST</div></div>
            <div class="dash-kpi-value">{{ number_format($totalStudents) }}</div>
            <div class="dash-kpi-label">TOTAL STUDENTS</div>
            <div class="dash-kpi-sub">{{ $scopeLabel }}</div>
        </div>

        <div class="dash-kpi kpi-navy">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">EN</div></div>
            <div class="dash-kpi-value">{{ number_format($totalEnrollments) }}</div>
            <div class="dash-kpi-label">ENROLLMENTS</div>
            <div class="dash-kpi-sub">SY. {{ $syLabel }}</div>
        </div>

        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">M</div></div>
            <div class="dash-kpi-value">{{ number_format($maleCount) }}</div>
            <div class="dash-kpi-label">MALE</div>
            <div class="dash-kpi-sub">Distinct learners</div>
        </div>

        <div class="dash-kpi kpi-sage">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">F</div></div>
            <div class="dash-kpi-value">{{ number_format($femaleCount) }}</div>
            <div class="dash-kpi-label">FEMALE</div>
            <div class="dash-kpi-sub">Distinct learners</div>
        </div>

        <div class="dash-kpi kpi-red">
            <div class="dash-kpi-top"><div class="dash-kpi-icon">GU</div></div>
            <div class="dash-kpi-value">{{ number_format($guardiansTotal) }}</div>
            <div class="dash-kpi-label">GUARDIANS</div>
            <div class="dash-kpi-sub">Blaan: {{ number_format($blaanCount) }} | Islam: {{ number_format($islamCount) }}</div>
        </div>
    </div>

    <div class="dash-panel dash-panel--students">
        <div class="dash-panel-hd">
            <div>
                <div class="dash-panel-title">Enrollment List</div>
                <div class="dash-panel-sub">Search, filter, add, edit, and delete student records</div>
            </div>
        </div>
        <div class="dash-panel-body">
            <div class="pill-row" style="margin-bottom: 12px;">
                <a class="pill pill-link {{ $status === 'active' ? 'pill-link--active' : '' }}"
                    href="{{ route('students.index', ['status' => 'active', 'school_year_id' => $selectedSchoolYear, 'grade_level' => $selectedGradeLevel, 'section_id' => $selectedSection, 'q' => $search]) }}">
                    Active ({{ $activeCount }})
                </a>
                <a class="pill pill-link {{ $status === 'deleted' ? 'pill-link--active' : '' }}"
                    href="{{ route('students.index', ['status' => 'deleted', 'school_year_id' => $selectedSchoolYear, 'grade_level' => $selectedGradeLevel, 'section_id' => $selectedSection, 'q' => $search]) }}">
                    Deleted ({{ $deletedCount }})
                </a>
            </div>

            <details class="inline-details" style="margin-bottom: 12px;">
                <summary class="btn btn-gold btn-sm">Add student</summary>
                <div class="inline-panel">
                    <form method="POST" action="{{ route('students.store') }}">
                        @csrf
                        <input type="hidden" name="school_year_id" value="{{ (int) $selectedSchoolYear }}">
                        <input type="hidden" name="grade_level" value="{{ (int) $selectedGradeLevel }}">
                        <input type="hidden" name="section_id" value="{{ (int) $selectedSection }}">
                        <div class="inline-grid" style="grid-template-columns: 160px 1fr 1fr auto;">
                            <input name="lrn" placeholder="LRN (optional)" value="{{ old('lrn') }}">
                            <input name="first_name" placeholder="First name" value="{{ old('first_name') }}" required>
                            <input name="last_name" placeholder="Last name" value="{{ old('last_name') }}" required>
                            <button class="btn btn-primary btn-sm" type="submit">Save</button>
                        </div>
                        <div class="inline-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-top: 10px;">
                            <input name="middle_name" placeholder="Middle name (optional)" value="{{ old('middle_name') }}">
                            <input name="suffix" placeholder="Suffix (optional)" value="{{ old('suffix') }}">
                            <select name="sex">
                                <option value="" @selected(old('sex') === '')>Sex (optional)</option>
                                <option value="Male" @selected(old('sex') === 'Male')>Male</option>
                                <option value="Female" @selected(old('sex') === 'Female')>Female</option>
                            </select>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}">
                            <input type="number" name="age" min="0" max="120" step="1" placeholder="Age" value="{{ old('age') }}">
                        </div>
                        <div class="inline-grid" style="grid-template-columns: 220px 1fr; margin-top: 10px;">
                            <input name="ethnicity" placeholder="Ethnicity (e.g., Blaan/Islam)" value="{{ old('ethnicity') }}">
                            <input name="address" placeholder="Address (optional)" value="{{ old('address') }}">
                        </div>
                    </form>
                </div>
            </details>

            <form method="GET" action="{{ route('students.index') }}" class="records-filters">
                <input type="hidden" name="status" value="{{ $status }}">
                <div class="records-filter-row">
                    <div class="records-filter">
                        <label class="records-label">School Year</label>
                        <select name="school_year_id">
                            @foreach ($schoolYears as $sy)
                                <option value="{{ $sy->id }}" @selected($selectedSchoolYear === $sy->id)>{{ $sy->name }}{{ $sy->is_active ? ' (Active)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="records-filter">
                        <label class="records-label">Level</label>
                        <select name="grade_level" onchange="this.form.submit()">
                            @foreach ($gradeLevels as $gradeLevel)
                                <option value="{{ $gradeLevel }}" @selected($selectedGradeLevel === (int) $gradeLevel)>
                                    Grade {{ $gradeLevel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="records-filter">
                        <label class="records-label">Section</label>
                        <select name="section_id">
                            @foreach ($sections as $sec)
                                <option value="{{ $sec->id }}" @selected($selectedSection === $sec->id)>
                                    {{ $sec->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="records-filter records-filter--search">
                        <label class="records-label">Search</label>
                        <input type="text" name="q" placeholder="LRN / name..." value="{{ $search ?? '' }}">
                    </div>
                    <div class="records-filter records-filter--btn">
                        <button class="btn btn-sm" type="submit">Apply</button>
                    </div>
                </div>
            </form>

            <div class="table-wrap students-table-wrap" style="margin-top: 12px;">
                <table class="table students-table">
                    <thead>
                        <tr>
                            <th>LRN</th>
                            <th>Student</th>
                            <th>Sex</th>
                            <th>Age</th>
                            <th>Ethnicity</th>
                            <th>Address</th>
                            <th>Section</th>
                            <th>Status</th>
                            <th>Guardians</th>
                            <th style="width: 240px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($enrollments as $enrollment)
                            @php $student = $enrollment->student; @endphp
                            <tr class="{{ $student && $student->trashed() ? 'row-deleted' : '' }}">
                                <td style="font-family: 'JetBrains Mono', monospace; font-weight: 800;">
                                    {{ $student?->lrn ?? '-' }}
                                </td>
                                <td style="font-weight: 800;">{{ $student?->full_name ?? '-' }}</td>
                                <td>{{ $student?->sex ?? '-' }}</td>
                                <td>{{ $student?->age ?? '-' }}</td>
                                <td>{{ $student?->ethnicity ?? '-' }}</td>
                                <td>{{ $student?->address ?? '-' }}</td>
                                <td>Grade {{ $enrollment->section?->grade_level }} - {{ $enrollment->section?->name }}</td>
                                <td><span class="chip">{{ $enrollment->status }}</span></td>
                                <td>{{ number_format((int) ($student?->guardians_count ?? 0)) }}</td>
                                <td>
                                    @if ($student)
                                        @if ($status === 'deleted')
                                            <form method="POST" action="{{ route('students.restore', $student->id) }}">
                                                @csrf
                                                <button class="btn btn-gold btn-sm" type="submit">Restore</button>
                                            </form>
                                        @else
                                            <div class="admin-actions">
                                                <details class="inline-details students-edit-details">
                                                    <summary class="btn btn-outline btn-sm">Edit</summary>
                                                    <div class="inline-panel">
                                                        <form method="POST" action="{{ route('students.update', $student) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <div class="inline-grid" style="grid-template-columns: 160px 1fr 1fr auto;">
                                                                <input name="lrn" placeholder="LRN" value="{{ $student->lrn }}">
                                                                <input name="first_name" value="{{ $student->first_name }}" required>
                                                                <input name="last_name" value="{{ $student->last_name }}" required>
                                                                <button class="btn btn-primary btn-sm" type="submit">Save</button>
                                                            </div>
                                                            <div class="inline-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-top: 10px;">
                                                                <input name="middle_name" placeholder="Middle name" value="{{ $student->middle_name }}">
                                                                <input name="suffix" placeholder="Suffix" value="{{ $student->suffix }}">
                                                                <select name="sex">
                                                                    <option value="" @selected(($student->sex ?? '') === '')>Sex (optional)</option>
                                                                    <option value="Male" @selected(($student->sex ?? '') === 'Male')>Male</option>
                                                                    <option value="Female" @selected(($student->sex ?? '') === 'Female')>Female</option>
                                                                </select>
                                                                <input type="date" name="date_of_birth" value="{{ optional($student->date_of_birth)->format('Y-m-d') }}">
                                                                <input type="number" name="age" min="0" max="120" step="1" placeholder="Age" value="{{ old('age', $student->age) }}">
                                                            </div>
                                                            <div class="inline-grid" style="grid-template-columns: 220px 1fr; margin-top: 10px;">
                                                                <input name="ethnicity" placeholder="Ethnicity (e.g., Blaan/Islam)" value="{{ old('ethnicity', $student->ethnicity) }}">
                                                                <input name="address" placeholder="Address (optional)" value="{{ old('address', $student->address) }}">
                                                            </div>
                                                        </form>
                                                    </div>
                                                </details>

                                                <form method="POST" action="{{ route('students.destroy', $student) }}"
                                                    onsubmit="return confirm('Delete this student?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                                </form>
                                            </div>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="muted">No enrollments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
