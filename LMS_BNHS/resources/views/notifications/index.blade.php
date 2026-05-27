@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="dash-topbar">
        <div class="dash-topbar-left">
            <span class="dash-topbar-title">EduTrack</span>
            <span class="dash-topbar-sep">/</span>
            <span class="dash-topbar-bc">Notifications</span>
        </div>
        <div class="dash-topbar-actions">
            @if ($notifications->isNotEmpty())
                <form method="POST" action="{{ route('notifications.read-all') }}" class="inline">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm">Mark all as read</button>
                </form>
            @endif
            <a class="btn btn-outline btn-sm" href="{{ route('dashboard') }}">Back to dashboard</a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert">{{ session('status') }}</div>
    @endif

    <div class="notif-list">
        @forelse ($notifications as $notification)
            <article class="notif-card {{ $notification->read_at ? 'notif-card--read' : '' }}">
                <div class="notif-card-body">
                    <div class="notif-card-title">{{ $notification->title }}</div>
                    <p class="notif-card-message">{{ $notification->message }}</p>
                    <div class="notif-card-meta">
                        {{ $notification->created_at?->format('Y-m-d H:i:s') }}
                    </div>
                </div>
                @if ($notification->read_at === null)
                    <form method="POST" action="{{ route('notifications.read', $notification) }}" class="notif-card-action">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline">Mark as Read</button>
                    </form>
                @else
                    <span class="muted notif-card-read-label">Read</span>
                @endif
            </article>
        @empty
            <p class="muted">No notifications yet.</p>
        @endforelse
    </div>

    <div class="pagination-wrap" style="margin-top:16px;">
        {{ $notifications->links() }}
    </div>
@endsection
