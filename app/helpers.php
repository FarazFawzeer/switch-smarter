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