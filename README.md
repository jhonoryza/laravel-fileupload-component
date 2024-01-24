# Laravel Fileupload Component

## Requirement
- php 8.2
- laravel 10
- livewire 3
- spatie/media-library

## Usage

prepare model, example Setting model
```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Jhonoryza\Component\FileUpload\Traits\InteractsWithFileUpload;

class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;
    use InteractsWithFileUpload;
    
    public function registerMediaCollections() : void
    {
        $this->addMediaCollection('settings');
    }
}
```

prepare livewire form class
```php
    public $images = []; // property to store multiple images
    
    // form save function example
    public function save()
    {
        $this->setting->syncFileUploadRequest($this->images)
                ->toMediaCollection('settings');
    }
```

in create or edit form
```php
    <livewire:file-upload-component
        name="images"
        wire:model="images"
        label="Gambar"
        :model="$setting"
        collection="settings"
        :multiple="true"
    />
```

in view form
```php
    <livewire:file-upload-component
        name="images"
        wire:model="images"
        label="Gambar"
        :model="$setting"
        collection="settings"
        :multiple="true"
        :canUploadFile="false"
    />
```

## Component Event

this component dispatch 2 event when temporary upload is started
- media:temporary-upload-started
- media:temporary-upload-finished

change media with the property name

## disable button when upload is started

```php
    <button
        x-data="{ disable: false }"
        x-bind:disabled="disable"
        x-bind:style="disable ? 'cursor: not-allowed' : ''"
        x-on:livewire-upload-start.window="disable = true"
        x-on:livewire-upload-error.window="disable = false"
        x-on:livewire-upload-progress.window="disable = true"
        x-on:livewire-upload-finish.window="disable = false"
        type="submit" 
        class="btn btn-primary"
    >
        Update Setting
    </button>
```

## next thing todo
- test validation with error message
- add unit test
- bug when interacts with session flash after redirect (session flash data is missing)

## Security

If you've found a bug regarding security please mail [jardik.oryza@gmail.com](mailto:jardik.oryza@gmail.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.