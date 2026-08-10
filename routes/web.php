<?php

use App\Livewire\Dashboard;
use App\Support\Permissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => Auth::check() ? redirect()->route('dashboard') : redirect()->route('login'))
    ->name('home');

/*
|--------------------------------------------------------------------------
| Application
|--------------------------------------------------------------------------
|
| Every module below is reachable from the sidebar. Modules not yet built
| render the shared "placeholder" view so navigation is complete and
| demonstrable from day one; each is swapped for its real Livewire page as
| that phase lands (§48 MVP priority order).
|
*/

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::view('profile', 'profile')->name('profile');

    Route::post('logout', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');

    // --- Phase: Projects -------------------------------------------------
    Route::view('projects', 'placeholder', [
        'module' => 'projects',
        'icon' => 'folder',
    ])->middleware('can:'.Permissions::PROJECTS_VIEW)->name('projects.index');

    // --- Phase: Documents ------------------------------------------------
    Route::view('documents', 'placeholder', [
        'module' => 'documents',
        'icon' => 'document-text',
    ])->middleware('can:'.Permissions::DOCUMENTS_VIEW)->name('documents.index');

    // --- Phase: Reviews --------------------------------------------------
    Route::view('reviews', 'placeholder', [
        'module' => 'reviews',
        'icon' => 'eye',
    ])->middleware('can:'.Permissions::REVIEWS_VIEW)->name('reviews.index');

    // --- Phase: Approvals ------------------------------------------------
    Route::view('approvals', 'placeholder', [
        'module' => 'approvals',
        'icon' => 'check-badge',
    ])->middleware('can:'.Permissions::APPROVALS_VIEW)->name('approvals.index');

    // --- Phase: Tasks ----------------------------------------------------
    Route::view('tasks', 'placeholder', [
        'module' => 'tasks',
        'icon' => 'clipboard-document-check',
    ])->middleware('can:'.Permissions::TASKS_VIEW)->name('tasks.index');

    // --- Phase: Reports --------------------------------------------------
    Route::view('reports', 'placeholder', [
        'module' => 'reports',
        'icon' => 'chart-bar',
    ])->middleware('can:'.Permissions::REPORTS_VIEW)->name('reports.index');

    // --- Phase: Notifications --------------------------------------------
    Route::view('notifications', 'placeholder', [
        'module' => 'notifications',
        'icon' => 'bell',
    ])->name('notifications.index');

    /*
    |----------------------------------------------------------------------
    | Administration
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::view('users', 'placeholder', [
            'module' => 'users',
            'icon' => 'users',
        ])->middleware('can:'.Permissions::USERS_MANAGE)->name('users');

        Route::view('roles', 'placeholder', [
            'module' => 'roles',
            'icon' => 'key',
        ])->middleware('can:'.Permissions::USERS_MANAGE)->name('roles');

        Route::view('disciplines', 'placeholder', [
            'module' => 'disciplines',
            'icon' => 'squares-2x2',
        ])->middleware('can:'.Permissions::DISCIPLINES_MANAGE)->name('disciplines');

        Route::view('settings', 'placeholder', [
            'module' => 'settings',
            'icon' => 'cog-6-tooth',
        ])->middleware('can:'.Permissions::SETTINGS_MANAGE)->name('settings');
    });
});

require __DIR__.'/auth.php';
