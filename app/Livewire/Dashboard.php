<?php

namespace App\Livewire;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\Review;
use App\Services\DashboardStatsService;
use App\Support\Permissions;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $stats = DashboardStatsService::for($user);

        return view('livewire.dashboard', [
            'stats' => $stats->totals(),
            'deadlines' => $stats->upcomingDeadlines(),
            'activities' => $user->can(Permissions::ACTIVITY_VIEW)
                ? $stats->recentActivity()
                : collect(),

            'recentDocuments' => $user->can(Permissions::DOCUMENTS_VIEW)
                ? Document::query()
                    ->with(['project:id,project_code', 'discipline:id,code'])
                    ->latest('updated_at')
                    ->limit(6)
                    ->get()
                : collect(),

            // The reviewer's own queue, which is what they open this page for.
            'pendingReviews' => $user->can(Permissions::REVIEWS_VIEW)
                ? Review::query()
                    ->with(['documentVersion.document:id,document_number,title'])
                    ->where('reviewer_id', $user->id)
                    ->open()
                    ->orderByRaw('deadline is null')
                    ->orderBy('deadline')
                    ->limit(5)
                    ->get()
                : collect(),

            'statuses' => DocumentStatus::cases(),
        ])->title(__('dashboard.title'));
    }
}
