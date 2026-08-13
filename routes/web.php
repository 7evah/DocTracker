<?php

use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\ReportExportController;
use App\Livewire\Admin\Disciplines as AdminDisciplines;
use App\Livewire\Admin\Roles as AdminRoles;
use App\Livewire\Admin\Settings as AdminSettings;
use App\Livewire\Admin\Users\Index as AdminUsers;
use App\Livewire\Admin\Workflows as AdminWorkflows;
use App\Livewire\Approvals\Index as ApprovalIndex;
use App\Livewire\Dashboard;
use App\Livewire\Documents\Create as DocumentCreate;
use App\Livewire\Documents\Edit as DocumentEdit;
use App\Livewire\Documents\Index as DocumentIndex;
use App\Livewire\Documents\Show as DocumentShow;
use App\Livewire\Notifications\Index as NotificationIndex;
use App\Livewire\Projects\Form as ProjectForm;
use App\Livewire\Projects\Index as ProjectIndex;
use App\Livewire\Projects\Show as ProjectShow;
use App\Livewire\Reports\Index as ReportIndex;
use App\Livewire\Reviews\Index as ReviewIndex;
use App\Livewire\Reviews\Show as ReviewShow;
use App\Livewire\Tasks\Index as TaskIndex;
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
| Every sidebar destination is a real page. Authorization is enforced inside
| each component rather than only by route middleware, so a crafted request
| cannot slip past by reaching a component directly (§13, §39).
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

    /*
    |----------------------------------------------------------------------
    | Tasks
    |----------------------------------------------------------------------
    | A single list page; creating and editing happen in a shared dialog that
    | the document and project pages reuse (§27).
    */
    Route::get('tasks', TaskIndex::class)->name('tasks.index');

    /*
    |----------------------------------------------------------------------
    | Reports
    |----------------------------------------------------------------------
    | The export route takes the same filters the page holds, so a download
    | always matches what was on screen (§28).
    */
    Route::get('reports', ReportIndex::class)->name('reports.index');
    Route::get('reports/export', ReportExportController::class)->name('reports.export');

    /*
    |----------------------------------------------------------------------
    | Notifications
    |----------------------------------------------------------------------
    | No permission gate: everyone reads their own notifications, and the
    | component only ever queries the signed-in user's relation (§26).
    */
    Route::get('notifications', NotificationIndex::class)->name('notifications.index');

    /*
    |----------------------------------------------------------------------
    | Administration
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('users', AdminUsers::class)->name('users');
        Route::get('roles', AdminRoles::class)->name('roles');
        Route::get('disciplines', AdminDisciplines::class)->name('disciplines');
        Route::get('workflows', AdminWorkflows::class)->name('workflows');
        Route::get('settings', AdminSettings::class)->name('settings');
    });
});

require __DIR__.'/auth.php';
