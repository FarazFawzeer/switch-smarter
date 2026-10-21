<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Types safe to display inline. Anything else (HTML, SVG, scripts…) is
     * forced to download so it can never execute in the app's origin.
     */
    private const INLINE_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/gif',
        'application/pdf',
    ];

    /**
     * Serve a file from storage/app/public directly,
     * bypassing the need for the storage:link symlink.
     */
    public function show(string $path)
    {
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $fullPath = Storage::disk('public')->path($path);
        $mime = Storage::disk('public')->mimeType($path);

        if (! in_array($mime, self::INLINE_MIME_TYPES, true)) {
            return response()->download($fullPath, basename($path), [
                'Content-Type'           => 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        }

        return response()->file($fullPath, [
            'Content-Type'           => $mime,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
