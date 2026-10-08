<?php

namespace App\Services;

use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ImageUploadTransaction
{
    public function persist(
        ?UploadedFile $image,
        string $directory,
        Closure $persist,
        ?string $previousPath = null
    ): mixed {
        $newPath = null;

        if ($image !== null) {
            $newPath = $image->store($directory, 'public');

            if (! is_string($newPath) || $newPath === '') {
                throw new RuntimeException('Unable to store uploaded image.');
            }
        }

        try {
            $result = DB::transaction(fn (): mixed => $persist($newPath));
        } catch (Throwable $exception) {
            if ($newPath !== null) {
                $this->delete($newPath);
            }

            throw $exception;
        }

        if ($newPath !== null && $previousPath !== null && $previousPath !== $newPath) {
            $this->delete($previousPath);
        }

        return $result;
    }

    public function deleteAfter(Closure $persist, array $paths): mixed
    {
        $result = DB::transaction($persist);

        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                $this->delete($path);
            }
        }

        return $result;
    }

    private function delete(string $path): void
    {
        try {
            if (! Storage::disk('public')->delete($path)) {
                Log::warning('Unable to delete image from public storage.', ['path' => $path]);
            }
        } catch (Throwable $exception) {
            Log::warning('Unable to delete image from public storage.', [
                'path' => $path,
                'exception' => $exception,
            ]);
        }
    }
}
