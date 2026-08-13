<?php

namespace App\Livewire\Reviews;

use App\Enums\ReviewStatus;
use App\Models\Review;
use App\Services\DocumentStorage;
use App\Services\ReviewService;
use Flux\Flux;
use Illuminate\View\View;
use Livewire\Component;

class Show extends Component
{
    public Review $review;

    public string $summary = '';

    public string $comment = '';

    public ?int $replyingTo = null;

    public string $replyBody = '';

    public function mount(Review $review): void
    {
        $this->authorize('view', $review);

        $this->review = $review->load([
            'reviewer:id,name,avatar_path',
            'assigner:id,name',
            'documentVersion',
            'documentVersion.document.project:id,project_code,name',
            'documentVersion.document.discipline:id,code,name',
            'documentVersion.document.creator:id,name,avatar_path',
        ]);

        $this->summary = $review->summary ?? '';

        // Opening the review is what moves it from Pending to In Progress —
        // it reflects reality better than a button the reviewer must click.
        if ($this->canDecide()) {
            app(ReviewService::class)->start($this->review, auth()->user());
            $this->review->refresh();
        }
    }

    public function canDecide(): bool
    {
        return auth()->user()->can('decide', $this->review);
    }

    public function document()
    {
        return $this->review->documentVersion?->document;
    }

    /*
    |--------------------------------------------------------------------------
    | Verdicts (§23)
    |--------------------------------------------------------------------------
    */

    public function approve(ReviewService $reviews): void
    {
        $this->decide($reviews, ReviewStatus::Approved);
    }

    public function requestRevision(ReviewService $reviews): void
    {
        $this->decide($reviews, ReviewStatus::RevisionRequested);
    }

    public function reject(ReviewService $reviews): void
    {
        $this->decide($reviews, ReviewStatus::Rejected);
    }

    private function decide(ReviewService $reviews, ReviewStatus $verdict): void
    {
        // Policy re-checked here, not just when rendering buttons (§39).
        $this->authorize('decide', $this->review);

        /*
        | A rejection or revision request without a written reason is unusable
        | to the engineer who has to act on it, so the summary is mandatory
        | for those two outcomes but optional for an approval.
        */
        if ($verdict !== ReviewStatus::Approved) {
            $this->validate(
                ['summary' => ['required', 'string', 'min:5', 'max:5000']],
                ['summary.required' => __('reviews.confirm.summary_required')],
                ['summary' => __('reviews.fields.summary')],
            );
        }

        $reviews->decide($this->review, auth()->user(), $verdict, $this->summary);

        $this->review->refresh();

        $this->modal('confirm-'.$verdict->value)->close();

        Flux::toast(
            text: match ($verdict) {
                ReviewStatus::Approved => __('reviews.messages.approved'),
                ReviewStatus::RevisionRequested => __('reviews.messages.revision_requested'),
                default => __('reviews.messages.rejected'),
            },
            variant: $verdict === ReviewStatus::Approved ? 'success' : 'warning',
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Comments (§25)
    |--------------------------------------------------------------------------
    */

    public function addComment(ReviewService $reviews): void
    {
        $this->authorize('comment', $this->review);

        $this->validate(
            ['comment' => ['required', 'string', 'max:5000']],
            attributes: ['comment' => __('reviews.comments.title')],
        );

        $reviews->addComment($this->review, auth()->user(), $this->comment);

        $this->reset('comment');

        Flux::toast(text: __('reviews.comments.added'), variant: 'success');
    }

    public function reply(ReviewService $reviews): void
    {
        $this->authorize('comment', $this->review);

        $this->validate(
            ['replyBody' => ['required', 'string', 'max:5000']],
            attributes: ['replyBody' => __('reviews.comments.reply')],
        );

        $reviews->addComment($this->review, auth()->user(), $this->replyBody, parentId: $this->replyingTo);

        $this->reset('replyBody', 'replyingTo');
    }

    public function resolveComment(int $commentId, ReviewService $reviews): void
    {
        $this->authorize('resolveComment', $this->review);

        $comment = $this->review->comments()->findOrFail($commentId);

        $reviews->resolveComment($comment, auth()->user());

        Flux::toast(text: __('reviews.comments.resolved_message'), variant: 'success');
    }

    public function render(): View
    {
        $comments = $this->review->comments()
            // 'author.roles' so <x-comment>'s role badge doesn't lazy-load
            // per row (§40) — see User::primaryRole().
            ->with(['author:id,name,avatar_path', 'author.roles', 'resolver:id,name', 'replies.author:id,name,avatar_path', 'replies.author.roles'])
            ->whereNull('parent_id')
            ->oldest()
            ->get();

        return view('livewire.reviews.show', [
            'comments' => $comments,
            'openComments' => $comments->where('resolved', false)->count(),
            'version' => $this->review->documentVersion,
            'document' => $this->document(),
            'storage' => app(DocumentStorage::class),
        ])->title(__('reviews.singular').' — '.($this->document()?->document_number ?? ''));
    }
}
