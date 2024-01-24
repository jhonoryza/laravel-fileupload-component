<div>
    <link rel="stylesheet" href="{{ asset('vendor/file-upload-component/css/file-upload-component.css') }}"/>

    <!-- File Input UI -->
    @if($canUploadFile === true)

        <!-- Alpine Progress Bar Defined -->
        <div
                class="form-group"
                wire:ignore
                x-data="{ uploading: false, progress: 0 }"
                x-on:livewire-upload-start="uploading = true"
                x-on:livewire-upload-finish="uploading = false"
                x-on:livewire-upload-error="uploading = false"
                x-on:livewire-upload-progress="progress = $event.detail.progress"
        >
            <label for="{{ $name }}">{{ $label }}</label>
            <input id="{{ $name }}"
                   name="{{ $name }}"
                   type="file"
                   class="form-control-file"
                   wire:key="file-upload-{{ $name }}"
                   wire:model="files"
                    {{ $multiple ? 'multiple' : '' }}
            >
            @error('files.*') <span class="text-danger">{{ $message }}</span> @enderror

            <!-- Upload Progress Bar UI -->
            <div x-show="uploading">
                <div>Uploading...</div>
                <div>
                    <progress max="100" x-bind:value="progress"></progress>
                </div>
            </div>
        </div>

    @else
        <label for="{{ $name }}">{{ $label }}</label>
    @endif

    <!-- Uploaded Files Previews -->
    <div
        wire:init="loadMedia"
        class="mb-3 comp
        {{ $multiple ? 'comp-multiple' : 'comp-single' }}
        {{ count($items) == 0 ? 'comp-empty' : 'comp-filled' }}"
    >
        <ul class="{{ count($items) == 0 ? 'comp-hidden' : 'comp-items' }}">
            @foreach ($items as $index => $media)
                <li class="comp-item comp-item-row"
                    wire:key="item-{{ $index }}"
                >
                    <!-- Preview -->
                    <div class="comp-thumb">
                        <a style="cursor: pointer"
                           href="{{ $media->preview_url }}" target="_blank">
                            <img
                                    src="{{ $media->preview_url }}"
                                    alt="Preview"
                                    class="mt-2 comp-thumb-img"
                            >
                        </a>
                    </div>

                    <!-- Properties -->
                    <div class="comp-properties comp-properties-fixed">
                        <div class="comp-property">
                            {{ strtoupper($media->extension) }}
                        </div>
                        @if ($media->size)
                            <div class="comp-property">
                                {{ \Spatie\MediaLibrary\Support\File::getHumanReadableSize($media->size) }}
                            </div>
                        @endif
                        <div class="comp-property">
                            <a href="{{ $media->preview_url }}"
                               download
                               class="comp-text-link">
                                Download
                            </a>
                        </div>
                        <div class="comp-property mt-1" wire:click="remove('{{ $media->uuid }}')">
                            <span class="comp-text-link">
                                Remove
                            </span>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>

