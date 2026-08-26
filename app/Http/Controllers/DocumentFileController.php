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
 *
 * Two dispositions, deliberately separate routes rather than one route with a
 * flag: an inline <object> preview and a real download are different actions
 * to a user and to the audit trail, and collapsing them caused a live bug —
 * the preview embed pointed at the attachment route, so merely opening a
 * review page fired a browser download (a Save-As window on Windows), left
 * the preview blank because browsers refuse to render an attachment inline,
 * and wrote a "downloaded" entry to the activity log on every page view.
 */
class DocumentFileController extends Controller
{
    public function __construct(private readonly DocumentStorage $storage) {}

    /**
     * Stream a revision as an attachment, and record it.
     *
     * The original filename is restored here for the user's convenience; it
     * was never used as a storage path.
     */
    public function download(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->guard($document, $version);

        activity('document')
            ->performedOn($document)
            ->causedBy(auth()->user())
            ->event('downloaded')
            ->withProperties(['revision' => $version->revision])
            ->log('document.downloaded');

        return $this->storage->disk()->download(
            $version->file_path,
            $version->original_filename,
        );
    }

    /**
     * Stream a revision inline, for the in-page viewer.
     *
     * Not logged: this fires automatically whenever a page holding the viewer
     * is rendered, so logging it would drown the document's real history in
     * noise rather than record a decision anyone made (§34).
     */
    public function preview(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->guard($document, $version);

        abort_unless(
            in_array($version->mime_type, config('documents.previewable_mimes'), true),
            404,
        );

        return $this->storage->disk()->response(
            $version->file_path,
            $version->original_filename,
            [
                'Content-Type' => $version->mime_type,
                // Explicit, rather than relying on response()'s default: an
                // attachment here is exactly the bug this method exists to fix.
                'Content-Disposition' => 'inline; filename="'.addslashes($version->original_filename).'"',
                // The viewer is same-origin only; nothing else may frame it.
                'X-Frame-Options' => 'SAMEORIGIN',
                'X-Content-Type-Options' => 'nosniff',
            ],
        );
    }

    /**
     * Shared gate. The nested binding is deliberate: passing both the document
     * and the version lets us verify the version actually belongs to that
     * document, so a valid version id cannot be paired with a document the
     * user happens to be allowed to read.
     */
    private function guard(Document $document, DocumentVersion $version): void
    {
        $this->authorize('download', $document);

        abort_unless($version->document_id === $document->id, 404);

        abort_unless($this->storage->exists($version), 404);
    }
}
