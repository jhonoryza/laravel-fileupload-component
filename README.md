# Laravel File Upload Component

laravel livewire file upload component

## Requirement
- php v8.2
- laravel v10
- livewire v3
- spatie/media-library v10

## Installation

```bash
composer install jhonoryza/laravel-fileupload-component
```

```bash
php artisan vendor:publish --provider=Jhonoryza\Component\FileUpload\FileUploadServiceProvider
```

## Quick Start

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
   /**
    * property to store multiple images
    */ 
    public $images = []; 
   
   /**
    * listener when there is onFileReplace event from the component
    */ 
   #[On('images:onFileReplace')]
   public function replaceImages(array $images): void
   {
       $this->images = $images;
   }

   /**
    * listener when there is onFileAdded event from the component
    */ 
   #[On('images:onFileAdded')]
   public function addToImages(array $file): void
   {
       $this->images[$file['uuid']] = $file;
   }
    
   /**
    * form save function example, setting is a Model
    * we call syncFileUploadRequest function
    * to save images to media library
    */ 
    public function save()
    {
        $this->setting
            ->syncFileUploadRequest($this->images)
            ->toMediaCollection('settings');
    }
```

in create or edit livewire component
```php
    <livewire:file-upload-component
        name="images"
        label="Image"
        :model="$setting"
        collection="settings"
        :multiple="true"
    />
```

in view livewire component
```php
    <livewire:file-upload-component
        name="images"
        label="Image"
        :model="$setting"
        collection="settings"
        :multiple="true"
        :canUploadFile="false"
    />
```

## Property Explanation
- name is required and will affect what the event name is
- :model you need to pass a variable with Model type that implement HasMedia
- collection is for media collection name
- :multiple for single file upload or multiple file upload
- :canUploadFile to hide file selector

## Component Event

this component dispatch 2 event when temporary upload is started
- media:temporary-upload-started
- media:temporary-upload-finished

change `media` with the `name` property, example `name` property is images

```php
    <button
        x-data="{ disable: false }"
        x-bind:disabled="disable"
        x-bind:style="disable ? 'cursor: not-allowed' : ''"
        x-on:images:temporary-upload-started.window="disable = true"
        x-on:images:temporary-upload-finished.window="disable = false"
        type="submit" 
        class="btn btn-primary"
    >
        Update Setting
    </button>
```

or you can listen to default livewire file upload event like this

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

another 2 event when the file is removed / loaded or added
- media:onFileReplace
- media:onFileAdded

change `media` with the `name` property, example `name` property is images

```php
   #[On('images:onFileReplace')]
   public function replaceImages(array $images): void
   {
       $this->images = $images;
   }

   #[On('images:onFileAdded')]
   public function addToImages(array $file): void
   {
       $this->images[$file['uuid']] = $file;
   }
```

## next thing todo
- [ ] test validation with error message
- [ ] add unit test
- [x] bug when interacts with session flash after redirect (session flash data is missing)

## Security

If you've found a bug regarding security please mail [jardik.oryza@gmail.com](mailto:jardik.oryza@gmail.com) instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.