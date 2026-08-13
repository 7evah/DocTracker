<?php

namespace App\Livewire\Admin;

use App\Enums\UserRole;
use App\Models\ApprovalWorkflow;
use App\Models\Project;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Approval circuit definitions (§29, §8).
 *
 * Editing a circuit changes only what future documents get: approvals already
 * instantiated onto a revision keep the steps they were created with, so a
 * signature history is never rewritten under someone's feet (§34).
 */
class Workflows extends Component
{
    public ?ApprovalWorkflow $editing = null;

    public string $name = '';

    public string $description = '';

    public string $project_id = '';

    public bool $is_active = true;

    public bool $is_default = false;

    /** @var array<int, array{step_order: int|string, role: string, label: string, turnaround_days: int|string}> */
    public array $steps = [];

    public function mount(): void
    {
        $this->authorize('manageWorkflows', User::class);
    }

    public function startNew(): void
    {
        $this->reset('editing', 'name', 'description', 'project_id');
        $this->is_active = true;
        $this->is_default = false;
        $this->steps = [$this->blankStep(1)];
        $this->resetValidation();

        $this->modal('workflow-form')->show();
    }

    public function edit(int $id): void
    {
        $workflow = ApprovalWorkflow::with('steps')->findOrFail($id);

        $this->editing = $workflow;
        $this->name = $workflow->name;
        $this->description = $workflow->description ?? '';
        $this->project_id = (string) ($workflow->project_id ?? '');
        $this->is_active = $workflow->is_active;
        $this->is_default = $workflow->is_default;

        $this->steps = $workflow->steps->map(fn ($step) => [
            'step_order' => $step->step_order,
            'role' => $step->role,
            'label' => $step->label ?? '',
            'turnaround_days' => $step->turnaround_days ?? 3,
        ])->values()->all();

        $this->resetValidation();

        $this->modal('workflow-form')->show();
    }

    /** @return array{step_order: int, role: string, label: string, turnaround_days: int} */
    private function blankStep(int $order): array
    {
        return [
            'step_order' => $order,
            'role' => UserRole::Reviewer->value,
            'label' => '',
            'turnaround_days' => 3,
        ];
    }

    public function addStep(): void
    {
        $this->steps[] = $this->blankStep(count($this->steps) + 1);
    }

    public function removeStep(int $index): void
    {
        unset($this->steps[$index]);

        // Renumber so the order stays contiguous after a removal.
        $this->steps = array_values($this->steps);

        foreach ($this->steps as $position => $step) {
            $this->steps[$position]['step_order'] = $position + 1;
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'project_id' => ['nullable', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.step_order' => ['required', 'integer', 'min:1', 'max:99'],
            'steps.*.role' => ['required', Rule::in(array_column(UserRole::cases(), 'value'))],
            'steps.*.label' => ['nullable', 'string', 'max:255'],
            'steps.*.turnaround_days' => ['required', 'integer', 'min:1', 'max:90'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'steps.required' => __('admin.workflows.messages.needs_steps'),
            'steps.min' => __('admin.workflows.messages.needs_steps'),
        ];
    }

    public function save(): void
    {
        $this->authorize('manageWorkflows', User::class);

        $validated = $this->validate();

        $orders = array_column($validated['steps'], 'step_order');

        // The steps table has unique(workflow_id, step_order); catching it
        // here gives a readable message instead of a constraint violation.
        if (count($orders) !== count(array_unique($orders))) {
            $this->addError('steps', __('admin.workflows.messages.duplicate_order'));

            return;
        }

        DB::transaction(function () use ($validated) {
            $attributes = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?: null,
                'project_id' => $validated['project_id'] ?: null,
                'is_active' => $validated['is_active'],
                'is_default' => $validated['is_default'],
            ];

            $workflow = $this->editing
                ? tap($this->editing)->update($attributes)
                : ApprovalWorkflow::create($attributes);

            /*
            | Only one default per scope, or ApprovalWorkflow::resolveFor()
            | would pick arbitrarily between them.
            */
            if ($workflow->is_default) {
                ApprovalWorkflow::query()
                    ->whereKeyNot($workflow->id)
                    ->where('project_id', $workflow->project_id)
                    ->update(['is_default' => false]);
            }

            // Replace the step set wholesale: simpler than diffing, and safe
            // because instantiated approvals hold their own copies.
            $workflow->steps()->delete();

            foreach ($validated['steps'] as $step) {
                $workflow->steps()->create([
                    'step_order' => $step['step_order'],
                    'role' => $step['role'],
                    'label' => $step['label'] ?: null,
                    'turnaround_days' => $step['turnaround_days'],
                    'required' => true,
                ]);
            }
        });

        $this->modal('workflow-form')->close();

        Flux::toast(
            text: $this->editing
                ? __('admin.workflows.messages.updated')
                : __('admin.workflows.messages.created'),
            variant: 'success',
        );

        $this->reset('editing');
    }

    public function delete(int $id): void
    {
        $this->authorize('manageWorkflows', User::class);

        ApprovalWorkflow::findOrFail($id)->delete();

        Flux::toast(text: __('admin.workflows.messages.deleted'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.workflows', [
            'workflows' => ApprovalWorkflow::with(['steps', 'project:id,project_code'])
                ->orderByRaw('project_id is not null')
                ->orderBy('name')
                ->get(),
            'projects' => Project::query()->orderBy('project_code')->pluck('project_code', 'id'),
            'roles' => UserRole::options(),
        ])->title(__('admin.workflows.title'));
    }
}
