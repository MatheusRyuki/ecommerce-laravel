@push('vite')
    @vite(['resources/js/admin-product-form.js'])
@endpush

@php
    $fieldClass = 'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500';
    $selectedColors = old('colors', []);
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-blue-600 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-200">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">{{ __('Create Product') }}</h3>
                    <a id="go-back-products" href="{{ route('admin.products.index') }}" class="relative z-10 inline-flex items-center justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        {{ __('Go Back') }}
                    </a>
                </div>

                <form id="admin-product-form" method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-5">
                    @csrf

                    @if ($errors->any())
                        <x-admin-alert class="mb-4" :status="__('Please correct the highlighted fields.')" tone="danger" :dismissible="false" />
                    @endif
                    <x-admin-alert class="mb-4" :status="session('status') === 'product-created' ? __('Product created successfully.') : session('status')" />

                    <div>
                        <label for="images" class="block text-sm font-medium text-gray-700">{{ __('Images') }}</label>
                        <input id="images" name="images[]" type="file" accept="image/jpeg,image/png,image/webp" multiple
                            class="{{ $fieldClass }} file:mr-3 file:rounded-md file:border-0 file:bg-gray-100 file:px-3 file:py-1.5 file:text-sm">
                        <p class="mt-1 text-xs text-gray-500">{{ __('JPEG, PNG or WebP. 1 to 5 files, up to 2048 KB each. Files must be selected again after a validation error.') }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('images')" />
                        <x-input-error class="mt-2" :messages="$errors->get('images.*')" />
                    </div>

                    @include('admin.products.partials.fields', [
                        'colors' => $colors,
                        'selectedColors' => $selectedColors,
                        'name' => old('name'),
                        'price' => old('price'),
                        'shortDescription' => old('short_description'),
                        'qty' => old('qty'),
                        'sku' => old('sku'),
                        'description' => old('description'),
                    ])

                    <div>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            {{ __('Create Product') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
