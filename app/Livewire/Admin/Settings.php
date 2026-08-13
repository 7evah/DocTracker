<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Support\Settings as SettingsStore;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class Settings extends Component
{
    /** @var array<string, mixed> */
    public array $values = [];

    public function mount(): void
    {
        $this->authorize('manageSettings', User::class);

        $this->values = SettingsStore::withDefaults();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'values.documents_max_size_kb' => ['required', 'integer', 'min:256', 'max:512000'],
            'values.reviews_default_turnaround_days' => ['required', 'integer', 'min:1', 'max:90'],
            'values.approvals_default_turnaround_days' => ['required', 'integer', 'min:1', 'max:90'],
            'values.notifications_email_enabled' => ['boolean'],
            'values.documents_require_version_notes' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return collect(SettingsStore::schema())
            ->keys()
            ->mapWithKeys(fn (string $key) => ["values.{$key}" => __("admin.settings.fields.{$key}")])
            ->all();
    }

    public function save(): void
    {
        $this->authorize('manageSettings', User::class);

        $this->validate();

        // setMany ignores anything outside the schema, so a crafted payload
        // cannot introduce arbitrary keys (§39).
        SettingsStore::setMany($this->values);

        activity('settings')
            ->causedBy(auth()->user())
            ->event('updated')
            ->log('settings.updated');

        Flux::toast(text: __('admin.settings.saved'), variant: 'success');
    }

    /**
     * Read-only diagnostics (§29). Deliberately excludes anything sensitive:
     * no credentials, hosts or keys — just the versions and drivers someone
     * needs when reporting a problem.
     *
     * @return array<string, string>
     */
    public function systemInfo(): array
    {
        return [
            __('admin.settings.info.laravel') => app()->version(),
            __('admin.settings.info.php') => PHP_VERSION,
            __('admin.settings.info.database') => DB::connection()->getDriverName(),
            __('admin.settings.info.storage_disk') => (string) config('documents.disk'),
            __('admin.settings.info.queue') => (string) config('queue.default'),
            __('admin.settings.info.locale') => (string) config('app.locale'),
            __('admin.settings.info.timezone') => (string) config('app.timezone'),
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.settings', [
            'schema' => SettingsStore::schema(),
            'info' => $this->systemInfo(),
        ])->title(__('admin.settings.title'));
    }
}
