<?php

namespace Sitewyn\Core\Base\Support;

class AdminFlash
{
    public function success(string $message, ?string $title = null): void
    {
        $this->flash('success', $message, $title);
    }

    public function error(string $message, ?string $title = null): void
    {
        $this->flash('danger', $message, $title);
    }

    public function warning(string $message, ?string $title = null): void
    {
        $this->flash('warning', $message, $title);
    }

    public function info(string $message, ?string $title = null): void
    {
        $this->flash('info', $message, $title);
    }

    public function flash(string $type, string $message, ?string $title = null): void
    {
        $type = $this->normalizeType($type);

        session()->flash('admin_flash', [
            'type' => $type,
            'title' => $title ?? $this->defaultTitle($type),
            'message' => $message,
        ]);

        session()->flash($type === 'danger' ? 'error' : 'status', $message);
    }

    /**
     * @return array{type: string, title: string, message: string}|null
     */
    public function current(): ?array
    {
        $flash = session('admin_flash');

        if (is_array($flash) && isset($flash['message'])) {
            $type = $this->normalizeType((string) ($flash['type'] ?? 'info'));

            return [
                'type' => $type,
                'title' => (string) ($flash['title'] ?? $this->defaultTitle($type)),
                'message' => (string) $flash['message'],
            ];
        }

        if (session()->has('error')) {
            return [
                'type' => 'danger',
                'title' => $this->defaultTitle('danger'),
                'message' => (string) session('error'),
            ];
        }

        if (session()->has('status')) {
            return [
                'type' => 'success',
                'title' => $this->defaultTitle('success'),
                'message' => (string) session('status'),
            ];
        }

        return null;
    }

    private function normalizeType(string $type): string
    {
        return match ($type) {
            'error' => 'danger',
            'danger', 'warning', 'success', 'info' => $type,
            default => 'info',
        };
    }

    private function defaultTitle(string $type): string
    {
        return match ($type) {
            'success' => __('Success'),
            'danger' => __('Error'),
            'warning' => __('Warning'),
            default => __('Notice'),
        };
    }
}
