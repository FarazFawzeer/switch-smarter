<?php

if (! function_exists('storage_asset')) {
    /**
     * Generate a URL for a file stored in storage/app/public,
     * served via our own route instead of relying on the storage:link symlink.
     */
    function storage_asset(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return route('media.show', ['path' => $path]);
    }
}

if (! function_exists('user_avatar')) {
    /**
     * Profile photo URL for a user, or the default (male) avatar when none is uploaded.
     * Handles both upload locations: profile photos saved under public/uploads/…
     * and team/admin photos saved on the storage "public" disk.
     */
    function user_avatar(?string $path): string
    {
        if (! $path) {
            return asset('images/users/default-male.jpg');
        }

        return str_starts_with($path, 'uploads/') ? asset($path) : storage_asset($path);
    }
}
if (! function_exists('qty')) {
    /** Stock quantity without needless decimals: 5.00 → "5", 2.50 → "2.5". */
    function qty($value): string
    {
        $formatted = number_format((float) $value, 2, '.', ',');

        return str_contains($formatted, '.') ? rtrim(rtrim($formatted, '0'), '.') : $formatted;
    }
}

if (! function_exists('money')) {
    /** Amount in QAR with 2 decimals. */
    function money($value): string
    {
        return 'QAR ' . number_format((float) $value, 2);
    }
}
