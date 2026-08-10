<?php

namespace App\Livewire;

use App\Services\DashboardStatsService;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        return view('livewire.dashboard', [
            'stats' => DashboardStatsService::for(auth()->user())->totals(),
        ])->title(__('dashboard.title'));
    }
}
