<?php

namespace Jhonoryza\Component\FileUpload\Livewire;

use Facades\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Jhonoryza\Component\FileUpload\Action\ConvertTemporaryUploadedFileToMedia;
use Jhonoryza\Component\FileUpload\Dto\ViewItem;
use Jhonoryza\Component\FileUpload\Exceptions\NotFoundException;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\S3DoesntSupportMultipleFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use function collect;

class FileUpload extends Component
{
    use WithFileUploads;

    /**
     * @var array<TemporaryUploadedFile>
     */
    public $files = [];

    /**
     * to signal Livewire that if wire:model is declared
     * on parent component it should bind to this property
     *
     * @var array<TemporaryUploadedFile>
     */
    #[Modelable]
    public $previews = [];

    public string $name;

    public string $label;

    public string $rules;

    public bool $multiple;

    public ?string $uuid = null;

    public ?HasMedia $model = null;

    public ?string $collection = null;

    public bool $canUploadFile = true;

    public function mount(
        ?string $name = null,
        ?string $label = null,
        ?string $rules = null,
        bool $multiple = false,
        mixed $model = null,
        ?string $collection = null,
        bool $canUploadFile = true
    ): void {
        $this->name = $name ?? 'media';
        $this->label = $label ?? $this->name;
        $this->rules = $rules ?? 'max:10240|mimes:jpeg,png';
        $this->multiple = $multiple;
        $this->uuid = Str::uuid();
        $this->model = $model;
        $this->collection = $collection;
        $this->canUploadFile = $canUploadFile;
    }

    public function getRules(): array
    {
        return [
            'files.*' => $this->rules,
        ];
    }

    protected function getValidationAttributes(): array
    {
        return [
            'files.*' => 'file',
        ];
    }

    public function render(): View
    {
        return view('file-upload-component::livewire.file-upload', [
            'items' => collect($this->previews)
                ->map(function (array $preview) {
                    return new ViewItem($this->name, $preview);
                })
                ->sortBy('order')
                ->values(),
        ]);
    }

    /**
     * Triggered when files property value updated
     */
    public function updatedFiles(): void
    {
        $this->validate();

        foreach ($this->files as $index => $file) {
            $this->handleUpload($file, $index);
        }

        $this->files = [];
    }

    /**
     * @throws NotFoundException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    protected function handleUpload(TemporaryUploadedFile $file, int $index): void
    {
        $media = ConvertTemporaryUploadedFileToMedia::execute($file);
        $this->previews[$index] = $this->formatMediaData($media);
        $this->dispatch($this->name . ':temporary-upload-finished', $this->previews[$index]);
    }

    public function boot(): void
    {
        $this->withValidator(function ($validator) {
            $validator->after(function (Validator $validator) {
                if ($validator->errors()->count() > 0) {
                    $this->files = [];
                }
            });
        });
    }

    /**
     * Override default _startUpload method to dispatch our start upload events
     *
     * @param  string  $name
     * @param  string  $fileInfo
     * @param  bool  $isMultiple
     * @return void
     *
     * @throws \Throwable
     */
    public function _startUpload($name, $fileInfo, $isMultiple)
    {
        $this->dispatch($this->name.':temporary-upload-started');
        if (FileUploadConfiguration::isUsingS3()) {
            throw_if($isMultiple, S3DoesntSupportMultipleFileUploads::class);

            $file = UploadedFile::fake()->create($fileInfo[0]['name'], $fileInfo[0]['size'] / 1024, $fileInfo[0]['type']);

            $this->dispatch('upload:generatedSignedUrlForS3', name: $name, payload: GenerateSignedUploadUrl::forS3($file))->self();

            return;
        }

        $this->dispatch('upload:generatedSignedUrl', name: $name, url: GenerateSignedUploadUrl::forLocal())->self();
    }

    public function loadMedia(): void
    {
        if (empty($this->previews)) {
            $this->previews = $this->getMedia($this->name, $this->model, $this->collection ?? 'default');
        }
    }

    protected function getMedia(string $name, HasMedia $model, string $collection): array
    {
        return old($name) ? old($name) : $model
            ->getMedia($collection)
            ->map(fn (Media $media) => $this->formatMediaData($media))
            ->keyBy('uuid')
            ->toArray();
    }

    protected function formatMediaData(Media $media): array
    {
        return [
            'name' => $media->name,
            'file_name' => $media->file_name,
            'uuid' => $media->uuid,
            'preview_url' => $media->getUrl(),
            'order' => $media->order_column,
            'custom_properties' => $media->custom_properties,
            'extension' => $media->extension,
            'size' => $media->size,
            'created_at' => $media->created_at->timestamp,
        ];
    }

    public function remove(string $uuid): void
    {
        $this->previews = collect($this->previews)
            ->reject(fn (array $mediaItem) => $mediaItem['uuid'] === $uuid)
            ->toArray();
    }
}
