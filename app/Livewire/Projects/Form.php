<?php

namespace App\Livewire\Projects;

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Create and edit a project. One component serves both so the field set,
 * validation and layout can never drift between the two screens.
 */
class Form extends Component
{
    public ?Project $project = null;

    public string $project_code = '';

    public string $name = '';

    public string $client = '';

    public string $location = '';

    public string $description = '';

    public string $manager_id = '';

    public string $status = ProjectStatus::Planning->value;

    public string $start_date = '';

    public string $end_date = '';

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->authorize('update', $project);

            $this->project = $project;
            $this->project_code = $project->project_code;
            $this->name = $project->name;
            $this->client = $project->client ?? '';
            $this->location = $project->location ?? '';
            $this->description = $project->description ?? '';
            $this->manager_id = (string) ($project->manager_id ?? '');
            $this->status = $project->status->value;
            $this->start_date = $project->start_date?->toDateString() ?? '';
            $this->end_date = $project->end_date?->toDateString() ?? '';

            return;
        }

        $this->authorize('create', Project::class);
    }

    public function isEditing(): bool
    {
        return $this->project !== null;
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'project_code' => [
                'required', 'string', 'max:32', 'regex:/^[A-Za-z0-9\-\_\.]+$/',
                Rule::unique('projects', 'project_code')->ignore($this->project?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'client' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'manager_id' => ['nullable', Rule::exists('users', 'id')],
            'status' => ['required', Rule::enum(ProjectStatus::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'project_code' => __('projects.fields.project_code'),
            'name' => __('projects.fields.name'),
            'client' => __('projects.fields.client'),
            'location' => __('projects.fields.location'),
            'description' => __('projects.fields.description'),
            'manager_id' => __('projects.fields.manager'),
            'status' => __('projects.fields.status'),
            'start_date' => __('projects.fields.start_date'),
            'end_date' => __('projects.fields.end_date'),
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Normalise "" from empty selects/dates to null before persisting.
        $validated['manager_id'] = $validated['manager_id'] ?: null;
        $validated['start_date'] = $validated['start_date'] ?: null;
        $validated['end_date'] = $validated['end_date'] ?: null;
        $validated['client'] = $validated['client'] ?: null;
        $validated['location'] = $validated['location'] ?: null;
        $validated['description'] = $validated['description'] ?: null;
        $validated['project_code'] = strtoupper($validated['project_code']);

        if ($this->isEditing()) {
            $this->authorize('update', $this->project);
            $this->project->update($validated);

            session()->flash('toast', __('projects.messages.updated'));
            $this->redirectRoute('projects.show', $this->project, navigate: true);

            return;
        }

        $this->authorize('create', Project::class);
        $project = Project::create($validated);

        session()->flash('toast', __('projects.messages.created'));
        $this->redirectRoute('projects.show', $project, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.projects.form', [
            'statuses' => ProjectStatus::options(),
            'managers' => User::query()
                ->active()
                ->role([UserRole::ProjectManager->value, UserRole::Administrator->value])
                ->orderBy('name')
                ->pluck('name', 'id'),
        ])->title($this->isEditing() ? __('projects.edit') : __('projects.create'));
    }
}
