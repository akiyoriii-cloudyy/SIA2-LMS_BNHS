<?php

namespace App\Services;

use App\Models\SchoolNotification;
use App\Models\User;

class InAppNotificationService
{
    public function notifyUser(int $userId, string $type, string $title, string $message, ?array $meta = null): void
    {
        SchoolNotification::query()->create([
            'user_id' => $userId,
            'student_id' => null,
            'type' => $type,
            'channel' => 'in_app',
            'title' => $title,
            'message' => $message,
            'meta' => $meta,
            'sent_at' => now(),
            'read_at' => null,
        ]);
    }

    /**
     * @param  iterable<int|string>  $userIds
     */
    public function notifyUsers(iterable $userIds, string $type, string $title, string $message, ?array $meta = null): void
    {
        foreach ($userIds as $id) {
            $this->notifyUser((int) $id, $type, $title, $message, $meta);
        }
    }

    public function notifyAllAdmins(string $type, string $title, string $message, ?array $meta = null): void
    {
        $ids = User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->pluck('id');

        $this->notifyUsers($ids, $type, $title, $message, $meta);
    }

    public function notifyAllTeachers(string $type, string $title, string $message, ?array $meta = null): void
    {
        $ids = User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['adviser', 'subject_teacher']))
            ->pluck('id');

        $this->notifyUsers($ids, $type, $title, $message, $meta);
    }
}
