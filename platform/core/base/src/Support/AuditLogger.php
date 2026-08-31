<?php

namespace Sitewyn\Core\Base\Support;

use Sitewyn\Core\Base\Models\AuditLog;
use Throwable;

class AuditLogger
{
    /**
     * Property keys that must never reach the audit table, at the top level
     * and one nested level down.
     */
    private const SENSITIVE_KEYS = ['password', 'password_confirmation', 'remember_token'];

    public function record(string $action, string $subjectType, ?int $subjectId, array $properties = []): void
    {
        try {
            AuditLog::query()->create([
                'user_id' => $this->resolveUserId(),
                'action' => $action,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'properties' => $this->sanitize($properties),
                'ip_address' => request()->ip(),
                'user_agent' => $this->userAgent(),
            ]);
        } catch (Throwable $exception) {
            // Auditing must never break the action being audited.
            report($exception);
        }
    }

    private function resolveUserId(): ?int
    {
        $user = auth('admin')->user();

        return $user === null ? null : (int) $user->getAuthIdentifier();
    }

    private function userAgent(): ?string
    {
        $userAgent = request()->userAgent();

        if ($userAgent === null) {
            return null;
        }

        return mb_substr($userAgent, 0, 500);
    }

    /**
     * Strip sensitive keys at the top level and inside first-level nested arrays.
     *
     * @param  array<array-key, mixed>  $properties
     * @return array<array-key, mixed>
     */
    private function sanitize(array $properties): array
    {
        $sanitized = [];

        foreach ($properties as $key => $value) {
            if ($this->isSensitive($key)) {
                continue;
            }

            $sanitized[$key] = is_array($value)
                ? collect($value)->reject(fn (mixed $nested, int|string $nestedKey): bool => $this->isSensitive($nestedKey))->all()
                : $value;
        }

        return $sanitized;
    }

    private function isSensitive(int|string $key): bool
    {
        return in_array(mb_strtolower((string) $key), self::SENSITIVE_KEYS, true);
    }
}
