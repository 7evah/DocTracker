<?php

namespace App\Livewire\Documents;

use App\Enums\DocumentStatus;
use App\Enums\Priority;
use App\Models\Document;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use Flux\Flux;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Assigns reviewers to a document's current revision (§23).
 *
 * Lives as its own component so the document page does not carry review
 * concerns, and so the same modal can be reused elsewhere later.
 */
class AssignReviewers extends Component
{
    public Document $document;

    /** @var array<int, string> */
    public array $reviewers = [];

    public string $priority = Priority::Medium->value;

    public string $deadline = '';

    public function mount(Document $document): void
    {
        $this->document = $document;
        $this->applyDefaultDeadline();

        // Pre-tick whoever is already assigned, so the modal doubles as
        // "change reviewers" rather than only "add".
        $this->reviewers = $this->currentReviews()
            ->pluck('reviewer_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /** Deadline follows the priority unless the user has typed their own. */
    public function updatedPriority(): void
    {
        $this->applyDefaultDeadline();
    }

    private function applyDefaultDeadline(): void
    {
        $priority = Priority::tryFrom($this->priority) ?? Priority::Medium;

        $this->deadline = now()->addDays($priority->defaultTurnaroundDays())->toDateString();
    }

    private function currentReviews()
    {
        $version = $this->document->latestVersion;

        return $version
            ? Review::where('document_version_id', $version->id)->get()
            : collect();
    }

    public function save(ReviewService $reviews): void
    {
        $this->authorize('assign', Review::class);

        $validated = $this->validate([
            'reviewers' => ['required', 'array', 'min:1'],
            'reviewers.*' => [Rule::exists('users', 'id')],
            'priority' => ['required', Rule::enum(Priority::class)],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
        ], attributes: [
            'reviewers' => __('reviews.assign.reviewers'),
            'priority' => __('reviews.fields.priority'),
            'deadline' => __('reviews.fields.deadline'),
        ]);

        $version = $this->document->latestVersion;

        if (! $version) {
            Flux::toast(text: __('documents.no_versions'), variant: 'danger');

            return;
        }

        // Status rule enforced server-side (§39).
        if (! in_array($this->document->status, [
            DocumentStatus::Draft,
            DocumentStatus::NeedsRevision,
            DocumentStatus::UnderReview,
        ], true)) {
            Flux::toast(text: __('reviews.messages.assign_blocked'), variant: 'danger');

            return;
        }

        $assigned = $reviews->assign(
            version: $version,
            reviewers: User::whereKey($validated['reviewers'])->get(),
            assigner: auth()->user(),
            priority: Priority::from($validated['priority']),
            deadline: $validated['deadline'] ? Carbon::parse($validated['deadline']) : null,
        );

        $this->modal('assign-reviewers')->close();

        Flux::toast(
            text: trans_choice('reviews.messages.assigned', $assigned->count(), ['count' => $assigned->count()]),
            variant: 'success',
        );

        // Tell the parent page to re-render with the new reviews.
        $this->dispatch('reviews-assigned');
    }

    #[On('reviews-assigned')]
    public function refreshState(): void
    {
        $this->document->refresh();
    }

    public function render(ReviewService $reviews): View
    {
        return view('livewire.documents.assign-reviewers', [
            'candidates' => $reviews->candidates($this->document),
            'priorities' => Priority::options(),
        ]);
    }
}
