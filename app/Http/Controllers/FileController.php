<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
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

        return response()->file($fullPath, [
            'Content-Type' => $mime,
        ]);
    }
}