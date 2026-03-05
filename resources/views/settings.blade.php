@extends('layouts.app')

@section('title', 'Settings')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Settings</span>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="error">{{ $errors->first() }}</div>
    @endif

    <div class="dash-row-2" style="grid-template-columns: 1fr;">
        <div class="dash-panel">
            <div class="dash-panel-hd">
                <div>
                    <div class="dash-panel-title">Profile</div>
                    <div class="dash-panel-sub">Update your account details</div>
                </div>
            </div>
            <div class="dash-panel-body">
                <form method="POST" action="{{ route('settings.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="grid-3" style="grid-template-columns: 1fr 1fr 1fr;">
                        <div>
                            <label>Name</label>
                            <input name="name" value="{{ old('name', $user?->name) }}" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" value="{{ $user?->email }}" readonly disabled>
                            <div class="muted" style="font-size:12px; margin-top:6px;">Email changes are managed by the admin.</div>
                        </div>
                        <div>
                            <label>Phone (optional)</label>
                            <input name="phone" value="{{ old('phone', $user?->phone) }}">
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button class="btn btn-primary" type="submit" style="width:100%;">Save</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
