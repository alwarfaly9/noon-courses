<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FileUploadService
{
    const ALLOWED_VIDEO_MIMES = ['video/mp4', 'video/x-msvideo', 'video/quicktime', 'video/x-matroska', 'video/webm'];
    const ALLOWED_VIDEO_EXTENSIONS = ['mp4', 'mov', 'mkv', 'avi', 'webm'];
    const MAX_VIDEO_SIZE = 512000;

    const ALLOWED_DOCUMENT_MIMES = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/x-rar-compressed'];
    const ALLOWED_DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar'];
    const MAX_DOCUMENT_SIZE = 512000;

    const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    const MAX_IMAGE_SIZE = 2048;

    const ALLOWED_SUBTITLE_MIMES = ['text/vtt', 'text/plain', 'application/x-subrip'];
    const ALLOWED_SUBTITLE_EXTENSIONS = ['vtt', 'srt', 'txt'];
    const MAX_SUBTITLE_SIZE = 10240;

    public static function validateVideo(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        return in_array($extension, self::ALLOWED_VIDEO_EXTENSIONS)
            && in_array($mime, self::ALLOWED_VIDEO_MIMES);
    }

    public static function validateDocument(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        return in_array($extension, self::ALLOWED_DOCUMENT_EXTENSIONS)
            && in_array($mime, self::ALLOWED_DOCUMENT_MIMES);
    }

    public static function validateImage(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        return in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS)
            && in_array($mime, self::ALLOWED_IMAGE_MIMES);
    }

    public static function validateSubtitle(UploadedFile $file): bool
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        return in_array($extension, self::ALLOWED_SUBTITLE_EXTENSIONS)
            && in_array($mime, self::ALLOWED_SUBTITLE_MIMES);
    }

    public static function validateLessonFile(UploadedFile $file): bool
    {
        return self::validateVideo($file) || self::validateDocument($file);
    }

    public static function getLessonValidationRules(): array
    {
        $allExts = array_merge(self::ALLOWED_VIDEO_EXTENSIONS, self::ALLOWED_DOCUMENT_EXTENSIONS);
        return [
            'file' => 'nullable|file|mimes:' . implode(',', $allExts) . '|max:' . self::MAX_VIDEO_SIZE,
            'subtitle' => 'nullable|file|mimes:' . implode(',', self::ALLOWED_SUBTITLE_EXTENSIONS) . '|max:' . self::MAX_SUBTITLE_SIZE,
            'video_file' => 'nullable|file|mimes:' . implode(',', self::ALLOWED_VIDEO_EXTENSIONS) . '|max:' . self::MAX_VIDEO_SIZE,
            'subtitle_file' => 'nullable|file|mimes:' . implode(',', self::ALLOWED_SUBTITLE_EXTENSIONS) . '|max:' . self::MAX_SUBTITLE_SIZE,
        ];
    }

    public static function getImageValidationRules(): array
    {
        return [
            'image' => 'nullable|image|mimes:' . implode(',', self::ALLOWED_IMAGE_EXTENSIONS) . '|max:' . self::MAX_IMAGE_SIZE,
        ];
    }

    public static function store(UploadedFile $file, string $path, string $disk = 'private'): string
    {
        return Storage::disk($disk)->putFile($path, $file);
    }

    public static function isVideo(UploadedFile $file): bool
    {
        return self::validateVideo($file);
    }
}
