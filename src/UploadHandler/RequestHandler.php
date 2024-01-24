<?php

namespace Jhonoryza\Component\FileUpload\UploadHandler;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Jhonoryza\Component\FileUpload\Dto\PendingItem;
use Jhonoryza\Component\FileUpload\Dto\RequestItem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class RequestHandler
{
    protected Model $model;

    protected Collection $requestItems;

    protected array $existingUuids;

    protected string $collectionName;

    protected function __construct(Model $model, Collection $requestItems, string $collectionName)
    {
        $this->model = $model;

        $this->existingUuids = $this->model->getMedia($collectionName)->pluck('uuid')->toArray();

        $this->requestItems = $requestItems;

        $this->collectionName = $collectionName;
    }

    public static function createRequestItems(
        Model $model,
        Collection $requestItems,
        string $collectionName
    ): self {
        return new static($model, $requestItems, $collectionName);
    }

    public function updateExistingMedia(): self
    {
        $this
            ->existingRequestItems()
            ->each(function (RequestItem $mediaResponseItem) {
                $this->handleExistingRequestItem($mediaResponseItem);
            });

        return $this;
    }

    protected function handleExistingRequestItem(RequestItem $requestItem): void
    {
        $mediaModelClass = config('media-library.media_model');

        $media = $mediaModelClass::findByUuid($requestItem->uuid);

        $media->update([
            'name' => $requestItem->name,
        ]);
    }

    protected function existingRequestItems(): Collection
    {
        return $this
            ->requestItems
            ->filter(fn (RequestItem $item) => in_array($item->uuid, $this->existingUuids));
    }

    public function deleteObsoleteMedia(): self
    {
        $keepUuids = $this->requestItems->pluck('uuid')->toArray();

        $this->model->getMedia($this->collectionName)
            ->reject(fn (Media $media) => in_array($media->uuid, $keepUuids))
            ->each(fn (Media $media) => $media->delete());

        return $this;
    }

    public function getPendingMediaItems(): Collection
    {
        return $this
            ->newRequestItems()
            ->map(function (RequestItem $item) {
                return new PendingItem(
                    $item->uuid,
                    $item->name,
                    $item->order,
                    $item->customProperties,
                    $item->customHeaders,
                    $item->fileName,
                );
            });
    }

    protected function newRequestItems(): Collection
    {
        return $this
            ->requestItems
            ->reject(fn (RequestItem $item) => in_array($item->uuid, $this->existingUuids));
    }
}
