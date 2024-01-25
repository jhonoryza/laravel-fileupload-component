<?php

namespace Jhonoryza\Component\FileUpload\Traits;

use Illuminate\Http\UploadedFile;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Facades\Livewire\Features\SupportFileUploads\GenerateSignedUploadUrl;
use Livewire\Features\SupportFileUploads\S3DoesntSupportMultipleFileUploads;
use Livewire\Features\SupportFileUploads\WithFileUploads;
use function throw_if;

trait FileUploadTrait
{
    use WithFileUploads;

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

}