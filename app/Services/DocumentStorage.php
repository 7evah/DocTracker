<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Owns every filesystem path DocFlow writes (§32, §53).
 *
 * Nothing else in the application constructs a document path, which is what
 * keeps the storage layout swappable for S3/Azure Blob later.
 */
class DocumentStorage
{
    public function disk(): Filesystem
    {
        return Storage::disk(config('documents.disk'));
    }

    /**
     * Directory for one revision: documents/{project}/{document}/{revision}.
     *
     * IDs and the revision label are the only inputs, and every one of them
     * is application-generated — no user string ever reaches the path.
     */
    public function directoryFor(Document $document, string $revision): string
    {
        return implode('/', [
            trim(config('documents.root'), '/'),
            $document->project_id,
            $document->id,
            $this->sanitiseSegment($revision),
        ]);
    }

    /**
     * Store an upload and return the attributes for a DocumentVersion row.
     *
     * The stored filename is random: the original is kept in the database for
     * display and re-attached at download time, but is never trusted as a
     * path component (§32 "do not trust original filenames").
     *
     * @return array{file_path: string, original_filename: string, mime_type: string|null, file_size: int}
     */
    public function store(UploadedFile|TemporaryUploadedFile $file, Document $document, string $revision): array
    {
        $extension = $this->safeExtension($file);
        $filename = Str::random(40).($extension ? '.'.$extension : '');

        $path = $this->disk()->putFileAs(
            $this->directoryFor($document, $revision),
            $file,
            $filename,
        );

        if ($path === false) {
            throw new \RuntimeException('Unable to store the uploaded document.');
        }

        return [
            'file_path' => $path,
            'original_filename' => $this->safeOriginalName($file),
            'mime_type' => $file->getMimeType() ?: null,
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function exists(DocumentVersion $version): bool
    {
        return filled($version->file_path) && $this->disk()->exists($version->file_path);
    }

    /**
     * Remove every stored file for a document.
     * Only ever called when a document is force-deleted by an administrator.
     */
    public function deleteAllFor(Document $document): void
    {
        $directory = implode('/', [
            trim(config('documents.root'), '/'),
            $document->project_id,
            $document->id,
        ]);

        $this->disk()->deleteDirectory($directory);
    }

    /**
     * Extension taken from the client filename, then whitelisted. Falls back
     * to the guessed extension so a file named "plan" still lands correctly.
     */
    private function safeExtension(UploadedFile|TemporaryUploadedFile $file): string
    {
        $allowed = config('documents.allowed_extensions');

        $extension = Str::lower($file->getClientOriginalExtension());

        if (! in_array($extension, $allowed, true)) {
            $extension = Str::lower((string) $file->guessExtension());
        }

        return in_array($extension, $allowed, true) ? $extension : '';
    }

    /**
     * Keep the original name for display, stripped of any path information.
     * basename() defeats "../" and "C:\..." style names before storage.
     */
    private function safeOriginalName(UploadedFile|TemporaryUploadedFile $file): string
    {
        $name = $file->getClientOriginalName();
        $name = str_replace('\\', '/', $name);
        $name = basename($name);

        return Str::limit(trim($name) ?: 'document', 250, '');
    }

    /** Defensive: revision labels are generated, but never build a path from an unvalidated string. */
    private function sanitiseSegment(string $segment): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '', $segment) ?: 'rev';
    }
}
