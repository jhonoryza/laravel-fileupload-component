<?php

namespace Jhonoryza\Component\FileUpload\Dto;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\HtmlString;

class ViewItem
{
    public function __construct(
        protected string $name,
        protected array $mediaItem
    ) {
    }

    public function __get($name)
    {
        return $this->mediaItem[$name] ?? null;
    }

    public function propertyAttributeName(string $name): string
    {
        return "{$this->name}[{$this->uuid}][$name]";
    }

    public function customPropertyAttributes(string $name): HtmlString
    {
        return new HtmlString(implode(PHP_EOL, [
            "name='{$this->customPropertyAttributeName($name)}'",
            "value='{$this->customPropertyAttributeValue($name)}'",
        ]));
    }

    public function livewireCustomPropertyAttributes(string $name): HtmlString
    {
        return new HtmlString(implode(PHP_EOL, [
            'x-data',
            "wire:model='media.{$this->uuid}.custom_properties.{$name}'",
            $this->customPropertyAttributes($name),
        ]));
    }

    public function customPropertyAttributeName(string $name): string
    {
        return "{$this->name}[{$this->uuid}][custom_properties][$name]";
    }

    public function customPropertyAttributeValue(string $name)
    {
        $value = Arr::get($this->mediaItem['custom_properties'] ?? [], $name);

        return old($this->customPropertyErrorName($name), $value);
    }

    public function errorName(): string
    {
        return "media.{$this->uuid}";
    }

    public function propertyErrorName(string $name): string
    {
        return "media.{$this->uuid}.{$name}";
    }

    public function customPropertyErrorName(string $name): string
    {
        return "media.{$this->uuid}.custom_properties.$name";
    }

    public function downloadUrl(): string
    {
        $mediaModelClass = config('media-library.media_model');

        return $mediaModelClass::findByUuid($this->uuid)->getUrl();
    }

    public function createdAt(): Carbon
    {
        return $this->created_at ? Carbon::createFromTimestamp($this->created_at) : now();
    }
}
