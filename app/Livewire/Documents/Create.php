<?php

namespace App\Livewire\Documents;

use App\Models\Discipline;
use App\Models\Document;
use App\Models\Project;
use App\Services\DocumentService;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class Create extends Component
{
    use WithFileUploads;

    public string $project_id = '';

    public string $discipline_id = '';

    public string $document_number = '';

    public string $title = '';

    public string $description = '';

    public string $revision = 'A';

    public string $version_notes = '';

    public ?TemporaryUploadedFile $file = null;

    public function mount(): void
    {
        $this->authorize('create', Document::class);

        // Pre-select when arriving from a project page.
        if ($project = request()->integer('project')) {
            $this->project_id = (string) $project;
        }
    }

    /** @return array<string, mixed> */
    protected function rules(): array
    {
        return [
            'project_id' => ['required', Rule::exists('projects', 'id')->whereNull('deleted_at')],
            'discipline_id' => ['required', Rule::exists('disciplines', 'id')],
            'document_number' => [
                'required', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-\_\.\/]+$/',
                // Uniqueness is scoped to the project, matching the DB
                // constraint — the same number may legitimately exist on
                // two different projects (§10).
                Rule::unique('documents', 'document_number')
                    ->where('project_id', $this->project_id ?: 0)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'revision' => ['required', 'string', 'max:8', 'regex:/^[A-Za-z0-9]+$/'],
            'version_notes' => ['nullable', 'string', 'max:2000'],
            'file' => [
                'required',
                'file',
                'max:'.config('documents.max_size_kb'),
                'mimes:'.implode(',', config('documents.allowed_extensions')),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'project_id' => __('documents.fields.project'),
            'discipline_id' => __('documents.fields.discipline'),
            'document_number' => __('documents.fields.document_number'),
            'title' => __('documents.fields.title'),
            'description' => __('documents.fields.description'),
            'revision' => __('documents.fields.revision'),
            'version_notes' => __('documents.fields.version_notes'),
            'file' => __('documents.fields.file'),
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'document_number.unique' => __('validation.custom.document_number.unique'),
        ];
    }

    /** Validate the file as soon as it is chosen, rather than at submit. */
    public function updatedFile(): void
    {
        $this->validateOnly('file');
    }

    /**
     * Suggest a document number prefix from the selected discipline, e.g.
     * choosing "Mécanique" pre-fills "ME-". Only fills an empty field so a
     * user's own input is never overwritten.
     */
    public function updatedDisciplineId(): void
    {
        if (filled($this->document_number)) {
            return;
        }

        if ($discipline = Discipline::find($this->discipline_id)) {
            $this->document_number = $discipline->code.'-';
        }
    }

    public function save(DocumentService $documents): void
    {
        $this->authorize('create', Document::class);

        $validated = $this->validate();

        $document = $documents->create(
            attributes: [
                'project_id' => $validated['project_id'],
                'discipline_id' => $validated['discipline_id'],
                'document_number' => strtoupper($validated['document_number']),
                'title' => $validated['title'],
                'description' => $validated['description'] ?: null,
                'current_revision' => strtoupper($validated['revision']),
            ],
            file: $this->file,
            author: auth()->user(),
            versionNotes: $validated['version_notes'] ?: null,
        );

        session()->flash('toast', __('documents.messages.created'));

        $this->redirectRoute('documents.show', $document, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.documents.create', [
            'projects' => Project::query()->open()->orderBy('project_code')
                ->get(['id', 'project_code', 'name'])
                ->mapWithKeys(fn (Project $p) => [$p->id => "{$p->project_code} — {$p->name}"]),
            'disciplines' => Discipline::options(),
        ])->title(__('documents.create'));
    }
}
