<?php

namespace App\Livewire\Admin;

use App\Models\Discipline;
use App\Models\User;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Disciplines extends Component
{
    public ?Discipline $editing = null;

    public string $code = '';

    public string $name = '';

    public string $description = '';

    public string $sort_order = '0';

    public bool $is_active = true;

    public function mount(): void
    {
        $this->authorize('manageDisciplines', User::class);
    }

    public function startNew(): void
    {
        $this->reset('editing', 'code', 'name', 'description');
        $this->sort_order = (string) ((Discipline::max('sort_order') ?? 0) + 1);
        $this->is_active = true;
        $this->resetValidation();

        $this->modal('discipline-form')->show();
    }

    public function edit(int $id): void
    {
        $discipline = Discipline::findOrFail($id);

        $this->editing = $discipline;
        $this->code = $discipline->code;
        $this->name = $discipline->name;
        $this->description = $discipline->description ?? '';
        $this->sort_order = (string) $discipline->sort_order;
        $this->is_active = $discipline->is_active;
        $this->resetValidation();

        $this->modal('discipline-form')->show();
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            // Uppercase letters/digits only: the code becomes a document
            // number prefix, so it has to be safe to concatenate.
            'code' => [
                'required', 'string', 'min:2', 'max:8', 'regex:/^[A-Za-z0-9]+$/',
                Rule::unique('disciplines', 'code')->ignore($this->editing?->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:999'],
            'is_active' => ['boolean'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'code' => __('admin.disciplines.fields.code'),
            'name' => __('admin.disciplines.fields.name'),
            'description' => __('admin.disciplines.fields.description'),
            'sort_order' => __('admin.disciplines.fields.sort_order'),
            'is_active' => __('admin.disciplines.fields.is_active'),
        ];
    }

    public function save(): void
    {
        $this->authorize('manageDisciplines', User::class);

        $validated = $this->validate();
        $validated['code'] = strtoupper($validated['code']);
        $validated['description'] = $validated['description'] ?: null;

        if ($this->editing) {
            $this->editing->update($validated);
            $message = __('admin.disciplines.messages.updated');
        } else {
            Discipline::create($validated);
            $message = __('admin.disciplines.messages.created');
        }

        $this->modal('discipline-form')->close();
        $this->reset('editing');

        Flux::toast(text: $message, variant: 'success');
    }

    public function delete(int $id): void
    {
        $this->authorize('manageDisciplines', User::class);

        $discipline = Discipline::withCount('documents')->findOrFail($id);

        // Documents reference the discipline and the FK is restrictOnDelete,
        // so refuse with an explanation rather than surfacing a SQL error.
        if ($discipline->documents_count > 0) {
            Flux::toast(text: __('admin.disciplines.messages.in_use'), variant: 'danger');

            return;
        }

        $discipline->delete();

        Flux::toast(text: __('admin.disciplines.messages.deleted'), variant: 'success');
    }

    public function render(): View
    {
        return view('livewire.admin.disciplines', [
            'disciplines' => Discipline::withCount('documents')->ordered()->get(),
        ])->title(__('admin.disciplines.title'));
    }
}
