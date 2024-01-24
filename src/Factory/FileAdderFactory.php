<?php

namespace Jhonoryza\Component\FileUpload\Factory;

use Illuminate\Database\Eloquent\Model;
use Jhonoryza\Component\FileUpload\Dto\PendingItem;
use Spatie\MediaLibrary\MediaCollections\Exceptions\UnknownType;

class FileAdderFactory
{
    /**
     * @throws UnknownType
     */
    public static function createForPendingMedia(Model $subject, PendingItem $pendingMedia): FileAdder
    {
        /** @var FileAdder $fileAdder */
        $fileAdder = app(FileAdder::class);
        return $fileAdder
            ->setSubject($subject)
            ->setFile($pendingMedia->temporaryUpload)
            ->setName($pendingMedia->name)
            ->setOrder($pendingMedia->order);
    }
}