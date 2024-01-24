<?php

namespace Jhonoryza\Component\FileUpload\UploadHandler;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jhonoryza\Component\FileUpload\Dto\PendingItem;
use Jhonoryza\Component\FileUpload\Dto\RequestItem;
use Jhonoryza\Component\FileUpload\Factory\FileAdderFactory;

class PendingRequestHandler
{
    protected Collection $requestItems;

    protected Model $model;

    protected bool $preserveExisting;

    public function __construct(array $requestItems, Model $model, bool $preserveExisting)
    {
        $this->requestItems = collect($requestItems)
            ->map(fn (array $properties) => RequestItem::fromArray($properties));

        $this->model = $model;

        $this->preserveExisting = $preserveExisting;
    }

    public function toMediaCollection(string $collection = 'default', string $diskName = ''): void
    {
        $requestHandler = RequestHandler::createRequestItems(
            $this->model,
            $this->requestItems,
            $collection
        )->updateExistingMedia();

        if (! $this->preserveExisting) {
            $requestHandler->deleteObsoleteMedia();
        }
        $requestHandler
            ->getPendingMediaItems()
            ->each(function (PendingItem $pendingItem) use ($diskName, $collection) {
                $fileAdder = app(FileAdderFactory::class)
                    ->createForPendingMedia($this->model, $pendingItem);
                if (! is_null($pendingItem->fileName)) {
                    $fileAdder->setFileName($pendingItem->fileName);
                }
                $fileAdder->toMediaCollection($collection, $diskName);
            });
    }
}
