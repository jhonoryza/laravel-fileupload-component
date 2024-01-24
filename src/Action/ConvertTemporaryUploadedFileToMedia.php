<?php

namespace Jhonoryza\Component\FileUpload\Action;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Jhonoryza\Component\FileUpload\Exceptions\NotFoundException;
use Jhonoryza\Component\FileUpload\Models\TemporaryUpload;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

use function session;

class ConvertTemporaryUploadedFileToMedia
{
    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws NotFoundException
     */
    public static function execute(TemporaryUploadedFile $tempFile): Media
    {
        return static::isLocalTemporaryDisk()
            ? static::generateFromLocal($tempFile)
            : static::generateFromRemote($tempFile);
    }

    protected static function isLocalTemporaryDisk(): bool
    {
        $diskBeforeTestFake = config('livewire.temporary_file_upload.disk') ?: config('filesystems.default');

        return config('filesystems.disks.'.strtolower($diskBeforeTestFake).'.driver') === 'local';
    }

    /**
     * @throws NotFoundException
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    protected static function generateFromLocal(TemporaryUploadedFile $tempFile): Media
    {
        $uploadedFile = new UploadedFile($tempFile->path(), $tempFile->getClientOriginalName());

        /** @var class-string<TemporaryUpload> $model */
        $model = config('file-upload.temporary_upload_model');

        return $model::createForFile(
            $uploadedFile,
            session()->getId(),
            (string) Str::uuid(),
            $tempFile->getClientOriginalName()
        )->getFirstMedia();
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     * @throws NotFoundException
     */
    protected static function generateFromRemote(TemporaryUploadedFile $tempFile): Media
    {
        /** @var class-string<TemporaryUpload> $model */
        $model = config('file-upload.temporary_upload_model');
        $tempDisk = config('livewire.temporary_file_upload.disk', 's3');

        $livewireDirectory = FileUploadConfiguration::directory();
        $remotePath = Str::of($livewireDirectory)->start('/')->finish('/').$tempFile->getFilename();

        return $model::createForRemoteFile(
            $remotePath,
            session()->getId(),
            (string) Str::uuid(),
            $tempFile->getClientOriginalName(),
            $tempDisk
        )->getFirstMedia();
    }
}
