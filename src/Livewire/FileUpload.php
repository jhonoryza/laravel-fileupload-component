<?php

namespace Jhonoryza\Component\FileUpload\Livewire;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Validator;
use Jhonoryza\Component\FileUpload\Action\ConvertTemporaryUploadedFileToMedia;
use Jhonoryza\Component\FileUpload\Dto\ViewItem;
use Jhonoryza\Component\FileUpload\Exceptions\NotFoundException;
use Jhonoryza\Component\FileUpload\Traits\FileUploadTrait;
use Livewire\Attributes\Modelable;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use function collect;

class FileUpload extends Component
{
    use FileUploadTrait;

    /**
     * @var array<TemporaryUploadedFile>
     */
    public $tempFiles = [];

    /**
     * to signal Livewire that if wire:model is declared
     * on parent component it should bind to this property
     *
     * @var array<TemporaryUploadedFile>
     */
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
        string $name,
        ?string $label = null,
        ?string $rules = null,
        bool $multiple = false,
        mixed $model = null,
        ?string $collection = null,
        bool $canUploadFile = true
    ): void {
        $this->name = $name;
        $this->label = $label ?? $this->name;
        $this->rules = $rules ?? 'max:10240|mimes:jpeg,png';
        $this->multiple = $multiple;
        $this->uuid = Str::uuid();
        $this->model = $model;
        $this->collection = $collection ?? 'default';
        $this->canUploadFile = $canUploadFile;
    }

    /**
     * validation rules
     */
    public function getRules(): array
    {
        return [
            'tempFiles.*' => $this->rules,
        ];
    }

    /**
     * validation attributes
     */
    protected function getValidationAttributes(): array
    {
        return [
            'tempFiles.*' => 'file',
        ];
    }

    /**
     * default render function
     */
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
     * this function is called first when the component is rendered
     * it call from file-upload blade files
     * if the model already have media the property previews will be filled
     */
    public function loadMedia(): void
    {
        if (empty($this->previews)) {
            $this->previews = $this->getMedia($this->name, $this->model, $this->collection ?? 'default');
            $this->dispatch($this->name . ':onFileReplace', $this->previews);
        }
    }

    /**
     * Triggered when files property value updated
     */
    public function updatedFiles(): void
    {
        $this->validate();

        foreach ($this->tempFiles as $file) {
            $this->handleUpload($file);
        }

        $this->tempFiles = [];
    }

    /**
     * this will convert TemporaryUploadedFile to Media class
     * then will add it to previews property
     *
     * @throws NotFoundException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    protected function handleUpload(TemporaryUploadedFile $file): void
    {
        $media = ConvertTemporaryUploadedFileToMedia::execute($file);
        $this->previews[$media->uuid] = $this->formatMediaData($media);
        $this->dispatch($this->name . ':temporary-upload-finished');
        $this->dispatch($this->name . ':onFileAdded', $this->previews[$media->uuid]);
    }

    /**
     * clear temporary files after validation error
     */
    public function boot(): void
    {
        $this->withValidator(function ($validator) {
            $validator->after(function (Validator $validator) {
                if ($validator->errors()->count() > 0) {
                    $this->tempFiles = [];
                }
            });
        });
    }

    /**
     * helper function to get media from the model
     */
    protected function getMedia(string $name, HasMedia $model, string $collection): array
    {
        return old($name) ? old($name) : $model
            ->getMedia($collection)
            ->map(fn (Media $media) => $this->formatMediaData($media))
            ->keyBy('uuid')
            ->toArray();
    }

    /**
     * helper function to format media data
     */
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

    /**
     * this will be called from file-upload blade files
     * when remove button is clicked
     */
    public function remove(string $uuid): void
    {
        $this->previews = collect($this->previews)
            ->reject(fn (array $mediaItem) => $mediaItem['uuid'] === $uuid)
            ->toArray();
        $this->dispatch($this->name . ':onFileReplace', $this->previews);
    }
}
