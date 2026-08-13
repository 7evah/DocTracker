<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Livewire\Approvals\Index as ApprovalIndex;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Create as DocumentCreate;
use App\Livewire\Documents\Edit as DocumentEdit;
use App\Livewire\Documents\Index as DocumentIndex;
use App\Livewire\Documents\Show as DocumentShow;
use App\Livewire\Projects\Form as ProjectForm;
use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Projects\Show as ProjectShow;
use App\Livewire\Reviews\Index as ReviewIndex;
use App\Livewire\Reviews\Show as ReviewShow;
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

    /*
    |----------------------------------------------------------------------
    | Projects
    |----------------------------------------------------------------------
    | Authorization is enforced by ProjectPolicy inside each component, so a
    | crafted request cannot bypass it by skipping route middleware (§13).
    */
    Route::get('projects', ProjectIndex::class)->name('projects.index');
    Route::get('projects/create', ProjectForm::class)->name('projects.create');
    Route::get('projects/{project}', ProjectShow::class)->name('projects.show');
    Route::get('projects/{project}/edit', ProjectForm::class)->name('projects.edit');

    /*
    |----------------------------------------------------------------------
    | Documents
    |----------------------------------------------------------------------
    */
    Route::get('documents', DocumentIndex::class)->name('documents.index');
    Route::get('documents/create', DocumentCreate::class)->name('documents.create');
    Route::get('documents/{document}', DocumentShow::class)->name('documents.show');
    Route::get('documents/{document}/edit', DocumentEdit::class)->name('documents.edit');

    /*
    | The only way a stored file leaves the server. The version is bound
    | alongside the document so the controller can verify it belongs to it,
    | rather than trusting a bare version id (§32, §53).
    */
    Route::get('documents/{document}/versions/{version}/download', DocumentDownloadController::class)
        ->name('documents.download');

    /*
    |----------------------------------------------------------------------
    | Reviews
    |----------------------------------------------------------------------
    */
    Route::get('reviews', ReviewIndex::class)->name('reviews.index');
    Route::get('reviews/{review}', ReviewShow::class)->name('reviews.show');

    /*
    |----------------------------------------------------------------------
    | Approvals
    |----------------------------------------------------------------------
    | Signing happens on the document's Approvals tab, where the full circuit
    | is visible; this queue is the approver's way in (§24).
    */
    Route::get('approvals', ApprovalIndex::class)->name('approvals.index');

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
