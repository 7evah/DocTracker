<?php

namespace App\Livewire\Documents;

use App\Models\Discipline;
use App\Models\Document;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Edits document metadata only.
 *
 * There is deliberately no file field here: replacing a file in place would
 * silently rewrite history. A changed file is a new revision (§6, §22).
 */
class Edit extends Component
{
    public Document $document;

    public string $discipline_id = '';

    public string $document_number = '';

    public string $title = '';

    public string $description = '';

    public function mount(Document $document): void
    {
        $this->authorize('update', $document);

        // An approved or archived document's metadata is what was signed off;
        // the disabled menu item is a courtesy, this is the rule (§39).
        abort_unless($document->acceptsMetadataEdit(), 403);

        $this->document = $document;
        $this->discipline_id = (string) $document->discipline_id;
        $this->document_number = $document->document_number;
        $this->title = $document->title;
        $this->description = $document->description ?? '';
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'discipline_id' => ['required', Rule::exists('disciplines', 'id')],
            'document_number' => [
                'required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-\_\.\/]+$/',
                Rule::unique('documents', 'document_number')
                    ->where('project_id', $this->document->project_id)
                    ->whereNull('deleted_at')
                    ->ignore($this->document->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'discipline_id' => __('documents.fields.discipline'),
            'document_number' => __('documents.fields.document_number'),
            'title' => __('documents.fields.title'),
            'description' => __('documents.fields.description'),
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'document_number.unique' => __('validation.custom.document_number.unique'),
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->document);

        abort_unless($this->document->acceptsMetadataEdit(), 403);

        $validated = $this->validate();
        $validated['document_number'] = strtoupper($validated['document_number']);
        $validated['description'] = $validated['description'] ?: null;

        $this->document->update($validated);

        session()->flash('toast', __('documents.messages.updated'));

        $this->redirectRoute('documents.show', $this->document, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.documents.edit', [
            'disciplines' => Discipline::options(),
        ])->title(__('documents.edit_heading', ['number' => $this->document->document_number]));
    }
}
