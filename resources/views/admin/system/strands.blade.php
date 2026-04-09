@extends('layouts.app')

@section('title', 'Manage Strands')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Manage Strands</span>
        </div>
        <div class="dash-topbar-actions">
            <a class="btn btn-outline btn-sm" href="{{ route('admin.system.index') }}">Back to Dashboard</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-panel">
        <div class="dash-panel-hd"><div class="dash-panel-title">Create New Strand</div></div>
        <div class="dash-panel-body">
            <form method="POST" action="{{ route('admin.system.strands.store') }}">
                @csrf
                <div class="grid-3">
                    <div><label>Strand Code</label><input name="code" placeholder="e.g., HUMSS, ABM, STEM" required></div>
                    <div><label>Strand Name</label><input name="name" placeholder="e.g., Humanities and Social Sciences" required></div>
                    <div><label>Description</label><input name="description" placeholder="Optional description"></div>
                </div>
                <button class="btn btn-primary" type="submit" style="margin-top:10px;">Create Strand</button>
            </form>
        </div>
    </div>

    <div class="dash-panel" style="margin-top:12px;">
        <div class="dash-panel-hd"><div class="dash-panel-title">Existing Strands</div></div>
        <div class="dash-panel-body">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>#</th><th>Code</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($strands as $index => $strand)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $strand->code }}</td>
                                <td>{{ $strand->name }}</td>
                                <td>{{ $strand->description ?: 'N/A' }}</td>
                                <td>
                                    <details class="inline-details">
                                        <summary class="btn btn-outline btn-sm">Edit</summary>
                                        <div class="inline-panel">
                                            <form method="POST" action="{{ route('admin.system.strands.update', $strand) }}">
                                                @csrf
                                                @method('PUT')
                                                <div class="grid-3">
                                                    <input name="code" value="{{ $strand->code }}" required>
                                                    <input name="name" value="{{ $strand->name }}" required>
                                                    <input name="description" value="{{ $strand->description }}">
                                                </div>
                                                <button class="btn btn-primary btn-sm" type="submit" style="margin-top:8px;">Save</button>
                                            </form>
                                        </div>
                                    </details>
                                    <form method="POST" action="{{ route('admin.system.strands.destroy', $strand) }}" style="margin-top:6px;" onsubmit="return confirm('Delete this strand?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline btn-sm" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="muted">No strands yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
