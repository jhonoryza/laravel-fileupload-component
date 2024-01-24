<?php

namespace Jhonoryza\Component\FileUpload\Traits;

use Jhonoryza\Component\FileUpload\UploadHandler\PendingRequestHandler;

trait InteractsWithFileUpload
{
    public function syncFileUploadRequest(?array $requestItems): PendingRequestHandler
    {
        return new PendingRequestHandler(
            $requestItems ?? [],
            $this,
            $preserveExisting = false
        );
    }
}
