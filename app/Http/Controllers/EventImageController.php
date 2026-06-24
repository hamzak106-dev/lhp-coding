<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EventImageController extends Controller
{
    public function storage(string $path): BinaryFileResponse
    {
        return $this->show("events/{$path}");
    }

    public function show(string $path): BinaryFileResponse
    {
        $path = ltrim($path, '/');

        abort_if(str_contains($path, '..') || str_contains($path, '\\'), 404);
        abort_unless(Str::startsWith($path, 'events/'), 404);

        $file = storage_path("app/public/{$path}");
        $eventsRoot = realpath(storage_path('app/public/events'));
        $realFile = realpath($file);

        abort_if($eventsRoot === false || $realFile === false, 404);
        abort_unless(Str::startsWith($realFile, $eventsRoot.DIRECTORY_SEPARATOR), 404);
        abort_unless(is_file($realFile), 404);

        return response()->file($realFile, [
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
