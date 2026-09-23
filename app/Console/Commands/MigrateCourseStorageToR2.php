<?php

namespace App\Console\Commands;

use App\Models\CourseLesson;
use App\Services\FileUploadService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MigrateCourseStorageToR2 extends Command
{
    protected $signature = 'noon:migrate-course-storage 
                            {--dry-run : Run without making changes}
                            {--lesson-id= : Migrate specific lesson ID}
                            {--course-id= : Migrate all lessons for a specific course}
                            {--batch-size=100 : Number of lessons to process per batch}
                            {--force : Force re-migration of already migrated files}';

    protected $description = 'Migrate lesson files from local private storage to Cloudflare R2';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $lessonId = $this->option('lesson-id');
        $courseId = $this->option('course-id');
        $batchSize = (int) $this->option('batch-size');
        $force = $this->option('force');

        $this->info('Starting course storage migration to R2...');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = CourseLesson::query()
            ->where(function ($q) {
                $q->whereNotNull('content_url')
                  ->orWhereNotNull('content_file')
                  ->orWhereNotNull('subtitle_file');
            });

        if ($lessonId) {
            $query->where('id', $lessonId);
        }

        if ($courseId) {
            $query->where('course_id', $courseId);
        }

        // Only migrate files that are not already on R2 (unless force is used)
        if (!$force) {
            $query->where(function ($q) {
                $q->where('content_url', 'NOT LIKE', 'courses/%/lessons/%/videos/%')
                  ->orWhere('content_file', 'NOT LIKE', 'courses/%/lessons/%/documents/%')
                  ->orWhere('subtitle_file', 'NOT LIKE', 'courses/%/lessons/%/subtitles/%');
            });
        }

        $totalLessons = $query->count();
        $this->info("Found {$totalLessons} lessons with files to process");

        if ($totalLessons === 0) {
            $this->info('No lessons to migrate.');
            return Command::SUCCESS;
        }

        $progressBar = $this->output->createProgressBar($totalLessons);
        $progressBar->setFormat('%current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');

        $processed = 0;
        $migrated = 0;
        $errors = 0;
        $skipped = 0;

        $query->chunkById($batchSize, function ($lessons) use (
            $dryRun,
            $force,
            &$processed,
            &$migrated,
            &$errors,
            &$skipped,
            $progressBar
        ) {
            foreach ($lessons as $lesson) {
                try {
                    $result = $this->migrateLessonFiles($lesson, $dryRun, $force);

                    switch ($result) {
                        case 'migrated':
                            $migrated++;
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                        case 'error':
                            $errors++;
                            break;
                    }
                } catch (\Throwable $e) {
                    $errors++;
                    $this->error("Error migrating lesson {$lesson->id}: {$e->getMessage()}");
                }

                $processed++;
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Migration Summary:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Processed', $processed],
                ['Migrated', $migrated],
                ['Skipped (already on R2)', $skipped],
                ['Errors', $errors],
            ]
        );

        if ($errors > 0) {
            $this->error("Migration completed with {$errors} errors.");
            return Command::FAILURE;
        }

        $this->info('Migration completed successfully!');
        return Command::SUCCESS;
    }

    private function migrateLessonFiles(CourseLesson $lesson, bool $dryRun, bool $force): string
    {
        $privateDisk = Storage::disk(FileUploadService::DISK_PRIVATE);
        $r2Disk = Storage::disk(FileUploadService::DISK_R2);

        $courseId = $lesson->course_id;
        $lessonId = $lesson->id;

        $migrated = false;

        // Migrate video/content_url
        if ($lesson->content_url && !$this->isR2Path($lesson->content_url)) {
            if ($privateDisk->exists($lesson->content_url)) {
                $newPath = $this->generateR2Path($courseId, $lessonId, 'videos', $lesson->content_url);
                if ($this->migrateFile($lesson->content_url, $newPath, $privateDisk, $r2Disk, $dryRun)) {
                    if (!$dryRun) {
                        $lesson->content_url = $newPath;
                    }
                    $migrated = true;
                    $this->line("  Migrated video: {$lesson->content_url} -> {$newPath}");
                } else {
                    $this->error("  Failed to migrate video: {$lesson->content_url}");
                    return 'error';
                }
            } else {
                $this->warn("  Video file not found in private storage: {$lesson->content_url}");
            }
        } elseif ($lesson->content_url && $force && $privateDisk->exists($lesson->content_url)) {
            $newPath = $this->generateR2Path($courseId, $lessonId, 'videos', $lesson->content_url);
            if ($this->migrateFile($lesson->content_url, $newPath, $privateDisk, $r2Disk, $dryRun)) {
                if (!$dryRun) {
                    $lesson->content_url = $newPath;
                }
                $migrated = true;
            }
        }

        // Migrate document/content_file
        if ($lesson->content_file && !$this->isR2Path($lesson->content_file)) {
            if ($privateDisk->exists($lesson->content_file)) {
                $newPath = $this->generateR2Path($courseId, $lessonId, 'documents', $lesson->content_file);
                if ($this->migrateFile($lesson->content_file, $newPath, $privateDisk, $r2Disk, $dryRun)) {
                    if (!$dryRun) {
                        $lesson->content_file = $newPath;
                    }
                    $migrated = true;
                    $this->line("  Migrated document: {$lesson->content_file} -> {$newPath}");
                } else {
                    $this->error("  Failed to migrate document: {$lesson->content_file}");
                    return 'error';
                }
            } else {
                $this->warn("  Document file not found in private storage: {$lesson->content_file}");
            }
        } elseif ($lesson->content_file && $force && $privateDisk->exists($lesson->content_file)) {
            $newPath = $this->generateR2Path($courseId, $lessonId, 'documents', $lesson->content_file);
            if ($this->migrateFile($lesson->content_file, $newPath, $privateDisk, $r2Disk, $dryRun)) {
                if (!$dryRun) {
                    $lesson->content_file = $newPath;
                }
                $migrated = true;
            }
        }

        // Migrate subtitle_file
        if ($lesson->subtitle_file && !$this->isR2Path($lesson->subtitle_file)) {
            if ($privateDisk->exists($lesson->subtitle_file)) {
                $newPath = $this->generateR2Path($courseId, $lessonId, 'subtitles', $lesson->subtitle_file);
                if ($this->migrateFile($lesson->subtitle_file, $newPath, $privateDisk, $r2Disk, $dryRun)) {
                    if (!$dryRun) {
                        $lesson->subtitle_file = $newPath;
                    }
                    $migrated = true;
                    $this->line("  Migrated subtitle: {$lesson->subtitle_file} -> {$newPath}");
                } else {
                    $this->error("  Failed to migrate subtitle: {$lesson->subtitle_file}");
                    return 'error';
                }
            } else {
                $this->warn("  Subtitle file not found in private storage: {$lesson->subtitle_file}");
            }
        } elseif ($lesson->subtitle_file && $force && $privateDisk->exists($lesson->subtitle_file)) {
            $newPath = $this->generateR2Path($courseId, $lessonId, 'subtitles', $lesson->subtitle_file);
            if ($this->migrateFile($lesson->subtitle_file, $newPath, $privateDisk, $r2Disk, $dryRun)) {
                if (!$dryRun) {
                    $lesson->subtitle_file = $newPath;
                }
                $migrated = true;
            }
        }

        if ($migrated && !$dryRun) {
            $lesson->save();
        }

        if (!$migrated) {
            return 'skipped';
        }

        return 'migrated';
    }

    private function isR2Path(string $path): bool
    {
        // Check if the path follows the R2 structure
        return Str::startsWith($path, ['courses/', 'lessons/']);
    }

    private function generateR2Path(int $courseId, int $lessonId, string $type, string $originalPath): string
    {
        $extension = pathinfo($originalPath, PATHINFO_EXTENSION);
        $uniqueName = Str::uuid() . '.' . $extension;
        return "courses/{$courseId}/lessons/{$lessonId}/{$type}/{$uniqueName}";
    }

    private function migrateFile(
        string $sourcePath,
        string $destPath,
        $sourceDisk,
        $destDisk,
        bool $dryRun
    ): bool {
        if ($dryRun) {
            return true;
        }

        try {
            // Read from source and write to destination
            $content = $sourceDisk->get($sourcePath);

            if ($content === false) {
                return false;
            }

            $destDisk->put($destPath, $content);

            // Verify the file was uploaded
            if (!$destDisk->exists($destPath)) {
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            $this->error("  Exception during migration: {$e->getMessage()}");
            return false;
        }
    }
}