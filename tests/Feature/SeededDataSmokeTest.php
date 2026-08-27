<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Review;
use App\Models\User;
use App\Support\Permissions;
use Database\Seeders\ApprovalSeeder;
use Database\Seeders\DemoUserSeeder;
use Database\Seeders\DisciplineSeeder;
use Database\Seeders\DocumentSeeder;
use Database\Seeders\NotificationSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\ReviewSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\TaskSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Renders every page against the fully-populated demo dataset, not synthetic
 * factories.
 *
 * DatabaseSeeder no longer runs this chain by default (a fresh install now
 * starts with logins but no business data — see FreshInstallTest), so this
 * test calls each showcase seeder explicitly. It still earns its keep: this
 * is what caught the LazyLoadingViolationException on /reviews/1 in
 * practice — the seeder produces comment authors, reviewers and disciplines
 * with the exact relation-loading shape a real demo run has, which
 * factory-built single-record tests do not always reproduce.
 */
class SeededDataSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('documents');

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DisciplineSeeder::class);
        $this->seed(DemoUserSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(DocumentSeeder::class);
        $this->seed(ReviewSeeder::class);
        $this->seed(ApprovalSeeder::class);
        $this->seed(TaskSeeder::class);
        $this->seed(NotificationSeeder::class);
    }

    public function test_every_seeded_review_renders_for_its_reviewer(): void
    {
        $reviews = Review::with('reviewer')->get();

        $this->assertGreaterThan(0, $reviews->count(), 'Expected the seeder to produce reviews.');

        foreach ($reviews as $review) {
            $this->actingAs($review->reviewer)
                ->get(route('reviews.show', $review))
                ->assertOk();
        }
    }

    public function test_every_seeded_document_renders_with_every_tab(): void
    {
        $admin = User::where('email', 'adminjesa@yopmail.com')->firstOrFail();

        $documents = Document::all();
        $this->assertGreaterThan(0, $documents->count(), 'Expected the seeder to produce documents.');

        foreach ($documents as $document) {
            foreach (['overview', 'revisions', 'reviews', 'comments', 'activity'] as $tab) {
                $this->actingAs($admin)
                    ->get(route('documents.show', $document).'?tab='.$tab)
                    ->assertOk();
            }
        }
    }

    public function test_the_reviews_and_documents_index_render_for_every_demo_role(): void
    {
        foreach (User::whereNotNull('email')->get() as $user) {
            if (! $user->can(Permissions::REVIEWS_VIEW)) {
                continue;
            }

            $this->actingAs($user)->get(route('reviews.index'))->assertOk();
            $this->actingAs($user)->get(route('documents.index'))->assertOk();
        }
    }
}
