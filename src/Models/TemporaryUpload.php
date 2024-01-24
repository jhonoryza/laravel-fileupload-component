<?php

namespace Jhonoryza\Component\FileUpload\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Jhonoryza\Component\FileUpload\Exceptions\NotFoundException;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class TemporaryUpload extends Model implements HasMedia
{
    use InteractsWithMedia;

    public static ?string $disk = null;

    protected $guarded = [];

    /**
     * @throws NotFoundException
     */
    public static function createSelf(string $sessionId, string $uuid): self
    {
        /** @var TemporaryUpload $temporaryUpload */
        $temporaryUpload = static::query()
            ->create([
                'session_id' => $sessionId,
            ]);

        if (static::findByMediaUuid($uuid)) {
            throw NotFoundException::uuidAlreadyExists();
        }

        return $temporaryUpload;
    }

    /**
     * @throws NotFoundException
     * @throws FileIsTooBig
     * @throws FileDoesNotExist
     */
    public static function createForRemoteFile(
        string $file,
        string $sessionId,
        string $uuid,
        string $name,
        string $diskName
    ): self {
        $temporaryUpload = static::createSelf($sessionId, $uuid);

        $temporaryUpload
            ->addMediaFromDisk($file, $diskName)
            ->setName($name)
            ->usingFileName($name)
            ->withProperties(['uuid' => $uuid])
            ->toMediaCollection('default', static::getDiskName());

        return $temporaryUpload->fresh();
    }

    /**
     * @throws NotFoundException
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public static function createForFile(
        UploadedFile $file,
        string $sessionId,
        string $uuid,
        string $name
    ): self {
        $temporaryUpload = static::createSelf($sessionId, $uuid);
        $temporaryUpload
            ->addMedia($file)
            ->setName($name)
            ->withProperties(['uuid' => $uuid])
            ->toMediaCollection('default', static::getDiskName());

        return $temporaryUpload->fresh();
    }

    public static function getDiskName(): string
    {
        return static::$disk ?? config('media-library.disk_name');
    }

    public static function findByMediaUuid(?string $mediaUuid): ?TemporaryUpload
    {
        $mediaModelClass = config('media-library.media_model');

        /** @var Media $media */
        $media = $mediaModelClass::query()
            ->where('uuid', $mediaUuid)
            ->first();

        if (! $media) {
            return null;
        }

        $temporaryUpload = $media->model;

        if (! $temporaryUpload instanceof TemporaryUpload) {
            return null;
        }

        return $temporaryUpload;
    }

    public static function findByUuidInCurrentSession(?string $mediaUuid): ?self
    {
        if (! $temporaryUpload = static::findByUuid($mediaUuid)) {
            return null;
        }

        return $temporaryUpload;
    }

    public static function findByUuid(?string $mediaUuid): ?self
    {
        $mediaModel = config('media-library.media_model');

        /** @var Media $media */
        $media = $mediaModel::query()
            ->where('uuid', $mediaUuid)
            ->first();

        if (! $media) {
            return null;
        }

        $temporaryUpload = $media->model;

        if (! $temporaryUpload instanceof TemporaryUpload) {
            return null;
        }

        return $temporaryUpload;
    }

    public function moveMedia(HasMedia $toModel, string $collectionName, string $diskName, string $fileName): Media
    {
        $media = $this->getFirstMedia();

        $temporaryUploadModel = $media->model;
        $uuid = $media->uuid;

        $newMedia = $media->move($toModel, $collectionName, $diskName, $fileName);

        $temporaryUploadModel->delete();

        $newMedia->update(compact('uuid'));

        return $newMedia;
    }
}
