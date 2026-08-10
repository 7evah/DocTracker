<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentStorage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The only route by which a stored document leaves the server (§32, §53).
 *
 * The disk is private and has no public URL, so authorization here cannot be
 * side-stepped by guessing a path.
 */
class DocumentDownloadController extends Controller
{
    public function __construct(private readonly DocumentStorage $storage) {}

    /**
     * Stream a specific revision.
     *
     * The nested route binding is deliberate: passing both the document and
     * the version lets us verify the version actually belongs to that
     * document, so a valid version id cannot be paired with a document the
     * user happens to be allowed to read.
     */
    public function __invoke(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->authorize('download', $document);

        abort_unless($version->document_id === $document->id, 404);

        abort_unless($this->storage->exists($version), 404);

        activity('document')
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->event('downloaded')
            ->withProperties(['revision' => $version->revision])
            ->log('document.downloaded');

        // The original filename is restored here for the user's convenience;
        // it was never used as a storage path.
        return $this->storage->disk()->download(
            $version->file_path,
            $version->original_filename,
        );
    }
}
