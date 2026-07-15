<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\AppDatabaseNotification;
use Illuminate\Support\Collection;

class AppNotifier
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public static function notify(User $user, string $title, string $body, string $href, string $kind, array $meta = []): void
    {
        if ($user->is_disabled) {
            return;
        }

        $user->notify(new AppDatabaseNotification([
            'title' => $title,
            'body' => $body,
            'href' => $href,
            'kind' => $kind,
            'meta' => $meta,
        ]));
    }

    /**
     * تجنب إشعارات مكررة لنفس الحدث خلال نافذة زمنية قصيرة (ضغط مزدوج / مسارين).
     *
     * @param  array<string, mixed>  $meta
     */
    public static function notifyOnce(
        User $user,
        string $title,
        string $body,
        string $href,
        string $kind,
        array $meta = [],
        int $withinSeconds = 90,
    ): void {
        if ($user->is_disabled) {
            return;
        }

        $consultationId = isset($meta['consultation_id']) ? (int) $meta['consultation_id'] : null;

        $recent = $user->notifications()
            ->where('created_at', '>=', now()->subSeconds(max($withinSeconds, 15)))
            ->latest()
            ->limit(20)
            ->get();

        $duplicate = $recent->contains(function ($row) use ($kind, $consultationId, $withinSeconds) {
            if ($row->created_at === null || $row->created_at->lt(now()->subSeconds($withinSeconds))) {
                return false;
            }
            /** @var array<string, mixed> $data */
            $data = $row->data;
            if (($data['kind'] ?? null) !== $kind) {
                return false;
            }
            if ($consultationId === null) {
                return true;
            }

            return (int) (($data['meta']['consultation_id'] ?? 0)) === $consultationId;
        });

        // ضغط مزدوج / مساران لنفس الرد خلال ثوانٍ فقط
        if (! $duplicate && $consultationId !== null && in_array($kind, [
            'consultation_replied',
            'consultation_physician_message',
        ], true)) {
            $duplicate = $recent->contains(function ($row) use ($consultationId) {
                if ($row->created_at === null || $row->created_at->lt(now()->subSeconds(12))) {
                    return false;
                }
                /** @var array<string, mixed> $data */
                $data = $row->data;
                $rowKind = (string) ($data['kind'] ?? '');
                if (! in_array($rowKind, ['consultation_replied', 'consultation_physician_message'], true)) {
                    return false;
                }

                return (int) (($data['meta']['consultation_id'] ?? 0)) === $consultationId;
            });
        }

        if ($duplicate) {
            return;
        }

        self::notify($user, $title, $body, $href, $kind, $meta);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public static function notifyAdmins(string $title, string $body, string $href, string $kind, array $meta = []): void
    {
        self::admins()->each(fn (User $admin) => self::notify($admin, $title, $body, $href, $kind, $meta));
    }

    /** @return Collection<int, User> */
    public static function admins(): Collection
    {
        return User::query()
            ->where('role', User::ROLE_ADMIN)
            ->where('is_disabled', false)
            ->get();
    }
}
