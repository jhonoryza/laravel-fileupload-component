<?php

namespace Jhonoryza\Component\FileUpload\Dto;

use Exception;
use Jhonoryza\Component\FileUpload\Models\TemporaryUpload;

class PendingItem
{
    public TemporaryUpload $temporaryUpload;

    public string $name;

    public int $order;

    public array $customProperties;

    public ?string $fileName;

    /**
     * @throws Exception
     */
    public function __construct(
        string $uuid,
        string $name,
        int $order,
        array $customProperties,
        array $customHeaders,
        ?string $fileName = null
    ) {
        $temporaryUploadModelClass = config('file-upload.temporary_upload_model');

        if (! $temporaryUpload = $temporaryUploadModelClass::findByUuidInCurrentSession($uuid)) {
            throw new Exception('invalid uuid');
        }

        $this->temporaryUpload = $temporaryUpload;

        $this->name = $name;

        $this->order = $order;

        $this->customProperties = $customProperties;

        $this->fileName = $fileName;
    }
}
