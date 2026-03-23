<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaStorageService
{
    public static function storeProfileAvatar(UploadedFile $file, int $userId): string
    {
        return self::storeAsPublic(
            $file,
            self::datedDirectory("profiles/users/{$userId}/avatar")
        );
    }

    public static function storeProfileCover(UploadedFile $file, int $userId): string
    {
        return self::storeAsPublic(
            $file,
            self::datedDirectory("profiles/users/{$userId}/cover")
        );
    }

    public static function storePostMedia(UploadedFile $file, int $userId, string $mediaType): string
    {
        $bucket = $mediaType === 'video' ? 'videos' : 'images';

        return self::storeAsPublic(
            $file,
            self::datedDirectory("posts/users/{$userId}/{$bucket}")
        );
    }

    public static function storeMessageMedia(UploadedFile $file, int $senderId, int $receiverId, string $mediaType): string
    {
        $participants = [$senderId, $receiverId];
        sort($participants);

        $conversationKey = $participants[0] . '_' . $participants[1];
        $bucket = $mediaType === 'video' ? 'videos' : 'images';

        return self::storeAsPublic(
            $file,
            self::datedDirectory("messages/conversations/{$conversationKey}/{$bucket}")
        );
    }

    public static function deletePublicFile(?string $path): void
    {
        if (!$path) {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        if (Str::startsWith($path, '/storage/')) {
            $path = Str::replaceFirst('/storage/', '', $path);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function storeAsPublic(UploadedFile $file, string $directory): string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension() ?: 'bin';
        $filename = Str::uuid()->toString() . '.' . strtolower($extension);

        return $file->storeAs($directory, $filename, 'public');
    }

    private static function datedDirectory(string $prefix): string
    {
        return $prefix . '/' . now()->format('Y/m');
    }
}
