<?php

namespace App\Services;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Builds the dashboard KPI set (§17).
 *
 * Kept out of the Livewire component so the same numbers can be reused by
 * reports and exports later without duplicating query logic (§5).
 *
 * Counts for tables that do not exist yet resolve to 0 rather than throwing,
 * which lets the dashboard ship before the Documents/Reviews phases land.
 * Each guard disappears as its module arrives.
 */
class DashboardStatsService
{
    public function __construct(private readonly User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        return [
            'projects' => $this->countProjects(),
            'documents' => $this->countIfExists('documents'),
            'pending_reviews' => $this->countIfExists('reviews', fn ($q) => $q->where('status', 'pending')),
            'pending_approvals' => $this->countIfExists('approvals', fn ($q) => $q->where('status', 'pending')),
            'approved_documents' => $this->countIfExists('documents', fn ($q) => $q->where('status', 'approved')),
            'needs_revision' => $this->countIfExists('documents', fn ($q) => $q->where('status', 'needs_revision')),
            'overdue_reviews' => $this->countIfExists(
                'reviews',
                fn ($q) => $q->where('status', 'pending')->whereNotNull('deadline')->where('deadline', '<', now())
            ),
            'overdue_approvals' => $this->countIfExists(
                'approvals',
                fn ($q) => $q->where('status', 'pending')->whereNotNull('deadline')->where('deadline', '<', now())
            ),
        ];
    }

    private function countProjects(): int
    {
        if (! Schema::hasColumn('projects', 'status')) {
            return Project::query()->count();
        }

        return Project::query()->where('status', '!=', 'closed')->count();
    }

    /**
     * @param  callable(Builder): Builder|null  $constrain
     */
    private function countIfExists(string $table, ?callable $constrain = null): int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        if ($constrain) {
            $query = $constrain($query);
        }

        return (int) $query->count();
    }

    /** @var array<string, bool> */
    private array $tableCache = [];

    /** Memoised so one dashboard render issues at most one check per table. */
    private function tableExists(string $table): bool
    {
        return $this->tableCache[$table] ??= Schema::hasTable($table);
    }
}
