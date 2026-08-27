<?php

namespace Tests\Feature\Reviews;

use App\Enums\DocumentStatus;
use App\Enums\Priority;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Livewire\Documents\AssignReviewers;
use App\Livewire\Reviews\Index as ReviewIndex;
use App\Livewire\Reviews\Show as ReviewShow;
use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Models\Review;
use App\Models\User;
use App\Notifications\DocumentSubmittedForReview;
use App\Notifications\ReviewAssigned;
use App\Notifications\ReviewCompleted;
use App\Services\DocumentService;
use App\Services\ReviewService;
use App\Support\Permissions;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');
        Notification::fake();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
    }

    private function userWithRole(UserRole $role, string $name = 'Utilisateur'): User
    {
        $user = User::factory()->create(['status' => UserStatus::Active, 'name' => $name]);
        $user->assignRole($role->value);

        return $user;
    }

    private function documentFor(User $author, string $number = 'ME-1023'): Document
    {
        return app(DocumentService::class)->create(
            attributes: [
                'project_id' => Project::factory()->create()->id,
                'discipline_id' => Discipline::first()->id,
                'document_number' => $number,
                'title' => 'Document de test',
                'current_revision' => 'A',
            ],
            file: UploadedFile::fake()->create('plan.pdf', 100, 'application/pdf'),
            author: $author,
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Submitting a revision (§23, §26)
    |--------------------------------------------------------------------------
    */

    /**
     * Submitting used to be silent: the status changed and nobody was told, so
     * a revision could sit in review with no reviewer for as long as it took
     * somebody to notice by chance.
     */
    public function test_submitting_a_revision_notifies_the_project_manager(): void
    {
        Notification::fake();

        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);

        $document = $this->documentFor($engineer);
        $document->project->forceFill(['manager_id' => $manager->id])->save();

        app(DocumentService::class)->submitForReview($document->fresh(), $engineer);

        Notification::assertSentTo(
            $manager,
            DocumentSubmittedForReview::class,
            fn (DocumentSubmittedForReview $n) => $n->reviewersCarriedForward === false,
        );
    }

    /** Nobody is told about their own action, as elsewhere in the app. */
    public function test_a_manager_submitting_their_own_document_is_not_notified(): void
    {
        Notification::fake();

        $manager = $this->userWithRole(UserRole::ProjectManager);

        $document = $this->documentFor($manager);
        $document->project->forceFill(['manager_id' => $manager->id])->save();

        app(DocumentService::class)->submitForReview($document->fresh(), $manager);

        Notification::assertNotSentTo($manager, DocumentSubmittedForReview::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Standing reviewers (§23)
    |--------------------------------------------------------------------------
    */

    public function test_a_standing_reviewer_is_reassigned_to_the_next_revision(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $reviewer = $this->userWithRole(UserRole::Reviewer);

        $document = $this->documentFor($engineer);
        $document->project->forceFill(['manager_id' => $manager->id])->save();

        Livewire::actingAs($manager)
            ->test(AssignReviewers::class, ['document' => $document])
            ->set('reviewers', [(string) $reviewer->id])
            ->set('scope', 'document')
            ->call('save')
            ->assertHasNoErrors();

        app(DocumentService::class)->addRevision(
            $document->fresh(),
            UploadedFile::fake()->create('rev-b.pdf', 100, 'application/pdf'),
            $engineer,
        );

        $document->refresh();
        app(DocumentService::class)->submitForReview($document, $engineer);

        $revisionB = $document->fresh()->latestVersion;

        $this->assertSame('B', $revisionB->revision);
        $this->assertTrue(
            $revisionB->reviews()->where('reviewer_id', $reviewer->id)->exists(),
            'The standing reviewer should have been re-assigned to revision B.',
        );
    }

    /** The default is one revision only, so nothing is inherited silently. */
    public function test_a_single_revision_reviewer_is_not_carried_forward(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $reviewer = $this->userWithRole(UserRole::Reviewer);

        $document = $this->documentFor($engineer);
        $document->project->forceFill(['manager_id' => $manager->id])->save();

        Livewire::actingAs($manager)
            ->test(AssignReviewers::class, ['document' => $document])
            ->set('reviewers', [(string) $reviewer->id])
            ->set('scope', 'version')
            ->call('save')
            ->assertHasNoErrors();

        app(DocumentService::class)->addRevision(
            $document->fresh(),
            UploadedFile::fake()->create('rev-b.pdf', 100, 'application/pdf'),
            $engineer,
        );

        $document->refresh();
        app(DocumentService::class)->submitForReview($document, $engineer);

        $this->assertSame(0, $document->fresh()->latestVersion->reviews()->count());
    }

    /** Which of the two happened decides whether the manager must act, so say so. */
    public function test_the_manager_is_told_when_standing_reviewers_were_kept(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $reviewer = $this->userWithRole(UserRole::Reviewer);

        $document = $this->documentFor($engineer);
        $document->project->forceFill(['manager_id' => $manager->id])->save();

        app(ReviewService::class)->assign(
            version: $document->latestVersion,
            reviewers: [$reviewer],
            assigner: $manager,
            carryForward: true,
        );

        app(DocumentService::class)->addRevision(
            $document->fresh(),
            UploadedFile::fake()->create('rev-b.pdf', 100, 'application/pdf'),
            $engineer,
        );

        Notification::fake();

        $document->refresh();
        app(DocumentService::class)->submitForReview($document, $engineer);

        Notification::assertSentTo(
            $manager,
            DocumentSubmittedForReview::class,
            fn (DocumentSubmittedForReview $n) => $n->reviewersCarriedForward === true,
        );
    }

    /** An explicit choice for this revision is never overwritten by an inherited one. */
    public function test_carry_forward_does_not_override_an_explicit_assignment(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $standing = $this->userWithRole(UserRole::Reviewer, 'Permanent');
        $chosen = $this->userWithRole(UserRole::Reviewer, 'Ponctuel');

        $document = $this->documentFor($engineer);
        $document->project->forceFill(['manager_id' => $manager->id])->save();

        app(ReviewService::class)->assign(
            version: $document->latestVersion,
            reviewers: [$standing],
            assigner: $manager,
            carryForward: true,
        );

        app(DocumentService::class)->addRevision(
            $document->fresh(),
            UploadedFile::fake()->create('rev-b.pdf', 100, 'application/pdf'),
            $engineer,
        );

        $document->refresh();
        $revisionB = $document->latestVersion;

        // The manager picks somebody else for this one revision before submit.
        app(ReviewService::class)->assign(
            version: $revisionB,
            reviewers: [$chosen],
            assigner: $manager,
        );

        app(DocumentService::class)->submitForReview($document->fresh(), $engineer);

        $this->assertSame([$chosen->id], $revisionB->reviews()->pluck('reviewer_id')->all());
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment (§23)
    |--------------------------------------------------------------------------
    */

    public function test_assigning_a_reviewer_moves_the_document_into_review_and_notifies(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer, 'Youssef Amrani');
        $manager = $this->userWithRole(UserRole::ProjectManager, 'Nadia Benchekroun');
        $reviewer = $this->userWithRole(UserRole::Reviewer, 'Karim Oulhaj');

        $document = $this->documentFor($engineer);

        Livewire::actingAs($manager)
            ->test(AssignReviewers::class, ['document' => $document])
            ->set('reviewers', [(string) $reviewer->id])
            ->set('priority', Priority::High->value)
            ->call('save')
            ->assertHasNoErrors();

        $review = Review::first();

        $this->assertNotNull($review);
        $this->assertSame($reviewer->id, $review->reviewer_id);
        $this->assertSame($manager->id, $review->assigned_by);
        $this->assertSame(ReviewStatus::Pending, $review->status);
        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);

        Notification::assertSentTo($reviewer, ReviewAssigned::class);
    }

    public function test_the_deadline_defaults_from_the_priority(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $reviewer = $this->userWithRole(UserRole::Reviewer);

        $document = $this->documentFor($engineer);

        app(ReviewService::class)->assign(
            version: $document->latestVersion,
            reviewers: [$reviewer],
            assigner: $manager,
            priority: Priority::Critical,
        );

        // Critical turns around in one day.
        $this->assertSame(
            now()->addDay()->toDateString(),
            Review::first()->deadline->toDateString(),
        );
    }

    public function test_reassigning_the_same_reviewer_updates_rather_than_duplicates(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $reviewer = $this->userWithRole(UserRole::Reviewer);

        $document = $this->documentFor($engineer);
        $service = app(ReviewService::class);

        $service->assign($document->latestVersion, [$reviewer], $manager, Priority::Low);
        $service->assign($document->latestVersion, [$reviewer], $manager, Priority::Critical);

        // The unique(version, reviewer) index must not be violated.
        $this->assertSame(1, Review::count());
        $this->assertSame(Priority::Critical, Review::first()->priority);
    }

    public function test_an_engineer_cannot_assign_reviewers(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);

        $this->assertFalse($engineer->can('assign', Review::class));
    }

    /** Nobody reviews their own drawing. */
    public function test_the_author_is_excluded_from_the_candidate_list(): void
    {
        $author = $this->userWithRole(UserRole::Reviewer, 'Auteur Vérificateur');
        $other = $this->userWithRole(UserRole::Reviewer, 'Autre Vérificateur');

        $document = $this->documentFor($author);

        $candidates = app(ReviewService::class)->candidates($document);

        $this->assertTrue($candidates->contains($other));
        $this->assertFalse($candidates->contains($author));
    }

    /*
    |--------------------------------------------------------------------------
    | Verdicts and rollup (§7, §23)
    |--------------------------------------------------------------------------
    */

    public function test_approval_by_the_only_reviewer_approves_the_document(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::Approved);

        $this->assertSame(DocumentStatus::Approved, $document->fresh()->status);
        $this->assertNotNull($review->fresh()->reviewed_at);
    }

    public function test_requesting_a_revision_moves_the_document_to_needs_revision(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::RevisionRequested, 'Cotes manquantes.');

        $this->assertSame(DocumentStatus::NeedsRevision, $document->fresh()->status);
    }

    public function test_a_rejection_moves_the_document_to_rejected(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::Rejected, 'Non conforme.');

        $this->assertSame(DocumentStatus::Rejected, $document->fresh()->status);
    }

    /**
     * With several reviewers the document must not be promoted until every
     * verdict is in.
     */
    public function test_the_document_stays_under_review_until_all_reviewers_have_answered(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $first = $this->userWithRole(UserRole::Reviewer, 'Premier');
        $second = $this->userWithRole(UserRole::Reviewer, 'Second');

        $document = $this->documentFor($engineer);
        $service = app(ReviewService::class);

        $service->assign($document->latestVersion, [$first, $second], $manager);

        $reviewOfFirst = Review::where('reviewer_id', $first->id)->first();
        $service->decide($reviewOfFirst, $first, ReviewStatus::Approved);

        $this->assertSame(DocumentStatus::UnderReview, $document->fresh()->status);

        $reviewOfSecond = Review::where('reviewer_id', $second->id)->first();
        $service->decide($reviewOfSecond, $second, ReviewStatus::Approved);

        $this->assertSame(DocumentStatus::Approved, $document->fresh()->status);
    }

    /** A single rejection outranks any number of approvals. */
    public function test_one_rejection_outranks_approvals(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $first = $this->userWithRole(UserRole::Reviewer, 'Premier');
        $second = $this->userWithRole(UserRole::Reviewer, 'Second');

        $document = $this->documentFor($engineer);
        $service = app(ReviewService::class);

        $service->assign($document->latestVersion, [$first, $second], $manager);

        $service->decide(Review::where('reviewer_id', $first->id)->first(), $first, ReviewStatus::Approved);
        $service->decide(Review::where('reviewer_id', $second->id)->first(), $second, ReviewStatus::Rejected, 'Non conforme.');

        $this->assertSame(DocumentStatus::Rejected, $document->fresh()->status);
    }

    public function test_the_author_is_notified_of_the_verdict(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::RevisionRequested, 'Cotes manquantes.');

        Notification::assertSentTo($document->creator, ReviewCompleted::class);
    }

    public function test_the_verdict_is_written_to_the_audit_trail(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::Approved);

        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $document->id,
            'causer_id' => $reviewer->id,
            'description' => 'document.review_approved',
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization (§13)
    |--------------------------------------------------------------------------
    */

    /**
     * A verdict is signed evidence of who checked what, so another reviewer
     * holding the same permission must not be able to answer on someone
     * else's behalf.
     */
    public function test_only_the_assigned_reviewer_can_give_a_verdict(): void
    {
        [, $review, $assigned] = $this->assignedReview();

        $other = $this->userWithRole(UserRole::Reviewer, 'Autre');

        $this->assertTrue($assigned->can('decide', $review));
        $this->assertFalse($other->can('decide', $review));
    }

    public function test_a_completed_review_cannot_be_decided_again(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::Approved);

        $this->assertFalse($reviewer->can('decide', $review->fresh()));
    }

    public function test_a_non_assigned_user_cannot_decide_through_the_component(): void
    {
        [, $review] = $this->assignedReview();

        $other = $this->userWithRole(UserRole::Reviewer, 'Intrus');

        Livewire::actingAs($other)
            ->test(ReviewShow::class, ['review' => $review])
            ->call('approve')
            ->assertForbidden();

        $this->assertSame(ReviewStatus::Pending, $review->fresh()->status);
    }

    /** A rejection without a written reason is unusable to the engineer. */
    public function test_a_summary_is_required_to_request_a_revision(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        Livewire::actingAs($reviewer)
            ->test(ReviewShow::class, ['review' => $review])
            ->set('summary', '')
            ->call('requestRevision')
            ->assertHasErrors('summary');

        $this->assertSame(ReviewStatus::InProgress, $review->fresh()->status);
    }

    public function test_an_approval_does_not_require_a_summary(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        Livewire::actingAs($reviewer)
            ->test(ReviewShow::class, ['review' => $review])
            ->set('summary', '')
            ->call('approve')
            ->assertHasNoErrors();

        $this->assertSame(ReviewStatus::Approved, $review->fresh()->status);
    }

    /** Opening the review is what marks it in progress. */
    public function test_opening_a_review_marks_it_in_progress(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        $this->assertSame(ReviewStatus::Pending, $review->status);

        Livewire::actingAs($reviewer)->test(ReviewShow::class, ['review' => $review]);

        $this->assertSame(ReviewStatus::InProgress, $review->fresh()->status);
    }

    /*
    |--------------------------------------------------------------------------
    | Comments (§25)
    |--------------------------------------------------------------------------
    */

    public function test_a_comment_can_be_added_and_resolved(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        Livewire::actingAs($reviewer)
            ->test(ReviewShow::class, ['review' => $review])
            ->set('comment', 'Les cotes de la vue en plan sont incohérentes.')
            ->call('addComment')
            ->assertHasNoErrors();

        $comment = $review->comments()->first();

        $this->assertNotNull($comment);
        $this->assertFalse($comment->resolved);

        Livewire::actingAs($reviewer)
            ->test(ReviewShow::class, ['review' => $review])
            ->call('resolveComment', $comment->id);

        $comment->refresh();

        $this->assertTrue($comment->resolved);
        // Resolution records who and when, for the audit trail (§34).
        $this->assertSame($reviewer->id, $comment->resolved_by);
        $this->assertNotNull($comment->resolved_at);
    }

    public function test_a_viewer_cannot_resolve_a_comment(): void
    {
        [, $review] = $this->assignedReview();

        $viewer = $this->userWithRole(UserRole::Viewer);

        $this->assertFalse($viewer->can('resolveComment', $review));
    }

    /**
     * Regression: a comment authored by someone other than the viewer used
     * to throw LazyLoadingViolationException. The author is a User instance
     * pulled through an Eloquent relation, distinct from auth()->user(),
     * whose `roles` was never implicitly loaded — unlike the acting user's,
     * which Gate::before warms via Spatie's hasRole(). Exercised over the
     * real HTTP route, matching how the bug was actually hit.
     */
    public function test_viewing_a_review_with_a_comment_from_another_user_does_not_crash(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        $commenter = $this->userWithRole(UserRole::ProjectManager, 'Autre Intervenant');
        app(ReviewService::class)->addComment($review, $commenter, 'Une remarque de quelqu’un d’autre.');

        $this->actingAs($reviewer)
            ->get(route('reviews.show', $review))
            ->assertOk()
            ->assertSee('Une remarque de quelqu’un d’autre.')
            ->assertSee('Autre Intervenant');
    }

    /** Same regression, via the document page's Comments tab. */
    public function test_document_comments_tab_with_another_users_comment_does_not_crash(): void
    {
        [$document, $review, $reviewer] = $this->assignedReview();

        $commenter = $this->userWithRole(UserRole::ProjectManager, 'Autre Intervenant');
        app(ReviewService::class)->addComment($review, $commenter, 'Une remarque sur le document.');

        $this->actingAs($document->creator)
            ->get(route('documents.show', $document).'?tab=comments')
            ->assertOk();
    }

    /**
     * Same regression, for the reviewer candidate list on the assign modal.
     * The modal's content lives in the DOM (Flux toggles it with a native
     * <dialog>), so assertSee still finds it without opening the modal.
     */
    public function test_reviewer_candidate_list_does_not_crash_on_role_badges(): void
    {
        $engineer = $this->userWithRole(UserRole::Engineer);
        $manager = $this->userWithRole(UserRole::ProjectManager);
        $candidate = $this->userWithRole(UserRole::Reviewer, 'Candidat Vérificateur');

        $document = $this->documentFor($engineer);

        Livewire::actingAs($manager)
            ->test(AssignReviewers::class, ['document' => $document])
            ->assertSee($candidate->name)
            ->assertSee(__('enums.role.reviewer'));
    }

    /*
    |--------------------------------------------------------------------------
    | Reviewer queue (§23)
    |--------------------------------------------------------------------------
    */

    public function test_the_queue_defaults_to_reviews_assigned_to_the_current_user(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        $otherReviewer = $this->userWithRole(UserRole::Reviewer, 'Autre');

        Livewire::actingAs($reviewer)
            ->test(ReviewIndex::class)
            ->assertSet('scope', 'mine')
            ->assertSee($review->documentVersion->document->document_number);

        Livewire::actingAs($otherReviewer)
            ->test(ReviewIndex::class)
            ->assertDontSee($review->documentVersion->document->document_number);
    }

    public function test_a_reviewer_cannot_widen_the_scope_to_all_reviews(): void
    {
        [, , $reviewer] = $this->assignedReview();

        // Even if the property is forced, the query stays scoped to the user.
        Livewire::actingAs($reviewer)
            ->test(ReviewIndex::class)
            ->assertSet('scope', 'mine');

        $this->assertFalse($reviewer->can(Permissions::REVIEWS_ASSIGN));
    }

    public function test_the_overdue_filter_only_returns_open_past_due_reviews(): void
    {
        [, $review, $reviewer] = $this->assignedReview();

        $review->forceFill(['deadline' => now()->subDays(3)])->save();

        $this->assertSame(1, Review::query()->overdue()->count());

        app(ReviewService::class)->decide($review, $reviewer, ReviewStatus::Approved);

        // A completed review is never overdue, however late it was.
        $this->assertSame(0, Review::query()->overdue()->count());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /** @return array{0: Document, 1: Review, 2: User} */
    private function assignedReview(): array
    {
        $engineer = $this->userWithRole(UserRole::Engineer, 'Youssef Amrani');
        $manager = $this->userWithRole(UserRole::ProjectManager, 'Nadia Benchekroun');
        $reviewer = $this->userWithRole(UserRole::Reviewer, 'Karim Oulhaj');

        $document = $this->documentFor($engineer);

        app(ReviewService::class)->assign($document->latestVersion, [$reviewer], $manager);

        return [$document->fresh(), Review::first(), $reviewer];
    }
}
