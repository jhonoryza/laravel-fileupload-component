<?php

namespace Jhonoryza\Component\FileUpload\Factory;

use Jhonoryza\Component\FileUpload\Models\TemporaryUpload;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\UnknownType;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\RemoteFile;

class FileAdder extends \Spatie\MediaLibrary\MediaCollections\FileAdder
{
    /**
     * @throws UnknownType
     */
    public function setFile($file): self
    {
        $this->file = $file;

        if ($file instanceof TemporaryUpload) {
            return $this;
        }

        return parent::setFile($file);
    }

    /**
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function toMediaCollection(string $collectionName = 'default', string $diskName = ''): Media
    {
        $sanitizedFileName = ($this->fileNameSanitizer)($this->fileName);
        $fileName = app(config('media-library.file_namer'))->originalFileName($sanitizedFileName);
        $this->fileName = $this->appendExtension($fileName, pathinfo($sanitizedFileName, PATHINFO_EXTENSION));

        if ($this->file instanceof RemoteFile) {
            return $this->toMediaCollectionFromRemote($collectionName, $diskName);
        }

        if ($this->file instanceof TemporaryUpload) {
            return $this->toMediaCollectionFromTemporaryUpload($collectionName, $diskName, $this->fileName);
        }
    }
    }