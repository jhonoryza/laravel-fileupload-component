<?php

namespace Jhonoryza\Component\FileUpload;

use Illuminate\Support\ServiceProvider;
use Jhonoryza\Component\FileUpload\Livewire\FileUpload;
use Livewire\Livewire;

class FileUploadServiceProvider extends ServiceProvider
{
    private string $namespace = 'file-upload-component';

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', $this->namespace);
        $this->mergeConfigFrom(__DIR__.'/../config/file-upload.php', 'file-upload');

        $this->registerPublishables()
            ->registerLivewireComponents();
    }

    protected function registerPublishables(): self
    {
        if (! class_exists('CreateTemporaryUploadsTable')) {
            $path = database_path('migrations/'.date('Y_m_d_His', time()).'_create_temporary_uploads_table.php');
            $this->publishes([
                __DIR__.'/../database/migrations/create_temporary_uploads_table.stub' => $path,
            ], $this->namespace.'-migrations');
        }

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/'.$this->namespace),
        ], $this->namespace.'-views');

        $this->publishes([
            __DIR__.'/../config/file-upload.php' => config_path('file-upload.php'),
        ], $this->namespace.'-config');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/'.$this->namespace),
        ], $this->namespace.'-assets');

        return $this;
    }

    public function registerLivewireComponents(): self
    {
        if (! class_exists(Livewire::class)) {
            return $this;
        }

        Livewire::component($this->namespace, FileUpload::class);

        return $this;
    }
}
