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